<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\DriverPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RouteSupervisorController extends Controller
{
    private function canUserManage(): bool
    {
        $u = Auth::user();
        if (!$u) return false;

        // Si usas spatie roles: bloquea cliente_web
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
     * ✅ VISTA HTML (BLADE)
     * Esta ruta DEBE regresar view(), NO JSON.
     */
    public function show(RoutePlan $routePlan)
    {
        $this->canManage();

        $routePlan->load('driver');

        // 👇 Asegúrate de que exista: resources/views/supervisor/routes/show.blade.php
        return view('supervisor.routes.show', compact('routePlan'));
    }

    /**
     * ✅ ENDPOINT JSON (POLL)
     * El front (fetch) debe pegarle a .../poll
     */
    public function poll(RoutePlan $routePlan, Request $r)
    {
        $this->canManage();

        $routePlan->load('driver');

        $stops = RouteStop::where('route_plan_id', $routePlan->id)
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get(['id','name','lat','lng','sequence_index','status','done_at','eta_seconds']);

        $lastPos = null;

        if ($routePlan->driver_id) {
            $last = DriverPosition::where('user_id', $routePlan->driver_id)
                ->latest('captured_at')
                ->first();

            $lastPos = $last ? [
                'lat' => $last->lat !== null ? (float) $last->lat : null,
                'lng' => $last->lng !== null ? (float) $last->lng : null,
                'accuracy' => $last->accuracy,
                'speed' => $last->speed,
                'heading' => $last->heading,
                'captured_at' => optional($last->captured_at)->toIso8601String(),
            ] : null;
        }

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
                'last_position' => $lastPos,
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
