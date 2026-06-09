<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sat_clave_unidad');

        Schema::create('sat_clave_unidad', function (Blueprint $table) {
            $table->string('clave', 20)->primary();
            $table->string('nombre', 255);
            $table->string('simbolo', 50)->nullable();
            $table->index('nombre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_clave_unidad');
    }
};