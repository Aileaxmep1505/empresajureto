<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRouteStop extends Model
{
    protected $table = 'delivery_route_stops';
    protected $fillable = [
        'delivery_route_id','provider_id','kind','seq','name','lat','lng',
        'service_minutes','eta','depart_at','meta'
    ];
    protected $casts = ['eta'=>'datetime','depart_at'=>'datetime','meta'=>'array'];

    public function route(): BelongsTo
    {
        return $this->belongsTo(DeliveryRoute::class, 'delivery_route_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }
}
