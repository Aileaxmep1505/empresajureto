<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_checklist_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_checklist_item_id')
                ->constrained('project_checklist_items')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->longText('body');

            $table->timestamps();

            // Nombre corto para evitar error MySQL 1059
            $table->index(
                ['project_checklist_item_id', 'created_at'],
                'pcli_notes_item_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_checklist_notes');
    }
};