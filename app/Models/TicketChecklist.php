<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketChecklist extends Model
{
    protected $table = 'ticket_checklists';

    protected $fillable = [
        'ticket_id','stage_id','title','instructions','assigned_to'
    ];

    public function stage() { return $this->belongsTo(TicketStage::class, 'stage_id'); }
    public function ticket(){ return $this->belongsTo(Ticket::class, 'ticket_id'); }
    public function items() { return $this->hasMany(TicketChecklistItem::class, 'checklist_id'); }
    public function assignee(){ return $this->belongsTo(\App\Models\User::class, 'assigned_to'); }
}
