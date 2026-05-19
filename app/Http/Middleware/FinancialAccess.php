<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinancialAccess
{
    /**
     * IDs de usuarios con acceso al módulo de estados financieros.
     */
    private const ALLOWED_IDS = [2, 12, 18];

    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !in_array(auth()->id(), self::ALLOWED_IDS)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}