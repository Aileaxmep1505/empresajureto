<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemGlobal extends Model
{
    use HasFactory;

    protected $table = 'items_globales';

    protected $fillable = [
        'clave_verificacion',
        'descripcion_global',
        'especificaciones_global',
        'unidad_medida',
        'cantidad_total',
        'marca',
        'modelo',
        'requisiciones',
    ];

    protected $casts = [
        'cantidad_total' => 'decimal:2',
        'requisiciones'  => 'array', // se guardará como JSON
    ];

    public function itemsOriginales()
    {
        return $this->hasMany(ItemOriginal::class, 'item_global_id');
    }
}
