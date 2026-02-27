<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Quién lo asignó (revisor principal)
            if (!Schema::hasColumn('tickets', 'assigned_by')) {
                $table->unsignedBigInteger('assigned_by')->nullable()->index()->after('assignee_id');
            }

            // Flujo de revisión
            if (!Schema::hasColumn('tickets', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->index()->after('due_at');
            }
            if (!Schema::hasColumn('tickets', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->index()->after('submitted_at');
            }

            // Calificación (1-5) + comentario
            if (!Schema::hasColumn('tickets', 'review_rating')) {
                $table->unsignedTinyInteger('review_rating')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('tickets', 'review_comment')) {
                $table->text('review_comment')->nullable()->after('review_rating');
            }

            // Reapertura
            if (!Schema::hasColumn('tickets', 'reopen_reason')) {
                $table->text('reopen_reason')->nullable()->after('review_comment');
            }
            if (!Schema::hasColumn('tickets', 'reopened_count')) {
                $table->unsignedInteger('reopened_count')->default(0)->after('reopen_reason');
            }
            if (!Schema::hasColumn('tickets', 'reopened_at')) {
                $table->timestamp('reopened_at')->nullable()->index()->after('reopened_count');
            }

            // FK opcionales (si quieres estrictas)
            // $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach ([
                'assigned_by','submitted_at','reviewed_at','review_rating','review_comment',
                'reopen_reason','reopened_count','reopened_at'
            ] as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};