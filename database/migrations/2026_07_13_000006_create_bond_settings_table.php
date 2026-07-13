<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bond_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('financial_statements_audited')->default(false);
            $table->boolean('has_solidary_debtor')->default(false);
            $table->string('solidary_business_name')->nullable();
            $table->string('solidary_tax_id', 30)->nullable();
            $table->string('solidary_representative')->nullable();
            $table->string('solidary_phone', 30)->nullable();
            $table->boolean('has_real_estate_guarantee')->default(false);
            $table->string('property_type', 50)->nullable();
            $table->decimal('property_value', 15, 2)->nullable();
            $table->text('property_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bond_settings');
    }
};
