<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotePago extends Model
{
    protected $table = 'lotes_pagos';

    protected $fillable = [
        'platform_id',
        'fecha_corte_esperada',
        'fecha_deposito_real',
        'monto_ventas_total',
        'comisiones_plataforma_total',
        'monto_esperado_banco',
        'monto_real_banco',
        'estatus',
        'factura_referencia'
    ];

    protected $casts = [
        'fecha_corte_esperada' => 'date',
        'fecha_deposito_real' => 'date',
    ];

    // Relación con la plataforma
    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    // Relación con los pedidos que integran este lote
    public function pedidos(): HasMany
    {
        return $this->hasMany(ContabilidadPedido::class, 'lote_pago_id');
    }
}