<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicitacionPdfPage extends Model
{
    use HasFactory;

    protected $table = 'licitacion_pdf_pages';

    protected $fillable = [
        'licitacion_pdf_id',
        'page_number',
        'raw_text',
        'tokens_count',
        'status',
        'error_message',
    ];

    /**
     * PDF al que pertenece esta página.
     */
    public function pdf()
    {
        return $this->belongsTo(LicitacionPdf::class, 'licitacion_pdf_id');
    }

    /**
     * Items extraídos por IA de esta página.
     */
    public function requestItems()
    {
        return $this->hasMany(LicitacionRequestItem::class, 'licitacion_pdf_page_id');
    }
}
