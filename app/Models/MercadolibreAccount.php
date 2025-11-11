<?php
// app/Models/MercadolibreAccount.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MercadolibreAccount extends Model
{
    protected $fillable = [
        'meli_user_id',
        'access_token',
        'refresh_token',
        'access_token_expires_at',
        'site_id',
    ];

    protected $casts = [
        'access_token_expires_at' => 'datetime',
    ];
}
