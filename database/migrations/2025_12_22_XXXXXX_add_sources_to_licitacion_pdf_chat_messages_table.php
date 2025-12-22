<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('licitacion_pdf_chat_messages', function (Blueprint $table) {
            $table->json('sources')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('licitacion_pdf_chat_messages', function (Blueprint $table) {
            $table->dropColumn('sources');
        });
    }
};
