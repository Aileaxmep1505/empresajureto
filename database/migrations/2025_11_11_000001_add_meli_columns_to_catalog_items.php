<?php
// database/migrations/2025_11_11_000001_add_meli_columns_to_catalog_items.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('catalog_items', function (Blueprint $t) {
            // Atributos que suelen pedir categorías (al menos BRAND y MODEL)
            $t->string('brand_name')->nullable()->after('brand_id');
            $t->string('model_name')->nullable()->after('brand_name');

            // Imagenes adicionales ya las tienes en JSON "images"
            // ML
            $t->string('meli_item_id', 32)->nullable()->index();
            $t->string('meli_category_id', 32)->nullable();
            $t->string('meli_listing_type_id', 32)->nullable();
            $t->timestamp('meli_synced_at')->nullable();
            $t->string('meli_status', 40)->nullable(); // active/paused/closed/draft
            $t->text('meli_last_error')->nullable();
        });
    }
    public function down(): void {
        Schema::table('catalog_items', function (Blueprint $t) {
            $t->dropColumn([
                'brand_name','model_name','meli_item_id','meli_category_id',
                'meli_listing_type_id','meli_synced_at','meli_status','meli_last_error'
            ]);
        });
    }
};
