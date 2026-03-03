<?php

namespace App\Http\Middleware;

use App\Services\Activity\ActivityLogger;
use Closure;
use Illuminate\Http\Request;

class LogScreenViews
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // ✅ solo GET de páginas (no ajax/json)
        if (strtoupper($request->method()) !== 'GET') return $response;
        if ($request->expectsJson()) return $response;

        // ✅ loguea solo si la ruta está declarada en config('activity.screens')
        try {
            app(ActivityLogger::class)->logScreenView($request);
        } catch (\Throwable $e) {
            // no rompas el sistema por bitácora
        }

        return $response;
    }
}