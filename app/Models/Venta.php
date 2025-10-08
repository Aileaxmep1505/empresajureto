<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id','cotizacion_id','estado','fecha','notas',
        'subtotal','descuento','envio','iva','total',
        'moneda','tipo_cambio','financiamiento_config','user_id',

        // ===== Campos de factura que sí existen en tu tabla =====
        'serie','folio','factura_id','factura_uuid',
        'factura_pdf_url','factura_xml_url','timbrada_en',
    ];

    protected $casts = [
        'subtotal' => 'float',
        'descuento'=> 'float',
        'envio'    => 'float',
        'iva'      => 'float',
        'total'    => 'float',
        'tipo_cambio' => 'float',
        'financiamiento_config' => 'array',
        'fecha'       => 'datetime',
        'timbrada_en' => 'datetime',
    ];

    protected $appends = ['folio_display'];

    // Si quieres seguir usando $venta->folio sin romper nada:
    public function getFolioAttribute(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    // y un alias "bonito" que combine serie+folio si existen:
    public function getFolioDisplayAttribute(): string
    {
        $serie = trim((string)($this->serie ?? ''));
        $folio = trim((string)($this->folio ?? ''));
        if ($serie !== '' || $folio !== '') {
            return trim($serie.' '.$folio);
        }
        return (string) $this->folio; // el accessor anterior (id)
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
