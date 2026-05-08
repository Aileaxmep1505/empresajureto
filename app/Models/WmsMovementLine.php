<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsMovementLine extends Model
{
    protected $fillable = [
        'movement_id',
        'line_uid',
        'catalog_item_id',
        'location_id',
        'qty',
        'source_type',
        'stock_before',
        'stock_after',
        'inv_before',
        'inv_after',
        'meta',
    ];

    protected $casts = [
        'movement_id' => 'integer',
        'catalog_item_id' => 'integer',
        'location_id' => 'integer',
        'qty' => 'integer',
        'stock_before' => 'integer',
        'stock_after' => 'integer',
        'inv_before' => 'integer',
        'inv_after' => 'integer',
        'meta' => 'array',
    ];

    public function movement(): BelongsTo
    {
        return $this->belongsTo(WmsMovement::class, 'movement_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function isVirtual(): bool
    {
        return strtolower((string) $this->source_type) === 'virtual'
            || (bool) data_get($this->meta, 'is_virtual', false)
            || (bool) data_get($this->meta, 'requires_pickup', false);
    }
}