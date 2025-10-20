<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApprovedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user) return redirect()->route('login');

        // Si definiste isApproved() en el modelo, úsalo
        if (!method_exists($user, 'isApproved') || !$user->isApproved()) {
            return redirect()->route('verification.notice')
                ->with('status', 'Tu cuenta está pendiente de aprobación por un administrador.');
        }

        return $next($request);
    }
}
