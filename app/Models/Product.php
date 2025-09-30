<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','sku','supplier_sku','unit','weight','cost','price','market_price','bid_price',
        'dimensions','color','pieces_per_unit','active','brand','category','material',
        'description','notes','tags','image_path',
    ];

    protected $casts = [
        'active' => 'boolean',
        'weight' => 'decimal:3',
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
        'market_price' => 'decimal:2',
        'bid_price' => 'decimal:2',
        'pieces_per_unit' => 'integer',
    ];
}
