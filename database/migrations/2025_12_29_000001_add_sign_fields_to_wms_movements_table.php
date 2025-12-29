<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('wms_movements', function (Blueprint $table) {
            $table->string('authorized_name')->nullable()->after('note');
            $table->string('authorized_role')->nullable()->after('authorized_name');
            $table->string('delivered_name')->nullable()->after('authorized_role');
            $table->string('received_name')->nullable()->after('delivered_name');
        });
    }

    public function down(): void
    {
        Schema::table('wms_movements', function (Blueprint $table) {
            $table->dropColumn(['authorized_name','authorized_role','delivered_name','received_name']);
        });
    }
};
