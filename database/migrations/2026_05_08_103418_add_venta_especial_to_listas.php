<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- Importación agregada

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Agregar la columna a custom_lists
        Schema::table('custom_lists', function (Blueprint $table) {
            $table->decimal('pct_venta_especial', 5, 2)->nullable()->after('filtro_relojes');
        });

        // 2. Insertar el valor por defecto en la configuración global
        DB::table('gelia_settings')->insertOrIgnore([
            ['key' => 'pct_venta_especial', 'value' => '25.00', 'created_at' => now(), 'updated_at' => now()]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir la inserción de configuración
        DB::table('gelia_settings')->where('key', 'pct_venta_especial')->delete();

        // Revertir la creación de la columna
        Schema::table('custom_lists', function (Blueprint $table) {
            $table->dropColumn('pct_venta_especial');
        });
    }
};