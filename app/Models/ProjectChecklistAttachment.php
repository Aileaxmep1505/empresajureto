<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectChecklistAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_checklist_item_id',
        'user_id',
        'original_name',
        'file_path',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = [
        'url',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ProjectChecklistItem::class, 'project_checklist_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path
            ? Storage::disk('public')->url($this->file_path)
            : null;
    }
}