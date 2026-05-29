<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaFalloOferta extends Model
{
    protected $table = 'propuesta_fallo_ofertas';

    protected $fillable = [
        'propuesta_fallo_partida_id',
        'empresa',
        'es_jureto',
        'gano',
        'precio',
        'cantidad',
        'meta',
    ];

    protected $casts = [
        'es_jureto' => 'boolean',
        'gano' => 'boolean',
        'precio' => 'decimal:2',
        'cantidad' => 'decimal:2',
        'meta' => 'array',
    ];

    public function partida()
    {
        return $this->belongsTo(PropuestaFalloPartida::class, 'propuesta_fallo_partida_id');
    }
}