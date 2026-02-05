<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
  protected $fillable = ['frequency','start_date','end_date','status','title'];

  protected $casts = [
    'start_date' => 'date',
    'end_date' => 'date',
  ];

  public function entries(): HasMany {
    return $this->hasMany(PayrollEntry::class);
  }

  public function expenses(): HasMany {
    return $this->hasMany(Expense::class);
  }
}
