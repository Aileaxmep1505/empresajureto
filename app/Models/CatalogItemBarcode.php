<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Presentación de venta / empaque de un CatalogItem.
 *
 * @property int         $catalog_item_id
 * @property string|null $barcode
 * @property string      $pack_type
 * @property string|null $label
 * @property int         $units
 * @property float|null  $price
 * @property float|null  $sale_price
 * @property bool        $is_sellable_web
 * @property bool        $is_primary
 */
class CatalogItemBarcode extends Model
{
    protected $table = 'catalog_item_barcodes';

    protected $fillable = [
        'catalog_item_id',
        'barcode',
        'pack_type',
        'label',
        'units',
        'price',
        'sale_price',
        'is_sellable_web',
        'is_primary',
    ];

    protected $casts = [
        'catalog_item_id' => 'integer',
        'units'           => 'integer',
        'price'           => 'decimal:2',
        'sale_price'      => 'decimal:2',
        'is_sellable_web' => 'boolean',
        'is_primary'      => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /** Precio efectivo de esta presentación (oferta si existe, si no precio). */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->price ?? 0);
    }
}
