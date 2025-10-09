<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaProducto extends Model
{
    protected $table = 'venta_productos';

    protected $fillable = [
        'venta_id','producto_id','descripcion',
        'cantidad','precio_unitario','descuento','iva_porcentaje','importe',

        // NUEVOS (ver migración sugerida)
        'cost',              // costo unitario del producto al momento de vender
        'importe_sin_iva',   // base de la fila (opcional)
        'iva_monto',         // iva de la fila (opcional)
    ];

    protected $casts = [
        'cantidad'        => 'float',
        'precio_unitario' => 'float',
        'descuento'       => 'float',
        'iva_porcentaje'  => 'float',
        'importe'         => 'float',
        'cost'            => 'float',
        'importe_sin_iva' => 'float',
        'iva_monto'       => 'float',
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
