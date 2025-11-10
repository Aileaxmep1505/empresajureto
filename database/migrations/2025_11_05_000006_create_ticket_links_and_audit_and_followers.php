<?php
// 2025_11_05_000006_create_ticket_links_and_audit_and_followers.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('ticket_links', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
      $t->string('label');
      $t->string('url');
      $t->timestamps();
    });

    Schema::create('ticket_sla_events', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
      $t->foreignId('stage_id')->nullable()->constrained('ticket_stages')->nullOnDelete();
      $t->string('event'); // created|due_soon|overdue|finished
      $t->dateTime('fired_at');
      $t->json('payload')->nullable();
      $t->timestamps();
    });

    Schema::create('ticket_audits', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
      $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
      $t->string('action'); // status_changed, reassigned, doc_uploaded, comment_added...
      $t->json('diff')->nullable();
      $t->timestamps();
    });

    Schema::create('ticket_followers', function (Blueprint $t) {
      $t->id();
      $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
      $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $t->timestamps();
      $t->unique(['ticket_id','user_id']);
    });

    // Opcional: relación con productos (inventario/pedidos)
    if (Schema::hasTable('products')) {
      Schema::create('ticket_products', function (Blueprint $t) {
        $t->id();
        $t->foreignId('ticket_id')->constrained()->cascadeOnDelete();
        $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
        $t->unsignedInteger('qty')->default(1);
        $t->json('meta')->nullable(); // stock, disponibilidad, OC interna, etc.
        $t->timestamps();
      });
    }
  }
  public function down(): void {
    if (Schema::hasTable('ticket_products')) Schema::dropIfExists('ticket_products');
    Schema::dropIfExists('ticket_followers');
    Schema::dropIfExists('ticket_audits');
    Schema::dropIfExists('ticket_sla_events');
    Schema::dropIfExists('ticket_links');
  }
};
