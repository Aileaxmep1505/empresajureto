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

// Servicio de IA (si lo tienes)
use App\Services\AiDynamicChecklistService;

class TicketStageController extends Controller
{
    /** Crear etapa manual (con assignee, SLA y prompt IA opcional) */
    public function store(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'name'              => ['required','string','max:180'],
            'assignee_id'       => ['nullable','integer'],
            'due_at'            => ['nullable','date'],
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
            'diff'      => ['stage_id'=>$stage->id],
        ]);

        return back()->with('ok','Etapa creada');
    }

    /** Generar checklist con IA (o fallback local) para una etapa */
    public function generateChecklist(Request $r, Ticket $ticket, TicketStage $stage, AiDynamicChecklistService $ai = null)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $data = $r->validate([
            'prompt' => ['required','string','max:2000'],
        ]);

        // 1) Intentar servicio real (si se inyecta o existe)
        $title = 'Checklist generado';
        $instructions = 'Sigue los puntos y adjunta evidencia si aplica.';
        $items = collect();

        try {
            if (!$ai && class_exists(\App\Services\AiDynamicChecklistService::class)) {
                $ai = app(\App\Services\AiDynamicChecklistService::class);
            }
            if ($ai) {
                $out = $ai->suggestChecklist([
                    'contexto' => [
                        'ticket_folio' => $ticket->folio,
                        'etapa'        => $stage->name,
                        'tipo'         => $ticket->type,
                        'prioridad'    => $ticket->priority,
                    ],
                    'tarea' => $data['prompt'],
                ]);
                $title        = $out['title'] ?? $title;
                $instructions = $out['instructions'] ?? $instructions;
                $items        = collect($out['items'] ?? [])->filter()->values();
            }
        } catch (\Throwable $e) {
            // sigue al fallback si falla el servicio
        }

        // 2) Fallback simple si no hubo ítems
        if ($items->isEmpty()) {
            $items = $this->fallbackItemsFromPrompt($data['prompt']);
            if (mb_strlen(trim($data['prompt'])) <= 120) {
                $title = trim($data['prompt']) ?: $title;
            }
        }

        DB::transaction(function () use ($ticket, $stage, $title, $instructions, $items) {
            // Construir atributos del checklist, respetando columnas existentes
            $chkAttrs = [
                'ticket_id' => $ticket->id,
                'stage_id'  => $stage->id,
                'title'     => $title,
            ];
            if (Schema::hasColumn('ticket_checklists','instructions')) {
                $chkAttrs['instructions'] = $instructions;
            } else {
                // Si no existe columna instructions, guarda en meta si existe
                if (Schema::hasColumn('ticket_checklists','meta')) {
                    $chkAttrs['meta'] = ['instructions'=>$instructions];
                }
            }
            if (Schema::hasColumn('ticket_checklists','assigned_to')) {
                $chkAttrs['assigned_to'] = $stage->assignee_id;
            }

            $chk = TicketChecklist::create($chkAttrs);

            $pos = 1;
            foreach ($items as $text) {
                $it = [
                    'checklist_id' => $chk->id,
                    'label'        => (string) $text,
                    'is_done'      => false,
                ];
                // campos opcionales
                if (Schema::hasColumn('ticket_checklist_items','position')) {
                    $it['position'] = $pos++;
                }
                if (Schema::hasColumn('ticket_checklist_items','type')) {
                    $it['type'] = 'checkbox';
                }
                TicketChecklistItem::create($it);
            }

            // Guardar info IA en la etapa si hay columnas
            $upd = [];
            if (Schema::hasColumn('ticket_stages', 'ai_prompt') && empty($stage->ai_prompt)) {
                $upd['ai_prompt'] = $title;
            }
            if (Schema::hasColumn('ticket_stages', 'ai_instructions')) {
                $upd['ai_instructions'] = $instructions;
            }
            if (!empty($upd)) {
                $stage->update($upd);
            }

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'checklist_generated',
                'diff'      => ['stage_id'=>$stage->id, 'checklist_title'=>$title],
            ]);
        });

        return response()->json(['ok'=>true]);
    }

    /** Marcar item del checklist (AJAX) */
    public function toggleItem(Request $r, Ticket $ticket, TicketChecklistItem $item)
    {
        $r->validate(['done' => ['required','boolean']]);

        $chk = $item->checklist;
        $stage = $chk->stage;

        abort_unless($chk->ticket_id === $ticket->id, 404);

        // Si hay responsable, solo él marca; puedes adaptar a permisos/roles
        if ($stage->assignee_id && $stage->assignee_id !== auth()->id()) {
            return response()->json(['ok'=>false,'msg'=>'No eres el responsable de esta etapa.'], 403);
        }

        $isDone = (bool) $r->boolean('done');
        $item->is_done = $isDone;

        // Guardar marcas de auditoría si existen columnas
        if (Schema::hasColumn('ticket_checklist_items','done_at')) {
            $item->done_at = $isDone ? now() : null;
        }
        if (Schema::hasColumn('ticket_checklist_items','done_by')) {
            $item->done_by = $isDone ? auth()->id() : null;
        }

        $item->save();

        return response()->json(['ok'=>true,'item_id'=>$item->id,'is_done'=>$item->is_done]);
    }

    /** Iniciar etapa (bloqueo secuencial) */
    public function start(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        if (!$this->prevStageIsDone($ticket, $stage)) {
            return response()->json(['ok'=>false,'msg'=>'Debes completar la etapa anterior.'], 422);
        }

        if ($stage->status === 'pendiente') {
            $upd = ['status' => 'en_progreso'];
            if (Schema::hasColumn('ticket_stages','started_at')) {
                $upd['started_at'] = now();
            }
            $stage->update($upd);

            TicketAudit::create([
                'ticket_id'=>$ticket->id,'user_id'=>auth()->id(),
                'action'=>'stage_started','diff'=>['stage_id'=>$stage->id]
            ]);
        }

        return response()->json([
            'ok'=>true,
            'stage_id'=>$stage->id,
            'status'=>$stage->status,
            'started_at'=>Schema::hasColumn('ticket_stages','started_at') ? $stage->started_at : null
        ]);
    }

    /** Completar etapa (valida checklist y evidencia requerida) */
    public function complete(Request $r, Ticket $ticket, TicketStage $stage)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        if ($this->checklistPendingCount($stage) > 0) {
            return response()->json(['ok'=>false,'msg'=>'Faltan items del checklist.'], 422);
        }

        if ($this->requiresEvidence($stage) && !$stage->documents()->exists()) {
            return response()->json(['ok'=>false,'msg'=>'Debes subir evidencia para cerrar la etapa.'], 422);
        }

        $upd = ['status'=>'terminado'];
        if (Schema::hasColumn('ticket_stages','finished_at')) {
            $upd['finished_at'] = now();
        }
        $stage->update($upd);

        $ticket->refreshProgress();

        TicketAudit::create([
            'ticket_id'=>$ticket->id,'user_id'=>auth()->id(),
            'action'=>'stage_completed','diff'=>['stage_id'=>$stage->id]
        ]);

        return response()->json(['ok'=>true,'progress'=>$ticket->progress]);
    }

    /** Subir evidencia (archivo o link) a la etapa */
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

        return response()->json(['ok'=>true]);
    }

    /* ========================= Helpers ========================= */

    /** True si la etapa anterior está terminada (o no hay anterior) */
    protected function prevStageIsDone(Ticket $ticket, TicketStage $stage): bool
    {
        $prev = $ticket->stages()->where('position','<',$stage->position)
            ->orderByDesc('position')->first();
        if (!$prev) return true;
        return $prev->status === 'terminado';
    }

    /** Número de ítems pendientes del checklist de la etapa */
    protected function checklistPendingCount(TicketStage $stage): int
    {
        $ids = $stage->checklists()->pluck('id');
        if ($ids->isEmpty()) return 0;
        return TicketChecklistItem::whereIn('checklist_id',$ids)->where('is_done',false)->count();
    }

    /** Si la etapa requiere evidencia (solo si existe la columna) */
    protected function requiresEvidence(TicketStage $stage): bool
    {
        if (!Schema::hasColumn('ticket_stages','requires_evidence')) return false;
        return (bool) $stage->requires_evidence;
    }

    /** Fallback local: convierte prompt en bullets básicos */
    protected function fallbackItemsFromPrompt(string $prompt)
    {
        $parts = preg_split('/[\.\n\r;•\-]+/u', $prompt) ?: [];
        return collect($parts)
            ->map(fn($s)=>trim(preg_replace('/\s+/', ' ', $s)))
            ->filter(fn($s)=>mb_strlen($s) >= 3)
            ->take(10)
            ->values();
    }
}
