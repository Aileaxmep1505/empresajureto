<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('route_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('route_plans', 'status')) {
                $table->string('status', 20)->default('scheduled')->index(); // scheduled|in_progress|done
            }

            // ¿Siempre regresa al inicio?
            if (!Schema::hasColumn('route_plans', 'return_to_start')) {
                $table->boolean('return_to_start')->default(true)->index();
            }

            // Para evitar que cambie el orden cada 15s (recomendado)
            if (!Schema::hasColumn('route_plans', 'sequence_locked')) {
                $table->boolean('sequence_locked')->default(false)->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('route_plans', function (Blueprint $table) {
            // Igual que arriba: normalmente no dropear en down en producción.
        });
    }
};
