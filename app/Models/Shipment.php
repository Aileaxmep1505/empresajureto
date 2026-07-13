<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'provider',
        'mode',
        'carrier',
        'carrier_key',
        'service',
        'tracking_number',
        'tracking_url',
        'label_url',
        'status',
        'status_label',
        'price',
        'currency',
        'destination',
        'last_tracking_event',
        'last_tracked_at',
        'raw_response',
        'tracking_raw',
    ];

    protected $casts = [
        'destination' => 'array',
        'last_tracking_event' => 'array',
        'raw_response' => 'array',
        'tracking_raw' => 'array',
        'last_tracked_at' => 'datetime',
        'price' => 'decimal:2',
    ];
}
