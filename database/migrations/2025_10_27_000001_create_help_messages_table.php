<?php
// database/migrations/2025_10_27_000001_create_help_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('help_messages', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained('help_tickets')->cascadeOnDelete();
      $t->enum('sender_type', ['user','ai','agent','system'])->index();
      $t->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
      $t->longText('body');
      $t->json('meta')->nullable();
      $t->boolean('is_solution')->default(false);
      $t->timestamps();

      $t->index(['ticket_id','created_at']);
    });
  }
  public function down(): void { Schema::dropIfExists('help_messages'); }
};
