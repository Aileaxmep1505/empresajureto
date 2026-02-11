<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'catalog_items';

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'price',
        'sale_price',
        'stock',          // stock global del producto
        'status',
        'excerpt',
        'description',

        // Clasificación interna
        'brand_id',
        'category_id',

        // Clave de categoría de catálogo (config/catalog.php)
        'category_key',

        // Mercado Libre (texto)
        'brand_name',
        'model_name',

        'is_featured',
        'published_at',

        // Fotos (rutas en storage/public)
        'photo_1',
        'photo_2',
        'photo_3',

        // Mercado Libre / catálogo
        'meli_item_id',
        'meli_category_id',
        'meli_listing_type_id',
        'meli_synced_at',
        'meli_status',
        'meli_last_error',
        'meli_gtin',

        // ===========================
        // AMAZON / SP-API (NUEVO)
        // ===========================
        'amazon_sku',
        'amazon_asin',
        'amazon_product_type',
        'amazon_status',
        'amazon_synced_at',
        'amazon_last_error',
        'amazon_listing_response',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'sale_price'       => 'decimal:2',
        'stock'            => 'integer',
        'is_featured'      => 'boolean',
        'published_at'     => 'datetime',
        'meli_synced_at'   => 'datetime',

        // Amazon
        'amazon_synced_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /* =====================
     *       Scopes
     * ===================== */

    public function scopePublished($q)
    {
        return $q->where('status', 1)
            ->where(function ($qq) {
                $qq->whereNull('published_at')
                   ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeFeatured($q)
    {
        return $q->where('is_featured', true);
    }

    public function scopeOrdered($q)
    {
        return $q->orderByDesc('published_at')
                 ->orderBy('name');
    }

    /* =====================
     *     Relaciones
     * ===================== */

    public function favoredBy()
    {
        return $this->belongsToMany(\App\Models\User::class, 'favorites')
            ->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id');
    }

    public function primaryLocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'primary_location_id');
    }

    public function inventoryRows()
    {
        return $this->hasMany(\App\Models\Inventory::class, 'catalog_item_id');
    }

    /* =====================
     *      Helpers
     * ===================== */

    public function mainPicture(): ?string
    {
        return $this->photo_1 ?: ($this->photo_2 ?: $this->photo_3);
    }

    public function photos(): array
    {
        return array_values(array_filter([
            $this->photo_1,
            $this->photo_2,
            $this->photo_3,
        ], fn($p) => is_string($p) && trim($p) !== ''));
    }

    public function hasMeliError(): bool
    {
        return !empty($this->meli_last_error);
    }

    public function shortMeliError(int $limit = 140): ?string
    {
        if (!$this->meli_last_error) {
            return null;
        }

        $txt = trim($this->meli_last_error);
        if (mb_strlen($txt) <= $limit) {
            return $txt;
        }

        return mb_substr($txt, 0, $limit - 3) . '...';
    }

    public function getCategoryLabelAttribute(): ?string
    {
        if (!$this->category_key) {
            return null;
        }

        $all = config('catalog.product_categories', []);
        return $all[$this->category_key] ?? $this->category_key;
    }

    /**
     * SKU efectivo para Amazon.
     * Si algún día quieres que Amazon use otro SKU distinto al interno,
     * llena amazon_sku y listo.
     */
    public function amazonSku(): ?string
    {
        $s = $this->amazon_sku ?: $this->sku;
        $s = is_string($s) ? trim($s) : null;
        return $s !== '' ? $s : null;
    }

    public function hasAmazonError(): bool
    {
        return !empty($this->amazon_last_error);
    }

    public function shortAmazonError(int $limit = 140): ?string
    {
        if (!$this->amazon_last_error) return null;

        $txt = trim($this->amazon_last_error);
        if (mb_strlen($txt) <= $limit) return $txt;

        return mb_substr($txt, 0, $limit - 3) . '...';
    }
}
