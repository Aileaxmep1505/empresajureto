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
    ];

    protected $casts = [
        'match_score'        => 'integer',
        'manual_selected'    => 'boolean',
        'cantidad_propuesta' => 'decimal:2',
        'precio_unitario'    => 'decimal:2',
        'subtotal'           => 'decimal:2',
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

    // ✅ esta relación es la que te faltaba (por eso el error suggestedProduct)
    public function suggestedProduct()
    {
        return $this->belongsTo(Product::class, 'suggested_product_id');
    }
}
