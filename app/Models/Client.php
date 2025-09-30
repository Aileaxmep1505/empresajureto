<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre','email','tipo_cliente','rfc','contacto','telefono',
        'calle','colonia','ciudad','estado','cp','estatus',
    ];

    protected $casts = [
        'estatus' => 'boolean',
    ];

    public function getEtiquetaEstatusAttribute(): string
    {
        return $this->estatus ? 'activo' : 'inactivo';
    }

    public function getEtiquetaTipoAttribute(): string
    {
        return $this->tipo_cliente === 'gobierno' ? 'Gobierno' : ($this->tipo_cliente === 'empresa' ? 'Empresa' : '—');
    }
}
