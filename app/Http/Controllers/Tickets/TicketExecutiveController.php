<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketExecutiveController extends Controller
{
    /**
     * Panel ejecutivo (renombrado).
     * Datos:
     * - stats: abiertos/cerrados/vencidos/por vencer/promedio resolución
     * - byUserPending: pendientes por persona
     * - byPriority: por prioridad
     * - workload: abiertos y vencidos por persona
     */
    public function index(Request $r)
    {
        $closedStatuses = ['completado','cancelado'];

        // ========= KPIs =========
        $open = Ticket::query()
            ->whereNotIn('status', $closedStatuses)
            ->count();

        $closed = Ticket::query()
            ->whereIn('status', $closedStatuses)
            ->count();

        $overdue = Ticket::query()
            ->whereNotIn('status', $closedStatuses)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->count();

        $dueSoon = Ticket::query()
            ->whereNotIn('status', $closedStatuses)
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->copy()->addHours(24)])
            ->count();

        // Promedio de resolución (en horas) para cerrados
        // Usa completed_at o cancelled_at como "fin"
        $avgHours = Ticket::query()
            ->whereIn('status', $closedStatuses)
            ->whereNotNull('created_at')
            ->where(function ($q) {
                $q->whereNotNull('completed_at')
                  ->orWhereNotNull('cancelled_at');
            })
            ->selectRaw("
                AVG(
                    TIMESTAMPDIFF(
                        MINUTE,
                        created_at,
                        COALESCE(completed_at, cancelled_at)
                    )
                ) / 60
                as avg_hours
            ")
            ->value('avg_hours');

        $stats = [
            'open'      => (int) $open,
            'closed'    => (int) $closed,
            'overdue'   => (int) $overdue,
            'due_soon'  => (int) $dueSoon,
            'avg_hours' => is_null($avgHours) ? null : (float) $avgHours,
        ];

        // ========= Pendientes por persona =========
        // Incluye "Sin asignar"
        $byUserPending = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->whereNotIn('tickets.status', $closedStatuses)
            ->groupBy('tickets.assignee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                DB::raw("COALESCE(users.name, 'Sin asignar') as name"),
                DB::raw("COUNT(*) as count"),
            ])
            ->map(fn($row) => ['name' => $row->name, 'count' => (int)$row->count]);

        // ========= Por prioridad (abiertos) =========
        $byPriority = Ticket::query()
            ->whereNotIn('status', $closedStatuses)
            ->groupBy('priority')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->get([
                'priority',
                DB::raw('COUNT(*) as count'),
            ])
            ->map(fn($row) => ['priority' => $row->priority, 'count' => (int)$row->count]);

        // ========= Carga de trabajo por usuario =========
        $workload = Ticket::query()
            ->leftJoin('users', 'users.id', '=', 'tickets.assignee_id')
            ->whereNotIn('tickets.status', $closedStatuses)
            ->groupBy('tickets.assignee_id', 'users.name')
            ->orderByDesc(DB::raw('COUNT(*)'))
            ->selectRaw("
                COALESCE(users.name, 'Sin asignar') as name,
                SUM(1) as open,
                SUM(CASE WHEN tickets.due_at IS NOT NULL AND tickets.due_at < NOW() THEN 1 ELSE 0 END) as overdue
            ")
            ->get()
            ->map(fn($row) => [
                'name'    => $row->name,
                'open'    => (int) $row->open,
                'overdue' => (int) $row->overdue,
            ]);

        return view('tickets.executive', [
            'stats'         => $stats,
            'byUserPending' => $byUserPending,
            'byPriority'    => $byPriority,
            'workload'      => $workload,

            // Opcional: para mostrar labels bonitos en la vista
            'priorities'    => TicketController::PRIORITIES,
        ]);
    }
}