<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('web_assistant_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('web_assistant_conversations', 'support_status')) {
                $table->string('support_status', 30)
                    ->nullable()
                    ->default('bot');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'support_requested_at')) {
                $table->timestamp('support_requested_at')
                    ->nullable();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'handoff_status')) {
                $table->string('handoff_status', 30)
                    ->nullable()
                    ->default('bot');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'handoff_requested_at')) {
                $table->timestamp('handoff_requested_at')
                    ->nullable();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_id')) {
                $table->unsignedBigInteger('advisor_id')
                    ->nullable();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_joined_at')) {
                $table->timestamp('advisor_joined_at')
                    ->nullable();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'last_customer_message_at')) {
                $table->timestamp('last_customer_message_at')
                    ->nullable();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'last_advisor_message_at')) {
                $table->timestamp('last_advisor_message_at')
                    ->nullable();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'closed_at')) {
                $table->timestamp('closed_at')
                    ->nullable();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'customer_unread_count')) {
                $table->unsignedInteger('customer_unread_count')
                    ->default(0);
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_unread_count')) {
                $table->unsignedInteger('advisor_unread_count')
                    ->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('web_assistant_conversations', function (Blueprint $table) {
            $columns = [
                'support_status',
                'support_requested_at',
                'handoff_status',
                'handoff_requested_at',
                'advisor_id',
                'advisor_joined_at',
                'last_customer_message_at',
                'last_advisor_message_at',
                'closed_at',
                'customer_unread_count',
                'advisor_unread_count',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('web_assistant_conversations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};