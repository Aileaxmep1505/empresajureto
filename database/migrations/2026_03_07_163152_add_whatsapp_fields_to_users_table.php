<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'whatsapp_phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('whatsapp_phone', 30)->nullable()->after('email');
            });
        }

        if (!Schema::hasColumn('users', 'whatsapp_opt_in_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('whatsapp_opt_in_at')->nullable()->after('whatsapp_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'whatsapp_opt_in_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('whatsapp_opt_in_at');
            });
        }

        if (Schema::hasColumn('users', 'whatsapp_phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('whatsapp_phone');
            });
        }
    }
};