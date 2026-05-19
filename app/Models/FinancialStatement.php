<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FinancialStatement extends Model
{
    protected $fillable = [
        'uploaded_by',
        'title',
        'period',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'notes',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /* ── Relaciones ──────────────────────────────── */

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* ── Accessors ──────────────────────────────── */

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1_048_576) return round($bytes / 1_048_576, 1) . ' MB';
        if ($bytes >= 1_024)     return round($bytes / 1_024, 0) . ' KB';
        return $bytes . ' B';
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'balance_general'   => 'Balance General',
            'estado_resultados' => 'Estado de Resultados',
            'flujo_efectivo'    => 'Flujo de Efectivo',
            'notas'             => 'Notas a los Estados',
            default             => 'Otro',
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'balance_general'   => 'scale',
            'estado_resultados' => 'trending-up',
            'flujo_efectivo'    => 'droplets',
            'notas'             => 'file-text',
            default             => 'file',
        };
    }
}