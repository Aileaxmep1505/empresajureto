<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre','email','rfc','tipo_persona','telefono',
        'calle','colonia','ciudad','estado','cp','estatus',
        'lat','lng','address_json',
    ];

    protected $casts = [
        'estatus' => 'boolean',
        'address_json' => 'array',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function getEtiquetaEstatusAttribute(): string
    {
        return $this->estatus ? 'activo' : 'inactivo';
    }
}
