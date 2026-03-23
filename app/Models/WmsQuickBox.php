<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WmsQuickBox extends Model
{
    protected $fillable = [
        'warehouse_id',
        'location_id',
        'catalog_item_id',
        'batch_code',
        'label_code',
        'box_number',
        'boxes_in_batch',
        'units_per_box',
        'current_units',
        'status',
        'received_at',
        'shipped_at',
        'received_by',
        'shipped_by',
        'reference',
        'notes',
        'meta',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'shipped_at'  => 'datetime',
        'meta'        => 'array',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function item()
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function shippedBy()
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }
}