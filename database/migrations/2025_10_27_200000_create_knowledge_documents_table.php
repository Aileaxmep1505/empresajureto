<?php
// database/migrations/2025_10_27_200000_create_knowledge_documents_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('knowledge_documents', function (Blueprint $t) {
      $t->id();
      $t->string('source_type', 40);     // products, pages, faqs, policies, etc.
      $t->string('source_id', 191);      // id/slug + "#chunk"
      $t->string('title', 200)->nullable();
      $t->string('url', 300)->nullable();
      $t->longText('content');           // texto plano (sin HTML)
      $t->longText('embedding')->nullable(); // JSON de floats
      $t->json('meta')->nullable();
      $t->boolean('is_active')->default(true);
      $t->timestamp('published_at')->nullable();
      $t->timestamps();

      $t->index(['source_type','source_id']);
    });
  }
  public function down(): void {
    Schema::dropIfExists('knowledge_documents');
  }
};
