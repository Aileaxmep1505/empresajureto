<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remision extends Model
{
    protected $table = 'remisiones';

    protected $fillable = [
        'adjudicacion_id',
        'folio',
        'fecha',
        'status',
        'recibe_nombre',
        'observaciones',
        'pdf_path',
        'meta',
    ];

    protected $casts = [
        'fecha' => 'date',
        'meta' => 'array',
    ];

    public function adjudicacion()
    {
        return $this->belongsTo(Adjudicacion::class, 'adjudicacion_id');
    }

    public function items()
    {
        return $this->hasMany(RemisionItem::class, 'remision_id');
    }
}