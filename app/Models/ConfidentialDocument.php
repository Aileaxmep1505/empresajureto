<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfidentialDocument extends Model
{
    protected $table = 'confidential_documents';

    protected $fillable = [
        'owner_user_id',
        'company_id',
        'uploaded_by',
        'title',
        'doc_key',
        'description',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'date',
        'requires_pin',
        'access_level',
        'last_accessed_at',
    ];

    protected $casts = [
        'requires_pin'     => 'boolean',
        'date'             => 'date',
        'last_accessed_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}