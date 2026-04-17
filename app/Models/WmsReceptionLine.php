<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WmsReceptionLine extends Model
{
    use HasFactory;

    protected $table = 'wms_reception_lines';

    protected $fillable = [
        'reception_id',
        'catalog_item_id',
        'location_id',
        'sku',
        'name',
        'description',
        'quantity',
        'lot',
        'condition',
        'is_new_product',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_new_product' => 'boolean',
    ];

    public function reception()
    {
        return $this->belongsTo(WmsReception::class, 'reception_id');
    }

    public function catalogItem()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}