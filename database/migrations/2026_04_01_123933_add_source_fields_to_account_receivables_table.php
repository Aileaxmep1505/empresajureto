<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_receivables', function (Blueprint $table) {
            $table->string('source_module', 80)->nullable()->after('company_id');
            $table->string('source_type', 180)->nullable()->after('source_module');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->string('source_folio', 120)->nullable()->after('source_id');

            $table->index('source_module');
            $table->index(['source_type', 'source_id'], 'account_receivables_source_idx');
            $table->unique(['source_type', 'source_id'], 'account_receivables_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('account_receivables', function (Blueprint $table) {
            $table->dropUnique('account_receivables_source_unique');
            $table->dropIndex('source_module');
            $table->dropIndex('account_receivables_source_idx');

            $table->dropColumn([
                'source_module',
                'source_type',
                'source_id',
                'source_folio',
            ]);
        });
    }
};