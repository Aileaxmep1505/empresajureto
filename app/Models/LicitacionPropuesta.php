<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicitacionPropuesta extends Model
{
    use HasFactory;

    protected $table = 'licitacion_propuestas';

    protected $fillable = [
        'licitacion_id',
        'requisicion_id',
        'codigo',
        'titulo',
        'moneda',
        'fecha',
        'status',
        'subtotal',
        'iva',
        'total',
    ];

    protected $casts = [
        'fecha'    => 'date',
        'subtotal' => 'decimal:2',
        'iva'      => 'decimal:2',
        'total'    => 'decimal:2',
    ];

    /**
     * Renglones de la propuesta (cuadro comparativo).
     */
    public function items()
    {
        return $this->hasMany(LicitacionPropuestaItem::class, 'licitacion_propuesta_id');
    }
}
