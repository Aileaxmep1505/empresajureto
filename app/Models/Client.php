<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'razon_social',     // Nombre registrado en el SAT
        'email',

        // Segmento comercial (no fiscal)
        'tipo_cliente',     // gobierno / empresa / particular / otro

        // Tipo de persona para SAT
        'tipo_persona',     // fisica / moral

        'rfc',
        'regimen_fiscal',   // c_RegimenFiscal SAT (ej: 601, 612, 605, etc.)
        'cfdi_uso',         // c_UsoCFDI (ej: G03, D01, etc.)

        'contacto',
        'telefono',

        // Domicilio fiscal
        'pais',             // MEX
        'calle',
        'num_exterior',     // Núm. exterior
        'num_interior',     // Núm. interior
        'colonia',
        'cp',               // Código postal
        'ciudad',
        'municipio',        // Municipio
        'estado',

        'estatus',
    ];

    protected $casts = [
        'estatus' => 'boolean',
    ];

    /**
     * Nombre que se muestra como "fiscal".
     * Si hay razón social, usamos esa, si no el nombre normal.
     */
    public function getNombreFiscalAttribute(): string
    {
        return $this->razon_social ?: $this->nombre;
    }

    public function getEtiquetaEstatusAttribute(): string
    {
        return $this->estatus ? 'activo' : 'inactivo';
    }

    /**
     * Etiqueta de tipo de persona (física / moral) - uso fiscal.
     */
    public function getEtiquetaPersonaAttribute(): string
    {
        return match ($this->tipo_persona) {
            'fisica' => 'Persona física',
            'moral'  => 'Persona moral',
            default  => '—',
        };
    }

    /**
     * Etiqueta de tipo de cliente (segmento comercial).
     * NO es lo mismo que persona física/moral.
     */
    public function getEtiquetaTipoAttribute(): string
    {
        return match ($this->tipo_cliente) {
            'gobierno'   => 'Gobierno',
            'empresa'    => 'Empresa',
            'particular' => 'Particular',
            'otro'       => 'Otro',
            default      => '—',
        };
    }
}
