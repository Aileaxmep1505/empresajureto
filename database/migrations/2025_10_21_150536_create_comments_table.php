<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            // Usuario que comenta (nullable para no romper si se elimina el usuario)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Comentario padre (para respuestas). Null = comentario raíz
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->cascadeOnDelete();

            // Snapshot opcional del nombre/email (útil si cambian el perfil)
            $table->string('nombre', 120)->nullable();
            $table->string('email', 190)->nullable();

            $table->text('contenido');

            $table->timestamps();

            // Índices útiles
            $table->index(['parent_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
