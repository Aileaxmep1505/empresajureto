<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketChecklist extends Model
{
    protected $fillable = [
        'ticket_id','title','source','created_by','meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function items()
    {
        return $this->hasMany(TicketChecklistItem::class, 'checklist_id')->orderBy('sort_order')->orderBy('id');
    }
}