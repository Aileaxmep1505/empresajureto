<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controllers\HasMiddleware; // interfaz (Laravel 12)
use Illuminate\Routing\Controllers\Middleware;     // helper
use Carbon\Carbon;

class DashboardController extends Controller implements HasMiddleware
{
    /** Middleware en Laravel 12+ */
    public static function middleware(): array
    {
        return [ new Middleware('auth') ];
    }

    public function index()
    {
        $hoy        = Carbon::today();
        $ayer       = Carbon::yesterday();
        $inicioMes  = Carbon::now()->startOfMonth();
        $ahora      = Carbon::now();

        $has = fn(string $table) => Schema::hasTable($table);

        /* ================= KPIs ================= */
        $kpiVentasHoy = $has('ventas')
            ? (int) DB::table('ventas')->whereDate('created_at', $hoy)->count()
            : 0;

        $kpiIngresosMes = $has('ventas')
            ? (float) DB::table('ventas')->whereBetween('created_at', [$inicioMes, $ahora])->sum('total')
            : 0.0;

        // Conteo robusto de clientes (modelo o tabla real)
        $kpiClientes = $this->contarClientes();

        // Pendientes (tareas -> cotizaciones.estado=pendiente -> 0)
        if ($has('tareas')) {
            $kpiPendientes = (int) DB::table('tareas')->where('estado', 'pendiente')->count();
        } elseif ($has('cotizaciones') && Schema::hasColumn('cotizaciones', 'estado')) {
            $kpiPendientes = (int) DB::table('cotizaciones')->where('estado', 'pendiente')->count();
        } else {
            $kpiPendientes = 0;
        }

        /* ============== Tendencias ============== */
        $ventasAyer  = $has('ventas') ? (int) DB::table('ventas')->whereDate('created_at', $ayer)->count() : 0;
        $trendVentas = $ventasAyer > 0
            ? (int) round(100 * ($kpiVentasHoy - $ventasAyer) / max(1, $ventasAyer))
            : ($kpiVentasHoy > 0 ? 100 : 0);

        if ($has('ventas')) {
            $inicioMesAnterior   = (clone $inicioMes)->subMonth();
            $finMesAnterior      = (clone $inicioMes)->subSecond();
            $ingresosMesAnterior = (float) DB::table('ventas')
                ->whereBetween('created_at', [$inicioMesAnterior, $finMesAnterior])
                ->sum('total');

            $trendIngresos = $ingresosMesAnterior > 0
                ? (int) round(100 * ($kpiIngresosMes - $ingresosMesAnterior) / max(1, $ingresosMesAnterior))
                : ($kpiIngresosMes > 0 ? 100 : 0);
        } else {
            $trendIngresos = 0;
        }

        if ($this->existeTablaCon($tabla = 'clientes', 'created_at')) {
            $c7 = (int) DB::table('clientes')->where('created_at', '>=', Carbon::now()->subDays(7))->count();
            $p7 = (int) DB::table('clientes')->whereBetween('created_at', [Carbon::now()->subDays(14), Carbon::now()->subDays(7)])->count();
            $trendClientes = $p7 > 0 ? (int) round(100 * ($c7 - $p7) / max(1, $p7)) : ($c7 > 0 ? 100 : 0);
        } else {
            $trendClientes = 0;
        }

        $trendPend = 0;

        /* ============ Series 12 días ============ */
        $serieVentas = [];
        $serieIngresos = [];
        if ($has('ventas')) {
            for ($i = 11; $i >= 0; $i--) {
                $d = Carbon::today()->subDays($i);
                $serieVentas[]   = (int) DB::table('ventas')->whereDate('created_at', $d)->count();
                $serieIngresos[] = (int) DB::table('ventas')->whereDate('created_at', $d)->sum('total');
            }
        } else {
            $serieVentas   = [4,6,5,7,9,8,10,9,12,11,14,15];
            $serieIngresos = [12,9,11,10,13,15,14,16,18,17,20,22];
        }

        /* ========= Actividad reciente ========= */
        $ultimasVentas = collect();
        if ($has('ventas')) {
            $q = DB::table('ventas')
                ->select('ventas.id', 'ventas.total', 'ventas.created_at', DB::raw("COALESCE(ventas.estado, 'pendiente') as estado"));

            // Detecta automáticamente tabla/columna de nombre del cliente
            $clienteMeta = $this->detectarTablaYColumnaCliente(); // [tabla, columnaNombre]
            if ($clienteMeta && Schema::hasColumn('ventas', 'cliente_id')) {
                [$tablaClientes, $colNombre] = $clienteMeta;
                $q->leftJoin($tablaClientes, "$tablaClientes.id", '=', 'ventas.cliente_id')
                  ->addSelect(DB::raw("$tablaClientes.$colNombre as cliente_nombre"));
            }

            $ultimasVentas = $q->orderBy('ventas.created_at', 'desc')->limit(6)->get();
        }

        /* ========== Progreso (placeholder) ========== */
        $progresoAlumno = 0;
        $modulosResumen = [
            ['titulo'=>'Módulo 1: Introducción','estado'=>'completado','pct'=>100],
            ['titulo'=>'Módulo 2: Intermedio','estado'=>'en progreso','pct'=>45],
            ['titulo'=>'Módulo 3: Avanzado','estado'=>'pendiente','pct'=>0],
        ];

        return view('dashboard', [
            'kpiVentasHoy'   => $kpiVentasHoy,
            'kpiIngresosMes' => $kpiIngresosMes,
            'kpiClientes'    => $kpiClientes,
            'kpiPendientes'  => $kpiPendientes,
            'trendVentas'    => $trendVentas,
            'trendIngresos'  => $trendIngresos,
            'trendClientes'  => $trendClientes,
            'trendPend'      => $trendPend,
            'serieVentas'    => $serieVentas,
            'serieIngresos'  => $serieIngresos,
            'ultimasVentas'  => $ultimasVentas,
            'progresoAlumno' => $progresoAlumno,
            'modulos'        => $modulosResumen,
        ]);
    }

    /* ================= Helpers ================= */

    /** Cuenta clientes usando modelo si existe; si no, por tabla común. */
    private function contarClientes(): int
    {
        // Modelos más comunes
        foreach (['App\\Models\\Cliente','App\\Models\\Client','App\\Models\\Customer'] as $model) {
            if (class_exists($model)) {
                try {
                    return (int) (new $model)->newQuery()->count();
                } catch (\Throwable $e) {
                    Log::warning('Dashboard contarClientes(model) falló', ['model'=>$model, 'e'=>$e->getMessage()]);
                }
            }
        }

        // Tablas comunes
        foreach (['clientes','clients','customers'] as $table) {
            try {
                if (Schema::hasTable($table)) {
                    return (int) DB::table($table)->count();
                }
            } catch (\Throwable $e) {
                Log::warning('Dashboard contarClientes(table) falló', ['table'=>$table, 'e'=>$e->getMessage()]);
            }
        }

        return 0;
    }

    /** Devuelve [tablaClientes, columnaNombre] si puede detectarla; null si no. */
    private function detectarTablaYColumnaCliente(): ?array
    {
        foreach (['clientes','clients','customers'] as $t) {
            if (Schema::hasTable($t)) {
                $col = null;
                if (Schema::hasColumn($t, 'nombre'))       $col = 'nombre';
                elseif (Schema::hasColumn($t, 'name'))      $col = 'name';
                elseif (Schema::hasColumn($t, 'razon_social')) $col = 'razon_social';
                if ($col) return [$t, $col];
            }
        }
        return null;
    }

    /** Utilidad: existe tabla y columna */
    private function existeTablaCon(string $tabla, string $columna): bool
    {
        return Schema::hasTable($tabla) && Schema::hasColumn($tabla, $columna);
    }
}
