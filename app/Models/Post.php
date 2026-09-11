<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model {
    use \App\Traits\LogsModelActivity;

    use HasFactory;

    protected $fillable = [
        'titulo', 'descripcion', 'tipo', 'archivo', 'fecha', 'empresa'
    ];

    // Convertir automáticamente 'fecha' a objeto Carbon
    protected $casts = [
        'fecha' => 'date',
    ];

    public function comentarios() {
        return $this->hasMany(PostComment::class);
    }
}
