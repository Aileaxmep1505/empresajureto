<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('route_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users');
            $table->string('name')->nullable();
            $table->enum('status', ['draft','scheduled','in_progress','done','cancelled'])->default('draft');
            $table->timestamp('planned_at')->nullable();
            $table->json('meta')->nullable(); // {traffic_provider, notes, last_osrm_payload, ...}
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('route_plans');
    }
};
