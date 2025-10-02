<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'customer_name','customer_email','customer_phone','customer_address',
        'currency','subtotal','shipping','tax','total','status'
    ];

    protected $casts = [
        'subtotal' => 'float',
        'shipping' => 'float',
        'tax'      => 'float',
        'total'    => 'float',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
