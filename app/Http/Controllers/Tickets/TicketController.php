<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

use App\Models\{
    Ticket,
    TicketAudit,
    TicketDocument,
    User
};

use App\Notifications\TicketAssigned;

// ✅ NUEVAS notificaciones (si existen)
use App\Notifications\TicketSubmittedForReview;
use App\Notifications\TicketReviewApproved;
use App\Notifications\TicketReviewRejected;

// ✅ PDF
use Barryvdh\DomPDF\Facade\Pdf;

class TicketController extends Controller
{
    public const STATUSES = [
        'pendiente'   => 'Pendiente',
        'revision'    => 'En revisión',
        'progreso'    => 'En progreso',
        'bloqueado'   => 'En espera (bloqueado)',
        'pruebas'     => 'En pruebas',

        'por_revisar' => 'Por revisar',
        'reabierto'   => 'Reabierto',

        'completado'  => 'Completado',
        'cancelado'   => 'Cancelado',
    ];

    public const PRIORITIES = [
        'critica' => 'Crítica',
        'alta'    => 'Alta',
        'media'   => 'Media',
        'baja'    => 'Baja',
        'mejora'  => 'Mejora futura',
    ];

    // ✅ Áreas reales (actualizadas)
    public const AREAS = [
        'almacen'        => 'Almacén',
        'logistica'      => 'Logística',
        'licitaciones'   => 'Licitaciones',
        'ventas'         => 'Ventas',
        'compras'        => 'Compras',
        'sistemas'       => 'Sistemas',
        'mercadotecnia'  => 'Mercadotecnia',
        'administracion' => 'Administración',
        'mantenimiento'  => 'Mantenimiento',

        // Opcionales comunes
        'contabilidad'   => 'Contabilidad',
        'direccion'      => 'Dirección',
        'calidad'        => 'Calidad',
    ];

    private function canWorkTicket(Ticket $ticket): bool
    {
        $uid = auth()->id();
        if (!$uid) return false;

        if (!empty($ticket->assignee_id) && (int)$ticket->assignee_id === (int)$uid) return true;
        if (!empty($ticket->created_by) && (int)$ticket->created_by === (int)$uid) return true;

        return false;
    }

    private function canReviewTicket(Ticket $ticket): bool
    {
        $uid = auth()->id();
        if (!$uid) return false;

        if (Schema::hasColumn('tickets', 'assigned_by') && !empty($ticket->assigned_by) && (int)$ticket->assigned_by === (int)$uid) {
            return true;
        }
        if (Schema::hasColumn('tickets', 'created_by') && !empty($ticket->created_by) && (int)$ticket->created_by === (int)$uid) {
            return true;
        }

        return false;
    }

    private function reviewerUserIds(Ticket $ticket): array
    {
        $ids = [];

        if (Schema::hasColumn('tickets', 'assigned_by') && !empty($ticket->assigned_by)) {
            $ids[] = (int)$ticket->assigned_by;
        }
        if (Schema::hasColumn('tickets', 'created_by') && !empty($ticket->created_by)) {
            $ids[] = (int)$ticket->created_by;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** ===================== PDF helpers ===================== */

    private function fmtSecs(?int $sec): string
    {
        $sec = max(0, (int)($sec ?? 0));
        $h = intdiv($sec, 3600);
        $m = intdiv(($sec % 3600), 60);
        $s = $sec % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    private function buildReportData(Ticket $ticket): array
    {
        $ticket->load([
            'assignee',
            'creator',
            'comments.user',
            'documents.uploader',
            'audits.user',

            // ✅ checklist para reportes (si lo quieres en el PDF o UI)
            'checklists.items',
        ]);

        $audits = $ticket->audits
            ? $ticket->audits->sortBy('created_at')->values()
            : collect();

        $statusEvents = collect();

        $lastCancelReason = '';
        $lastReopenReason = '';
        $lastRejectReason = '';

        foreach ($audits as $a) {
            $action = (string)($a->action ?? '');
            $diff   = (array)($a->diff ?? []);

            $before = data_get($diff, 'before.status');
            $after  = data_get($diff, 'after.status');

            // Normalizar acciones legacy / modernas
            if ($action === 'ticket_completed') {
                $before = data_get($diff, 'from', $before);
                $after  = data_get($diff, 'to', 'completado');
            }

            if ($action === 'ticket_cancelled') {
                $before = data_get($diff, 'from', $before);
                $after  = data_get($diff, 'to', 'cancelado');
                $lastCancelReason = (string) data_get($diff, 'reason', $lastCancelReason);
            }

            if ($action === 'ticket_submitted_for_review') {
                $before = data_get($diff, 'from', $before);
                $after  = data_get($diff, 'to', 'por_revisar');
            }

            if ($action === 'ticket_review_approved') {
                $before = data_get($diff, 'from', $before);
                $after  = data_get($diff, 'to', 'completado');
            }

            if ($action === 'ticket_review_rejected') {
                $before = data_get($diff, 'from', $before);
                $after  = data_get($diff, 'to', 'reabierto');
                $lastRejectReason = (string) data_get($diff, 'reason', $lastRejectReason);
            }

            if ($action === 'ticket_reopened' || $action === 'ticket_force_reopened') {
                $before = data_get($diff, 'from', $before);
                $after  = data_get($diff, 'to', 'reabierto');
                $lastReopenReason = (string) data_get($diff, 'reason', $lastReopenReason);
            }

            if (!empty($after) && $before !== $after) {
                $statusEvents->push([
                    'at'      => $a->created_at,
                    'user'    => optional($a->user)->name ?: 'Sistema',
                    'from'    => $before ?: null,
                    'to'      => $after,
                    'elapsed' => (int) data_get($diff, 'elapsed_seconds', 0),
                    'note'    => (string) data_get($diff, 'note', ''),
                    'reason'  => (string) data_get($diff, 'reason', ''),
                ]);
            }
        }

        // Segmentar duración por timestamps
        $segments = [];
        $startAt = $ticket->created_at;
        $endAt   = $ticket->completed_at ?: ($ticket->cancelled_at ?: now());

        $cursorAt = $startAt;
        $cursorStatus = $ticket->status ?: 'pendiente';

        if ($statusEvents->count()) {
            $cursorStatus = $statusEvents->first()['from'] ?: ($ticket->status ?: 'pendiente');
        }

        foreach ($statusEvents as $ev) {
            $at = $ev['at'];
            if ($at && $cursorAt && $at->greaterThan($cursorAt)) {
                $segments[] = [
                    'status' => $cursorStatus,
                    'from'   => $cursorAt,
                    'to'     => $at,
                    'secs'   => $cursorAt->diffInSeconds($at),
                ];
            }
            $cursorAt = $at ?: $cursorAt;
            $cursorStatus = $ev['to'] ?: $cursorStatus;
        }

        if ($cursorAt && $endAt && $endAt->greaterThan($cursorAt)) {
            $segments[] = [
                'status' => $cursorStatus,
                'from'   => $cursorAt,
                'to'     => $endAt,
                'secs'   => $cursorAt->diffInSeconds($endAt),
            ];
        }

        // UI elapsed acumulado
        $uiElapsed = 0;
        foreach ($audits as $a) {
            $diff = (array)($a->diff ?? []);
            $uiElapsed += (int) data_get($diff, 'elapsed_seconds', 0);
        }

        $totalSpan = ($ticket->created_at && $endAt) ? $ticket->created_at->diffInSeconds($endAt) : 0;

        // Separar evidencias vs adjuntos
        $docs = $ticket->documents ?? collect();
        $evidences = $docs->filter(function ($d) {
            $cat = (string)($d->category ?? '');
            return in_array($cat, ['evidencia', 'evidence'], true);
        })->values();

        $attachments = $docs->reject(function ($d) {
            $cat = (string)($d->category ?? '');
            return in_array($cat, ['evidencia', 'evidence'], true);
        })->values();

        return [
            'ticket'           => $ticket,
            'statuses'         => self::STATUSES,
            'priorities'       => self::PRIORITIES,
            'areas'            => self::AREAS,
            'audits'           => $audits,
            'statusEvents'     => $statusEvents,
            'segments'         => $segments,
            'uiElapsed'        => $uiElapsed,
            'totalSpan'        => $totalSpan,
            'fmtSecs'          => fn($s) => $this->fmtSecs((int)$s),

            'evidences'        => $evidences,
            'attachments'      => $attachments,

            'lastCancelReason' => $lastCancelReason,
            'lastReopenReason' => $lastReopenReason,
            'lastRejectReason' => $lastRejectReason,
        ];
    }

    private function generateAndAttachCompletionPdf(Ticket $ticket): ?TicketDocument
    {
        if ((string)$ticket->status !== 'completado') return null;

        $data = $this->buildReportData($ticket);

        $pdf = Pdf::loadView('tickets.pdf.report', $data)
            ->setPaper('a4', 'portrait');

        $disk = config('filesystems.default');
        $safeFolio = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string)($ticket->folio ?? ('TKT-' . $ticket->id)));
        $path = "tickets/{$ticket->id}/reporte_{$safeFolio}.pdf";

        $old = TicketDocument::where('ticket_id', $ticket->id)
            ->where('category', 'reporte')
            ->get();

        foreach ($old as $doc) {
            if (!empty($doc->path)) {
                try {
                    Storage::disk($disk)->delete($doc->path);
                } catch (\Throwable $e) {
                }
            }
            try {
                $doc->delete();
            } catch (\Throwable $e) {
            }
        }

        $bin = $pdf->output();
        Storage::disk($disk)->put($path, $bin);

        $doc = TicketDocument::create([
            'ticket_id'    => $ticket->id,
            'uploaded_by'  => auth()->id(),
            'stage_id'     => null,
            'category'     => 'reporte',
            'name'         => "Reporte {$safeFolio}.pdf",
            'path'         => $path,
            'external_url' => null,
            'version'      => 1,
            'meta'         => [
                'mime' => 'application/pdf',
                'size' => strlen($bin),
                'kind' => 'completion_report',
            ],
        ]);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'report_generated',
            'diff'      => [
                'document' => [
                    'name'     => $doc->name,
                    'path'     => $path,
                    'category' => 'reporte',
                ],
            ],
        ]);

        return $doc;
    }

    /** ===================== CRUD ===================== */

    public function index(Request $r)
    {
        $q = Ticket::query()
            ->when($r->filled('status'), fn($qq) => $qq->where('status', $r->string('status')))
            ->when($r->filled('priority'), fn($qq) => $qq->where('priority', $r->string('priority')))
            ->when($r->filled('area'), fn($qq) => $qq->where('area', $r->string('area')))
            ->when($r->filled('assignee'), fn($qq) => $qq->where('assignee_id', $r->integer('assignee')))
            ->when($r->filled('q'), function ($qq) use ($r) {
                $s = trim((string)$r->string('q'));
                $qq->where(function ($w) use ($s) {
                    $w->where('title', 'like', "%{$s}%")
                        ->orWhere('description', 'like', "%{$s}%")
                        ->orWhere('folio', 'like', "%{$s}%");
                });
            })
            ->latest();

        $tickets = $q->paginate(20)->withQueryString();
        $users   = User::orderBy('name')->get(['id', 'name']);

        return view('tickets.index', [
            'tickets'    => $tickets,
            'users'      => $users,
            'statuses'   => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('tickets.create', [
            'users'      => $users,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    public function work(Ticket $ticket)
    {
        // ✅ IMPORTANTE: cargar checklist para que el asignado lo vea en work
        $ticket->load(['assignee', 'creator', 'documents.uploader', 'checklists.items']);

        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para trabajar este ticket.');
        }

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('tickets.work', [
            'ticket'     => $ticket,
            'users'      => $users,
            'statuses'   => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    protected function nextFolio(): string
    {
        $y = now()->year;
        $last = Ticket::whereYear('created_at', $y)->max('id') ?? 0;
        return sprintf('TKT-%d-%04d', $y, $last + 1);
    }

    public function store(Request $r)
    {
        $priorityKeys = implode(',', array_keys(self::PRIORITIES));
        $areaKeys     = implode(',', array_keys(self::AREAS));

        $data = $r->validate([
            'title'       => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'priority'    => ['required', "in:{$priorityKeys}"],
            'area'        => ['required', "in:{$areaKeys}"],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at'      => ['nullable', 'date'],
            'impact'      => ['nullable', 'integer', 'min:1', 'max:5'],
            'urgency'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'effort'      => ['nullable', 'integer', 'min:1', 'max:5'],

            // ✅ payload checklist (viene como string JSON)
            'checklist_payload' => ['nullable', 'string'],

            'files'       => ['nullable', 'array'],
            'files.*'     => ['file'],
        ], [
            'files.array' => 'Archivos inválidos.',
        ]);

        return DB::transaction(function () use ($data, $r) {
            $attrs = $data;
            unset($attrs['files'], $attrs['checklist_payload']);

            $attrs['folio']  = $this->nextFolio();
            $attrs['status'] = 'pendiente';

            if (Schema::hasColumn('tickets', 'created_by')) {
                $attrs['created_by'] = auth()->id();
            }

            if (Schema::hasColumn('tickets', 'assigned_by') && !empty($attrs['assignee_id'])) {
                $attrs['assigned_by'] = auth()->id();
            }

            $impact  = (int)($attrs['impact'] ?? 0);
            $urgency = (int)($attrs['urgency'] ?? 0);
            $effort  = (int)($attrs['effort'] ?? 0);
            if ($impact && $urgency && $effort && Schema::hasColumn('tickets', 'score')) {
                $attrs['score'] = ($impact + $urgency) - $effort;
            }

            $ticket = Ticket::create($attrs);

            // ✅ Guardar checklist desde CREATE (payload JSON)
            $rawPayload = trim((string)$r->input('checklist_payload', ''));
            if ($rawPayload !== '') {
                $payload = json_decode($rawPayload, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($payload)) {
                    app(\App\Http\Controllers\Tickets\TicketChecklistController::class)
                        ->applyPayloadToTicket($ticket, $payload);
                }
            }

            $uploaded = 0;
            $summary = [];

            foreach (($r->file('files') ?? []) as $file) {
                if (!$file) continue;

                $path = $file->store("tickets/{$ticket->id}", ['disk' => config('filesystems.default')]);

                TicketDocument::create([
                    'ticket_id'    => $ticket->id,
                    'uploaded_by'  => auth()->id(),
                    'stage_id'     => null,
                    'category'     => 'adjunto',
                    'name'         => $file->getClientOriginalName(),
                    'path'         => $path,
                    'external_url' => null,
                    'version'      => 1,
                    'meta'         => [
                        'mime' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ],
                ]);

                $uploaded++;
                $summary[] = [
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'ticket_created',
                'diff'      => [
                    'ticket' => [
                        'folio'    => $ticket->folio,
                        'title'    => $ticket->title,
                        'priority' => $ticket->priority,
                        'area'     => $ticket->area,
                        'assignee' => $ticket->assignee_id,
                        'due_at'   => optional($ticket->due_at)->toISOString(),
                        'score'    => $ticket->score,
                        'status'   => $ticket->status,
                    ],
                    'files_uploaded' => $uploaded,
                    'files'          => array_slice($summary, 0, 6),
                ],
            ]);

            if (!empty($ticket->assignee_id)) {
                $u = User::find($ticket->assignee_id);
                if ($u && class_exists(TicketAssigned::class)) {
                    $u->notify(new TicketAssigned($ticket));
                }
            }

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('ok', 'Ticket creado.');
        });
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'assignee',
            'creator',
            'comments.user',
            'documents.uploader',
            'audits.user',

            // ✅ checklist visible en show también
            'checklists.items',
        ]);

        $users = User::orderBy('name')->get(['id', 'name']);

        return view('tickets.show', [
            'ticket'     => $ticket,
            'users'      => $users,
            'statuses'   => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    public function update(Request $r, Ticket $ticket)
    {
        $statusKeys   = implode(',', array_keys(self::STATUSES));
        $priorityKeys = implode(',', array_keys(self::PRIORITIES));
        $areaKeys     = implode(',', array_keys(self::AREAS));

        $data = $r->validate([
            'title'       => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string'],

            'status'      => ['nullable', "in:{$statusKeys}"],
            'priority'    => ['nullable', "in:{$priorityKeys}"],
            'area'        => ['nullable', "in:{$areaKeys}"],

            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at'      => ['nullable', 'date'],

            'impact'      => ['nullable', 'integer', 'min:1', 'max:5'],
            'urgency'     => ['nullable', 'integer', 'min:1', 'max:5'],
            'effort'      => ['nullable', 'integer', 'min:1', 'max:5'],

            'elapsed_seconds' => ['nullable', 'integer', 'min:0', 'max:864000'],
        ]);

        if (array_key_exists('status', $data) && !is_null($data['status'])) {
            if (!$this->canWorkTicket($ticket)) {
                abort(403, 'No tienes permiso para cambiar el estado de este ticket.');
            }
        }

        $before = $ticket->toArray();
        $beforeAssignee = $ticket->assignee_id;

        $ticket->fill(array_filter(
            $data,
            fn($v, $k) => $k !== 'elapsed_seconds' && !is_null($v),
            ARRAY_FILTER_USE_BOTH
        ));

        if (Schema::hasColumn('tickets', 'score')) {
            $impact  = (int)($ticket->impact ?? 0);
            $urgency = (int)($ticket->urgency ?? 0);
            $effort  = (int)($ticket->effort ?? 0);
            if ($impact && $urgency && $effort) {
                $ticket->score = ($impact + $urgency) - $effort;
            }
        }

        if (Schema::hasColumn('tickets', 'completed_at') && $ticket->status === 'completado') {
            $ticket->completed_at = $ticket->completed_at ?: now();
        }
        if (Schema::hasColumn('tickets', 'cancelled_at') && $ticket->status === 'cancelado') {
            $ticket->cancelled_at = $ticket->cancelled_at ?: now();
        }

        if (Schema::hasColumn('tickets', 'assigned_by')) {
            if (array_key_exists('assignee_id', $data) && !is_null($data['assignee_id']) && (int)$data['assignee_id'] !== (int)$beforeAssignee) {
                $ticket->assigned_by = auth()->id();
            }
        }

        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_updated',
            'diff'      => [
                'changed' => array_keys(array_filter(
                    $data,
                    fn($v, $k) => $k !== 'elapsed_seconds' && !is_null($v),
                    ARRAY_FILTER_USE_BOTH
                )),
                'elapsed_seconds' => (int)($data['elapsed_seconds'] ?? 0),
                'before'          => [
                    'status'      => $before['status'] ?? null,
                    'priority'    => $before['priority'] ?? null,
                    'area'        => $before['area'] ?? null,
                    'assignee_id' => $before['assignee_id'] ?? null,
                    'due_at'      => $before['due_at'] ?? null,
                ],
                'after'           => [
                    'status'      => $ticket->status,
                    'priority'    => $ticket->priority,
                    'area'        => $ticket->area,
                    'assignee_id' => $ticket->assignee_id,
                    'due_at'      => optional($ticket->due_at)->toISOString(),
                ],
            ],
        ]);

        if (!empty($ticket->assignee_id) && $ticket->assignee_id !== $beforeAssignee) {
            $u = User::find($ticket->assignee_id);
            if ($u && class_exists(TicketAssigned::class)) {
                $u->notify(new TicketAssigned($ticket));
            }
        }

        return back()->with('ok', 'Ticket actualizado.');
    }

    /** ✅ Subir evidencias (category=evidencia) y auditarlas */
    public function uploadEvidence(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para subir evidencias a este ticket.');
        }

        $data = $r->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file'],
            'note'    => ['nullable', 'string', 'max:2000'],
        ]);

        $disk = config('filesystems.default');

        foreach (($r->file('files') ?? []) as $file) {
            if (!$file) continue;

            $path = $file->store("tickets/{$ticket->id}/evidencias", ['disk' => $disk]);

            $doc = TicketDocument::create([
                'ticket_id'    => $ticket->id,
                'uploaded_by'  => auth()->id(),
                'stage_id'     => null,
                'category'     => 'evidencia',
                'name'         => $file->getClientOriginalName(),
                'path'         => $path,
                'external_url' => null,
                'version'      => 1,
                'meta'         => [
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ],
            ]);

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'evidence_uploaded',
                'diff'      => [
                    'note' => (string)($data['note'] ?? ''),
                    'document' => [
                        'id'       => $doc->id,
                        'name'     => $doc->name,
                        'path'     => $doc->path,
                        'category' => $doc->category,
                        'mime'     => data_get($doc, 'meta.mime'),
                        'size'     => data_get($doc, 'meta.size'),
                    ],
                ],
            ]);
        }

        return back()->with('ok', 'Evidencias subidas.');
    }

    public function submitForReview(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para finalizar este ticket.');
        }

        if (in_array((string)($ticket->status ?? ''), ['completado', 'cancelado'], true)) {
            return back()->with('ok', 'Este ticket ya está cerrado.');
        }

        $beforeStatus = (string)($ticket->status ?? 'pendiente');

        $ticket->status = 'por_revisar';
        if (Schema::hasColumn('tickets', 'submitted_at')) {
            $ticket->submitted_at = now();
        }
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_submitted_for_review',
            'diff'      => [
                'from' => $beforeStatus,
                'to'   => $ticket->status,
            ],
        ]);

        $reviewerIds = $this->reviewerUserIds($ticket);
        if (!empty($reviewerIds) && class_exists(TicketSubmittedForReview::class)) {
            $users = User::whereIn('id', $reviewerIds)->get();
            foreach ($users as $u) {
                $u->notify(new TicketSubmittedForReview($ticket));
            }
        }

        return back()->with('ok', 'Ticket enviado a revisión.');
    }

    /** ✅ Aprobación de revisión: por_revisar -> completado (+PDF) */
    public function reviewApprove(Request $r, Ticket $ticket)
    {
        if (!$this->canReviewTicket($ticket)) {
            abort(403, 'No tienes permiso para aprobar este ticket.');
        }

        $payload = $r->validate([
            'note'            => ['nullable', 'string', 'max:2000'],
            'elapsed_seconds' => ['nullable', 'integer', 'min:0', 'max:864000'],
        ]);

        $beforeStatus = (string)($ticket->status ?? 'pendiente');

        $ticket->status = 'completado';
        if (Schema::hasColumn('tickets', 'completed_at')) $ticket->completed_at = now();
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_review_approved',
            'diff'      => [
                'from' => $beforeStatus,
                'to'   => 'completado',
                'note' => (string)($payload['note'] ?? ''),
                'elapsed_seconds' => (int)($payload['elapsed_seconds'] ?? 0),
            ],
        ]);

        if (class_exists(TicketReviewApproved::class)) {
            $ids = $this->reviewerUserIds($ticket);
            $users = !empty($ids) ? User::whereIn('id', $ids)->get() : collect();
            foreach ($users as $u) {
                $u->notify(new TicketReviewApproved($ticket));
            }
        }

        $this->generateAndAttachCompletionPdf($ticket);

        return back()->with('ok', 'Ticket aprobado y completado. Se generó el PDF.');
    }

    /** ✅ Rechazo de revisión: por_revisar -> reabierto (con motivo) */
    public function reviewReject(Request $r, Ticket $ticket)
    {
        if (!$this->canReviewTicket($ticket)) {
            abort(403, 'No tienes permiso para rechazar este ticket.');
        }

        $payload = $r->validate([
            'reason'          => ['required', 'string', 'max:2000'],
            'note'            => ['nullable', 'string', 'max:2000'],
            'elapsed_seconds' => ['nullable', 'integer', 'min:0', 'max:864000'],
        ]);

        if ((string)$ticket->status === 'cancelado') {
            return back()->with('err', 'No puedes rechazar un ticket cancelado.');
        }

        $beforeStatus = (string)($ticket->status ?? 'pendiente');

        $ticket->status = 'reabierto';
        if (Schema::hasColumn('tickets', 'reopened_at')) $ticket->reopened_at = now();
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_review_rejected',
            'diff'      => [
                'from' => $beforeStatus,
                'to'   => 'reabierto',
                'reason' => (string)$payload['reason'],
                'note'   => (string)($payload['note'] ?? ''),
                'elapsed_seconds' => (int)($payload['elapsed_seconds'] ?? 0),
            ],
        ]);

        if (class_exists(TicketReviewRejected::class) && !empty($ticket->assignee_id)) {
            $u = User::find($ticket->assignee_id);
            if ($u) $u->notify(new TicketReviewRejected($ticket));
        }

        return back()->with('ok', 'Ticket rechazado y reabierto.');
    }

    /** ✅ Reabrir manual (con motivo) */
    public function reopen(Request $r, Ticket $ticket)
    {
        if (!$this->canReviewTicket($ticket) && !$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para reabrir este ticket.');
        }

        $payload = $r->validate([
            'reason'          => ['required', 'string', 'max:2000'],
            'note'            => ['nullable', 'string', 'max:2000'],
            'elapsed_seconds' => ['nullable', 'integer', 'min:0', 'max:864000'],
        ]);

        if ((string)$ticket->status === 'cancelado') {
            return back()->with('err', 'No puedes reabrir un ticket cancelado.');
        }

        $beforeStatus = (string)($ticket->status ?? 'pendiente');

        $ticket->status = 'reabierto';
        if (Schema::hasColumn('tickets', 'reopened_at')) $ticket->reopened_at = now();
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_reopened',
            'diff'      => [
                'from' => $beforeStatus,
                'to'   => 'reabierto',
                'reason' => (string)$payload['reason'],
                'note'   => (string)($payload['note'] ?? ''),
                'elapsed_seconds' => (int)($payload['elapsed_seconds'] ?? 0),
            ],
        ]);

        return back()->with('ok', 'Ticket reabierto.');
    }

    /**
     * ✅ COMPLETE (FIX)
     * Tu modal manda completion_detail y checklist_json.
     * Aquí lo guardamos en auditoría (diff.note) y, si existe columna, en tickets.completion_detail
     */
    public function complete(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para completar este ticket.');
        }

        if (in_array((string)($ticket->status ?? ''), ['completado', 'cancelado'], true)) {
            return back()->with('ok', 'Este ticket ya está cerrado.');
        }

        $payload = $r->validate([
            'elapsed_seconds'   => ['nullable', 'integer', 'min:0', 'max:864000'],
            'completion_detail' => ['required', 'string', 'min:10', 'max:20000'],
            'checklist_json'    => ['nullable', 'string', 'max:200000'],
        ]);

        $beforeStatus = (string)($ticket->status ?? 'pendiente');

        $ticket->status = 'completado';
        if (Schema::hasColumn('tickets', 'completed_at')) {
            $ticket->completed_at = now();
        }

        // ✅ si agregaste columna, se guarda también en tickets
        if (Schema::hasColumn('tickets', 'completion_detail')) {
            $ticket->completion_detail = (string)$payload['completion_detail'];
        }

        $ticket->save();

        // ✅ snapshot opcional del checklist (para el PDF / auditoría)
        $checklistSnap = null;
        $raw = trim((string)($payload['checklist_json'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $checklistSnap = $decoded;
            }
        }

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_completed',
            'diff'      => [
                'from' => $beforeStatus,
                'to'   => 'completado',
                // ✅ AQUÍ queda tu “justificación” del modal
                'note' => (string)$payload['completion_detail'],
                'checklist_snapshot' => $checklistSnap,
                'elapsed_seconds' => (int)($payload['elapsed_seconds'] ?? 0),
            ],
        ]);

        $this->generateAndAttachCompletionPdf($ticket);

        return back()->with('ok', 'Ticket completado. Se guardó el detalle y se generó el PDF de reporte.');
    }

    public function cancel(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para cancelar este ticket.');
        }

        $payload = $r->validate([
            'reason'          => ['nullable', 'string', 'max:2000'],
            'cancel_reason'   => ['nullable', 'string', 'max:2000'],
            'elapsed_seconds' => ['nullable', 'integer', 'min:0', 'max:864000'],
            'note'            => ['nullable', 'string', 'max:2000'],
        ]);

        $reason = trim((string)($payload['reason'] ?? $payload['cancel_reason'] ?? ''));
        if ($reason === '') {
            return back()->with('err', 'Falta el motivo de cancelación.');
        }

        $beforeStatus = (string)($ticket->status ?? 'pendiente');

        $ticket->status = 'cancelado';
        if (Schema::hasColumn('tickets', 'cancelled_at')) $ticket->cancelled_at = now();
        if (Schema::hasColumn('tickets', 'cancel_reason')) $ticket->cancel_reason = $reason;
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_cancelled',
            'diff'      => [
                'from' => $beforeStatus,
                'to'   => 'cancelado',
                'reason' => $reason,
                'note' => (string)($payload['note'] ?? ''),
                'elapsed_seconds' => (int)($payload['elapsed_seconds'] ?? 0),
            ],
        ]);

        return back()->with('ok', 'Ticket cancelado.');
    }

    public function reportPdf(Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket) && !$this->canReviewTicket($ticket)) {
            abort(403, 'No tienes permiso para ver este reporte.');
        }

        $data = $this->buildReportData($ticket);

        $pdf = Pdf::loadView('tickets.pdf.report', $data)
            ->setPaper('a4', 'portrait');

        $folio = $ticket->folio ?: ('TKT-' . $ticket->id);
        $name = 'Reporte_' . $folio . '.pdf';

        return $pdf->download($name);
    }
}