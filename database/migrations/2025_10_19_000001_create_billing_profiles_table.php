<?php
// database/migrations/2025_10_19_000001_create_billing_profiles_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('billing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Datos fiscales mínimos (ajusta a tus necesidades / Facturapi)
            $table->string('razon_social')->nullable();
            $table->string('rfc', 13)->nullable();              // XAXX... permitido
            $table->string('regimen', 5)->nullable();           // catálogo SAT, p.e. '601', '616'
            $table->string('uso_cfdi', 3)->nullable();          // p.e. 'G03' o 'S01'
            $table->string('email', 190)->nullable();

            // Domicilio fiscal mínimo (Facturapi solo exige CP)
            $table->string('zip', 10)->nullable();

            // Control
            $table->boolean('is_default')->default(true);

            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('billing_profiles');
    }
};
