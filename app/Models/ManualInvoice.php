<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'type',
        'serie',
        'folio',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'currency',
        'status',
        'facturapi_id',
        'facturapi_uuid',
        'verification_url',
        'facturapi_status',
        'cancellation_status',
        'stamped_at',
        'receiver_name',
        'receiver_rfc',
        'receiver_email',
        'notes',
    ];

    protected $casts = [
        'stamped_at' => 'datetime',
    ];

    /* =====================
       Relaciones
    ======================*/

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(ManualInvoiceItem::class);
    }

    /* =====================
       Helpers para la vista
    ======================*/

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'          => 'Borrador',
            'valid'          => 'Válida',
            'cancelled'      => 'Cancelada',
            'pending_cancel' => 'Pendiente de cancelación',
            default          => ucfirst($this->status),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'I' => 'Ingreso',
            'E' => 'Egreso',
            'P' => 'Pago',
            default => 'Ingreso',
        };
    }

    // Por si quieres mostrar "F10", "P1", etc.
    public function getSerieFolioAttribute(): ?string
    {
        if (!$this->serie && !$this->folio) return null;
        return trim(($this->serie ?? '') . ' ' . ($this->folio ?? ''));
    }
}
