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
                $table->string('support_status', 30)->default('bot')->index()->after('status');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_id')) {
                $table->foreignId('advisor_id')->nullable()->after('guest_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'support_requested_at')) {
                $table->timestamp('support_requested_at')->nullable()->index()->after('last_activity_at');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_assigned_at')) {
                $table->timestamp('advisor_assigned_at')->nullable()->after('support_requested_at');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_closed_at')) {
                $table->timestamp('advisor_closed_at')->nullable()->after('advisor_assigned_at');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'customer_unread_count')) {
                $table->unsignedInteger('customer_unread_count')->default(0)->after('advisor_closed_at');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_unread_count')) {
                $table->unsignedInteger('advisor_unread_count')->default(0)->after('customer_unread_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('web_assistant_conversations', function (Blueprint $table) {
            foreach ([
                'advisor_id',
                'support_status',
                'support_requested_at',
                'advisor_assigned_at',
                'advisor_closed_at',
                'customer_unread_count',
                'advisor_unread_count',
            ] as $column) {
                if (Schema::hasColumn('web_assistant_conversations', $column)) {
                    if ($column === 'advisor_id') {
                        try {
                            $table->dropConstrainedForeignId('advisor_id');
                        } catch (\Throwable $e) {
                            $table->dropColumn('advisor_id');
                        }
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
