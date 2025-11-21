<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('catalog_ai_intakes', function (Blueprint $t) {
      $t->id();

      // Token único para QR / link móvil
      $t->string('token', 64)->unique();

      // quién generó la captura
      $t->foreignId('created_by')->nullable()
        ->constrained('users')->nullOnDelete();

      // 0=pending(QR creado) 1=uploaded 2=processing 3=ready 4=confirmed 9=failed
      $t->unsignedTinyInteger('status')->default(0)->index();

      // tipo de documento
      $t->string('source_type', 30)->nullable(); // factura|remision|otro
      $t->string('original_filename')->nullable();
      $t->text('notes')->nullable();

      // meta extra (logs, pendientes, error, etc.)
      $t->json('meta')->nullable();

      // JSON final extraído por IA
      $t->json('extracted')->nullable();

      $t->timestamp('uploaded_at')->nullable();
      $t->timestamp('processed_at')->nullable();
      $t->timestamp('confirmed_at')->nullable();

      $t->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('catalog_ai_intakes');
  }
};
