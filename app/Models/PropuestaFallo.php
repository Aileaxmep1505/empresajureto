<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaFallo extends Model
{
    protected $table = 'propuesta_fallos';

    protected $fillable = [
        'propuesta_comercial_id',
        'numero_acta',
        'fecha_fallo',
        'file_path',
        'resultado',
        'document_ai_run_id',
        'ocr_status',
        'ocr_text',
        'meta',
    ];

    protected $casts = [
        'fecha_fallo' => 'date',
        'meta' => 'array',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaComercial::class, 'propuesta_comercial_id');
    }

    public function partidas()
    {
        return $this->hasMany(PropuestaFalloPartida::class, 'propuesta_fallo_id');
    }

    public function adjudicaciones()
    {
        return $this->hasMany(Adjudicacion::class, 'propuesta_fallo_id');
    }
}