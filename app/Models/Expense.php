<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Expense extends Model
{
  protected $fillable = [
    'expense_category_id','vehicle_id','payroll_period_id',
    'created_by','vendor',
    'expense_date','concept','description',
    'amount','currency','payment_method','status','tags'
  ];

  protected $casts = [
    'expense_date' => 'date',
    'amount' => 'decimal:2',
  ];

  public function category(): BelongsTo {
    return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
  }

  public function vehicle(): BelongsTo {
    return $this->belongsTo(Vehicle::class);
  }

  public function payrollPeriod(): BelongsTo {
    return $this->belongsTo(PayrollPeriod::class);
  }

  public function creator(): BelongsTo {
    return $this->belongsTo(User::class, 'created_by');
  }

  public function attachments(): MorphMany {
    return $this->morphMany(Attachment::class, 'attachable');
  }
}
