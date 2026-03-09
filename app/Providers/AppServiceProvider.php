<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Services\FacturapiWebClient;
use App\Services\FacturaApiInternalService;

use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppInboundService;
use App\Services\WhatsApp\WhatsAppAiAssistantService;
use App\Services\OpenAI\OpenAIResponsesService;

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

        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });

        $this->app->singleton(WhatsAppInboundService::class, function ($app) {
            return new WhatsAppInboundService();
        });

        $this->app->singleton(WhatsAppAiAssistantService::class, function ($app) {
            return new WhatsAppAiAssistantService();
        });

        $this->app->singleton(OpenAIResponsesService::class, function ($app) {
            return new OpenAIResponsesService();
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