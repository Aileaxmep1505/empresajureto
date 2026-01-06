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

    public function show(RoutePlan $routePlan)
    {
        $this->canManage();

        $routePlan->load('driver');

        return response()->json([
            'plan_id' => $routePlan->id,
            'name'    => $routePlan->name,
            'status'  => $routePlan->status,
            'driver'  => [
                'id'   => $routePlan->driver_id,
                'name' => $routePlan->driver?->name,
            ],
        ]);
    }

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
                'lat' => (float)$last->lat,
                'lng' => (float)$last->lng,
                'accuracy' => $last->accuracy,
                'speed' => $last->speed,
                'heading' => $last->heading,
                'captured_at' => optional($last->captured_at)->toIso8601String(),
            ] : null;
        }

        $done = $stops->where('status','done')->count();
        $pending = $stops->count() - $done;

        return response()->json([
            'plan' => [
                'id' => $routePlan->id,
                'name' => $routePlan->name,
                'status' => $routePlan->status,
            ],
            'driver' => [
                'id' => $routePlan->driver_id,
                'name' => $routePlan->driver?->name,
                'last_position' => $lastPos,
            ],
            'stops' => $stops,
            'kpis' => [
                'total' => $stops->count(),
                'done' => $done,
                'pending' => $pending,
            ],
            'server_time' => now()->toIso8601String(),
        ], 200);
    }
}
