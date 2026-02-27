<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAudit;
use App\Models\TicketChecklist;
use App\Models\TicketChecklistItem;
use App\Services\Tickets\TicketChecklistAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketChecklistController extends Controller
{
    private function canWorkTicket(Ticket $ticket): bool
    {
        $uid = auth()->id();
        if (!$uid) return false;

        if (!empty($ticket->assignee_id) && (int)$ticket->assignee_id === (int)$uid) return true;
        if (Schema::hasColumn('tickets','created_by') && !empty($ticket->created_by) && (int)$ticket->created_by === (int)$uid) return true;

        return false;
    }

    private function assertItemBelongsToTicketOr404(Ticket $ticket, TicketChecklistItem $item): TicketChecklist
    {
        $cl = TicketChecklist::find($item->checklist_id);
        if (!$cl || (int)$cl->ticket_id !== (int)$ticket->id) abort(404);
        return $cl;
    }

    /**
     * ✅ PREVIEW IA (para CREATE)
     * NO existe ticket aún -> regresa JSON { title, items }
     * Ruta: POST tickets/checklist/preview-ai  name: tickets.checklist.preview
     */
    public function previewAi(Request $r, TicketChecklistAiService $ai)
    {
        $data = $r->validate([
            'title'       => ['required','string','max:180'],
            'description' => ['nullable','string','max:5000'],
            'area'        => ['required','string','max:60'],
        ]);

        try{
            $out = $ai->generateChecklist(
                (string)$data['title'],
                (string)($data['description'] ?? ''),
                (string)$data['area']
            );

            return response()->json($out);

        } catch (\Throwable $e){
            \Log::warning('Checklist IA preview falló', [
                'err' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'No se pudo generar el checklist IA.',
                'error'   => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ APLICAR PAYLOAD (para TicketController@store)
     */
    public function applyPayloadToTicket(Ticket $ticket, array $payload): ?TicketChecklist
    {
        $source = in_array(($payload['source'] ?? 'manual'), ['ai','manual'], true)
            ? (string)($payload['source'] ?? 'manual')
            : 'manual';

        $title = trim((string)($payload['title'] ?? 'Checklist'));
        if ($title === '') $title = 'Checklist';

        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];
        $items = array_values(array_filter($items, function($it){
            $t = trim((string)($it['title'] ?? ''));
            return $t !== '';
        }));

        if (count($items) === 0) return null;

        return DB::transaction(function () use ($ticket, $source, $title, $items) {

            $old = $ticket->checklists()->latest('id')->first();
            if ($old) {
                try { $old->items()->delete(); } catch (\Throwable $e) {}
                try { $old->delete(); } catch (\Throwable $e) {}
            }

            $cl = TicketChecklist::create([
                'ticket_id'  => $ticket->id,
                'title'      => $title,
                'source'     => $source,
                'created_by' => auth()->id(),
                'meta'       => null,
            ]);

            $order = 10;
            foreach ($items as $it) {
                $t = trim((string)($it['title'] ?? ''));
                if ($t === '') continue;

                TicketChecklistItem::create([
                    'checklist_id' => $cl->id,
                    'title'        => $t,
                    'detail'       => !empty($it['detail']) ? (string)$it['detail'] : null,
                    'recommended'  => array_key_exists('recommended', (array)$it) ? (bool)$it['recommended'] : true,
                    'done'         => false,
                    'sort_order'   => $order,
                    'meta'         => null,
                ]);
                $order += 10;
            }

            if (Schema::hasColumn('tickets','ai_checklist_generated_at')) {
                if ($source === 'ai') $ticket->ai_checklist_generated_at = now();
            }
            if (Schema::hasColumn('tickets','ai_checklist_opt_out')) {
                $ticket->ai_checklist_opt_out = false;
            }
            $ticket->save();

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'checklist_created_on_ticket_create',
                'diff'      => [
                    'checklist_id' => $cl->id,
                    'source'       => $source,
                    'items'        => $cl->items()->count(),
                ],
            ]);

            return $cl;
        });
    }

    /**
     * ✅ Generar IA ya con Ticket en DB
     */
    public function generateAi(Request $r, Ticket $ticket, TicketChecklistAiService $ai)
    {
        if (!$this->canWorkTicket($ticket)) abort(403, 'No tienes permiso para generar checklist.');

        $existing = $ticket->checklists()->where('source','ai')->latest('id')->first();
        if ($existing) {
            return back()->with('ok', 'Ya existe un checklist generado por IA.');
        }

        return DB::transaction(function () use ($ticket, $ai) {

            // ✅ FIX: llamar al service con strings (NO pasar $ticket completo)
            $gen = $ai->generateChecklist(
                (string)($ticket->title ?? ''),
                (string)($ticket->description ?? ''),
                (string)($ticket->area ?? '')
            );

            $cl = TicketChecklist::create([
                'ticket_id'   => $ticket->id,
                'title'       => (string)($gen['title'] ?? 'Checklist sugerido'),
                'source'      => 'ai',
                'created_by'  => auth()->id(),
                'meta'        => $gen['meta'] ?? null,
            ]);

            $order = 10;
            foreach (($gen['items'] ?? []) as $it) {
                $t = trim((string)($it['title'] ?? ''));
                if ($t === '') continue;

                TicketChecklistItem::create([
                    'checklist_id' => $cl->id,
                    'title'        => $t,
                    'detail'       => $it['detail'] ?? null,
                    'recommended'  => (bool)($it['recommended'] ?? true),
                    'done'         => false,
                    'sort_order'   => $order,
                    'meta'         => null,
                ]);
                $order += 10;
            }

            if (Schema::hasColumn('tickets','ai_checklist_generated_at')) {
                $ticket->ai_checklist_generated_at = now();
            }
            if (Schema::hasColumn('tickets','ai_checklist_opt_out')) {
                $ticket->ai_checklist_opt_out = false;
            }
            $ticket->save();

            TicketAudit::create([
                'ticket_id' => $ticket->id,
                'user_id'   => auth()->id(),
                'action'    => 'checklist_ai_generated',
                'diff'      => [
                    'checklist_id' => $cl->id,
                    'items'        => $cl->items()->count(),
                ],
            ]);

            return back()->with('ok', 'Checklist generado con IA.');
        });
    }

    public function optOut(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) abort(403, 'No tienes permiso.');

        if (Schema::hasColumn('tickets','ai_checklist_opt_out')) {
            $ticket->ai_checklist_opt_out = true;
            $ticket->save();
        }

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'checklist_ai_opt_out',
            'diff'      => ['opt_out' => true],
        ]);

        return back()->with('ok', 'Listo. Ya no se volverá a preguntar por checklist IA.');
    }

    public function addItem(Request $r, Ticket $ticket)
    {
        if (!$this->canWorkTicket($ticket)) abort(403, 'No tienes permiso.');

        $data = $r->validate([
            'checklist_id' => ['nullable','integer','exists:ticket_checklists,id'],
            'title'        => ['required','string','max:180'],
            'detail'       => ['nullable','string','max:2000'],
            'recommended'  => ['nullable','boolean'],
        ]);

        $cl = null;
        if (!empty($data['checklist_id'])) {
            $cl = TicketChecklist::where('ticket_id',$ticket->id)->where('id',(int)$data['checklist_id'])->first();
        }
        if (!$cl) $cl = $ticket->checklists()->latest('id')->first();
        if (!$cl) {
            $cl = TicketChecklist::create([
                'ticket_id'  => $ticket->id,
                'title'      => 'Checklist',
                'source'     => 'manual',
                'created_by' => auth()->id(),
            ]);
        }

        $max  = (int) TicketChecklistItem::where('checklist_id',$cl->id)->max('sort_order');
        $sort = $max > 0 ? $max + 10 : 10;

        $item = TicketChecklistItem::create([
            'checklist_id' => $cl->id,
            'title'        => $data['title'],
            'detail'       => $data['detail'] ?? null,
            'recommended'  => (bool)($data['recommended'] ?? true),
            'done'         => false,
            'sort_order'   => $sort,
        ]);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'checklist_item_added',
            'diff'      => [
                'item_id' => $item->id,
                'title'   => $item->title,
            ],
        ]);

        return back()->with('ok', 'Item agregado al checklist.');
    }

    public function updateItem(Request $r, Ticket $ticket, TicketChecklistItem $item)
    {
        if (!$this->canWorkTicket($ticket)) abort(403, 'No tienes permiso.');

        $this->assertItemBelongsToTicketOr404($ticket, $item);

        $data = $r->validate([
            'title'       => ['nullable','string','max:180'],
            'detail'      => ['nullable','string','max:2000'],
            'recommended' => ['nullable','boolean'],
        ]);

        $before = $item->toArray();

        foreach (['title','detail','recommended'] as $k) {
            if (array_key_exists($k,$data) && !is_null($data[$k])) $item->{$k} = $data[$k];
        }
        $item->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'checklist_item_updated',
            'diff'      => [
                'item_id' => $item->id,
                'before'  => [
                    'title'       => $before['title'] ?? null,
                    'detail'      => $before['detail'] ?? null,
                    'recommended' => $before['recommended'] ?? null
                ],
                'after'   => [
                    'title'       => $item->title,
                    'detail'      => $item->detail,
                    'recommended' => $item->recommended
                ],
            ],
        ]);

        return back()->with('ok', 'Item actualizado.');
    }

    public function deleteItem(Request $r, Ticket $ticket, TicketChecklistItem $item)
    {
        if (!$this->canWorkTicket($ticket)) abort(403, 'No tienes permiso.');

        $this->assertItemBelongsToTicketOr404($ticket, $item);

        $id    = $item->id;
        $title = $item->title;

        $item->delete();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'checklist_item_deleted',
            'diff'      => ['item_id'=>$id,'title'=>$title],
        ]);

        return back()->with('ok', 'Item eliminado.');
    }

    public function toggle(Request $r, Ticket $ticket, TicketChecklistItem $item)
    {
        if (!$this->canWorkTicket($ticket)) {
            return response()->json(['message' => 'No tienes permiso.'], 403);
        }

        $this->assertItemBelongsToTicketOr404($ticket, $item);

        $data = $r->validate([
            'done'          => ['required','boolean'],
            'evidence_note' => ['nullable','string','max:3000'],
        ]);

        $beforeDone = (bool)$item->done;

        $item->done = (bool)$data['done'];
        if ($item->done) {
            $item->done_at = now();
            $item->done_by = auth()->id();
        } else {
            $item->done_at = null;
            $item->done_by = null;
        }

        if (array_key_exists('evidence_note', $data)) {
            $item->evidence_note = $data['evidence_note'] ?? null;
        }

        $item->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'checklist_item_toggled',
            'diff'      => [
                'item_id'       => $item->id,
                'from'          => $beforeDone,
                'to'            => (bool)$item->done,
                'evidence_note' => (string)($item->evidence_note ?? ''),
            ],
        ]);

        return response()->json([
            'ok'   => true,
            'done' => (bool)$item->done,
        ]);
    }

    public function toggleDone(Request $r, Ticket $ticket, TicketChecklistItem $item)
    {
        if (!$this->canWorkTicket($ticket)) abort(403, 'No tienes permiso.');

        $this->assertItemBelongsToTicketOr404($ticket, $item);

        $data = $r->validate([
            'done'          => ['required','boolean'],
            'evidence_note' => ['nullable','string','max:3000'],
        ]);

        $beforeDone = (bool)$item->done;

        $item->done = (bool)$data['done'];
        if ($item->done) {
            $item->done_at = now();
            $item->done_by = auth()->id();
        } else {
            $item->done_at = null;
            $item->done_by = null;
        }

        if (array_key_exists('evidence_note',$data)) {
            $item->evidence_note = $data['evidence_note'] ?? null;
        }

        $item->save();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'checklist_item_toggled',
            'diff'      => [
                'item_id'       => $item->id,
                'from'          => $beforeDone,
                'to'            => (bool)$item->done,
                'evidence_note' => (string)($item->evidence_note ?? ''),
            ],
        ]);

        if ($r->expectsJson()) {
            return response()->json(['ok' => true, 'done' => (bool)$item->done]);
        }

        return back()->with('ok', 'Checklist actualizado.');
    }
}