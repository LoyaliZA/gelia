<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contabilidad_pedidos', function (Blueprint $table) {
            // Añadimos la columna después del número de pedido. Por defecto todo será 'venta'
            $table->string('tipo_transaccion')->default('venta')->after('numero_pedido');
        });
    }

    public function down(): void
    {
        Schema::table('contabilidad_pedidos', function (Blueprint $table) {
            $table->dropColumn('tipo_transaccion');
        });
    }
};