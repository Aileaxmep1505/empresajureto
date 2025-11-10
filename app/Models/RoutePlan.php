<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutePlan extends Model
{
    protected $fillable = [
        'driver_id','name','status','planned_at','meta',
    ];
    protected $casts = [
        'meta' => 'array',
        'planned_at' => 'datetime',
    ];

    public function driver(): BelongsTo { return $this->belongsTo(User::class, 'driver_id'); }
    public function stops(): HasMany { return $this->hasMany(RouteStop::class)->orderBy('sequence_index')->orderBy('id'); }

    public function pendingStops(): HasMany {
        return $this->hasMany(RouteStop::class)->where('status','pending')->orderBy('sequence_index');
    }
}
