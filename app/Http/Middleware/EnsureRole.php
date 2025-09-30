<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || empty($roles)) {
            abort(403, 'Acceso denegado.');
        }

        // HasRoles trait (Spatie) está en tu User, así que esto funciona:
        if (!$user->hasAnyRole($roles)) {
            abort(403, 'No tienes el rol requerido.');
        }

        return $next($request);
    }
}
