<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            // ✅ assigned_by (no dependas de assignee_id)
            if (!Schema::hasColumn('tickets', 'assigned_by')) {
                if (Schema::hasColumn('tickets', 'assignee_id')) {
                    $table->unsignedBigInteger('assigned_by')->nullable()->index()->after('assignee_id');
                } elseif (Schema::hasColumn('tickets', 'created_by')) {
                    $table->unsignedBigInteger('assigned_by')->nullable()->index()->after('created_by');
                } elseif (Schema::hasColumn('tickets', 'status')) {
                    $table->unsignedBigInteger('assigned_by')->nullable()->index()->after('status');
                } else {
                    $table->unsignedBigInteger('assigned_by')->nullable()->index();
                }
            }

            // ✅ submitted_at
            if (!Schema::hasColumn('tickets', 'submitted_at')) {
                if (Schema::hasColumn('tickets', 'due_at')) {
                    $table->timestamp('submitted_at')->nullable()->index()->after('due_at');
                } elseif (Schema::hasColumn('tickets', 'updated_at')) {
                    $table->timestamp('submitted_at')->nullable()->index()->after('updated_at');
                } else {
                    $table->timestamp('submitted_at')->nullable()->index();
                }
            }

            // ✅ reviewed_at
            if (!Schema::hasColumn('tickets', 'reviewed_at')) {
                if (Schema::hasColumn('tickets', 'submitted_at')) {
                    $table->timestamp('reviewed_at')->nullable()->index()->after('submitted_at');
                } else {
                    $table->timestamp('reviewed_at')->nullable()->index();
                }
            }

            // ✅ review_rating
            if (!Schema::hasColumn('tickets', 'review_rating')) {
                if (Schema::hasColumn('tickets', 'reviewed_at')) {
                    $table->unsignedTinyInteger('review_rating')->nullable()->after('reviewed_at');
                } else {
                    $table->unsignedTinyInteger('review_rating')->nullable();
                }
            }

            // ✅ review_comment
            if (!Schema::hasColumn('tickets', 'review_comment')) {
                if (Schema::hasColumn('tickets', 'review_rating')) {
                    $table->text('review_comment')->nullable()->after('review_rating');
                } else {
                    $table->text('review_comment')->nullable();
                }
            }

            // ✅ reopen_reason
            if (!Schema::hasColumn('tickets', 'reopen_reason')) {
                if (Schema::hasColumn('tickets', 'review_comment')) {
                    $table->text('reopen_reason')->nullable()->after('review_comment');
                } elseif (Schema::hasColumn('tickets', 'status')) {
                    $table->text('reopen_reason')->nullable()->after('status');
                } else {
                    $table->text('reopen_reason')->nullable();
                }
            }

            // ✅ reopened_count
            if (!Schema::hasColumn('tickets', 'reopened_count')) {
                if (Schema::hasColumn('tickets', 'reopen_reason')) {
                    $table->unsignedInteger('reopened_count')->default(0)->after('reopen_reason');
                } else {
                    $table->unsignedInteger('reopened_count')->default(0);
                }
            }

            // ✅ reopened_at
            if (!Schema::hasColumn('tickets', 'reopened_at')) {
                if (Schema::hasColumn('tickets', 'reopened_count')) {
                    $table->timestamp('reopened_at')->nullable()->index()->after('reopened_count');
                } else {
                    $table->timestamp('reopened_at')->nullable()->index();
                }
            }

            // 🔒 FK opcional (solo si tu BD está limpia)
            // if (Schema::hasTable('users')) {
            //   $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            // }
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