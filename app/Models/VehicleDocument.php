<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDocument extends Model
{
  protected $fillable = [
    'vehicle_id','type','original_name','mime_type','size','path'
  ];

  protected $appends = ['url'];

  public function vehicle(): BelongsTo {
    return $this->belongsTo(Vehicle::class);
  }

  public function getUrlAttribute(): string {
    return asset('storage/'.$this->path);
  }
}
