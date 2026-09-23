<?php

namespace App\Http\Controllers\PartContable;

use App\Http\Controllers\Controller;
use App\Models\UserActivity;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function all(Request $request)
    {
        // Gate admin si aplica
        // abort_unless(auth()->user()?->hasRole('admin'), 403);

        $q = trim((string) $request->get('q', ''));
        $companyId = $request->get('company_id');
        $action = $request->get('action');
        $userId = $request->get('user_id');

        $baseQuery = UserActivity::query()
            ->with([
                'user:id,name,email',
                'company:id,name,slug',
                'document',
            ])
            ->when($companyId, fn ($qq) => $qq->where('company_id', $companyId))
            ->when($action, fn ($qq) => $qq->where('action', $action))
            ->when($userId, fn ($qq) => $qq->where('user_id', $userId))
            ->when($q, function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('ip', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%")
                        ->orWhere('module', 'like', "%{$q}%")
                        ->orWhere('screen', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('path', 'like', "%{$q}%")
                        ->orWhere('route', 'like', "%{$q}%")
                        ->orWhere('method', 'like', "%{$q}%")
                        ->orWhere('status_code', 'like', "%{$q}%")
                        ->orWhere('referer', 'like', "%{$q}%")
                        ->orWhere('request_id', 'like', "%{$q}%")
                        ->orWhere('session_id', 'like', "%{$q}%")
                        ->orWhere('meta', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('name', 'like', "%{$q}%")
                              ->orWhere('email', 'like', "%{$q}%");
                        })
                        ->orWhereHas('company', function ($c) use ($q) {
                            $c->where('name', 'like', "%{$q}%")
                              ->orWhere('slug', 'like', "%{$q}%");
                        })
                        ->orWhereHas('document', function ($d) use ($q) {
                            $d->where('title', 'like', "%{$q}%");

                            if (\Schema::hasColumn($d->getModel()->getTable(), 'filename')) {
                                $d->orWhere('filename', 'like', "%{$q}%");
                            }

                            if (\Schema::hasColumn($d->getModel()->getTable(), 'original_name')) {
                                $d->orWhere('original_name', 'like', "%{$q}%");
                            }
                        });
                });
            })
            ->latest('id');

        $total = (clone $baseQuery)->count();
        $perPage = max(1, min($total ?: 1, 50000));

        $rows = $baseQuery->paginate($perPage)->withQueryString();

        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $actions = UserActivity::query()
            ->select('action')
            ->whereNotNull('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('partcontable.activity_all', compact(
            'rows',
            'companies',
            'users',
            'actions',
            'q',
            'companyId',
            'action',
            'userId'
        ));
    }

    /**
     * Analíticas del registro de actividad: lo que más se hace, quién lo hace,
     * actividad en el tiempo y tiempo activo por usuario (todo el historial o
     * un rango de días).
     */
    public function analytics(Request $request)
    {
        $rango = (string) $request->get('rango', '30'); // 7 | 30 | 90 | todo
        $desde = in_array($rango, ['7', '30', '90'], true)
            ? now()->subDays((int) $rango)->startOfDay()
            : null;

        $base = fn () => UserActivity::query()->when($desde, fn ($q) => $q->where('created_at', '>=', $desde));

        // KPIs
        $totalEventos    = $base()->count();
        $usuariosActivos = (int) $base()->whereNotNull('user_id')->distinct()->count('user_id');
        $escrituras      = $base()->whereIn('method', ['POST', 'PUT', 'PATCH', 'DELETE'])->count();

        // Lo que más se hace, en lenguaje legible (ej. "Editó productos").
        $mapAccion = function (?string $method, ?string $ruta): string {
            $method = strtoupper((string) $method);
            $ruta = (string) $ruta;
            $partes = explode('.', $ruta);
            $accion = end($partes) ?: '';

            // Prefijo de ruta → nombre del recurso (el orden importa: lo específico primero)
            $dic = [
                'admin.catalog'          => 'productos',
                'catalog'                => 'productos',
                'admin.orders'           => 'pedidos web',
                'admin.licitacion-pdfs'  => 'bases/PDF',
                'admin.licitacion-propuestas' => 'comparativas',
                'cotizaciones'           => 'cotizaciones',
                'propuestas-comerciales' => 'cotizaciones',
                'ventas'                 => 'ventas',
                'clients'                => 'clientes',
                'providers'              => 'proveedores',
                'projects'               => 'licitaciones',
                'licitaciones-ai'        => 'licitaciones (IA)',
                'publications'           => 'compras y ventas',
                'partcontable'           => 'parte contable',
                'profile'                => 'perfil',
                'settings'               => 'configuración',
                'wms'                    => 'WMS',
                'inventory'              => 'inventario',
                'agenda'                 => 'agenda',
                'maintenance'            => 'mantenimiento',
                'accounting'             => 'gastos',
                'dashboard'              => 'inicio',
            ];

            $recurso = null;
            foreach ($dic as $pre => $lbl) {
                if ($ruta === $pre || str_starts_with($ruta, $pre . '.')) { $recurso = $lbl; break; }
            }
            if ($recurso === null) {
                $recurso = str_replace(['-', '_'], ' ', $partes[0] !== '' ? $partes[0] : ($ruta ?: 'el sistema'));
            }

            $verbo = match (true) {
                in_array($accion, ['store', 'create'], true)          => 'Creó',
                in_array($accion, ['update', 'edit'], true)           => 'Editó',
                in_array($accion, ['destroy', 'delete'], true)        => 'Eliminó',
                in_array($accion, ['index', 'list', 'all', 'show', 'view', 'search', 'control'], true) => 'Consultó',
                default => match ($method) {
                    'POST'           => 'Registró',
                    'PUT', 'PATCH'   => 'Actualizó',
                    'DELETE'         => 'Eliminó',
                    default          => 'Consultó',
                },
            };

            return $verbo . ' ' . $recurso;
        };

        $accionesRaw = $base()
            ->selectRaw("method, COALESCE(NULLIF(route, ''), path) as ruta, COUNT(*) as n")
            ->groupBy('method', 'ruta')->get();

        $aggAcciones = [];
        foreach ($accionesRaw as $r) {
            $lbl = $mapAccion($r->method, $r->ruta);
            $aggAcciones[$lbl] = ($aggAcciones[$lbl] ?? 0) + (int) $r->n;
        }
        arsort($aggAcciones);
        $topPantallas = collect($aggAcciones)->take(12)
            ->map(fn ($n, $lbl) => ['etiqueta' => $lbl, 'n' => $n])->values();

        // Quién lo hace (top usuarios por eventos)
        $topUsuariosRaw = $base()->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as n')
            ->groupBy('user_id')->orderByDesc('n')->limit(12)->get();

        // Métodos (GET vs escritura, etc.)
        $porMetodo = $base()->selectRaw('method, COUNT(*) as n')->groupBy('method')->orderByDesc('n')->pluck('n', 'method');

        // Actividad en el tiempo (por día)
        $serie = $base()->selectRaw('DATE(created_at) as d, COUNT(*) as n')->groupBy('d')->orderBy('d')->pluck('n', 'd');

        // Tiempo activo por usuario (heurística: sesiones con hueco > 30 min)
        $filas = $base()->whereNotNull('user_id')->orderBy('user_id')->orderBy('created_at')->get(['user_id', 'created_at']);
        $activo = [];
        foreach ($filas->groupBy('user_id') as $uid => $items) {
            $mins = 0; $inicio = null; $prev = null;
            foreach ($items as $it) {
                $t = $it->created_at;
                if ($prev === null) {
                    $inicio = $t;
                } elseif ($t->diffInMinutes($prev) > 30) {
                    $mins += $prev->diffInMinutes($inicio);
                    $inicio = $t;
                }
                $prev = $t;
            }
            if ($prev !== null) {
                $mins += $prev->diffInMinutes($inicio);
            }
            $activo[(int) $uid] = $mins;
        }
        arsort($activo);
        $tiempoActivoTotal = array_sum($activo);

        // Nombres de usuarios (una sola consulta)
        $ids = collect($topUsuariosRaw->pluck('user_id'))->merge(array_keys($activo))->unique()->filter()->values();
        $nombres = User::whereIn('id', $ids)->pluck('name', 'id');
        $nombre = fn ($id) => $nombres[$id] ?? ('Usuario #' . $id);

        $topUsuarios = $topUsuariosRaw->map(fn ($r) => ['nombre' => $nombre($r->user_id), 'n' => (int) $r->n])->values();
        $tiempoActivo = collect($activo)->take(12)
            ->map(fn ($m, $uid) => ['nombre' => $nombre($uid), 'min' => (int) $m])->values();

        return view('partcontable.activity_analytics', compact(
            'rango', 'totalEventos', 'usuariosActivos', 'escrituras',
            'topPantallas', 'topUsuarios', 'porMetodo', 'serie', 'tiempoActivo', 'tiempoActivoTotal'
        ));
    }
}