<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaProducto extends Model
{
    protected $table = 'venta_productos';

    protected $fillable = [
        'venta_id','producto_id','descripcion',
        'cantidad','precio_unitario','descuento','iva_porcentaje','importe'
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'producto_id');
    }
}
