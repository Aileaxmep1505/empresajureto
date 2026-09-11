<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use \App\Traits\LogsModelActivity;

    protected $fillable = [
        'inventory_item_id', 'user_id', 'movement_type', 'quantity', 'reason',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}