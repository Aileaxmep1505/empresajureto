<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'label_styles')) {
                $table->json('label_styles')->nullable()->after('labels');
            }

            if (!Schema::hasColumn('projects', 'favorite')) {
                $table->boolean('favorite')->default(false)->after('start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'label_styles')) {
                $table->dropColumn('label_styles');
            }

            if (Schema::hasColumn('projects', 'favorite')) {
                $table->dropColumn('favorite');
            }
        });
    }
};
