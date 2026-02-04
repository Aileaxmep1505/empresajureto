<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alta_docs', function (Blueprint $table) {
            if (!Schema::hasColumn('alta_docs', 'category')) {
                $table->string('category', 50)->nullable()->after('path');
            }
            if (!Schema::hasColumn('alta_docs', 'title')) {
                $table->string('title', 160)->nullable()->after('category');
            }
            if (!Schema::hasColumn('alta_docs', 'doc_date')) {
                $table->date('doc_date')->nullable()->after('title');
            }

            $table->index(['category', 'doc_date'], 'alta_docs_category_date_idx');
            $table->index(['title'], 'alta_docs_title_idx');
        });
    }

    public function down(): void
    {
        Schema::table('alta_docs', function (Blueprint $table) {
            if (Schema::hasColumn('alta_docs', 'category')) {
                $table->dropIndex('alta_docs_category_date_idx');
                $table->dropColumn('category');
            }
            if (Schema::hasColumn('alta_docs', 'title')) {
                $table->dropIndex('alta_docs_title_idx');
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('alta_docs', 'doc_date')) {
                $table->dropColumn('doc_date');
            }
        });
    }
};
