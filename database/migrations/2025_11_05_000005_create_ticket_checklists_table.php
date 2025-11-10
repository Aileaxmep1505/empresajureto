<?php
// 2025_11_05_000005_create_ticket_checklists_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ticket_checklists', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
      $t->foreignId('stage_id')->nullable()->constrained('ticket_stages')->nullOnDelete();
      $t->string('title');
      $t->json('meta')->nullable(); // plantilla/tipo
      $t->timestamps();
    });

    Schema::create('ticket_checklist_items', function (Blueprint $t) {
      $t->id();
      $t->foreignId('checklist_id')->constrained('ticket_checklists')->cascadeOnDelete();
      $t->string('label');
      $t->enum('type', ['text','checkbox','date','file'])->default('checkbox');
      $t->text('value')->nullable(); // texto/fecha/ruta archivo/json
      $t->boolean('is_done')->default(false);
      $t->unsignedSmallInteger('position')->default(1);
      $t->timestamps();
    });
  }
  public function down(): void {
    Schema::dropIfExists('ticket_checklist_items');
    Schema::dropIfExists('ticket_checklists');
  }
};
