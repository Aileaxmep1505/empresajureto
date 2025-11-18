<?php
// database/migrations/2025_11_12_000001_create_posts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('tipo'); // 'foto', 'video', 'documento'
            $table->string('archivo')->nullable(); // ruta del archivo
            $table->date('fecha'); // para filtrar por día, mes, año
            $table->string('empresa')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('posts');
    }
};
