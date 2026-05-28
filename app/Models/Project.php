<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','slug','user_id','column_id','priority','color',
        'assigned_to','start_date','favorite','labels',
        'status','structured_data','error_message',
        'draft_content','checklist','report_content',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'favorite'        => 'boolean',
        'labels'          => 'array',
        'structured_data' => 'array',
        'checklist'       => 'array',
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

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->orderBy('id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ProjectChatMessage::class)->orderBy('id');
    }

    /* ============================================================
     |  ROUTE BINDING POR SLUG
     * ============================================================ */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}