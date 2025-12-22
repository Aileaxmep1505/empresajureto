<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LicitacionPdfChatMessage extends Model
{
    protected $fillable = [
        'licitacion_pdf_id',
        'user_id',
        'role',
        'content',
        'sources',
    ];

    protected $casts = [
        'sources' => 'array',
    ];
}
