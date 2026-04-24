<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropuestaComercial extends Model
{
    protected $table = 'propuestas_comerciales';

    protected $fillable = [
        'licitacion_pdf_id',
        'document_ai_run_id',
        'titulo',
        'folio',
        'cliente',
        'porcentaje_utilidad',
        'porcentaje_descuento',
        'porcentaje_impuesto',
        'subtotal',
        'descuento_total',
        'impuesto_total',
        'total',
        'status',
        'meta',
    ];

    protected $casts = [
        'porcentaje_utilidad' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'descuento_total' => 'decimal:2',
        'impuesto_total' => 'decimal:2',
        'total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(PropuestaComercialItem::class, 'propuesta_comercial_id');
    }

    public function aiRun()
    {
        return $this->belongsTo(DocumentAiRun::class, 'document_ai_run_id');
    }
}