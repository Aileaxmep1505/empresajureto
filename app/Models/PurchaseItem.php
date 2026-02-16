<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_document_id','item_name','item_raw','unit','qty','unit_price','line_total','ai_meta',
    ];

    protected $casts = [
        'ai_meta' => 'array',
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:2',
    ];

    public function document()
    {
        return $this->belongsTo(PurchaseDocument::class, 'purchase_document_id');
    }
}