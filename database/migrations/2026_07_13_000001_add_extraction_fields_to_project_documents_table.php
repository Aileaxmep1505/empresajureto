<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('project_documents', 'extracted_text')) {
                $table->longText('extracted_text')->nullable()->after('file_size');
            }

            if (!Schema::hasColumn('project_documents', 'extracted_raw')) {
                $table->json('extracted_raw')->nullable()->after('extracted_text');
            }

            if (!Schema::hasColumn('project_documents', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_documents', function (Blueprint $table) {
            $columns = [];

            foreach (['extracted_text', 'extracted_raw', 'processed_at'] as $column) {
                if (Schema::hasColumn('project_documents', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
