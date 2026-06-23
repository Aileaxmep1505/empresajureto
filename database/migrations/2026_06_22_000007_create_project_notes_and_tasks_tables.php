<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_notes')) {
            Schema::create('project_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('content');
                $table->timestamps();

                $table->index(['project_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('project_tasks')) {
            Schema::create('project_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title', 500);
                $table->string('priority', 20)->default('normal');
                $table->boolean('completed')->default(false);
                $table->date('due_date')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'completed']);
                $table->index(['project_id', 'due_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
        Schema::dropIfExists('project_notes');
    }
};
