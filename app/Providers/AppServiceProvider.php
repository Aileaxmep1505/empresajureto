<?php

namespace App\Providers;

use App\Services\FacturapiWebClient;
use App\Services\FacturaApiInternalService;


use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FacturapiWebClient::class, function ($app) {
        return new FacturapiWebClient();
    });

    $this->app->singleton(FacturaApiInternalService::class, function ($app) {
        return new FacturaApiInternalService();
    });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
