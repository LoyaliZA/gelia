<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SefeProveedor extends Model
{
    // Forzamos el nombre correcto de la tabla
    protected $table = 'sefe_proveedores';

    protected $fillable = [
        'rfc',
        'nombre',
        'mapeo_columnas'
    ];

    protected $casts = [
        'mapeo_columnas' => 'array',
    ];

    public function facturas(): HasMany
    {
        return $this->hasMany(SefeFactura::class);
    }
}