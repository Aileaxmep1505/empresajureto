<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    protected $fillable = ['name', 'code', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
