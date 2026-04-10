<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contabilidad_pedido_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contabilidad_pedido_id')->constrained('contabilidad_pedidos')->onDelete('cascade');
            
            // Datos congelados (Snapshot)
            $table->string('sku');
            $table->integer('piezas')->default(1);
            $table->string('nombre_producto');
            $table->decimal('precio_unitario', 10, 2); // Precio del producto ese día
            $table->decimal('subtotal', 10, 2); // piezas * precio_unitario
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contabilidad_pedido_detalles');
    }
};