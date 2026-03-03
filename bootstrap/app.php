<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AltaDocsPinMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',      // ✅ HABILITADO
        commands: __DIR__.'/../routes/console.php',
        // health: '/up', // opcional
    )
    ->withMiddleware(function (Middleware $middleware) {

        /*
        |--------------------------------------------------------------------------
        | ✅ SOLO "pantallas" (screen_view)
        |--------------------------------------------------------------------------
        | Esto NO registra cada request; solo registra si la ruta está declarada
        | en config('activity.screens').
        |
        | Evita el spam tipo:
        | GET /notifications/feed · route: notifications.feed...
        */
        $middleware->web(append: [
            \App\Http\Middleware\LogScreenViews::class,
        ]);

        // Aliases de middleware personalizados
        $middleware->alias([
            'approved'           => \App\Http\Middleware\EnsureUserIsApproved::class,

            // Roles/permisos
            'role'               => \App\Http\Middleware\EnsureRole::class,
            'permission'         => \App\Http\Middleware\EnsurePermission::class,
            'role_or_permission' => \App\Http\Middleware\EnsureRoleOrPermission::class,

            // 🔐 NIP para documentación de altas
            'alta_docs_pin'      => \App\Http\Middleware\AltaDocsPinMiddleware::class,
        ]);

        // (Opcional) api group si quieres
        // $middleware->api([...]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();