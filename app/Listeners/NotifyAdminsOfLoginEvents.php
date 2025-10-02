<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\AccessAttemptNotification;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;

class NotifyAdminsOfLoginEvents
{
    protected function admins()
    {
        // Con Spatie (ya lo usas con @role)
        return User::role('admin')->get();
    }

    protected function notify(string $email, string $status): void
    {
        $ip = request()->ip() ?? '0.0.0.0';
        $ua = request()->header('User-Agent', 'unknown');

        foreach ($this->admins() as $admin) {
            $admin->notify(new AccessAttemptNotification($email, $ip, $ua, $status));
        }
    }

    public function handleLogin(Login $event): void
    {
        $this->notify($event->user->email ?? 'desconocido', 'success');
    }

    public function handleFailed(Failed $event): void
    {
        $this->notify($event->credentials['email'] ?? 'desconocido', 'failed');
    }

    public function handleLockout(Lockout $event): void
    {
        $email = $event->request?->input('email', 'desconocido') ?? 'desconocido';
        $this->notify($email, 'lockout');
    }
}
