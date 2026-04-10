<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContabilidadPedido extends Model
{
    protected $fillable = [
        'fecha_salida',
        'numero_pedido',
        'tipo_transaccion',
        'platform_id',
        'venta_total',
        'costo_envio',
        'envio_pagado_cliente',
        'comision_plataforma',
        'utilidad_total',
        'bloqueado'
    ];

    protected $casts = [
        'fecha_salida' => 'date',
        'envio_pagado_cliente' => 'boolean',
        'bloqueado' => 'boolean',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(ContabilidadPedidoDetalle::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }
}