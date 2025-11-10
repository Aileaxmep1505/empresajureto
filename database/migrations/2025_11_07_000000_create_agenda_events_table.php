<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('agenda_events', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            $t->dateTime('start_at');                        // Fecha/hora del evento
            $t->integer('remind_offset_minutes')->default(60); // Cuánto antes se recuerda (min)
            $t->enum('repeat_rule', ['none','daily','weekly','monthly'])->default('none');
            $t->string('timezone')->default('America/Mexico_City');

            // Datos del destinatario
            $t->string('attendee_name')->nullable();
            $t->string('attendee_email')->nullable();
            $t->string('attendee_phone')->nullable(); // Para WhatsApp (con código país, ej. 52...)

            // Canales (email/whatsapp) activados
            $t->boolean('send_email')->default(true);
            $t->boolean('send_whatsapp')->default(false);

            // Control de recordatorios
            $t->dateTime('next_reminder_at')->nullable(); // Calculado a partir de start_at - offset
            $t->dateTime('last_reminder_sent_at')->nullable();

            $t->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('agenda_events');
    }
};
