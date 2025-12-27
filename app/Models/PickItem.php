<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PickItem extends Model
{
    protected $fillable = [
        'pick_wave_id','catalog_item_id',
        'requested_qty','picked_qty',
        'suggested_location_id','status','sort_key','meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function wave(): BelongsTo
    {
        return $this->belongsTo(PickWave::class, 'pick_wave_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    public function suggestedLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'suggested_location_id');
    }
}
