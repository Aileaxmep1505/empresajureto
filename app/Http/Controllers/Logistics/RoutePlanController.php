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
use Illuminate\Support\Facades\Schema;

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

    /* ============================
     | CRUD / Panel de logística
     * ============================ */

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

        $orderCol = Schema::hasColumn('users', 'name') ? 'name'
                  : (Schema::hasColumn('users','email') ? 'email' : 'id');

        $drivers = \App\Models\User::query()
            ->when(
                class_exists(\Spatie\Permission\Models\Role::class),
                fn($q) => $q->whereDoesntHave('roles', fn($r) => $r->where('name', 'cliente_web'))
            )
            ->orderBy($orderCol)
            ->get();

        /**
         * ✅ Providers: ahora NO se filtran por lat/lng.
         * Se filtran por tener dirección (calle/colonia/ciudad/estado/cp),
         * y si existen lat/lng se traen (pueden venir NULL).
         */
        $providers = collect();

        if (Schema::hasTable('providers')) {
            $cols = Schema::getColumnListing('providers');

            // Posibles nombres de columnas
            $nameCols = ['name','nombre','razon_social','razon','empresa','provider_name','title'];
            $latCols  = ['lat','latitude','latitud','latitud_gps'];
            $lngCols  = ['lng','lon','long','longitude','longitud','longitud_gps'];

            // Columnas de dirección (las que ya tienes)
            $addrCols = [
                'calle','street',
                'colonia','neighborhood',
                'ciudad','city',
                'estado','state',
                'cp','zip','codigo_postal',
            ];

            $pName = collect($nameCols)->first(fn($c) => in_array($c, $cols, true));
            $pLat  = collect($latCols)->first(fn($c) => in_array($c, $cols, true));
            $pLng  = collect($lngCols)->first(fn($c) => in_array($c, $cols, true));

            // Dirección: columnas realmente existentes en tu tabla
            $addrPresent = collect($addrCols)->filter(fn($c) => in_array($c, $cols, true))->values()->all();

            $select = ['id'];

            // Alias uniformes para tu blade
            $select[] = $pName ? DB::raw("`{$pName}` as `name`") : DB::raw("CONCAT('Proveedor #', id) as `name`");
            $select[] = $pLat  ? DB::raw("`{$pLat}` as `lat`")   : DB::raw("NULL as `lat`");
            $select[] = $pLng  ? DB::raw("`{$pLng}` as `lng`")   : DB::raw("NULL as `lng`");

            // Address armado (bonito) para mostrar en UI si no hay lat/lng
            if (!empty($addrPresent)) {
                $parts = array_map(fn($c) => "NULLIF(TRIM(`{$c}`),'')", $addrPresent);
                $select[] = DB::raw("CONCAT_WS(', ', " . implode(', ', $parts) . ") as `address`");
            } else {
                $select[] = DB::raw("'' as `address`");
            }

            $q = DB::table('providers')->select($select);

            // ✅ FILTRO: que tenga dirección útil (cualquiera de esas columnas)
            if (!empty($addrPresent)) {
                $q->where(function ($w) use ($addrPresent) {
                    foreach ($addrPresent as $c) {
                        $w->orWhere(function ($ww) use ($c) {
                            $ww->whereNotNull($c)->whereRaw("TRIM(`{$c}`) <> ''");
                        });
                    }
                });
            }

            // Opcional: primero los que ya tienen lat/lng (si existen)
            if ($pLat && $pLng) {
                $q->orderByRaw("(`{$pLat}` is not null and `{$pLng}` is not null) desc");
            }

            $q->orderBy('name');

            $providers = $q->get();
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
            ->get(['id','name','lat','lng','sequence_index','status']);

        return view('driver.routes.show', compact('routePlan','stops'));
    }

    /* ============================================
     | GPS del chofer (persistencia de ubicación)
     * ============================================ */

    /** Guarda última ubicación del chofer (llámalo desde watchPosition). */
    public function saveDriverLocation(Request $r)
    {
        $u = Auth::user(); abort_unless($u, 401);

        $data = $r->validate([
            'lat' => ['required','numeric'],
            'lng' => ['required','numeric'],
            'accuracy' => ['nullable','numeric'],
            'speed'    => ['nullable','numeric'],
            'heading'  => ['nullable','numeric'],
            'captured_at' => ['nullable','date'],
        ]);

        DriverPosition::create($data + [
            'user_id'     => $u->id,
            'captured_at' => $data['captured_at'] ?? now(),
        ]);

        return response()->json(['ok' => true], 200);
    }

    /** Devuelve última ubicación persistida del chofer. */
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

        $startLat = $r->input('start_lat');
        $startLng = $r->input('start_lng');

        $stops = $routePlan->stops()
            ->where('status','pending')
            ->orderByRaw('COALESCE(sequence_index, 999999), id')
            ->get();

        if ($stops->isEmpty()) {
            return response()->json(['message'=>'No hay paradas pendientes','routes'=>[]], 200);
        }

        if (!is_numeric($startLat) || !is_numeric($startLng)) {
            $last = DriverPosition::where('user_id', Auth::id())->latest('captured_at')->first();
            if (!$last) {
                return response()->json(['message'=>'No hay ubicación actual del chofer. Toca “Usar mi ubicación”.'], 422);
            }
            $startLat = $last->lat;
            $startLng = $last->lng;
        }

        $coords = [];
        $coords[] = ['lat'=>$startLat,'lng'=>$startLng];
        foreach ($stops as $s) {
            $coords[] = ['lat'=>$s->lat,'lng'=>$s->lng];
        }

        $principal = null;
        $alts      = [];
        $legs      = [];
        $ordered   = $coords;
        $steps     = [];

        try {
            $trip = $this->osrm->trip($coords, [
                'roundtrip'   => 'false',
                'destination' => 'last',
                'steps'       => 'true',
                'geometries'  => 'geojson',
                'overview'    => 'full',
            ]);

            if (($trip['code'] ?? '') === 'Ok'
                && !empty($trip['trips'])
                && !empty($trip['trips'][0]['waypoint_indices'])
                && is_array($trip['trips'][0]['waypoint_indices'])) {

                $route   = $trip['trips'][0];
                $indices = $route['waypoint_indices'] ?? [];

                $tmp = [];
                foreach ($indices as $i) {
                    if (isset($coords[$i])) $tmp[] = $coords[$i];
                }
                if (count($tmp) >= 2) $ordered = $tmp;

                $osrmLegs = $route['legs'] ?? [];
                $legs = [];
                for ($k = 0; $k < count($osrmLegs); $k++) {
                    $from = $ordered[$k]   ?? null;
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

                $legs = $this->traffic->applyDelays($legs);
                $totalAdj = collect($legs)->sum('adj_duration');

                $steps = $this->formatStepsFromOsrm($route);

                $principal = [
                    'label'      => 'ruta_principal',
                    'geometry'   => $route['geometry'] ?? null,
                    'legs'       => $legs,
                    'total_sec'  => $totalAdj ?: (int) round($route['duration'] ?? 0),
                    'total_m'    => (int) round($route['distance'] ?? 0),
                    'steps'      => $steps,
                ];
            }

            if (!$principal) {
                $routeAltSimple = $this->osrm->route($ordered, [
                    'alternatives'=>'false',
                    'steps'       =>'true',
                    'geometries'  =>'geojson',
                    'overview'    =>'full',
                ]);
                if (($routeAltSimple['code'] ?? '') !== 'Ok' || empty($routeAltSimple['routes'][0])) {
                    return response()->json([
                        'message' => 'OSRM no devolvió una ruta válida.',
                        'detail'  => $routeAltSimple,
                    ], 422);
                }
                $r0 = $routeAltSimple['routes'][0];

                $legs = [];
                $rlegs = $r0['legs'] ?? [];
                if (!empty($rlegs)) {
                    for ($k = 0; $k < count($rlegs); $k++) {
                        $from = $ordered[$k]   ?? null;
                        $to   = $ordered[$k+1] ?? null;
                        if (!$from || !$to) break;
                        $leg = $rlegs[$k];
                        $legs[] = [
                            'from'     => $from,
                            'to'       => $to,
                            'distance' => (int) round($leg['distance'] ?? 0),
                            'duration' => (int) round($leg['duration'] ?? 0),
                        ];
                    }
                } else {
                    $legs[] = [
                        'from'     => $ordered[0],
                        'to'       => end($ordered),
                        'distance' => (int) round($r0['distance'] ?? 0),
                        'duration' => (int) round($r0['duration'] ?? 0),
                    ];
                }

                $legs = $this->traffic->applyDelays($legs);
                $totalAdj = collect($legs)->sum('adj_duration');

                $steps = $this->formatStepsFromOsrm($r0);

                $principal = [
                    'label'      => 'ruta_principal',
                    'geometry'   => $r0['geometry'] ?? null,
                    'legs'       => $legs,
                    'total_sec'  => $totalAdj ?: (int) round($r0['duration'] ?? 0),
                    'total_m'    => (int) round($r0['distance'] ?? 0),
                    'steps'      => $steps,
                ];
            }

            try {
                $routeAlt = $this->osrm->route($ordered, [
                    'alternatives'=>'true',
                    'steps'       =>'false',
                    'geometries'  =>'geojson',
                    'overview'    =>'full',
                ]);
                if (($routeAlt['code'] ?? '') === 'Ok') {
                    foreach (($routeAlt['routes'] ?? []) as $idx => $rr) {
                        if ($idx === 0) continue;
                        $alts[] = [
                            'label'     => 'alternativa_'.$idx,
                            'geometry'  => $rr['geometry'] ?? null,
                            'legs'      => $principal['legs'],
                            'total_sec' => (int) round($rr['duration'] ?? ($principal['total_sec'] + 120*$idx)),
                            'total_m'   => (int) round($rr['distance'] ?? $principal['total_m'] ),
                        ];
                        if (count($alts) >= 2) break;
                    }
                }
            } catch (\Throwable $e) {
                // Ignorar si el backend no soporta alternativas
            }

            $seqStops = $routePlan->stops()
                ->whereNotNull('sequence_index')
                ->orderBy('sequence_index')
                ->get();

            foreach ($seqStops as $i => $stop) {
                $eta = $legs[$i]['adj_duration'] ?? $legs[$i]['duration'] ?? null;
                $stop->update(['eta_seconds' => $eta]);
            }

            $advice = $this->advisor->advise(array_merge([$principal], $alts), [
                'driver' => $routePlan->driver?->name,
                'route'  => $routePlan->name,
            ]);

            $exportLinks = $this->buildNavLinks($principal, $stops, (float)$startLat, (float)$startLng);

            return response()->json([
                'plan_id'       => $routePlan->id,
                'ordered_stops' => $routePlan->stops()
                    ->orderBy('sequence_index')
                    ->get(['id','name','lat','lng','sequence_index','eta_seconds','status']),
                'routes'        => array_merge([$principal], $alts),
                'advice_md'     => $advice,
                'total_minutes' => (int) round(($principal['total_sec'] ?? 0) / 60),
                'export_links'  => $exportLinks,
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
                    'name'       => $roadName,
                    'distance'   => $distance,
                    'duration'   => $duration,
                ];
            }
        }
        return $out;
    }

    private function humanInstruction(string $type, string $modifier, string $name): string
    {
        $dir = match($modifier) {
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

        return match($type) {
            'depart'  => 'Inicia' . $on,
            'arrive'  => 'Llegada' . $on,
            'turn'    => $dir ? "Gira {$dir}{$on}" : "Gira{$on}",
            'merge'   => "Incorpórate{$on}",
            'ramp'    => "Toma la rampa{$on}",
            'roundabout' => "Toma la glorieta{$on}",
            'fork'    => "Mantente en la bifurcación{$on}",
            'continue'=> "Continúa{$on}",
            default   => ucfirst($type) . $on,
        };
    }

    private function buildNavLinks(array $principal, $pendingStops, float $startLat, float $startLng): array
    {
        $ordered = collect($pendingStops)->sortBy(function($s){
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

        $waze  = 'https://waze.com/ul?ll=' . urlencode($dest) . '&navigate=yes&zoom=17';

        return [
            'maps_principal' => $gmaps,
            'waze_next'      => $waze,
        ];
    }
}
