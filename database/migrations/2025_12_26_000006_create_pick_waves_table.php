<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pick_waves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Código visible (opcional) ej: PICK-000123
            $table->string('code', 50)->unique();

            // 0=new, 1=in_progress, 2=done, 3=cancelled
            $table->unsignedTinyInteger('status')->default(0);

            $table->foreignId('assigned_to')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // “estado de la sesión” para UX móvil
            $table->foreignId('current_location_id')->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            // 👇 IMPORTANTE:
            // No agregamos el FK aquí para evitar error por orden/ciclo con pick_items.
            // Solo dejamos la columna nullable.
            $table->unsignedBigInteger('current_pick_item_id')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['warehouse_id','status']);
            $table->index(['assigned_to','status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pick_waves');
    }
};
