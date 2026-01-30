<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('licitacion_pdf_checklist_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('checklist_id')->constrained('licitacion_pdf_checklists')->cascadeOnDelete();

      $table->string('section')->index();        // "Propuesta técnica (TEC)"
      $table->string('code')->nullable()->index(); // "TEC-01"
      $table->text('text');                     // requisito
      $table->boolean('required')->default(true);

      $table->foreignId('parent_id')->nullable()->constrained('licitacion_pdf_checklist_items')->nullOnDelete();
      $table->unsignedInteger('sort')->default(0)->index();

      $table->boolean('done')->default(false);
      $table->text('notes')->nullable();

      // evidencia desde PDF para disciplina: {page, excerpt}
      $table->json('evidence')->nullable();

      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('licitacion_pdf_checklist_items');
  }
};
