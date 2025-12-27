<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Jerarquía: pasillo -> sección -> stand -> bin (opcional)
            $table->foreignId('parent_id')->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->string('type', 30)->default('bin'); // aisle|section|stand|rack|level|bin
            $table->string('code', 80)->unique();       // EJ: A-03-S2-R1-N4-B07 (lo imprimes grande)

            // Campos opcionales para orden/ruta
            $table->string('aisle', 10)->nullable();    // A
            $table->string('section', 10)->nullable();  // 03
            $table->string('stand', 10)->nullable();    // S2
            $table->string('rack', 10)->nullable();     // R1
            $table->string('level', 10)->nullable();    // N4
            $table->string('bin', 10)->nullable();      // B07

            $table->string('name')->nullable();         // Texto humano opcional
            $table->string('qr_secret', 80)->nullable();// si quieres firmar QRs
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['warehouse_id', 'type']);
            $table->index(['warehouse_id', 'aisle', 'section']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
