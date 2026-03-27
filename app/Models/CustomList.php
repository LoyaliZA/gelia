<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomList extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_creador',
        'titulo_lista',
        'descripcion',
        'color',
        'archivos_requeridos',
        'columnas_exportar',
        'nombre_archivo_salida',
        'active',
        'solo_con_existencia', // Nueva columna para filtrar solo productos con existencia
        'filtro_relojes', // Nueva columna para filtrar solo productos que inician con 'R'
    ];

    // Esto hace la magia: convierte el JSON de la BD a Array de PHP automáticamente
    protected $casts = [
        'archivos_requeridos' => 'array',
        'columnas_exportar' => 'array',
        'active' => 'boolean',
        'solo_con_existencia' => 'boolean', // Asegura que esta columna también se trate como booleano
    ];
}