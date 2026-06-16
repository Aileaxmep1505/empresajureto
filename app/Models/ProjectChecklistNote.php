<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectChecklistNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_checklist_item_id',
        'user_id',
        'body',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ProjectChecklistItem::class, 'project_checklist_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}