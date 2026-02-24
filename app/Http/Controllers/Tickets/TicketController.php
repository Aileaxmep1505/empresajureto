<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use App\Models\{
    Ticket,
    TicketAudit,
    TicketDocument,
    User
};

use App\Notifications\TicketAssigned;

class TicketController extends Controller
{
    // ✅ Workflow “pro”
    public const STATUSES = [
        'pendiente'   => 'Pendiente',
        'revision'    => 'En revisión',
        'progreso'    => 'En progreso',
        'bloqueado'   => 'En espera (bloqueado)',
        'pruebas'     => 'En pruebas',
        'completado'  => 'Completado',
        'cancelado'   => 'Cancelado',
    ];

    // ✅ Prioridades reales
    public const PRIORITIES = [
        'critica' => 'Crítica',
        'alta'    => 'Alta',
        'media'   => 'Media',
        'baja'    => 'Baja',
        'mejora'  => 'Mejora futura',
    ];

    // ✅ Áreas sugeridas
    public const AREAS = [
        'contabilidad'      => 'Contabilidad',
        'logistica'         => 'Logística',
        'inventario_medico' => 'Inventario Médico',
        'mercado_libre'     => 'Mercado Libre',
        'amazon_fba'        => 'Amazon FBA',
        'desarrollo'        => 'Desarrollo',
        'marketing'         => 'Marketing',
        'administracion'    => 'Administración',
    ];

    /** Permiso simple: asignado o creador (ajústalo si quieres) */
    private function canWorkTicket(Ticket $ticket): bool
    {
        $uid = auth()->id();
        if (!$uid) return false;

        // asignado
        if (!empty($ticket->assignee_id) && (int)$ticket->assignee_id === (int)$uid) return true;

        // creador
        if (!empty($ticket->created_by) && (int)$ticket->created_by === (int)$uid) return true;

        // si tienes roles, aquí puedes permitir admin
        // if (auth()->user()?->hasRole('admin')) return true;

        return false;
    }

    /** Listado con filtros */
    public function index(Request $r)
    {
        $q = Ticket::query()
            ->when($r->filled('status'),   fn($qq) => $qq->where('status', $r->string('status')))
            ->when($r->filled('priority'), fn($qq) => $qq->where('priority', $r->string('priority')))
            ->when($r->filled('area'),     fn($qq) => $qq->where('area', $r->string('area')))
            ->when($r->filled('assignee'), fn($qq) => $qq->where('assignee_id', $r->integer('assignee')))
            ->when($r->filled('q'), function ($qq) use ($r) {
                $s = trim((string) $r->string('q'));
                $qq->where(function ($w) use ($s) {
                    $w->where('title', 'like', "%{$s}%")
                      ->orWhere('description', 'like', "%{$s}%")
                      ->orWhere('folio', 'like', "%{$s}%");
                });
            })
            ->latest();

        $tickets = $q->paginate(20)->withQueryString();
        $users   = User::orderBy('name')->get(['id','name']);

        return view('tickets.index', [
            'tickets'    => $tickets,
            'users'      => $users,
            'statuses'   => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    /** Form crear */
    public function create()
    {
        $users = User::orderBy('name')->get(['id','name']);

        return view('tickets.create', [
            'users'      => $users,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    /** Vista de trabajo para asignado */
    public function work(Ticket $ticket)
    {
        $ticket->load(['assignee','creator','documents.uploader']);

        // si no tiene asignado, puedes bloquear o permitir
        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para trabajar este ticket.');
        }

        $users = User::orderBy('name')->get(['id','name']);

        return view('tickets.work', [
            'ticket'     => $ticket,
            'users'      => $users,
            'statuses'   => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    /** Folio TKT-YYYY-#### */
    protected function nextFolio(): string
    {
        $y = now()->year;
        $last = Ticket::whereYear('created_at', $y)->max('id') ?? 0;
        return sprintf('TKT-%d-%04d', $y, $last + 1);
    }

    /** Crear ticket + subir archivos mínimos (3) */
    public function store(Request $r)
    {
        $priorityKeys = implode(',', array_keys(self::PRIORITIES));
        $areaKeys     = implode(',', array_keys(self::AREAS));

        $data = $r->validate([
            'title'       => ['required','string','max:180'],
            'description' => ['nullable','string'],

            // ❌ status ya NO se captura en la vista
            'priority'    => ['required', "in:{$priorityKeys}"],
            'area'        => ['required', "in:{$areaKeys}"],

            'assignee_id' => ['nullable','integer','exists:users,id'],
            'due_at'      => ['nullable','date'],

            // ✅ Score inputs opcionales
            'impact'      => ['nullable','integer','min:1','max:5'],
            'urgency'     => ['nullable','integer','min:1','max:5'],
            'effort'      => ['nullable','integer','min:1','max:5'],

            // ✅ Archivos: mínimo 3
            'files'       => ['required','array','min:3'],
            'files.*'     => ['file'],
        ], [
            'files.required' => 'Debes subir mínimo 3 archivos.',
            'files.array'    => 'Archivos inválidos.',
            'files.min'      => 'Debes subir mínimo 3 archivos.',
        ]);

        return DB::transaction(function () use ($data, $r) {

            // === attrs para tickets (SIN files) ===
            $attrs = $data;
            unset($attrs['files']);

            // defaults
            $attrs['folio'] = $this->nextFolio();

            // ✅ FORZADO (pero ojo: tu BD debe permitir este valor)
            $attrs['status'] = 'pendiente';

            // creator
            if (Schema::hasColumn('tickets', 'created_by')) {
                $attrs['created_by'] = auth()->id();
            }

            // score automático
            $impact  = (int) ($attrs['impact']  ?? 0);
            $urgency = (int) ($attrs['urgency'] ?? 0);
            $effort  = (int) ($attrs['effort']  ?? 0);
            if ($impact && $urgency && $effort && Schema::hasColumn('tickets','score')) {
                $attrs['score'] = ($impact + $urgency) - $effort;
            }

            // ⚠️ Si tu status es ENUM sin 'pendiente', aquí truena con Data truncated.
            // En ese caso corre la migración de abajo.
            $ticket = Ticket::create($attrs);

            // ===== Guardar archivos en ticket_documents =====
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

            if ($uploaded < 3) {
                throw ValidationException::withMessages([
                    'files' => 'Debes subir mínimo 3 archivos (no se detectaron 3 archivos válidos).',
                ]);
            }

            // ✅ Audit entendible (sin arrays raros)
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
                    'files' => array_slice($summary, 0, 6), // muestra máximo 6 en log
                ],
            ]);

            // notificar asignado
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

    /** Detalle */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'assignee',
            'creator',
            'comments.user',
            'documents.uploader',
            'audits.user',
        ]);

        $users = User::orderBy('name')->get(['id','name']);

        return view('tickets.show', [
            'ticket'     => $ticket,
            'users'      => $users,
            'statuses'   => self::STATUSES,
            'priorities' => self::PRIORITIES,
            'areas'      => self::AREAS,
        ]);
    }

    /** Update (para workflow y cambios puntuales) */
    public function update(Request $r, Ticket $ticket)
    {
        $statusKeys   = implode(',', array_keys(self::STATUSES));
        $priorityKeys = implode(',', array_keys(self::PRIORITIES));
        $areaKeys     = implode(',', array_keys(self::AREAS));

        $data = $r->validate([
            'title'       => ['nullable','string','max:180'],
            'description' => ['nullable','string'],

            'status'      => ['nullable', "in:{$statusKeys}"],
            'priority'    => ['nullable', "in:{$priorityKeys}"],
            'area'        => ['nullable', "in:{$areaKeys}"],

            'assignee_id' => ['nullable','integer','exists:users,id'],
            'due_at'      => ['nullable','date'],

            'impact'      => ['nullable','integer','min:1','max:5'],
            'urgency'     => ['nullable','integer','min:1','max:5'],
            'effort'      => ['nullable','integer','min:1','max:5'],
        ]);

        // ✅ Si viene status, solo asignado/creador lo puede mover (para “work”)
        if (array_key_exists('status', $data) && !is_null($data['status'])) {
            if (!$this->canWorkTicket($ticket)) {
                abort(403, 'No tienes permiso para cambiar el estado de este ticket.');
            }
        }

        $before = $ticket->toArray();
        $beforeAssignee = $ticket->assignee_id;

        $ticket->fill(array_filter($data, fn($v) => !is_null($v)));

        // recalcular score si aplica
        if (Schema::hasColumn('tickets','score')) {
            $impact  = (int) ($ticket->impact  ?? 0);
            $urgency = (int) ($ticket->urgency ?? 0);
            $effort  = (int) ($ticket->effort  ?? 0);
            if ($impact && $urgency && $effort) {
                $ticket->score = ($impact + $urgency) - $effort;
            }
        }

        // timestamps status
        if (Schema::hasColumn('tickets','completed_at') && $ticket->status === 'completado') {
            $ticket->completed_at = $ticket->completed_at ?: now();
        }
        if (Schema::hasColumn('tickets','cancelled_at') && $ticket->status === 'cancelado') {
            $ticket->cancelled_at = $ticket->cancelled_at ?: now();
        }

        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_updated',
            'diff'      => [
                'changed' => array_keys(array_filter($data, fn($v) => !is_null($v))),
                'before'  => [
                    'status'      => $before['status'] ?? null,
                    'priority'    => $before['priority'] ?? null,
                    'area'        => $before['area'] ?? null,
                    'assignee_id' => $before['assignee_id'] ?? null,
                    'due_at'      => $before['due_at'] ?? null,
                ],
                'after'   => [
                    'status'      => $ticket->status,
                    'priority'    => $ticket->priority,
                    'area'        => $ticket->area,
                    'assignee_id' => $ticket->assignee_id,
                    'due_at'      => optional($ticket->due_at)->toISOString(),
                ],
            ],
        ]);

        // notificar si cambió asignado
        if (!empty($ticket->assignee_id) && $ticket->assignee_id !== $beforeAssignee) {
            $u = User::find($ticket->assignee_id);
            if ($u && class_exists(TicketAssigned::class)) {
                $u->notify(new TicketAssigned($ticket));
            }
        }

        return back()->with('ok', 'Ticket actualizado.');
    }

    /** Completar */
    public function complete(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para completar este ticket.');
        }

        $before = $ticket->toArray();

        $ticket->status = 'completado';
        if (Schema::hasColumn('tickets','completed_at')) $ticket->completed_at = now();
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_completed',
            'diff'      => [
                'from' => $before['status'] ?? null,
                'to'   => $ticket->status,
            ],
        ]);

        return back()->with('ok', 'Ticket completado.');
    }

    /** Cancelar */
    public function cancel(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) {
            abort(403, 'No tienes permiso para cancelar este ticket.');
        }

        $before = $ticket->toArray();

        $ticket->status = 'cancelado';
        if (Schema::hasColumn('tickets','cancelled_at')) $ticket->cancelled_at = now();
        $ticket->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'ticket_cancelled',
            'diff'      => [
                'from' => $before['status'] ?? null,
                'to'   => $ticket->status,
            ],
        ]);

        return back()->with('ok', 'Ticket cancelado.');
    }
}