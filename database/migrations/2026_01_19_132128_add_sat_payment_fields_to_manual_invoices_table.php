<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_invoices', function (Blueprint $table) {
            // Campos SAT / Facturapi
            $table->string('payment_method', 3)->nullable()->after('currency'); // PUE/PPD
            $table->string('payment_form', 2)->nullable()->after('payment_method'); // 01..99
            $table->string('cfdi_use', 5)->nullable()->after('payment_form'); // G03, S01, etc.
            $table->string('exportation', 2)->nullable()->after('cfdi_use'); // 01..04
            $table->decimal('exchange_rate', 18, 6)->nullable()->after('exportation'); // tipo de cambio
        });
    }

    public function down(): void
    {
        Schema::table('manual_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_form',
                'cfdi_use',
                'exportation',
                'exchange_rate',
            ]);
        });
    }
};
