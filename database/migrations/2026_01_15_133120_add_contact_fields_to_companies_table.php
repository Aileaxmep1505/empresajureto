<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('rfc');
            $table->string('email', 255)->nullable()->after('phone');
            $table->string('address', 255)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['phone','email','address']);
        });
    }
};
