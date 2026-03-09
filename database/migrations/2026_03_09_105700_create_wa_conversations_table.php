<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wa_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 30)->index();
            $table->string('channel', 20)->default('whatsapp')->index();
            $table->string('status', 20)->default('bot')->index(); // bot, human, closed
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['phone', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_conversations');
    }
};