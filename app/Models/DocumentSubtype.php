<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentSubtype extends Model
{
    use HasFactory;

    protected $fillable = ['section_id','key','name'];

    public function section()
    {
        return $this->belongsTo(DocumentSection::class);
    }
}
