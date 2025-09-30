<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CotizacionPlazo extends Model
{
    protected $table = 'cotizacion_plazos';

    protected $fillable = [
        'cotizacion_id','numero','vence_el','monto','pagado'
    ];

    protected $casts = [
        'vence_el' => 'date',
        'pagado' => 'boolean',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cotizacion::class);
    }
}
