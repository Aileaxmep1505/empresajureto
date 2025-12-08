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
    TicketChecklist,
    TicketChecklistItem,
    TicketDocument,
    TicketAudit
};

use App\Services\AiDynamicChecklistService;

class TicketStageController extends Controller
{
    /* =========================================================
     * 1) CREAR ETAPA MANUAL (configurador)
     * ========================================================= */
    public function store(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'name'              => ['required','string','max:180'],
            'assignee_id'       => ['nullable','integer'],
            'due_at'            => ['nullable','date'], // si luego quieres datetime-local, se cambia aquí
            'ai_prompt'         => ['nullable','string','max:2000'],
            'requires_evidence' => ['nullable','boolean'],
        ]);

        $position = (int) $ticket->stages()->max('position') + 1;

        $attrs = [
            'position'    => $position,
            'name'        => $data['name'],
            'status'      => 'pendiente',
            'assignee_id' => $data['assignee_id'] ?? null,
        ];

        if (!empty($data['due_at'])) {
            $attrs['due_at'] = $data['due_at'];
        }
        if (Schema::hasColumn('ticket_stages', 'ai_prompt') && !empty($data['ai_prompt'])) {
            $attrs['ai_prompt'] = $data['ai_prompt'];
        }
        if (Schema::hasColumn('ticket_stages', 'requires_evidence')) {
            $attrs['requires_evidence'] = (bool)($data['requires_evidence'] ?? false);
        }

        $stage = $ticket->stages()->create($attrs);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'stage_created',
            'diff'      => ['stage_id' => $stage->id],
        ]);

        return back()->with('ok','Etapa creada');
    }

    /* =========================================================
     * 2) IA – SUGERIR CHECKLIST (para la vista con preview)
     *    Ruta sugerida: tickets.ai.suggest
     * ========================================================= */
    public function aiSuggest(
        Request $r,
        Ticket $ticket,
        TicketStage $stage,
        AiDynamicChecklistService $ai = null
    ) {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $data = $r->validate([
            'prompt' => ['required','string','max:2000'],
        ]);

        $prompt = $data['prompt'];

        // Construir contexto para la IA
        $context = [
            'ticket' => [
                'folio'    => $ticket->folio,
                'type'     => $ticket->type,
                'priority' => $ticket->priority,
                'client'   => $ticket->client_name ?? optional($ticket->client)->name,
            ],
            'stage' => [
                'name'     => $stage->name,
                'position' => $stage->position,
            ],
            'current_year' => now()->year,
        ];

        $title        = 'Checklist sugerido';
        $instructions = 'Sigue los puntos y adjunta evidencia cuando aplique.';
        $items        = collect();

        try {
            if (!$ai) {
                $ai = app(AiDynamicChecklistService::class);
            }

            $out = $ai->checklistFor($prompt, $context);

            $title        = (string) ($out['title'] ?? $title);
            $instructions = (string) ($out['instructions'] ?? $instructions);

            $items = collect($out['items'] ?? [])
                ->map(fn($it) => trim((string) ($it['text'] ?? '')))
                ->filter()
                ->values();
        } catch (\Throwable $e) {
            // Si falla la IA, usamos fallback local
        }

        // Fallback local si IA no dio nada útil
        if ($items->isEmpty()) {
            $items = $this->fallbackItemsFromPrompt($prompt);
            if ($items->isEmpty()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'La IA no pudo generar un checklist útil. Ajusta el texto.',
                ], 422);
            }
        }

        // Aquí NO guardamos nada aún: solo devolvemos para preview
        return response()->json([
            'ok'           => true,
            'title'        => $title,
            'instructions' => $instructions,
            'items'        => $items->values()->all(),
        ]);
    }

    /* =========================================================
     * 3) IA – CREAR CHECKLIST DESDE EL PREVIEW
     *    Ruta sugerida: tickets.ai.create
     * ========================================================= */
    public function aiCreate(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'stage_id'     => ['required','integer'],
            'title'        => ['required','string','max:180'],
            'items'        => ['required','array','min:8','max:12'],
            'items.*'      => ['required','string','max:500'],
        ]);

        $stage = TicketStage::where('id', $data['stage_id'])
            ->where('ticket_id', $ticket->id)
            ->firstOrFail();

        $items = collect($data['items'])
            ->map(fn($s) => trim($s))
            ->filter()
            ->values();

        if ($items->count() < 8 || $items->count() > 12) {
            return response()->json([
                'ok'      => false,
                'message' => 'Debes tener entre 8 y 12 puntos.',
            ], 422);
        }

        $title        = $data['title'];
        $instructions = "Checklist generado automáticamente para la etapa {$stage->name} de la licitación.";

        $this->createChecklistForStage($ticket, $stage, $title, $instructions, $items->all());

        return response()->json(['ok' => true]);
    }

    /* =========================================================
     * 4) BACKCOMPAT – Generar y crear checklist en un solo paso
     *    (si aún tienes una ruta vieja apuntando aquí)
     * ========================================================= */
    public function generateChecklist(
        Request $r,
        Ticket $ticket,
        TicketStage $stage,
        AiDynamicChecklistService $ai = null
    ) {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $data = $r->validate([
            'prompt' => ['required','string','max:2000'],
        ]);

        $prompt = $data['prompt'];

        $context = [
            'ticket' => [
                'folio'    => $ticket->folio,
                'type'     => $ticket->type,
                'priority' => $ticket->priority,
                'client'   => $ticket->client_name ?? optional($ticket->client)->name,
            ],
            'stage' => [
                'name'     => $stage->name,
                'position' => $stage->position,
            ],
            'current_year' => now()->year,
        ];

        $title        = 'Checklist generado';
        $instructions = 'Sigue los puntos y adjunta evidencia cuando aplique.';
        $items        = collect();

        try {
            if (!$ai) {
                $ai = app(AiDynamicChecklistService::class);
            }

            $out = $ai->checklistFor($prompt, $context);

            $title        = (string) ($out['title'] ?? $title);
            $instructions = (string) ($out['instructions'] ?? $instructions);

            $items = collect($out['items'] ?? [])
                ->map(fn($it) => trim((string) ($it['text'] ?? '')))
                ->filter()
                ->values();
        } catch (\Throwable $e) {
            // deja seguir a fallback
        }

        if ($items->isEmpty()) {
            $items = $this->fallbackItemsFromPrompt($prompt);
        }

        if ($items->isEmpty()) {
            return back()->with('err', 'La IA no pudo generar un checklist. Ajusta el texto.');
        }

        $this->createChecklistForStage($ticket, $stage, $title, $instructions, $items->all());

        return back()->with('ok','Checklist generado para la etapa.');
    }

    /* =========================================================
     * 5) Marcar ítem de checklist (AJAX)
     * ========================================================= */
    public function toggleItem(Request $r, Ticket $ticket, TicketChecklistItem $item)
    {
        $r->validate(['done' => ['required','boolean']]);

        $chk   = $item->checklist;
        $stage = $chk->stage;

        abort_unless($chk->ticket_id === $ticket->id, 404);

        // Solo el responsable de la etapa (o ajusta según roles/permisos)
        if ($stage->assignee_id && $stage->assignee_id !== auth()->id()) {
            return response()->json([
                'ok'  => false,
                'msg' => 'No eres el responsable de esta etapa.',
            ], 403);
        }

        $isDone = (bool) $r->boolean('done');

        $item->is_done = $isDone;

        if (Schema::hasColumn('ticket_checklist_items','done_at')) {
            $item->done_at = $isDone ? now() : null;
        }
        if (Schema::hasColumn('ticket_checklist_items','done_by')) {
            $item->done_by = $isDone ? auth()->id() : null;
        }

        $item->save();

        return response()->json([
            'ok'       => true,
            'item_id'  => $item->id,
            'is_done'  => $item->is_done,
        ]);
    }

    /* =========================================================
     * 6) Iniciar etapa (secuencial)
     * ========================================================= */
    public function start(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        if (!$this->prevStageIsDone($ticket, $stage)) {
            return response()->json([
                'ok'  => false,
                'msg' => 'Debes completar la etapa anterior.',
            ], 422);
        }

        if ($stage->status === 'pendiente') {
            $upd = ['status' => 'en_progreso'];
            if (Schema::hasColumn('ticket_stages','started_at')) {
                $upd['started_at'] = now();
            }
            $stage->update($upd);

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'stage_started',
                'diff'      => ['stage_id'=>$stage->id],
            ]);
        }

        return response()->json([
            'ok'         => true,
            'stage_id'   => $stage->id,
            'status'     => $stage->status,
            'started_at' => Schema::hasColumn('ticket_stages','started_at')
                ? $stage->started_at
                : null,
        ]);
    }

    /* =========================================================
     * 7) Completar etapa (valida checklist + evidencia)
     * ========================================================= */
    public function complete(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        if ($this->checklistPendingCount($stage) > 0) {
            return response()->json([
                'ok'  => false,
                'msg' => 'Faltan items del checklist.',
            ], 422);
        }

        if ($this->requiresEvidence($stage) && !$stage->documents()->exists()) {
            return response()->json([
                'ok'  => false,
                'msg' => 'Debes subir evidencia para cerrar la etapa.',
            ], 422);
        }

        $upd = ['status' => 'terminado'];
        if (Schema::hasColumn('ticket_stages','finished_at')) {
            $upd['finished_at'] = now();
        }
        $stage->update($upd);

        $ticket->refreshProgress();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'stage_completed',
            'diff'      => ['stage_id'=>$stage->id],
        ]);

        return response()->json([
            'ok'       => true,
            'progress' => $ticket->progress,
        ]);
    }

    /* =========================================================
     * 8) Subir evidencia (archivo o link) a la etapa
     * ========================================================= */
    public function evidence(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $data = $r->validate([
            'file'=>['nullable','file','max:40960','mimetypes:image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'],
            'link'=>['nullable','url'],
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
                'meta'        => ['type'=>'evidence'],
            ]);
        }

        if ($r->filled('link')) {
            TicketDocument::create([
                'ticket_id'   => $ticket->id,
                'uploaded_by' => auth()->id(),
                'stage_id'    => $stage->id,
                'category'    => 'evidencia',
                'name'        => 'Evidencia (link)',
                'path'        => null,
                'version'     => 1,
                'meta'        => ['type'=>'evidence','url'=>$r->string('link')],
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /* =========================================================
     * Helpers internos
     * ========================================================= */

    /** Crea checklist + items + actualiza etapa & auditoría */
    protected function createChecklistForStage(
        Ticket $ticket,
        TicketStage $stage,
        string $title,
        ?string $instructions,
        array $items
    ): void {
        DB::transaction(function () use ($ticket, $stage, $title, $instructions, $items) {
            $chkAttrs = [
                'ticket_id' => $ticket->id,
                'stage_id'  => $stage->id,
                'title'     => $title,
            ];

            if (Schema::hasColumn('ticket_checklists','instructions')) {
                $chkAttrs['instructions'] = $instructions ?? '';
            } elseif (Schema::hasColumn('ticket_checklists','meta')) {
                $chkAttrs['meta'] = ['instructions'=>$instructions];
            }

            if (Schema::hasColumn('ticket_checklists','assigned_to')) {
                $chkAttrs['assigned_to'] = $stage->assignee_id;
            }

            $chk = TicketChecklist::create($chkAttrs);

            $pos = 1;
            foreach ($items as $text) {
                $text = trim((string) $text);
                if ($text === '') continue;

                $itemAttrs = [
                    'checklist_id' => $chk->id,
                    'label'        => $text,
                    'is_done'      => false,
                ];

                if (Schema::hasColumn('ticket_checklist_items','position')) {
                    $itemAttrs['position'] = $pos++;
                }
                if (Schema::hasColumn('ticket_checklist_items','type')) {
                    $itemAttrs['type'] = 'checkbox';
                }

                TicketChecklistItem::create($itemAttrs);
            }

            // Guardar meta IA en la etapa si hay columnas
            $upd = [];
            if (Schema::hasColumn('ticket_stages','ai_prompt') && empty($stage->ai_prompt)) {
                $upd['ai_prompt'] = $title;
            }
            if (Schema::hasColumn('ticket_stages','ai_instructions')) {
                $upd['ai_instructions'] = $instructions;
            }
            if (!empty($upd)) {
                $stage->update($upd);
            }

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'checklist_generated',
                'diff'      => [
                    'stage_id'        => $stage->id,
                    'checklist_title' => $title,
                ],
            ]);
        });
    }

    /** True si la etapa anterior está terminada (o no hay anterior) */
    protected function prevStageIsDone(Ticket $ticket, TicketStage $stage): bool
    {
        $prev = $ticket->stages()
            ->where('position','<',$stage->position)
            ->orderByDesc('position')
            ->first();

        if (!$prev) return true;
        return $prev->status === 'terminado';
    }

    /** Número de ítems pendientes del checklist de la etapa */
    protected function checklistPendingCount(TicketStage $stage): int
    {
        $ids = $stage->checklists()->pluck('id');
        if ($ids->isEmpty()) return 0;

        return TicketChecklistItem::whereIn('checklist_id',$ids)
            ->where('is_done',false)
            ->count();
    }

    /** Si la etapa requiere evidencia (solo si existe la columna) */
    protected function requiresEvidence(TicketStage $stage): bool
    {
        if (!Schema::hasColumn('ticket_stages','requires_evidence')) return false;
        return (bool) $stage->requires_evidence;
    }

    /** Fallback local: separa el prompt en bullets básicos */
    protected function fallbackItemsFromPrompt(string $prompt)
    {
        $parts = preg_split('/[\.\n\r;•\-]+/u', $prompt) ?: [];

        return collect($parts)
            ->map(fn($s) => trim(preg_replace('/\s+/', ' ', $s)))
            ->filter(fn($s) => mb_strlen($s) >= 3)
            ->take(10)
            ->values();
    }
}
