<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAudit extends Model
{
    protected $table = 'ticket_audits';

    protected $fillable = ['ticket_id','user_id','action','diff'];
    protected $casts = ['diff' => 'array'];

    public function ticket(){ return $this->belongsTo(Ticket::class); }
    public function user(){ return $this->belongsTo(User::class); }
}