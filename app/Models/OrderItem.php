<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id','catalog_item_id','name','sku','qty','unit_price','total'
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'float',
        'total' => 'float',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
