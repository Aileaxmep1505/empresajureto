<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicitacionPdfChecklistItem extends Model
{
  protected $fillable = [
    'checklist_id','section','code','text','required','parent_id','sort','done','notes','evidence'
  ];

  protected $casts = [
    'required' => 'boolean',
    'done'     => 'boolean',
    'evidence' => 'array',
  ];

  public function checklist(): BelongsTo
  {
    return $this->belongsTo(LicitacionPdfChecklist::class, 'checklist_id');
  }

  public function children(): HasMany
  {
    return $this->hasMany(self::class, 'parent_id');
  }
}
