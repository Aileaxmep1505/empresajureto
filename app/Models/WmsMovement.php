<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsMovement extends Model
{
    protected $fillable = ['warehouse_id','user_id','type','note'];

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
}
