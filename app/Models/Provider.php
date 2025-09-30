<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre','email','rfc','tipo_persona','telefono',
        'calle','colonia','ciudad','estado','cp',
        'estatus',
    ];

    protected $casts = [
        'estatus' => 'boolean',
    ];

    public function getEtiquetaEstatusAttribute(): string
    {
        return $this->estatus ? 'activo' : 'inactivo';
    }
}
