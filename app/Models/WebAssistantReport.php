<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebAssistantReport extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'guest_id',
        'folio',
        'type',
        'status',
        'order_table',
        'order_id',
        'order_folio',
        'customer_email',
        'summary',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WebAssistantConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
