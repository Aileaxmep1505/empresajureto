<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogAiIntakeFile extends Model
{
    protected $table = 'catalog_ai_intake_files';

    protected $fillable = [
        'intake_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'page_no',
    ];

    public function intake()
    {
        return $this->belongsTo(CatalogAiIntake::class, 'intake_id');
    }
}
