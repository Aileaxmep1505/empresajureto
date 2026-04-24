<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentAiRun extends Model
{
    protected $fillable = [
        'licitacion_pdf_id',
        'python_job_id',
        'filename',
        'pages_per_chunk',
        'status',
        'error',
        'result_json',
        'structured_json',
        'items_json',
    ];

    protected $casts = [
        'result_json' => 'array',
        'structured_json' => 'array',
        'items_json' => 'array',
    ];
}