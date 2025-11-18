<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicitacionPregunta extends Model
{
    use HasFactory;

    protected $fillable = [
        'licitacion_id',
        'user_id',
        'texto_pregunta',
        'notas_internas',
        'fecha_pregunta',
        'texto_respuesta',
        'fecha_respuesta',
        'esta_bloqueada',
    ];

    protected $casts = [
        'fecha_pregunta'  => 'datetime',
        'fecha_respuesta' => 'datetime',
        'esta_bloqueada'  => 'boolean',
    ];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
