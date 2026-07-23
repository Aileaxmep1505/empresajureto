<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_assistant_conversations', function (Blueprint $table) {
            $table->timestamp('handoff_requested_at')
                ->nullable()
                ->after('updated_at');

            $table->string('handoff_status', 30)
                ->nullable()
                ->default(null)
                ->after('handoff_requested_at');

            $table->index('handoff_requested_at');
            $table->index('handoff_status');
        });
    }

    public function down(): void
    {
        Schema::table('web_assistant_conversations', function (Blueprint $table) {
            $table->dropIndex(['handoff_requested_at']);
            $table->dropIndex(['handoff_status']);

            $table->dropColumn([
                'handoff_requested_at',
                'handoff_status',
            ]);
        });
    }
};