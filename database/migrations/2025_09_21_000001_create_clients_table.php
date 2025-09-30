<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Obligatorios
            $table->string('nombre');                  // Razón social / nombre comercial
            $table->string('email')->unique();

            // Opcionales
            $table->enum('tipo_cliente', ['gobierno', 'empresa'])->nullable()->index(); // gobierno (licitaciones) | empresa
            $table->string('rfc')->nullable()->index();          // número fiscal
            $table->string('contacto')->nullable();              // persona de contacto
            $table->string('telefono')->nullable();

            // Dirección normalizada
            $table->string('calle')->nullable();
            $table->string('colonia')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('estado')->nullable();
            $table->string('cp', 10)->nullable();

            $table->boolean('estatus')->default(true); // activo/inactivo
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('clients');
    }
};
