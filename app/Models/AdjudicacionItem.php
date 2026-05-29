<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdjudicacionItem extends Model
{
    protected $table = 'adjudicacion_items';

    protected $fillable = [
        'adjudicacion_id',
        'propuesta_comercial_item_id',
        'sort',
        'descripcion',
        'unidad',
        'cantidad',
        'costo_unitario',
        'precio_unitario',
        'subtotal',
        'meta',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'meta' => 'array',
    ];

    public function adjudicacion()
    {
        return $this->belongsTo(Adjudicacion::class, 'adjudicacion_id');
    }
}