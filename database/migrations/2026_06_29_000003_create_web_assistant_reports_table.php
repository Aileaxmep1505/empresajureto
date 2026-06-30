<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_assistant_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained('web_assistant_conversations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_id', 80)->nullable()->index();
            $table->string('folio', 40)->unique();
            $table->string('type', 60)->default('general')->index();
            $table->string('status', 40)->default('open')->index();
            $table->string('order_table', 80)->nullable();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('order_folio', 120)->nullable()->index();
            $table->string('customer_email')->nullable()->index();
            $table->text('summary')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_assistant_reports');
    }
};
