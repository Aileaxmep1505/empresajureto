<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'is_primary',
        'position',
        'image_url',
        'description',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'position' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->orderBy('position')
            ->orderBy('name');
    }

    public function items()
    {
        return $this->hasMany(CatalogItem::class, 'category_id');
    }

    public function catalogItems()
    {
        return $this->hasMany(CatalogItem::class, 'category_id');
    }

    public function publishedItems()
    {
        return $this->hasMany(CatalogItem::class, 'category_id')
            ->published();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePrimary($query)
    {
        return $query
            ->where('is_primary', true)
            ->orderBy('position')
            ->orderBy('name');
    }

    public function scopeWithPublishedProducts($query)
    {
        return $query->whereHas('catalogItems', function ($q) {
            $q->published();
        });
    }
}