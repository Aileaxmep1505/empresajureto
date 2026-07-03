<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\DriverPosition;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\User;
use App\Models\WmsShipment;
use App\Services\RouteAiAdvisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RoutePlanController extends Controller
{
    public function __construct(
        protected RouteAiAdvisor $advisor,
    ) {
        // $this->middleware('auth');
    }

    /* =========================================================================
     | Permisos
     * ========================================================================= */

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

    private function canDrive(RoutePlan $plan): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        if (class_exists(\Spatie\Permission\Models\Role::class) && method_exists($user, 'hasRole')) {
            $ok = ((int) $user->id === (int) $plan->driver_id) || $this->canUserManage();
            abort_unless($ok, 403);
            return;
        }

        abort_unless((int) $user->id === (int) $plan->driver_id, 403);
    }

    /* =========================================================================
     | Helpers coordenadas
     * ========================================================================= */

    private function normalizeCoord($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if ($value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        if (!is_finite($float)) {
            return null;
        }

        if (abs($float) < 0.0000001) {
            return null;
        }

        return $float;
    }

    private function validLatLng(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }

        if (abs($lat) > 90 || abs($lng) > 180) {
            return false;
        }

        if (abs($lat) < 0.0000001 && abs($lng) < 0.0000001) {
            return false;
        }

        return true;
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000.0;
        $toRad = fn ($degrees) => $degrees * M_PI / 180;

        $dLat = $toRad($lat2 - $lat1);
        $dLng = $toRad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos($toRad($lat1)) * cos($toRad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(sqrt($a));
    }

    private function normalizeText(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = $converted ?: $text;
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function dedupeParts(array $parts): array
    {
        $out = [];
        $seen = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part === '') {
                continue;
            }

            $key = mb_strtolower($this->normalizeText($part));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $out[] = $part;
        }

        return $out;
    }

    private function coordString(float $lat, float $lng): string
    {
        return $lat . ',' . $lng;
    }

    private function ensureMexicoHint(string $address): string
    {
        $address = trim($address);

        if ($address === '') {
            return '';
        }

        if (
            stripos($address, 'mexico') === false &&
            stripos($address, 'méxico') === false &&
            stripos($address, 'mx') === false
        ) {
            $address .= ', México';
        }

        return $address;
    }

    private function buildAddressFromStop(array $stop): string
    {
        $address = trim((string) ($stop['address'] ?? ''));

        if ($address !== '') {
            return $this->ensureMexicoHint($address);
        }

        $parts = [];

        foreach (['calle', 'colonia', 'ciudad', 'estado', 'cp'] as $key) {
            $value = trim((string) ($stop[$key] ?? ''));

            if ($value !== '') {
                $parts[] = $value;
            }
        }

        $address = trim(implode(', ', $this->dedupeParts($parts)));

        return $this->ensureMexicoHint($address);
    }

    /* =========================================================================
     | Google Maps helpers
     * ========================================================================= */

    private function googleServerKey(): ?string
    {
        $key = config('services.google_maps.server_key')
            ?: config('services.google_maps.browser_key')
            ?: env('GOOGLE_MAPS_SERVER_KEY')
            ?: env('GOOGLE_MAPS_BROWSER_KEY');

        $key = trim((string) $key);

        return $key !== '' ? $key : null;
    }

    private function googleHttp()
    {
        return Http::timeout(25)
            ->connectTimeout(8)
            ->acceptJson();
    }

    private function googleGeocode(string $address): array
    {
        $key = $this->googleServerKey();

        if (!$key) {
            Log::error('google.geocode.missing_key');
            return [null, null, null];
        }

        $address = $this->ensureMexicoHint($address);

        if ($address === '') {
            return [null, null, null];
        }

        try {
            $response = $this->googleHttp()->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'region' => 'mx',
                'language' => 'es-419',
                'key' => $key,
            ]);

            if (!$response->ok()) {
                Log::warning('google.geocode.http_failed', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);

                return [null, null, null];
            }

            $json = $response->json();

            if (($json['status'] ?? null) !== 'OK' || empty($json['results'][0]['geometry']['location'])) {
                Log::warning('google.geocode.invalid_response', [
                    'status' => $json['status'] ?? null,
                    'error_message' => $json['error_message'] ?? null,
                    'address' => $address,
                ]);

                return [null, null, null];
            }

            $location = $json['results'][0]['geometry']['location'];
            $lat = isset($location['lat']) ? (float) $location['lat'] : null;
            $lng = isset($location['lng']) ? (float) $location['lng'] : null;

            if (!$this->validLatLng($lat, $lng)) {
                return [null, null, null];
            }

            return [
                $lat,
                $lng,
                $json['results'][0]['formatted_address'] ?? $address,
            ];
        } catch (\Throwable $e) {
            Log::error('google.geocode.exception', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return [null, null, null];
        }
    }

    private function googleDirections(array $coords, bool $optimizeWaypoints = false, bool $steps = true): array
    {
        $key = $this->googleServerKey();

        if (!$key) {
            return [
                'ok' => false,
                'status' => 'MISSING_KEY',
                'message' => 'Falta GOOGLE_MAPS_SERVER_KEY o GOOGLE_MAPS_BROWSER_KEY.',
            ];
        }

        $coords = collect($coords)
            ->map(function ($coord) {
                $lat = $this->normalizeCoord($coord['lat'] ?? null);
                $lng = $this->normalizeCoord($coord['lng'] ?? null);

                if (!$this->validLatLng($lat, $lng)) {
                    return null;
                }

                return [
                    'lat' => (float) $lat,
                    'lng' => (float) $lng,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if (count($coords) < 2) {
            return [
                'ok' => false,
                'status' => 'NOT_ENOUGH_COORDS',
                'message' => 'Google Directions necesita mínimo origen y destino.',
                'coords_count' => count($coords),
            ];
        }

        $origin = $coords[0];
        $destination = $coords[count($coords) - 1];

        $waypoints = array_slice($coords, 1, max(0, count($coords) - 2));

        $params = [
            'origin' => $this->coordString($origin['lat'], $origin['lng']),
            'destination' => $this->coordString($destination['lat'], $destination['lng']),
            'mode' => 'driving',
            'language' => 'es-419',
            'region' => 'mx',
            'units' => 'metric',
            'departure_time' => 'now',
            'traffic_model' => 'best_guess',
            'alternatives' => 'false',
            'key' => $key,
        ];

        if (!empty($waypoints)) {
            $waypointStrings = collect($waypoints)
                ->map(fn ($point) => $this->coordString($point['lat'], $point['lng']))
                ->values()
                ->all();

            $params['waypoints'] = ($optimizeWaypoints ? 'optimize:true|' : '') . implode('|', $waypointStrings);
        }

        try {
            $response = $this->googleHttp()->get('https://maps.googleapis.com/maps/api/directions/json', $params);

            if (!$response->ok()) {
                return [
                    'ok' => false,
                    'status' => 'HTTP_' . $response->status(),
                    'message' => 'Google Directions no respondió correctamente.',
                    'body' => Str::limit($response->body(), 1000),
                ];
            }

            $json = $response->json();

            if (($json['status'] ?? null) !== 'OK' || empty($json['routes'][0])) {
                return [
                    'ok' => false,
                    'status' => $json['status'] ?? 'UNKNOWN',
                    'message' => $json['error_message'] ?? 'Google Directions no devolvió ruta válida.',
                    'response' => $json,
                ];
            }

            return [
                'ok' => true,
                'status' => 'OK',
                'response' => $json,
                'route' => $json['routes'][0],
                'coords' => $coords,
                'waypoints' => $waypoints,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 'EXCEPTION',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function formatStepsFromGoogle(array $googleRoute): array
    {
        $out = [];

        foreach (($googleRoute['legs'] ?? []) as $leg) {
            foreach (($leg['steps'] ?? []) as $step) {
                $html = (string) ($step['html_instructions'] ?? '');
                $instruction = trim(strip_tags(str_replace(['<div', '</div>'], ['. <div', '</div>'], $html)));
                $instruction = preg_replace('/\s+/', ' ', $instruction);

                $out[] = [
                    'instruction' => $instruction !== '' ? $instruction : 'Continúa',
                    'name' => '',
                    'distance' => (int) ($step['distance']['value'] ?? 0),
                    'duration' => (int) ($step['duration']['value'] ?? 0),
                    'polyline' => $step['polyline']['points'] ?? null,
                    'start' => [
                        'lat' => $step['start_location']['lat'] ?? null,
                        'lng' => $step['start_location']['lng'] ?? null,
                    ],
                    'end' => [
                        'lat' => $step['end_location']['lat'] ?? null,
                        'lng' => $step['end_location']['lng'] ?? null,
                    ],
                ];
            }
        }

        return $out;
    }

    private function googleLegsPayload(array $googleRoute): array
    {
        $legs = [];

        foreach (($googleRoute['legs'] ?? []) as $leg) {
            $duration = (int) ($leg['duration']['value'] ?? 0);
            $durationTraffic = (int) ($leg['duration_in_traffic']['value'] ?? $duration);

            $legs[] = [
                'from' => [
                    'lat' => $leg['start_location']['lat'] ?? null,
                    'lng' => $leg['start_location']['lng'] ?? null,
                ],
                'to' => [
                    'lat' => $leg['end_location']['lat'] ?? null,
                    'lng' => $leg['end_location']['lng'] ?? null,
                ],
                'distance' => (int) ($leg['distance']['value'] ?? 0),
                'duration' => $duration,
                'adj_duration' => $durationTraffic,
                'start_address' => $leg['start_address'] ?? null,
                'end_address' => $leg['end_address'] ?? null,
            ];
        }

        return $legs;
    }

    private function googleRoutePayload(array $googleRoute, float $startLat, float $startLng): array
    {
        $legs = $this->googleLegsPayload($googleRoute);

        $totalDistance = (int) collect($legs)->sum('distance');
        $totalDuration = (int) collect($legs)->sum(fn ($leg) => (int) ($leg['adj_duration'] ?? $leg['duration'] ?? 0));

        return [
            'label' => 'ruta_principal',
            'provider' => 'google_maps',
            'polyline' => $googleRoute['overview_polyline']['points'] ?? null,
            'geometry' => null,
            'bounds' => $googleRoute['bounds'] ?? null,
            'legs' => $legs,
            'total_sec' => $totalDuration,
            'total_m' => $totalDistance,
            'steps' => $this->formatStepsFromGoogle($googleRoute),
            'roundtrip' => true,
            'start' => [
                'lat' => (float) $startLat,
                'lng' => (float) $startLng,
            ],
        ];
    }

    /* =========================================================================
     | Geocoding de paradas / proveedores
     * ========================================================================= */

    private function geocodeMx(string $address, array $parts = []): array
    {
        $address = trim($address);

        if ($address !== '') {
            return $this->googleGeocode($address);
        }

        $calle = trim((string) ($parts['calle'] ?? ''));
        $colonia = trim((string) ($parts['colonia'] ?? ''));
        $ciudad = trim((string) ($parts['ciudad'] ?? ''));
        $estado = trim((string) ($parts['estado'] ?? ''));
        $cp = trim((string) ($parts['cp'] ?? ''));

        $fallback = $this->ensureMexicoHint(
            implode(', ', $this->dedupeParts([$calle, $colonia, $ciudad, $estado, $cp]))
        );

        if ($fallback === '') {
            return [null, null, null];
        }

        return $this->googleGeocode($fallback);
    }

    private function resolveStopLatLng(array $stop): array
    {
        $lat = $this->normalizeCoord($stop['lat'] ?? null);
        $lng = $this->normalizeCoord($stop['lng'] ?? null);

        if ($this->validLatLng($lat, $lng)) {
            return [$lat, $lng, null, null];
        }

        $providerId = isset($stop['provider_id']) && is_numeric($stop['provider_id'])
            ? (int) $stop['provider_id']
            : null;

        $providerAddress = null;
        $providerParts = [];

        if ($providerId && Schema::hasTable('providers')) {
            $provider = DB::table('providers')->where('id', $providerId)->first();

            if ($provider) {
                $providerLat = $this->normalizeCoord($provider->lat ?? null);
                $providerLng = $this->normalizeCoord($provider->lng ?? null);

                if ($this->validLatLng($providerLat, $providerLng)) {
                    return [$providerLat, $providerLng, null, $providerId];
                }

                $providerParts = [
                    'calle' => $provider->calle ?? null,
                    'colonia' => $provider->colonia ?? null,
                    'ciudad' => $provider->ciudad ?? null,
                    'estado' => $provider->estado ?? null,
                    'cp' => $provider->cp ?? null,
                ];

                $providerAddress = $this->ensureMexicoHint(
                    implode(', ', $this->dedupeParts([
                        $provider->calle ?? null,
                        $provider->colonia ?? null,
                        $provider->ciudad ?? null,
                        $provider->estado ?? null,
                        $provider->cp ?? null,
                    ]))
                );
            }
        }

        $address = $this->buildAddressFromStop($stop);

        if ($address !== '') {
            [$geoLat, $geoLng] = $this->geocodeMx($address, [
                'calle' => $stop['calle'] ?? null,
                'colonia' => $stop['colonia'] ?? null,
                'ciudad' => $stop['ciudad'] ?? null,
                'estado' => $stop['estado'] ?? null,
                'cp' => $stop['cp'] ?? null,
            ]);

            if ($this->validLatLng($geoLat, $geoLng)) {
                return [$geoLat, $geoLng, $address, $providerId];
            }
        }

        if ($providerAddress) {
            [$geoLat, $geoLng] = $this->geocodeMx($providerAddress, $providerParts);

            if ($this->validLatLng($geoLat, $geoLng)) {
                return [$geoLat, $geoLng, $providerAddress, $providerId];
            }
        }

        return [null, null, $address ?: $providerAddress, $providerId];
    }

    /* =========================================================================
     | Presence / ubicación chofer
     * ========================================================================= */

    private function presenceConfig(): array
    {
        return [
            'online_seconds' => 120,
            'warn_seconds' => 45,
        ];
    }

    private function lastSeenAt(?DriverPosition $position): ?\Illuminate\Support\Carbon
    {
        if (!$position) {
            return null;
        }

        if (!empty($position->received_at)) {
            return \Illuminate\Support\Carbon::parse($position->received_at);
        }

        if (!empty($position->captured_at)) {
            return \Illuminate\Support\Carbon::parse($position->captured_at);
        }

        if (!empty($position->created_at)) {
            return \Illuminate\Support\Carbon::parse($position->created_at);
        }

        return null;
    }

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
        ];
    }

    private function positionPayload(?DriverPosition $position): ?array
    {
        if (!$position) {
            return null;
        }

        $lat = $position->lat !== null ? (float) $position->lat : null;
        $lng = $position->lng !== null ? (float) $position->lng : null;

        if (!$this->validLatLng($lat, $lng)) {
            return null;
        }

        return [
            'id' => $position->id,
            'user_id' => $position->user_id,

            'lat' => $lat,
            'lng' => $lng,

            'snap_lat' => $position->snap_lat !== null ? (float) $position->snap_lat : null,
            'snap_lng' => $position->snap_lng !== null ? (float) $position->snap_lng : null,
            'snap_distance_m' => $position->snap_distance_m ?? null,

            'accuracy' => $position->accuracy,
            'speed' => $position->speed,
            'heading' => $position->heading,

            'captured_at' => optional($position->captured_at)->toIso8601String(),
            'received_at' => optional($position->received_at)->toIso8601String(),
            'seen_at' => $this->lastSeenAt($position)?->toIso8601String(),

            'app_state' => $position->app_state ?? null,
            'battery' => $position->battery ?? null,
            'network' => $position->network ?? null,
            'is_mocked' => $position->is_mocked ?? null,
        ];
    }

    private function resolveLocationTargetUserId(Request $request): int
    {
        $authUser = Auth::user();

        abort_unless($authUser, 401);

        $targetUserId = (int) $authUser->id;
        $routePlanId = $request->input('route_plan_id') ?: $request->query('route_plan_id');

        if ($routePlanId) {
            $routePlan = RoutePlan::query()->find($routePlanId);

            if ($routePlan && $routePlan->driver_id) {
                $targetUserId = (int) $routePlan->driver_id;
            }
        }

        return $targetUserId;
    }

    private function latestDriverPosition(?int $driverId): ?DriverPosition
    {
        if (!$driverId) {
            return null;
        }

        return DriverPosition::where('user_id', $driverId)
            ->orderByRaw('COALESCE(received_at, captured_at, created_at) DESC')
            ->first();
    }

    /* =========================================================================
     | Helpers WMS / Shipment
     * ========================================================================= */

    private function userPhoneValue(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        $attrs = $user->getAttributes();

        foreach (['phone', 'telefono', 'phone_number', 'mobile', 'cellphone', 'whatsapp_phone'] as $field) {
            $value = trim((string) ($attrs[$field] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function findLinkedShipment(RoutePlan $routePlan): ?WmsShipment
    {
        if (!class_exists(WmsShipment::class)) {
            return null;
        }

        if (!Schema::hasTable('wms_shipments')) {
            return null;
        }

        if (Schema::hasColumn('wms_shipments', 'meta')) {
            $shipment = WmsShipment::query()
                ->where('meta->route_plan_id', (int) $routePlan->id)
                ->latest('id')
                ->first();

            if ($shipment) {
                return $shipment;
            }
        }

        if (Schema::hasColumn('wms_shipments', 'route_name') && !empty($routePlan->name)) {
            $shipment = WmsShipment::query()
                ->where('route_name', (string) $routePlan->name)
                ->latest('id')
                ->first();

            if ($shipment) {
                return $shipment;
            }
        }

        return null;
    }

    /* =========================================================================
     | CRUD / Panel logística
     * ========================================================================= */

    public function index()
    {
        $this->canManage();

        $plans = RoutePlan::with('driver')
            ->withCount([
                'stops',
                'stops as done_stops_count' => fn ($query) => $query->where('status', 'done'),
            ])
            ->latest()
            ->paginate(20);

        return view('logistics.routes.index', compact('plans'));
    }

    public function create()
    {
        $this->canManage();

        $orderCol = Schema::hasColumn('users', 'name')
            ? 'name'
            : (Schema::hasColumn('users', 'email') ? 'email' : 'id');

        $drivers = User::query()
            ->when(
                class_exists(\Spatie\Permission\Models\Role::class),
                fn ($query) => $query->whereDoesntHave('roles', fn ($role) => $role->where('name', 'cliente_web'))
            )
            ->orderBy($orderCol)
            ->get();

        $providers = collect();

        if (Schema::hasTable('providers')) {
            $cols = Schema::getColumnListing('providers');

            $latCols = ['lat', 'latitude', 'latitud', 'latitud_gps'];
            $lngCols = ['lng', 'lon', 'long', 'longitude', 'longitud', 'longitud_gps'];

            $pLat = collect($latCols)->first(fn ($col) => in_array($col, $cols, true));
            $pLng = collect($lngCols)->first(fn ($col) => in_array($col, $cols, true));

            $select = ['id'];

            $select[] = in_array('empresa', $cols, true)
                ? DB::raw("`empresa` as `empresa`")
                : DB::raw("NULL as `empresa`");

            $select[] = in_array('nombre', $cols, true)
                ? DB::raw("`nombre` as `nombre`")
                : DB::raw("NULL as `nombre`");

            foreach (['email', 'telefono', 'rfc', 'tipo_persona', 'calle', 'colonia', 'ciudad', 'estado', 'cp'] as $col) {
                $select[] = in_array($col, $cols, true)
                    ? DB::raw("`{$col}` as `{$col}`")
                    : DB::raw("NULL as `{$col}`");
            }

            $select[] = $pLat ? DB::raw("NULLIF(`{$pLat}`, 0) as `lat`") : DB::raw("NULL as `lat`");
            $select[] = $pLng ? DB::raw("NULLIF(`{$pLng}`, 0) as `lng`") : DB::raw("NULL as `lng`");

            $addrParts = [];

            foreach (['calle', 'colonia', 'ciudad', 'estado', 'cp'] as $col) {
                if (in_array($col, $cols, true)) {
                    $addrParts[] = "NULLIF(TRIM(`{$col}`),'')";
                }
            }

            $select[] = !empty($addrParts)
                ? DB::raw("CONCAT_WS(', ', " . implode(', ', $addrParts) . ") as `address`")
                : DB::raw("'' as `address`");

            $query = DB::table('providers')->select($select);

            if (in_array('estatus', $cols, true)) {
                $query->where(function ($where) {
                    $where->where('estatus', 1)
                        ->orWhere('estatus', '1')
                        ->orWhere('estatus', true);
                });
            }

            if ($pLat && $pLng) {
                $query->orderByRaw("(NULLIF(`{$pLat}`,0) is not null and NULLIF(`{$pLng}`,0) is not null) desc");
            }

            $query->orderByRaw("COALESCE(NULLIF(TRIM(`empresa`), ''), NULLIF(TRIM(`nombre`), ''), CONCAT('Proveedor #', id)) ASC");

            $providers = $query->get();
        }

        return view('logistics.routes.create', compact('drivers', 'providers'));
    }

    public function store(Request $request)
    {
        $this->canManage();

        $stopsInput = $request->input('stops');

        if (is_string($stopsInput)) {
            $decoded = json_decode($stopsInput, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['stops' => $decoded]);
            }
        }

        $data = $request->validate([
            'driver_id' => ['required', 'exists:users,id'],
            'name' => ['nullable', 'string', 'max:180'],
            'shipment_id' => ['nullable', 'integer'],
            'back_url' => ['nullable', 'string', 'max:2000'],

            'stops' => ['required', 'array', 'min:1'],
            'stops.*.name' => ['nullable', 'string', 'max:180'],
            'stops.*.provider_id' => ['nullable'],

            'stops.*.address' => ['nullable', 'string', 'max:700'],
            'stops.*.calle' => ['nullable', 'string', 'max:250'],
            'stops.*.colonia' => ['nullable', 'string', 'max:250'],
            'stops.*.ciudad' => ['nullable', 'string', 'max:250'],
            'stops.*.estado' => ['nullable', 'string', 'max:250'],
            'stops.*.cp' => ['nullable', 'string', 'max:20'],

            'stops.*.lat' => ['nullable'],
            'stops.*.lng' => ['nullable'],
        ]);

        $resolvedStops = [];
        $errors = [];

        foreach ($data['stops'] as $index => $stop) {
            [$lat, $lng, $addressUsed, $providerIdUsed] = $this->resolveStopLatLng($stop);

            if (!$this->validLatLng($lat, $lng)) {
                $name = trim((string) ($stop['name'] ?? ''));
                $label = $name ?: ($addressUsed ?: 'sin nombre');

                $errors[] = 'No se pudo obtener coordenadas para el punto #' . ($index + 1) . " ({$label}).";

                Log::warning('routes.store.stop_without_coords', [
                    'index' => $index + 1,
                    'stop' => $stop,
                    'address_used' => $addressUsed,
                ]);

                continue;
            }

            $resolvedStops[] = [
                'name' => $stop['name'] ?? null,
                'lat' => (float) $lat,
                'lng' => (float) $lng,
                'address' => $addressUsed ?: $this->buildAddressFromStop($stop),

                'calle' => $stop['calle'] ?? null,
                'colonia' => $stop['colonia'] ?? null,
                'ciudad' => $stop['ciudad'] ?? null,
                'estado' => $stop['estado'] ?? null,
                'cp' => $stop['cp'] ?? null,

                'provider_id' => $providerIdUsed,
            ];
        }

        if (empty($resolvedStops)) {
            return back()->withInput()->withErrors([
                'stops' => 'No se pudo guardar: ninguna parada tiene coordenadas válidas.',
            ]);
        }

        if (!empty($errors)) {
            return back()->withInput()->withErrors([
                'stops' => implode("\n", $errors),
            ]);
        }

        return DB::transaction(function () use ($data, $resolvedStops) {
            $plan = RoutePlan::create([
                'driver_id' => $data['driver_id'],
                'name' => $data['name'] ?? null,
                'status' => 'scheduled',
            ]);

            foreach ($resolvedStops as $index => $stop) {
                $payload = [
                    'route_plan_id' => $plan->id,
                    'name' => $stop['name'] ?: ('Punto ' . ($index + 1)),
                    'lat' => $stop['lat'],
                    'lng' => $stop['lng'],
                    'sequence_index' => null,
                    'status' => 'pending',
                ];

                if (Schema::hasColumn('route_stops', 'provider_id')) {
                    $payload['provider_id'] = $stop['provider_id'];
                }

                if (Schema::hasColumn('route_stops', 'address')) {
                    $payload['address'] = $stop['address'];
                }

                if (Schema::hasColumn('route_stops', 'calle')) {
                    $payload['calle'] = $stop['calle'];
                }

                if (Schema::hasColumn('route_stops', 'colonia')) {
                    $payload['colonia'] = $stop['colonia'];
                }

                if (Schema::hasColumn('route_stops', 'ciudad')) {
                    $payload['ciudad'] = $stop['ciudad'];
                }

                if (Schema::hasColumn('route_stops', 'estado')) {
                    $payload['estado'] = $stop['estado'];
                }

                if (Schema::hasColumn('route_stops', 'cp')) {
                    $payload['cp'] = $stop['cp'];
                }

                RouteStop::create($payload);

                if (!empty($stop['provider_id']) && Schema::hasTable('providers')) {
                    try {
                        DB::table('providers')->where('id', (int) $stop['provider_id'])->update([
                            'lat' => $stop['lat'],
                            'lng' => $stop['lng'],
                            'updated_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('routes.store.provider_update_failed', [
                            'provider_id' => $stop['provider_id'],
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            $shipmentId = !empty($data['shipment_id']) ? (int) $data['shipment_id'] : null;
            $backUrl = trim((string) ($data['back_url'] ?? ''));

            if ($shipmentId && class_exists(WmsShipment::class) && Schema::hasTable('wms_shipments')) {
                $shipment = WmsShipment::query()->find($shipmentId);

                if ($shipment) {
                    $driver = User::query()->find((int) $data['driver_id']);
                    $driverPhone = $this->userPhoneValue($driver);

                    $meta = is_array($shipment->meta) ? $shipment->meta : [];
                    $meta['route_plan_id'] = $plan->id;
                    $meta['route_plan_name'] = $plan->name ?: ('Ruta #' . $plan->id);
                    $meta['delivery_user_id'] = $driver?->id;
                    $meta['delivery_user_name'] = $driver?->name;

                    if (Schema::hasColumn($shipment->getTable(), 'route_name')) {
                        $shipment->route_name = $plan->name ?: ('Ruta #' . $plan->id);
                    }

                    if (Schema::hasColumn($shipment->getTable(), 'driver_name') && $driver?->name) {
                        $shipment->driver_name = $driver->name;
                    }

                    if (
                        Schema::hasColumn($shipment->getTable(), 'driver_phone') &&
                        empty($shipment->driver_phone) &&
                        !empty($driverPhone)
                    ) {
                        $shipment->driver_phone = $driverPhone;
                    }

                    if (Schema::hasColumn($shipment->getTable(), 'meta')) {
                        $shipment->meta = $meta;
                    }

                    if (Schema::hasColumn($shipment->getTable(), 'updated_by')) {
                        $shipment->updated_by = auth()->id();
                    }

                    $shipment->save();
                }
            }

            if ($backUrl !== '' && str_starts_with($backUrl, url('/'))) {
                $separator = str_contains($backUrl, '?') ? '&' : '?';

                return redirect()->to($backUrl . $separator . 'route_plan_id=' . $plan->id)
                    ->with('ok', 'Ruta creada y vinculada al embarque');
            }

            return redirect()->route('routes.show', $plan)->with('ok', 'Ruta creada');
        });
    }

    public function show(RoutePlan $routePlan)
    {
        $this->canManage();

        $routePlan->load([
            'driver',
            'stops' => function ($query) {
                $query->orderByRaw('COALESCE(sequence_index, 999999), id');
            },
        ]);

        $linkedShipment = $this->findLinkedShipment($routePlan);

        return view('logistics.routes.show', compact('routePlan', 'linkedShipment'));
    }

    public function driver(RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        $routePlan->load('stops', 'driver');

        $stops = $routePlan->stops()
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get([
                'id',
                'name',
                'lat',
                'lng',
                'sequence_index',
                'eta_seconds',
                'status',
                'done_at',
            ]);

        Log::info('driver.routes.show view boot', [
            'plan_id' => $routePlan->id,
            'stops_count' => $stops->count(),
            'has_driver' => (bool) $routePlan->driver,
            'driver_id' => $routePlan->driver_id,
            'auth_user_id' => auth()->id(),
        ]);

        return view('driver.routes.show', compact('routePlan', 'stops'));
    }

    /* =========================================================================
     | GPS chofer
     * ========================================================================= */

    public function saveDriverLocation(Request $request)
    {
        $authUser = Auth::user();

        abort_unless($authUser, 401);

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

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        if (!$this->validLatLng($lat, $lng)) {
            return response()->json([
                'ok' => false,
                'message' => 'GPS inválido.',
            ], 422);
        }

        $routePlan = null;
        $targetUserId = (int) $authUser->id;

        if (!empty($data['route_plan_id'])) {
            $routePlan = RoutePlan::query()->find($data['route_plan_id']);

            if ($routePlan && $routePlan->driver_id) {
                $targetUserId = (int) $routePlan->driver_id;
            }
        }

        $capturedAt = !empty($data['captured_at'])
            ? \Illuminate\Support\Carbon::parse($data['captured_at'])
            : now();

        $previous = $this->latestDriverPosition($targetUserId);

        if ($previous && $previous->lat !== null && $previous->lng !== null) {
            $previousSeenAt = $this->lastSeenAt($previous);

            if ($previousSeenAt) {
                $seconds = abs($capturedAt->diffInSeconds($previousSeenAt));

                $distance = $this->haversineMeters(
                    (float) $previous->lat,
                    (float) $previous->lng,
                    $lat,
                    $lng
                );

                if ($seconds <= 20 && $distance > 2500) {
                    Log::warning('driver.location.jump_detected_but_saved', [
                        'auth_user_id' => $authUser->id,
                        'target_user_id' => $targetUserId,
                        'route_plan_id' => $routePlan?->id,
                        'previous_position_id' => $previous->id,
                        'seconds_between' => $seconds,
                        'distance_m' => $distance,
                        'previous_lat' => (float) $previous->lat,
                        'previous_lng' => (float) $previous->lng,
                        'new_lat' => $lat,
                        'new_lng' => $lng,
                    ]);
                }
            }
        }

        $accuracy = isset($data['accuracy']) ? (float) $data['accuracy'] : null;

        if ($accuracy !== null && $accuracy > 500) {
            Log::warning('driver.location.low_accuracy_but_saved', [
                'auth_user_id' => $authUser->id,
                'target_user_id' => $targetUserId,
                'route_plan_id' => $routePlan?->id,
                'accuracy' => $accuracy,
                'lat' => $lat,
                'lng' => $lng,
            ]);
        }

        try {
            $position = DriverPosition::create([
                'user_id' => $targetUserId,

                'lat' => $lat,
                'lng' => $lng,
                'accuracy' => $data['accuracy'] ?? null,
                'speed' => $data['speed'] ?? null,
                'heading' => $data['heading'] ?? null,

                'captured_at' => $capturedAt,
                'received_at' => now(),

                'app_state' => $data['app_state'] ?? null,
                'battery' => $data['battery'] ?? null,
                'network' => $data['network'] ?? null,
                'is_mocked' => $data['is_mocked'] ?? null,

                // Ya no usamos OSRM ni snap-to-road.
                'snap_lat' => null,
                'snap_lng' => null,
                'snap_distance_m' => null,
            ]);

            Log::info('driver.location.saved', [
                'position_id' => $position->id,
                'auth_user_id' => $authUser->id,
                'saved_user_id' => $position->user_id,
                'route_plan_id' => $routePlan?->id,
                'route_driver_id' => $routePlan?->driver_id,
                'lat' => $position->lat,
                'lng' => $position->lng,
                'accuracy' => $position->accuracy,
                'received_at' => optional($position->received_at)->toDateTimeString(),
            ]);

            return response()->json([
                'ok' => true,
                'id' => $position->id,

                'auth_user_id' => $authUser->id,
                'saved_user_id' => $position->user_id,

                'route_plan_id' => $routePlan?->id,
                'route_driver_id' => $routePlan?->driver_id,

                'lat' => (float) $position->lat,
                'lng' => (float) $position->lng,
                'accuracy' => $position->accuracy,
                'captured_at' => optional($position->captured_at)->toIso8601String(),
                'received_at' => optional($position->received_at)->toIso8601String(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('driver.location.save.failed', [
                'auth_user_id' => $authUser->id,
                'target_user_id' => $targetUserId,
                'route_plan_id' => $routePlan?->id,
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

    public function getDriverLocation(Request $request)
    {
        $authUser = Auth::user();

        abort_unless($authUser, 401);

        $targetUserId = $this->resolveLocationTargetUserId($request);
        $last = $this->latestDriverPosition($targetUserId);

        return response()->json([
            'ok' => true,
            'auth_user_id' => $authUser->id,
            'target_user_id' => $targetUserId,

            'found' => (bool) $last,
            'id' => $last?->id,
            'lat' => $last?->lat !== null ? (float) $last->lat : null,
            'lng' => $last?->lng !== null ? (float) $last->lng : null,
            'accuracy' => $last?->accuracy,
            'speed' => $last?->speed,
            'heading' => $last?->heading,

            'captured_at' => optional($last?->captured_at)->toIso8601String(),
            'received_at' => optional($last?->received_at)->toIso8601String(),
            'presence' => $this->presencePayload($last),
        ], 200);
    }

    public function live(RoutePlan $routePlan)
    {
        $user = Auth::user();

        abort_unless($user, 401);

        $ok = $this->canUserManage() || ((int) $user->id === (int) $routePlan->driver_id);

        abort_unless($ok, 403);

        $last = $this->latestDriverPosition($routePlan->driver_id);

        $stops = $routePlan->stops()
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get([
                'id',
                'name',
                'lat',
                'lng',
                'sequence_index',
                'eta_seconds',
                'status',
                'done_at',
            ]);

        return response()->json([
            'plan_id' => $routePlan->id,
            'driver_id' => $routePlan->driver_id,
            'presence' => $this->presencePayload($last),
            'driver_last' => $last ? $this->positionPayload($last) : null,
            'stops' => $stops,
            'start' => [
                'lat' => $routePlan->start_lat,
                'lng' => $routePlan->start_lng,
            ],
            'sequence_locked' => (bool) ($routePlan->sequence_locked ?? false),
            'server_time' => now()->toIso8601String(),
        ], 200);
    }

    /* =========================================================================
     | Optimización / cálculo de ruta con Google Maps
     * ========================================================================= */

    private function lockSequence(RoutePlan $routePlan, float $startLat, float $startLng): void
    {
        if (!empty($routePlan->sequence_locked)) {
            return;
        }

        $pending = $routePlan->stops()
            ->where('status', 'pending')
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get();

        $stopsValid = $pending->filter(function ($stop) {
            $lat = $this->normalizeCoord($stop->lat);
            $lng = $this->normalizeCoord($stop->lng);

            return $this->validLatLng($lat, $lng);
        })->values();

        if ($stopsValid->isEmpty()) {
            $routePlan->update([
                'start_lat' => (float) $startLat,
                'start_lng' => (float) $startLng,
                'started_at' => $routePlan->started_at ?: now(),
                'status' => 'in_progress',
                'sequence_locked' => true,
            ]);

            return;
        }

        $coords = [];

        $coords[] = [
            'lat' => (float) $startLat,
            'lng' => (float) $startLng,
        ];

        foreach ($stopsValid as $stop) {
            $coords[] = [
                'lat' => (float) $this->normalizeCoord($stop->lat),
                'lng' => (float) $this->normalizeCoord($stop->lng),
            ];
        }

        // Roundtrip: destino final vuelve al inicio.
        $coords[] = [
            'lat' => (float) $startLat,
            'lng' => (float) $startLng,
        ];

        $directions = $this->googleDirections($coords, true, false);

        if (!$directions['ok']) {
            Log::warning('lockSequence.google_directions_invalid', [
                'plan_id' => $routePlan->id,
                'detail' => $directions,
            ]);

            // Fallback sin OSRM: ordenar por cercanía progresiva.
            $orderedStops = $this->nearestNeighborOrder($stopsValid, $startLat, $startLng);
        } else {
            $waypointOrder = $directions['route']['waypoint_order'] ?? [];

            if (empty($waypointOrder)) {
                $orderedStops = $stopsValid;
            } else {
                $orderedStops = collect($waypointOrder)
                    ->map(fn ($index) => $stopsValid[(int) $index] ?? null)
                    ->filter()
                    ->values();

                if ($orderedStops->count() !== $stopsValid->count()) {
                    $orderedStops = $stopsValid;
                }
            }
        }

        DB::transaction(function () use ($routePlan, $orderedStops, $startLat, $startLng) {
            foreach ($orderedStops as $index => $stop) {
                $stop->sequence_index = $index + 1;
                $stop->save();
            }

            $routePlan->update([
                'start_lat' => (float) $startLat,
                'start_lng' => (float) $startLng,
                'started_at' => $routePlan->started_at ?: now(),
                'status' => 'in_progress',
                'sequence_locked' => true,
            ]);
        });
    }

    private function nearestNeighborOrder($stops, float $startLat, float $startLng)
    {
        $remaining = collect($stops)->values();
        $ordered = collect();

        $currentLat = $startLat;
        $currentLng = $startLng;

        while ($remaining->isNotEmpty()) {
            $nearestIndex = null;
            $nearestDistance = INF;

            foreach ($remaining as $index => $stop) {
                $lat = (float) $this->normalizeCoord($stop->lat);
                $lng = (float) $this->normalizeCoord($stop->lng);

                $distance = $this->haversineMeters($currentLat, $currentLng, $lat, $lng);

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $index;
                }
            }

            $nearest = $remaining[$nearestIndex];
            $ordered->push($nearest);

            $currentLat = (float) $this->normalizeCoord($nearest->lat);
            $currentLng = (float) $this->normalizeCoord($nearest->lng);

            $remaining = $remaining->reject(fn ($_, $index) => $index === $nearestIndex)->values();
        }

        return $ordered;
    }

    public function start(Request $request, RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        if (!empty($routePlan->sequence_locked)) {
            return response()->json([
                'ok' => true,
                'message' => 'Orden ya bloqueado',
                'sequence_locked' => true,
            ], 200);
        }

        $startLat = $this->normalizeCoord($request->input('start_lat'));
        $startLng = $this->normalizeCoord($request->input('start_lng'));

        if (!$this->validLatLng($startLat, $startLng)) {
            $last = $this->latestDriverPosition($routePlan->driver_id ?: Auth::id());

            if (!$last) {
                return response()->json([
                    'message' => 'No hay ubicación actual del chofer.',
                ], 422);
            }

            $startLat = $this->normalizeCoord($last->lat);
            $startLng = $this->normalizeCoord($last->lng);
        }

        if (!$this->validLatLng($startLat, $startLng)) {
            return response()->json([
                'message' => 'Ubicación de inicio inválida.',
            ], 422);
        }

        $this->lockSequence($routePlan->fresh(), (float) $startLat, (float) $startLng);

        return response()->json([
            'ok' => true,
            'message' => 'Ruta iniciada con Google Maps.',
            'start' => [
                'lat' => (float) $startLat,
                'lng' => (float) $startLng,
            ],
            'sequence_locked' => true,
        ], 200);
    }

    public function compute(Request $request, RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        $routePlan->load('driver');

        $startLat = $this->normalizeCoord($routePlan->start_lat);
        $startLng = $this->normalizeCoord($routePlan->start_lng);

        if (!$this->validLatLng($startLat, $startLng)) {
            $startLat = $this->normalizeCoord($request->input('start_lat'));
            $startLng = $this->normalizeCoord($request->input('start_lng'));
        }

        if (!$this->validLatLng($startLat, $startLng)) {
            $last = $this->latestDriverPosition($routePlan->driver_id ?: Auth::id());

            if (!$last) {
                return response()->json([
                    'message' => 'No hay ubicación actual del chofer. Toca “Ver mi ubicación”.',
                ], 422);
            }

            $startLat = $this->normalizeCoord($last->lat);
            $startLng = $this->normalizeCoord($last->lng);
        }

        if (!$this->validLatLng($startLat, $startLng)) {
            return response()->json([
                'message' => 'Ubicación de inicio inválida.',
            ], 422);
        }

        if (empty($routePlan->sequence_locked)) {
            $this->lockSequence($routePlan->fresh(), (float) $startLat, (float) $startLng);
            $routePlan = $routePlan->fresh();
        }

        $stops = $routePlan->stops()
            ->where('status', 'pending')
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get();

        if ($stops->isEmpty()) {
            return response()->json([
                'message' => 'No hay paradas pendientes',
                'routes' => [],
                'ordered_stops' => $routePlan->stops()
                    ->orderByRaw('COALESCE(sequence_index, 999999), id')
                    ->get(['id', 'name', 'lat', 'lng', 'sequence_index', 'eta_seconds', 'status', 'done_at']),
            ], 200);
        }

        $stopsValid = $stops->filter(function ($stop) {
            $lat = $this->normalizeCoord($stop->lat);
            $lng = $this->normalizeCoord($stop->lng);

            return $this->validLatLng($lat, $lng);
        })->values();

        if ($stopsValid->isEmpty()) {
            return response()->json([
                'message' => 'No hay paradas pendientes con coordenadas válidas.',
                'routes' => [],
            ], 200);
        }

        $routeCoords = [];

        $routeCoords[] = [
            'lat' => (float) $startLat,
            'lng' => (float) $startLng,
        ];

        foreach ($stopsValid as $stop) {
            $routeCoords[] = [
                'lat' => (float) $this->normalizeCoord($stop->lat),
                'lng' => (float) $this->normalizeCoord($stop->lng),
            ];
        }

        // Roundtrip: vuelve al inicio.
        $routeCoords[] = [
            'lat' => (float) $startLat,
            'lng' => (float) $startLng,
        ];

        $directions = $this->googleDirections($routeCoords, false, true);

        if (!$directions['ok']) {
            Log::error('compute.google_directions_invalid', [
                'plan_id' => $routePlan->id,
                'detail' => $directions,
            ]);

            return response()->json([
                'message' => 'Google Maps no devolvió una ruta válida.',
                'detail' => $directions,
            ], 422);
        }

        $googleRoute = $directions['route'];
        $principal = $this->googleRoutePayload($googleRoute, (float) $startLat, (float) $startLng);

        $legs = $principal['legs'] ?? [];
        $etaAcc = 0;

        foreach ($stopsValid as $index => $stop) {
            $leg = $legs[$index] ?? null;

            $etaAcc += (int) ($leg['adj_duration'] ?? $leg['duration'] ?? 0);

            $stop->eta_seconds = $etaAcc;

            try {
                $stop->save();
            } catch (\Throwable $e) {
                Log::warning('compute.stop_eta_save_failed', [
                    'stop_id' => $stop->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $advice = $this->advisor->advise([$principal], [
                'driver' => $routePlan->driver?->name,
                'route' => $routePlan->name,
                'provider' => 'google_maps',
            ]);
        } catch (\Throwable $e) {
            Log::warning('route.advisor.failed', [
                'plan_id' => $routePlan->id,
                'error' => $e->getMessage(),
            ]);

            $advice = null;
        }

        $exportLinks = $this->buildNavLinks(
            $principal,
            $routePlan->stops()->where('status', 'pending')->get(),
            (float) $startLat,
            (float) $startLng
        );

        return response()->json([
            'plan_id' => $routePlan->id,
            'provider' => 'google_maps',
            'sequence_locked' => (bool) ($routePlan->sequence_locked ?? false),
            'roundtrip' => true,
            'start' => [
                'lat' => (float) $startLat,
                'lng' => (float) $startLng,
            ],
            'ordered_stops' => $routePlan->stops()
                ->orderByRaw('COALESCE(sequence_index, 999999), id')
                ->get(['id', 'name', 'lat', 'lng', 'sequence_index', 'eta_seconds', 'status', 'done_at']),
            'routes' => [$principal],
            'advice_md' => $advice ?: 'Ruta calculada con Google Maps y cierre al punto de inicio.',
            'total_minutes' => (int) round(($principal['total_sec'] ?? 0) / 60),
            'export_links' => $exportLinks,
        ], 200);
    }

    public function recompute(Request $request, RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        return $this->compute($request, $routePlan);
    }

    public function markStopDone(Request $request, RoutePlan $routePlan, RouteStop $stop)
    {
        $this->canDrive($routePlan);

        if ((int) $stop->route_plan_id !== (int) $routePlan->id) {
            return response()->json([
                'message' => 'Stop no pertenece a esta ruta',
            ], 404);
        }

        $stop->update([
            'status' => 'done',
            'done_at' => now(),
        ]);

        $pending = $routePlan->stops()->where('status', 'pending')->count();

        $routePlan->update([
            'status' => $pending === 0 ? 'done' : 'in_progress',
        ]);

        return response()->json([
            'ok' => true,
        ], 200);
    }

    private function buildNavLinks(array $principal, $pendingStops, float $startLat, float $startLng): array
    {
        $ordered = collect($pendingStops)
            ->sortBy(function ($stop) {
                $sequence = $stop->sequence_index ?? 999999;
                return sprintf('%06d-%06d', $sequence, $stop->id);
            })
            ->values();

        $next = $ordered->first();

        if (!$next) {
            return [];
        }

        $origin = $startLat . ',' . $startLng;
        $destination = $next->lat . ',' . $next->lng;

        $googleMaps = 'https://www.google.com/maps/dir/?api=1'
            . '&origin=' . urlencode($origin)
            . '&destination=' . urlencode($destination)
            . '&travelmode=driving'
            . '&dir_action=navigate';

        $waze = 'https://waze.com/ul?ll='
            . urlencode($destination)
            . '&navigate=yes&zoom=17';

        return [
            'maps_principal' => $googleMaps,
            'waze_next' => $waze,
        ];
    }
}