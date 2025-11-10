<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRoute extends Model
{
    protected $table = 'delivery_routes';
    protected $fillable = [
        'plan_date','driver_user_id','created_by','status',
        'distance_km','drive_minutes','service_minutes','total_minutes','engine'
    ];
    protected $casts = ['engine' => 'array','plan_date'=>'date'];

    public function stops(): HasMany
    {
        return $this->hasMany(DeliveryRouteStop::class)->orderBy('seq');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_user_id');
    }
}
