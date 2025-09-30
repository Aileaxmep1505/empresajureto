<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id','cotizacion_id','estado','notas',
        'subtotal','descuento','envio','iva','total',
        'moneda','financiamiento_config'
    ];

    protected $casts = [
        'financiamiento_config' => 'array',
    ];

    protected $appends = ['folio'];

    public function getFolioAttribute(): int
    {
        $key = $this->getKey();
        return $key ? (int) $key : 0;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class, 'cliente_id');
    }

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Cotizacion::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\VentaProducto::class);
    }

    public function plazos(): HasMany
    {
        return $this->hasMany(\App\Models\VentaPlazo::class)->orderBy('numero');
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
}
