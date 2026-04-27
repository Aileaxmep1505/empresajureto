<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaComercialExternalMatch extends Model
{
    protected $fillable = [
        'propuesta_comercial_item_id',
        'rank',
        'source',
        'title',
        'seller',
        'price',
        'currency',
        'url',
        'score',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'score' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(PropuestaComercialItem::class, 'propuesta_comercial_item_id');
    }
}