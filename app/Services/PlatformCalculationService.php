<?php

namespace App\Services;

use App\Models\Platform;

/**
 * Servicio centralizado para el cálculo de comisiones y utilidades.
 */
class PlatformCalculationService
{
    /**
     * Calcula el desglose financiero de una venta según la plataforma.
     *
     * @param float $price Precio total pagado por el cliente en la página.
     * @param float $cost Costo real del producto (Precio de P.).
     * @param float $shipping Costo de envío pagado a paquetería.
     * @param Platform $platform Instancia del modelo Platform con sus tasas.
     * @return array Desglose de comisiones y utilidad neta.
     */
    public function calculateTransaction(float $price, float $cost, float $shipping, Platform $platform): array
    {
        // 1. Convertir porcentajes enteros a decimales para la matemática (ej. 3.60 -> 0.036)
        $commissionRate = $platform->commission_percent / 100;
        $taxRate = $platform->tax_rate / 100;

        // 2. Calcular la comisión base de la plataforma
        $baseCommission = ($price * $commissionRate) + $platform->fixed_fee;

        // 3. Calcular el IVA sobre esa comisión base
        $commissionTax = $baseCommission * $taxRate;

        // 4. Sumar el total real que retendrá la plataforma
        $totalCommission = $baseCommission + $commissionTax;

        // 5. Calcular la Utilidad Neta real para la empresa
        $netUtility = $price - $cost - $shipping - $totalCommission;

        // 6. Retornamos los datos formateados a 2 decimales para precisión financiera
        return [
            'base_commission'  => round($baseCommission, 2),
            'commission_tax'   => round($commissionTax, 2),
            'total_commission' => round($totalCommission, 2),
            'net_utility'      => round($netUtility, 2),
        ];
    }
}