<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CatalogAiIntake extends Model
{
  use HasFactory;

  protected $table = 'catalog_ai_intakes';

  protected $fillable = [
    'token',
    'created_by',
    'status',
    'source_type',
    'original_filename',
    'notes',
    'meta',
    'extracted',
    'uploaded_at',
    'processed_at',
    'confirmed_at',
  ];

  protected $casts = [
    'meta'         => 'array',
    'extracted'    => 'array',
    'uploaded_at'  => 'datetime',
    'processed_at' => 'datetime',
    'confirmed_at' => 'datetime',
  ];

  public function files()
  {
    return $this->hasMany(CatalogAiIntakeFile::class, 'intake_id');
  }

  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
