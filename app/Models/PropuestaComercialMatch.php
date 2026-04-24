<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaComercialMatch extends Model
{
    protected $table = 'propuesta_comercial_matches';

    protected $fillable = [
        'propuesta_comercial_item_id',
        'product_id',
        'rank',
        'score',
        'unidad_coincide',
        'seleccionado',
        'motivo',
        'meta',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'unidad_coincide' => 'boolean',
        'seleccionado' => 'boolean',
        'meta' => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(PropuestaComercialItem::class, 'propuesta_comercial_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}