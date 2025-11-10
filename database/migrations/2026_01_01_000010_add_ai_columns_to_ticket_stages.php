<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ticket_stages', function (Blueprint $t) {
            if (!Schema::hasColumn('ticket_stages','ai_prompt')) {
                $t->string('ai_prompt',255)->nullable();
            }
            if (!Schema::hasColumn('ticket_stages','ai_instructions')) {
                $t->text('ai_instructions')->nullable();
            }
            if (!Schema::hasColumn('ticket_stages','requires_evidence')) {
                $t->boolean('requires_evidence')->default(false);
            }
        });
    }
    public function down(): void {
        Schema::table('ticket_stages', function (Blueprint $t) {
            if (Schema::hasColumn('ticket_stages','ai_prompt')) $t->dropColumn('ai_prompt');
            if (Schema::hasColumn('ticket_stages','ai_instructions')) $t->dropColumn('ai_instructions');
            if (Schema::hasColumn('ticket_stages','requires_evidence')) $t->dropColumn('requires_evidence');
        });
    }
};
