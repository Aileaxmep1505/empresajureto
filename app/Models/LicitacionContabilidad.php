<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicitacionContabilidad extends Model
{
    use HasFactory;

    protected $table = 'licitacion_contabilidad';

    protected $fillable = [
        'licitacion_id',
        'monto_inversion_estimado',
        'costo_total',
        'detalle_costos',
        'utilidad_estimada',
        'notas',
    ];

    protected $casts = [
        'monto_inversion_estimado' => 'decimal:2',
        'costo_total'              => 'decimal:2',
        'utilidad_estimada'        => 'decimal:2',
        'detalle_costos'           => 'array',
    ];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }
}
