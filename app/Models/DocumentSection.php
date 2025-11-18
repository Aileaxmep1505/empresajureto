<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentSection extends Model
{
    use HasFactory;

    protected $fillable = ['key','name','description'];

    public function subtypes()
    {
        return $this->hasMany(DocumentSubtype::class, 'section_id');
    }
}
