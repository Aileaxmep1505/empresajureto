<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostComment extends Model {
    use \App\Traits\LogsModelActivity;

    use HasFactory;

    protected $fillable = ['post_id', 'usuario', 'comentario'];

    public function post() {
        return $this->belongsTo(Post::class);
    }
}
