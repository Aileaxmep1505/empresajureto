<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'rfc',
        'phone',
        'email',
        'address',
    ];

    public function documents()
    {
        return $this->hasMany(\App\Models\Document::class);
    }
}
