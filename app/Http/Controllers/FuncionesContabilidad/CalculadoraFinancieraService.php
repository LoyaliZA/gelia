<?php

namespace App\Http\Controllers\FuncionesContabilidad;

class CalculadoraFinancieraService
{
    /**
     * Calcula la utilidad neta centralizada respetando comisiones bancarias previas.
     */
    public static function calcularUtilidad(
        float $ventaTotal,
        float $costoProductos,
        float $costoEnvio,
        bool $envioPagadoCliente,
        float $comisionPlataforma,
        string $tipoTransaccion,
        string $estatusPago = 'pendiente',
        float $comisionTransferencia = 0.0
    ): float {
        // Si el cliente pagó el envío, el costo para la empresa es $0.
        $gastoEnvioEmpresa = $envioPagadoCliente ? 0.0 : $costoEnvio;

        if (str_contains(strtolower($tipoTransaccion), 'venta')) {
            $utilidadBase = $ventaTotal - $costoProductos - $gastoEnvioEmpresa - $comisionPlataforma;
        } else {
            // Contracargos y Reembolsos son pérdida (se restan en negativo)
            $utilidadBase = -($costoProductos + $gastoEnvioEmpresa + $comisionPlataforma);
        }

        // EL FIX: Si el pago ya fue confirmado, deducimos la comisión real del banco
        if ($estatusPago === 'transferido') {
            $utilidadBase -= $comisionTransferencia;
        }

        return $utilidadBase;
    }

    /**
     * Calcula lo que se espera que la plataforma deposite en el banco.
     */
    public static function calcularMontoEsperadoBanco(string $tipoTransaccion, float $ventaTotal, float $comisionPlataforma): float {
        if (str_contains(strtolower($tipoTransaccion), 'venta')) {
            return $ventaTotal - $comisionPlataforma;
        }
        return -abs($ventaTotal + $comisionPlataforma);
    }
}