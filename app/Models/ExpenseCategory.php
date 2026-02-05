<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ExpenseCategory extends Model
{
  protected $fillable = ['name','slug','type','active'];

  protected static function booted(): void {
    static::saving(function($cat){
      if (empty($cat->slug) && !empty($cat->name)) {
        $cat->slug = Str::slug($cat->name);
      }
    });
  }

  public function expenses(): HasMany {
    return $this->hasMany(Expense::class);
  }
}
