<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicitacionChecklistCompra extends Model
{
    use HasFactory;

    protected $fillable = [
        'licitacion_id',
        'descripcion_item',
        'completado',
        'fecha_entregado',
        'entregado_por',
        'observaciones',
    ];

    protected $casts = [
        'completado' => 'boolean',
        'fecha_entregado' => 'date',
    ];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }
}
