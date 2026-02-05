<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VehicleEvent extends Model
{
  protected $fillable = [
    'vehicle_id','type','event_date','title','description','expense_id'
  ];

  protected $casts = [
    'event_date' => 'date',
  ];

  public function vehicle(): BelongsTo {
    return $this->belongsTo(Vehicle::class);
  }

  public function expense(): BelongsTo {
    return $this->belongsTo(Expense::class);
  }

  public function attachments(): MorphMany {
    return $this->morphMany(Attachment::class, 'attachable');
  }
}
