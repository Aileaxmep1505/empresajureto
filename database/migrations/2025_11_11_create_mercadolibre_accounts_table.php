<?php
// database/migrations/xxxx_xx_xx_create_mercadolibre_accounts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mercadolibre_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('meli_user_id')->nullable();
            $table->string('access_token', 2000);
            $table->string('refresh_token', 2000)->nullable();
            $table->timestamp('access_token_expires_at')->nullable();
            $table->string('site_id')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('mercadolibre_accounts');
    }
};
