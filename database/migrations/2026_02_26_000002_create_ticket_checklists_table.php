<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();

            $table->string('title')->nullable(); // opcional (ej: "Checklist sugerido")
            $table->string('source')->default('ai'); // ai | manual
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->json('meta')->nullable(); // prompt/model, etc
            $table->timestamps();

            $table->index(['ticket_id','source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_checklists');
    }
};