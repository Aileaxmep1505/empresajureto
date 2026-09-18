<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tarea de reabastecimiento: mover stock de una ubicación de reserva a la de picking. */
class WmsReplenishmentTask extends Model
{
    protected $fillable = [
        'warehouse_id', 'catalog_item_id', 'from_location_id', 'to_location_id',
        'qty_suggested', 'qty_moved', 'priority', 'status',
        'assigned_user_id', 'created_by', 'completed_by', 'completed_at', 'notes', 'meta',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function item(): BelongsTo { return $this->belongsTo(CatalogItem::class, 'catalog_item_id'); }
    public function fromLocation(): BelongsTo { return $this->belongsTo(Location::class, 'from_location_id'); }
    public function toLocation(): BelongsTo { return $this->belongsTo(Location::class, 'to_location_id'); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function completedBy(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}
