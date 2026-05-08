<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsMovement extends Model
{
    protected $fillable = [
        'warehouse_id',
        'user_id',
        'type',
        'note',
        'reference',
        'meta',
        'authorized_name',
        'authorized_role',
        'delivered_name',
        'received_name',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'user_id' => 'integer',
        'meta' => 'array',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(WmsMovementLine::class, 'movement_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVirtualPickup(): bool
    {
        $type = strtolower((string) $this->type);

        return in_array($type, [
            'virtual_pickup_collected',
            'virtual_pickup_staged',
        ], true);
    }
}