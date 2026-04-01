<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WoocommerceSyncLog extends Model
{
    // Mantenemos la convención de no usar filigranas y permitir asignación masiva
    protected $guarded = [];

    /**
     * Opcional: Relación para saber qué archivos (templates) 
     * se generaron en este proceso de sincronización.
     */
    public function getPorcentajeAttribute()
    {
        if ($this->total_productos <= 0) return 0;
        return round(($this->procesados / this->total_productos) * 100);
    }
}