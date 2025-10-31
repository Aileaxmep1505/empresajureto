<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $table = 'order_items';

    // Incluimos TODOS los campos que usa el controlador y los que existen en tu tabla
    protected $fillable = [
        'order_id',
        'catalog_item_id',
        'product_id',
        'name',
        'sku',
        'image_url',
        'meta',
        'price',        // <— unitario (también tienes unit_price)
        'qty',
        'unit_price',   // <— duplicado de price en tu esquema
        'total',        // <— duplicado de amount en tu esquema
        'amount',       // <— importe (price * qty)
        'currency',
        'tax_rate',
        'discount',
    ];

    protected $casts = [
        'qty'        => 'integer',
        'price'      => 'float',
        'unit_price' => 'float',
        'total'      => 'float',
        'amount'     => 'float',
        'tax_rate'   => 'float',
        'discount'   => 'float',
        'meta'       => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * Normaliza y completa columnas antes de guardar para evitar ceros.
     * - Si viene price pero no unit_price (o viceversa) los sincroniza.
     * - Calcula amount/total si faltan.
     * - Aplica defaults.
     */
    protected static function booted(): void
    {
        static::saving(function (OrderItem $m) {
            $m->qty = max(1, (int)($m->qty ?? 1));

            // Sincroniza price <-> unit_price (tu tabla tiene ambas)
            if (is_null($m->unit_price) && !is_null($m->price)) {
                $m->unit_price = (float)$m->price;
            }
            if (is_null($m->price) && !is_null($m->unit_price)) {
                $m->price = (float)$m->unit_price;
            }

            // Calcula importes si vienen vacíos
            $base = (float)($m->price ?? $m->unit_price ?? 0);
            if (is_null($m->amount)) {
                $m->amount = round($base * $m->qty, 2);
            }
            if (is_null($m->total)) {
                $m->total = (float)$m->amount;
            }

            // Defaults
            if (empty($m->currency)) {
                $m->currency = 'MXN';
            }
            if (is_null($m->discount)) {
                $m->discount = 0.0;
            }
        });
    }
}
