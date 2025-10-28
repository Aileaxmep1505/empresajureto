<?php
// database/migrations/2025_10_27_000000_create_help_tickets_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('help_tickets', function (Blueprint $t) {
      $t->id();
      $t->foreignId('user_id')->constrained()->cascadeOnDelete();
      $t->string('subject', 160);
      $t->string('category')->nullable();
      $t->enum('priority', ['low','normal','high'])->default('normal');
      $t->enum('status', ['new','ai_answered','waiting_user','pending_agent','agent_answered','closed'])->default('new');
      $t->timestamp('last_activity_at')->nullable();
      $t->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
      $t->timestamps();

      $t->index(['status','last_activity_at']);
    });
  }
  public function down(): void { Schema::dropIfExists('help_tickets'); }
};
