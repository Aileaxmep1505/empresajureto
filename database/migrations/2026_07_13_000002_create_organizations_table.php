<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('country', 2)->nullable();
            $table->string('organization_type', 50)->nullable();
            $table->string('tax_id', 30)->nullable();
            $table->string('legal_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('institutional_email')->nullable();
            $table->string('institutional_phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->string('legal_country', 2)->nullable();
            $table->string('legal_state', 100)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->string('city', 120)->nullable();
            $table->text('legal_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
