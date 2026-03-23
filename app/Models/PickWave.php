<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PickWave extends Model
{
    protected $fillable = [
        'warehouse_id',
        'code',
        'task_number',
        'reference',
        'order_number',
        'status',
        'assigned_to',
        'assigned_user_id',
        'priority',
        'notes',
        'items',
        'items_json',
        'deliveries',
        'deliveries_json',
        'total_phases',
        'current_location_id',
        'current_pick_item_id',
        'started_at',
        'completed_at',
        'finished_at',
        'meta',
    ];

    protected $casts = [
        'meta'         => 'array',
        'items'        => 'array',
        'deliveries'   => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'finished_at'  => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function itemsRelation(): HasMany
    {
        return $this->hasMany(PickItem::class, 'pick_wave_id');
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}