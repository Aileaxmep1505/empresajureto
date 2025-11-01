<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'parent_id', 'is_primary', 'position', 'image_url', 'description'
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
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position');
    }

    public function items()
    {
        return $this->hasMany(CatalogItem::class, 'category_id');
    }

    /* Scopes */
    public function scopePrimary($q)
    {
        return $q->where('is_primary', true)->orderBy('position');
    }
}
