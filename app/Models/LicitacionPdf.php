<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'meta'        => 'array',
    ];

    /**
     * Páginas del PDF (texto por página, si algún día vuelves a usar IA).
     */
    public function pages()
    {
        return $this->hasMany(LicitacionPdfPage::class);
    }

    /**
     * Items solicitados extraídos del PDF (through pages).
     */
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
