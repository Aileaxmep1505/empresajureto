<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            // Quién entrega / quién recibe (ambos flujos: firma y devolución)
            if (!Schema::hasColumn('inventory_assignments', 'delivered_by')) {
                $table->unsignedBigInteger('delivered_by')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('inventory_assignments', 'received_by')) {
                $table->unsignedBigInteger('received_by')->nullable()->after('delivered_by');
            }

            // Devolución
            if (!Schema::hasColumn('inventory_assignments', 'return_checklist')) {
                $table->json('return_checklist')->nullable();
            }
            if (!Schema::hasColumn('inventory_assignments', 'return_images')) {
                $table->json('return_images')->nullable();
            }
            if (!Schema::hasColumn('inventory_assignments', 'returned_at')) {
                $table->timestamp('returned_at')->nullable();
            }
            // Por si aún no existían de antes:
            if (!Schema::hasColumn('inventory_assignments', 'return_condition')) {
                $table->string('return_condition')->nullable();
            }
            if (!Schema::hasColumn('inventory_assignments', 'return_reason')) {
                $table->string('return_reason')->nullable();
            }
            if (!Schema::hasColumn('inventory_assignments', 'return_details')) {
                $table->text('return_details')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'delivered_by',
                'received_by',
                'return_checklist',
                'return_images',
                'returned_at',
            ]);
        });
    }
};