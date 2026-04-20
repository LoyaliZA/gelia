<?php

namespace App\Http\Controllers\FuncionesContabilidad;

use App\Models\ContabilidadPedido;
use App\Models\Platform;
use App\Models\LotePago;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RetirosService
{
    /*
     * Prepara los datos para la vista de retiros, agrupando inteligentemente.
     */
    public static function obtenerDatosVista()
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
            
            $grupos = $pedidos->groupBy(function($pedido) use ($frecuencia) {
                $fecha = Carbon::parse($pedido->fecha_salida);
                
                if ($frecuencia == 'semanal') {
                    return 'Semana del ' . $fecha->copy()->startOfWeek()->format('d/m/Y') . ' al ' . $fecha->copy()->endOfWeek()->format('d/m/Y');
                } elseif ($frecuencia == 'quincenal') {
                    if ($fecha->day <= 15) return 'Quincena 01/' . $fecha->format('m/Y') . ' al 15/' . $fecha->format('m/Y');
                    return 'Quincena 16/' . $fecha->format('m/Y') . ' al ' . $fecha->endOfMonth()->format('d/m/Y');
                } elseif ($frecuencia == 'diario' || $frecuencia == 'inmediato') {
                    return 'Día ' . $fecha->format('d/m/Y');
                }
                
                return 'Periodo ' . $fecha->format('F Y');
            });

            $grupos = $grupos->sortBy(function ($pedidosEnGrupo) {
                return $pedidosEnGrupo->min('fecha_salida');
            });

            $datosPlataformas[] = [
                'plataforma' => $plat,
                'grupos' => $grupos,
                'total_pendientes' => $pedidos->count()
            ];
        }

        return $datosPlataformas;
    }

    /*
     * Procesa la confirmación de un solo pedido desde la tabla principal.
     */
    public static function confirmarIndividual($request, $id)
    {
        try {
            DB::beginTransaction();
            $pedido = ContabilidadPedido::findOrFail($id);

            $montoEsperado = CalculadoraFinancieraService::calcularMontoEsperadoBanco(
                $pedido->tipo_transaccion, 
                $pedido->venta_total, 
                $pedido->comision_plataforma
            );
            
            $montoReal = (float) $request->monto_real_banco;
            
            $diferencia = $montoEsperado - $montoReal;
            $comisionTransferencia = $diferencia > 0 ? $diferencia : 0;
            
            $nuevaUtilidad = $pedido->utilidad_total - $comisionTransferencia;

            $lote = LotePago::create([
                'platform_id' => $pedido->platform_id,
                'fecha_corte_esperada' => Carbon::now(),
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

    /*
     * Procesa la confirmación de un lote de pedidos desde el Dashboard de Retiros.
     */
    public static function confirmarLote($request)
    {
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
                
                $montoEsperado = CalculadoraFinancieraService::calcularMontoEsperadoBanco(
                    $pedido->tipo_transaccion, 
                    $pedido->venta_total, 
                    $pedido->comision_plataforma
                );

                $montoEsperadoTotal += $montoEsperado;
                $montoRealTotal += $montoReal;

                $diferencia = $montoEsperado - $montoReal;
                $comisionTransferencia = $diferencia > 0 ? $diferencia : 0;
                $nuevaUtilidad = $pedido->utilidad_total - $comisionTransferencia;

                $pedidosAActualizar[] = [
                    'model' => $pedido,
                    'comision_trans' => $comisionTransferencia,
                    'nueva_utilidad' => $nuevaUtilidad
                ];
            }

            $lote = LotePago::create([
                'platform_id' => $request->platform_id,
                'fecha_corte_esperada' => Carbon::now(),
                'fecha_deposito_real' => $request->fecha_deposito,
                'monto_ventas_total' => $ventasTotal,
                'comisiones_plataforma_total' => $comisionesTotal,
                'monto_esperado_banco' => $montoEsperadoTotal,
                'monto_real_banco' => $montoRealTotal,
                'estatus' => 'completado'
            ]);

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
}