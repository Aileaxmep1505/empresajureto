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
                $table->boolean('is_pinned')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'is_pinned']);
                $table->index(['project_id', 'archived_at']);
            });
        } else {
            Schema::table('project_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('project_notes', 'is_pinned')) {
                    $table->boolean('is_pinned')->default(false)->after('content');
                }

                if (!Schema::hasColumn('project_notes', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('is_pinned');
                }
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
                $table->boolean('is_pinned')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();

                $table->index(['project_id', 'completed']);
                $table->index(['project_id', 'due_date']);
                $table->index(['project_id', 'is_pinned']);
                $table->index(['project_id', 'archived_at']);
            });
        } else {
            Schema::table('project_tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('project_tasks', 'is_pinned')) {
                    $table->boolean('is_pinned')->default(false)->after('due_date');
                }

                if (!Schema::hasColumn('project_tasks', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('is_pinned');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_tasks')) {
            Schema::table('project_tasks', function (Blueprint $table) {
                if (Schema::hasColumn('project_tasks', 'archived_at')) {
                    $table->dropColumn('archived_at');
                }

                if (Schema::hasColumn('project_tasks', 'is_pinned')) {
                    $table->dropColumn('is_pinned');
                }
            });
        }

        if (Schema::hasTable('project_notes')) {
            Schema::table('project_notes', function (Blueprint $table) {
                if (Schema::hasColumn('project_notes', 'archived_at')) {
                    $table->dropColumn('archived_at');
                }

                if (Schema::hasColumn('project_notes', 'is_pinned')) {
                    $table->dropColumn('is_pinned');
                }
            });
        }
    }
};
