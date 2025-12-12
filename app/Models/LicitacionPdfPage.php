<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicitacionPdfPage extends Model
{
    protected $fillable = [
        'licitacion_pdf_id',
        'page_number',
        'text',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function pdf()
    {
        return $this->belongsTo(LicitacionPdf::class, 'licitacion_pdf_id');
    }
}
