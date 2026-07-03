<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\DriverPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RouteSupervisorController extends Controller
{
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

    private function presenceConfig(): array
    {
        return [
            'online_seconds' => 120,
            'warn_seconds' => 45,
            'fresh_position_minutes' => 5,
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

        if (!empty($last->captured_at)) {
            return \Illuminate\Support\Carbon::parse($last->captured_at);
        }

        if (!empty($last->created_at)) {
            return \Illuminate\Support\Carbon::parse($last->created_at);
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
                'message' => 'Sin ubicación reciente',
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
            'message' => $online ? 'Chofer en vivo' : 'Ubicación vencida',
        ];
    }

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

        if (abs($lat) < 0.000001 && abs($lng) < 0.000001) {
            return null;
        }

        $seen = $this->lastSeenAt($last);

        return [
            'id' => $last->id,

            // Para supervisor usamos GPS real, no snap.
            'lat' => $lat,
            'lng' => $lng,

            // Los dejamos informativos, pero el Blade debe pintar lat/lng.
            'snap_lat' => $last->snap_lat !== null ? (float) $last->snap_lat : null,
            'snap_lng' => $last->snap_lng !== null ? (float) $last->snap_lng : null,
            'snap_place_id' => $last->snap_place_id ?? null,

            'accuracy' => $last->accuracy,
            'speed' => $last->speed,
            'heading' => $last->heading,

            'captured_at' => optional($last->captured_at)->toIso8601String(),
            'received_at' => optional($last->received_at)->toIso8601String(),
            'seen_at' => $seen?->toIso8601String(),

            'app_state' => $last->app_state ?? null,
            'battery' => $last->battery ?? null,
            'network' => $last->network ?? null,
            'is_mocked' => $last->is_mocked ?? null,
        ];
    }

    private function latestFreshDriverPosition(?int $driverId): ?DriverPosition
    {
        if (!$driverId) {
            return null;
        }

        $cutoff = now()->subMinutes($this->presenceConfig()['fresh_position_minutes']);

        return DriverPosition::where('user_id', $driverId)
            ->where(function ($q) use ($cutoff) {
                $q->where('received_at', '>=', $cutoff)
                    ->orWhere('captured_at', '>=', $cutoff)
                    ->orWhere('created_at', '>=', $cutoff);
            })
            ->orderByRaw('COALESCE(received_at, captured_at, created_at) DESC')
            ->first();
    }

    private function latestAnyDriverPosition(?int $driverId): ?DriverPosition
    {
        if (!$driverId) {
            return null;
        }

        return DriverPosition::where('user_id', $driverId)
            ->orderByRaw('COALESCE(received_at, captured_at, created_at) DESC')
            ->first();
    }

    public function show(RoutePlan $routePlan)
    {
        $this->canManage();

        $routePlan->load('driver');

        return view('supervisor.routes.show', compact('routePlan'));
    }

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

        $freshLast = $this->latestFreshDriverPosition($routePlan->driver_id);
        $anyLast = $this->latestAnyDriverPosition($routePlan->driver_id);

        $pos = $this->buildPositionPayload($freshLast);
        $presence = $this->presencePayload($freshLast);

        $done = (int) $stops->where('status', 'done')->count();
        $total = (int) $stops->count();
        $pending = max(0, $total - $done);

        Log::info('supervisor.route.poll', [
            'route_plan_id' => $routePlan->id,
            'driver_id' => $routePlan->driver_id,
            'fresh_position_id' => $freshLast?->id,
            'fresh_seen_at' => $this->lastSeenAt($freshLast)?->toDateTimeString(),
            'any_position_id' => $anyLast?->id,
            'any_seen_at' => $this->lastSeenAt($anyLast)?->toDateTimeString(),
            'has_live_position' => $pos !== null,
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
                'presence' => $presence,
                'last_position' => $pos,

                // Esto es solo para depurar en Network.
                'debug_last_saved' => [
                    'id' => $anyLast?->id,
                    'seen_at' => $this->lastSeenAt($anyLast)?->toIso8601String(),
                    'lat' => $anyLast?->lat !== null ? (float) $anyLast->lat : null,
                    'lng' => $anyLast?->lng !== null ? (float) $anyLast->lng : null,
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