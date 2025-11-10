<?php
// database/migrations/2025_11_03_000001_add_geodata_to_providers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('providers', function (Blueprint $t) {
            $t->decimal('lat', 10, 7)->nullable()->after('cp');
            $t->decimal('lng', 10, 7)->nullable()->after('lat');
            $t->json('address_json')->nullable()->after('lng');
            $t->index(['lat','lng']);
        });
    }
    public function down(): void {
        Schema::table('providers', function (Blueprint $t) {
            $t->dropColumn(['lat','lng','address_json']);
        });
    }
};
