<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->boolean('is_sample')->default(false)->after('is_featured');
            $table->string('sample_status', 20)->nullable()->after('is_sample');
            $table->string('sample_holder')->nullable()->after('sample_status');
            $table->date('sample_out_at')->nullable()->after('sample_holder');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn(['is_sample', 'sample_status', 'sample_holder', 'sample_out_at']);
        });
    }
};