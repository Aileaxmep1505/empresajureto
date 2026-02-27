<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets','ai_checklist_opt_out')) {
                $table->boolean('ai_checklist_opt_out')->default(false)->after('status');
            }
            if (!Schema::hasColumn('tickets','ai_checklist_generated_at')) {
                $table->dateTime('ai_checklist_generated_at')->nullable()->after('ai_checklist_opt_out');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets','ai_checklist_opt_out')) $table->dropColumn('ai_checklist_opt_out');
            if (Schema::hasColumn('tickets','ai_checklist_generated_at')) $table->dropColumn('ai_checklist_generated_at');
        });
    }
};