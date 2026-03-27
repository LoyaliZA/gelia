<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('custom_lists', function (Blueprint $table) {
            // Se añade el campo con valor por defecto false para no afectar las listas creadas anteriormente
            $table->boolean('filtro_relojes')->default(false)->after('solo_con_existencia');
        });
    }

    public function down()
    {
        Schema::table('custom_lists', function (Blueprint $table) {
            $table->dropColumn('filtro_relojes');
        });
    }
};