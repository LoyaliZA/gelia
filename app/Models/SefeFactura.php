<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SefeFactura extends Model
{
    protected $fillable = [
        'sefe_proveedor_id',
        'uuid',
        'folio',
        'total',
        'ruta_xml',
        'ruta_excel'
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(SefeProveedor::class, 'sefe_proveedor_id');
    }
}