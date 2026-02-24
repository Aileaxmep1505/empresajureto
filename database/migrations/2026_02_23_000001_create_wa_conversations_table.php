<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wa_conversations', function (Blueprint $table) {
            $table->id();

            // WhatsApp wa_id (E164 sin +) ej: 5212205381046
            $table->string('wa_id', 32)->unique();

            $table->string('name', 120)->nullable();
            $table->string('last_message_preview', 255)->nullable();
            $table->timestamp('last_message_at')->nullable();

            $table->unsignedInteger('unread_count')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_conversations');
    }
};