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

    /**
     * ✅ Genera automático: PROV-00001 (5 dígitos)
     * - Sin año
     * - Secuencial
     * - Con bloqueo para evitar duplicados en concurrencia
     */
    protected static function booted(): void
    {
        static::creating(function (Provider $provider) {
            // Si ya viene un code manual, lo respetamos
            if (!empty($provider->code)) return;

            $provider->code = DB::transaction(function () {
                // Tomamos el mayor número existente y sumamos 1
                // code: "PROV-00001" => substring desde el 6 = "00001"
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