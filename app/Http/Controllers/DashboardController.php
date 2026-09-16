<?php

namespace App\Http\Controllers;

use App\Services\DashboardAiService;
use App\Support\DashboardWidgets;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Tablero de inicio.
 *
 * Cada quien elige qué tarjetas ve, en qué orden y de qué tamaño. La
 * preferencia vive en users.dashboard_widgets. El menú completo de módulos
 * (la pantalla de inicio anterior) sigue disponible en /panel/menu.
 */
class DashboardController extends Controller
{
    public function index(Request $request, DashboardAiService $dashboardAiService): View
    {
        $user = $request->user();
        $activas = DashboardWidgets::paraUsuario($user);

        // Solo se calculan los datos de lo que realmente se va a pintar.
        $datos = [];

        foreach ($activas as $widget) {
            // El tamaño va incluido: una tarjeta grande trae además su
            // desglose, una chica se queda con el dato principal.
            $datos[$widget['id']] = DashboardWidgets::datos(
                $widget['id'], $user, $widget['w'], $widget['h']
            );
        }

        $catalogo = DashboardWidgets::catalogoPara($user);

        return view('dashboard.index', [
            'activas' => $activas,
            'datos' => $datos,
            'catalogo' => $catalogo,
            // Los límites de cada tarjeta viajan a la vista para que el
            // arrastre no la deje más chica ni más grande de lo razonable.
            'limites' => collect($catalogo)
                ->map(fn ($def, $id) => DashboardWidgets::definicion($id))
                ->all(),
            'inspirationalPhrase' => $this->fraseDelDia($dashboardAiService, $user->name ?? 'Usuario'),
        ]);
    }

    /** Menú completo de módulos: la pantalla de inicio de antes. */
    public function menu(Request $request, DashboardAiService $dashboardAiService): View
    {
        $inspirationalPhrase = $this->fraseDelDia($dashboardAiService, $request->user()->name ?? 'Usuario');

        return view('dashboard', compact('inspirationalPhrase'));
    }

    /** Guarda el acomodo elegido en el panel de personalizar. */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'widgets' => ['nullable', 'array'],
            'widgets.*.id' => ['required', 'string'],
            'widgets.*.w' => ['required', 'integer'],
            'widgets.*.h' => ['required', 'integer'],
        ]);

        $limpio = DashboardWidgets::normalizar($request->input('widgets', []), $request->user());

        $request->user()->update(['dashboard_widgets' => $limpio]);

        return redirect()->route('dashboard')->with('status', 'Tablero actualizado.');
    }

    /** Vuelve al acomodo de fábrica. */
    public function reset(Request $request): RedirectResponse
    {
        $request->user()->update(['dashboard_widgets' => null]);

        return redirect()->route('dashboard')->with('status', 'Tablero restaurado al acomodo original.');
    }

    /**
     * Frase del día generada por IA, una sola vez al día para todos.
     * Si la IA falla el tablero se pinta igual, sin frase.
     */
    private function fraseDelDia(DashboardAiService $dashboardAiService, string $userName): ?string
    {
        $tz = config('app.timezone', 'America/Mexico_City');
        $cacheKey = 'dashboard_inspirational_phrase_' . now()->timezone($tz)->format('Y-m-d');
        $expiresAt = now()->timezone($tz)->endOfDay();

        try {
            return Cache::remember($cacheKey, $expiresAt, function () use ($dashboardAiService, $userName) {
                return $dashboardAiService->getDailyInspirationalPhrase($userName);
            });
        } catch (\Throwable $e) {
            Log::error('Dashboard IA: error en controller', ['message' => $e->getMessage()]);

            return null;
        }
    }
}
