<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WoocommerceMargin;
use App\Models\WoocommerceConfig;
use Illuminate\Support\Facades\Hash;

class WoocommerceSeeder extends Seeder
{
    public function run()
    {
        // 1. Configuraciones iniciales (IVA y Contraseña por defecto: 1234)
        WoocommerceConfig::create(['llave' => 'iva', 'valor' => '1.16', 'descripcion' => 'Valor del IVA para dividir los precios']);
        WoocommerceConfig::create(['llave' => 'admin_pin', 'valor' => Hash::make('1234'), 'descripcion' => 'PIN de acceso al módulo WooCommerce']);

        // 2. Escalones de precios actuales
        $margenes = [
            ['min' => 0, 'max' => 100, 'rebaja' => 1.70, 'normal' => 1.80],
            ['min' => 100.01, 'max' => 129, 'rebaja' => 1.60, 'normal' => 1.78],
            ['min' => 129.01, 'max' => 190, 'rebaja' => 1.55, 'normal' => 1.73],
            ['min' => 190.01, 'max' => 280, 'rebaja' => 1.50, 'normal' => 1.63],
            ['min' => 280.01, 'max' => 359, 'rebaja' => 1.45, 'normal' => 1.55],
            ['min' => 359.01, 'max' => 399, 'rebaja' => 1.35, 'normal' => 1.41],
            ['min' => 399.01, 'max' => 699, 'rebaja' => 1.30, 'normal' => 1.35],
            ['min' => 699.01, 'max' => 999, 'rebaja' => 1.25, 'normal' => 1.28],
            ['min' => 999.01, 'max' => 999999, 'rebaja' => 1.20, 'normal' => 1.22], // El tope máximo
        ];

        foreach ($margenes as $m) {
            WoocommerceMargin::create([
                'precio_min' => $m['min'],
                'precio_max' => $m['max'],
                'multiplicador_rebaja' => $m['rebaja'],
                'multiplicador_normal' => $m['normal'],
            ]);
        }
    }
}