<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Agregamos 'category' a la tabla de publicaciones (archivos)
        Schema::table('publications', function (Blueprint $table) {
            // 'purchase' = Compra/Gasto, 'sale' = Venta/Entrega/Ingreso
            $table->string('category')->default('purchase')->after('kind')->index();
        });

        // Agregamos 'category' a la tabla de documentos extraídos (datos financieros)
        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->string('category')->default('purchase')->after('source_kind')->index();
        });
    }

    public function down()
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('purchase_documents', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};