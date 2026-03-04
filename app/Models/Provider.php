<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        // ✅ Folio simple (PROV-00001)
        'code',

        // ✅ NUEVO: Empresa
        'empresa',

        // Contacto / Asesor
        'nombre','email','telefono',

        // Fiscal
        'rfc','tipo_persona',

        // Dirección
        'calle','colonia','ciudad','estado','cp',

        // Estado
        'estatus',

        // Geodata opcional
        'lat','lng','address_json',
    ];

    protected $casts = [
        'estatus' => 'boolean',
        'address_json' => 'array',
        'lat' => 'float',
        'lng' => 'float',
    ];

    /**
     * ✅ Genera automático: PROV-00001 (5 dígitos)
     */
    protected static function booted(): void
    {
        static::creating(function (Provider $provider) {
            if (!empty($provider->code)) return;

            $provider->code = DB::transaction(function () {
                $maxNum = DB::table('providers')
                    ->where('code', 'like', 'PROV-%')
                    ->lockForUpdate()
                    ->selectRaw("MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)) as m")
                    ->value('m');

                $next = ((int) $maxNum) + 1;

                return 'PROV-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
            });
        });
    }

    public function getEtiquetaEstatusAttribute(): string
    {
        return $this->estatus ? 'activo' : 'inactivo';
    }
}