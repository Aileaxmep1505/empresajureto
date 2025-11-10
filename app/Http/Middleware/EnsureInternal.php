<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInternal
{
    public function handle(Request $request, Closure $next)
    {
        $u = $request->user();
        if (!$u) abort(401);

        // Ajusta a tu esquema (ejemplo simple):
        if (isset($u->role) && $u->role === 'cliente_web') {
            abort(403);
        }

        return $next($request);
    }
}
