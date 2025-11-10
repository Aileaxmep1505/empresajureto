<?php

namespace App\Mail;

use App\Models\AgendaEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgendaReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AgendaEvent $event) {}

    public function build()
    {
        return $this->subject('Recordatorio: ' . $this->event->title)
            ->view('emails.agenda.reminder', ['event' => $this->event]);
    }
}
