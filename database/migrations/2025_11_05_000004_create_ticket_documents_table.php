<?php
// 2025_11_05_000004_create_ticket_documents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ticket_documents', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
      $t->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
      $t->foreignId('stage_id')->nullable()->constrained('ticket_stages')->nullOnDelete();
      $t->string('category')->nullable(); // propuesta, evidencia, contrato, cotizacion, etc.
      $t->string('name');                 // nombre visible
      $t->string('path')->nullable();     // storage local
      $t->string('external_url')->nullable(); // Drive/externo
      $t->unsignedInteger('version')->default(1);
      $t->json('meta')->nullable();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('ticket_documents'); }
};
