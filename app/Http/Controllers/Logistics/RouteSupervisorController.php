<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\DriverPosition;
use App\Services\OsrmClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RouteSupervisorController extends Controller
{
    public function __construct(
        protected OsrmClient $osrm
    ) {}

    private function canUserManage(): bool
    {
        $u = Auth::user();
        if (!$u) return false;

        if (class_exists(\Spatie\Permission\Models\Role::class) && method_exists($u, 'hasRole')) {
            return !$u->hasRole('cliente_web');
        }
        return true;
    }

    private function canManage(): void
    {
        abort_unless($this->canUserManage(), 403);
    }

    /** Config presencia */
    private function presenceConfig(): array
    {
        return [
            'online_seconds' => 120,   // si no manda ubicación en 2 min => offline
            'warn_seconds'   => 45,    // a los 45s ya lo marcas “tarde”
        ];
    }

    private function lastSeenAt(?DriverPosition $last): ?\Illuminate\Support\Carbon
    {
        if (!$last) return null;

        // Preferimos tiempo del servidor para “last seen”
        if (!empty($last->received_at)) return \Illuminate\Support\Carbon::parse($last->received_at);
        if (!empty($last->created_at))  return \Illuminate\Support\Carbon::parse($last->created_at);
        if (!empty($last->captured_at)) return \Illuminate\Support\Carbon::parse($last->captured_at);

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

    /** Snap-to-road: usa snaps guardados si existen; si no, intenta OSRM nearest */
    private function buildPositionPayload(?DriverPosition $last): ?array
    {
        if (!$last) return null;

        $lat = $last->lat !== null ? (float)$last->lat : null;
        $lng = $last->lng !== null ? (float)$last->lng : null;

        if ($lat === null || $lng === null) return null;

        $snapLat = property_exists($last, 'snap_lat') ? ($last->snap_lat !== null ? (float)$last->snap_lat : null) : null;
        $snapLng = property_exists($last, 'snap_lng') ? ($last->snap_lng !== null ? (float)$last->snap_lng : null) : null;

        // Si no hay snap guardado, intentamos nearest (cuidado: esto corre en cada poll si no guardas snap)
        if (($snapLat === null || $snapLng === null)) {
            try {
                $near = $this->osrm->nearest(['lat'=>$lat,'lng'=>$lng], ['number' => 1]);
                if (($near['code'] ?? '') === 'Ok' && !empty($near['waypoints'][0]['location'])) {
                    // OSRM location viene como [lng, lat]
                    $loc = $near['waypoints'][0]['location'];
                    $snapLng = isset($loc[0]) ? (float)$loc[0] : null;
                    $snapLat = isset($loc[1]) ? (float)$loc[1] : null;
                }
            } catch (\Throwable $e) {
                // silencioso: no rompas el poll
            }
        }

        $seen = $this->lastSeenAt($last);

        return [
            'lat' => $lat,
            'lng' => $lng,

            // opcional: punto “pegado a la calle”
            'snap_lat' => $snapLat,
            'snap_lng' => $snapLng,

            'accuracy' => $last->accuracy,
            'speed' => $last->speed,
            'heading' => $last->heading,
            'captured_at' => optional($last->captured_at)->toIso8601String(),

            // mejor para “último visto”
            'received_at' => $seen?->toIso8601String(),

            // opcionales
            'app_state' => $last->app_state ?? null,
            'battery'   => $last->battery ?? null,
            'network'   => $last->network ?? null,
            'is_mocked' => $last->is_mocked ?? null,
        ];
    }

    /** VISTA HTML */
    public function show(RoutePlan $routePlan)
    {
        $this->canManage();
        $routePlan->load('driver');
        return view('supervisor.routes.show', compact('routePlan'));
    }

    /** JSON Poll */
    public function poll(RoutePlan $routePlan, Request $r)
    {
        $this->canManage();

        $routePlan->load('driver');

        $stops = RouteStop::where('route_plan_id', $routePlan->id)
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get(['id','name','lat','lng','sequence_index','status','done_at','eta_seconds']);

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