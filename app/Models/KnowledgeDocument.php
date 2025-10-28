<?php
// app/Models/KnowledgeDocument.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeDocument extends Model
{
  protected $fillable = [
    'source_type','source_id','title','url','content','embedding','meta','is_active','published_at'
  ];

  protected $casts = [
    'meta'=>'array',
    'is_active'=>'boolean',
    'published_at'=>'datetime',
  ];

  // Helper
  public function embeddingArray(): array {
    $e = $this->embedding;
    if (is_array($e)) return $e;
    if (is_string($e)) return json_decode($e, true) ?: [];
    return [];
  }
}
