<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
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

        // Evidencias (si existen en DB)
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'attachment_size',
        'evidence_paths',

        // Firmas / aprobación / recibo
        'manager_signature_path',
        'counterparty_signature_path',
        'nip_approved_by',
        'nip_approved_at',
        'pdf_receipt_path',

        // Otros campos que sí se ven en tu SELECT
        'entry_kind',
        'expense_type',
        'vehicle_category',
        'payroll_category',
        'payroll_period',

        // Movimientos (según tu SELECT)
        'manager_id',
        'counterparty_id',
        'movement_self_receive',
        'movement_mode',
        'qr_token',
        'qr_expires_at',
        'acknowledged_at',
    ];

    protected $casts = [
        'expense_date'    => 'date',
        'performed_at'    => 'datetime',
        'amount'          => 'decimal:2',
        'attachment_size' => 'integer',
        'nip_approved_at' => 'datetime',
        'qr_expires_at'   => 'datetime',
        'acknowledged_at' => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // ✅ SOLO casteamos evidence_paths a array si la columna existe
        if (Schema::hasColumn($this->table, 'evidence_paths')) {
            $this->casts['evidence_paths'] = 'array';
        }
    }

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
        if (!Schema::hasColumn($this->table, 'pdf_receipt_path')) return null;

        return $this->pdf_receipt_path
            ? asset('storage/' . ltrim($this->pdf_receipt_path, '/'))
            : null;
    }

    public function getManagerSignatureUrlAttribute(): ?string
    {
        if (!Schema::hasColumn($this->table, 'manager_signature_path')) return null;

        return $this->manager_signature_path
            ? asset('storage/' . ltrim($this->manager_signature_path, '/'))
            : null;
    }

    public function getCounterpartySignatureUrlAttribute(): ?string
    {
        if (!Schema::hasColumn($this->table, 'counterparty_signature_path')) return null;

        return $this->counterparty_signature_path
            ? asset('storage/' . ltrim($this->counterparty_signature_path, '/'))
            : null;
    }

    /**
     * Evidencia principal (compatibilidad).
     * - Si existe attachment_path, usa esa.
     * - Si no, intenta evidence_paths[0] si existe la columna.
     */
    public function getEvidenceUrlAttribute(): ?string
    {
        // attachment_path
        if (Schema::hasColumn($this->table, 'attachment_path') && !empty($this->attachment_path)) {
            return Storage::disk('public')->url($this->attachment_path);
        }

        // evidence_paths
        if (Schema::hasColumn($this->table, 'evidence_paths')) {
            $arr = is_array($this->evidence_paths) ? $this->evidence_paths : [];
            $first = $arr[0] ?? null;
            return $first ? Storage::disk('public')->url($first) : null;
        }

        return null;
    }

    public function getHasEvidenceAttribute(): bool
    {
        if (Schema::hasColumn($this->table, 'attachment_path') && !empty($this->attachment_path)) return true;

        if (Schema::hasColumn($this->table, 'evidence_paths')) {
            $arr = is_array($this->evidence_paths) ? $this->evidence_paths : [];
            return count($arr) > 0;
        }

        return false;
    }

    public function getHasReceiptAttribute(): bool
    {
        if (!Schema::hasColumn($this->table, 'pdf_receipt_path')) return false;
        return (bool) $this->pdf_receipt_path;
    }

    /**
     * ✅ Lista completa de evidencias (attachment + evidence_paths), sin duplicados.
     * Retorna rutas relativas en disk "public".
     */
    public function evidencePaths(): array
    {
        $paths = [];

        if (Schema::hasColumn($this->table, 'evidence_paths')) {
            $arr = is_array($this->evidence_paths) ? $this->evidence_paths : [];
            foreach ($arr as $p) {
                if (is_string($p) && trim($p) !== '') $paths[] = trim($p);
            }
        }

        if (Schema::hasColumn($this->table, 'attachment_path') && !empty($this->attachment_path)) {
            $paths[] = (string) $this->attachment_path;
        }

        return array_values(array_unique(array_filter($paths)));
    }
}
