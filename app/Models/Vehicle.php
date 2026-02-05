<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
  protected $fillable = [
    'plate','brand','model','year','vin','nickname',
    'last_verification_at','last_service_at',
    'next_verification_due_at','next_service_due_at',
    'tenencia_due_at','circulation_card_due_at',
    'notes',

    'image_left','image_right',

    'agenda_verification_id','agenda_service_id','agenda_tenencia_id','agenda_circulation_id','agenda_insurance_id',
  ];

  protected $casts = [
    'last_verification_at' => 'date',
    'last_service_at' => 'date',
    'next_verification_due_at' => 'date',
    'next_service_due_at' => 'date',
    'tenencia_due_at' => 'date',
    'circulation_card_due_at' => 'date',
  ];

  protected $appends = ['image_left_url','image_right_url'];

  public function events(): HasMany { return $this->hasMany(VehicleEvent::class); }
  public function expenses(): HasMany { return $this->hasMany(Expense::class); }

  public function documents(): HasMany {
    return $this->hasMany(VehicleDocument::class);
  }

  public function getImageLeftUrlAttribute(): ?string {
    return $this->image_left ? asset('storage/'.$this->image_left) : null;
  }

  public function getImageRightUrlAttribute(): ?string {
    return $this->image_right ? asset('storage/'.$this->image_right) : null;
  }
}
