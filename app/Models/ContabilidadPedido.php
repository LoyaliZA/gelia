<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContabilidadPedido extends Model
{
    protected $fillable = [
        'fecha_salida', 'numero_pedido', 'cliente_nombre', 'tipo_transaccion', // cliente_nombre añadido
        'platform_id', 'lote_pago_id', 'venta_total', 'costo_envio', // lote_pago_id añadido
        'envio_pagado_cliente', 'comision_plataforma', 'utilidad_total', 
        'estatus_pago', 'comision_transferencia', 'fecha_retiro', 'bloqueado' // campos de retiro añadidos
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