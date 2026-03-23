<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WmsShipmentScan extends Model
{
    use HasFactory;

    protected $table = 'wms_shipment_scans';

    protected $fillable = [
        'shipment_id',
        'shipment_line_id',
        'scan_type',
        'scan_value',
        'qty',
        'box_label',
        'result',
        'message',
        'payload',
        'user_id',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(WmsShipment::class, 'shipment_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(WmsShipmentLine::class, 'shipment_line_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}