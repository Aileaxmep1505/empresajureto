<?php
// 2025_11_05_000001_create_tickets_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('tickets', function (Blueprint $t) {
      $t->id();
      $t->string('folio')->unique();                          // TKT-2025-0001
      $t->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete(); // o customers
      $t->string('client_name')->nullable();                  // fallback si no hay clients
      $t->enum('type', ['licitacion','pedido','cotizacion','entrega','queja'])->index();
      $t->enum('priority', ['alta','media','baja'])->default('media')->index();
      $t->enum('status', ['revision','proceso','finalizado','cerrado'])->default('revision')->index();
      $t->dateTime('opened_at')->nullable();
      $t->dateTime('closed_at')->nullable();
      $t->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();     // responsable general
      $t->dateTime('due_at')->nullable();                     // fecha límite global (SLA)
      $t->unsignedTinyInteger('progress')->default(0);        // % calculado (cacheado)
      $t->json('meta')->nullable();                           // extra (ej. SLA por etapa)
      // Integración licitaciones
      $t->string('numero_licitacion')->nullable();
      $t->decimal('monto_propuesta', 14,2)->nullable();
      $t->date('fecha_entrega_docs')->nullable();
      $t->date('fecha_apertura_tecnica')->nullable();
      $t->date('fecha_apertura_economica')->nullable();
      $t->enum('estatus_adjudicacion', ['en_espera','ganada','perdida'])->nullable();
      $t->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('tickets'); }
};
