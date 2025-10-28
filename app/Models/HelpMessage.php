<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpMessage extends Model
{
    protected $fillable = [
        'ticket_id','sender_type','sender_id','body','meta','is_solution'
    ];

    protected $casts = [
        'meta' => 'array',
        'is_solution' => 'boolean',
    ];

    // OJO: especificamos la FK correcta 'ticket_id'
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(HelpTicket::class, 'ticket_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
