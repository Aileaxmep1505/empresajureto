<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'nombre','apellido','email','password','telefono','direccion',
    ];

    protected $hidden = ['password','remember_token'];
}
