<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketExecutiveController extends Controller
{
    public function index(Request $r)
    {
        // Ventanas analíticas
        $daysTrend = (int)($r->integer('days_trend') ?: 14);
        $daysKpi   = (int)($r->integer('days_kpi')   ?: 30);

        // "Quién está trabajando ahorita" (minutos de actividad)
        $activeMins = (int)($r->integer('active_mins') ?: 15);

        $now = now();
        $startTrend = $now->copy()->startOfDay()->subDays(max(7, $daysTrend - 1));
        $startKpi   = $now->copy()->startOfDay()->subDays(max(7, $daysKpi - 1));
        $activeSince = $now->copy()->subMinutes(max(3, $activeMins));

        $closedStatuses = ['completado', 'cancelado'];

        // ========= KPIs =========
        $open = Ticket::query()->whereNotIn('status', $closedStatuses)->count();
        $closed = Ticket::query()->whereIn('status', $closedStatuses)->count();

        $overdue = 0;
        $dueSoon = 0;

        if (Schema::hasColumn('tickets', 'due_at')) {
            $overdue = Ticket::query()
                ->whereNotIn('status', $closedStatuses)
                ->whereNotNull('due_at')
                ->where('due_at', '<', $now)
                ->count();

            $dueSoon = Ticket::query()
                ->whereNotIn('status', $closedStatuses)
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$now, $now->copy()->addHours(24)])
                ->count();
        }

        $avgHours = null;
        if (Schema::hasColumn('tickets', 'completed_at') || Schema::hasColumn('tickets', 'cancelled_at')) {
            $avg = Ticket::query()
                ->whereIn('status', $closedStatuses)
                ->whereNotNull('created_at')
                ->where(function ($q) {
                    if (Schema::hasColumn('tickets', 'completed_at')) $q->whereNotNull('completed_at');
                    if (Schema::hasColumn('tickets', 'cancelled_at')) $q->orWhereNotNull('cancelled_at');
                })
                ->selectRaw("
                    AVG(
                        TIMESTAMPDIFF(
                            MINUTE,
                            created_at,
                            COALESCE(" . (Schema::hasColumn('tickets','completed_at') ? 'completed_at' : 'NULL') . ",
                                     " . (Schema::hasColumn('tickets','cancelled_at') ? 'cancelled_at' : 'NULL') . ")
                        )
                    ) / 60
                    as avg_hours
                ")
                ->value('avg_hours');

            $avgHours = is_null($avg) ? null : (float)$avg;
        }

        $stats = [
            'open'      => (int) $open,
            'closed'    => (int) $closed,
            'overdue'   => (int) $overdue,
            'due_soon'  => (int) $dueSoon,
            'avg_hours' => $avgHours,
        ];

        // ========= Pendientes por persona =========
        $byUserPending = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->whereNotIn('tickets.status', $closedStatuses)
            ->groupBy('tickets.assignee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                DB::raw("COALESCE(users.name, 'Sin asignar') as name"),
                DB::raw("COUNT(*) as count"),
            ])
            ->map(fn($row) => ['name' => (string)$row->name, 'count' => (int)$row->count]);

        // ========= Por prioridad (abiertos) =========
        $byPriority = Ticket::query()
            ->whereNotIn('status', $closedStatuses)
            ->groupBy('priority')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                DB::raw("COALESCE(priority,'') as priority"),
                DB::raw('COUNT(*) as count'),
            ])
            ->map(fn($row) => ['priority' => (string)$row->priority, 'count' => (int)$row->count]);

        // ========= Carga de trabajo por usuario (abiertos + vencidos) =========
        $workload = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->whereNotIn('tickets.status', $closedStatuses)
            ->groupBy('tickets.assignee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw("
                COALESCE(users.name, 'Sin asignar') as name,
                SUM(1) as open,
                " . (Schema::hasColumn('tickets','due_at')
                    ? "SUM(CASE WHEN tickets.due_at IS NOT NULL AND tickets.due_at < NOW() THEN 1 ELSE 0 END)"
                    : "0"
                ) . " as overdue
            ")
            ->get()
            ->map(fn($row) => [
                'name'    => (string)$row->name,
                'open'    => (int) $row->open,
                'overdue' => (int) $row->overdue,
            ]);

        // =========================
        // ✅ EXTRA ANALÍTICO (NO REPETIR GRÁFICAS)
        // =========================

        // A) Tickets asignados en ventana KPI (creados en la ventana, agrupados por assignee)
        $assignedByUser = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->where('tickets.created_at', '>=', $startKpi)
            ->groupBy('tickets.assignee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                DB::raw("COALESCE(users.name, 'Sin asignar') as name"),
                DB::raw("COUNT(*) as count"),
            ])
            ->map(fn($row) => ['name' => (string)$row->name, 'count' => (int)$row->count]);

        // B) Tickets resueltos en ventana KPI
        $resolvedByUser = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->whereIn('tickets.status', ['completado'])
            ->where('tickets.created_at', '>=', $startKpi)
            ->groupBy('tickets.assignee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                DB::raw("COALESCE(users.name, 'Sin asignar') as name"),
                DB::raw("COUNT(*) as count"),
            ])
            ->map(fn($row) => ['name' => (string)$row->name, 'count' => (int)$row->count]);

        // C) Promedio horas por usuario
        $avgResolveByUser = collect();
        if (Schema::hasColumn('tickets', 'completed_at')) {
            $avgResolveByUser = Ticket::query()
                ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
                ->whereIn('tickets.status', ['completado'])
                ->whereNotNull('tickets.completed_at')
                ->where('tickets.created_at', '>=', $startKpi)
                ->groupBy('tickets.assignee_id', 'users.name')
                ->orderBy(DB::raw('AVG(TIMESTAMPDIFF(SECOND, tickets.created_at, tickets.completed_at))'))
                ->selectRaw("
                    COALESCE(users.name, 'Sin asignar') as name,
                    AVG(TIMESTAMPDIFF(SECOND, tickets.created_at, tickets.completed_at)) as avg_sec
                ")
                ->get()
                ->map(function($row){
                    $h = is_null($row->avg_sec) ? null : round(((float)$row->avg_sec) / 3600, 2);
                    return ['name' => (string)$row->name, 'avg_hours' => $h];
                });
        }

        // D) Calidad (0-100) (tu lógica actual)
        $qualityByUser = collect();
        if (Schema::hasColumn('tickets','due_at') && Schema::hasColumn('tickets','completed_at')) {
            $qualityByUser = Ticket::query()
                ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
                ->whereIn('tickets.status', ['completado'])
                ->whereNotNull('tickets.completed_at')
                ->where('tickets.created_at', '>=', $startKpi)
                ->groupBy('tickets.assignee_id', 'users.name')
                ->selectRaw("
                    COALESCE(users.name, 'Sin asignar') as name,
                    COUNT(*) as total_done,
                    SUM(
                        CASE
                            WHEN tickets.due_at IS NULL THEN 1
                            WHEN tickets.completed_at <= tickets.due_at THEN 1
                            ELSE 0
                        END
                    ) as on_time
                ")
                ->get()
                ->map(function($row){
                    $total = (int)($row->total_done ?? 0);
                    $onTime = (int)($row->on_time ?? 0);
                    $pct = $total > 0 ? round(($onTime / $total) * 100) : 0;
                    return ['name' => (string)$row->name, 'score' => (int)$pct];
                })
                ->sortByDesc('score')
                ->values();
        } else {
            $base = Ticket::query()
                ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
                ->where('tickets.created_at', '>=', $startKpi)
                ->groupBy('tickets.assignee_id', 'users.name')
                ->selectRaw("
                    COALESCE(users.name, 'Sin asignar') as name,
                    SUM(CASE WHEN tickets.status = 'completado' THEN 1 ELSE 0 END) as done_count
                ")
                ->get();

            $maxDone = (int)($base->max('done_count') ?? 0);

            $qualityByUser = $base->map(function($row) use ($maxDone){
                $done = (int)($row->done_count ?? 0);
                $vol = $maxDone > 0 ? round(($done / $maxDone) * 100) : 0;
                return ['name' => (string)$row->name, 'score' => max(0, min(100, (int)$vol))];
            })->sortByDesc('score')->values();
        }

        // ✅ E) Puntuación real por usuario (promedio tickets.score)
        $userScoreByUser = collect();
        if (Schema::hasColumn('tickets', 'score')) {
            $userScoreByUser = Ticket::query()
                ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
                ->where('tickets.created_at', '>=', $startKpi)
                ->whereNotNull('tickets.score')
                ->groupBy('tickets.assignee_id', 'users.name')
                ->selectRaw("
                    COALESCE(users.name, 'Sin asignar') as name,
                    AVG(tickets.score) as avg_score,
                    COUNT(*) as n
                ")
                ->orderByDesc(DB::raw('AVG(tickets.score)'))
                ->get()
                ->map(function($row){
                    return [
                        'name' => (string)$row->name,
                        'avg_score' => is_null($row->avg_score) ? null : round((float)$row->avg_score, 2),
                        'n' => (int)($row->n ?? 0),
                    ];
                });
        }

        // ✅ F) Tiempo por usuario (UI timer) sumando audits.diff.elapsed_seconds
        // Nota: esto depende de ticket_audits.diff JSON
        $userUiTimeByUser = collect();
        try {
            $userUiTimeByUser = DB::table('ticket_audits')
                ->leftJoin('users', 'users.id', '=', 'ticket_audits.user_id')
                ->where('ticket_audits.created_at', '>=', $startKpi)
                ->groupBy('ticket_audits.user_id', 'users.name')
                ->selectRaw("
                    COALESCE(users.name, 'Sistema') as name,
                    SUM(
                      COALESCE(
                        CAST(JSON_UNQUOTE(JSON_EXTRACT(ticket_audits.diff, '$.elapsed_seconds')) AS UNSIGNED),
                        0
                      )
                    ) as ui_seconds
                ")
                ->orderByDesc(DB::raw('ui_seconds'))
                ->get()
                ->map(function($row){
                    $sec = (int)($row->ui_seconds ?? 0);
                    $h = $sec > 0 ? round($sec / 3600, 2) : 0;
                    return ['name' => (string)$row->name, 'ui_hours' => $h, 'ui_seconds' => $sec];
                });
        } catch (\Throwable $e) {
            $userUiTimeByUser = collect(); // si la BD no soporta JSON_EXTRACT, no revienta
        }

        // ✅ G) “Quién está trabajando ahorita”
        // Heurística: tickets abiertos que han sido actualizados recientemente (updated_at)
        $activeNowByUser = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->whereNotIn('tickets.status', $closedStatuses)
            ->where('tickets.updated_at', '>=', $activeSince)
            ->groupBy('tickets.assignee_id', 'users.name')
            ->selectRaw("
                COALESCE(users.name, 'Sin asignar') as name,
                COUNT(*) as count
            ")
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get()
            ->map(fn($row)=>['name'=>(string)$row->name, 'count'=>(int)$row->count]);

        $activeTickets = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->whereNotIn('tickets.status', $closedStatuses)
            ->where('tickets.updated_at', '>=', $activeSince)
            ->orderByDesc('tickets.updated_at')
            ->limit(8)
            ->get([
                'tickets.id',
                'tickets.folio',
                'tickets.title',
                'tickets.status',
                'tickets.updated_at',
                DB::raw("COALESCE(users.name, 'Sin asignar') as assignee_name"),
            ])
            ->map(function($t){
                return [
                    'id' => (int)$t->id,
                    'folio' => (string)($t->folio ?? ''),
                    'title' => (string)($t->title ?? ''),
                    'status' => (string)($t->status ?? ''),
                    'updated_at' => $t->updated_at ? Carbon::parse($t->updated_at)->toDateTimeString() : null,
                    'assignee' => (string)($t->assignee_name ?? 'Sin asignar'),
                ];
            });

        // ========= Trend (serie diaria) =========
        $dates = collect();
        for ($d = $startTrend->copy(); $d->lte($now->copy()->startOfDay()); $d->addDay()) {
            $dates->push($d->copy());
        }

        $createdPerDay = Ticket::query()
            ->where('created_at', '>=', $startTrend)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as c')
            ->groupBy('day')
            ->pluck('c', 'day');

        $closedPerDay = collect();
        if (Schema::hasColumn('tickets','completed_at')) {
            $closedPerDay = Ticket::query()
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $startTrend)
                ->selectRaw('DATE(completed_at) as day, COUNT(*) as c')
                ->groupBy('day')
                ->pluck('c', 'day');
        }

        $trend = $dates->map(function(Carbon $day) use ($createdPerDay, $closedPerDay){
            $k = $day->toDateString();
            return [
                'label'  => $day->format('d M'),
                'open'   => (int)($createdPerDay[$k] ?? 0),
                'closed' => (int)($closedPerDay[$k] ?? 0),
            ];
        })->values();

        return view('tickets.executive', [
            'stats'            => $stats,
            'byUserPending'    => $byUserPending,
            'byPriority'       => $byPriority,
            'workload'         => $workload,

            'assignedByUser'   => $assignedByUser,
            'resolvedByUser'   => $resolvedByUser,
            'avgResolveByUser' => $avgResolveByUser,
            'qualityByUser'    => $qualityByUser,
            'trend'            => $trend,

            // ✅ NUEVOS
            'userScoreByUser'  => $userScoreByUser,   // puntuación promedio (tickets.score)
            'userUiTimeByUser' => $userUiTimeByUser,  // horas UI por usuario (audits elapsed_seconds)
            'activeNowByUser'  => $activeNowByUser,   // quién está trabajando ahorita
            'activeTickets'    => $activeTickets,     // lista rápida

            // Labels bonitos
            'priorities'       => TicketController::PRIORITIES,
        ]);
    }
}