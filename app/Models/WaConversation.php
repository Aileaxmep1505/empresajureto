<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaConversation extends Model
{
    protected $table = 'wa_conversations';

    protected $fillable = [
        'wa_id','name','last_message_preview','last_message_at','unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class, 'conversation_id');
    }
}