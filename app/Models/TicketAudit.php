<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAudit extends Model
{
    protected $table = 'ticket_audits';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',   // p. ej.: ticket_created, ticket_updated, stage_added, doc_uploaded, comment_added, ticket_closed
        'diff',     // array con before/after u otros datos
    ];

    protected $casts = [
        'diff' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
