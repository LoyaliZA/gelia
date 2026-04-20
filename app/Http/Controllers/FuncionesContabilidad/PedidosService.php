<?php

namespace App\Http\Controllers\FuncionesContabilidad;

use App\Models\ContabilidadPedido;
use App\Models\ContabilidadPedidoDetalle;
use App\Models\Platform;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PedidosService
{
    /**
     * Registra un nuevo pedido manual en el sistema.
     */
    public static function guardar($request, $calcService)
    {
        try {
            DB::beginTransaction();
            
            $platform = Platform::findOrFail($request->platform_id);
            $ventaTotal = (float) $request->venta_total;
            $costoEnvio = (float) $request->costo_envio;
            $envioPagadoCliente = filter_var($request->envio_pagado_cliente, FILTER_VALIDATE_BOOLEAN);

            // Calcular el costo total de los productos ingresados
            $costoProductos = 0.0;
            foreach ($request->productos as $prod) {
                $costoProductos += ((float) $prod['precio'] * (int) $prod['piezas']);
            }

            $gastoEnvioEmpresa = $envioPagadoCliente ? 0.0 : $costoEnvio;

            // Calcular comisión base de la plataforma usando el servicio heredado
            $finanzas = $calcService->calculateTransaction($ventaTotal, $costoProductos, $gastoEnvioEmpresa, $platform);
            $comisionFinal = $request->filled('comision_real') ? (float) $request->comision_real : (float) $finanzas['total_commission'];
            $tipoTransaccion = $request->input('tipo_transaccion', 'venta');

            // Calcular la utilidad neta mediante el servicio centralizado
            $utilidadFinal = CalculadoraFinancieraService::calcularUtilidad(
                $ventaTotal, 
                $costoProductos, 
                $costoEnvio, 
                $envioPagadoCliente, 
                $comisionFinal, 
                $tipoTransaccion
            );

            // Crear el registro principal
            $pedido = ContabilidadPedido::create([
                'fecha_salida' => $request->fecha_salida,
                'numero_pedido' => $request->numero_pedido,
                'cliente_nombre' => $request->cliente_nombre,
                'tipo_transaccion' => $tipoTransaccion,
                'platform_id' => $request->platform_id,
                'venta_total' => $ventaTotal,
                'costo_envio' => $costoEnvio,
                'envio_pagado_cliente' => $envioPagadoCliente,
                'comision_plataforma' => $comisionFinal,
                'utilidad_total' => $utilidadFinal,
            ]);

            // Guardar el desglose de productos
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
            DB::rollBack();
            Log::error('GELIA (Conta) - Error al guardar pedido manual: ' . $e->getMessage() . ' en la línea ' . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Fallo interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza cantidades y montos financieros de un pedido existente.
     */
    public static function actualizarRapido($request, $id)
    {
        try {
            DB::beginTransaction();
            $pedido = ContabilidadPedido::with('detalles')->findOrFail($id);

            if ($pedido->bloqueado) {
                return response()->json(['success' => false, 'message' => 'El periodo está bloqueado y no puede ser editado.'], 403);
            }

            // Recalcular el costo de los productos según las nuevas cantidades
            $costoProductos = 0.0;
            foreach ($request->productos as $prodReq) {
                $detalle = $pedido->detalles->where('id', $prodReq['id'])->first();
                if ($detalle) {
                    $detalle->piezas = $prodReq['piezas'];
                    $detalle->subtotal = $detalle->precio_unitario * $prodReq['piezas'];
                    $detalle->save();
                    
                    $costoProductos += $detalle->subtotal;
                }
            }

            $envioPagadoCliente = filter_var($request->envio_pagado_cliente, FILTER_VALIDATE_BOOLEAN);

            // FIX: Utilizamos la calculadora financiera inyectando el estatus de pago actual y la comisión bancaria previa.
            // Esto evita que la actualización borre la comisión del banco si el pedido ya había sido transferido.
            $utilidad = CalculadoraFinancieraService::calcularUtilidad(
                (float) $request->venta_total,
                $costoProductos,
                (float) $request->costo_envio,
                $envioPagadoCliente,
                (float) $request->comision_plataforma,
                $request->tipo_transaccion,
                $pedido->estatus_pago,
                (float) $pedido->comision_transferencia
            );

            // Actualizar el registro principal
            $pedido->update([
                'tipo_transaccion' => $request->tipo_transaccion,
                'platform_id' => $request->platform_id,
                'cliente_nombre' => $request->cliente_nombre,
                'venta_total' => (float) $request->venta_total,
                'costo_envio' => (float) $request->costo_envio,
                'envio_pagado_cliente' => $envioPagadoCliente,
                'comision_plataforma' => (float) $request->comision_plataforma,
                'utilidad_total' => $utilidad
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pedido actualizado correctamente.']);
            
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('GELIA (Conta) - Error actualizando pedido ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina un pedido si no pertenece a un periodo bloqueado.
     */
    public static function eliminar($id)
    {
        try {
            $pedido = ContabilidadPedido::findOrFail($id);
            
            if (!$pedido->bloqueado) {
                $pedido->delete();
                return response()->json(['success' => true]);
            }
            
            return response()->json(['success' => false, 'message' => 'El periodo está bloqueado.'], 403);
            
        } catch (\Throwable $e) {
            Log::error('GELIA (Conta) - Error al eliminar pedido ' . $id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}