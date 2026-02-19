<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alta_docs', function (Blueprint $table) {
            // ✅ vigencia (fecha de vencimiento)
            if (!Schema::hasColumn('alta_docs', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('doc_date');
            }

            // ✅ link y contraseña
            if (!Schema::hasColumn('alta_docs', 'link_url')) {
                $table->string('link_url', 500)->nullable()->after('expires_at');
            }

            if (!Schema::hasColumn('alta_docs', 'link_password')) {
                $table->string('link_password', 180)->nullable()->after('link_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alta_docs', function (Blueprint $table) {
            if (Schema::hasColumn('alta_docs', 'link_password')) {
                $table->dropColumn('link_password');
            }
            if (Schema::hasColumn('alta_docs', 'link_url')) {
                $table->dropColumn('link_url');
            }
            if (Schema::hasColumn('alta_docs', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
        });
    }
};
