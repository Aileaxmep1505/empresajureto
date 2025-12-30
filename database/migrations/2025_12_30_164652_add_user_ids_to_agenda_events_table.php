<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            // MySQL 5.7+ soporta JSON. Si tu MySQL es viejo, usa text().
            $table->json('user_ids')->nullable()->after('repeat_rule');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            $table->dropColumn('user_ids');
        });
    }
};
