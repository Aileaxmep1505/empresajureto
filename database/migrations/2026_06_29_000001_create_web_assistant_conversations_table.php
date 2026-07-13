<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_assistant_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_id', 80)->nullable()->index();
            $table->string('title')->default('Nueva conversación');
            $table->string('status', 40)->default('active')->index();
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_activity_at']);
            $table->index(['guest_id', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_assistant_conversations');
    }
};
