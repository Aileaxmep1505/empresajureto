<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

use App\Models\HomeBanner;
use App\Models\HomeProductSection;
use App\Models\CatalogItem;

use App\Services\FacturapiWebClient;
use App\Services\FacturaApiInternalService;

use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppInboundService;
use App\Services\WhatsApp\WhatsAppAiAssistantService;
use App\Services\OpenAI\OpenAIResponsesService;

class AppServiceProvider extends ServiceProvider
{
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

    public function boot(): void
    {
        View::composer('web.home', function ($view) {
            $homeBanners = HomeBanner::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get();

            $homeProductSections = HomeProductSection::query()
                ->with([
                    'categoryProduct',
                    'items.product.categoryProduct',
                ])
                ->visible()
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get()
                ->map(function ($section) {
                    if ($section->source_type === 'category' && $section->category_product_id) {
                        $section->home_products = CatalogItem::query()
                            ->with(['categoryProduct'])
                            ->where('status', 1)
                            ->where('is_sample', false)
                            ->where('category_product_id', $section->category_product_id)
                            ->orderByDesc('is_featured')
                            ->orderByDesc('id')
                            ->limit($section->products_limit)
                            ->get();
                    } else {
                        $section->home_products = $section->items
                            ->pluck('product')
                            ->filter()
                            ->take($section->products_limit)
                            ->values();
                    }

                    return $section;
                })
                ->filter(function ($section) {
                    return $section->home_products->count() > 0;
                })
                ->values();

            $view->with([
                'homeBanners' => $homeBanners,
                'homeProductSections' => $homeProductSections,
            ]);
        });
    }
}