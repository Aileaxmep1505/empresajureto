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
        'partida_number',
        'user_description',
        'image_path',
        'brand_image_path',
        'ai_description',
        'ai_features',
        'ai_specs',
        'public_token',

        // ✅ PDFs ligados a la ficha
        'brand_pdf_path',
        'custom_pdf_path',
        'active_pdf', // 'brand' | 'custom' | null (null = PDF generado)
    ];

    protected $casts = [
        'ai_features' => 'array',
        'ai_specs'    => 'array',
    ];
}
