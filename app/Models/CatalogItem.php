<?php
// app/Models/CatalogItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'catalog_items';

    protected $fillable = [
        'name','slug','sku','price','sale_price','status',
        'excerpt','description','brand_id','category_id',
        'brand_name','model_name',
        'image_url','images','is_featured','published_at',
        // ML
        'meli_item_id','meli_category_id','meli_listing_type_id',
        'meli_synced_at','meli_status','meli_last_error',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'sale_price'   => 'decimal:2',
        'images'       => 'array',
        'is_featured'  => 'boolean',
        'published_at' => 'datetime',
        'meli_synced_at' => 'datetime',
    ];

    public function getRouteKeyName(){ return 'slug'; }

    /* Scopes */
    public function scopePublished($q){
        return $q->where('status', 1)->where(function ($qq) {
            $qq->whereNull('published_at')->orWhere('published_at','<=',now());
        });
    }
    public function scopeFeatured($q){ return $q->where('is_featured', true); }
    public function scopeOrdered($q){ return $q->orderByDesc('published_at')->orderBy('name'); }

    /* Relaciones de ejemplo */
    public function favoredBy(){ return $this->belongsToMany(\App\Models\User::class, 'favorites')->withTimestamps(); }
    public function category(){ return $this->belongsTo(\App\Models\Category::class, 'category_id'); }

    /* Helpers mínimos */
    public function mainPicture(): ?string {
        return $this->image_url ?: ($this->images[0] ?? null);
    }
}
