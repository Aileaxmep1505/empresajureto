<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeProductSection extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'subtitle',
        'source_type',
        'category_product_id',
        'products_limit',
        'is_active',
        'starts_at',
        'ends_at',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'products_limit' => 'integer',
        'sort_order' => 'integer',
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(HomeProductSectionItem::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function categoryProduct(): BelongsTo
    {
        return $this->belongsTo(CategoryProduct::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now()->toDateString());
            });
    }
}