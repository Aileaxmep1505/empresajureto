<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            // 3 fotos (rutas en storage)
            $table->string('photo_1')->nullable()->after('image_url');
            $table->string('photo_2')->nullable()->after('photo_1');
            $table->string('photo_3')->nullable()->after('photo_2');

            // Opcional: si ya NO usarás links, puedes eliminar estos campos:
            // $table->dropColumn(['image_url', 'images']);
        });
    }

    public function down(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn(['photo_1','photo_2','photo_3']);

            // si en up() borraste columns, aquí tendrías que recrearlas (opcional)
        });
    }
};
