<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebAssistantMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WebAssistantConversation::class, 'conversation_id');
    }
}
