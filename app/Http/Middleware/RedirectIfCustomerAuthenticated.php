<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfCustomerAuthenticated
{
    public function handle($request, Closure $next, $guard = 'customer')
    {
        if (Auth::guard($guard)->check()) {
            return redirect()->route('web.home'); // si ya está logueado, a inicio
        }
        return $next($request);
    }
}
