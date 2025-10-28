<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpTicket extends Model
{
    protected $fillable = [
        'user_id','subject','category','priority','status','last_activity_at','resolved_by_id'
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_id');
    }

    // OJO: especificamos la FK correcta 'ticket_id'
    public function messages(): HasMany
    {
        return $this->hasMany(HelpMessage::class, 'ticket_id')->orderBy('created_at');
    }
}
