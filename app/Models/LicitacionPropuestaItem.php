<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicitacionPropuestaItem extends Model
{
    use HasFactory;

    protected $table = 'licitacion_propuesta_items';

    protected $fillable = [
        'licitacion_propuesta_id',
        'licitacion_request_item_id',
        'product_id',

        // Orden exacto en el PDF / split
        'split_index',
        'split_order',

        // IA
        'descripcion_raw',
        'suggested_product_id',
        'match_score',
        'match_status',
        'match_reason',
        'manual_selected',

        // Cotización
        'motivo_seleccion',
        'unidad_propuesta',
        'cantidad_propuesta',
        'precio_unitario',
        'subtotal',
        'notas',

        // ✅ NUEVO: utilidad y costos
        'utilidad_pct',
        'utilidad_monto',
        'subtotal_con_utilidad',
        'costo',
        'costo_jureto',
    ];

    protected $casts = [
        'split_index'        => 'integer',
        'split_order'        => 'integer',

        'match_score'        => 'integer',
        'manual_selected'    => 'boolean',

        'cantidad_propuesta' => 'decimal:2',
        'precio_unitario'    => 'decimal:2',
        'subtotal'           => 'decimal:2',

        // ✅ NUEVO
        'utilidad_pct'        => 'decimal:2',
        'utilidad_monto'      => 'decimal:2',
        'subtotal_con_utilidad' => 'decimal:2',
        'costo'               => 'decimal:2',
        'costo_jureto'        => 'decimal:2',
    ];

    public function propuesta()
    {
        return $this->belongsTo(LicitacionPropuesta::class, 'licitacion_propuesta_id');
    }

    public function requestItem()
    {
        return $this->belongsTo(LicitacionRequestItem::class, 'licitacion_request_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // sugerido por IA
    public function suggestedProduct()
    {
        return $this->belongsTo(Product::class, 'suggested_product_id');
    }
}
