<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingProfile extends Model
{
    // Si tu tabla no sigue la convención, descomenta y ajusta:
    // protected $table = 'billing_profiles';

    protected $fillable = [
        'user_id',
        'razon_social',
        'rfc',
        'regimen',       // código SAT (p.ej. 612, 626…)
        'uso_cfdi',      // código SAT (p.ej. G03, S01…)
        'email',

        // Extras solicitados:
        'contacto',
        'telefono',
        'direccion',
        'zip',           // aquí guardamos el C.P.
        'colonia',
        'estado',
        'metodo_pago',   // “Tarjeta”
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
