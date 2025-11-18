<?php
// database/migrations/2025_11_11_000002_add_is_test_to_mercadolibre_accounts.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('mercadolibre_accounts', function (Blueprint $t) {
      $t->boolean('is_test')->default(false)->index();
      $t->string('label', 50)->nullable();
    });
  }
  public function down(): void {
    Schema::table('mercadolibre_accounts', function (Blueprint $t) {
      $t->dropColumn(['is_test','label']);
    });
  }
};
