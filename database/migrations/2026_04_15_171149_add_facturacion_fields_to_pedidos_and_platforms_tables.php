<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->string('frecuencia_pago')->default('inmediato')->after('name'); // diario, semanal, quincenal, inmediato, personalizado
            $table->date('ultimo_corte')->nullable()->after('frecuencia_pago');
            $table->integer('dias_personalizados')->nullable()->after('ultimo_corte'); 
        });

        Schema::table('contabilidad_pedidos', function (Blueprint $table) {
            $table->string('cliente_nombre')->nullable()->after('numero_pedido');
            $table->string('estatus_pago')->default('pendiente')->after('utilidad_total'); // pendiente, transferido
            $table->decimal('comision_transferencia', 10, 2)->default(0)->after('estatus_pago');
            $table->date('fecha_retiro')->nullable()->after('comision_transferencia');
            $table->unsignedBigInteger('lote_pago_id')->nullable()->after('platform_id');
        });
    }

    public function down(): void
    {
        Schema::table('platforms', function (Blueprint $table) {
            $table->dropColumn(['frecuencia_pago', 'ultimo_corte', 'dias_personalizados']);
        });
        Schema::table('contabilidad_pedidos', function (Blueprint $table) {
            $table->dropColumn(['cliente_nombre', 'estatus_pago', 'comision_transferencia', 'fecha_retiro', 'lote_pago_id']);
        });
    }
};
