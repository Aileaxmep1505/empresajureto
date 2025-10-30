<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $table = 'orders';

    /**
     * Evita problemas de MassAssignment en creates/updates programáticos.
     * (Alternativa a $fillable: dejamos todo liberado y validamos en controlador)
     */
    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'float',
        'shipping' => 'float',
        'tax'      => 'float',
        'total'    => 'float',

        'shipping_store_pays'   => 'boolean',
        'shipping_carrier_cost' => 'float',
        'shipping_address_json' => 'array',
    ];

    /* ================= Relaciones ================= */

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    /* ================= Helpers ================= */

    public function markPaid(): void
    {
        if ($this->status !== 'paid') {
            $this->status = 'paid';
            $this->save();
        }
    }

    /** Búsqueda idempotente por sesión de Stripe (si tienes la columna). */
    public static function findByStripeSession(?string $sid): ?self
    {
        if (!$sid) return null;
        return static::where('stripe_session_id', $sid)->first();
    }
}
