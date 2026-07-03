<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DriverPosition;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RouteSupervisorController extends Controller
{
    /**
     * Valida que el usuario pueda ver el supervisor.
     * Si usas Spatie, bloquea a cliente_web.
     */
    private function canUserManage(): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        if (class_exists(\Spatie\Permission\Models\Role::class) && method_exists($user, 'hasRole')) {
            return !$user->hasRole('cliente_web');
        }

        return true;
    }

    private function canManage(): void
    {
        abort_unless($this->canUserManage(), 403);
    }

    /**
     * Configuración de presencia.
     *
     * online_seconds:
     *   Cuánto tiempo se considera "en vivo".
     *
     * warn_seconds:
     *   Cuándo mostrar advertencia de señal tardía.
     *
     * fresh_position_minutes:
     *   Si la última ubicación tiene más de estos minutos,
     *   NO se manda como last_position para evitar pintar puntos viejos.
     */
    private function presenceConfig(): array
    {
        return [
            'online_seconds' => 120,
            'warn_seconds' => 45,
            'fresh_position_minutes' => 5,
        ];
    }

    /**
     * Obtiene la fecha real más confiable de una posición.
     */
    private function lastSeenAt(?DriverPosition $position): ?Carbon
    {
        if (!$position) {
            return null;
        }

        if (!empty($position->received_at)) {
            return Carbon::parse($position->received_at);
        }

        if (!empty($position->captured_at)) {
            return Carbon::parse($position->captured_at);
        }

        if (!empty($position->created_at)) {
            return Carbon::parse($position->created_at);
        }

        return null;
    }

    /**
     * Estado de presencia para el panel del supervisor.
     */
    private function presencePayload(?DriverPosition $position): array
    {
        $config = $this->presenceConfig();
        $seenAt = $this->lastSeenAt($position);

        if (!$seenAt) {
            return [
                'state' => 'offline',
                'last_seen_at' => null,
                'stale_seconds' => null,
                'warn' => false,
                'disconnected_at' => null,
                'message' => 'Sin ubicación reciente',
            ];
        }

        $age = now()->diffInSeconds($seenAt);
        $online = $age <= $config['online_seconds'];

        return [
            'state' => $online ? 'online' : 'offline',
            'last_seen_at' => $seenAt->toIso8601String(),
            'stale_seconds' => $age,
            'warn' => $age >= $config['warn_seconds'],
            'disconnected_at' => $online ? null : $seenAt->toIso8601String(),
            'message' => $online ? 'Chofer en vivo' : 'Ubicación vencida',
        ];
    }

    /**
     * Convierte una posición de DB a JSON para el supervisor.
     *
     * IMPORTANTE:
     * Para el supervisor se manda lat/lng reales del GPS.
     * snap_lat/snap_lng se dejan como datos informativos, pero el Blade
     * debe pintar lat/lng para no mover al chofer a una calle incorrecta.
     */
    private function buildPositionPayload(?DriverPosition $position): ?array
    {
        if (!$position) {
            return null;
        }

        $lat = $position->lat !== null ? (float) $position->lat : null;
        $lng = $position->lng !== null ? (float) $position->lng : null;

        if ($lat === null || $lng === null) {
            return null;
        }

        if (abs($lat) < 0.000001 && abs($lng) < 0.000001) {
            return null;
        }

        if (abs($lat) > 90 || abs($lng) > 180) {
            return null;
        }

        $seenAt = $this->lastSeenAt($position);

        return [
            'id' => $position->id,
            'user_id' => $position->user_id,

            'lat' => $lat,
            'lng' => $lng,

            'snap_lat' => $position->snap_lat !== null ? (float) $position->snap_lat : null,
            'snap_lng' => $position->snap_lng !== null ? (float) $position->snap_lng : null,
            'snap_place_id' => $position->snap_place_id ?? null,

            'accuracy' => $position->accuracy !== null ? (float) $position->accuracy : null,
            'speed' => $position->speed !== null ? (float) $position->speed : null,
            'heading' => $position->heading !== null ? (float) $position->heading : null,

            'captured_at' => optional($position->captured_at)->toIso8601String(),
            'received_at' => optional($position->received_at)->toIso8601String(),
            'seen_at' => $seenAt?->toIso8601String(),

            'app_state' => $position->app_state ?? null,
            'battery' => $position->battery !== null ? (float) $position->battery : null,
            'network' => $position->network ?? null,
            'is_mocked' => $position->is_mocked,
        ];
    }

    /**
     * Última ubicación reciente del chofer.
     *
     * Esta es la que SÍ se manda como last_position al supervisor.
     */
    private function latestFreshDriverPosition(?int $driverId): ?DriverPosition
    {
        if (!$driverId) {
            return null;
        }

        $cutoff = now()->subMinutes($this->presenceConfig()['fresh_position_minutes']);

        return DriverPosition::where('user_id', $driverId)
            ->where(function ($query) use ($cutoff) {
                $query->where('received_at', '>=', $cutoff)
                    ->orWhere('captured_at', '>=', $cutoff)
                    ->orWhere('created_at', '>=', $cutoff);
            })
            ->orderByRaw('COALESCE(received_at, captured_at, created_at) DESC')
            ->first();
    }

    /**
     * Última ubicación guardada aunque sea vieja.
     *
     * Solo se usa para debug en Network/logs, NO para pintar el marcador.
     */
    private function latestAnyDriverPosition(?int $driverId): ?DriverPosition
    {
        if (!$driverId) {
            return null;
        }

        return DriverPosition::where('user_id', $driverId)
            ->orderByRaw('COALESCE(received_at, captured_at, created_at) DESC')
            ->first();
    }

    /**
     * Vista HTML del supervisor.
     */
    public function show(RoutePlan $routePlan)
    {
        $this->canManage();

        $routePlan->load('driver');

        return view('supervisor.routes.show', compact('routePlan'));
    }

    /**
     * Endpoint JSON que consume el Blade del supervisor.
     */
    public function poll(RoutePlan $routePlan, Request $request)
    {
        $this->canManage();

        $routePlan->load('driver');

        $stops = RouteStop::where('route_plan_id', $routePlan->id)
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get([
                'id',
                'name',
                'lat',
                'lng',
                'sequence_index',
                'status',
                'done_at',
                'eta_seconds',
            ]);

        $freshLast = $this->latestFreshDriverPosition($routePlan->driver_id);
        $anyLast = $this->latestAnyDriverPosition($routePlan->driver_id);

        $positionPayload = $this->buildPositionPayload($freshLast);
        $presencePayload = $this->presencePayload($freshLast);

        $done = (int) $stops->where('status', 'done')->count();
        $total = (int) $stops->count();
        $pending = max(0, $total - $done);

        Log::info('supervisor.route.poll', [
            'route_plan_id' => $routePlan->id,
            'route_name' => $routePlan->name,
            'driver_id' => $routePlan->driver_id,
            'driver_name' => $routePlan->driver?->name,

            'fresh_position_id' => $freshLast?->id,
            'fresh_position_user_id' => $freshLast?->user_id,
            'fresh_seen_at' => $this->lastSeenAt($freshLast)?->toDateTimeString(),

            'any_position_id' => $anyLast?->id,
            'any_position_user_id' => $anyLast?->user_id,
            'any_seen_at' => $this->lastSeenAt($anyLast)?->toDateTimeString(),

            'has_live_position' => $positionPayload !== null,
            'server_time' => now()->toDateTimeString(),
        ]);

        return response()->json([
            'ok' => true,

            'plan' => [
                'id' => $routePlan->id,
                'name' => $routePlan->name,
                'status' => $routePlan->status,
                'started_at' => optional($routePlan->started_at)->toIso8601String(),
                'sequence_locked' => (bool) ($routePlan->sequence_locked ?? false),
                'start' => [
                    'lat' => $routePlan->start_lat !== null ? (float) $routePlan->start_lat : null,
                    'lng' => $routePlan->start_lng !== null ? (float) $routePlan->start_lng : null,
                ],
            ],

            'driver' => [
                'id' => $routePlan->driver_id,
                'name' => $routePlan->driver?->name,
                'presence' => $presencePayload,

                /**
                 * Esta es la única ubicación que debe pintar el mapa.
                 * Si viene null, el Blade debe quitar el marcador y mostrar
                 * "Sin ubicación reciente".
                 */
                'last_position' => $positionPayload,

                /**
                 * Debug para Network:
                 * Te dice cuál fue la última posición guardada aunque sea vieja.
                 */
                'debug_last_saved' => [
                    'id' => $anyLast?->id,
                    'user_id' => $anyLast?->user_id,
                    'seen_at' => $this->lastSeenAt($anyLast)?->toIso8601String(),
                    'lat' => $anyLast?->lat !== null ? (float) $anyLast->lat : null,
                    'lng' => $anyLast?->lng !== null ? (float) $anyLast->lng : null,
                    'created_at' => optional($anyLast?->created_at)->toIso8601String(),
                    'captured_at' => optional($anyLast?->captured_at)->toIso8601String(),
                    'received_at' => optional($anyLast?->received_at)->toIso8601String(),
                ],
            ],

            'stops' => $stops,

            'kpis' => [
                'total' => $total,
                'done' => $done,
                'pending' => $pending,
                'done_pct' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
            ],

            'server_time' => now()->toIso8601String(),
        ], 200);
    }
}