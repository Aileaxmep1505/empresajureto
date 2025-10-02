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
        // si existe en tu schema, puedes agregar 'venta_id' aquí sin problema
    ];

    protected $casts = [
        'financiamiento_config' => 'array',
        'vence_el' => 'date',
        'validez_dias' => 'integer', // evita pasar string a Carbon

        // ── AÑADIDO: asegura tipos numéricos coherentes ─────────────────────
        'subtotal' => 'float',
        'descuento'=> 'float',
        'envio'    => 'float',
        'iva'      => 'float',
        'total'    => 'float',
    ];

    // ── MANTENIDO + AÑADIDOS: se agregan nuevas props sin quitar 'folio' ───
    protected $appends = ['folio', 'estado_label', 'expira_en_dias', 'vencida'];

    // ========== LO QUE YA TENÍAS ==========
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
    // ========== /LO QUE YA TENÍAS ==========


    // ────────────────────────────────────────────────────────────────────────
    // AÑADIDOS (no quitan nada de lo anterior)
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Relación opcional a Venta (si tienes 'venta_id' en 'cotizaciones').
     * Si tu FK se llama distinto, ajusta el segundo parámetro.
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Venta::class, 'venta_id');
    }

    /** Etiqueta legible del estado. */
    public function getEstadoLabelAttribute(): string
    {
        return match ($this->estado) {
            'converted' => 'Convertida',
            'cancelled' => 'Cancelada',
            'abierta', 'open', null, '' => 'Abierta',
            default => ucfirst((string) $this->estado),
        };
    }

    /** Días que faltan para vencer (negativo si ya venció). */
    public function getExpiraEnDiasAttribute(): ?int
    {
        if (!$this->vence_el instanceof Carbon) return null;
        return Carbon::now()->startOfDay()->diffInDays($this->vence_el->startOfDay(), false);
    }

    /** ¿Está vencida? */
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

    // ── Scopes útiles (filtros encadenables) ────────────────────────────────
    public function scopeAbierta($q)
    {
        return $q->where(function ($q) {
            $q->whereNull('estado')
              ->orWhereIn('estado', ['abierta', 'open', 'borrador', 'draft']);
        });
    }

    public function scopeVencida($q)
    {
        return $q->whereNotNull('vence_el')
                 ->where('vence_el', '<', Carbon::now()->startOfDay());
    }

    public function scopeDeCliente($q, $clienteId)
    {
        return $q->where('cliente_id', $clienteId);
    }

    // ── Hook de modelo: mantiene tu setValidez() antes de guardar ──────────
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->setValidez();
            // Si quieres recalcular siempre y ya tienes items cargados:
            // if ($model->relationLoaded('items')) {
            //     $model->recalcularTotales();
            // }
        });
    }
}
