<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. (Aquí le decimos qué agregar)
     */
    public function up(): void
    {
        Schema::table('custom_lists', function (Blueprint $table) {
            // Agregamos una columna booleana (verdadero/falso) llamada 'solo_con_existencia'.
            // Por defecto será 'false' (falso), para no afectar a las listas que ya tienes creadas.
            $table->boolean('solo_con_existencia')->default(false)->after('columnas_exportar');
        });
    }

    /**
     * Reverse the migrations. (Aquí le decimos cómo deshacer el cambio por si nos equivocamos)
     */
    public function down(): void
    {
        Schema::table('custom_lists', function (Blueprint $table) {
            // Si revertimos la migración, eliminamos esta columna
            $table->dropColumn('solo_con_existencia');
        });
    }
};