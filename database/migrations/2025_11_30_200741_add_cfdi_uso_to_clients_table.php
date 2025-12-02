<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'cfdi_uso')) {
                $table->string('cfdi_uso', 3)
                    ->nullable()
                    ->after('regimen_fiscal'); // queda junto a los campos fiscales
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'cfdi_uso')) {
                $table->dropColumn('cfdi_uso');
            }
        });
    }
};
