<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Contexto (parte contable)
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();

            // Acción
            $table->string('action', 80); // ej: pc_unlock, pc_preview, pc_download, pc_upload, pc_delete
            $table->json('meta')->nullable();

            // Auditoría
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'company_id', 'action']);
            $table->index(['document_id', 'action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
