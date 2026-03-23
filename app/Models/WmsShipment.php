<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsShipment extends Model
{
    use HasFactory;

    protected $table = 'wms_shipments';

    protected $fillable = [
        'pick_wave_id',
        'warehouse_id',
        'shipment_number',
        'order_number',
        'task_number',
        'vehicle_plate',
        'vehicle_name',
        'driver_name',
        'driver_phone',
        'route_name',
        'operator_user_id',
        'status',
        'expected_lines',
        'scanned_lines',
        'expected_qty',
        'loaded_qty',
        'missing_qty',
        'extra_qty',
        'expected_boxes',
        'loaded_boxes',
        'missing_boxes',
        'loading_started_at',
        'loading_completed_at',
        'dispatched_at',
        'signed_by_name',
        'signed_by_role',
        'signature_data',
        'notes',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'loading_started_at' => 'datetime',
        'loading_completed_at' => 'datetime',
        'dispatched_at' => 'datetime',
    ];

    public function pickWave(): BelongsTo
    {
        return $this->belongsTo(PickWave::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(WmsShipmentLine::class, 'shipment_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(WmsShipmentScan::class, 'shipment_id');
    }
}