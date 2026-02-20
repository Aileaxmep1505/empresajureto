<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverPosition extends Model
{
    protected $table = 'driver_positions';

    protected $fillable = [
        'user_id',
        'lat', 'lng',
        'accuracy', 'speed', 'heading',
        'captured_at',

        // NUEVOS (migración)
        'received_at',
        'app_state',
        'battery',
        'network',
        'is_mocked',
        'snap_lat',
        'snap_lng',
        'snap_distance_m',
    ];

    protected $casts = [
        'user_id'     => 'int',

        'lat'         => 'float',
        'lng'         => 'float',
        'accuracy'    => 'float',
        'speed'       => 'float',
        'heading'     => 'float',

        'captured_at' => 'datetime',
        'received_at' => 'datetime',

        'battery'     => 'int',
        'is_mocked'   => 'bool',

        'snap_lat'    => 'float',
        'snap_lng'    => 'float',
        'snap_distance_m' => 'int',
    ];
}