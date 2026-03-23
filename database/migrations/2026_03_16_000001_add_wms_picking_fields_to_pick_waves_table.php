<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pick_waves')) {
            return;
        }

        Schema::table('pick_waves', function (Blueprint $table) {
            if (!Schema::hasColumn('pick_waves', 'task_number')) {
                $table->string('task_number', 120)->nullable()->after('code');
            }

            if (!Schema::hasColumn('pick_waves', 'order_number')) {
                $table->string('order_number', 120)->nullable()->after('task_number');
            }

            if (!Schema::hasColumn('pick_waves', 'assigned_user_id')) {
                $table->unsignedBigInteger('assigned_user_id')->nullable()->after('assigned_to');
            }

            if (!Schema::hasColumn('pick_waves', 'priority')) {
                $table->string('priority', 20)->nullable()->after('assigned_user_id');
            }

            if (!Schema::hasColumn('pick_waves', 'notes')) {
                $table->text('notes')->nullable()->after('priority');
            }

            if (!Schema::hasColumn('pick_waves', 'items')) {
                $table->json('items')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('pick_waves', 'deliveries')) {
                $table->json('deliveries')->nullable()->after('items');
            }

            if (!Schema::hasColumn('pick_waves', 'total_phases')) {
                $table->unsignedInteger('total_phases')->default(1)->after('deliveries');
            }

            if (!Schema::hasColumn('pick_waves', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }
        });

        Schema::table('pick_waves', function (Blueprint $table) {
            if (Schema::hasColumn('pick_waves', 'assigned_user_id')) {
                try {
                    $table->foreign('assigned_user_id')
                        ->references('id')
                        ->on('users')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // evita error si ya existe la foreign key
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pick_waves')) {
            return;
        }

        Schema::table('pick_waves', function (Blueprint $table) {
            try {
                $table->dropForeign(['assigned_user_id']);
            } catch (\Throwable $e) {
            }
        });

        Schema::table('pick_waves', function (Blueprint $table) {
            if (Schema::hasColumn('pick_waves', 'completed_at')) {
                $table->dropColumn('completed_at');
            }

            if (Schema::hasColumn('pick_waves', 'total_phases')) {
                $table->dropColumn('total_phases');
            }

            if (Schema::hasColumn('pick_waves', 'deliveries')) {
                $table->dropColumn('deliveries');
            }

            if (Schema::hasColumn('pick_waves', 'items')) {
                $table->dropColumn('items');
            }

            if (Schema::hasColumn('pick_waves', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('pick_waves', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('pick_waves', 'assigned_user_id')) {
                $table->dropColumn('assigned_user_id');
            }

            if (Schema::hasColumn('pick_waves', 'order_number')) {
                $table->dropColumn('order_number');
            }

            if (Schema::hasColumn('pick_waves', 'task_number')) {
                $table->dropColumn('task_number');
            }
        });
    }
};