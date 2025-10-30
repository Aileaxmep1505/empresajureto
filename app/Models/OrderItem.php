<?php
// app/Models/OrderItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_items';

    // Abrimos mass assignment para el uso con ::create([...]) si lo requieres
    protected $guarded = [];

    protected $casts = [
        'price'    => 'float',
        'qty'      => 'integer',
        'amount'   => 'float',
        'tax_rate' => 'float',
        'discount' => 'float',
        'meta'     => 'array',   // TEXT/JSON: Laravel lo serializa/deserializa
    ];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            // Normaliza cantidad y calcula el total de la línea
            $m->qty = max(1, (int) $m->qty);
            $line = ((float) $m->price * $m->qty) - (float) ($m->discount ?? 0);
            $m->amount = round(max(0, $line), 2);

            // Moneda por defecto si viene vacía
            if (empty($m->currency)) {
                $m->currency = 'MXN';
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
