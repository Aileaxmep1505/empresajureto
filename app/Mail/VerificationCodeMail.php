<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;
    public int $minutes;

    public function __construct(string $code, int $minutes = 15)
    {
        $this->code = $code;
        $this->minutes = $minutes;
    }

    public function build()
    {
        return $this->subject('Tu código de verificación')
            ->view('emails.verify-code');
    }
}
