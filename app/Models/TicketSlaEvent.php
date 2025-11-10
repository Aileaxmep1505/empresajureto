<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketSlaEvent extends Model
{
    protected $table = 'ticket_sla_events';

    protected $fillable = ['ticket_id','stage_id','event','fired_at','payload'];

    protected $casts = [
        'fired_at' => 'datetime',
        'payload'  => 'array',
    ];

    public function ticket(){ return $this->belongsTo(Ticket::class); }
}
