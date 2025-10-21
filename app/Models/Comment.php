<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasFactory;

    // Campos asignables
    protected $fillable = [
        'user_id',
        'parent_id',
        'nombre',
        'email',
        'contenido',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        // Ordena respuestas más nuevas primero; ajusta si prefieres ascendente
        return $this->hasMany(Comment::class, 'parent_id')->latest();
    }

    // Scopes
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
