<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Publication extends Model
{
    protected $fillable = [
        'title','description',
        'file_path','original_name','mime_type','size','extension',
        'kind','pinned','created_by'
    ];

    protected $casts = [
        'pinned' => 'bool',
    ];

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getNiceSizeAttribute(): string
    {
        $bytes = (float)($this->size ?? 0);
        if ($bytes <= 0) return '0 B';
        $units = ['B','KB','MB','GB','TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = max(0, min($i, count($units) - 1));
        return round($bytes / (1024 ** $i), 1) . ' ' . $units[$i];
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string)$this->mime_type, 'image/');
    }

    public function getIsVideoAttribute(): bool
    {
        return str_starts_with((string)$this->mime_type, 'video/');
    }

    public function getIsPdfAttribute(): bool
    {
        return (string)$this->mime_type === 'application/pdf';
    }
}
