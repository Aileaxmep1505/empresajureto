<?php

namespace App\Listeners;

use App\Services\Activity\ActivityLogger;
use Illuminate\Auth\Events\Failed;

class LogAuthFailed
{
    public function handle(Failed $event): void
    {
        // ⚠️ NO guardamos email/username en claro si quieres máxima privacidad:
        // aquí lo dejo mínimo
        app(ActivityLogger::class)->log('auth_login_failed', [
            'guard' => $event->guard,
            'reason' => 'credentials_invalid',
        ]);
    }
}