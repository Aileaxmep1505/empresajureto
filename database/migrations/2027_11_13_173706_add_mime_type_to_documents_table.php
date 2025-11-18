<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // añade mime_type si no existe (nullable por seguridad)
            if (! Schema::hasColumn('documents', 'mime_type')) {
                $table->string('mime_type')->nullable()->after('file_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'mime_type')) {
                $table->dropColumn('mime_type');
            }
        });
    }
};
