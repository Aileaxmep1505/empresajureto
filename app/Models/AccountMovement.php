<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountMovement extends Model
{
    use HasFactory;

    protected $table = 'account_movements';

    protected $fillable = [
        'company_id',
        'direction',
        'related_type',
        'related_id',
        'movement_date',
        'amount',
        'currency',
        'method',
        'reference',
        'status',
        'evidence_url',
        'documents',
        'document_names',
        'notes',
        'expense_id',
        'created_by',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'amount' => 'decimal:2',
        'documents' => 'array',
        'document_names' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function receivable()
    {
        return $this->belongsTo(AccountReceivable::class, 'related_id')
            ->where('related_type', 'receivable');
    }

    public function payable()
    {
        return $this->belongsTo(AccountPayable::class, 'related_id')
            ->where('related_type', 'payable');
    }
}