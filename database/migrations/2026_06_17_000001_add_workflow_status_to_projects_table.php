<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'workflow_status')) {
                $table->string('workflow_status', 60)
                    ->default('analisis_bases')
                    ->after('status')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'workflow_status')) {
                $table->dropIndex(['workflow_status']);
                $table->dropColumn('workflow_status');
            }
        });
    }
};
