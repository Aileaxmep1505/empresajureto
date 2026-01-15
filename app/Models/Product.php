<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','sku','supplier_sku','unit','weight','cost','price','market_price','bid_price',
        'dimensions','color','pieces_per_unit','active','brand','category','material',
        'description','notes','tags','image_path','image_url',
        'clave_sat',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'price'        => 'decimal:2',
        'cost'         => 'decimal:2',
        'market_price' => 'decimal:2',
        'bid_price'    => 'decimal:2',
        'weight'       => 'decimal:3',
    ];

    /**
     * ✅ image_src: SOLO URL segura (nunca data-uri, nunca SVG).
     * Evita imágenes/HTML/SVG que rompen el layout.
     */
    public function getImageSrcAttribute(): ?string
    {
        // 1) image_url (prioridad)
        $url = trim((string) ($this->image_url ?? ''));

        if ($url !== '') {
            $url = self::normalizeDriveUrl($url);

            // Bloqueos duros
            if (Str::startsWith($url, 'data:')) return null;
            if (!Str::startsWith($url, ['http://', 'https://'])) return null;

            // Evitar SVG (es el sospechoso típico de “figura gigante”)
            $path = parse_url($url, PHP_URL_PATH) ?: '';
            if (Str::endsWith(Str::lower($path), '.svg')) return null;

            return $url;
        }

        // 2) image_path en public disk
        $rel = trim((string) ($this->image_path ?? ''));
        if ($rel !== '') {
            $rel = ltrim($rel, '/');
            if (Str::startsWith($rel, 'storage/')) {
                $rel = Str::after($rel, 'storage/');
            }

            try {
                if (Storage::disk('public')->exists($rel)) {
                    // ✅ forma correcta en producción (con URL del disk)
                    return Storage::disk('public')->url($rel);
                }
            } catch (\Throwable $e) {
                // No tronamos por Storage
            }

            // fallback opcional: base URL externa
            $base = rtrim((string) config('app.products_image_base_url', env('PRODUCTS_IMAGE_BASE_URL', '')), '/');
            if ($base !== '') {
                $candidate = $base.'/'.$rel;
                // evita svg también
                $p = parse_url($candidate, PHP_URL_PATH) ?: '';
                if (!Str::endsWith(Str::lower($p), '.svg')) return $candidate;
            }
        }

        return null;
    }

    /**
     * ✅ Normaliza Google Drive a un URL de “vista” más estable.
     * Nota: Drive puede bloquear por permisos, pero al menos evitamos urls raras.
     */
    public static function normalizeDriveUrl(?string $url): ?string
    {
        if (!$url) return null;
        $url = trim($url);

        if (preg_match('~^//~', $url)) {
            $url = 'https:'.$url;
        } elseif (!preg_match('~^https?://~i', $url) && str_contains($url, 'google.com')) {
            $url = 'https://'.$url;
        }

        // Si es data-uri, lo devolvemos tal cual y será bloqueado arriba
        if (Str::startsWith($url, 'data:')) return $url;

        // Drive file/d/<id>
        if (preg_match('~drive\.google\.com\/file\/d\/([^\/\?]+)~i', $url, $m)) {
            return 'https://drive.google.com/uc?export=view&id='.$m[1];
        }
        // Drive open?id=<id>
        if (preg_match('~drive\.google\.com\/open\?id=([^&]+)~i', $url, $m)) {
            return 'https://drive.google.com/uc?export=view&id='.$m[1];
        }
        // google uc?id=<id>
        if (preg_match('~google\.com\/uc\?id=([^&]+)~i', $url, $m)) {
            return 'https://drive.google.com/uc?export=view&id='.$m[1];
        }

        return $url;
    }

    /**
     * Si te pasan una URL en image_path, la movemos a image_url.
     */
    public function setImagePathAttribute($value): void
    {
        $v = is_string($value) ? trim($value) : $value;

        if (is_string($v) && preg_match('~^(https?:)?\/\/~i', $v)) {
            $this->attributes['image_url']  = self::normalizeDriveUrl($v);
            $this->attributes['image_path'] = null;
            return;
        }

        $this->attributes['image_path'] = $v ? ltrim((string)$v, '/') : null;
    }

    public function setImageUrlAttribute($value): void
    {
        $v = is_string($value) ? trim($value) : $value;
        $this->attributes['image_url'] = $v ? self::normalizeDriveUrl($v) : null;
    }
}
