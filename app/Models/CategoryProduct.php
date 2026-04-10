<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CategoryProduct extends Model
{
    protected $table = 'category_products';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'full_path',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (CategoryProduct $category) {
            if (blank($category->slug) && filled($category->name)) {
                $baseSlug = Str::slug($category->name);

                if (blank($baseSlug)) {
                    $baseSlug = 'categoria';
                }

                $slug = $baseSlug;
                $i = 1;

                while (
                    static::where('slug', $slug)
                        ->when($category->exists, fn ($q) => $q->where('id', '!=', $category->id))
                        ->exists()
                ) {
                    $slug = $baseSlug . '-' . $i++;
                }

                $category->slug = $slug;
            }
        });

        static::saved(function (CategoryProduct $category) {
            $category->refreshFullPath();
            foreach ($category->children as $child) {
                $child->refreshFullPathRecursively();
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(CategoryProduct::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(CategoryProduct::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function catalogItems()
    {
        return $this->hasMany(CatalogItem::class, 'category_product_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getBreadcrumbArrayAttribute(): array
    {
        $items = [];
        $current = $this;

        while ($current) {
            array_unshift($items, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }

        return $items;
    }

    public function getDisplayPathAttribute(): string
    {
        return collect($this->breadcrumb_array)->pluck('name')->implode(' > ');
    }

    public function refreshFullPath(): void
    {
        $this->full_path = $this->display_path;
        $this->saveQuietly();
    }

    public function refreshFullPathRecursively(): void
    {
        $this->refreshFullPath();

        foreach ($this->children as $child) {
            $child->refreshFullPathRecursively();
        }
    }
}