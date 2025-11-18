<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicitacionChecklistFacturacion extends Model
{
    use HasFactory;

    protected $table = 'licitacion_checklist_facturacion';

    protected $fillable = [
        'licitacion_id',
        'tiene_factura',
        'fecha_factura',
        'monto_factura',
        'evidencia_path',
    ];

    protected $casts = [
        'tiene_factura' => 'boolean',
        'fecha_factura' => 'date',
        'monto_factura' => 'decimal:2',
    ];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }
}
