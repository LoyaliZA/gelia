<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gelia_settings', function (Blueprint $table) {
        $table->id();
        // Almacena el nombre del campo (ej: pct_bronce)
        $table->string('key')->unique(); 
        // Almacena el valor numérico
        $table->string('value');         
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gelia_settings');
    }
};
