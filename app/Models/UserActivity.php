<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'document_id',
        'action',
        'route',
        'path',
        'method',
        'status_code',
        'meta',
        'ip',
        'user_agent',
        'session_id',
        'request_id',
        'duration_ms',
        'referer',
        'previous_hash',
        'current_hash',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function document()
    {
        return $this->belongsTo(\App\Models\Document::class);
    }
}