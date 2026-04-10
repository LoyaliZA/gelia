<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contabilidad_pedidos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_salida');
            $table->string('numero_pedido')->unique();
            $table->foreignId('platform_id')->constrained('platforms');
            
            // Finanzas globales del pedido
            $table->decimal('venta_total', 10, 2); // Precio total pagado por el cliente
            $table->decimal('costo_envio', 8, 2)->default(0); // Lo que cuesta el envío
            $table->boolean('envio_pagado_cliente')->default(false); // ¿El cliente pagó el envío?
            
            // Totales calculados
            $table->decimal('comision_plataforma', 8, 2)->default(0);
            $table->decimal('utilidad_total', 10, 2)->default(0);
            
            // Control de Cierre de Mes
            $table->boolean('bloqueado')->default(false); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contabilidad_pedidos');
    }
};