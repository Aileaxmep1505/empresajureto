<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'user_id',
        'column_id',
        'priority',
        'color',
        'assigned_to',
        'start_date',
        'favorite',
        'labels',
        'status',
        'workflow_status',
        'no_participa_reason',
        'no_participa_confirmed_at',
        'no_participa_confirmed_by',
        'structured_data',
        'error_message',
        'draft_content',

        // Se puede dejar temporalmente por compatibilidad con datos viejos.
        // El checklist nuevo se guardará en project_checklist_items.
        'checklist',

        'report_content',
    ];

    protected $casts = [
        'start_date'                   => 'date',
        'favorite'                     => 'boolean',
        'labels'                       => 'array',
        'structured_data'              => 'array',
        'no_participa_confirmed_at'    => 'datetime',

        // Temporal / legacy.
        'checklist'                    => 'array',
    ];

    /* ============================================================
     |  RELACIONES
     * ============================================================ */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function noParticipaConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'no_participa_confirmed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->orderBy('id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ProjectChatMessage::class)->orderBy('id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ProjectChecklistItem::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function checklistNotes(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProjectChecklistNote::class,
            ProjectChecklistItem::class,
            'project_id',
            'project_checklist_item_id'
        );
    }

    public function checklistAttachments(): HasManyThrough
    {
        return $this->hasManyThrough(
            ProjectChecklistAttachment::class,
            ProjectChecklistItem::class,
            'project_id',
            'project_checklist_item_id'
        );
    }

    /* ============================================================
     |  ROUTE BINDING POR SLUG
     * ============================================================ */

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
