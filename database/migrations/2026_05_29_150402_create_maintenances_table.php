<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->cascadeOnDelete();

            $table->enum('type', ['preventivo', 'correctivo'])->default('preventivo');
            $table->enum('status', ['programado', 'en_proceso', 'completado', 'cancelado'])->default('programado');

            $table->string('technician')->nullable();
            $table->decimal('cost', 12, 2)->nullable();

            $table->date('maintenance_date');
            $table->date('next_maintenance_date')->nullable();

            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};