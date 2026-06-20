<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'priority')) {
                $table->string('priority', 30)->default('Normal')->after('column_id');
            }

            if (!Schema::hasColumn('projects', 'assigned')) {
                $table->string('assigned', 10)->nullable()->after('color');
            }

            if (!Schema::hasColumn('projects', 'assigned_name')) {
                $table->string('assigned_name', 120)->nullable()->after('assigned');
            }

            if (!Schema::hasColumn('projects', 'assigned_email')) {
                $table->string('assigned_email', 160)->nullable()->after('assigned_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'assigned_email')) {
                $table->dropColumn('assigned_email');
            }

            if (Schema::hasColumn('projects', 'assigned_name')) {
                $table->dropColumn('assigned_name');
            }

            if (Schema::hasColumn('projects', 'assigned')) {
                $table->dropColumn('assigned');
            }
        });
    }
};
