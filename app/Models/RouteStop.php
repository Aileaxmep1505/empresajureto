<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    protected $fillable = [
        'route_plan_id','name','lat','lng','sequence_index','status','eta_seconds','meta'
    ];
    protected $casts = [
        'lat'=>'float','lng'=>'float','meta'=>'array'
    ];

    public function routePlan(): BelongsTo { return $this->belongsTo(RoutePlan::class); }
}
