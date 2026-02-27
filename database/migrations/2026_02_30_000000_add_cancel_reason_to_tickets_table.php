<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {

            // cancel_reason
            if (!Schema::hasColumn('tickets', 'cancel_reason')) {
                if (Schema::hasColumn('tickets', 'cancelled_at')) {
                    $table->text('cancel_reason')->nullable()->after('cancelled_at');
                } elseif (Schema::hasColumn('tickets', 'status')) {
                    $table->text('cancel_reason')->nullable()->after('status');
                } else {
                    $table->text('cancel_reason')->nullable();
                }
            }

            // cancelled_by
            if (!Schema::hasColumn('tickets', 'cancelled_by')) {
                if (Schema::hasColumn('tickets', 'cancel_reason')) {
                    $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancel_reason');
                } else {
                    $table->unsignedBigInteger('cancelled_by')->nullable();
                }
            }

            // completed_by
            if (!Schema::hasColumn('tickets', 'completed_by')) {
                if (Schema::hasColumn('tickets', 'completed_at')) {
                    $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');
                } elseif (Schema::hasColumn('tickets', 'status')) {
                    $table->unsignedBigInteger('completed_by')->nullable()->after('status');
                } else {
                    $table->unsignedBigInteger('completed_by')->nullable();
                }
            }

            // 🔒 FK opcional
            // $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            foreach (['cancel_reason','cancelled_by','completed_by'] as $col) {
                if (Schema::hasColumn('tickets', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};