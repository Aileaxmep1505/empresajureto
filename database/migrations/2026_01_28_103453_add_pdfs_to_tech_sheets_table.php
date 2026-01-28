<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tech_sheets', function (Blueprint $table) {
            $table->string('brand_pdf_path')->nullable()->after('image_path');
            $table->string('custom_pdf_path')->nullable()->after('brand_pdf_path');
            // 'brand' | 'custom' | null (null = usar PDF generado)
            $table->string('active_pdf')->nullable()->after('custom_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('tech_sheets', function (Blueprint $table) {
            $table->dropColumn(['brand_pdf_path','custom_pdf_path','active_pdf']);
        });
    }
};
