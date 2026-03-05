<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            if (!Schema::hasColumn('publications', 'operation_datetime')) {
                $table->dateTime('operation_datetime')->nullable()->index()->after('batch_key');
            }
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            if (Schema::hasColumn('publications', 'operation_datetime')) {
                $table->dropIndex(['operation_datetime']);
                $table->dropColumn('operation_datetime');
            }
        });
    }
};