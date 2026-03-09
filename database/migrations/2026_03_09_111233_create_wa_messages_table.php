<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('wa_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('direction', 10)->index(); // inbound, outbound
            $table->string('message_type', 30)->nullable()->index();
            $table->string('wa_message_id')->nullable()->index();

            $table->text('text')->nullable();
            $table->string('status', 30)->nullable()->index();
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
    }
};