<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la gestión de Plataformas de Pago y sus comisiones.
 */
class Platform extends Model
{
    use HasFactory;

    /**
     * Atributos que son asignables en masa (Seguridad).
     * Previene que usuarios inyecten campos no deseados en la base de datos.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', 'commission_percent', 'fixed_fee', 'tax_rate', 'active',
        'frecuencia_pago', 'ultimo_corte', 'dias_personalizados' // Nuevos
    ];

    /**
     * Conversión de tipos de datos.
     * Asegura que los cálculos matemáticos se hagan con números reales y no cadenas de texto.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'commission_percent' => 'decimal:2',
        'fixed_fee'          => 'decimal:2',
        'tax_rate'           => 'decimal:2',
        'active'             => 'boolean',
    ];
}