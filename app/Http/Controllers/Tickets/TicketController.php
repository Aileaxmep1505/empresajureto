<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

use App\Models\{
    Ticket,
    TicketStage,
    TicketAudit,
    TicketDocument,
    TicketLink,
    TicketChecklist,
    TicketChecklistItem,
    User
};

class TicketController extends Controller
{
    /**
     * Opciones de proceso dentro de la licitación.
     */
    private const PHASES = [
        'analisis_bases' => 'Análisis de bases',
        'preguntas'      => 'Preguntas / aclaraciones',
        'cotizacion'     => 'Cotización',
        'muestras'       => 'Muestras',
        'ir_por_pedido'  => 'Ir por pedido',
        'entrega'        => 'Entrega',
        'seguimiento'    => 'Seguimiento / otros',
    ];

    /** Listado con filtros básicos (solo licitaciones) */
    public function index(Request $r)
    {
        $q = Ticket::query()
            ->where('type', 'licitacion')
            ->when($r->filled('status'), fn($qq) => $qq->where('status', $r->string('status')))
            ->when($r->filled('priority'), fn($qq) => $qq->where('priority', $r->string('priority')))
            ->when($r->filled('phase'), fn($qq) => $qq->where('licitacion_phase', $r->string('phase')))
            ->when($r->filled('owner_id'), fn($qq) => $qq->where('owner_id', $r->integer('owner_id')))
            ->when($r->filled('q'), function ($qq) use ($r) {
                $s = $r->string('q');
                $qq->where(function ($w) use ($s) {
                    $w->where('title', 'like', "%{$s}%")
                        ->orWhere('client_name', 'like', "%{$s}%")
                        ->orWhere('numero_licitacion', 'like', "%{$s}%");
                });
            })
            ->latest();

        $tickets = $q->paginate(20)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    /** Formulario de creación */
    public function create()
    {
        // Para selector de responsables por nombre
        $users = User::orderBy('name')->get();

        return view('tickets.create', compact('users'));
    }

    /** Genera un folio tipo TKT-YYYY-#### */
    protected function nextFolio(): string
    {
        $y    = now()->year;
        $last = Ticket::whereYear('created_at', $y)->max('id') ?? 0;

        return sprintf('TKT-%d-%04d', $y, $last + 1);
    }

    /**
     * Guarda ticket de licitación + etapas por defecto.
     */
    public function store(Request $r)
    {
        $phaseKeys = implode(',', array_keys(self::PHASES));

        $data = $r->validate([
            'client_id'         => ['nullable', 'integer'],
            'client_name'       => ['nullable', 'string', 'max:180'],
            'title'             => ['required', 'string', 'max:180'],

            // Todos son de licitación, pero validamos por si llega algo
            'type'              => ['nullable', 'string'],

            'licitacion_phase'  => ['required', "in:{$phaseKeys}"],
            'priority'          => ['required', 'in:alta,media,baja'],
            'owner_id'          => ['nullable', 'integer', 'exists:users,id'],
            'due_at'            => ['nullable', 'date'],

            'numero_licitacion' => ['nullable', 'string', 'max:120'],
            'monto_propuesta'   => ['nullable', 'numeric'],
            'link_inicial'      => ['nullable', 'url'],

            // Notas rápidas que sí se guardan
            'quick_notes'       => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $r) {
            // Tipo forzado a licitación
            $attrs = array_merge($data, [
                'type'      => 'licitacion',
                'folio'     => $this->nextFolio(),
                'status'    => 'revision',
                'opened_at' => now(),
            ]);

            // created_by si existe la columna
            if (Schema::hasColumn('tickets', 'created_by')) {
                $attrs['created_by'] = auth()->id();
            }

            $ticket = Ticket::create($attrs);

            // Etapas por defecto, orientadas a licitaciones
            $defaultStages = [
                'Recepción de bases / documentos',
                'Análisis técnico / comercial',
                'Preguntas y aclaraciones',
                'Cotización y envío',
                'Muestras / pedido',
                'Entrega y cierre',
            ];

            foreach ($defaultStages as $i => $name) {
                TicketStage::create([
                    'ticket_id' => $ticket->id,
                    'position'  => $i + 1,
                    'name'      => $name,
                    'status'    => 'pendiente',
                ]);
            }

            // Enlace inicial
            if (!empty($data['link_inicial'])) {
                TicketLink::create([
                    'ticket_id' => $ticket->id,
                    'label'     => 'Enlace inicial',
                    'url'       => $data['link_inicial'],
                ]);
            }

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'ticket_created',
                'diff'      => ['payload' => $data],
            ]);

            return redirect()
                ->route('tickets.show', $ticket)
                ->with('ok', 'Ticket creado');
        });
    }

    /** Detalle del ticket */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'stages.checklists.items',
            'comments.user',
            'documents.uploader',
            'links',
            'audits',
        ]);

        return view('tickets.show', compact('ticket'));
    }

    /**
     * Actualiza campos básicos del ticket de licitación.
     */
    public function update(Request $r, Ticket $ticket)
    {
        $phaseKeys = implode(',', array_keys(self::PHASES));

        $data = $r->validate([
            'title'             => ['nullable', 'string', 'max:180'],
            'priority'          => ['nullable', 'in:alta,media,baja'],
            'status'            => ['nullable', 'in:revision,proceso,finalizado,cerrado'],
            'owner_id'          => ['nullable', 'integer', 'exists:users,id'],
            'due_at'            => ['nullable', 'date'],
            'numero_licitacion' => ['nullable', 'string', 'max:120'],
            'monto_propuesta'   => ['nullable', 'numeric'],
            'estatus_adjudicacion' => ['nullable', 'in:en_espera,ganada,perdida'],

            'licitacion_phase'  => ['nullable', "in:{$phaseKeys}"],
            'quick_notes'       => ['nullable', 'string'],
        ]);

        $before = $ticket->toArray();

        // Solo llenamos lo que venga no nulo
        $ticket->fill(array_filter($data, fn($v) => !is_null($v)))->save();
        $ticket->refreshProgress();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_updated',
            'diff'      => [
                'before' => $before,
                'after'  => $ticket->fresh()->toArray(),
            ],
        ]);

        return back()->with('ok', 'Actualizado');
    }

    /** Cerrar ticket (usado por ruta tickets.close) */
    public function close(Request $r, Ticket $ticket)
    {
        $before = $ticket->toArray();

        $ticket->status = 'cerrado';
        if (Schema::hasColumn('tickets', 'closed_at')) {
            $ticket->closed_at = now();
        }
        if (Schema::hasColumn('tickets', 'closed_by')) {
            $ticket->closed_by = auth()->id();
        }
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_closed',
            'diff'      => [
                'before' => $before,
                'after'  => $ticket->fresh()->toArray(),
            ],
        ]);

        return back()->with('ok', 'Ticket cerrado');
    }

    /** Configurador: crear etapa */
    public function storeStage(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'name' => ['required', 'string', 'max:160'],
        ]);

        $pos = (int) $ticket->stages()->max('position') + 1;

        $stage = TicketStage::create([
            'ticket_id' => $ticket->id,
            'position'  => $pos,
            'name'      => $data['name'],
            'status'    => 'pendiente',
        ]);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'stage_created',
            'diff'      => ['stage_id' => $stage->id, 'name' => $stage->name],
        ]);

        return back()->with('ok', 'Etapa agregada');
    }

    /** Configurador: eliminar etapa + cascade */
    public function destroyStage(Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        DB::transaction(function () use ($ticket, $stage) {
            $stage->load('checklists.items');

            // Borrar checklists + items
            foreach ($stage->checklists as $chk) {
                TicketChecklistItem::where('checklist_id', $chk->id)->delete();
                $chk->delete();
            }

            // Borrar documentos (y archivos físicos)
            $docs = $stage->documents()->get();
            foreach ($docs as $d) {
                if ($d->path && Storage::exists($d->path)) {
                    Storage::delete($d->path);
                }
                $d->delete();
            }

            $deletedPos = $stage->position;
            $stage->delete();

            // Reindexar positions
            $ticket->stages()
                ->where('position', '>', $deletedPos)
                ->orderBy('position')
                ->get()
                ->each(function ($s) {
                    $s->position = $s->position - 1;
                    $s->save();
                });

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'stage_deleted',
                'diff'      => ['position_removed' => $deletedPos],
            ]);

            $ticket->refreshProgress();
        });

        return back()->with('ok', 'Etapa eliminada y posiciones reindexadas');
    }

    /** Eliminar documento del ticket */
    public function destroyDocument(Ticket $ticket, TicketDocument $document)
    {
        abort_unless($document->ticket_id === $ticket->id, 404);

        if ($document->path && Storage::exists($document->path)) {
            Storage::delete($document->path);
        }
        $document->delete();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'document_deleted',
            'diff'      => ['document_id' => $document->id],
        ]);

        return back()->with('ok', 'Documento eliminado');
    }

    /** Poll básico (vista ejecutor) */
    public function poll(Request $r, Ticket $ticket)
    {
        $ticket->load(['stages.checklists.items', 'documents', 'comments']);

        $stages = $ticket->stages->map(function ($s) {
            $done  = $s->checklists->flatMap->items->where('is_done', true)->count();
            $total = $s->checklists->flatMap->items->count();

            return [
                'id'          => $s->id,
                'position'    => $s->position,
                'name'        => $s->name,
                'status'      => $s->status,
                'due_at'      => optional($s->due_at)->toDateTimeString(),
                'assignee'    => optional($s->assignee)->name ?? null,
                'check_done'  => $done,
                'check_total' => $total,
                'evidences'   => $s->documents()->count(),
                'signal'      => method_exists($s, 'slaSignal') ? $s->slaSignal() : 'neutral',
            ];
        })->values();

        // Señal de prioridad para colores en frontend
        $prioritySignal = 'neutral';
        if ($ticket->priority === 'alta') {
            $prioritySignal = 'danger';
        } elseif ($ticket->priority === 'media') {
            $prioritySignal = 'warn';
        } elseif ($ticket->priority === 'baja') {
            $prioritySignal = 'ok';
        }

        return response()->json([
            'ok' => true,
            'ticket' => [
                'id'              => $ticket->id,
                'folio'           => $ticket->folio,
                'status'          => $ticket->status,
                'priority'        => $ticket->priority,
                'priority_signal' => $prioritySignal,
                'progress'        => $ticket->progress,
                'due_at'          => optional($ticket->due_at)->toDateTimeString(),
                'phase'           => $ticket->licitacion_phase,
            ],
            'stages' => $stages,
            'counts' => [
                'documents' => $ticket->documents->count(),
                'comments'  => $ticket->comments->count(),
            ],
            'ts' => now()->timestamp,
        ]);
    }

    /* ======= Compatibilidad con tus rutas AJAX existentes ======= */

    /** Iniciar etapa por AJAX (validación secuencial) */
    public function ajaxStartStage(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $prev = $ticket->stages()->where('position', '<', $stage->position)
            ->orderByDesc('position')->first();

        if ($prev && $prev->status !== 'terminado') {
            return response()->json(['ok' => false, 'msg' => 'Debes completar la etapa anterior.'], 422);
        }

        if ($stage->status === 'pendiente') {
            $upd = ['status' => 'en_progreso'];
            if (Schema::hasColumn('ticket_stages', 'started_at')) {
                $upd['started_at'] = now();
            }
            $stage->update($upd);

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'stage_started',
                'diff'      => ['stage_id' => $stage->id],
            ]);
        }

        return response()->json([
            'ok'         => true,
            'stage_id'   => $stage->id,
            'status'     => $stage->status,
            'started_at' => Schema::hasColumn('ticket_stages', 'started_at') ? $stage->started_at : null,
        ]);
    }

    /** Completar etapa por AJAX (valida checklist/evidencia) */
    public function ajaxCompleteStage(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $ids = $stage->checklists()->pluck('id');

        $pending = $ids->isEmpty()
            ? 0
            : TicketChecklistItem::whereIn('checklist_id', $ids)->where('is_done', false)->count();

        if ($pending > 0) {
            return response()->json(['ok' => false, 'msg' => 'Faltan items del checklist.'], 422);
        }

        if (Schema::hasColumn('ticket_stages', 'requires_evidence') && $stage->requires_evidence) {
            if (!$stage->documents()->exists()) {
                return response()->json(['ok' => false, 'msg' => 'Debes subir evidencia para cerrar la etapa.'], 422);
            }
        }

        $upd = ['status' => 'terminado'];
        if (Schema::hasColumn('ticket_stages', 'finished_at')) {
            $upd['finished_at'] = now();
        }
        $stage->update($upd);

        $ticket->refreshProgress();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'stage_completed',
            'diff'      => ['stage_id' => $stage->id],
        ]);

        return response()->json(['ok' => true, 'progress' => $ticket->progress]);
    }

    /** Subir evidencia por AJAX (archivo o link) */
    public function ajaxUploadEvidence(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $data = $r->validate([
            'file' => [
                'nullable',
                'file',
                'max:40960',
                'mimetypes:image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation',
            ],
            'link' => ['nullable', 'url'],
        ]);

        if ($r->hasFile('file')) {
            $path = $r->file('file')->store("tickets/{$ticket->id}/stage_{$stage->id}");
            TicketDocument::create([
                'ticket_id'   => $ticket->id,
                'uploaded_by' => auth()->id(),
                'stage_id'    => $stage->id,
                'category'    => 'evidencia',
                'name'        => $r->file('file')->getClientOriginalName(),
                'path'        => $path,
                'version'     => 1,
                'meta'        => ['type' => 'evidence'],
            ]);
        }

        if (!empty($data['link'])) {
            TicketDocument::create([
                'ticket_id'   => $ticket->id,
                'uploaded_by' => auth()->id(),
                'stage_id'    => $stage->id,
                'category'    => 'evidencia',
                'name'        => 'Evidencia (link)',
                'path'        => null,
                'version'     => 1,
                'meta'        => ['type' => 'evidence', 'url' => $data['link']],
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
