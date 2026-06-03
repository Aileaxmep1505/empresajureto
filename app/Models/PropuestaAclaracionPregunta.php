<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaAclaracionPregunta extends Model
{
    protected $table = 'propuesta_aclaracion_preguntas';

    protected $fillable = [
        'propuesta_comercial_id',
        'propuesta_comercial_item_id',
        'sort',
        'tipo',
        'estado',
        'texto_usuario',
        'pregunta_generada',
        'producto_solicitado',
        'producto_sugerido',
        'sku_sugerido',
        'marca_sugerida',
        'precio_sugerido',
        'justificacion',
        'fuentes',
        'meta',
    ];

    protected $casts = [
        'fuentes' => 'array',
        'meta' => 'array',
        'precio_sugerido' => 'decimal:2',
    ];

    public function propuestaComercial()
    {
        return $this->belongsTo(PropuestaComercial::class, 'propuesta_comercial_id');
    }

    public function item()
    {
        return $this->belongsTo(PropuestaComercialItem::class, 'propuesta_comercial_item_id');
    }
}