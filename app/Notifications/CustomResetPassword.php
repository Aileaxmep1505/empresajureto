<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends BaseResetPassword
{
    public function toMail($notifiable)
    {
        $url = url(route('password.reset', $this->token, false));

        return (new MailMessage)
            ->subject('Restablece tu contraseña')
            ->view('emails.reset_html', [
                'user' => $notifiable,
                'url'  => $url,
            ]);
    }
}
