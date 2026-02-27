<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('ticket_checklists')->cascadeOnDelete();

            $table->string('title');
            $table->text('detail')->nullable();

            // “Recomendado” (no bloquea, pero se marca como importante)
            $table->boolean('recommended')->default(true);

            // Estado
            $table->boolean('done')->default(false);
            $table->dateTime('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();

            // Evidencia (solo nota, como pediste)
            $table->text('evidence_note')->nullable();

            $table->unsignedInteger('sort_order')->default(100);

            // Meta extra (IA)
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->index(['checklist_id','done']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_checklist_items');
    }
};