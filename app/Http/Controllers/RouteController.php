<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\RoutePlan;
use App\Models\RouteStop;
use App\Services\OsrmClient;
use App\Services\RouteAiAdvisor;
use App\Services\TrafficService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoutePlanController extends Controller
{
    public function __construct(
        protected OsrmClient $osrm,
        protected TrafficService $traffic,
        protected RouteAiAdvisor $advisor,
    ) {
        // $this->middleware('auth'); // descomenta si quieres forzar auth global
    }

    /* =========================================================================
     | Permisos simples
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
     | Panel / CRUD logístico
     * ========================= */

    public function index()
    {
        $this->canManage();

        $plans = RoutePlan::with('driver')
            ->withCount([
                'stops',
                'stops as done_stops_count' => fn($q) => $q->where('status','done'),
            ])
            ->latest()
            ->paginate(20);

        return view('logistics.routes.index', compact('plans'));
    }

    public function create()
    {
        $this->canManage();

        $orderCol = Schema::hasColumn('users', 'name') ? 'name' : (Schema::hasColumn('users','email') ? 'email' : 'id');

        $drivers = \App\Models\User::query()
            ->when(
                class_exists(\Spatie\Permission\Models\Role::class),
                fn($q) => $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'cliente_web'))
            )
            ->orderBy($orderCol)
            ->get();

        $providers = collect();
        if (Schema::hasTable('providers')) {
            $nameCols = ['name','nombre','razon_social','razon','empresa','provider_name','title'];
            $latCols  = ['lat','latitude','latitud','latitud_gps'];
            $lngCols  = ['lng','lon','long','longitude','longitud','longitud_gps'];

            $cols  = Schema::getColumnListing('providers');
            $pName = collect($nameCols)->first(fn($c) => in_array($c, $cols, true));
            $pLat  = collect($latCols)->first(fn($c) => in_array($c, $cols, true));
            $pLng  = collect($lngCols)->first(fn($c) => in_array($c, $cols, true));

            if ($pLat && $pLng) {
                $select = ['id'];
                $select[] = $pName ? DB::raw("`{$pName}` as `name`") : DB::raw("'' as `name`");
                $select[] = DB::raw("`{$pLat}` as `lat`");
                $select[] = DB::raw("`{$pLng}` as `lng`");

                $providers = DB::table('providers')
                    ->select($select)
                    ->whereNotNull($pLat)
                    ->whereNotNull($pLng)
                    ->get();
            }
        }

        return view('logistics.routes.create', compact('drivers','providers'));
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
            'driver_id'        => ['required','exists:users,id'],
            'name'             => ['nullable','string','max:180'],
            'stops'            => ['required','array','min:1'],
            'stops.*.name'     => ['nullable','string','max:180'],
            'stops.*.lat'      => ['required','numeric'],
            'stops.*.lng'      => ['required','numeric'],
        ]);

        return DB::transaction(function () use ($data) {
            $plan = RoutePlan::create([
                'driver_id' => $data['driver_id'],
                'name'      => $data['name'] ?? null,
                'status'    => 'scheduled',
            ]);

            foreach ($data['stops'] as $i => $s) {
                RouteStop::create([
                    'route_plan_id'  => $plan->id,
                    'name'           => $s['name'] ?? ('Punto '.($i+1)),
                    'lat'            => $s['lat'],
                    'lng'            => $s['lng'],
                    'sequence_index' => null,
                    'status'         => 'pending',
                ]);
            }

            return redirect()->route('routes.show', $plan)->with('ok','Ruta creada');
        });
    }

    public function show(RoutePlan $routePlan)
    {
        $this->canManage();

        $routePlan->load('driver','stops');
        return view('logistics.routes.show', compact('routePlan'));
    }

    /* ==================
     | Vista de Chofer
     * ================== */

    public function driver(RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        $routePlan->load('stops','driver');

        $stops = $routePlan->stops()
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get(['id','name','lat','lng','sequence_index','status','eta_seconds']);

        return view('driver.routes.show', compact('routePlan','stops'));
    }

    /* ===========================
     | Cálculo / Re-cálculo API
     * =========================== */

    public function compute(Request $r, RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        $data = $r->validate([
            'start_lat' => ['required','numeric'],
            'start_lng' => ['required','numeric'],
            'fuel'      => ['nullable','in:gasoline,diesel'], // para costo de combustible
        ]);

        try {
            $stops = $routePlan->stops()
                ->where('status','pending')
                ->orderByRaw('COALESCE(sequence_index, 999999), id')
                ->get();

            if ($stops->isEmpty()) {
                return response()->json(['message'=>'No hay paradas pendientes','routes'=>[]], 200);
            }

            // 1) origen + paradas (para /trip)
            $coords = [];
            $coords[] = ['lat'=>$data['start_lat'], 'lng'=>$data['start_lng']];
            foreach ($stops as $s) $coords[] = ['lat'=>$s->lat,'lng'=>$s->lng];

            // 2) TRIP (orden óptimo)
            $trip = $this->osrm->trip($coords, ['roundtrip'=>'false','destination'=>'last']);

            $ordered = $coords; // fallback
            $principal = null;
            $alts = [];

            if (($trip['code'] ?? '') === 'Ok'
                && !empty($trip['trips'][0]['waypoint_indices'])
                && is_array($trip['trips'][0]['waypoint_indices'])) {

                $route  = $trip['trips'][0];
                $idxs   = $route['waypoint_indices'];

                // Ordenar coords según indices
                $tmp = [];
                foreach ($idxs as $i) { if (isset($coords[$i])) $tmp[] = $coords[$i]; }
                if (count($tmp) >= 2) $ordered = $tmp;

                // Legs desde /trip
                $legs = [];
                $osrmLegs = $route['legs'] ?? [];
                for ($k=0; $k<count($osrmLegs); $k++) {
                    $from = $ordered[$k] ?? null;
                    $to   = $ordered[$k+1] ?? null;
                    if (!$from || !$to) break;
                    $leg = $osrmLegs[$k];
                    $legs[] = [
                        'from'     => $from,
                        'to'       => $to,
                        'distance' => (int) round($leg['distance'] ?? 0),
                        'duration' => (int) round($leg['duration'] ?? 0),
                    ];
                }

                // Tráfico por tramo
                $legs = $this->traffic->applyDelays($legs);
                $totalAdj = array_sum(array_map(fn($l)=> $l['adj_duration'] ?? $l['duration'] ?? 0, $legs));
                $totalM   = (int) round($route['distance'] ?? array_sum(array_map(fn($l)=>$l['distance']??0,$legs)));

                $principal = [
                    'label'      => 'ruta_principal',
                    'geometry'   => $route['geometry'] ?? null,
                    'legs'       => $legs,
                    'total_sec'  => (int) $totalAdj,
                    'total_m'    => $totalM,
                ];
            }

            // 3) Fallback a /route si /trip no sirvió
            if (!$principal) {
                $routeAltSimple = $this->osrm->route($ordered, ['alternatives'=>'false']);
                if (($routeAltSimple['code'] ?? '') !== 'Ok' || empty($routeAltSimple['routes'][0])) {
                    return response()->json([
                        'message' => 'OSRM no devolvió una ruta válida.',
                        'detail'  => $routeAltSimple,
                    ], 422);
                }
                $r0 = $routeAltSimple['routes'][0];

                $legs = [];
                foreach (($r0['legs'] ?? []) as $k => $leg) {
                    $from = $ordered[$k] ?? null;
                    $to   = $ordered[$k+1] ?? null;
                    if (!$from || !$to) break;
                    $legs[] = [
                        'from'     => $from,
                        'to'       => $to,
                        'distance' => (int) round($leg['distance'] ?? 0),
                        'duration' => (int) round($leg['duration'] ?? 0),
                    ];
                }
                if (!$legs && isset($r0['distance'],$r0['duration'])) {
                    $legs[] = [
                        'from'     => $ordered[0],
                        'to'       => end($ordered),
                        'distance' => (int) round($r0['distance']),
                        'duration' => (int) round($r0['duration']),
                    ];
                }

                $legs = $this->traffic->applyDelays($legs);
                $totalAdj = array_sum(array_map(fn($l)=> $l['adj_duration'] ?? $l['duration'] ?? 0, $legs));
                $totalM   = (int) round($r0['distance'] ?? array_sum(array_map(fn($l)=>$l['distance']??0,$legs)));

                $principal = [
                    'label'      => 'ruta_principal',
                    'geometry'   => $r0['geometry'] ?? null,
                    'legs'       => $legs,
                    'total_sec'  => (int) $totalAdj,
                    'total_m'    => $totalM,
                ];
            }

            // 4) Alternativas reales si el backend las soporta
            try {
                $routeAlt = $this->osrm->route($ordered, ['alternatives'=>'true']);
                if (($routeAlt['code'] ?? '') === 'Ok') {
                    foreach (($routeAlt['routes'] ?? []) as $idx => $rr) {
                        if ($idx === 0) continue;
                        $alts[] = [
                            'label'     => 'alternativa_'.$idx,
                            'geometry'  => $rr['geometry'] ?? null,
                            'legs'      => $principal['legs'], // reusamos legs base para pintar tráfico
                            'total_sec' => (int) round($rr['duration'] ?? ($principal['total_sec'] + 120*$idx)),
                            'total_m'   => (int) round($rr['distance'] ?? $principal['total_m']),
                        ];
                        if (count($alts) >= 2) break;
                    }
                }
            } catch (\Throwable $e) {
                // ignoramos si no hay alternativas
            }

            // Si solo hay 1, creamos alternativas sintéticas (+2/+4 min)
            if (count($alts) < 2) {
                $alts[] = [
                    'label'     => 'alternativa_1',
                    'geometry'  => $principal['geometry'],
                    'legs'      => $principal['legs'],
                    'total_sec' => $principal['total_sec'] + 120,
                    'total_m'   => $principal['total_m'],
                ];
                $alts[] = [
                    'label'     => 'alternativa_2',
                    'geometry'  => $principal['geometry'],
                    'legs'      => $principal['legs'],
                    'total_sec' => $principal['total_sec'] + 240,
                    'total_m'   => $principal['total_m'],
                ];
            }

            // 5) Combustible + Peajes (estimado) para todas las rutas
            $fuelType = $data['fuel'] ?? 'gasoline';
            $all = array_merge([$principal], $alts);
            foreach ($all as &$rte) {
                $rte['fuel'] = $this->estimateFuel($rte['total_m'], $fuelType);
                $rte['toll'] = $this->estimateTollsStub($rte); // “libre” por defecto
            }
            unset($rte);
            [$principal, $alts] = [array_shift($all), $all];

            // 6) ETAs por stop usando legs de la principal (índices protegidos)
            $seqStops = $routePlan->stops()
                ->whereNotNull('sequence_index')
                ->orderBy('sequence_index')
                ->get();

            foreach ($seqStops as $i => $stop) {
                $eta = $principal['legs'][$i]['adj_duration'] ?? $principal['legs'][$i]['duration'] ?? null;
                $stop->update(['eta_seconds' => $eta]);
            }

            // 7) IA rica (explica combustible, peajes, tráfico)
            $advice = $this->advisor->advise(array_merge([$principal], $alts), [
                'driver' => $routePlan->driver?->name,
                'route'  => $routePlan->name,
            ]);

            // 8) Deep-links
            $orderedStops = $routePlan->stops()
                ->orderBy('sequence_index')
                ->get(['id','name','lat','lng','sequence_index','eta_seconds','status']);

            $orderedCoords = $this->coordsFromStopsWithOrigin($orderedStops, $data['start_lat'], $data['start_lng']);
            $links = $this->buildDeepLinks($orderedStops->all(), $orderedCoords);

            return response()->json([
                'plan_id'        => $routePlan->id,
                'ordered_stops'  => $orderedStops,
                'routes'         => array_merge([$principal], $alts),
                'advice_md'      => $advice,
                'total_minutes'  => (int) round(($principal['total_sec'] ?? 0) / 60),
                'export_links'   => $links,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Error de cálculo de ruta',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function recompute(Request $r, RoutePlan $routePlan)
    {
        $this->canDrive($routePlan);

        $r->validate([
            'start_lat' => ['required','numeric'],
            'start_lng' => ['required','numeric'],
        ]);

        return $this->compute($r, $routePlan);
    }

    public function markStopDone(Request $r, RoutePlan $routePlan, RouteStop $stop)
    {
        $this->canDrive($routePlan);
        abort_unless($stop->route_plan_id === $routePlan->id, 404);

        $stop->update(['status' => 'done']);

        $pending = $routePlan->stops()->where('status','pending')->count();
        $routePlan->update(['status' => $pending === 0 ? 'done' : 'in_progress']);

        return response()->json(['ok' => true], 200);
    }

    /* ======================
     | Helpers internos
     * ====================== */

    /**
     * Estimación simple de combustible.
     * Env opcionales:
     *   FUEL_PRICE_MXN (gasolina), FUEL_PRICE_DIESEL_MXN, VEHICLE_KM_PER_L, VEHICLE_KM_PER_L_DIESEL
     */
    private function estimateFuel(int $meters, string $fuel = 'gasoline'): array
    {
        $price = $fuel === 'diesel'
            ? (float) env('FUEL_PRICE_DIESEL_MXN', 27)
            : (float) env('FUEL_PRICE_MXN', 25.5);

        $kmPerL = $fuel === 'diesel'
            ? (float) env('VEHICLE_KM_PER_L_DIESEL', 9)
            : (float) env('VEHICLE_KM_PER_L', 11);

        $km = max(0, $meters) / 1000.0;
        $liters = $kmPerL > 0 ? $km / $kmPerL : 0;
        $mxn = $liters * $price;

        return [
            'km'       => round($km, 1),
            'liters'   => round($liters, 2),
            'price'    => round($price, 2),
            'mxn'      => round($mxn, 2),
            'fuel'     => $fuel,
            'km_per_l' => $kmPerL,
        ];
    }

    /**
     * Stub de peajes: devuelve “libre”. Sustituye cuando conectes proveedor real.
     */
    private function estimateTollsStub(array $route): array
    {
        return [
            'has_toll'       => false,
            'estimated_mxn'  => 0.0,
            'mode'           => 'stub',
            'note'           => 'Estimación sin proveedor; considera 0 en peajes.',
        ];
    }

    /**
     * Genera deep-links (Google Maps con waypoints y Waze al siguiente).
     */
    private function buildDeepLinks($orderedStops, array $orderedCoords): array
    {
        $stops = is_array($orderedStops) ? $orderedStops : $orderedStops->all();
        $pending = array_values(array_filter($stops, fn($s)=>($s['status'] ?? $s->status ?? null) !== 'done'));
        if (empty($pending)) return [];

        $origin = $orderedCoords[0];
        $dest   = end($orderedCoords);

        $wp = [];
        foreach (array_slice($orderedCoords, 1) as $i => $c) {
            if ($i >= 8) break; // límite común de waypoints en móvil
            $wp[] = $c['lat'].','.$c['lng'];
        }
        $o = $origin['lat'].','.$origin['lng'];
        $d = $dest['lat'].','.$dest['lng'];
        $wpStr = implode('|', $wp);

        $gmaps = "https://www.google.com/maps/dir/?api=1&travelmode=driving&origin=".rawurlencode($o)
               ."&destination=".rawurlencode($d).($wpStr ? "&waypoints=".rawurlencode($wpStr) : "");

        $first = $pending[0];
        $lat = $first['lat'] ?? $first->lat;
        $lng = $first['lng'] ?? $first->lng;
        $wazeNext = "https://waze.com/ul?ll=".rawurlencode($lat.','.$lng)."&navigate=yes";

        return [
            'maps_principal' => $gmaps,
            'waze_next'      => $wazeNext,
        ];
    }

    /**
     * Construye array de coords (incluye origen GPS) para deep-links y bounds.
     */
    private function coordsFromStopsWithOrigin($orderedStops, float $startLat, float $startLng): array
    {
        $out = [];
        $out[] = ['lat'=>$startLat,'lng'=>$startLng];
        foreach ($orderedStops as $s) {
            $out[] = ['lat'=>(float) ($s->lat ?? $s['lat']), 'lng'=>(float) ($s->lng ?? $s['lng'])];
        }
        return $out;
    }
}
