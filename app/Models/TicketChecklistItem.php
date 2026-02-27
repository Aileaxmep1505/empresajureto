<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketChecklistItem extends Model
{
    protected $fillable = [
        'checklist_id',
        'title','detail',
        'recommended',
        'done','done_at','done_by',
        'evidence_note',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'recommended' => 'boolean',
        'done' => 'boolean',
        'done_at' => 'datetime',
        'meta' => 'array',
    ];

    public function checklist()
    {
        return $this->belongsTo(TicketChecklist::class, 'checklist_id');
    }
}