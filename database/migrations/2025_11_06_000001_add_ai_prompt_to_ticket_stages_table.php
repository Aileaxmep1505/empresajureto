<?php
// database/migrations/2025_11_06_000001_add_ai_prompt_to_ticket_stages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('ticket_stages', 'ai_prompt')) {
            Schema::table('ticket_stages', function (Blueprint $table) {
                $table->text('ai_prompt')->nullable()->after('name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ticket_stages', 'ai_prompt')) {
            Schema::table('ticket_stages', function (Blueprint $table) {
                $table->dropColumn('ai_prompt');
            });
        }
    }
};
