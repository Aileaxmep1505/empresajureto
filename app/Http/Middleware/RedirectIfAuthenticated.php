<?php
// app/Http/Middleware/RedirectIfAuthenticated.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                if (method_exists($user, 'hasRole') && $user->hasRole('cliente_web')) {
                    return redirect()->route('customer.welcome');
                }
                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
