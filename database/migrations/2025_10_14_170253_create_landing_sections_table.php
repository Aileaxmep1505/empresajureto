<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('landing_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Ej: "Promos de Oficina"
            $table->string('layout')->default('grid-3');    // grid-1, grid-2, grid-3, banner-wide
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('landing_sections');
    }
};
