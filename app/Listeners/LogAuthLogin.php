<?php

namespace App\Listeners;

use App\Services\Activity\ActivityLogger;
use Illuminate\Auth\Events\Login;

class LogAuthLogin
{
    public function handle(Login $event): void
    {
        app(ActivityLogger::class)->log('auth_login', [
            'guard' => $event->guard,
        ]);
    }
}