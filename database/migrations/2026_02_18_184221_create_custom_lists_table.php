<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_lists', function (Blueprint $table) {
            $table->id();
            
            // Datos Informativos
            $table->string('nombre_creador'); // "Juan Perez"
            $table->string('titulo_lista');   // "Lista Finanzas"
            $table->text('descripcion')->nullable();
            
            // Configuración Visual
            // Guardaremos el nombre del color (blue, red, emerald) para usarlo en las clases de Tailwind
            $table->string('color')->default('blue'); 

            // Configuración Lógica (JSON es perfecto para esto)
            // Ejemplo: ["existencias", "precios"]
            $table->json('archivos_requeridos'); 
            
            // Ejemplo: ["SKU", "Descripcion", "CostoCalculado"]
            $table->json('columnas_exportar');

            // Configuración de Salida
            // Ejemplo: "REPORTE-FINANCIERO" (el sistema le agregará la fecha automáticamente)
            $table->string('nombre_archivo_salida');

            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_lists');
    }
};