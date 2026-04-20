<?php

namespace App\Http\Controllers\FuncionesContabilidad;

use App\Models\LotePago;
use App\Models\ContabilidadPedido;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistorialService
{
    /**
     * Obtiene los lotes de pago confirmados con sus pedidos desglosados.
     */
    public static function obtenerHistorial($request)
    {
        $mes = $request->input('mes', date('m'));
        $anio = $request->input('anio', date('Y'));
        $platformId = $request->input('platform_id');

        $query = LotePago::with(['pedidos.detalles', 'platform'])
            ->whereMonth('fecha_deposito_real', $mes)
            ->whereYear('fecha_deposito_real', $anio);

        // Filtro opcional por plataforma
        if ($request->filled('platform_id') && $platformId !== 'todas') {
            $query->where('platform_id', $platformId);
        }

        return $query->orderBy('fecha_deposito_real', 'desc')->get();
    }

    /**
     * Procesa la edición de emergencia validando contraseña y dejando registro.
     */
    public static function edicionEmergencia($request, $id)
    {
        // 1. Validación de seguridad (Contraseña de emergencia)
        // Nota: En producción, esto debería estar en un campo 'emergency_password' encriptado en config o DB.
        $passwordCorrecta = "Bellaroma2026Admin"; 

        if ($request->password_emergencia !== $passwordCorrecta) {
            return response()->json(['success' => false, 'message' => 'Contraseña de emergencia incorrecta.'], 403);
        }

        try {
            DB::beginTransaction();
            $pedido = ContabilidadPedido::findOrFail($id);

            // 2. Registro de Auditoría (Log)
            // Guardamos quién (si hay auth), cuándo y POR QUÉ se hizo el cambio.
            $usuario = auth()->user()->name ?? 'Sistema';
            $logNota = "MODIFICACIÓN DE EMERGENCIA por {$usuario}. Motivo: " . $request->motivo_cambio;
            
            Log::channel('contabilidad_audit')->info($logNota, [
                'pedido_id' => $id,
                'valores_anteriores' => $pedido->only(['venta_total', 'utilidad_total', 'comision_transferencia']),
                'valores_nuevos' => $request->only(['venta_total', 'monto_real_banco'])
            ]);

            // 3. Aplicar cambios usando nuestra Calculadora Centralizada
            $montoReal = (float) $request->monto_real_banco;
            
            // Recalculamos comisiones bancarias y utilidad
            $montoEsperado = CalculadoraFinancieraService::calcularMontoEsperadoBanco(
                $pedido->tipo_transaccion, 
                (float) $request->venta_total, 
                $pedido->comision_plataforma
            );

            $diferencia = $montoEsperado - $montoReal;
            $comisionTransferencia = $diferencia > 0 ? $diferencia : 0;
            
            // Calculamos utilidad base (sin banco) y luego restamos la nueva comisión del banco
            $utilidadBase = CalculadoraFinancieraService::calcularUtilidad(
                (float) $request->venta_total,
                $pedido->detalles->sum('subtotal'),
                $pedido->costo_envio,
                $pedido->envio_pagado_cliente,
                $pedido->comision_plataforma,
                $pedido->tipo_transaccion,
                'pendiente' // Lo pasamos como pendiente para que el service no reste comisiones viejas
            );

            $pedido->update([
                'venta_total' => (float) $request->venta_total,
                'comision_transferencia' => $comisionTransferencia,
                'utilidad_total' => $utilidadBase - $comisionTransferencia,
                'nota_auditoria' => $logNota // Asumiendo que agregas este campo a la tabla
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Cambio aplicado y registrado en el historial de auditoría.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}