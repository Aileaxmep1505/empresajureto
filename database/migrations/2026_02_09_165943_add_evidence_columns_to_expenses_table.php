<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Evidencia única
            if (!Schema::hasColumn('expenses', 'attachment_path'))  $table->string('attachment_path')->nullable()->after('description');
            if (!Schema::hasColumn('expenses', 'attachment_name'))  $table->string('attachment_name')->nullable()->after('attachment_path');
            if (!Schema::hasColumn('expenses', 'attachment_mime'))  $table->string('attachment_mime')->nullable()->after('attachment_name');
            if (!Schema::hasColumn('expenses', 'attachment_size'))  $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');

            // Evidencias múltiples (JSON)
            if (!Schema::hasColumn('expenses', 'evidence_paths'))    $table->longText('evidence_paths')->nullable()->after('attachment_size');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'evidence_paths'))   $table->dropColumn('evidence_paths');
            if (Schema::hasColumn('expenses', 'attachment_size'))  $table->dropColumn('attachment_size');
            if (Schema::hasColumn('expenses', 'attachment_mime'))  $table->dropColumn('attachment_mime');
            if (Schema::hasColumn('expenses', 'attachment_name'))  $table->dropColumn('attachment_name');
            if (Schema::hasColumn('expenses', 'attachment_path'))  $table->dropColumn('attachment_path');
        });
    }
};
