<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemisionItem extends Model
{
    protected $table = 'remision_items';

    protected $fillable = [
        'remision_id',
        'adjudicacion_item_id',
        'sort',
        'descripcion',
        'unidad',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'meta',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'meta' => 'array',
    ];

    public function remision()
    {
        return $this->belongsTo(Remision::class, 'remision_id');
    }

    public function adjudicacionItem()
    {
        return $this->belongsTo(AdjudicacionItem::class, 'adjudicacion_item_id');
    }
}