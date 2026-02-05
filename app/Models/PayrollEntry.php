<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PayrollEntry extends Model
{
  protected $fillable = [
    'payroll_period_id','user_id',
    'gross_amount','deductions','net_amount',
    'status','notes'
  ];

  protected $casts = [
    'gross_amount' => 'decimal:2',
    'deductions' => 'decimal:2',
    'net_amount' => 'decimal:2',
  ];

  public function period(): BelongsTo {
    return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
  }

  public function user(): BelongsTo {
    return $this->belongsTo(User::class);
  }

  public function attachments(): MorphMany {
    return $this->morphMany(Attachment::class, 'attachable');
  }
}
