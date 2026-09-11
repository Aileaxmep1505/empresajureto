<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCategory extends Model
{
    use \App\Traits\LogsModelActivity;

  protected $fillable = ['name'];

  public function items()
  {
    return $this->hasMany(InventoryItem::class);
  }
}
