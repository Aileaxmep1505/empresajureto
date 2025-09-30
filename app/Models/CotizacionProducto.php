<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionProducto extends Model
{
    protected $table = 'cotizacion_productos';

    protected $fillable = [
        'cotizacion_id','producto_id','descripcion',
        'cantidad','precio_unitario','descuento','iva_porcentaje','importe'
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cotizacion::class);
    }

    // Importante: la FK se llama producto_id pero la tabla es products
    public function producto(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'producto_id');
    }
}
