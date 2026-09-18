<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mercancía recibida que se manda directo a una ola de picking, sin guardarse en rack. */
class WmsCrossdockAssignment extends Model
{
    public const STATUSES = [
        'asignado'  => 'Asignado',
        'en_anden'  => 'En andén',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
    ];

    /** Estados que todavía apartan mercancía. */
    public const ACTIVOS = ['asignado', 'en_anden', 'entregado'];

    protected $fillable = [
        'reception_id', 'reception_line_id', 'pick_wave_id', 'wave_line_id', 'catalog_item_id',
        'qty', 'staging_location_id', 'status', 'created_by', 'updated_by',
        'staged_at', 'delivered_at', 'notes',
    ];

    protected $casts = [
        'staged_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function reception(): BelongsTo { return $this->belongsTo(WmsReception::class, 'reception_id'); }
    public function receptionLine(): BelongsTo { return $this->belongsTo(WmsReceptionLine::class, 'reception_line_id'); }
    public function wave(): BelongsTo { return $this->belongsTo(PickWave::class, 'pick_wave_id'); }
    public function item(): BelongsTo { return $this->belongsTo(CatalogItem::class, 'catalog_item_id'); }
    public function stagingLocation(): BelongsTo { return $this->belongsTo(Location::class, 'staging_location_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }
}
