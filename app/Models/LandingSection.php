<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingSection extends Model
{
    protected $fillable = ['name','layout','is_active','sort_order'];

    public function items(): HasMany
    {
        return $this->hasMany(LandingItem::class)->orderBy('sort_order');
    }
}
