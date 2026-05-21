<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedTinyInteger('column_id')->default(1); // 1=Análisis de Bases, 2=Revisión, etc.
            $table->string('priority', 20)->default('Normal');
            $table->string('color', 20)->nullable();
            $table->string('assigned_to', 20)->nullable();
            $table->date('start_date')->nullable();
            $table->boolean('favorite')->default(false);
            $table->json('labels')->nullable();

            // AI / extracción
            $table->string('status', 30)->default('processing'); // processing, ready, error, partial
            $table->json('structured_data')->nullable(); // resultado del structurer (Ficha, Fechas, Resumen Ejecutivo, etc.)
            $table->text('error_message')->nullable();

            // Edición humana
            $table->longText('draft_content')->nullable(); // Borrador (WYSIWYG)
            $table->json('checklist')->nullable();

            $table->timestamps();
            $table->index(['column_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};