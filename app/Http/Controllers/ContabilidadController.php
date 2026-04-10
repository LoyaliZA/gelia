<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContabilidadPedido;
use App\Models\ContabilidadPedidoDetalle;
use App\Models\Platform;
use App\Services\PlatformCalculationService;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContabilidadController extends Controller
{
    protected $calcService;

    public function __construct(PlatformCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    public function index(Request $request)
    {
        $platforms = Platform::where('active', true)->get();

        $mesActual = $request->input('mes', date('m'));
        $anioActual = $request->input('anio', date('Y'));

        $pedidos = ContabilidadPedido::with(['detalles', 'platform'])
            ->whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anioActual)
            ->orderBy('fecha_salida', 'desc')
            ->get();

        // Extraemos las comisiones agrupadas por plataforma para la gráfica de Dona
        $comisionesPorPlataforma = $pedidos->groupBy('platform.name')->map(function ($row) {
            return $row->sum('comision_plataforma');
        });

        return view('contabilidad.index', compact('platforms', 'pedidos', 'mesActual', 'anioActual', 'comisionesPorPlataforma'));
    }

    /**
     * Genera un reporte dinámico en Excel según el mes filtrado.
     */
    public function exportarReporte(Request $request)
    {
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));

        $pedidos = ContabilidadPedido::with(['detalles', 'platform'])
            ->whereMonth('fecha_salida', $mes)
            ->whereYear('fecha_salida', $anio)
            ->orderBy('fecha_salida', 'desc')
            ->get();

        $nombreArchivo = "Reporte_Contabilidad_Bellaroma_{$mes}_{$anio}.xlsx";

        // Mapeamos los datos para que el Excel salga limpio y legible
        $datosGenerador = function () use ($pedidos) {
            foreach ($pedidos as $pedido) {
                yield [
                    'Fecha' => $pedido->fecha_salida->format('Y-m-d'),
                    'Pedido' => $pedido->numero_pedido,
                    'Plataforma' => $pedido->platform->name,
                    'Venta Total ($)' => $pedido->venta_total,
                    'Costo Envío ($)' => $pedido->costo_envio,
                    'Comisión ($)' => $pedido->comision_plataforma,
                    'Utilidad Neta ($)' => $pedido->utilidad_total,
                    'Total SKUs' => $pedido->detalles->count(),
                    'Piezas Vendidas' => $pedido->detalles->sum('piezas'),
                ];
            }
        };

        return (new FastExcel($datosGenerador()))->download($nombreArchivo);
    }

    public function procesarLista(Request $request)
    {
        $request->validate(['lista_resurtido' => 'required|file|mimes:xlsx,csv']);
        $diccionario = [];
        $archivo = $request->file('lista_resurtido');
        $nombreTemp = 'temp_conta_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $rutaCompleta = sys_get_temp_dir() . '/' . $nombreTemp;
        $archivo->move(sys_get_temp_dir(), $nombreTemp);

        try {
            (new FastExcel)->import($rutaCompleta, function ($linea) use (&$diccionario) {
                $sku = trim((string)($linea['SKU'] ?? ''));
                if ($sku !== '') {
                    $precioBase = $linea['Plataformas'] ?? $linea['Lista3'] ?? $linea['PG'] ?? 0;

                    $diccionario[$sku] = [
                        'nombre' => $linea['Descripcion'] ?? 'Producto Desconocido',
                        'precio' => (float)$precioBase
                    ];
                }
            });
        } catch (\Throwable $e) {
            Log::error('GELIA (Conta) - Error procesando Excel: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Fallo al leer las columnas del Excel.'], 500);
        } finally {
            if (file_exists($rutaCompleta)) unlink($rutaCompleta);
        }

        return response()->json(['success' => true, 'data' => $diccionario]);
    }

    public function guardarPedido(Request $request)
    {
        $request->validate([
            'fecha_salida' => 'required|date',
            'numero_pedido' => 'required|string',
            'platform_id' => 'required|exists:platforms,id',
            'venta_total' => 'required|numeric',
            'costo_envio' => 'required|numeric',
            'envio_pagado_cliente' => 'required|boolean',
            'comision_real' => 'nullable|numeric',
            'productos' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            // Usamos findOrFail para asegurar el objeto
            $platform = Platform::findOrFail($request->platform_id);

            // Casteo explícito a float para evitar TypeErrors en matemáticas estrictas
            $ventaTotal = (float) $request->venta_total;
            $costoEnvio = (float) $request->costo_envio;

            $costoProductos = 0.0;
            foreach ($request->productos as $prod) {
                $costoProductos += ((float) $prod['precio'] * (int) $prod['piezas']);
            }

            $gastoEnvioEmpresa = $request->boolean('envio_pagado_cliente') ? 0.0 : $costoEnvio;

            $finanzas = $this->calcService->calculateTransaction(
                $ventaTotal,
                $costoProductos,
                $gastoEnvioEmpresa,
                $platform
            );

            // Validamos la comisión sobreescrita
            $comisionFinal = $request->filled('comision_real') ? (float) $request->comision_real : (float) $finanzas['total_commission'];
            $$tipoTransaccion = $request->input('tipo_transaccion', 'venta');

            if ($tipoTransaccion === 'venta') {
                $utilidadFinal = $ventaTotal - $costoProductos - $gastoEnvioEmpresa - $comisionFinal;
            } else {
                // Es Reembolso o Contracargo: No hay ganancia de venta, todo es pérdida
                $utilidadFinal = - ($costoProductos + $gastoEnvioEmpresa + $comisionFinal);
            }

            $pedido = ContabilidadPedido::create([
                'fecha_salida' => $request->fecha_salida,
                'numero_pedido' => $request->numero_pedido,
                'tipo_transaccion' => $tipoTransaccion,
                'platform_id' => $request->platform_id,
                'venta_total' => $ventaTotal,
                'costo_envio' => $costoEnvio,
                'envio_pagado_cliente' => $request->envio_pagado_cliente,
                'comision_plataforma' => $comisionFinal,
                'utilidad_total' => $utilidadFinal,
            ]);

            foreach ($request->productos as $prod) {
                ContabilidadPedidoDetalle::create([
                    'contabilidad_pedido_id' => $pedido->id,
                    'sku' => $prod['sku'],
                    'piezas' => (int) $prod['piezas'],
                    'nombre_producto' => $prod['nombre'],
                    'precio_unitario' => (float) $prod['precio'],
                    'subtotal' => (float) $prod['precio'] * (int) $prod['piezas']
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pedido registrado con éxito.']);
        } catch (\Throwable $e) {
            // Throwable atrapa errores fatales (TypeError) además de Excepciones estándar
            DB::rollBack();
            Log::error('GELIA (Conta) - Error: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Fallo interno: ' . $e->getMessage()], 500);
        }
    }

    public function eliminarPedido($id)
    {
        try {
            $pedido = ContabilidadPedido::findOrFail($id);
            if (!$pedido->bloqueado) {
                $pedido->delete();
                return response()->json(['success' => true]);
            }
            return response()->json(['success' => false, 'message' => 'El periodo está bloqueado.'], 403);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Genera un archivo CSV al vuelo con las columnas exactas para la carga masiva.
     */
    public function descargarPlantilla()
    {
        // 1. AÑADIMOS 'Tipo_Transaccion' A LOS ENCABEZADOS
        $headers = ['Fecha', 'Pedido', 'Tipo_Transaccion', 'Plataforma', 'SKU', 'Piezas', 'Precio_Pagina', 'Venta_Total', 'Envio_Cobrado', 'Comision_Cobrada', 'Envio_Pagado_Cliente'];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            // Fila de ejemplo (Agregamos 'Venta' en la 3ra posición)
            fputcsv($file, [date('Y-m-d'), 'Ej: 28098', 'Venta', 'Mercado Pago', '12345', '1', '1500.00', '1800.00', '99.00', '45.50', 'SI']);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=Plantilla_Historico_Bellaroma.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    /**
     * Procesa el archivo histórico, agrupa los pedidos descombinados y calcula utilidades.
     */
    public function importarHistorico(Request $request)
    {
        $request->validate(['archivo_historico' => 'required|file|mimes:xlsx,csv']);

        $archivo = $request->file('archivo_historico');
        $nombreTemp = 'temp_hist_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $rutaCompleta = sys_get_temp_dir() . '/' . $nombreTemp;
        $archivo->move(sys_get_temp_dir(), $nombreTemp);

        // Diccionario de plataformas para búsqueda rápida sin importar mayúsculas/espacios
        $platformsDB = Platform::all()->keyBy(function ($item) {
            return strtolower(str_replace(' ', '', $item->name));
        });

        $pedidosAgrupados = [];

        try {
            (new FastExcel)->import($rutaCompleta, function ($linea) use (&$pedidosAgrupados) {
                $numPedido = trim((string)($linea['Pedido'] ?? ''));
                if ($numPedido === '' || $numPedido === 'Ej: 28098') return;

                // Si es la primera vez que vemos este número de pedido, guardamos sus datos globales
                if (!isset($pedidosAgrupados[$numPedido])) {

                    // Parseo de fecha flexible (Carbon o String)
                    $fecha = isset($linea['Fecha']) && $linea['Fecha'] instanceof \DateTime
                        ? $linea['Fecha']->format('Y-m-d')
                        : date('Y-m-d', strtotime(str_replace('/', '-', $linea['Fecha'])));

                    // Reemplaza la función $limpiarNumero por esta en las dos partes que aparece:
                    $limpiarNumero = function ($valor) {
                        if (!$valor) return 0.0;
                        // Esto quita $, espacios, letras y comas, dejando solo el número y el punto decimal
                        $soloNumero = preg_replace('/[^0-9.]/', '', (string)$valor);
                        return (float) $soloNumero;
                    };

                    $pedidosAgrupados[$numPedido] = [
                        'fecha' => $fecha,
                        // 2. EXTRAEMOS EL TIPO DE TRANSACCIÓN DESDE EL EXCEL AQUÍ
                        'tipo_transaccion' => strtolower(trim((string)($linea['Tipo_Transaccion'] ?? 'venta'))),
                        'plataforma' => trim((string)$linea['Plataforma'] ?? ''),
                        'venta_total' => $limpiarNumero($linea['Venta_Total'] ?? 0),
                        'envio_cobrado' => $limpiarNumero($linea['Envio_Cobrado'] ?? 0),
                        'comision_cobrada' => $limpiarNumero($linea['Comision_Cobrada'] ?? 0),
                        'envio_pagado_cliente' => strtoupper(trim((string)($linea['Envio_Pagado_Cliente'] ?? 'NO'))) === 'SI',
                        'productos' => []
                    ];
                }

                // Reemplaza la función $limpiarNumero por esta en las dos partes que aparece:
                $limpiarNumero = function ($valor) {
                    if (!$valor) return 0.0;
                    // Esto quita $, espacios, letras y comas, dejando solo el número y el punto decimal
                    $soloNumero = preg_replace('/[^0-9.]/', '', (string)$valor);
                    return (float) $soloNumero;
                };

                // Insertamos el SKU a la matriz de productos de ese pedido
                $pedidosAgrupados[$numPedido]['productos'][] = [
                    'sku' => trim((string)($linea['SKU'] ?? '')),
                    'piezas' => (int)($linea['Piezas'] ?? 1),
                    'precio_pagina' => $limpiarNumero($linea['Precio_Pagina'] ?? 0)
                ];
            });

            // Una vez agrupados, procesamos contra la Base de Datos
            DB::beginTransaction();

            $conteoRegistrados = 0;

            foreach ($pedidosAgrupados as $numPedido => $data) {
                // Buscamos el ID de la plataforma
                $platKey = strtolower(str_replace(' ', '', $data['plataforma']));
                $platform_id = isset($platformsDB[$platKey]) ? $platformsDB[$platKey]->id : $platformsDB->first()->id;

                $costoProductos = 0.0;
                foreach ($data['productos'] as $prod) {
                    $costoProductos += ($prod['precio_pagina'] * $prod['piezas']);
                }

                $gastoEnvioEmpresa = $data['envio_pagado_cliente'] ? 0.0 : $data['envio_cobrado'];

                // 3. APLICAMOS LA REGLA MATEMÁTICA DE GANANCIA O PÉRDIDA AQUÍ
                if ($data['tipo_transaccion'] === 'venta') {
                    $utilidadFinal = $data['venta_total'] - $costoProductos - $gastoEnvioEmpresa - $data['comision_cobrada'];
                } else {
                    // Es Reembolso o Contracargo: No hay ganancia de venta, todo es pérdida
                    $utilidadFinal = - ($costoProductos + $gastoEnvioEmpresa + $data['comision_cobrada']);
                }

                $pedido = ContabilidadPedido::updateOrCreate(
                    ['numero_pedido' => $numPedido], // Evita duplicados
                    [
                        'fecha_salida' => $data['fecha'],
                        // 4. GUARDAMOS EL TIPO DE TRANSACCIÓN EN LA BD AQUÍ
                        'tipo_transaccion' => $data['tipo_transaccion'],
                        'platform_id' => $platform_id,
                        'venta_total' => $data['venta_total'],
                        'costo_envio' => $data['envio_cobrado'],
                        'envio_pagado_cliente' => $data['envio_pagado_cliente'],
                        'comision_plataforma' => $data['comision_cobrada'],
                        'utilidad_total' => $utilidadFinal,
                    ]
                );

                // Limpiamos detalles viejos por si es una re-importación correctiva
                $pedido->detalles()->delete();

                foreach ($data['productos'] as $prod) {
                    ContabilidadPedidoDetalle::create([
                        'contabilidad_pedido_id' => $pedido->id,
                        'sku' => $prod['sku'],
                        'piezas' => $prod['piezas'],
                        'nombre_producto' => 'Carga Masiva (SKU: ' . $prod['sku'] . ')',
                        'precio_unitario' => $prod['precio_pagina'],
                        'subtotal' => $prod['precio_pagina'] * $prod['piezas']
                    ]);
                }

                $conteoRegistrados++;
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Se importaron $conteoRegistrados pedidos exitosamente."]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('GELIA (Conta Historico) - Error: ' . $e->getMessage() . ' Línea: ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Fallo al procesar el archivo. Verifique el formato.'], 500);
        } finally {
            if (file_exists($rutaCompleta)) unlink($rutaCompleta);
        }
    }

    /**
     * API para alimentar el Dashboard Avanzado de forma dinámica.
     */
    public function getDashboardData(Request $request)
    {
        $filtro = $request->input('filtro', 'mes');
        $query = ContabilidadPedido::with('platform');

        if ($filtro === 'mes') {
            $query->whereMonth('fecha_salida', $request->input('mes', date('m')))
                ->whereYear('fecha_salida', $request->input('anio', date('Y')));
        } elseif ($filtro === 'dia') {
            $query->whereDate('fecha_salida', $request->input('fecha', date('Y-m-d')));
        } elseif ($filtro === 'anio') {
            $query->whereYear('fecha_salida', $request->input('anio', date('Y')));
        } elseif ($filtro === 'custom') {
            $query->whereBetween('fecha_salida', [$request->input('inicio'), $request->input('fin')]);
        }

        $pedidos = $query->orderBy('fecha_salida', 'asc')->get();

        $ventas = $pedidos->sum('venta_total');
        $comisiones = $pedidos->sum('comision_plataforma');
        // Separación estricta de utilidades
        $ganancias = $pedidos->where('utilidad_total', '>=', 0)->sum('utilidad_total');
        $perdidas = $pedidos->where('utilidad_total', '<', 0)->sum('utilidad_total');

        $comisionesPlataforma = $pedidos->groupBy('platform.name')->map->sum('comision_plataforma');

        $grafica = $pedidos->groupBy(function ($date) {
            return \Carbon\Carbon::parse($date->fecha_salida)->format('d/m/Y');
        })->map(function ($row) {
            return [
                'venta' => $row->sum('venta_total'),
                'utilidad' => $row->sum('utilidad_total')
            ];
        });

        return response()->json([
            'kpis' => compact('ventas', 'comisiones', 'ganancias', 'perdidas'),
            'plataformas' => $comisionesPlataforma,
            'grafica' => $grafica
        ]);
    }
}
