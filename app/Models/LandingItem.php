<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingItem extends Model
{
    protected $fillable = [
        'landing_section_id','image_path','title','subtitle','cta_text','cta_url','sort_order'
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(LandingSection::class,'landing_section_id');
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image_path
            ? asset('storage/'.$this->image_path)
            : asset('images/placeholder.png');
    }
}
