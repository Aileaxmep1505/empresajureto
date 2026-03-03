<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('confidential_documents', function (Blueprint $table) {
            $table->id();

            // Dueño del “vault” (a quién pertenecen los docs)
            $table->unsignedBigInteger('owner_user_id')->index();

            // Si quieres amarrarlo a Company como en Part Contable (opcional)
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Quien subió
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();

            // Metadatos
            $table->string('title')->nullable();
            $table->string('doc_key', 60)->index(); // ej: csf, efirma_cer, efirma_key, contrato, etc.
            $table->text('description')->nullable();

            // Archivo
            $table->string('file_path');           // ruta en disk public
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            // Fecha del documento (para filtrar por año/mes como lo haces)
            $table->date('date')->nullable();

            // Seguridad
            $table->boolean('requires_pin')->default(true);
            $table->string('access_level', 20)->default('alto'); // medio|alto|critico

            $table->timestamp('last_accessed_at')->nullable();

            $table->timestamps();

            // FKs (si en tu proyecto NO usas FKs, puedes borrar estas 3 líneas)
            $table->foreign('owner_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confidential_documents');
    }
};