<?php
// database/migrations/2025_11_05_000003_create_ticket_comments_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ticket_comments', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
      $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $t->text('body');                 // comentario (soporta @menciones)
      $t->json('mentions')->nullable(); // ids mencionados
      $t->timestamps();
      $t->index(['ticket_id','created_at']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('ticket_comments');
  }
};
