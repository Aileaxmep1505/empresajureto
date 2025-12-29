<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsMovementLine extends Model
{
    protected $fillable = [
        'movement_id','catalog_item_id','location_id','qty',
        'stock_before','stock_after','inv_before','inv_after'
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
}
