<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'structured_data')) {
                $table->json('structured_data')->nullable()->after('status');
            }

            if (!Schema::hasColumn('projects', 'checklist')) {
                $table->json('checklist')->nullable()->after('structured_data');
            }

            if (!Schema::hasColumn('projects', 'draft_content')) {
                $table->longText('draft_content')->nullable()->after('checklist');
            }

            if (!Schema::hasColumn('projects', 'report_content')) {
                $table->longText('report_content')->nullable()->after('draft_content');
            }

            if (!Schema::hasColumn('projects', 'error_message')) {
                $table->text('error_message')->nullable()->after('report_content');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'error_message')) {
                $table->dropColumn('error_message');
            }

            if (Schema::hasColumn('projects', 'report_content')) {
                $table->dropColumn('report_content');
            }

            if (Schema::hasColumn('projects', 'draft_content')) {
                $table->dropColumn('draft_content');
            }

            if (Schema::hasColumn('projects', 'checklist')) {
                $table->dropColumn('checklist');
            }

            if (Schema::hasColumn('projects', 'structured_data')) {
                $table->dropColumn('structured_data');
            }
        });
    }
};