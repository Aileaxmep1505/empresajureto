<?php
// 2025_11_05_000002_create_ticket_stages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ticket_stages', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
      $t->unsignedTinyInteger('position'); // 1..n
      $t->string('name'); // Recepción, Análisis, Cotización, Aprobación, Entrega, Post-venta
      $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
      $t->enum('status', ['pendiente','en_progreso','terminado'])->default('pendiente');
      $t->dateTime('due_at')->nullable();         // SLA etapa
      $t->dateTime('started_at')->nullable();
      $t->dateTime('finished_at')->nullable();
      $t->unsignedSmallInteger('expected_hours')->nullable(); // para medir vs real
      $t->unsignedSmallInteger('spent_hours')->default(0);    // acumulado
      $t->json('meta')->nullable();                // campos extra
      $t->timestamps();
      $t->unique(['ticket_id','position']);
    });
  }
  public function down(): void { Schema::dropIfExists('ticket_stages'); }
};
