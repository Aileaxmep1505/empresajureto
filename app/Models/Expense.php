<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        // Relaciones / llaves
        'expense_category_id',
        'vehicle_id',
        'payroll_period_id',
        'created_by',

        // Datos base
        'vendor',
        'expense_date',
        'performed_at',
        'concept',
        'description',

        // Montos / estado
        'amount',
        'currency',
        'payment_method',
        'status',
        'tags',

        // Evidencias (✅ asegúrate de tener estas columnas en DB)
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'evidence_paths', // JSON/array (múltiples evidencias)

        // Firmas / aprobación / recibo
        'manager_signature_path',
        'counterparty_signature_path',
        'nip_approved_by',
        'nip_approved_at',
        'pdf_receipt_path',

        // (Opcionales si existen en tu tabla)
        'entry_kind',
        'expense_type',
        'vehicle_category',
        'payroll_category',
        'payroll_period',

        // Movimientos (si existen en tu tabla)
        'manager_id',
        'counterparty_id',
        'movement_self_receive',
        'movement_mode',
        'qr_token',
        'qr_expires_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'expense_date'       => 'date',
        'performed_at'       => 'datetime',
        'amount'             => 'decimal:2',
        'attachment_size'    => 'integer',
        'nip_approved_at'    => 'datetime',
        'qr_expires_at'      => 'datetime',
        'acknowledged_at'    => 'datetime',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',

        // ✅ Para evidencias múltiples guardadas como JSON
        'evidence_paths'     => 'array',
    ];

    /* ---------------- Relaciones ---------------- */

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ExpenseCategory::class, 'expense_category_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vehicle::class, 'vehicle_id');
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(\App\Models\PayrollPeriod::class, 'payroll_period_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'nip_approved_by');
    }

    /* ---------------- Accessors / Helpers ---------------- */

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_receipt_path
            ? asset('storage/' . ltrim($this->pdf_receipt_path, '/'))
            : null;
    }

    public function getManagerSignatureUrlAttribute(): ?string
    {
        return $this->manager_signature_path
            ? asset('storage/' . ltrim($this->manager_signature_path, '/'))
            : null;
    }

    public function getCounterpartySignatureUrlAttribute(): ?string
    {
        return $this->counterparty_signature_path
            ? asset('storage/' . ltrim($this->counterparty_signature_path, '/'))
            : null;
    }

    /**
     * Evidencia principal (compatibilidad):
     * - Si tienes attachment_path, devuelve su URL
     * - Si no, intenta con evidence_paths[0]
     */
    public function getEvidenceUrlAttribute(): ?string
    {
        if (!empty($this->attachment_path)) {
            return Storage::disk('public')->url($this->attachment_path);
        }

        $first = $this->evidence_paths[0] ?? null;
        return $first ? Storage::disk('public')->url($first) : null;
    }

    public function getHasEvidenceAttribute(): bool
    {
        if (!empty($this->attachment_path)) return true;
        return is_array($this->evidence_paths) && count($this->evidence_paths) > 0;
    }

    public function getHasReceiptAttribute(): bool
    {
        return (bool) $this->pdf_receipt_path;
    }

    /**
     * ✅ Lista completa de evidencias (attachment + evidence_paths), sin duplicados.
     * Retorna rutas relativas en disk "public".
     */
    public function evidencePaths(): array
    {
        $paths = [];

        if (is_array($this->evidence_paths)) {
            foreach ($this->evidence_paths as $p) {
                if (is_string($p) && trim($p) !== '') $paths[] = trim($p);
            }
        }

        if (!empty($this->attachment_path)) {
    
