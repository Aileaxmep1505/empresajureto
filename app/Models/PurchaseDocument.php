<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseDocument extends Model
{
    protected $fillable = [
        'publication_id','created_by','source_kind',
        'category', // <--- NUEVO CAMPO
        'document_type','supplier_name','currency',
        'document_datetime','subtotal','tax','total','ai_meta',
    ];

    protected $casts = [
        'ai_meta' => 'array',
        'document_datetime' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}