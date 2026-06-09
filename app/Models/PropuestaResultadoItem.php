<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaResultadoItem extends Model
{
    protected $table = 'propuesta_resultado_items';

    protected $fillable = [
        'propuesta_resultado_id',
        'propuesta_comercial_item_id',
        'sort',
        'partida_numero',
        'descripcion_original',
        'unidad_solicitada',
        'cantidad',
        'costo_unitario',
        'precio_unitario',
        'precio_ofertado',
        'subtotal',
        'resultado',
        'motivo_perdida',
        'proveedor_ganador',
        'precio_ganador',
        'diferencia_monto',
        'diferencia_pct',
        'analisis_ia',
        'meta',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_ofertado' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'precio_ganador' => 'decimal:2',
        'diferencia_monto' => 'decimal:2',
        'diferencia_pct' => 'decimal:2',
        'meta' => 'array',
    ];

    public function resultado()
    {
        return $this->belongsTo(PropuestaResultado::class, 'propuesta_resultado_id');
    }
}