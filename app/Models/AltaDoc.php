<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AltaDoc extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alta_docs';

    public const CATEGORY_CEDULA_ESTADO       = 'cedula_estado';
    public const CATEGORY_CEDULA_MUNICIPIO    = 'cedula_municipio';
    public const CATEGORY_CEDULA_UNIVERSIDAD  = 'cedula_universidad';
    public const CATEGORY_CEDULA_ORGANISMO    = 'cedula_organismo'; // ✅ nuevo

    public const CATEGORIES = [
        self::CATEGORY_CEDULA_ESTADO,
        self::CATEGORY_CEDULA_MUNICIPIO,
        self::CATEGORY_CEDULA_UNIVERSIDAD,
        self::CATEGORY_CEDULA_ORGANISMO, // ✅ nuevo
    ];

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_CEDULA_ESTADO      => 'Cédula por estado',
            self::CATEGORY_CEDULA_MUNICIPIO   => 'Cédula por municipio',
            self::CATEGORY_CEDULA_UNIVERSIDAD => 'Cédula por universidad',
            self::CATEGORY_CEDULA_ORGANISMO   => 'Cédula por organismo', // ✅ nuevo
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        $labels = self::categoryLabels();
        return $labels[$this->category] ?? 'Documento';
    }

    protected $fillable = [
        'category',
        'title',
        'doc_date',

        // ✅ nuevos
        'expires_at',
        'link_url',
        'link_password',

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
        'size'       => 'integer',
        'doc_date'   => 'date:Y-m-d',
        'expires_at' => 'date:Y-m-d', // ✅ nuevo
    ];

    public function uploadedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    public function getHumanSizeAttribute(): string
    {
        if (!$this->size) return '—';

        $bytes = (int) $this->size;
        if ($bytes < 1024) return $bytes . ' B';

        $kb = $bytes / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';

        $mb = $kb / 1024;
        return round($mb, 1) . ' MB';
    }

    public function getFriendlyTypeAttribute(): string
    {
        $mime = (string) ($this->mime ?? '');
        $name = (string) ($this->original_name ?? '');
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
