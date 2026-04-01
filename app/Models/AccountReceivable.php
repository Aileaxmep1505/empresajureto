<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountReceivable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'account_receivables';

    protected $fillable = [
        'company_id',
        'folio',
        'client_id',
        'client_name',
        'description',
        'document_type',
        'category',
        'amount',
        'amount_paid',
        'currency',
        'issue_date',
        'due_date',
        'payment_date',
        'status',
        'priority',
        'payment_method',
        'bank_reference',
        'credit_days',
        'interest_rate',
        'assigned_to',
        'collection_status',
        'evidence_url',
        'documents',
        'document_names',
        'notes',
        'reminder_days_before',
        'tags',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'issue_date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'documents' => 'array',
        'document_names' => 'array',
        'tags' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function movements()
    {
        return $this->hasMany(AccountMovement::class, 'related_id')
            ->where('related_type', 'receivable')
            ->orderByDesc('movement_date');
    }

    public function getPendingAmountAttribute(): float
    {
        return max((float) $this->amount - (float) $this->amount_paid, 0);
    }
}