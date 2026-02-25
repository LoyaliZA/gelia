<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sefe_proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('rfc', 15)->unique();
            $table->string('nombre');
            $table->json('mapeo_columnas'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sefe_proveedores');
    }
};