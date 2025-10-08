<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionProducto extends Model
{
    protected $table = 'cotizacion_productos';

    protected $fillable = [
        'cotizacion_id','producto_id','descripcion',
        'cantidad',
        // ==== NUEVOS / AJUSTADOS ====
        'cost',                 // costo base
        'precio_unitario',      // venta calculada (snapshot)
        'descuento',            // monto por fila $
        'iva_porcentaje',       // %
        // snapshots de importes
        'importe_sin_iva',
        'iva_monto',
        'importe_total',
        // compatibilidad con tu campo previo:
        'importe',
    ];

    protected $casts = [
        'cantidad'        => 'float',
        'cost'            => 'float',
        'precio_unitario' => 'float',
        'descuento'       => 'float',
        'iva_porcentaje'  => 'float',
        'importe_sin_iva' => 'float',
        'iva_monto'       => 'float',
        'importe_total'   => 'float',
        'importe'         => 'float',
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
