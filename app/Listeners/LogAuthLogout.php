<?php

namespace App\Listeners;

use App\Services\Activity\ActivityLogger;
use Illuminate\Auth\Events\Logout;

class LogAuthLogout
{
    public function handle(Logout $event): void
    {
        app(ActivityLogger::class)->log('auth_logout', [
            'guard' => $event->guard,
        ]);
    }
}