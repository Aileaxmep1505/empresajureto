<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjudicacion extends Model
{
    protected $table = 'adjudicaciones';

    protected $fillable = [
        'propuesta_comercial_id',
        'folio',
        'titulo',
        'cliente',
        'total_partidas',
        'ganadas_count',
        'perdidas_count',
        'subtotal_ganadas',
        'total_ganadas',
        'status',
        'meta',
    ];

    protected $casts = [
        'subtotal_ganadas' => 'decimal:2',
        'total_ganadas' => 'decimal:2',
        'meta' => 'array',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaComercial::class, 'propuesta_comercial_id');
    }

    public function items()
    {
        return $this->hasMany(AdjudicacionItem::class, 'adjudicacion_id')
            ->orderBy('sort')->orderBy('id');
    }

    public function ganadas()
    {
        return $this->hasMany(AdjudicacionItem::class, 'adjudicacion_id')
            ->where('resultado', 'ganada')
            ->orderBy('sort');
    }

    public function perdidas()
    {
        return $this->hasMany(AdjudicacionItem::class, 'adjudicacion_id')
            ->where('resultado', 'perdida')
            ->orderBy('sort');
    }
}