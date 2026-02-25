<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sefe_facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sefe_proveedor_id')->constrained('sefe_proveedores')->cascadeOnDelete();
            $table->string('uuid', 36)->unique(); // El folio fiscal real del SAT
            $table->string('folio')->nullable(); // El folio interno de la empresa
            $table->decimal('total', 15, 2)->nullable();
            $table->string('ruta_xml');
            $table->string('ruta_excel')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sefe_facturas');
    }
};