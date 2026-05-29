<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjudicacion extends Model
{
    protected $table = 'adjudicaciones';

    protected $fillable = [
        'propuesta_comercial_id',
        'propuesta_fallo_id',
        'client_id',
        'cliente_nombre',
        'folio',
        'fecha',
        'subtotal',
        'descuento_total',
        'porcentaje_impuesto',
        'impuesto_total',
        'total',
        'status',
        'observaciones',
        'meta',
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
        'impuesto_total' => 'decimal:2',
        'total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaComercial::class, 'propuesta_comercial_id');
    }

    public function fallo()
    {
        return $this->belongsTo(PropuestaFallo::class, 'propuesta_fallo_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function items()
    {
        return $this->hasMany(AdjudicacionItem::class, 'adjudicacion_id');
    }

    public function remisiones()
    {
        return $this->hasMany(Remision::class, 'adjudicacion_id');
    }
}