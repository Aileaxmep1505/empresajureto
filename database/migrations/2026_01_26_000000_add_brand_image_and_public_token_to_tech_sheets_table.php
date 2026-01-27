<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\TechSheet;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tech_sheets', function (Blueprint $table) {
            $table->string('brand_image_path')->nullable()->after('image_path');
            $table->string('public_token', 64)->nullable()->unique()->after('brand_image_path');
        });

        // Opcional: generar token público para fichas existentes
        if (Schema::hasColumn('tech_sheets', 'public_token')) {
            TechSheet::whereNull('public_token')->chunkById(100, function ($rows) {
                foreach ($rows as $sheet) {
                    $sheet->public_token = (string) Str::uuid();
                    $sheet->save();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('tech_sheets', function (Blueprint $table) {
            $table->dropColumn(['brand_image_path', 'public_token']);
        });
    }
};
