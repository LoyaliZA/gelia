<?php

namespace App\Http\Controllers\FuncionesContabilidad;

use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ContabilidadPedido;
use App\Models\ContabilidadPedidoDetalle;
use App\Models\Platform;
use Carbon\Carbon;

class CargaMasivaService
{
    /**
     * Procesa la lista de resurtido diario (Precios en memoria).
     */
    public static function procesarLista($request)
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
            
            return response()->json(['success' => true, 'data' => $diccionario]);
        } catch (\Throwable $e) {
            Log::error('GELIA (Conta) - Error procesando Excel: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Fallo al leer las columnas del Excel.'], 500);
        } finally {
            if (file_exists($rutaCompleta)) unlink($rutaCompleta);
        }
    }

    /**
     * Importación masiva de registros históricos y actualización parcial.
     */
    public static function importarHistorico($request)
    {
        $request->validate(['archivo_historico' => 'required|file|mimes:xlsx,csv']);
        
        $archivo = $request->file('archivo_historico');
        $nombreTemp = 'temp_hist_' . uniqid() . '.' . $archivo->getClientOriginalExtension();
        $rutaCompleta = sys_get_temp_dir() . '/' . $nombreTemp;
        $archivo->move(sys_get_temp_dir(), $nombreTemp);

        $platformsDB = Platform::all()->keyBy(function($item) {
            return strtolower(str_replace(' ', '', $item->name));
        });

        $pedidosAgrupados = self::agruparPedidosDesdeExcel($rutaCompleta);

        try {
            DB::beginTransaction();
            $conteoRegistrados = 0;
            $conteoActualizados = 0;

            foreach ($pedidosAgrupados as $numPedido => $data) {
                $pedidoExistente = ContabilidadPedido::where('numero_pedido', $numPedido)->first();

                if ($pedidoExistente) {
                    // ==========================================
                    // FLUJO A: ACTUALIZACIÓN PARCIAL INTELIGENTE
                    // ==========================================
                    $updateData = [];

                    if ($data['cliente_nombre'] !== '') $updateData['cliente_nombre'] = $data['cliente_nombre'];

                    if ($data['plataforma'] !== '') {
                        $platKey = strtolower(str_replace(' ', '', $data['plataforma']));
                        if (isset($platformsDB[$platKey])) {
                            $updateData['platform_id'] = $platformsDB[$platKey]->id;
                        }
                    }

                    if ($data['fecha'] !== null) $updateData['fecha_salida'] = $data['fecha'];
                    if ($data['tipo_transaccion'] !== null) $updateData['tipo_transaccion'] = $data['tipo_transaccion'];
                    if ($data['venta_total'] !== null) $updateData['venta_total'] = $data['venta_total'];
                    if ($data['envio_cobrado'] !== null) $updateData['costo_envio'] = $data['envio_cobrado'];
                    if ($data['envio_pagado_cliente'] !== null) $updateData['envio_pagado_cliente'] = $data['envio_pagado_cliente'];
                    if ($data['comision_cobrada'] !== null) $updateData['comision_plataforma'] = $data['comision_cobrada'];

                    // Si hay cambios que afecten lo financiero, recalculamos con la regla centralizada
                    if (isset($updateData['venta_total']) || isset($updateData['comision_plataforma']) || isset($updateData['costo_envio']) || count($data['productos']) > 0) {
                        
                        $ventaCalc = $updateData['venta_total'] ?? $pedidoExistente->venta_total;
                        $comisionCalc = $updateData['comision_plataforma'] ?? $pedidoExistente->comision_plataforma;
                        $envioCobradoCalc = $updateData['costo_envio'] ?? $pedidoExistente->costo_envio;
                        $envioPagadoClienteCalc = $updateData['envio_pagado_cliente'] ?? $pedidoExistente->envio_pagado_cliente;
                        $tipoTransaccionCalc = $updateData['tipo_transaccion'] ?? $pedidoExistente->tipo_transaccion;
                        
                        $costoProductos = 0.0;
                        if (count($data['productos']) > 0) {
                            foreach($data['productos'] as $prod) {
                                $costoProductos += ($prod['precio_pagina'] * $prod['piezas']);
                            }
                        } else {
                            $costoProductos = $pedidoExistente->detalles->sum('subtotal');
                        }

                        // FIX APLICADO: Cálculo seguro mediante el servicio financiero
                        $updateData['utilidad_total'] = CalculadoraFinancieraService::calcularUtilidad(
                            (float) $ventaCalc,
                            (float) $costoProductos,
                            (float) $envioCobradoCalc,
                            (bool) $envioPagadoClienteCalc,
                            (float) $comisionCalc,
                            $tipoTransaccionCalc,
                            $pedidoExistente->estatus_pago,
                            (float) $pedidoExistente->comision_transferencia
                        );
                    }

                    if (!empty($updateData)) {
                        $pedidoExistente->update($updateData);
                    }

                    // Sobrescritura de productos si vienen en el Excel
                    if (count($data['productos']) > 0) {
                        $pedidoExistente->detalles()->delete();
                        self::guardarDetallesMasivos($pedidoExistente->id, $data['productos']);
                    }
                    $conteoActualizados++;

                } else {
                    // ==========================================
                    // FLUJO B: CREACIÓN DE PEDIDO NUEVO
                    // ==========================================
                    $platKey = strtolower(str_replace(' ', '', $data['plataforma']));
                    $platform_id = isset($platformsDB[$platKey]) ? $platformsDB[$platKey]->id : $platformsDB->first()->id;

                    $costoProductos = 0.0;
                    foreach($data['productos'] as $prod) {
                        $costoProductos += ($prod['precio_pagina'] * $prod['piezas']);
                    }

                    $tipoTransaccion = $data['tipo_transaccion'] ?? 'venta';
                    $ventaTotal = (float) ($data['venta_total'] ?? 0);
                    $costoEnvio = (float) ($data['envio_cobrado'] ?? 0);
                    $envioPagadoCliente = (bool) ($data['envio_pagado_cliente'] ?? false);
                    $comisionCobrada = (float) ($data['comision_cobrada'] ?? 0);
                    
                    // Cálculo inicial asumiendo estado "transferido" (por ser histórico)
                    $utilidadFinal = CalculadoraFinancieraService::calcularUtilidad(
                        $ventaTotal,
                        $costoProductos,
                        $costoEnvio,
                        $envioPagadoCliente,
                        $comisionCobrada,
                        $tipoTransaccion,
                        'transferido',
                        0.0
                    );

                    $nuevoPedido = ContabilidadPedido::create([
                        'fecha_salida' => $data['fecha'] ?? date('Y-m-d'),
                        'numero_pedido' => $numPedido,
                        'cliente_nombre' => $data['cliente_nombre'],
                        'tipo_transaccion' => $tipoTransaccion,
                        'platform_id' => $platform_id,
                        'venta_total' => $ventaTotal,
                        'costo_envio' => $costoEnvio,
                        'envio_pagado_cliente' => $envioPagadoCliente,
                        'comision_plataforma' => $comisionCobrada,
                        'utilidad_total' => $utilidadFinal,
                        'estatus_pago' => 'transferido',
                        'fecha_retiro' => $data['fecha'] ?? date('Y-m-d'),
                        'comision_transferencia' => 0
                    ]);

                    self::guardarDetallesMasivos($nuevoPedido->id, $data['productos']);
                    $conteoRegistrados++;
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Proceso exitoso: $conteoRegistrados pedidos nuevos creados. $conteoActualizados actualizados."]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('GELIA (Conta Historico) - Error: ' . $e->getMessage() . ' - Línea: ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Fallo al procesar el archivo. Verifique el formato.'], 500);
        } finally {
            if (file_exists($rutaCompleta)) unlink($rutaCompleta);
        }
    }

    /**
     * Auxiliar: Extrae y agrupa la información cruda del archivo Excel.
     */
    private static function agruparPedidosDesdeExcel($rutaCompleta)
    {
        $pedidosAgrupados = [];

        (new FastExcel)->import($rutaCompleta, function ($linea) use (&$pedidosAgrupados) {
            $numPedido = trim((string)($linea['Pedido'] ?? ''));
            if ($numPedido === '' || $numPedido === '28098' || str_contains($numPedido, 'Ej:')) return;

            if (!isset($pedidosAgrupados[$numPedido])) {
                $fechaRaw = trim((string)($linea['Fecha'] ?? ''));
                $fechaFinal = null;

                if ($fechaRaw !== '') {
                    try {
                        if ($linea['Fecha'] instanceof \DateTime) {
                            $fechaFinal = $linea['Fecha']->format('Y-m-d');
                        } else {
                            $fechaFinal = Carbon::parse(str_replace('/', '-', $fechaRaw))->format('Y-m-d');
                        }
                    } catch (\Exception $e) {
                        Log::warning("GELIA: No se pudo parsear fecha $fechaRaw.");
                    }
                }

                $limpiarNumero = function($valor) {
                    if ($valor === null || $valor === '') return null;
                    return (float) preg_replace('/[^0-9.]/', '', (string)$valor);
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
            if ($sku !== '') {
                $limpiarNumero = function($valor) {
                    return (float) preg_replace('/[^0-9.]/', '', (string)$valor);
                };

                $pedidosAgrupados[$numPedido]['productos'][] = [
                    'sku' => $sku,
                    'piezas' => (int)($linea['Piezas'] ?? 1),
                    'precio_pagina' => $limpiarNumero($linea['Precio_Pagina'] ?? 0)
                ];
            }
        });

        return $pedidosAgrupados;
    }

    /**
     * Auxiliar: Inserta los productos vinculados a un pedido.
     */
    private static function guardarDetallesMasivos($pedidoId, $productos)
    {
        foreach($productos as $prod) {
            ContabilidadPedidoDetalle::create([
                'contabilidad_pedido_id' => $pedidoId,
                'sku' => $prod['sku'],
                'piezas' => $prod['piezas'],
                'nombre_producto' => 'Carga Masiva (SKU: '.$prod['sku'].')',
                'precio_unitario' => $prod['precio_pagina'],
                'subtotal' => $prod['precio_pagina'] * $prod['piezas']
            ]);
        }
    }
}