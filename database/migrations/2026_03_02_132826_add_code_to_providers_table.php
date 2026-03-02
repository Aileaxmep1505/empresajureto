<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->unique()->after('id'); // PROV-00001
        });

        // Opcional: si ya tienes proveedores, puedes llenar code después con un comando.
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};