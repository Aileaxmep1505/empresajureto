<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('licitacion_pdf_checklists', function (Blueprint $table) {
      $table->id();
      $table->foreignId('licitacion_pdf_id')->constrained('licitacion_pdfs')->cascadeOnDelete();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();

      $table->string('title')->default('Checklist del PDF');
      $table->json('meta')->nullable(); // {version, model, generated_at, strict_mode, ...}

      $table->timestamps();

      $table->unique(['licitacion_pdf_id', 'user_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('licitacion_pdf_checklists');
  }
};
