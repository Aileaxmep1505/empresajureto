<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $fillable = [
        'user_id',
        'contact_name',
        'phone',
        'phone_ext',
        'address_type',
        'street',
        'ext_number',
        'int_number',
        'colony',
        'postal_code',
        'state',
        'municipality',
        'between_street_1',
        'between_street_2',
        'references',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
