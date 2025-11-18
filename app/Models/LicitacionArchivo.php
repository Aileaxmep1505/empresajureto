<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicitacionArchivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'licitacion_id',
        'tipo',
        'path',
        'nombre_original',
        'mime_type',
        'uploaded_by',
    ];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
