<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'folio',
        'title',
        'description',

        'created_by',
        'assignee_id',

        'priority',
        'status',
        'area',
        'due_at',

        'impact',
        'urgency',
        'effort',
        'score',

        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'due_at'       => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'impact'       => 'integer',
        'urgency'      => 'integer',
        'effort'       => 'integer',
        'score'        => 'integer',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TicketDocument::class)->latest();
    }

    public function audits(): HasMany
    {
        return $this->hasMany(TicketAudit::class)->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    // Señal SLA simple por vencimiento (para UI)
    public function getSlaSignalAttribute(): string
    {
        if (!$this->due_at) return 'neutral';

        $now = now();
        if ($now->gt($this->due_at)) return 'overdue';
        if ($now->diffInHours($this->due_at) <= 24) return 'due_soon';
        return 'ok';
    }
    public function checklists()
{
    return $this->hasMany(\App\Models\TicketChecklist::class);
}

public function checklistItems()
{
    return $this->hasManyThrough(
        \App\Models\TicketChecklistItem::class,
        \App\Models\TicketChecklist::class,
        'ticket_id',      // FK en ticket_checklists
        'checklist_id',   // FK en items
        'id',             // PK tickets
        'id'              // PK checklists
    );
}
}