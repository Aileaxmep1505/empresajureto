<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id','filename','file_path','mime_type','file_size',
        'status','extracted_text','extracted_raw','error_message','processed_at',
    ];

    protected $casts = [
        'extracted_raw' => 'array',
        'processed_at'  => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }
}