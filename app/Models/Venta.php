<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Illuminate\Support\Facades\Schema;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id','cotizacion_id','estado','fecha','notas',
        'subtotal','descuento','envio','iva','total',
        'moneda','tipo_cambio','financiamiento_config','user_id',

        // Factura
        'serie','folio','factura_id','factura_uuid',
        'factura_pdf_url','factura_xml_url','timbrada_en',

        // Nuevos / financieros
        'utilidad_global',      // % aplicado al momento de vender (clonado de cotización)
        'inversion_total',      // Σ (cost * cantidad)
        'ganancia_estimada',    // subtotal - inversion_total
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

        // Nuevos
        'utilidad_global'   => 'float',
        'inversion_total'   => 'float',
        'ganancia_estimada' => 'float',
    ];

    protected $appends = ['folio_display'];

    /** Alias folio = id (como pediste) */
    public function getFolioAttribute(): int
    {
        return (int) ($this->attributes['id'] ?? 0);
    }

    /** Mostrar folio siempre como ID (sin serie) */
    public function getFolioDisplayAttribute(): string
    {
        return (string) $this->folio; // usa accessor anterior (id)
    }

    // ================== Relaciones ==================
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

    // ================== Totales ==================
    /**
     * Recalcula totales de la venta:
     * - base (por fila) = max(0, precio_unitario*cantidad - descuento_fila)
     * - iva_fila        = base * (iva_porcentaje/100)
     * - subtotal        = Σ base
     * - iva             = Σ iva_fila
     * - total           = subtotal - descuento_global + envio + iva
     *
     * Adicional (si existen columnas en 'ventas'):
     * - inversion_total   = Σ (cost * cantidad)   // desde venta_productos.cost
     * - ganancia_estimada = subtotal - inversion_total
     */
    public function recalcularTotales(): void
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        $sumBase  = 0.0;
        $sumIva   = 0.0;
        $sumCosto = 0.0;

        foreach ($items as $it) {
            $pu   = (float) ($it->precio_unitario ?? 0);
            $qty  = (float) ($it->cantidad ?? 0);
            $desc = (float) ($it->descuento ?? 0);
            $ivaP = (float) ($it->iva_porcentaje ?? 0);
            $cost = (float) ($it->cost ?? 0); // puede ser null si no migraste aún

            $base    = max(0, ($pu * $qty) - $desc);
            $ivaFila = round($base * ($ivaP / 100), 2);

            $sumBase  += $base;
            $sumIva   += $ivaFila;
            $sumCosto += ($cost * $qty);

            // Si tu tabla tiene columnas para snapshot por fila, puedes mantenerlas actualizadas:
            if (Schema::hasColumn($it->getTable(), 'importe_sin_iva')) {
                $it->importe_sin_iva = $base;
            }
            if (Schema::hasColumn($it->getTable(), 'iva_monto')) {
                $it->iva_monto = $ivaFila;
            }
            // No guardamos aquí para evitar N escrituras; el caller decide si persiste los items.
        }

        $this->subtotal = round($sumBase, 2);
        $this->iva      = round($sumIva, 2);
        $this->total    = max(0, round($this->subtotal - (float)$this->descuento + (float)$this->envio + $this->iva, 2));

        if (Schema::hasColumn($this->getTable(), 'inversion_total')) {
            $this->inversion_total = round($sumCosto, 2);
        }
        if (Schema::hasColumn($this->getTable(), 'ganancia_estimada')) {
            $inv = isset($this->inversion_total) ? (float)$this->inversion_total : $sumCosto;
            $this->ganancia_estimada = round($this->subtotal - $inv, 2);
        }
    }
}
