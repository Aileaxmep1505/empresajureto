<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketLink extends Model
{
    protected $table = 'ticket_links';

    protected $fillable = ['ticket_id','label','url'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
