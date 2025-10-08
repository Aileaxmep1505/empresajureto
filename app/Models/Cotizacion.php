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
        'moneda','validez_dias','vence_el','financiamiento_config',
        // ==== NUEVOS CAMPOS ====
        'utilidad_global',       // % global
        'inversion_total',       // suma de cost*cantidad
        'ganancia_estimada',     // subtotal (antes desc. global) - inversion_total
        // 'venta_id', // opcional si existe
    ];

    protected $casts = [
        'financiamiento_config' => 'array',
        'vence_el'      => 'date',
        'validez_dias'  => 'integer',

        // numéricos
        'utilidad_global'   => 'float',
        'subtotal'          => 'float',
        'descuento'         => 'float', // descuento global ($)
        'envio'             => 'float',
        'iva'               => 'float',
        'total'             => 'float',
        'inversion_total'   => 'float',
        'ganancia_estimada' => 'float',
    ];

    protected $appends = ['folio', 'estado_label', 'expira_en_dias', 'vencida'];

    // ================== RELACIONES ==================
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class, 'cliente_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\CotizacionProducto::class, 'cotizacion_id');
    }

    public function plazos(): HasMany
    {
        return $this->hasMany(\App\Models\CotizacionPlazo::class)->orderBy('numero');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Venta::class, 'venta_id');
    }

    // ================== ACCESORS ==================
    public function getFolioAttribute(): int
    {
        return $this->getKey() ? (int) $this->getKey() : 0;
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'converted' => 'Convertida',
            'cancelled' => 'Cancelada',
            'abierta', 'open', null, '' => 'Abierta',
            default => ucfirst((string) $this->estado),
        };
    }

    public function getExpiraEnDiasAttribute(): ?int
    {
        if (!$this->vence_el instanceof Carbon) return null;
        return Carbon::now()->startOfDay()->diffInDays($this->vence_el->startOfDay(), false);
    }

    public function getVencidaAttribute(): bool
    {
        return $this->isVencida();
    }

    public function isVencida(): bool
    {
        return $this->vence_el instanceof Carbon
            ? Carbon::now()->greaterThan($this->vence_el->endOfDay())
            : false;
    }

    // ================== LÓGICA ==================
    public function setValidez(): void
    {
        $days = (int) ($this->validez_dias ?? 0);
        if (!$this->vence_el && $days > 0) {
            $this->vence_el = Carbon::now()->addDays($days);
        }
    }

    /**
     * Recalcula y deja snapshot en items:
     * - precio_unitario = cost * (1 + utilidad_global%)
     * - base = (precio_unitario*cantidad - descuento_fila_monto) >= 0
     * - iva_monto = base * (iva_porcentaje/100)
     * - importe_total = base + iva_monto
     * - subtotal = sum(base), iva = sum(iva_monto)
     * - total = subtotal - descuento_global + envio + iva
     * - inversion_total = sum(cost*cantidad)
     * - ganancia_estimada = (subtotal) - inversion_total
     */
    public function recalcularTotales(): void
    {
        $utilidad = (float)($this->utilidad_global ?? 0); // %
        $descuentoGlobal = (float)($this->descuento ?? 0);
        $envio = (float)($this->envio ?? 0);

        $subtotal = 0.0;
        $ivaTotal = 0.0;
        $inversion = 0.0;

        // Si no está cargada la relación, evitas N+1
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        foreach ($items as $it) {
            $cost   = (float)$it->cost;                 // NUEVO: base real
            $qty    = (float)$it->cantidad;
            $desc   = (float)$it->descuento;            // monto $
            $ivaPct = (float)$it->iva_porcentaje;       // %

            $precioUnit = round($cost * (1 + $utilidad/100), 2);
            $base = max(0, ($precioUnit * $qty) - $desc);
            $ivaMonto = round($base * ($ivaPct/100), 2);
            $importeTotal = round($base + $ivaMonto, 2);

            // Actualiza snapshot de la fila
            $it->precio_unitario  = $precioUnit;
            $it->importe_sin_iva  = round($base, 2);
            $it->iva_monto        = $ivaMonto;
            $it->importe_total     = $importeTotal;

            // Mantén 'importe' como alias de total por compatibilidad
            if ($it->isFillable('importe')) {
                $it->importe = $importeTotal;
            }

            // Acumula
            $subtotal += $base;
            $ivaTotal += $ivaMonto;
            $inversion += ($cost * $qty);
        }

        $this->subtotal = round($subtotal, 2);
        $this->iva      = round($ivaTotal, 2);
        $this->inversion_total   = round($inversion, 2);
        $this->ganancia_estimada = round($subtotal - $inversion, 2);

        $this->total = max(0, round($subtotal - $descuentoGlobal + $envio + $ivaTotal, 2));
    }

    // Hook de modelo: mantiene tu setValidez() antes de guardar
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->setValidez();
        });
    }
}
