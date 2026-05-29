<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaFalloPartida extends Model
{
    protected $table = 'propuesta_fallo_partidas';

    protected $fillable = [
        'propuesta_fallo_id',
        'propuesta_comercial_item_id',
        'partida_label',
        'descripcion',
        'cantidad',
        'ganador',
        'empresa_ganadora',
        'precio_ganador',
        'nuestro_precio',
        'diferencia',
        'motivo',
        'source',
        'meta',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_ganador' => 'decimal:2',
        'nuestro_precio' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'meta' => 'array',
    ];

    public function fallo()
    {
        return $this->belongsTo(PropuestaFallo::class, 'propuesta_fallo_id');
    }

    public function item()
    {
        return $this->belongsTo(PropuestaComercialItem::class, 'propuesta_comercial_item_id');
    }

    public function ofertas()
    {
        return $this->hasMany(PropuestaFalloOferta::class, 'propuesta_fallo_partida_id');
    }
}