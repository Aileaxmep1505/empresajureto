<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Expense extends Model
{
    protected $table = 'expenses';

    protected $fillable = [
        // Relaciones / llaves (aunque luego quites expense_category_id)
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

        // Evidencia
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',

        // Firmas / aprobación / recibo
        'manager_signature_path',
        'counterparty_signature_path',
        'nip_approved_by',
        'nip_approved_at',
        'pdf_receipt_path',
    ];

    protected $casts = [
        'expense_date'    => 'date',
        'performed_at'    => 'datetime',
        'amount'          => 'decimal:2',
        'attachment_size' => 'integer',
        'nip_approved_at' => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
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
        return $this->pdf_receipt_path ? asset('storage/' . ltrim($this->pdf_receipt_path, '/')) : null;
    }

    public function getManagerSignatureUrlAttribute(): ?string
    {
        return $this->manager_signature_path ? asset('storage/' . ltrim($this->manager_signature_path, '/')) : null;
    }

    public function getCounterpartySignatureUrlAttribute(): ?string
    {
        return $this->counterparty_signature_path ? asset('storage/' . ltrim($this->counterparty_signature_path, '/')) : null;
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        return $this->attachment_path ? Storage::disk('public')->url($this->attachment_path) : null;
    }

    public function getHasEvidenceAttribute(): bool
    {
        return (bool) $this->attachment_path;
    }

    public function getHasReceiptAttribute(): bool
    {
        return (bool) $this->pdf_receipt_path;
    }
}
