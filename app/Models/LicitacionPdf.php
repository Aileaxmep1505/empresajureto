<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class LicitacionPdf extends Model
{
    use HasFactory;

    protected $table = 'licitacion_pdfs';

    protected $fillable = [
        'licitacion_id',
        'requisicion_id',
        'original_filename',
        'original_path',
        'pages_count',
        'status',
        'meta',
    ];

    protected $casts = [
        'pages_count' => 'integer',
        'meta'        => 'array',   // aquí vive "splits"
    ];

    /**
     * Accessor: $licitacionPdf->splits
     * Los recortes se guardan dentro de meta['splits'].
     */
    public function getSplitsAttribute(): array
    {
        $meta = $this->meta ?? [];

        if ($meta instanceof Collection) {
            $meta = $meta->toArray();
        }

        $splits = $meta['splits'] ?? [];

        if ($splits instanceof Collection) {
            $splits = $splits->toArray();
        }

        if (!is_array($splits)) {
            $decoded = json_decode($splits, true);
            $splits  = is_array($decoded) ? $decoded : [];
        }

        return $splits;
    }

    /**
     * Mutator: $licitacionPdf->splits = [...]
     */
    public function setSplitsAttribute($value): void
    {
        $meta = $this->meta ?? [];

        if ($meta instanceof Collection) {
            $meta = $meta->toArray();
        }

        $meta['splits'] = $value;
        $this->meta     = $meta;
    }

    public function pages()
    {
        return $this->hasMany(LicitacionPdfPage::class);
    }

    public function requestItems()
    {
        return $this->hasManyThrough(
            LicitacionRequestItem::class,
            LicitacionPdfPage::class,
            'licitacion_pdf_id',
            'licitacion_pdf_page_id',
            'id',
            'id'
        );
    }
}
