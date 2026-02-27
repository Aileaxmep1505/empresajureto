<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'cancel_reason')) {
                $table->text('cancel_reason')->nullable()->after('cancelled_at');
            }
            if (!Schema::hasColumn('tickets', 'cancelled_by')) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('cancel_reason');
            }
            if (!Schema::hasColumn('tickets', 'completed_by')) {
                $table->unsignedBigInteger('completed_by')->nullable()->after('completed_at');
            }

            // opcional: FK si quieres (si tu BD ya está limpia)
            // $table->foreign('cancelled_by')->references('id')->on('users')->nullOnDelete();
            // $table->foreign('completed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'cancel_reason')) $table->dropColumn('cancel_reason');
            if (Schema::hasColumn('tickets', 'cancelled_by')) $table->dropColumn('cancelled_by');
            if (Schema::hasColumn('tickets', 'completed_by')) $table->dropColumn('completed_by');
        });
    }
};