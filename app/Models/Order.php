<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'billing_profile_id',
        'customer_name',
        'customer_email',

        'subtotal',
        'shipping_amount',
        'tax',
        'total',
        'currency',
        'status',

        'address_json',
        'shipping_code',         // tracking / guía
        'shipping_name',
        'shipping_service',
        'shipping_eta',
        'shipping_store_pays',
        'shipping_carrier_cost',

        'stripe_session_id',
        'stripe_payment_intent',
        'invoice_id',

        // ✅ Skydropx PRO
        'skydropx_quotation_id',
        'skydropx_rate_id',
        'shipping_label_url',    // PDF
        'shipment_status',
    ];

    protected $casts = [
        'subtotal'              => 'float',
        'shipping_amount'       => 'float',
        'tax'                   => 'float',
        'total'                 => 'float',

        'shipping_store_pays'   => 'boolean',
        'shipping_carrier_cost' => 'float',

        'address_json'          => 'array',
    ];

    /* ================= Relaciones ================= */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function billingProfile(): BelongsTo
    {
        return $this->belongsTo(BillingProfile::class);
    }

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

    /** Búsqueda idempotente por sesión de Stripe. */
    public static function findByStripeSession(?string $sid): ?self
    {
        if (!$sid) return null;
        return static::where('stripe_session_id', $sid)->first();
    }

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if (empty($m->currency)) {
                $m->currency = 'MXN';
            }
        });
    }
}
