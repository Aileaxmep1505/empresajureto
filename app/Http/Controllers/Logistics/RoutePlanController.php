<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Models\DriverPosition;
use App\Services\OsrmClient;
use App\Services\RouteAiAdvisor;
use App\Services\TrafficService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class RoutePlanController extends Controller
{
    public function __construct(
        protected OsrmClient $osrm,
        protected TrafficService $traffic,
        protected RouteAiAdvisor $advisor,
    ) {
        // $this->middleware('auth');
    }

    /* =========================================================================
     | Permisos
     * ========================================================================= */
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

    private function canDrive(RoutePlan $plan): void
    {
        $u = Auth::user();
        abort_unless($u, 403);

        if (class_exists(\Spatie\Permission\Models\Role::class) && method_exists($u, 'hasRole')) {
            $ok = ($u->id === $plan->driver_id) || $this->canUserManage();
            abort_unless($ok, 403);
            return;
        }

        abort_unless($u->id === $plan->driver_id, 403);
    }

    /* =========================
     | Helpers coords + dirección
     * ========================= */

    /** Convierte "0" o 0 a null, convierte strings numéricos a float. */
    private function normalizeCoord($v): ?float
    {
        if ($v === null) return null;
        if ($v === '') return null;

        if (is_string($v)) {
            $v = trim($v);
            $v = str_replace(',', '.', $v);
        }

        if (!is_numeric($v)) return null;

        $f = (float) $v;
        if (!is_finite($f)) return null;

        // 0,0 no sirve
        if (abs($f) < 0.0000001) return null;

        return $f;
    }

    private function validLatLng(?float $lat, ?float $lng): bool
    {
        if ($lat === null || $lng === null) return false;
        if (abs($lat) > 90 || abs($lng) > 180) return false;
        if (abs($lat) < 0.0000001 && abs($lng) < 0.0000001) return false; // 0,0
        return true;
    }

    /** Quita acentos y normaliza espacios. */
    private function normalizeText(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';

        // quitar acentos
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;

        // espacios
        $s = preg_replace('/\s+/', ' ', $s);
        $s = trim($s);

        return $s;
    }

    /** Elimina duplicados obvios (ej: "CIUDAD DE MEXICO, CIUDAD DE MEXICO"). */
    private function dedupeParts(array $parts): array
    {
        $out = [];
        $seen = [];

        foreach ($parts as $p) {
            $p = trim((string)$p);
            if ($p === '') continue;

            $key = mb_strtolower($this->normalizeText($p));
            if (isset($seen[$key])) continue;

            $seen[$key] = true;
            $out[] = $p;
        }
        return $out;
    }

    /** Arma address a partir de address o (calle/colonia/ciudad/estado/cp) y agrega México. */
    private function buildAddressFromStop(array $s): string
    {
        $address = trim((string)($s['address'] ?? ''));
        if ($address !== '') return $this->ensureMexicoHint($address);

        $parts = [];
        foreach (['calle', 'colonia', 'ciudad', 'estado', 'cp'] as $k) {
            $v = trim((string)($s[$k] ?? ''));
            if ($v !== '') $parts[] = $v;
        }

        $parts = $this->dedupeParts($parts);
        $addr = trim(implode(', ', $parts));
        return $this->ensureMexicoHint($addr);
    }

    private function ensureMexicoHint(string $addr): string
    {
        $addr = trim($addr);
        if ($addr === '') return '';
        if (stripos($addr, 'mex') === false) $addr .= ', México';
        return $addr;
    }

    private function httpNominatim()
    {
        return Http::timeout(12)
            ->withHeaders([
                'Accept' => 'application/json',
                // User-Agent fuerte (Nominatim suele ser sensible a esto)
                'User-Agent' => (config('app.name', 'Laravel') ?: 'Laravel') . ' routes-geocoder/1.0',
                'Referer'    => config('app.url') ?: 'http://localhost',
            ]);
    }

    /**
     * Geocodifica con Nominatim:
     * - intenta búsqueda estructurada primero (street/city/state/postalcode/country)
     * - luego fallback con q simplificado (sin acentos / sin duplicados)
     *
     * Devuelve [lat, lng, displayName] o [null,null,null]
     */
    private function geocodeMx(string $address, array $parts = []): array
    {
        $address = trim($address);

        // parts esperados: calle, colonia, ciudad, estado, cp
        $calle  = trim((string)($parts['calle'] ?? ''));
        $col    = trim((string)($parts['colonia'] ?? ''));
        $ciudad = trim((string)($parts['ciudad'] ?? ''));
        $estado = trim((string)($parts['estado'] ?? ''));
        $cp     = trim((string)($parts['cp'] ?? ''));

        // 0) nada
        if ($address === '' && $calle === '' && $ciudad === '' && $estado === '' && $cp === '') {
            return [null, null, null];
        }

        // --- 1) Estructurado (mejor hit rate en MX) ---
        try {
            $street = trim(implode(', ', $this->dedupeParts([$calle, $col])));
            $street = $this->normalizeText($street);
            $city   = $this->normalizeText($ciudad);
            $state  = $this->normalizeText($estado);
            $zip    = $this->normalizeText($cp);

            if ($street !== '' || $city !== '' || $state !== '' || $zip !== '') {
                $res = $this->httpNominatim()->get('https://nominatim.openstreetmap.org/search', array_filter([
                    'format'          => 'jsonv2',
                    'limit'           => 1,
                    'accept-language' => 'es',
                    'countrycodes'    => 'mx',
                    'addressdetails'  => 1,

                    // parámetros estructurados:
                    'street'      => $street !== '' ? $street : null,
                    'city'        => $city   !== '' ? $city   : null,
                    'state'       => $state  !== '' ? $state  : null,
                    'postalcode'  => $zip    !== '' ? $zip    : null,
                    'country'     => 'Mexico',
                ], fn($v) => !is_null($v) && $v !== ''));

                if ($res->ok()) {
                    $j = $res->json();
                    if (is_array($j) && !empty($j[0])) {
                        $lat = isset($j[0]['lat']) ? (float)$j[0]['lat'] : null;
                        $lng = isset($j[0]['lon']) ? (float)$j[0]['lon'] : null;
                        if ($this->validLatLng($lat, $lng)) {
                            return [$lat, $lng, (string)($j[0]['display_name'] ?? null)];
                        }
                    } else {
                        Log::info('geocodeMx structured empty', [
                            'street'=>$street, 'city'=>$city, 'state'=>$state, 'cp'=>$zip,
                            'json'=>$j
                        ]);
                    }
                } else {
                    Log::warning('geocodeMx structured non-200', [
                        'status'=>$res->status(),
                        'body'=>$res->body(),
                        'street'=>$street, 'city'=>$city, 'state'=>$state, 'cp'=>$zip,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('geocodeMx structured exception', ['error'=>$e->getMessage()]);
        }

        // --- 2) q normal (con México) ---
        $q = $address !== '' ? $this->ensureMexicoHint($address) : '';
        $q = $this->normalizeText($q);

        // si venía muy repetido, reconstruye desde partes
        if ($q === '' && ($calle || $col || $ciudad || $estado || $cp)) {
            $q = $this->normalizeText($this->ensureMexicoHint(
                implode(', ', $this->dedupeParts([$calle, $col, $ciudad, $estado, $cp]))
            ));
        }

        // helper para intentar con q
        $tryQ = function(string $qq){
            $qq = trim($qq);
            if ($qq === '') return [null,null,null];

            $res = $this->httpNominatim()->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'jsonv2',
                'limit'  => 1,
                'accept-language' => 'es',
                'countrycodes'    => 'mx',
                'addressdetails'  => 1,
                'q' => $qq,
            ]);

            if (!$res->ok()) {
                Log::warning('geocodeMx q non-200', ['q'=>$qq, 'status'=>$res->status(), 'body'=>$res->body()]);
                return [null,null,null];
            }

            $j = $res->json();
            if (!is_array($j) || empty($j[0])) {
                Log::info('geocodeMx empty', ['address'=>$qq, 'json'=>$j]);
                return [null,null,null];
            }

            $lat = isset($j[0]['lat']) ? (float)$j[0]['lat'] : null;
            $lng = isset($j[0]['lon']) ? (float)$j[0]['lon'] : null;

            if (!$this->validLatLng($lat, $lng)) {
                Log::warning('geocodeMx invalid coords', ['q'=>$qq, 'lat'=>$lat, 'lng'=>$lng, 'hit'=>$j[0]]);
                return [null,null,null];
            }

            return [$lat, $lng, (string)($j[0]['display_name'] ?? null)];
        };

        // 2a) q completo
        if ($q !== '') {
            [$lat, $lng, $dn] = $tryQ($q);
            if ($this->validLatLng($lat, $lng)) return [$lat, $lng, $dn];
        }

        // 2b) fallback: CP + ciudad + estado
        $q2 = $this->normalizeText($this->ensureMexicoHint(
            implode(', ', $this->dedupeParts(array_filter([$cp, $ciudad, $estado], fn($x)=>trim((string)$x) !== '')))
        ));
        if ($q2 !== '' && $q2 !== $q) {
            [$lat, $lng, $dn] = $tryQ($q2);
            if ($this->validLatLng($lat, $lng)) return [$lat, $lng, $dn];
        }

        // 2c) fallback: calle + ciudad/estado (sin colonia)
        $q3 = $this->normalizeText($this->ensureMexicoHint(
            implode(', ', $this->dedupeParts(array_filter([$calle, $ciudad, $estado, $cp], fn($x)=>trim((string)$x) !== '')))
        ));
        if ($q3 !== '' && $q3 !== $q2) {
            [$lat, $lng, $dn] = $tryQ($q3);
            if ($this->validLatLng($lat, $lng)) return [$lat, $lng, $dn];
        }

        return [null, null, null];
    }

    /**
     * Intenta resolver coords:
     * 1) stop lat/lng
     * 2) provider lat/lng
     * 3) geocode por dirección (stop)
     * 4) geocode por dirección (provider)
     *
     * Retorna: [lat, lng, address_used, provider_id_used]
     */
    private function resolveStopLatLng(array $s): array
    {
        $lat = $this->normalizeCoord($s['lat'] ?? null);
        $lng = $this->normalizeCoord($s['lng'] ?? null);

        if ($this->validLatLng($lat, $lng)) {
            return [$lat, $lng, null, null];
        }

        $providerId = isset($s['provider_id']) && is_numeric($s['provider_id']) ? (int)$s['provider_id'] : null;
        $providerAddress = null;
        $providerParts = [];

        if ($providerId && Schema::hasTable('providers')) {
            $prov = DB::table('providers')->where('id', $providerId)->first();
            if ($prov) {
                $pLat = $this->normalizeCoord($prov->lat ?? null);
                $pLng = $this->normalizeCoord($prov->lng ?? null);

                if ($this->validLatLng($pLat, $pLng)) {
                    return [$pLat, $pLng, null, $providerId];
                }

                $providerParts = [
                    'calle'   => $prov->calle ?? null,
                    'colonia' => $prov->colonia ?? null,
                    'ciudad'  => $prov->ciudad ?? null,
                    'estado'  => $prov->estado ?? null,
                    'cp'      => $prov->cp ?? null,
                ];

                $providerAddress = $this->ensureMexicoHint(
                    implode(', ', $this->dedupeParts(array_filter([
                        $prov->calle ?? null,
                        $prov->colonia ?? null,
                        $prov->ciudad ?? null,
                        $prov->estado ?? null,
                        $prov->cp ?? null,
                    ], fn($x)=> trim((string)$x) !== '')))
                );
            }
        }

        $addr = $this->buildAddressFromStop($s);

        // 3) geocode stop
        if ($addr !== '') {
            [$gLat, $gLng] = $this->geocodeMx($addr, [
                'calle'   => $s['calle'] ?? null,
                'colonia' => $s['colonia'] ?? null,
                'ciudad'  => $s['ciudad'] ?? null,
                'estado'  => $s['estado'] ?? null,
                'cp'      => $s['cp'] ?? null,
            ]);
            if ($this->validLatLng($gLat, $gLng)) {
                return [$gLat, $gLng, $addr, $providerId];
            }
        }

        // 4) geocode provider
        if ($providerAddress) {
            [$gLat, $gLng] = $this->geocodeMx($providerAddress, $providerParts);
            if ($this->validLatLng($gLat, $gLng)) {
                return [$gLat, $gLng, $providerAddress, $providerId];
            }
        }

        return [null, null, $addr ?: $providerAddress, $providerId];
    }

    /* ============================
     | CRUD / Panel de logística
     * ============================ */

    public function index()
    {
        $this->canManage();

        $plans = RoutePlan::with('driver')
            ->withCount([
                'stops',
                'stops as done_stops_count' => fn($q) => $q->where('status', 'done'),
            ])
            ->latest()
            ->paginate(20);

        return view('logistics.routes.index', compact('plans'));
    }

    public function create()
    {
        $this->canManage();

        $orderCol = Schema::hasColumn('users', 'name') ? 'name'
            : (Schema::hasColumn('users', 'email') ? 'email' : 'id');

        $drivers = \App\Models\User::query()
            ->when(
                class_exists(\Spatie\Permission\Models\Role::class),
                fn($q) => $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'cliente_web'))
            )
            ->orderBy($orderCol)
            ->get();

        $providers = collect();

        if (Schema::hasTable('providers')) {
            $cols = Schema::getColumnListing('providers');

            $nameCols = ['name', 'nombre', 'razon_social', 'razon', 'empresa', 'provider_name', 'title'];
            $latCols  = ['lat', 'latitude', 'latitud', 'latitud_gps'];
            $lngCols  = ['lng', 'lon', 'long', 'longitude', 'longitud', 'longitud_gps'];

            $addrCols = ['calle','colonia','ciudad','estado','cp'];

            $pName = collect($nameCols)->first(fn($c) => in_array($c, $cols, true));
            $pLat  = collect($latCols)->first(fn($c) => in_array($c, $cols, true));
            $pLng  = collect($lngCols)->first(fn($c) => in_array($c, $cols, true));

            $select = ['id'];

            $select[] = $pName
                ? DB::raw("`{$pName}` as `name`")
                : DB::raw("CONCAT('Proveedor #', id) as `name`");

            $select[] = $pLat ? DB::raw("NULLIF(`{$pLat}`, 0) as `lat`") : DB::raw("NULL as `lat`");
            $select[] = $pLng ? DB::raw("NULLIF(`{$pLng}`, 0) as `lng`") : DB::raw("NULL as `lng`");

            foreach ($addrCols as $c) {
                if (in_array($c, $cols, true)) $select[] = DB::raw("`{$c}` as `{$c}`");
            }

            // concat address legible (sin duplicar tanto)
            $parts = [];
            foreach ($addrCols as $c) {
                if (in_array($c, $cols, true)) $parts[] = "NULLIF(TRIM(`{$c}`),'')";
            }
            $select[] = !empty($parts)
                ? DB::raw("CONCAT_WS(', ', " . implode(', ', $parts) . ") as `address`")
                : DB::raw("'' as `address`");

            $q = DB::table('providers')->select($select);

            // solo providers que tengan ALGO de dirección
            $q->where(function ($w) use ($addrCols) {
                foreach ($addrCols as $c) {
                    $w->orWhere(function ($ww) use ($c) {
                        $ww->whereNotNull($c)->whereRaw("TRIM(`{$c}`) <> ''");
                    });
                }
            });

            if ($pLat && $pLng) {
                $q->orderByRaw("(NULLIF(`{$pLat}`,0) is not null and NULLIF(`{$pLng}`,0) is not null) desc");
            }

            $q->orderBy('name');

            $providers = $q->get();
        }

        return view('logistics.routes.create', compact('drivers', 'providers'));
    }

    public function store(Request $r)
    {
        $this->canManage();

        $stopsInput = $r->input('stops');
        if (is_string($stopsInput)) {
            $decoded = json_decode($stopsInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $r->merge(['stops' => $decoded]);
            }
        }

        $data = $r->validate([
            'driver_id'            => ['required', 'exists:users,id'],
            'name'                 => ['nullable', 'string', 'max:180'],
            'stops'                => ['required', 'array', 'min:1'],
            'stops.*.name'         => ['nullable', 'string', 'max:180'],

            'stops.*.provider_id'  => ['nullable'],

            'stops.*.address'      => ['nullable', 'string', 'max:700'],
            'stops.*.calle'        => ['nullable', 'string', 'max:250'],
            'stops.*.colonia'      => ['nullable', 'string', 'max:250'],
            'stops.*.ciudad'       => ['nullable', 'string', 'max:250'],
            'stops.*.estado'       => ['nullable', 'string', 'max:250'],
            'stops.*.cp'           => ['nullable', 'string', 'max:20'],

            'stops.*.lat'          => ['nullable'],
            'stops.*.lng'          => ['nullable'],
        ]);

        $resolvedStops = [];
        $errors = [];

        foreach ($data['stops'] as $i => $s) {
            [$lat, $lng, $addrUsed, $providerIdUsed] = $this->resolveStopLatLng($s);

            if (!$this->validLatLng($lat, $lng)) {
                $name = trim((string)($s['name'] ?? ''));
                $who  = $name ?: ($addrUsed ?: 'sin nombre');
                $errors[] = "No se pudo obtener coordenadas para el punto #".($i+1)." ({$who}).";
                Log::warning('store() stop without coords', ['i'=>$i+1, 'stop'=>$s, 'addr_used'=>$addrUsed]);
                continue;
            }

            $resolvedStops[] = [
                'name'        => $s['name'] ?? null,
                'lat'         => (float)$lat,
                'lng'         => (float)$lng,
                'address'     => $addrUsed ?: $this->buildAddressFromStop($s),

                'calle'   => $s['calle'] ?? null,
                'colonia' => $s['colonia'] ?? null,
                'ciudad'  => $s['ciudad'] ?? null,
                'estado'  => $s['estado'] ?? null,
                'cp'      => $s['cp'] ?? null,

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
                'name'      => $data['name'] ?? null,
                'status'    => 'scheduled',
            ]);

            foreach ($resolvedStops as $i => $s) {
                $payload = [
                    'route_plan_id'  => $plan->id,
                    'name'           => $s['name'] ?: ('Punto '.($i+1)),
                    'lat'            => $s['lat'],
                    'lng'            => $s['lng'],
                    'sequence_index' => null,
                    'status'         => 'pending',
                ];

                // ✅ guarda extras si existen columnas
                if (Schema::hasColumn('route_stops', 'provider_id')) $payload['provider_id'] = $s['provider_id'];
                if (Schema::hasColumn('route_stops', 'address'))     $payload['address']     = $s['address'];
                if (Schema::hasColumn('route_stops', 'calle'))       $payload['calle']       = $s['calle'];
                if (Schema::hasColumn('route_stops', 'colonia'))     $payload['colonia']     = $s['colonia'];
                if (Schema::hasColumn('route_stops', 'ciudad'))      $payload['ciudad']      = $s['ciudad'];
                if (Schema::hasColumn('route_stops', 'estado'))      $payload['estado']      = $s['estado'];
                if (Schema::hasColumn('route_stops', 'cp'))          $payload['cp']          = $s['cp'];

                RouteStop::create($payload);

                // opcional: guarda coords al provider
                if (!empty($s['provider_id']) && Schema::hasTable('providers')) {
                    try {
                        DB::table('providers')->where('id', (int)$s['provider_id'])->update([
                            'lat' => $s['lat'],
                            'lng' => $s['lng'],
                            'updated_at' => now(),
                        ]);
                    } catch (\Throwable $e) {
                        Log::warning('store() provider update failed', [
                            'provider_id'=>$s['provider_id'],
                            'error'=>$e->getMessage(),
                        ]);
                    }
                }
            }

            return redirect()->route('routes.show', $plan)->with('ok', 'Ruta creada');
        });
    }

    public function show(RoutePlan $routePlan)
    {
        $this->canManage();
        $routePlan->load('driver', 'stops');
        return view('logistics.routes.show', compact('routePlan'));
    }

    /* ==================
     | Vista de Chofer
     * ================== */

    public function driver(RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        $routePlan->load('stops', 'driver');

        $stops = $routePlan->stops()
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get(['id', 'name', 'lat', 'lng', 'sequence_index', 'status']);

        Log::info('driver.routes.show view boot', [
            'plan_id' => $routePlan->id,
            'stops_count' => $stops->count(),
            'has_driver' => (bool) $routePlan->driver,
        ]);

        return view('driver.routes.show', compact('routePlan', 'stops'));
    }

    /* ============================================
     | GPS del chofer (persistencia de ubicación)
     * ============================================ */

    public function saveDriverLocation(Request $r)
    {
        $u = Auth::user(); abort_unless($u, 401);

        $data = $r->validate([
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'accuracy' => ['nullable', 'numeric'],
            'speed'    => ['nullable', 'numeric'],
            'heading'  => ['nullable', 'numeric'],
            'captured_at' => ['nullable', 'date'],
        ]);

        DriverPosition::create($data + [
            'user_id'     => $u->id,
            'captured_at' => $data['captured_at'] ?? now(),
        ]);

        return response()->json(['ok' => true], 200);
    }

    public function getDriverLocation()
    {
        $u = Auth::user(); abort_unless($u, 401);

        $last = DriverPosition::where('user_id', $u->id)
            ->latest('captured_at')
            ->first();

        return response()->json([
            'lat' => $last?->lat,
            'lng' => $last?->lng,
            'captured_at' => optional($last?->captured_at)->toIso8601String(),
        ], 200);
    }

    /* ============================
     | Cálculo / Re-cálculo (API)
     * ============================ */

 public function compute(Request $r, RoutePlan $routePlan)
{
    $this->canDrive($routePlan);

    $startLat = $this->normalizeCoord($r->input('start_lat'));
    $startLng = $this->normalizeCoord($r->input('start_lng'));

    $stops = $routePlan->stops()
        ->where('status', 'pending')
        ->orderByRaw('COALESCE(sequence_index, 999999), id')
        ->get();

    if ($stops->isEmpty()) {
        Log::info('compute() no pending stops', ['plan_id'=>$routePlan->id]);
        return response()->json(['message' => 'No hay paradas pendientes', 'routes' => []], 200);
    }

    if (!$this->validLatLng($startLat, $startLng)) {
        $last = DriverPosition::where('user_id', Auth::id())->latest('captured_at')->first();
        if (!$last) {
            Log::warning('compute() missing driver location', ['plan_id'=>$routePlan->id, 'user_id'=>Auth::id()]);
            return response()->json(['message' => 'No hay ubicación actual del chofer. Toca “Usar mi ubicación”.'], 422);
        }
        $startLat = $this->normalizeCoord($last->lat);
        $startLng = $this->normalizeCoord($last->lng);
    }

    $stopsValid = $stops->filter(function ($s) {
        $lat = $this->normalizeCoord($s->lat);
        $lng = $this->normalizeCoord($s->lng);
        return $this->validLatLng($lat, $lng);
    })->values();

    $invalidStops = $stops->count() - $stopsValid->count();

    if ($stopsValid->isEmpty()) {
        Log::warning('compute() no valid stops', [
            'plan_id'=>$routePlan->id,
            'pending'=>$stops->count(),
            'invalid'=>$invalidStops,
            'stops'=>$stops->map(fn($s)=>['id'=>$s->id,'lat'=>$s->lat,'lng'=>$s->lng,'name'=>$s->name])->values(),
        ]);
        return response()->json([
            'message' => 'No hay paradas pendientes con coordenadas válidas.',
            'routes'  => [],
        ], 200);
    }

    $coords = [];
    $coords[] = ['lat' => (float)$startLat, 'lng' => (float)$startLng];
    foreach ($stopsValid as $s) {
        $coords[] = [
            'lat' => (float)$this->normalizeCoord($s->lat),
            'lng' => (float)$this->normalizeCoord($s->lng),
        ];
    }

    Log::info('compute() boot', [
        'plan_id' => $routePlan->id,
        'user_id' => Auth::id(),
        'start'   => ['lat'=>$startLat, 'lng'=>$startLng],
        'pending_total' => $stops->count(),
        'pending_valid' => $stopsValid->count(),
        'pending_invalid'=> $invalidStops,
        'coords_count'  => count($coords),
    ]);

    if (count($coords) < 2) {
        return response()->json(['message' => 'No hay coordenadas suficientes para calcular ruta.', 'routes'=>[]], 422);
    }

    $principal = null;
    $alts = [];
    $legs = [];
    $ordered = $coords;
    $steps = [];

    try {
        // 1) TRIP (optimiza orden)
        $trip = $this->osrm->trip($coords, [
            'roundtrip'   => 'false',
            'destination' => 'last',
            'steps'       => 'true',
            'geometries'  => 'geojson',
            'overview'    => 'full',
        ]);

        Log::info('OSRM trip response', [
            'plan_id' => $routePlan->id,
            'code'    => $trip['code'] ?? null,
            'message' => $trip['message'] ?? null,
            'trips_count' => isset($trip['trips']) ? count($trip['trips']) : null,
        ]);

        if (($trip['code'] ?? '') === 'Ok'
            && !empty($trip['trips'][0]['waypoint_indices'])
            && is_array($trip['trips'][0]['waypoint_indices'])) {

            $route   = $trip['trips'][0];
            $indices = $route['waypoint_indices'];

            $tmp = [];
            foreach ($indices as $i) if (isset($coords[$i])) $tmp[] = $coords[$i];
            if (count($tmp) >= 2) $ordered = $tmp;

            $osrmLegs = $route['legs'] ?? [];
            $legs = [];
            for ($k=0; $k<count($osrmLegs); $k++){
                $from = $ordered[$k] ?? null;
                $to   = $ordered[$k+1] ?? null;
                if (!$from || !$to) break;
                $leg = $osrmLegs[$k];
                $legs[] = [
                    'from'=>$from,'to'=>$to,
                    'distance'=>(int)round($leg['distance'] ?? 0),
                    'duration'=>(int)round($leg['duration'] ?? 0),
                ];
            }

            $legs = $this->traffic->applyDelays($legs);
            $totalAdj = collect($legs)->sum('adj_duration');

            $steps = $this->formatStepsFromOsrm($route);

            $principal = [
                'label'     => 'ruta_principal',
                'geometry'  => $route['geometry'] ?? null,
                'legs'      => $legs,
                'total_sec' => $totalAdj ?: (int)round($route['duration'] ?? 0),
                'total_m'   => (int)round($route['distance'] ?? 0),
                'steps'     => $steps,
            ];
        }

        // 2) ROUTE fallback (sin optimizar)
        if (!$principal) {
            $routeRes = $this->osrm->route($ordered, [
                'alternatives' => 'false',
                'steps'        => 'true',
                'geometries'   => 'geojson',
                'overview'     => 'full',
            ]);

            Log::info('OSRM route fallback response', [
                'plan_id' => $routePlan->id,
                'code'    => $routeRes['code'] ?? null,
                'message' => $routeRes['message'] ?? null,
                'routes_count' => isset($routeRes['routes']) ? count($routeRes['routes']) : null,
            ]);

            if (($routeRes['code'] ?? '') !== 'Ok' || empty($routeRes['routes'][0])) {
                Log::error('OSRM invalid route', ['plan_id'=>$routePlan->id, 'detail'=>$routeRes]);
                return response()->json([
                    'message' => 'OSRM no devolvió una ruta válida.',
                    'detail'  => $routeRes,
                ], 422);
            }

            $r0 = $routeRes['routes'][0];

            $legs = [];
            $rlegs = $r0['legs'] ?? [];
            if (!empty($rlegs)) {
                for ($k=0; $k<count($rlegs); $k++){
                    $from = $ordered[$k] ?? null;
                    $to   = $ordered[$k+1] ?? null;
                    if (!$from || !$to) break;
                    $leg = $rlegs[$k];
                    $legs[] = [
                        'from'=>$from,'to'=>$to,
                        'distance'=>(int)round($leg['distance'] ?? 0),
                        'duration'=>(int)round($leg['duration'] ?? 0),
                    ];
                }
            } else {
                $legs[] = [
                    'from'=>$ordered[0],'to'=>end($ordered),
                    'distance'=>(int)round($r0['distance'] ?? 0),
                    'duration'=>(int)round($r0['duration'] ?? 0),
                ];
            }

            $legs = $this->traffic->applyDelays($legs);
            $totalAdj = collect($legs)->sum('adj_duration');

            $steps = $this->formatStepsFromOsrm($r0);

            $principal = [
                'label'     => 'ruta_principal',
                'geometry'  => $r0['geometry'] ?? null,
                'legs'      => $legs,
                'total_sec' => $totalAdj ?: (int)round($r0['duration'] ?? 0),
                'total_m'   => (int)round($r0['distance'] ?? 0),
                'steps'     => $steps,
            ];
        }

        // Alternativas best-effort
        try {
            $altRes = $this->osrm->route($ordered, [
                'alternatives' => 'true',
                'steps'        => 'false',
                'geometries'   => 'geojson',
                'overview'     => 'full',
            ]);

            Log::info('OSRM alternatives response', [
                'plan_id'=>$routePlan->id,
                'code'=>$altRes['code'] ?? null,
                'routes_count'=>isset($altRes['routes']) ? count($altRes['routes']) : null,
            ]);

            if (($altRes['code'] ?? '') === 'Ok') {
                foreach (($altRes['routes'] ?? []) as $idx => $rr) {
                    if ($idx === 0) continue;
                    $alts[] = [
                        'label'     => 'alternativa_' . $idx,
                        'geometry'  => $rr['geometry'] ?? null,
                        'legs'      => $principal['legs'],
                        'total_sec' => (int)round($rr['duration'] ?? ($principal['total_sec'] + 120*$idx)),
                        'total_m'   => (int)round($rr['distance'] ?? $principal['total_m']),
                    ];
                    if (count($alts) >= 2) break;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OSRM alternatives exception', ['plan_id'=>$routePlan->id, 'error'=>$e->getMessage()]);
        }

        $advice = $this->advisor->advise(array_merge([$principal], $alts), [
            'driver' => $routePlan->driver?->name,
            'route'  => $routePlan->name,
        ]);

        $exportLinks = $this->buildNavLinks($principal, $stopsValid, (float)$startLat, (float)$startLng);

        return response()->json([
            'plan_id'       => $routePlan->id,
            'ordered_stops' => $routePlan->stops()
                ->orderByRaw('COALESCE(sequence_index, 999999), id')
                ->get(['id','name','lat','lng','sequence_index','eta_seconds','status']),
            'routes'        => array_merge([$principal], $alts),
            'advice_md'     => $advice,
            'total_minutes' => (int)round(($principal['total_sec'] ?? 0) / 60),
            'export_links'  => $exportLinks,
        ], 200);

    } catch (\Throwable $e) {
        Log::error('compute() exception', [
            'plan_id'=>$routePlan->id,
            'user_id'=>Auth::id(),
            'error'=>$e->getMessage(),
        ]);

        return response()->json([
            'message' => 'Error calculando ruta (OSRM).',
            'error'   => $e->getMessage(),
        ], 422);
    }
}

    public function recompute(Request $r, RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);
        return $this->compute($r, $routePlan);
    }

    public function markStopDone(Request $r, RoutePlan $routePlan, RouteStop $stop)
    {
        $this->canDrive($routePlan);
        abort_unless($stop->route_plan_id === $routePlan->id, 404);

        $stop->update(['status' => 'done']);

        $pending = $routePlan->stops()->where('status', 'pending')->count();
        $routePlan->update(['status' => $pending === 0 ? 'done' : 'in_progress']);

        return response()->json(['ok' => true], 200);
    }

    /* =========================
     | Helpers privados
     * ========================= */

    private function formatStepsFromOsrm(array $route): array
    {
        $out = [];
        $legs = $route['legs'] ?? [];
        foreach ($legs as $leg) {
            $slist = $leg['steps'] ?? [];
            foreach ($slist as $st) {
                $maneuver = $st['maneuver']['type'] ?? '';
                $modifier = $st['maneuver']['modifier'] ?? '';
                $roadName = $st['name'] ?? '';
                $distance = (int) round($st['distance'] ?? 0);
                $duration = (int) round($st['duration'] ?? 0);

                $instr = $this->humanInstruction($maneuver, $modifier, $roadName);
                $out[] = [
                    'instruction' => $instr,
                    'name'        => $roadName,
                    'distance'    => $distance,
                    'duration'    => $duration,
                ];
            }
        }
        return $out;
    }

    private function humanInstruction(string $type, string $modifier, string $name): string
    {
        $dir = match ($modifier) {
            'left' => 'a la izquierda',
            'right' => 'a la derecha',
            'slight_left' => 'ligeramente a la izquierda',
            'slight_right' => 'ligeramente a la derecha',
            'sharp_left' => 'giro cerrado a la izquierda',
            'sharp_right' => 'giro cerrado a la derecha',
            'uturn' => 'retorno',
            default => ''
        };

        $on = $name ? " sobre {$name}" : '';

        return match ($type) {
            'depart'  => 'Inicia' . $on,
            'arrive'  => 'Llegada' . $on,
            'turn'    => $dir ? "Gira {$dir}{$on}" : "Gira{$on}",
            'merge'   => "Incorpórate{$on}",
            'ramp'    => "Toma la rampa{$on}",
            'roundabout' => "Toma la glorieta{$on}",
            'fork'    => "Mantente en la bifurcación{$on}",
            'continue' => "Continúa{$on}",
            default   => ucfirst($type) . $on,
        };
    }

    private function buildNavLinks(array $principal, $pendingStops, float $startLat, float $startLng): array
    {
        $ordered = collect($pendingStops)->sortBy(function ($s) {
            $si = $s->sequence_index ?? 999999;
            return sprintf("%06d-%06d", $si, $s->id);
        })->values();

        $next = $ordered->first();
        if (!$next) return [];

        $origin = $startLat . ',' . $startLng;
        $dest   = $next->lat . ',' . $next->lng;

        $gmaps = 'https://www.google.com/maps/dir/?api=1'
            . '&origin=' . urlencode($origin)
            . '&destination=' . urlencode($dest)
            . '&travelmode=driving&dir_action=navigate';

        $waze = 'https://waze.com/ul?ll=' . urlencode($dest) . '&navigate=yes&zoom=17';

        return [
            'maps_principal' => $gmaps,
            'waze_next'      => $waze,
        ];
    }
}
