<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets','completion_detail')) {
                return;
            }

            if (Schema::hasColumn('tickets','cancel_reason')) {
                $table->longText('completion_detail')->nullable()->after('cancel_reason');
            } elseif (Schema::hasColumn('tickets','status')) {
                $table->longText('completion_detail')->nullable()->after('status');
            } else {
                $table->longText('completion_detail')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets','completion_detail')) {
                $table->dropColumn('completion_detail');
            }
        });
    }
};