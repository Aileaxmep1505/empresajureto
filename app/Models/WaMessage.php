<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'direction',
        'message_type',
        'wa_message_id',
        'text',
        'status',
        'payload',
        'meta',
        'from_wa_id',
        'to_wa_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'meta' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}