<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaComercialItem extends Model
{
    protected $table = 'propuesta_comercial_items';

    protected $fillable = [
        'propuesta_comercial_id',
        'sort',
        'partida_numero',
        'subpartida_numero',
        'descripcion_original',
        'unidad_solicitada',
        'cantidad_minima',
        'cantidad_maxima',
        'cantidad_cotizada',
        'producto_seleccionado_id',
        'match_score',
        'costo_unitario',
        'precio_unitario',
        'subtotal',
        'status',
        'meta',
    ];

    protected $casts = [
        'cantidad_minima' => 'decimal:2',
        'cantidad_maxima' => 'decimal:2',
        'cantidad_cotizada' => 'decimal:2',
        'match_score' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'meta' => 'array',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaComercial::class, 'propuesta_comercial_id');
    }

    public function matches()
    {
        return $this->hasMany(PropuestaComercialMatch::class, 'propuesta_comercial_item_id')
            ->orderBy('rank');
    }

    public function productoSeleccionado()
    {
        return $this->belongsTo(Product::class, 'producto_seleccionado_id');
    }
}