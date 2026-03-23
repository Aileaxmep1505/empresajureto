<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Location extends Model
{
    protected $fillable = [
        'warehouse_id',
        'parent_id',
        'type',
        'code',
        'aisle',
        'section',
        'stand',
        'rack',
        'level',
        'bin',
        'name',
        'qr_secret',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /* =========================
     |  RELATIONS
     =========================*/

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function inventoryRows(): HasMany
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    /* =========================
     |  HELPERS
     =========================*/

    public function normalizedMeta(): array
    {
        return is_array($this->meta) ? $this->meta : [];
    }

    public function normalizedType(): string
    {
        $type = Str::slug((string) $this->type, '_');

        return match ($type) {
            'pick', 'picker', 'picking_area' => 'picking',
            'fastflow', 'fast-flow', 'fast_flow_area' => 'fast_flow',
            'receiving', 'receiver', 'recepcion', 'recepción', 'entrante', 'incoming' => 'incoming',
            'shipping', 'dispatch_area', 'despacho', 'embarque', 'expedicion', 'expedición', 'dispatch' => 'dispatch',
            'rack', 'slot', 'location', 'ubicacion', 'ubicación', 'almacenamiento', 'bin' => 'bin',
            default => $type !== '' ? $type : 'bin',
        };
    }

    public function isRackSlot(): bool
    {
        return $this->zone === 'rack';
    }

    /* =========================
     |  ACCESSORS
     =========================*/

    public function getZoneAttribute(): string
    {
        $meta = $this->normalizedMeta();

        if (!empty($meta['zone'])) {
            $zone = Str::slug((string) $meta['zone'], '_');

            return match ($zone) {
                'recepcion', 'recepción', 'incoming', 'entrante' => 'incoming',
                'fastflow', 'fast_flow', 'fast-flow' => 'fast_flow',
                'picking', 'pick' => 'picking',
                'dispatch', 'despacho', 'embarque', 'expedicion', 'expedición', 'shipping' => 'dispatch',
                'rack', 'bin', 'almacenamiento' => 'rack',
                default => $zone !== '' ? $zone : 'rack',
            };
        }

        $type = $this->normalizedType();

        if (in_array($type, ['incoming', 'fast_flow', 'picking', 'dispatch'], true)) {
            return $type;
        }

        if (filled($this->rack) || filled($this->level) || filled($this->bin) || $type === 'bin') {
            return 'rack';
        }

        return 'rack';
    }

    public function getZoneLabelAttribute(): string
    {
        return match ($this->zone) {
            'incoming' => 'Entrante',
            'fast_flow' => 'Fast Flow',
            'picking' => 'Picking',
            'dispatch' => 'Despacho',
            'rack' => 'Rack',
            default => Str::headline((string) $this->zone),
        };
    }

    public function getCapacityAttribute(): int
    {
        $meta = $this->normalizedMeta();

        return max(
            0,
            (int) ($meta['max_capacity'] ?? $meta['capacity'] ?? 100)
        );
    }

    public function getRackNumberAttribute(): int
    {
        return $this->extractNumber($this->rack);
    }

    public function getLevelNumberAttribute(): int
    {
        return $this->extractNumber($this->level);
    }

    public function getPositionNumberAttribute(): int
    {
        return $this->extractNumber($this->bin);
    }

    public function getPositionLabelAttribute(): string
    {
        if ($this->zone === 'rack') {
            $rack = $this->rackNumberAttributeValue();
            $level = $this->levelNumberAttributeValue();
            $position = $this->positionNumberAttributeValue();

            if ($rack > 0 && $level > 0 && $position > 0) {
                return "Rack {$rack} / Nivel {$level} / Posición {$position}";
            }
        }

        return collect([
            $this->aisle,
            $this->section,
            $this->stand,
            $this->rack,
            $this->level,
            $this->bin,
        ])->filter(fn ($v) => filled($v))->implode(' / ') ?: $this->zoneLabel;
    }

    public function getLayoutXAttribute(): int
    {
        $meta = $this->normalizedMeta();

        if (array_key_exists('x', $meta)) {
            return (int) $meta['x'];
        }

        if ($this->zone === 'rack') {
            $rack = max(1, $this->rackNumberAttributeValue());
            $position = max(1, $this->positionNumberAttributeValue());

            return 80 + (($rack - 1) * 640) + (($position - 1) * 56);
        }

        return match ($this->zone) {
            'picking' => 80,
            'fast_flow' => 160,
            'incoming' => 240,
            'dispatch' => 320,
            default => 80,
        };
    }

    public function getLayoutYAttribute(): int
    {
        $meta = $this->normalizedMeta();

        if (array_key_exists('y', $meta)) {
            return (int) $meta['y'];
        }

        if ($this->zone === 'rack') {
            $level = max(1, $this->levelNumberAttributeValue());
            return 80 + (($level - 1) * 42);
        }

        return match ($this->zone) {
            'picking' => 430,
            'fast_flow' => 520,
            'incoming' => 610,
            'dispatch' => 700,
            default => 790,
        };
    }

    public function getLayoutWAttribute(): int
    {
        $meta = $this->normalizedMeta();
        return max(1, (int) ($meta['w'] ?? ($this->zone === 'rack' ? 48 : 64)));
    }

    public function getLayoutHAttribute(): int
    {
        $meta = $this->normalizedMeta();
        return max(1, (int) ($meta['h'] ?? ($this->zone === 'rack' ? 30 : 42)));
    }

    /* =========================
     |  INTERNAL
     =========================*/

    protected function extractNumber(mixed $value): int
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        if (preg_match('/(\d+)/', $value, $m)) {
            return max(0, (int) $m[1]);
        }

        return 0;
    }

    protected function rackNumberAttributeValue(): int
    {
        return $this->extractNumber($this->rack);
    }

    protected function levelNumberAttributeValue(): int
    {
        return $this->extractNumber($this->level);
    }

    protected function positionNumberAttributeValue(): int
    {
        return $this->extractNumber($this->bin);
    }
}