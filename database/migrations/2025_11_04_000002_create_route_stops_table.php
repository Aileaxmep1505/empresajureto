<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_plan_id')->constrained('route_plans')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('sequence_index')->nullable(); // orden calculado por OSRM
            $table->enum('status', ['pending','skipped','done'])->default('pending');
            $table->unsignedInteger('eta_seconds')->nullable(); // ETA desde el anterior
            $table->json('meta')->nullable(); // {provider_id?, notes?}
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('route_stops');
    }
};
