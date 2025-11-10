<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketStage extends Model
{
    protected $table = 'ticket_stages';

    protected $fillable = [
        'ticket_id','position','name','assignee_id','status','due_at',
        'started_at','finished_at','expected_hours','spent_hours','meta',
        'ai_prompt','ai_instructions','requires_evidence'
    ];

    protected $casts = [
        'due_at'        => 'datetime',
        'started_at'    => 'datetime',
        'finished_at'   => 'datetime',
        'expected_hours'=> 'integer',
        'spent_hours'   => 'integer',
        'meta'          => 'array',
        'requires_evidence' => 'boolean',
    ];

    /** Relaciones */
    public function ticket()     { return $this->belongsTo(Ticket::class); }
    public function assignee()   { return $this->belongsTo(\App\Models\User::class, 'assignee_id'); }
    public function checklists() { return $this->hasMany(TicketChecklist::class, 'stage_id'); }
    public function documents()  { return $this->hasMany(TicketDocument::class, 'stage_id'); }

    /** Señal SLA (para UI) */
    public function slaSignal(): string
    {
        if ($this->status === 'terminado') return 'ok';
        if (!$this->due_at) return 'neutral';
        $now = now();
        if ($now->gt($this->due_at)) return 'overdue';
        return $now->diffInHours($this->due_at) <= 24 ? 'due_soon' : 'ok';
    }

    public function getSlaSignalAttribute(): string
    {
        return $this->slaSignal();
    }

    /** Reglas de negocio */
    public function canStartSequential(): bool
    {
        if ($this->position <= 1) return true;
        return $this->ticket
            ->stages()
            ->where('position', $this->position - 1)
            ->where('status', 'terminado')
            ->exists();
    }

    public function checklistPendingCount(): int
    {
        return $this->checklists()->with('items')->get()->flatMap->items->where('is_done', false)->count();
    }
}
