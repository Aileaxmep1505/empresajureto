<?php
// app/Models/OrderPayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $table = 'order_payments';

    // Abierto para create([...]) desde el controlador
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        // Guardes lo que guardes (array o JSON string), al leer obtendrás array si es JSON válido
        'raw'    => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if (empty($m->currency)) {
                $m->currency = 'MXN';
            }
            if (empty($m->status)) {
                $m->status = 'paid';
            }
            if (is_string($m->raw)) {
                // Si llega string JSON, lo dejamos; si llega array, Eloquent lo serializa.
                // No hacemos nada extra para no doble-serializar.
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
