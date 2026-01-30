<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicitacionPdfChecklist extends Model
{
  protected $fillable = [
    'licitacion_pdf_id', 'user_id', 'title', 'meta',
  ];

  protected $casts = [
    'meta' => 'array',
  ];

  public function items(): HasMany
  {
    return $this->hasMany(LicitacionPdfChecklistItem::class, 'checklist_id');
  }
}
