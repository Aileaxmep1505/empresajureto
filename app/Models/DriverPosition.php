<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverPosition extends Model
{
    protected $table = 'driver_positions';

    protected $fillable = [
        'user_id', 'lat', 'lng', 'accuracy', 'speed', 'heading', 'captured_at',
    ];

    protected $casts = [
        'lat'         => 'float',
        'lng'         => 'float',
        'accuracy'    => 'float',
        'speed'       => 'float',
        'heading'     => 'float',
        'captured_at' => 'datetime',
    ];
}
