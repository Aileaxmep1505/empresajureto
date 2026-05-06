<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('user_activities', 'module')) {
                $table->string('module')->nullable()->after('action');
            }

            if (!Schema::hasColumn('user_activities', 'screen')) {
                $table->string('screen')->nullable()->after('module');
            }

            if (!Schema::hasColumn('user_activities', 'description')) {
                $table->text('description')->nullable()->after('screen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            if (Schema::hasColumn('user_activities', 'description')) {
                $table->dropColumn('description');
            }

            if (Schema::hasColumn('user_activities', 'screen')) {
                $table->dropColumn('screen');
            }

            if (Schema::hasColumn('user_activities', 'module')) {
                $table->dropColumn('module');
            }
        });
    }
};