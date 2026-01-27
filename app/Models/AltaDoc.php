<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AltaDoc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alta_docs';

    protected $fillable = [
        'original_name',
        'stored_name',
        'disk',
        'path',
        'mime',
        'size',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * Tamaño legible (KB / MB).
     */
    public function getHumanSizeAttribute(): string
    {
        if (!$this->size) {
            return '—';
        }

        $bytes = (int) $this->size;
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $kb = $bytes / 1024;
        if ($kb < 1024) {
            return round($kb, 1) . ' KB';
        }

        $mb = $kb / 1024;
        return round($mb, 1) . ' MB';
    }

    /**
     * Devuelve un tipo amigable (PDF, Word, Excel, XML, etc) según el mime o extensión
     */
    public function getFriendlyTypeAttribute(): string
    {
        $mime = (string) $this->mime;
        $name = (string) $this->original_name;
        $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (str_contains($mime, 'pdf') || $ext === 'pdf') return 'PDF';
        if (in_array($ext, ['doc', 'docx'])) return 'Word';
        if (in_array($ext, ['xls', 'xlsx'])) return 'Excel';
        if ($ext === 'csv') return 'CSV';
        if ($ext === 'xml') return 'XML';
        if ($ext === 'txt') return 'TXT';

        return strtoupper($ext) ?: 'Archivo';
    }
}
