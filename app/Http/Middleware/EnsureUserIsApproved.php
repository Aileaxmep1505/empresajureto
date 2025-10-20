<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    /**
     * Solo exige aprobación para el rol "user".
     * Los roles "cliente_web" (y opcionalmente admin/empleado) no requieren aprobación.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Si NO usas Spatie en algún contexto, evita errores
        $hasRole = method_exists($user, 'hasRole');

        // ---- Roles que NO requieren aprobación ----
        // cliente_web (compradores web) siempre pasan
        if ($hasRole && $user->hasRole('cliente_web')) {
            return $next($request);
        }

        // (Opcional) también deja pasar admin/empleado
        if ($hasRole && $user->hasAnyRole(['admin', 'empleado'])) {
            return $next($request);
        }

        // ---- Roles que SÍ requieren aprobación ----
        // Solo aplicamos la verificación a "user"
        $requiresApproval = $hasRole ? $user->hasRole('user') : true;

        if ($requiresApproval && ($user->status ?? null) !== 'approved') {
            // Mantén tu comportamiento actual: cerrar sesión y mandar a login
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu cuenta está pendiente de aprobación.']);
        }

        return $next($request);
    }
}
