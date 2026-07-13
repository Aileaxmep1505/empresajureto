<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebAssistantConversation extends Model
{
    protected $fillable = [
        'user_id',
        'guest_id',
        'title',
        'status',
        'last_activity_at',
        'meta',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WebAssistantMessage::class, 'conversation_id')->orderBy('created_at')->orderBy('id');
    }
}
