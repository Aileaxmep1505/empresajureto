<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::table('web_assistant_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('web_assistant_conversations', 'advisor_joined_at')) {
                $table->timestamp('advisor_joined_at')
                    ->nullable()
                    ->after('advisor_id');
            }

            if (!Schema::hasColumn('web_assistant_conversations', 'closed_at')) {
                $table->timestamp('closed_at')
                    ->nullable()
                    ->after('advisor_joined_at');
            }
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::table('web_assistant_conversations', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('web_assistant_conversations', 'advisor_joined_at')) {
                $columns[] = 'advisor_joined_at';
            }

            if (Schema::hasColumn('web_assistant_conversations', 'closed_at')) {
                $columns[] = 'closed_at';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};