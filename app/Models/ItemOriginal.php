<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemOriginal extends Model
{
    use HasFactory;

    protected $table = 'items_originales';

    protected $fillable = [
        'licitacion_file_id',
        'requisicion',
        'partida',
        'clave_verificacion',
        'descripcion_bien',
        'especificaciones',
        'cantidad',
        'unidad_medida',
        'marca',
        'modelo',
        'embedding',
        'item_global_id',
    ];

    protected $casts = [
        'embedding' => 'array',
        'cantidad'  => 'decimal:2',
    ];

    public function archivo()
    {
        return $this->belongsTo(LicitacionFile::class, 'licitacion_file_id');
    }

    public function itemGlobal()
    {
        return $this->belongsTo(ItemGlobal::class, 'item_global_id');
    }
}
