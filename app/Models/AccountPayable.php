<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountPayable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'account_payables';

    protected $fillable = [
        'company_id',
        'folio',
        'supplier_id',
        'supplier_name',
        'title',
        'description',
        'category',
        'frequency',
        'amount',
        'amount_paid',
        'currency',
        'issue_date',
        'due_date',
        'payment_date',
        'status',
        'payment_method',
        'bank_reference',
        'evidence_url',
        'documents',
        'document_names',
        'retention_expiry',
        'notes',
        'reminder_days_before',
        'expense_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'retention_expiry' => 'date',
        'documents' => 'array',
        'document_names' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function movements()
    {
        return $this->hasMany(AccountMovement::class, 'related_id')
            ->where('related_type', 'payable')
            ->orderByDesc('movement_date');
    }

    public function getPendingAmountAttribute(): float
    {
        return max((float) $this->amount - (float) $this->amount_paid, 0);
    }
}