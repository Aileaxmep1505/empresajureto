<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsShipmentLine extends Model
{
    use HasFactory;

    protected $table = 'wms_shipment_lines';

    protected $fillable = [
        'shipment_id',
        'pick_line_id',
        'product_id',
        'product_name',
        'product_sku',
        'batch_code',
        'location_code',
        'staging_location_code',
        'is_fastflow',
        'phase',
        'expected_qty',
        'loaded_qty',
        'missing_qty',
        'extra_qty',
        'expected_boxes',
        'loaded_boxes',
        'missing_boxes',
        'status',
        'reason_code',
        'reason_note',
        'expected_boxes_json',
        'loaded_boxes_json',
        'expected_allocations_json',
        'loaded_allocations_json',
        'meta',
    ];

    protected $casts = [
        'is_fastflow' => 'boolean',
        'expected_boxes_json' => 'array',
        'loaded_boxes_json' => 'array',
        'expected_allocations_json' => 'array',
        'loaded_allocations_json' => 'array',
        'meta' => 'array',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(WmsShipment::class, 'shipment_id');
    }

    public function scans(): HasMany
    {
        return $this->hasMany(WmsShipmentScan::class, 'shipment_line_id');
    }
}