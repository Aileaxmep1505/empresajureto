<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            if (!Schema::hasColumn('publications', 'batch_key')) {
                $table->string('batch_key', 64)->nullable()->index()->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            if (Schema::hasColumn('publications', 'batch_key')) {
                $table->dropIndex(['batch_key']);
                $table->dropColumn('batch_key');
            }
        });
    }
};