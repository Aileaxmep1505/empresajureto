<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        // ✅ Mantengo TODO lo que ya tienes y solo agrego lo necesario (sin quitar nada)

        // (Opcional) Si quieres forzar HTTPS en producción, descomenta:
        // if (app()->environment('production')) {
        //     \Illuminate\Support\Facades\URL::forceScheme('https');
        // }

        $this->routes(function () {
            // Rutas API con prefijo /api y middleware api
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Rutas web
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}