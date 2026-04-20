<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('document_sections')->onDelete('cascade');
            $table->foreignId('subtype_id')->nullable()->constrained('document_subtypes')->onDelete('set null');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path'); // storage path
            $table->string('file_type')->nullable(); // foto, video, documento
            $table->date('date')->nullable(); // fecha que representa el documento (uso para filtros)
            $table->unsignedBigInteger('uploaded_by')->nullable(); // usuario id (opcional)
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('documents');
    }
};
