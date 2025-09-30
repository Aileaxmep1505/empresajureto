<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPlazo extends Model
{
    protected $table = 'venta_plazos';

    protected $fillable = [
        'venta_id','numero','vence_el','monto','pagado'
    ];

    protected $casts = [
        'vence_el' => 'date',
        'pagado' => 'boolean',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Venta::class);
    }
}
