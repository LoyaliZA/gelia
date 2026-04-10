<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContabilidadPedidoDetalle extends Model
{
    protected $fillable = [
        'contabilidad_pedido_id',
        'sku',
        'piezas',
        'nombre_producto',
        'precio_unitario',
        'subtotal'
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(ContabilidadPedido::class, 'contabilidad_pedido_id');
    }
}