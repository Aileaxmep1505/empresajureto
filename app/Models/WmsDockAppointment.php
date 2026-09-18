<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cita de andén: un vehículo programado para cargar o descargar. */
class WmsDockAppointment extends Model
{
    public const STATUSES = [
        'programada' => 'Programada',
        'llego'      => 'Llegó',
        'en_anden'   => 'En andén',
        'terminada'  => 'Terminada',
        'no_llego'   => 'No llegó',
        'cancelada'  => 'Cancelada',
    ];

    /** Minutos de tolerancia para considerar puntual una llegada. */
    public const TOLERANCIA_MIN = 15;

    protected $fillable = [
        'warehouse_id', 'dock', 'type', 'carrier', 'vehicle_plate', 'driver_name', 'reference',
        'scheduled_at', 'duration_min', 'status', 'arrived_at', 'started_at', 'finished_at',
        'notes', 'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'arrived_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_min' => 'integer',
    ];

    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    /** true = llegó a tiempo, false = llegó tarde, null = aún no llega. */
    public function getPuntualAttribute(): ?bool
    {
        if (! $this->arrived_at) {
            return null;
        }

        return $this->arrived_at->lte($this->scheduled_at->copy()->addMinutes(self::TOLERANCIA_MIN));
    }

    /** Minutos que estuvo (o lleva) en el andén. */
    public function getMinutosEnAndenAttribute(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        // Carbon 3 devuelve la diferencia con signo; nunca se muestra un tiempo negativo.
        return max(0, (int) $this->started_at->diffInMinutes($this->finished_at ?? now()));
    }
}
