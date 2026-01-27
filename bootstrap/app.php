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
        // Aliases de middleware personalizados
        $middleware->alias([
            'approved'          => \App\Http\Middleware\EnsureUserIsApproved::class,

            // Tus middlewares de roles/permisos
            'role'              => \App\Http\Middleware\EnsureRole::class,
            'permission'        => \App\Http\Middleware\EnsurePermission::class,
            'role_or_permission'=> \App\Http\Middleware\EnsureRoleOrPermission::class,

            // 🔐 NIP para documentación de altas
              'alta_docs_pin' => \App\Http\Middleware\AltaDocsPinMiddleware::class,
        ]);

        // (Opcional) puedes agregar globales o grupos aquí si los necesitas:
        // $middleware->web([...]);
        // $middleware->api([...]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
