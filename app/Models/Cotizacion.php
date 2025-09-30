<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Carbon;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'cliente_id','estado','notas',
        'subtotal','descuento','envio','iva','total',
        'moneda','validez_dias','vence_el','financiamiento_config'
    ];

    protected $casts = [
        'financiamiento_config' => 'array',
        'vence_el' => 'date',
        'validez_dias' => 'integer', // evita pasar string a Carbon
    ];

    protected $appends = ['folio'];

    public function getFolioAttribute(): int
    {
        $key = $this->getKey(); // id si existe
        return $key ? (int) $key : 0;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class, 'cliente_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\CotizacionProducto::class);
    }

    public function plazos(): HasMany
    {
        return $this->hasMany(\App\Models\CotizacionPlazo::class)->orderBy('numero');
    }

    public function recalcularTotales(): void
    {
        $subtotal = $this->items->sum(function($it){
            $pu = (float)$it->precio_unitario;
            $cant = (float)$it->cantidad;
            $desc = (float)$it->descuento;
            return max(0, ($pu * $cant) - $desc);
        });

        $iva = $this->items->sum(function($it){
            $pu = (float)$it->precio_unitario;
            $cant = (float)$it->cantidad;
            $desc = (float)$it->descuento;
            $base = max(0, ($pu * $cant) - $desc);
            return round($base * ((float)$it->iva_porcentaje/100), 2);
        });

        $this->subtotal = $subtotal;
        $this->iva      = $iva;
        $this->total    = max(0, $subtotal - (float)$this->descuento + (float)$this->envio + $iva);
    }

    public function setValidez(): void
    {
        $days = (int) ($this->validez_dias ?? 0);
        if (!$this->vence_el && $days > 0) {
            $this->vence_el = Carbon::now()->addDays($days);
        }
    }
}
