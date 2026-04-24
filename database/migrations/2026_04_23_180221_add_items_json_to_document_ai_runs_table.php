<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_ai_runs', function (Blueprint $table) {
            $table->json('items_json')->nullable()->after('structured_json');
        });
    }

    public function down(): void
    {
        Schema::table('document_ai_runs', function (Blueprint $table) {
            $table->dropColumn('items_json');
        });
    }
};