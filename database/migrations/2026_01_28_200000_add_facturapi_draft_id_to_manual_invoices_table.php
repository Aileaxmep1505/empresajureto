<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('manual_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('manual_invoices', 'facturapi_draft_id')) {
                $table->string('facturapi_draft_id')->nullable()->after('facturapi_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manual_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('manual_invoices', 'facturapi_draft_id')) {
                $table->dropColumn('facturapi_draft_id');
            }
        });
    }
};
