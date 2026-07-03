<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Services\GoogleMapsClient;
use Illuminate\Http\Request;

class RouteGoogleController extends Controller
{
    public function __construct(
        protected GoogleMapsClient $maps
    ) {}

    public function estimate(Request $request)
    {
        $data = $request->validate([
            'points' => ['required', 'array', 'min:2'],
            'points.*.lat' => ['required', 'numeric'],
            'points.*.lng' => ['required', 'numeric'],
            'avoid_tolls' => ['nullable', 'boolean'],
            'round_trip' => ['nullable', 'boolean'],
        ]);

        $points = collect($data['points'])
            ->map(fn ($p) => [
                'lat' => (float) $p['lat'],
                'lng' => (float) $p['lng'],
            ])
            ->values();

        $origin = $points->first();
        $destination = $points->last();

        $waypoints = $points
            ->slice(1, max(0, $points->count() - 2))
            ->values()
            ->all();

        $result = $this->maps->computeRoute(
            origin: $origin,
            destination: $destination,
            waypoints: $waypoints,
            avoidTolls: (bool) ($data['avoid_tolls'] ?? false),
            roundTrip: (bool) ($data['round_trip'] ?? false),
        );

        if (!($result['ok'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }
}