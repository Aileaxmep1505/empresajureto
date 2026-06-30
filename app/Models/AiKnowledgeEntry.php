<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiKnowledgeEntry extends Model
{
    protected $table = 'ai_knowledge_entries';

    protected $fillable = [
        'category',
        'question',
        'answer',
        'keywords',
        'source',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
    ];
}