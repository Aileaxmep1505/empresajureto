<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\DriverPosition;
use App\Services\GoogleMapsClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RouteSupervisorController extends Controller
{
    public function __construct(
        protected GoogleMapsClient $maps
    ) {}

    private function canUserManage(): bool
    {
        $u = Auth::user();

        if (!$u) {
            return false;
        }

        if (class_exists(\Spatie\Permission\Models\Role::class) && method_exists($u, 'hasRole')) {
            return !$u->hasRole('cliente_web');
        }

        return true;
    }

    private function canManage(): void
    {
        abort_unless($this->canUserManage(), 403);
    }

    /**
     * Config presencia.
     */
    private function presenceConfig(): array
    {
        return [
            'online_seconds' => 120,
            'warn_seconds'   => 45,
        ];
    }

    private function lastSeenAt(?DriverPosition $last): ?\Illuminate\Support\Carbon
    {
        if (!$last) {
            return null;
        }

        if (!empty($last->received_at)) {
            return \Illuminate\Support\Carbon::parse($last->received_at);
        }

        if (!empty($last->created_at)) {
            return \Illuminate\Support\Carbon::parse($last->created_at);
        }

        if (!empty($last->captured_at)) {
            return \Illuminate\Support\Carbon::parse($last->captured_at);
        }

        return null;
    }

    private function presencePayload(?DriverPosition $last): array
    {
        $cfg = $this->presenceConfig();
        $seen = $this->lastSeenAt($last);

        if (!$seen) {
            return [
                'state' => 'offline',
                'last_seen_at' => null,
                'stale_seconds' => null,
                'warn' => false,
                'disconnected_at' => null,
            ];
        }

        $age = now()->diffInSeconds($seen);
        $online = $age <= $cfg['online_seconds'];

        return [
            'state' => $online ? 'online' : 'offline',
            'last_seen_at' => $seen->toIso8601String(),
            'stale_seconds' => $age,
            'warn' => $age >= $cfg['warn_seconds'],
            'disconnected_at' => $online ? null : $seen->toIso8601String(),
        ];
    }

    private function hasModelAttribute(DriverPosition $position, string $attribute): bool
    {
        return array_key_exists($attribute, $position->getAttributes());
    }

    /**
     * Snap-to-road con Google Roads API.
     *
     * Si ya existen snap_lat y snap_lng guardados, los usa.
     * Si no existen, intenta Google Roads API.
     * Si las columnas existen en la tabla, guarda el snap para no consumir API en cada poll.
     */
    private function buildPositionPayload(?DriverPosition $last): ?array
    {
        if (!$last) {
            return null;
        }

        $lat = $last->lat !== null ? (float) $last->lat : null;
        $lng = $last->lng !== null ? (float) $last->lng : null;

        if ($lat === null || $lng === null) {
            return null;
        }

        $hasSnapLat = $this->hasModelAttribute($last, 'snap_lat');
        $hasSnapLng = $this->hasModelAttribute($last, 'snap_lng');

        $snapLat = $hasSnapLat && $last->snap_lat !== null ? (float) $last->snap_lat : null;
        $snapLng = $hasSnapLng && $last->snap_lng !== null ? (float) $last->snap_lng : null;

        if ($snapLat === null || $snapLng === null) {
            try {
                [$googleSnapLat, $googleSnapLng, $placeId] = $this->maps->nearestRoad($lat, $lng);

                if ($googleSnapLat !== null && $googleSnapLng !== null) {
                    $snapLat = $googleSnapLat;
                    $snapLng = $googleSnapLng;

                    /**
                     * Guarda el snap si tus columnas existen.
                     * Esto evita llamar Roads API cada 5 segundos para la misma posición.
                     */
                    if ($hasSnapLat && $hasSnapLng) {
                        $updates = [
                            'snap_lat' => $snapLat,
                            'snap_lng' => $snapLng,
                        ];

                        if ($this->hasModelAttribute($last, 'snap_place_id')) {
                            $updates['snap_place_id'] = $placeId;
                        }

                        $last->forceFill($updates)->saveQuietly();
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Google Roads nearestRoad falló en supervisor poll', [
                    'driver_position_id' => $last->id ?? null,
                    'lat' => $lat,
                    'lng' => $lng,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $seen = $this->lastSeenAt($last);

        return [
            'lat' => $lat,
            'lng' => $lng,

            'snap_lat' => $snapLat,
            'snap_lng' => $snapLng,

            'accuracy' => $last->accuracy,
            'speed' => $last->speed,
            'heading' => $last->heading,
            'captured_at' => optional($last->captured_at)->toIso8601String(),
            'received_at' => $seen?->toIso8601String(),

            'app_state' => $last->app_state ?? null,
            'battery'   => $last->battery ?? null,
            'network'   => $last->network ?? null,
            'is_mocked' => $last->is_mocked ?? null,
        ];
    }

    /**
     * Vista HTML.
     */
    public function show(RoutePlan $routePlan)
    {
        $this->canManage();

        $routePlan->load('driver');

        return view('supervisor.routes.show', compact('routePlan'));
    }

    /**
     * JSON Poll.
     */
    public function poll(RoutePlan $routePlan, Request $r)
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

        $last = null;

        if ($routePlan->driver_id) {
            $last = DriverPosition::where('user_id', $routePlan->driver_id)
                ->latest('captured_at')
                ->first();
        }

        $pos = $this->buildPositionPayload($last);
        $presence = $this->presencePayload($last);

        $done = (int) $stops->where('status', 'done')->count();
        $total = (int) $stops->count();
        $pending = max(0, $total - $done);

        return response()->json([
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
                'presence' => $presence,
                'last_position' => $pos,
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