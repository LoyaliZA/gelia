<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Platform;

/**
 * Pobla la base de datos con los costos reales de cada pasarela de pago.
 */
class PlatformSeeder extends Seeder
{
    public function run(): void
    {
        $platforms = [
            [
                'name'               => 'Paypal',
                'commission_percent' => 3.95,
                'fixed_fee'          => 4.00,
                'tax_rate'           => 16.00, // IVA sobre comisión
            ],
            [
                'name'               => 'Stripe',
                'commission_percent' => 3.60,
                'fixed_fee'          => 3.00,
                'tax_rate'           => 16.00,
            ],
            [
                'name'               => 'Open Pay',
                'commission_percent' => 2.90,
                'fixed_fee'          => 2.50,
                'tax_rate'           => 16.00,
            ],
            [
                'name'               => 'Kueskipay',
                'commission_percent' => 5.50,
                'fixed_fee'          => 0.00,
                'tax_rate'           => 16.00,
            ],
            [
                'name'               => 'Mercado Pago',
                'commission_percent' => 3.49,
                'fixed_fee'          => 4.00,
                'tax_rate'           => 16.00,
            ],
        ];

        foreach ($platforms as $platform) {
            Platform::updateOrCreate(
                ['name' => $platform['name']],
                [
                    'commission_percent' => $platform['commission_percent'],
                    'fixed_fee'          => $platform['fixed_fee'],
                    'tax_rate'           => $platform['tax_rate'],
                    'active'             => true,
                ]
            );
        }
    }
}