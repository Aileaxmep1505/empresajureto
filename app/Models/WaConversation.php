<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaConversation extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'channel',
        'status',
        'assigned_to',
        'last_message_at',
        'meta',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class, 'conversation_id');
    }

    public function handoffs(): HasMany
    {
        return $this->hasMany(WaHandoff::class, 'conversation_id');
    }
}