<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketFollower extends Model
{
    protected $table = 'ticket_followers';

    protected $fillable = ['ticket_id','user_id'];

    public function ticket(){ return $this->belongsTo(Ticket::class); }
    public function user(){ return $this->belongsTo(User::class); }
}
