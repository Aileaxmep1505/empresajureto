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
        $inicioMes  = Carbon::now()->startOfMonth();
        $ahora      = Carbon::now();

        $has = fn(string $table) => Schema::hasTable($table);

        /**
         * =========================
         * Dashboard = Propuestas Comparativas
         * =========================
         * Intentamos detectar automáticamente la tabla principal.
         * Prioridad típica en tu proyecto:
         * - licitacion_propuestas (por tu LicitacionPropuestaController)
         * - propuestas_comparativas
         * - propuestas
         * - cotizaciones (fallback si no existe nada más)
         */
        $tablaPropuestas = $this->detectarTablaPropuestasComparativas();

        // Detecta el campo "estado" si existe
        $colEstado = ($tablaPropuestas && Schema::hasColumn($tablaPropuestas, 'estado')) ? 'estado' : null;

        // Detecta campo monto/total si existe (para sumas si lo necesitas)
        $colTotal = null;
        foreach (['total', 'monto', 'importe', 'subtotal', 'total_estimado'] as $c) {
            if ($tablaPropuestas && Schema::hasColumn($tablaPropuestas, $c)) { $colTotal = $c; break; }
        }

        /* ================= KPIs (Reales) ================= */
        // Propuestas del mes
        $kpiPropuestasMes = $tablaPropuestas
            ? (int) DB::table($tablaPropuestas)->whereBetween('created_at', [$inicioMes, $ahora])->count()
            : 0;

        // En revisión (si existe estado)
        $kpiEnRevision = ($tablaPropuestas && $colEstado)
            ? (int) DB::table($tablaPropuestas)
                ->whereIn($colEstado, ['revision', 'en_revision', 'analisis', 'en_analisis'])
                ->count()
            : 0;

        // Adjudicadas (mes)
        $kpiAdjudicadasMes = ($tablaPropuestas && $colEstado)
            ? (int) DB::table($tablaPropuestas)
                ->whereBetween('created_at', [$inicioMes, $ahora])
                ->whereIn($colEstado, ['adjudicada', 'adjudicado', 'ganada'])
                ->count()
            : 0;

        // Pendientes (si existe estado)
        $kpiPendientes = ($tablaPropuestas && $colEstado)
            ? (int) DB::table($tablaPropuestas)
                ->whereIn($colEstado, ['pendiente', 'borrador', 'revision', 'en_revision', 'enviado'])
                ->count()
            : 0;

        // Conteo robusto de clientes
        $kpiClientes = $this->contarClientes();

        /* ============== Tendencias (Reales) ============== */
        // Tendencia: últimos 12 días vs 12 días anteriores (propuestas creadas)
        $days = 12;
        $currStart = Carbon::now()->startOfDay()->subDays($days - 1);
        $currEnd   = Carbon::now()->endOfDay();
        $prevStart = (clone $currStart)->subDays($days);
        $prevEnd   = (clone $currStart)->subSecond();

        $currCount = $tablaPropuestas
            ? (int) DB::table($tablaPropuestas)->whereBetween('created_at', [$currStart, $currEnd])->count()
            : 0;

        $prevCount = $tablaPropuestas
            ? (int) DB::table($tablaPropuestas)->whereBetween('created_at', [$prevStart, $prevEnd])->count()
            : 0;

        $trendPropuestas = $this->pctChange($prevCount, $currCount);

        // Tendencia adjudicadas: mismo criterio
        if ($tablaPropuestas && $colEstado) {
            $currAdj = (int) DB::table($tablaPropuestas)
                ->whereBetween('created_at', [$currStart, $currEnd])
                ->whereIn($colEstado, ['adjudicada', 'adjudicado', 'ganada'])
                ->count();

            $prevAdj = (int) DB::table($tablaPropuestas)
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->whereIn($colEstado, ['adjudicada', 'adjudicado', 'ganada'])
                ->count();

            $trendAdjudicadas = $this->pctChange($prevAdj, $currAdj);
        } else {
            $trendAdjudicadas = 0;
        }

        // Tendencias opcionales (si quieres calcularlas)
        $trendRevision = 0;
        $trendPend = 0;

        /* ============ Series REALES (12 días) ============ */
        $seriePropuestas = [];
        $serieAdjudicadas = [];

        if ($tablaPropuestas) {
            // Propuestas por día (COUNT)
            $rawDaily = DB::table($tablaPropuestas)
                ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                ->whereBetween('created_at', [$currStart, $currEnd])
                ->groupBy('d')
                ->orderBy('d')
                ->pluck('c', 'd'); // ['2026-01-05'=>3...]

            // Adjudicadas por día (COUNT con estado)
            $rawDailyAdj = collect();
            if ($colEstado) {
                $rawDailyAdj = DB::table($tablaPropuestas)
                    ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
                    ->whereBetween('created_at', [$currStart, $currEnd])
                    ->whereIn($colEstado, ['adjudicada', 'adjudicado', 'ganada'])
                    ->groupBy('d')
                    ->orderBy('d')
                    ->pluck('c', 'd');
            }

            for ($i = 0; $i < $days; $i++) {
                $date = (clone $currStart)->addDays($i)->toDateString();
                $seriePropuestas[]  = (int)($rawDaily[$date] ?? 0);
                $serieAdjudicadas[] = (int)($rawDailyAdj[$date] ?? 0);
            }
        } else {
            // Si no hay tabla, dejar vacío (no ficticio)
            $seriePropuestas = [];
            $serieAdjudicadas = [];
        }

        /* ========= Actividad reciente (Real) ========= */
        // OJO: el blade que ya tienes arma campos con fallbacks.
        $ultimasPropuestas = collect();
        if ($tablaPropuestas) {
            $ultimasPropuestas = DB::table($tablaPropuestas)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        /* ========= Pipeline (Real por estado) ========= */
        $pipeBorrador = $pipeRevision = $pipeEnviado = $pipeAdjudicado = 0;
        $pipePctBorrador = $pipePctRevision = $pipePctEnviado = $pipePctAdjudicado = 0;

        if ($tablaPropuestas && $colEstado) {
            $countsByEstado = DB::table($tablaPropuestas)
                ->selectRaw($colEstado.' as estado, COUNT(*) as c')
                ->groupBy('estado')
                ->pluck('c', 'estado');

            $pipeBorrador   = (int)($countsByEstado['borrador'] ?? 0);
            $pipeRevision   = (int)(
                ($countsByEstado['revision'] ?? 0)
                + ($countsByEstado['en_revision'] ?? 0)
                + ($countsByEstado['analisis'] ?? 0)
                + ($countsByEstado['en_analisis'] ?? 0)
            );
            $pipeEnviado    = (int)($countsByEstado['enviado'] ?? 0);
            $pipeAdjudicado = (int)(
                ($countsByEstado['adjudicada'] ?? 0)
                + ($countsByEstado['adjudicado'] ?? 0)
                + ($countsByEstado['ganada'] ?? 0)
            );

            $totalPipe = max(1, $pipeBorrador + $pipeRevision + $pipeEnviado + $pipeAdjudicado);
            $pipePctBorrador   = (int) round($pipeBorrador   * 100 / $totalPipe);
            $pipePctRevision   = (int) round($pipeRevision   * 100 / $totalPipe);
            $pipePctEnviado    = (int) round($pipeEnviado    * 100 / $totalPipe);
            $pipePctAdjudicado = (int) round($pipeAdjudicado * 100 / $totalPipe);
        }

        return view('dashboard', [
            // KPIs (nuevo dashboard)
            'kpiPropuestasMes'   => $kpiPropuestasMes,
            'kpiEnRevision'      => $kpiEnRevision,
            'kpiAdjudicadasMes'  => $kpiAdjudicadasMes,
            'kpiPendientes'      => $kpiPendientes,
            'kpiClientes'        => $kpiClientes,

            // Tendencias
            'trendPropuestas'    => $trendPropuestas,
            'trendRevision'      => $trendRevision,
            'trendAdjudicadas'   => $trendAdjudicadas,
            'trendPend'          => $trendPend,

            // Series reales (sparklines)
            'seriePropuestas'    => $seriePropuestas,
            'serieAdjudicadas'   => $serieAdjudicadas,

            // Actividad
            'ultimasPropuestas'  => $ultimasPropuestas,

            // Pipeline real
            'pipeBorrador'       => $pipeBorrador,
            'pipeRevision'       => $pipeRevision,
            'pipeEnviado'        => $pipeEnviado,
            'pipeAdjudicado'     => $pipeAdjudicado,
            'pipePctBorrador'    => $pipePctBorrador,
            'pipePctRevision'    => $pipePctRevision,
            'pipePctEnviado'     => $pipePctEnviado,
            'pipePctAdjudicado'  => $pipePctAdjudicado,
        ]);
    }

    /* ================= Helpers ================= */

    private function pctChange(int $prev, int $curr): int
    {
        if ($prev <= 0) return $curr > 0 ? 100 : 0;
        return (int) round((($curr - $prev) / $prev) * 100);
    }

    /** Detecta la tabla principal de propuestas comparativas. */
    private function detectarTablaPropuestasComparativas(): ?string
    {
        $candidatas = [
            'licitacion_propuestas',
            'propuestas_comparativas',
            'propuestas',
            'cotizaciones', // fallback
        ];

        foreach ($candidatas as $t) {
            if (Schema::hasTable($t) && Schema::hasColumn($t, 'created_at')) {
                return $t;
            }
        }

        return null;
    }

    /** Cuenta clientes usando modelo si existe; si no, por tabla común. */
    private function contarClientes(): int
    {
        foreach (['App\\Models\\Cliente','App\\Models\\Client','App\\Models\\Customer'] as $model) {
            if (class_exists($model)) {
                try {
                    return (int) (new $model)->newQuery()->count();
                } catch (\Throwable $e) {
                    Log::warning('Dashboard contarClientes(model) falló', ['model'=>$model, 'e'=>$e->getMessage()]);
                }
            }
        }

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
}
