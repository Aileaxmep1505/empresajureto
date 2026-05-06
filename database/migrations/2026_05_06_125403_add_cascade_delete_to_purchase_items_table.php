<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            /*
             | Primero intentamos eliminar la foreign key actual.
             | Laravel normalmente la nombra:
             | purchase_items_purchase_document_id_foreign
             */
            try {
                $table->dropForeign(['purchase_document_id']);
            } catch (\Throwable $e) {
                //
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            /*
             | Volvemos a crear la relación, ahora con cascadeOnDelete.
             */
            $table->foreign('purchase_document_id')
                ->references('id')
                ->on('purchase_documents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            try {
                $table->dropForeign(['purchase_document_id']);
            } catch (\Throwable $e) {
                //
            }
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            /*
             | Revertimos a una foreign key normal sin cascade.
             */
            $table->foreign('purchase_document_id')
                ->references('id')
                ->on('purchase_documents');
        });
    }
};