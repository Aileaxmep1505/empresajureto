<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsCountLine extends Model
{
    protected $fillable = [
        'count_id', 'location_id', 'catalog_item_id', 'expected_qty', 'counted_qty',
        'adjusted', 'counted_by', 'counted_at', 'note',
    ];

    protected $casts = [
        'expected_qty' => 'integer',
        'counted_qty' => 'integer',
        'adjusted' => 'boolean',
        'counted_at' => 'datetime',
    ];

    public function count(): BelongsTo { return $this->belongsTo(WmsCount::class, 'count_id'); }
    public function item(): BelongsTo { return $this->belongsTo(CatalogItem::class, 'catalog_item_id'); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function counter(): BelongsTo { return $this->belongsTo(User::class, 'counted_by'); }

    public function isCounted(): bool
    {
        return $this->counted_qty !== null;
    }

    /** Diferencia contado - esperado; nulo si todavía no se cuenta. */
    public function getVarianceAttribute(): ?int
    {
        return $this->counted_qty === null ? null : $this->counted_qty - $this->expected_qty;
    }
}
