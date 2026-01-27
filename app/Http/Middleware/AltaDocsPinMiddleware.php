<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AltaDocsPinMiddleware
{
    /**
     * Verifica si la sesión ya pasó el NIP para ver documentación confidencial.
     */
    public function handle(Request $request, Closure $next)
    {
        // Si ya tiene el flag en sesión, pasa normal
        if ($request->session()->get('alta_docs_unlocked') === true) {
            return $next($request);
        }

        // Log para debug (opcional)
        Log::info('AltaDocsPinMiddleware: acceso bloqueado, redirigiendo a PIN', [
            'user_id' => optional($request->user())->id,
            'ip'      => $request->ip(),
            'url'     => $request->fullUrl(),
        ]);

        // Si es petición JSON (por si algún día lo usas vía API)
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'NIP requerido para acceder a documentación confidencial.',
            ], 403);
        }

        // Redirigir al formulario de NIP
        return redirect()
            ->route('secure.alta-docs.pin.show')
            ->with('ok', 'Ingresa el NIP para ver la documentación de altas.');
    }
}
