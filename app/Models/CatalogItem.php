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
        'status',        // 0=borrador 1=publicado 2=oculto
        'excerpt',
        'description',
        'brand_id',
        'category_id',
        'image_url',     // portada
        'images',        // JSON de URLs
        'is_featured',   // destacado para Home
        'published_at',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'sale_price'   => 'decimal:2',
        'images'       => 'array',
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /* ===== Scopes ===== */
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
        return $q->orderByDesc('published_at')->orderBy('name');
    }
    public function favoredBy() {
    return $this->belongsToMany(\App\Models\User::class, 'favorites')->withTimestamps();
}

}
