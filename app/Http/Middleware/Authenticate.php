<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        if ($request->expectsJson()) return null;

        // Si la ruta usa auth:customer → login de clientes
        $mws = (array) ($request->route()?->computedMiddleware ?? []);
        foreach ($mws as $mw) {
            if (stripos((string)$mw, 'auth:customer') !== false) {
                return route('customer.login');
            }
        }

        // Heurística por URL de checkout (por si alguna ruta no puso el guard explícito)
        if ($request->is('checkout*') || $request->is('carrito/checkout')) {
            return route('customer.login');
        }

        // Por defecto → login interno
        return route('login');
    }
}
