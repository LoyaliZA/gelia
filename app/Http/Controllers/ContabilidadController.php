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
use Barryvdh\DomPDF\Facade\Pdf;

class ContabilidadController extends Controller
{
    protected $calcService;

    public function __construct(PlatformCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    public function index(Request $request)
    {
        $platforms = \App\Models\Platform::where('active', true)->get();

        $mesActual = $request->input('mes', date('m'));
        $anioActual = $request->input('anio', date('Y'));

        $pedidos = \App\Models\ContabilidadPedido::with(['detalles', 'platform'])
            ->whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anioActual)
            ->orderBy('fecha_salida', 'desc')
            ->get();

        $comisionesPorPlataforma = $pedidos->groupBy('platform.name')->map(function ($row) {
            return $row->sum('comision_plataforma');
        });

        // 1. NUEVO CÓDIGO: Agrupamos los pedidos por fecha para inyectarlos a la gráfica
        $datosGrafica = $pedidos->groupBy(function($date) {
            return \Carbon\Carbon::parse($date->fecha_salida)->format('Y-m-d');
        })->map(function ($row) {
            return [
                'utilidad' => $row->sum('utilidad_total'),
                'venta' => $row->sum('venta_total')
            ];
        });

        // 2. CORRECCIÓN: Agregamos 'datosGrafica' dentro de la función compact()
        return view('contabilidad.index', compact(
            'platforms', 
            'pedidos', 
            'mesActual', 
            'anioActual', 
            'comisionesPorPlataforma', 
            'datosGrafica'
        ));
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
            
            // CORRECCIÓN: Asignación estándar con un solo signo de dólar
            $tipoTransaccion = $request->input('tipo_transaccion', 'venta');

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

        $platformsDB = Platform::all()->keyBy(function($item) {
            return strtolower(str_replace(' ', '', $item->name));
        });

        $pedidosAgrupados = [];

        try {
            (new FastExcel)->import($rutaCompleta, function ($linea) use (&$pedidosAgrupados) {
                $numPedido = trim((string)($linea['Pedido'] ?? ''));
                if ($numPedido === '' || $numPedido === 'Ej: 28098') return;

                if (!isset($pedidosAgrupados[$numPedido])) {
                    
                    // --- LÓGICA DE FECHA ROBUSTA ---
                    $fechaRaw = trim((string)($linea['Fecha'] ?? ''));
                    $fechaFinal = date('Y-m-d'); // Fallback hoy

                    try {
                        if ($linea['Fecha'] instanceof \DateTime) {
                            $fechaFinal = $linea['Fecha']->format('Y-m-d');
                        } else {
                            // Intentamos parsear el formato DD/MM/YY que mencionas
                            $fechaFinal = \Carbon\Carbon::parse(str_replace('/', '-', $fechaRaw))->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        Log::warning("GELIA: No se pudo parsear fecha $fechaRaw, usando fecha actual.");
                    }

                    // --- FUNCIÓN DE LIMPIEZA DE MONEDA (Signos, comas, espacios) ---
                    $limpiarNumero = function($valor) {
                        if (!$valor) return 0.0;
                        $soloNumero = preg_replace('/[^0-9.]/', '', (string)$valor);
                        return (float) $soloNumero;
                    };

                    $pedidosAgrupados[$numPedido] = [
                        'fecha' => $fechaFinal,
                        'tipo_transaccion' => strtolower(trim((string)($linea['Tipo_Transaccion'] ?? 'venta'))),
                        'plataforma' => trim((string)$linea['Plataforma'] ?? ''),
                        'venta_total' => $limpiarNumero($linea['Venta_Total'] ?? 0),
                        'envio_cobrado' => $limpiarNumero($linea['Envio_Cobrado'] ?? 0),
                        'comision_cobrada' => $limpiarNumero($linea['Comision_Cobrada'] ?? 0),
                        'envio_pagado_cliente' => strtoupper(trim((string)($linea['Envio_Pagado_Cliente'] ?? 'NO'))) === 'SI',
                        'productos' => []
                    ];
                }

                $limpiarNumero = function($valor) {
                    $soloNumero = preg_replace('/[^0-9.]/', '', (string)$valor);
                    return (float) $soloNumero;
                };

                $pedidosAgrupados[$numPedido]['productos'][] = [
                    'sku' => trim((string)($linea['SKU'] ?? '')),
                    'piezas' => (int)($linea['Piezas'] ?? 1),
                    'precio_pagina' => $limpiarNumero($linea['Precio_Pagina'] ?? 0)
                ];
            });

            DB::beginTransaction();
            $conteoRegistrados = 0;

            foreach ($pedidosAgrupados as $numPedido => $data) {
                $platKey = strtolower(str_replace(' ', '', $data['plataforma']));
                $platform_id = isset($platformsDB[$platKey]) ? $platformsDB[$platKey]->id : $platformsDB->first()->id;

                $costoProductos = 0.0;
                foreach($data['productos'] as $prod) {
                    $costoProductos += ($prod['precio_pagina'] * $prod['piezas']);
                }

                $gastoEnvioEmpresa = $data['envio_pagado_cliente'] ? 0.0 : $data['envio_cobrado'];
                
                // CORRECCIÓN: Ahora verificamos si la cadena contiene la palabra 'venta' 
                // para que 'Venta Normal' sea procesada correctamente como ganancia.
                if (str_contains($data['tipo_transaccion'], 'venta')) {
                    $utilidadFinal = $data['venta_total'] - $costoProductos - $gastoEnvioEmpresa - $data['comision_cobrada'];
                } else {
                    // Es Reembolso o Contracargo
                    $utilidadFinal = -($costoProductos + $gastoEnvioEmpresa + $data['comision_cobrada']);
                }

                $pedido = ContabilidadPedido::updateOrCreate(
                    ['numero_pedido' => $numPedido],
                    [
                        'fecha_salida' => $data['fecha'],
                        'tipo_transaccion' => $data['tipo_transaccion'],
                        'platform_id' => $platform_id,
                        'venta_total' => $data['venta_total'],
                        'costo_envio' => $data['envio_cobrado'],
                        'envio_pagado_cliente' => $data['envio_pagado_cliente'],
                        'comision_plataforma' => $data['comision_cobrada'],
                        'utilidad_total' => $utilidadFinal,
                    ]
                );

                $pedido->detalles()->delete();

                foreach($data['productos'] as $prod) {
                    ContabilidadPedidoDetalle::create([
                        'contabilidad_pedido_id' => $pedido->id,
                        'sku' => $prod['sku'],
                        'piezas' => $prod['piezas'],
                        'nombre_producto' => 'Carga Masiva (SKU: '.$prod['sku'].')',
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
            Log::error('GELIA (Conta Historico) - Error: ' . $e->getMessage());
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
        $query = ContabilidadPedido::with(['platform', 'detalles']); // Aseguramos traer detalles

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
        $ganancias = $pedidos->where('utilidad_total', '>=', 0)->sum('utilidad_total');
        $perdidas = $pedidos->where('utilidad_total', '<', 0)->sum('utilidad_total');
        $enviosEmpresa = $pedidos->where('envio_pagado_cliente', false)->sum('costo_envio');

        // NUEVOS CÁLCULOS
        $notasAE = 0;
        foreach ($pedidos as $p) {
            foreach ($p->detalles as $d) {
                $notasAE += ($d->precio_unitario * $d->piezas);
            }
        }
        
        $margen = $ventas > 0 ? ($pedidos->sum('utilidad_total') / $ventas) * 100 : 0;
        $enviosClientesCount = $pedidos->where('envio_pagado_cliente', true)->count();
        $enviosClientesMonto = $pedidos->where('envio_pagado_cliente', true)->sum('costo_envio');

        $comisionesPlataforma = $pedidos->groupBy('platform.name')->map->sum('comision_plataforma');

        $grafica = $pedidos->groupBy(function ($date) {
            return \Carbon\Carbon::parse($date->fecha_salida)->format('d/m/Y');
        })->map(function ($row) {
            return ['venta' => $row->sum('venta_total'), 'utilidad' => $row->sum('utilidad_total')];
        });

        return response()->json([
            'kpis' => compact('ventas', 'comisiones', 'ganancias', 'perdidas', 'enviosEmpresa', 'notasAE', 'margen', 'enviosClientesCount', 'enviosClientesMonto'),
            'plataformas' => $comisionesPlataforma,
            'grafica' => $grafica
        ]);
    }

    /**
     * Actualiza las comisiones de las plataformas desde el modal de configuración.
     */
    public function actualizarComisiones(Request $request)
    {
        $request->validate([
            'plataformas' => 'required|array',
            'plataformas.*.id' => 'required|exists:platforms,id',
            'plataformas.*.commission_percent' => 'required|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();
            foreach ($request->plataformas as $plat) {
                Platform::where('id', $plat['id'])->update([
                    'commission_percent' => $plat['commission_percent']
                ]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Comisiones actualizadas.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error actualizando comisiones: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar.'], 500);
        }
    }

    /**
     * Actualización rápida de montos financieros de un pedido.
     */
    public function actualizarPedidoRapido(Request $request, $id)
    {
        $request->validate([
            'tipo_transaccion' => 'required|string',
            'platform_id' => 'required|exists:platforms,id',
            'venta_total' => 'required|numeric',
            'costo_envio' => 'required|numeric',
            'comision_plataforma' => 'required|numeric',
            'productos' => 'required|array', // Nuevo requerimiento
            'productos.*.id' => 'required|exists:contabilidad_pedido_detalles,id',
            'productos.*.piezas' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();
            $pedido = ContabilidadPedido::with('detalles')->findOrFail($id);

            if ($pedido->bloqueado) {
                return response()->json(['success' => false, 'message' => 'El periodo está bloqueado.'], 403);
            }

            $costoProductos = 0.0;

            // Actualizamos la cantidad de cada producto y recalculamos su subtotal
            foreach ($request->productos as $prodReq) {
                $detalle = $pedido->detalles->where('id', $prodReq['id'])->first();
                if ($detalle) {
                    $detalle->piezas = $prodReq['piezas'];
                    // El precio unitario original se mantiene intacto, solo cambia el subtotal
                    $detalle->subtotal = $detalle->precio_unitario * $prodReq['piezas'];
                    $detalle->save();
                    
                    $costoProductos += $detalle->subtotal;
                }
            }

            $gastoEnvio = (float) $request->costo_envio;
            $comision = (float) $request->comision_plataforma;
            $venta = (float) $request->venta_total;
            $tipo = strtolower($request->tipo_transaccion);

            // Recálculo de utilidad con el nuevo costo de productos
            if (str_contains($tipo, 'venta')) {
                $utilidad = $venta - $costoProductos - $gastoEnvio - $comision;
            } else {
                $utilidad = -($costoProductos + $gastoEnvio + $comision);
            }

            $pedido->update([
                'tipo_transaccion' => $tipo,
                'platform_id' => $request->platform_id,
                'venta_total' => $venta,
                'costo_envio' => $gastoEnvio,
                'comision_plataforma' => $comision,
                'utilidad_total' => $utilidad
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pedido actualizado correctamente.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error actualizando pedido {$id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Recibe los datos y gráficas en Base64 para generar el reporte estático.
     */
    public function generarReportePDF(Request $request)
    {
        $request->validate([
            'img_plataformas' => 'required|string',
            'img_ventas' => 'required|string',
            'periodo' => 'required|string',
            'filtro' => 'required|string'
        ]);

        $filtro = $request->input('filtro');
        $query = ContabilidadPedido::with(['platform', 'detalles']); 

        if ($filtro === 'mes') {
            $query->whereMonth('fecha_salida', $request->input('mes'))->whereYear('fecha_salida', $request->input('anio'));
        } elseif ($filtro === 'dia') {
            $query->whereDate('fecha_salida', $request->input('fecha'));
        } elseif ($filtro === 'anio') {
            $query->whereYear('fecha_salida', $request->input('anio'));
        } elseif ($filtro === 'custom') {
            $query->whereBetween('fecha_salida', [$request->input('inicio'), $request->input('fin')]);
        }

        $pedidos = $query->orderBy('fecha_salida', 'desc')->get(); // Traemos de más reciente a antiguo

        // 1. Cálculos de KPIs Nativos para el PDF
        $kpis = [
            'ventas' => $pedidos->sum('venta_total'),
            'ganancias' => $pedidos->where('utilidad_total', '>=', 0)->sum('utilidad_total'),
            'perdidas' => $pedidos->where('utilidad_total', '<', 0)->sum('utilidad_total'),
            'comisiones' => $pedidos->sum('comision_plataforma'),
            'envios_empresa' => $pedidos->where('envio_pagado_cliente', false)->sum('costo_envio'),
            'envios_clientes_count' => $pedidos->where('envio_pagado_cliente', true)->count(),
            'envios_clientes_monto' => $pedidos->where('envio_pagado_cliente', true)->sum('costo_envio'),
            'notas_ae' => 0
        ];

        foreach ($pedidos as $p) {
            foreach ($p->detalles as $d) {
                $kpis['notas_ae'] += ($d->precio_unitario * $d->piezas);
            }
        }
        $kpis['margen'] = $kpis['ventas'] > 0 ? ($pedidos->sum('utilidad_total') / $kpis['ventas']) * 100 : 0;

        // 2. Análisis Avanzado de Plataformas
        $platStats = [];
        $totalComisiones = $kpis['comisiones'];
        
        foreach($pedidos->groupBy('platform.name') as $nombre => $grupo) {
            $comisionPlataforma = $grupo->sum('comision_plataforma');
            $cantidad = $grupo->count();
            $platStats[$nombre] = [
                'comision' => $comisionPlataforma,
                'cantidad' => $cantidad,
                'porcentaje' => $totalComisiones > 0 ? ($comisionPlataforma / $totalComisiones) * 100 : 0,
                'promedio' => $cantidad > 0 ? ($comisionPlataforma / $cantidad) : 0,
            ];
        }

        $platInsights = [
            'mas_usada' => collect($platStats)->sortByDesc('cantidad')->keys()->first() ?? 'N/A',
            'mas_cara' => collect($platStats)->sortByDesc('promedio')->keys()->first() ?? 'N/A',
            'menos_relevante' => collect($platStats)->sortBy('cantidad')->keys()->first() ?? 'N/A'
        ];

        // 3. Ordenamiento Estratégico: Contracargos y Reembolsos primero
        $anomalias = $pedidos->filter(fn($p) => !str_contains(strtolower($p->tipo_transaccion), 'venta'));
        $ventasNorm = $pedidos->filter(fn($p) => str_contains(strtolower($p->tipo_transaccion), 'venta'));
        $pedidosOrdenados = $anomalias->merge($ventasNorm);

        $data = [
            'fechaImpresion' => date('d/m/Y'),
            'periodo' => $request->periodo,
            'kpis' => $kpis,
            'platStats' => $platStats,
            'platInsights' => $platInsights,
            'imgPlataformas' => $request->img_plataformas,
            'imgVentas' => $request->img_ventas,
            'pedidos' => $pedidosOrdenados,
            'logoBellaroma' => base64_encode(file_get_contents(public_path('assets/BELLAROMA-LOGOTIPO-04.png')))
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('contabilidad.partials.reporte_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('Reporte_Bellaroma_'.$request->periodo.'.pdf');
    }
}
