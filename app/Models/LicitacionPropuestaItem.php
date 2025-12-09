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
        'match_score',
        'motivo_seleccion',
        'unidad_propuesta',
        'cantidad_propuesta',
        'precio_unitario',
        'subtotal',
        'notas',
    ];

    protected $casts = [
        'match_score'        => 'integer',
        'cantidad_propuesta' => 'decimal:2',
        'precio_unitario'    => 'decimal:2',
        'subtotal'           => 'decimal:2',
    ];

    /**
     * Propuesta económica a la que pertenece este renglón.
     */
    public function propuesta()
    {
        return $this->belongsTo(LicitacionPropuesta::class, 'licitacion_propuesta_id');
    }

    /**
     * Renglón original solicitado en la requisición (texto del PDF).
     */
    public function requestItem()
    {
        return $this->belongsTo(LicitacionRequestItem::class, 'licitacion_request_item_id');
    }

    /**
     * Producto de catálogo sugerido/ofertado.
     */
    public function product()
    {
        // Model Product en tabla products
        return $this->belongsTo(Product::class, 'product_id');
    }
}
