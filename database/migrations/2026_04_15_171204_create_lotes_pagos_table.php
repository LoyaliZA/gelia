<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes_pagos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_id');
            $table->date('fecha_corte_esperada');
            $table->date('fecha_deposito_real')->nullable();
            $table->decimal('monto_ventas_total', 12, 2)->default(0);
            $table->decimal('comisiones_plataforma_total', 12, 2)->default(0);
            $table->decimal('monto_esperado_banco', 12, 2)->default(0); // Lo que Gelia predice
            $table->decimal('monto_real_banco', 12, 2)->nullable();     // Lo que la Lic. confirma
            $table->string('estatus')->default('pendiente'); // pendiente, completado
            $table->string('factura_referencia')->nullable(); // Ej: ACH 399 SFC
            $table->timestamps();

            $table->foreign('platform_id')->references('id')->on('platforms')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_pagos');
    }
};
