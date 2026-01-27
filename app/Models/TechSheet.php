<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechSheet extends Model
{
    protected $fillable = [
        'product_name',
        'brand',
        'model',
        'reference',
        'identification',
        'partida_number',      // 👈 nuevo
        'user_description',
        'image_path',
        'brand_image_path',
        'ai_description',
        'ai_features',
        'ai_specs',
        'public_token',
    ];

    protected $casts = [
        'ai_features' => 'array',
        'ai_specs'    => 'array',
    ];
}
