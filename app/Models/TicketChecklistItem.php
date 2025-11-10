<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketChecklistItem extends Model
{
    protected $table = 'ticket_checklist_items';

    protected $fillable = [
        'checklist_id',
        'label',        // ← IMPORTANTE
        'type',
        'position',
        'is_done',
        'value',
        'done_at',
        'done_by',
    ];

    protected $casts = [
        'is_done' => 'boolean',
        'value'   => 'array',
        'done_at' => 'datetime',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(TicketChecklist::class, 'checklist_id');
    }
}
