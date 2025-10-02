<?php

// database/migrations/xxxx_xx_xx_xxxxxx_add_avatar_path_to_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','avatar_path')) {
                $table->string('avatar_path')->nullable()->after('email');
            }
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users','avatar_path')) {
                $table->dropColumn('avatar_path');
            }
        });
    }
};

