<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicitacionFile extends Model
{
    use HasFactory;

    protected $table = 'licitacion_files';

    protected $fillable = [
        'nombre_original',
        'ruta',
        'mime_type',
        'estado',
        'total_items',
        'error_mensaje',
    ];

    public function itemsOriginales()
    {
        return $this->hasMany(ItemOriginal::class);
    }
}
