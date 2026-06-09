<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('sat_prodserv');

        Schema::create('sat_prodserv', function (Blueprint $table) {
            $table->string('clave', 8)->primary();
            $table->string('descripcion', 500);
            $table->text('palabras_similares')->nullable();
            $table->string('incluir_iva', 30)->nullable();
            $table->string('incluir_ieps', 30)->nullable();
            $table->index('descripcion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sat_prodserv');
    }
};