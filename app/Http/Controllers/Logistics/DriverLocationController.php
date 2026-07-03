<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DriverPosition;
use App\Models\RoutePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DriverLocationController extends Controller
{
    /**
     * Guarda la ubicación enviada por la vista del chofer.
     *
     * Punto clave:
     * - Si viene route_plan_id y la ruta tiene driver_id, guarda la posición con ese driver_id.
     * - Así el supervisor puede encontrarla con route_plans.driver_id.
     */
    public function save(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $data = $request->validate([
            'route_plan_id' => ['nullable', 'integer', 'exists:route_plans,id'],
            'route_driver_id' => ['nullable', 'integer'],
            'auth_user_id' => ['nullable', 'integer'],

            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'speed' => ['nullable', 'numeric'],
            'heading' => ['nullable', 'numeric'],
            'captured_at' => ['nullable', 'date'],

            'app_state' => ['nullable', 'string', 'max:80'],
            'battery' => ['nullable', 'numeric'],
            'network' => ['nullable', 'string', 'max:80'],
            'is_mocked' => ['nullable', 'boolean'],
        ]);

        $routePlan = null;
        $targetUserId = (int) $authUser->id;

        if (!empty($data['route_plan_id'])) {
            $routePlan = RoutePlan::query()->find($data['route_plan_id']);

            if ($routePlan && $routePlan->driver_id) {
                $targetUserId = (int) $routePlan->driver_id;
            }
        }

        try {
            $payload = [
                'user_id' => $targetUserId,
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lng'],
            ];

            $optionalColumns = [
                'accuracy' => $data['accuracy'] ?? null,
                'speed' => $data['speed'] ?? null,
                'heading' => $data['heading'] ?? null,
                'captured_at' => !empty($data['captured_at'])
                    ? Carbon::parse($data['captured_at'])
                    : now(),
                'received_at' => now(),
                'app_state' => $data['app_state'] ?? null,
                'battery' => $data['battery'] ?? null,
                'network' => $data['network'] ?? null,
                'is_mocked' => $data['is_mocked'] ?? null,
            ];

            foreach ($optionalColumns as $column => $value) {
                if (Schema::hasColumn('driver_positions', $column)) {
                    $payload[$column] = $value;
                }
            }

            $position = new DriverPosition();
            $position->forceFill($payload);
            $position->save();

            Log::info('driver.location.saved', [
                'position_id' => $position->id,
                'auth_user_id' => $authUser->id,
                'saved_user_id' => $targetUserId,
                'route_plan_id' => $routePlan?->id,
                'route_driver_id' => $routePlan?->driver_id,
                'request_route_driver_id' => $data['route_driver_id'] ?? null,
                'lat' => $position->lat,
                'lng' => $position->lng,
                'captured_at' => optional($position->captured_at)->toDateTimeString(),
                'received_at' => optional($position->received_at)->toDateTimeString(),
            ]);

            return response()->json([
                'ok' => true,
                'id' => $position->id,
                'auth_user_id' => (int) $authUser->id,
                'saved_user_id' => (int) $position->user_id,
                'route_plan_id' => $routePlan?->id,
                'route_driver_id' => $routePlan?->driver_id,
                'lat' => $position->lat !== null ? (float) $position->lat : null,
                'lng' => $position->lng !== null ? (float) $position->lng : null,
                'accuracy' => $position->accuracy ?? null,
                'speed' => $position->speed ?? null,
                'heading' => $position->heading ?? null,
                'captured_at' => optional($position->captured_at)->toIso8601String(),
                'received_at' => optional($position->received_at)->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            Log::error('driver.location.save.failed', [
                'auth_user_id' => $authUser->id,
                'target_user_id' => $targetUserId,
                'route_plan_id' => $data['route_plan_id'] ?? null,
                'payload' => $data,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No se pudo guardar la ubicación.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Devuelve la última ubicación para precargar la vista del chofer.
     * Si viene route_plan_id, busca al driver_id de esa ruta.
     */
    public function last(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $routePlanId = $request->query('route_plan_id');
        $routePlan = null;
        $targetUserId = (int) $authUser->id;

        if ($routePlanId) {
            $routePlan = RoutePlan::query()->find($routePlanId);

            if ($routePlan && $routePlan->driver_id) {
                $targetUserId = (int) $routePlan->driver_id;
            }
        }

        $last = DriverPosition::where('user_id', $targetUserId)
            ->orderByRaw($this->latestOrderExpression())
            ->first();

        if (!$last) {
            return response()->json([
                'ok' => true,
                'found' => false,
                'user_id' => $targetUserId,
                'route_plan_id' => $routePlan?->id,
                'route_driver_id' => $routePlan?->driver_id,
                'lat' => null,
                'lng' => null,
            ]);
        }

        return response()->json([
            'ok' => true,
            'found' => true,
            'id' => $last->id,
            'user_id' => (int) $last->user_id,
            'route_plan_id' => $routePlan?->id,
            'route_driver_id' => $routePlan?->driver_id,
            'lat' => $last->lat !== null ? (float) $last->lat : null,
            'lng' => $last->lng !== null ? (float) $last->lng : null,
            'accuracy' => $last->accuracy ?? null,
            'speed' => $last->speed ?? null,
            'heading' => $last->heading ?? null,
            'captured_at' => optional($last->captured_at)->toIso8601String(),
            'received_at' => optional($last->received_at)->toIso8601String(),
            'created_at' => optional($last->created_at)->toIso8601String(),
        ]);
    }

    private function latestOrderExpression(): string
    {
        $columns = [];

        foreach (['received_at', 'captured_at', 'created_at'] as $column) {
            if (Schema::hasColumn('driver_positions', $column)) {
                $columns[] = $column;
            }
        }

        if (!$columns) {
            return 'id DESC';
        }

        return 'COALESCE(' . implode(', ', $columns) . ') DESC';
    }
}
