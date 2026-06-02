<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            $table->string('sign_token', 64)->nullable()->unique()->after('folio');
            $table->string('signature_status')->default('pending')->after('sign_token'); // pending | signed
            $table->timestamp('signed_at')->nullable()->after('signature_status');
            $table->string('signer_name')->nullable()->after('signed_at');
            $table->longText('signature_image')->nullable()->after('signer_name'); // firma en base64 (PNG)
            $table->json('delivery_checklist')->nullable()->after('signature_image'); // con qué se entrega
        });
    }

    public function down(): void
    {
        Schema::table('inventory_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'sign_token', 'signature_status', 'signed_at',
                'signer_name', 'signature_image', 'delivery_checklist',
            ]);
        });
    }
};