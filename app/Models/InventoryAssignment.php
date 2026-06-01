<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAssignment extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'user_id',
        'quantity',
        'signature',
        'assigned_at',
        'folio',
        'notes',
        'status',
        'return_reason',
        'return_details',
        'return_condition',
        'returned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}