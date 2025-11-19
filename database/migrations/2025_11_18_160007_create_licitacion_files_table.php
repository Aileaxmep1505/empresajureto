<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licitacion_files', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_original');
            $table->string('ruta'); // ruta en storage
            $table->string('mime_type')->nullable();
            $table->enum('estado', ['pendiente', 'procesando', 'procesado', 'error'])
                  ->default('pendiente');
            $table->unsignedInteger('total_items')->default(0);
            $table->text('error_mensaje')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacion_files');
    }
};
