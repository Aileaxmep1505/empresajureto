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
        'licitacion_pdf_id',
        'codigo',
        'titulo',
        'moneda',
        'fecha',
        'status',
        'subtotal',
        'iva',
        'total',
        'processed_split_indexes', // 👈 nuevo
        'merge_status',            // 👈 nuevo
    ];

    protected $casts = [
        'fecha'                  => 'date',
        'subtotal'               => 'decimal:2',
        'iva'                    => 'decimal:2',
        'total'                  => 'decimal:2',
        'processed_split_indexes'=> 'array',
    ];

    public function items()
    {
        return $this->hasMany(LicitacionPropuestaItem::class, 'licitacion_propuesta_id');
    }

    public function licitacionPdf()
    {
        return $this->belongsTo(LicitacionPdf::class, 'licitacion_pdf_id');
    }
}
