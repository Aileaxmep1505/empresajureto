<?php
// database/migrations/2025_11_12_000002_create_post_comments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('post_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->onDelete('cascade');
            $table->string('usuario'); // nombre o id del usuario
            $table->text('comentario');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('post_comments');
    }
};
