<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','sku','supplier_sku','unit','weight','cost','price','market_price','bid_price',
        'dimensions','color','pieces_per_unit','active','brand','category','material',
        'description','notes','tags','image_path','image_url',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'price'        => 'decimal:2',
        'cost'         => 'decimal:2',
        'market_price' => 'decimal:2',
        'bid_price'    => 'decimal:2',
        'weight'       => 'decimal:3',
    ];

    public function getImageSrcAttribute(): ?string
    {
        if (!empty($this->image_url)) {
            return self::normalizeDriveUrl($this->image_url);
        }
        if (!empty($this->image_path)) {
            $rel = ltrim($this->image_path, '/');
            $storagePath = storage_path('app/public/'.$rel);
            if (is_file($storagePath)) {
                return asset('storage/'.$rel);
            }
            $base = rtrim((string)config('app.products_image_base_url', env('PRODUCTS_IMAGE_BASE_URL', '')), '/');
            if ($base !== '') {
                return $base.'/'.$rel;
            }
        }
        return null;
    }

    public static function normalizeDriveUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = trim($url);
        if (preg_match('~^//~', $url)) $url = 'https:'.$url;
        elseif (!preg_match('~^https?://~i', $url) && str_contains($url, 'google.com')) $url = 'https://'.$url;

        if (preg_match('~drive\.google\.com\/file\/d\/([^\/\?]+)~i', $url, $m)) return 'https://drive.google.com/uc?id='.$m[1];
        if (preg_match('~drive\.google\.com\/open\?id=([^&]+)~i', $url, $m)) return 'https://drive.google.com/uc?id='.$m[1];
        if (preg_match('~google\.com\/uc\?id=([^&]+)~i', $url, $m))       return 'https://drive.google.com/uc?id='.$m[1];

        return $url;
    }

    public function setImagePathAttribute($value): void
    {
        $v = is_string($value) ? trim($value) : $value;
        if (is_string($v) && preg_match('~^(https?:)?\/\/~i', $v)) {
            $this->attributes['image_url']  = self::normalizeDriveUrl($v);
            $this->attributes['image_path'] = null;
        } else {
            $this->attributes['image_path'] = $v ? ltrim($v, '/') : null;
        }
    }

    public function setImageUrlAttribute($value): void
    {
        $v = is_string($value) ? trim($value) : $value;
        $this->attributes['image_url'] = $v ? self::normalizeDriveUrl($v) : null;
    }
}
