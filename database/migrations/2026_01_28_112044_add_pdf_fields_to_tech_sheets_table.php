<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tech_sheets', function (Blueprint $table) {
            // ✅ PDF marca
            if (!Schema::hasColumn('tech_sheets', 'brand_pdf_path')) {
                $table->string('brand_pdf_path')->nullable()->after('brand_image_path');
            }

            // ✅ PDF mío
            if (!Schema::hasColumn('tech_sheets', 'custom_pdf_path')) {
                $table->string('custom_pdf_path')->nullable()->after('brand_pdf_path');
            }

            // ✅ 'brand' | 'custom' | null (null = generado)
            if (!Schema::hasColumn('tech_sheets', 'active_pdf')) {
                $table->string('active_pdf', 20)->nullable()->after('custom_pdf_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tech_sheets', function (Blueprint $table) {
            if (Schema::hasColumn('tech_sheets', 'active_pdf')) {
                $table->dropColumn('active_pdf');
            }
            if (Schema::hasColumn('tech_sheets', 'custom_pdf_path')) {
                $table->dropColumn('custom_pdf_path');
            }
            if (Schema::hasColumn('tech_sheets', 'brand_pdf_path')) {
                $table->dropColumn('brand_pdf_path');
            }
        });
    }
};
