<?php

namespace App\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use App\Listeners\NotifyAdminsOfLoginEvents;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // dejamos vacío y adjuntamos manualmente en boot()
    ];

    public function boot(): void
    {
        parent::boot();

        $listener = app(NotifyAdminsOfLoginEvents::class);
        \Event::listen(Login::class,  [$listener, 'handleLogin']);
        \Event::listen(Failed::class, [$listener, 'handleFailed']);
        \Event::listen(Lockout::class,[$listener, 'handleLockout']);
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
