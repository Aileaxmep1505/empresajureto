<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('rfc')->nullable()->index(); // número fiscal
            $table->enum('tipo_persona', ['fisica','moral'])->nullable();
            $table->string('telefono')->nullable();

            // Dirección (normalizada sencilla)
            $table->string('calle')->nullable();
            $table->string('colonia')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('estado')->nullable();
            $table->string('cp', 10)->nullable();

            $table->boolean('estatus')->default(true); // true = activo
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('providers');
    }
};
