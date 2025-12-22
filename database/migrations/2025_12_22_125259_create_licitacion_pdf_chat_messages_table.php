<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licitacion_pdf_chat_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('licitacion_pdf_id')
                ->constrained('licitacion_pdfs')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('role', ['user', 'assistant'])->index();

            $table->longText('content');

            $table->timestamps();

            // ✅ Nombre corto para evitar el error de MySQL: "Identifier name is too long"
            $table->index(
                ['licitacion_pdf_id', 'user_id', 'created_at'],
                'lpmsg_pdf_user_created'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_pdf_chat_messages');
    }
};
