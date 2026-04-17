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

        // CORRECCIÓN: Mapeamos la colección directamente en lugar de usar un Generador (yield)
        $datos = $pedidos->map(function ($pedido) {
            return [
                'Fecha' => $pedido->fecha_salida->format('Y-m-d'),
                'Pedido' => $pedido->numero_pedido,
                'Cliente' => $pedido->cliente_nombre ?? 'Sin Nombre',
                'Plataforma' => $pedido->platform->name,
                'Transacción' => ucfirst($pedido->tipo_transaccion),
                'Venta Total ($)' => $pedido->venta_total,
                'Costo Envío ($)' => $pedido->costo_envio,
                'Comisión Plataforma ($)' => $pedido->comision_plataforma,
                'Comisión Retiro Banco ($)' => $pedido->comision_transferencia,
                'Utilidad Final Neta ($)' => $pedido->utilidad_total,
                'Estatus Pago' => strtoupper($pedido->estatus_pago),
                'Total SKUs' => $pedido->detalles->count(),
                'Piezas Vendidas' => $pedido->detalles->sum('piezas'),
            ];
        });

        return (new \Rap2hpoutre\FastExcel\FastExcel($datos))->download($nombreArchivo);
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
            'cliente_nombre' => 'nullable|string', // <-- CAMBIO AQUÍ
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
                'cliente_nombre' => $request->cliente_nombre,
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
        // 1. AÑADIMOS 'Cliente' A LOS ENCABEZADOS Y AJUSTAMOS EL ORDEN
        $headers = [
            'Fecha', 
            'Pedido', 
            'Cliente', // NUEVO CAMPO
            'Tipo_Transaccion', 
            'Plataforma', 
            'SKU', 
            'Piezas', 
            'Precio_Pagina', 
            'Venta_Total', 
            'Envio_Cobrado', 
            'Comision_Cobrada', 
            'Envio_Pagado_Cliente'
        ];

        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            // Fila de ejemplo actualizada
            fputcsv($file, [
                date('d/m/Y'), 
                '28098', 
                'Ej: Jair Treviño', // Ejemplo de cliente
                'Venta', 
                'Mercado Pago', 
                '12345', 
                '1', 
                '1500.00', 
                '1800.00', 
                '99.00', 
                '45.50', 
                'SI'
            ]);
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
     * Procesa el archivo histórico, permitiendo tanto creación nueva como actualización parcial inteligente.
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
                if ($numPedido === '' || $numPedido === '28098' || str_contains($numPedido, 'Ej:')) return;

                if (!isset($pedidosAgrupados[$numPedido])) {
                    
                    // --- LÓGICA DE FECHA ROBUSTA ---
                    $fechaRaw = trim((string)($linea['Fecha'] ?? ''));
                    $fechaFinal = null; // Iniciamos en null para saber si venía vacía

                    if ($fechaRaw !== '') {
                        try {
                            if ($linea['Fecha'] instanceof \DateTime) {
                                $fechaFinal = $linea['Fecha']->format('Y-m-d');
                            } else {
                                $fechaFinal = \Carbon\Carbon::parse(str_replace('/', '-', $fechaRaw))->format('Y-m-d');
                            }
                        } catch (\Exception $e) {
                            Log::warning("GELIA: No se pudo parsear fecha $fechaRaw.");
                        }
                    }

                    $limpiarNumero = function($valor) {
                        if ($valor === null || $valor === '') return null; // Retornamos null si está vacío
                        $soloNumero = preg_replace('/[^0-9.]/', '', (string)$valor);
                        return (float) $soloNumero;
                    };

                    $pedidosAgrupados[$numPedido] = [
                        'fecha' => $fechaFinal,
                        'cliente_nombre' => trim((string)($linea['Cliente'] ?? $linea['Nombre del Cliente'] ?? '')),
                        'tipo_transaccion' => isset($linea['Tipo_Transaccion']) && $linea['Tipo_Transaccion'] !== '' ? strtolower(trim((string)$linea['Tipo_Transaccion'])) : null,
                        'plataforma' => trim((string)($linea['Plataforma'] ?? '')),
                        'venta_total' => $limpiarNumero($linea['Venta_Total'] ?? null),
                        'envio_cobrado' => $limpiarNumero($linea['Envio_Cobrado'] ?? null),
                        'comision_cobrada' => $limpiarNumero($linea['Comision_Cobrada'] ?? null),
                        'envio_pagado_cliente' => isset($linea['Envio_Pagado_Cliente']) && $linea['Envio_Pagado_Cliente'] !== '' ? (strtoupper(trim((string)$linea['Envio_Pagado_Cliente'])) === 'SI') : null,
                        'productos' => []
                    ];
                }

                $sku = trim((string)($linea['SKU'] ?? ''));
                // Solo agregamos el producto a la lista si realmente viene un SKU en el Excel
                if ($sku !== '') {
                    $limpiarNumero = function($valor) {
                        $soloNumero = preg_replace('/[^0-9.]/', '', (string)$valor);
                        return (float) $soloNumero;
                    };

                    $pedidosAgrupados[$numPedido]['productos'][] = [
                        'sku' => $sku,
                        'piezas' => (int)($linea['Piezas'] ?? 1),
                        'precio_pagina' => $limpiarNumero($linea['Precio_Pagina'] ?? 0)
                    ];
                }
            });

            DB::beginTransaction();
            $conteoRegistrados = 0;
            $conteoActualizados = 0;

            foreach ($pedidosAgrupados as $numPedido => $data) {
                // Buscamos si el pedido ya existe
                $pedidoExistente = ContabilidadPedido::where('numero_pedido', $numPedido)->first();

                if ($pedidoExistente) {
                    // ==========================================
                    // FLUJO A: ACTUALIZACIÓN PARCIAL INTELIGENTE
                    // ==========================================
                    $updateData = [];

                    // Solo actualizamos el cliente si viene en el Excel
                    if ($data['cliente_nombre'] !== '') {
                        $updateData['cliente_nombre'] = $data['cliente_nombre'];
                    }

                    // Solo actualizamos la plataforma si viene una válida
                    if ($data['plataforma'] !== '') {
                        $platKey = strtolower(str_replace(' ', '', $data['plataforma']));
                        if (isset($platformsDB[$platKey])) {
                            $updateData['platform_id'] = $platformsDB[$platKey]->id;
                        }
                    }

                    // Actualizamos otros campos solo si no son null (es decir, venían en el Excel)
                    if ($data['fecha'] !== null) $updateData['fecha_salida'] = $data['fecha'];
                    if ($data['tipo_transaccion'] !== null) $updateData['tipo_transaccion'] = $data['tipo_transaccion'];
                    if ($data['venta_total'] !== null) $updateData['venta_total'] = $data['venta_total'];
                    if ($data['envio_cobrado'] !== null) $updateData['costo_envio'] = $data['envio_cobrado'];
                    if ($data['envio_pagado_cliente'] !== null) $updateData['envio_pagado_cliente'] = $data['envio_pagado_cliente'];
                    if ($data['comision_cobrada'] !== null) $updateData['comision_plataforma'] = $data['comision_cobrada'];

                    // Si se enviaron montos nuevos, recalculamos la utilidad. 
                    // Si no, la utilidad se queda intacta.
                    if (isset($updateData['venta_total']) || isset($updateData['comision_plataforma']) || isset($updateData['costo_envio']) || count($data['productos']) > 0) {
                        
                        // Usamos los datos nuevos si existen, si no, usamos los que ya tiene el pedido en BD
                        $ventaCalc = $updateData['venta_total'] ?? $pedidoExistente->venta_total;
                        $comisionCalc = $updateData['comision_plataforma'] ?? $pedidoExistente->comision_plataforma;
                        $envioCobradoCalc = $updateData['costo_envio'] ?? $pedidoExistente->costo_envio;
                        $envioPagadoClienteCalc = $updateData['envio_pagado_cliente'] ?? $pedidoExistente->envio_pagado_cliente;
                        $tipoTransaccionCalc = $updateData['tipo_transaccion'] ?? $pedidoExistente->tipo_transaccion;
                        
                        // Determinar el costo de los productos (Si llegaron productos nuevos, se recalculan, si no, se saca de la BD)
                        $costoProductos = 0.0;
                        if (count($data['productos']) > 0) {
                            foreach($data['productos'] as $prod) {
                                $costoProductos += ($prod['precio_pagina'] * $prod['piezas']);
                            }
                        } else {
                            $costoProductos = $pedidoExistente->detalles->sum('subtotal');
                        }

                        $gastoEnvioEmpresa = $envioPagadoClienteCalc ? 0.0 : $envioCobradoCalc;
                        
                        if (str_contains($tipoTransaccionCalc, 'venta')) {
                            $updateData['utilidad_total'] = $ventaCalc - $costoProductos - $gastoEnvioEmpresa - $comisionCalc;
                        } else {
                            $updateData['utilidad_total'] = -($costoProductos + $gastoEnvioEmpresa + $comisionCalc);
                        }
                    }

                    // Aplicamos la actualización solo con los campos que cambiaron
                    if (!empty($updateData)) {
                        $pedidoExistente->update($updateData);
                    }

                    // Solo borramos y recreamos los productos SI el Excel traía productos para este pedido
                    if (count($data['productos']) > 0) {
                        $pedidoExistente->detalles()->delete();
                        foreach($data['productos'] as $prod) {
                            ContabilidadPedidoDetalle::create([
                                'contabilidad_pedido_id' => $pedidoExistente->id,
                                'sku' => $prod['sku'],
                                'piezas' => $prod['piezas'],
                                'nombre_producto' => 'Carga Masiva (SKU: '.$prod['sku'].')',
                                'precio_unitario' => $prod['precio_pagina'],
                                'subtotal' => $prod['precio_pagina'] * $prod['piezas']
                            ]);
                        }
                    }
                    $conteoActualizados++;

                } else {
                    // ==========================================
                    // FLUJO B: CREACIÓN DE PEDIDO NUEVO
                    // ==========================================
                    
                    // Validaciones de seguridad: si es nuevo, necesita tener datos mínimos
                    $platKey = strtolower(str_replace(' ', '', $data['plataforma']));
                    $platform_id = isset($platformsDB[$platKey]) ? $platformsDB[$platKey]->id : $platformsDB->first()->id;

                    $costoProductos = 0.0;
                    foreach($data['productos'] as $prod) {
                        $costoProductos += ($prod['precio_pagina'] * $prod['piezas']);
                    }

                    $gastoEnvioEmpresa = ($data['envio_pagado_cliente'] ?? false) ? 0.0 : ($data['envio_cobrado'] ?? 0);
                    $tipoTransaccion = $data['tipo_transaccion'] ?? 'venta';
                    $ventaTotal = $data['venta_total'] ?? 0;
                    $comisionCobrada = $data['comision_cobrada'] ?? 0;
                    
                    if (str_contains($tipoTransaccion, 'venta')) {
                        $utilidadFinal = $ventaTotal - $costoProductos - $gastoEnvioEmpresa - $comisionCobrada;
                    } else {
                        $utilidadFinal = -($costoProductos + $gastoEnvioEmpresa + $comisionCobrada);
                    }

                    $nuevoPedido = ContabilidadPedido::create([
                        'fecha_salida' => $data['fecha'] ?? date('Y-m-d'),
                        'numero_pedido' => $numPedido,
                        'cliente_nombre' => $data['cliente_nombre'],
                        'tipo_transaccion' => $tipoTransaccion,
                        'platform_id' => $platform_id,
                        'venta_total' => $ventaTotal,
                        'costo_envio' => $data['envio_cobrado'] ?? 0,
                        'envio_pagado_cliente' => $data['envio_pagado_cliente'] ?? false,
                        'comision_plataforma' => $comisionCobrada,
                        'utilidad_total' => $utilidadFinal,
                        'estatus_pago' => 'transferido', // Históricos se asumen cobrados
                        'fecha_retiro' => $data['fecha'] ?? date('Y-m-d'),
                        'comision_transferencia' => 0
                    ]);

                    foreach($data['productos'] as $prod) {
                        ContabilidadPedidoDetalle::create([
                            'contabilidad_pedido_id' => $nuevoPedido->id,
                            'sku' => $prod['sku'],
                            'piezas' => $prod['piezas'],
                            'nombre_producto' => 'Carga Masiva (SKU: '.$prod['sku'].')',
                            'precio_unitario' => $prod['precio_pagina'],
                            'subtotal' => $prod['precio_pagina'] * $prod['piezas']
                        ]);
                    }
                    $conteoRegistrados++;
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Proceso exitoso: $conteoRegistrados pedidos nuevos creados. $conteoActualizados pedidos actualizados parcialmente."]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('GELIA (Conta Historico) - Error: ' . $e->getMessage() . ' - Línea: ' . $e->getLine());
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
        // 1. AÑADIMOS EL CLIENTE A LAS REGLAS DE VALIDACIÓN
        $request->validate([
            'tipo_transaccion' => 'required|string',
            'platform_id' => 'required|exists:platforms,id',
            'venta_total' => 'required|numeric',
            'costo_envio' => 'required|numeric',
            'comision_plataforma' => 'required|numeric',
            'cliente_nombre' => 'nullable|string', // <-- Nuevo campo permitido
            'productos' => 'required|array', 
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

            // 2. INYECTAMOS EL NOMBRE DEL CLIENTE AL MOMENTO DE GUARDAR
            $pedido->update([
                'tipo_transaccion' => $tipo,
                'platform_id' => $request->platform_id,
                'cliente_nombre' => $request->cliente_nombre, // <-- Guardado en BD
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

    /**
     * Vista principal del Control de Retiros Inteligente (Agrupado por periodos)
     */
    public function gestionRetiros()
    {
        $platforms = Platform::where('active', true)->get();
        $pedidosPendientes = ContabilidadPedido::with('detalles', 'platform')
            ->where('estatus_pago', 'pendiente')
            ->orderBy('fecha_salida', 'asc')
            ->get();

        $datosPlataformas = [];
        
        foreach ($platforms as $plat) {
            $pedidos = $pedidosPendientes->where('platform_id', $plat->id)->values();
            $frecuencia = strtolower($plat->frecuencia_pago);
            
            // AGRUPACIÓN INTELIGENTE VIRTUAL
            $grupos = $pedidos->groupBy(function($pedido) use ($frecuencia) {
                $fecha = \Carbon\Carbon::parse($pedido->fecha_salida);
                
                if ($frecuencia == 'semanal') {
                    return 'Semana del ' . $fecha->copy()->startOfWeek()->format('d/m/Y') . ' al ' . $fecha->copy()->endOfWeek()->format('d/m/Y');
                } elseif ($frecuencia == 'quincenal') {
                    if ($fecha->day <= 15) return 'Quincena 01/' . $fecha->format('m/Y') . ' al 15/' . $fecha->format('m/Y');
                    return 'Quincena 16/' . $fecha->format('m/Y') . ' al ' . $fecha->endOfMonth()->format('d/m/Y');
                } elseif ($frecuencia == 'diario' || $frecuencia == 'inmediato') {
                    return 'Día ' . $fecha->format('d/m/Y');
                }
                
                // Personalizado o fallback
                return 'Periodo ' . $fecha->format('F Y');
            });

            // Ordenamos los grupos para que los más antiguos salgan primero
            $grupos = $grupos->sortKeys();

            $datosPlataformas[] = [
                'plataforma' => $plat,
                'grupos' => $grupos,
                'total_pendientes' => $pedidos->count()
            ];
        }

        return view('contabilidad.retiros', compact('datosPlataformas'));
    }

    /**
     * Procesa la confirmación de un lote completo (Con montos individuales)
     */
    public function confirmarLote(Request $request)
    {
        $request->validate([
            'platform_id' => 'required|exists:platforms,id',
            'pedidos' => 'required|array',
            'pedidos.*.id' => 'required|exists:contabilidad_pedidos,id',
            'pedidos.*.monto_real' => 'required|numeric', // Ahora exigimos el monto por cada pedido
            'fecha_deposito' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $pedidosIds = collect($request->pedidos)->pluck('id');
            $pedidosBD = ContabilidadPedido::whereIn('id', $pedidosIds)->get()->keyBy('id');

            $ventasTotal = 0;
            $comisionesTotal = 0;
            $montoEsperadoTotal = 0;
            $montoRealTotal = 0;
            
            $pedidosAActualizar = [];

            foreach ($request->pedidos as $reqPedido) {
                $pedido = $pedidosBD[$reqPedido['id']];
                $montoReal = (float) $reqPedido['monto_real'];

                $ventasTotal += $pedido->venta_total;
                $comisionesTotal += $pedido->comision_plataforma;
                
                $montoEsperado = 0;
                if(str_contains(strtolower($pedido->tipo_transaccion), 'venta')) {
                    $montoEsperado = $pedido->venta_total - $pedido->comision_plataforma;
                } else {
                    $montoEsperado = -abs($pedido->venta_total + $pedido->comision_plataforma);
                }

                $montoEsperadoTotal += $montoEsperado;
                $montoRealTotal += $montoReal;

                // Calculamos la discrepancia individual
                $diferencia = $montoEsperado - $montoReal;
                $comisionTransferencia = $diferencia > 0 ? $diferencia : 0;
                $nuevaUtilidad = $pedido->utilidad_total - $comisionTransferencia;

                // Lo guardamos en memoria para actualizarlo después de crear el lote
                $pedidosAActualizar[] = [
                    'model' => $pedido,
                    'comision_trans' => $comisionTransferencia,
                    'nueva_utilidad' => $nuevaUtilidad
                ];
            }

            // 1. Creamos el Lote agrupador
            $lote = \App\Models\LotePago::create([
                'platform_id' => $request->platform_id,
                'fecha_corte_esperada' => \Carbon\Carbon::now(),
                'fecha_deposito_real' => $request->fecha_deposito,
                'monto_ventas_total' => $ventasTotal,
                'comisiones_plataforma_total' => $comisionesTotal,
                'monto_esperado_banco' => $montoEsperadoTotal,
                'monto_real_banco' => $montoRealTotal,
                'estatus' => 'completado'
            ]);

            // 2. Actualizamos los pedidos con su comisión específica
            foreach ($pedidosAActualizar as $data) {
                $data['model']->update([
                    'estatus_pago' => 'transferido',
                    'lote_pago_id' => $lote->id,
                    'fecha_retiro' => $request->fecha_deposito,
                    'comision_transferencia' => $data['comision_trans'],
                    'utilidad_total' => $data['nueva_utilidad']
                ]);
            }

            Platform::where('id', $request->platform_id)->update(['ultimo_corte' => $request->fecha_deposito]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Retiros confirmados y desglosados correctamente.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirmación Rápida Individual (Con modificación de Monto Real)
     */
    public function confirmarIndividual(Request $request, $id)
    {
        $request->validate([
            'monto_real_banco' => 'required|numeric',
            'fecha_deposito' => 'required|date'
        ]);

        try {
            DB::beginTransaction();
            $pedido = ContabilidadPedido::findOrFail($id);

            // 1. Calcular el monto que esperábamos que llegara de la plataforma
            $montoEsperado = 0;
            if(str_contains(strtolower($pedido->tipo_transaccion), 'venta')) {
                $montoEsperado = $pedido->venta_total - $pedido->comision_plataforma;
            } else {
                $montoEsperado = -abs($pedido->venta_total + $pedido->comision_plataforma);
            }

            // 2. Calcular diferencia (Comisión del banco por transferir)
            $montoReal = (float) $request->monto_real_banco;
            $diferencia = $montoEsperado - $montoReal;
            
            $comisionTransferencia = $diferencia > 0 ? $diferencia : 0;
            
            // 3. Restar esa comisión bancaria de la Utilidad Neta final
            $nuevaUtilidad = $pedido->utilidad_total - $comisionTransferencia;

            // 4. Crear el Lote y Actualizar
            $lote = \App\Models\LotePago::create([
                'platform_id' => $pedido->platform_id,
                'fecha_corte_esperada' => \Carbon\Carbon::now(),
                'fecha_deposito_real' => $request->fecha_deposito,
                'monto_ventas_total' => $pedido->venta_total,
                'comisiones_plataforma_total' => $pedido->comision_plataforma,
                'monto_esperado_banco' => $montoEsperado,
                'monto_real_banco' => $montoReal,
                'estatus' => 'completado'
            ]);

            $pedido->update([
                'estatus_pago' => 'transferido',
                'lote_pago_id' => $lote->id,
                'fecha_retiro' => $request->fecha_deposito,
                'comision_transferencia' => $comisionTransferencia,
                'utilidad_total' => $nuevaUtilidad
            ]);

            Platform::where('id', $pedido->platform_id)->update(['ultimo_corte' => $request->fecha_deposito]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pago confirmado.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
