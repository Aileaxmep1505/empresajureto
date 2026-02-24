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

            // inbound/outbound
            $table->enum('direction', ['in', 'out'])->index();

            // id que devuelve WhatsApp (wamid....) para outbound o inbound
            $table->string('wa_message_id', 255)->nullable()->index();

            $table->string('from_wa_id', 32)->nullable()->index();
            $table->string('to_wa_id', 32)->nullable()->index();

            $table->string('type', 40)->default('text')->index(); // text, image, interactive, etc.
            $table->text('body')->nullable();                     // texto (si aplica)
            $table->json('payload')->nullable();                  // payload crudo para otros tipos

            // statuses para outbound: sent/delivered/read/failed
            $table->string('status', 30)->nullable()->index();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->unique(['conversation_id','wa_message_id'], 'uniq_conv_wa_msg');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
    }
};