<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

use App\Models\{
    Ticket,
    TicketStage,
    TicketChecklist,
    TicketChecklistItem
};

// Opcionales
use PDF;                       // barryvdh/laravel-dompdf
use PhpOffice\PhpWord\PhpWord; // phpoffice/phpword

use App\Services\AiDynamicChecklistService;

class TicketChecklistController extends Controller
{
    /** Crear checklist manual (opcionalmente asociada a una etapa) */
    public function store(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'title'     => ['required','string','max:160'],
            'stage_id'  => ['nullable','integer','exists:ticket_stages,id'],
            'items'     => ['sometimes','array','min:0'], // strings u objetos {label|text|title|name}
        ]);

        $attrs = [
            'ticket_id' => $ticket->id,
            'title'     => $data['title'],
            'meta'      => ['source' => 'manual'],
        ];

        if (!empty($data['stage_id'])) {
            $stage = TicketStage::findOrFail($data['stage_id']);
            abort_unless($stage->ticket_id === $ticket->id, 404);
            $attrs['stage_id'] = $stage->id;
        }

        $check = TicketChecklist::create($attrs);

        if (!empty($data['items'])) {
            $pos = 1; $bulk = [];
            foreach ($data['items'] as $raw) {
                $label = is_string($raw) ? $raw : (
                    $raw['label'] ?? $raw['text'] ?? $raw['title'] ?? $raw['name'] ?? null
                );
                $label = trim((string) $label) ?: "Tarea {$pos}";
                $type  = is_array($raw) ? ($raw['type'] ?? 'checkbox') : 'checkbox';

                $bulk[] = [
                    'checklist_id' => $check->id,
                    'label'        => $label,
                    'type'         => in_array($type, ['text','checkbox','date','file'], true) ? $type : 'checkbox',
                    'position'     => $pos++,
                    'is_done'      => false,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
            TicketChecklistItem::insert($bulk);
        }

        return $r->wantsJson()
            ? response()->json(['ok'=>true,'checklist_id'=>$check->id])
            : back()->with('ok','Checklist creada');
    }

    public function update(Request $r, TicketChecklist $checklist)
    {
        $data = $r->validate([
            'title'       => ['nullable','string','max:160'],
            'require_all' => ['nullable','boolean'],
        ]);

        if (array_key_exists('title', $data) && $data['title'] !== null) {
            $t = trim($data['title']); if ($t !== '') $checklist->title = $t;
        }
        if (array_key_exists('require_all', $data)) {
            $meta = (array) $checklist->meta;
            $meta['require_all'] = (bool) $data['require_all'];
            $checklist->meta = $meta;
        }
        $checklist->save();

        return $r->wantsJson() ? response()->json(['ok'=>true])
                               : back()->with('ok','Checklist actualizada');
    }

    public function destroy(Request $r, TicketChecklist $checklist)
    {
        $id = $checklist->id;
        $checklist->items()->delete();
        $checklist->delete();

        return $r->wantsJson() ? response()->json(['ok'=>true,'deleted_id'=>$id])
                               : back()->with('ok','Checklist eliminada');
    }

    public function addItem(Request $r, TicketChecklist $checklist)
    {
        $data = $r->validate([
            'label'    => ['required','string','max:200'],
            'type'     => ['nullable','in:text,checkbox,date,file'],
            'position' => ['nullable','integer','min:1']
        ]);

        $label = trim($data['label']) ?: 'Tarea';
        $type  = $data['type'] ?? 'checkbox';
        $pos   = $data['position'] ?? (($checklist->items()->max('position') ?? 0) + 1);

        $item = $checklist->items()->create([
            'label'    => $label,
            'type'     => $type,
            'position' => $pos,
            'is_done'  => false,
        ]);

        return $r->wantsJson() ? response()->json(['ok'=>true,'item'=>$item])
                               : back()->with('ok','Item agregado');
    }

    public function updateItem(Request $r, TicketChecklistItem $item)
    {
        $data = $r->validate([
            'value'    => ['nullable'],
            'is_done'  => ['nullable','boolean'],
            'label'    => ['nullable','string','max:200'],
            'position' => ['nullable','integer','min:1'],
            'type'     => ['nullable','in:text,checkbox,date,file'],
            'done_at'  => ['nullable','date'],
            'done_by'  => ['nullable','integer'],
        ]);

        if (array_key_exists('label', $data) && $data['label'] !== null) {
            $t = trim($data['label']); if ($t !== '') $item->label = $t;
        }
        if (array_key_exists('value', $data))     $item->value = $data['value'];
        if (array_key_exists('is_done', $data))   $item->is_done = (bool)$data['is_done'];
        if (array_key_exists('position', $data))  $item->position = (int)$data['position'];
        if (array_key_exists('type', $data))      $item->type = $data['type'] ?? $item->type;
        if (array_key_exists('done_at', $data))   $item->done_at = $data['done_at'] ? Carbon::parse($data['done_at']) : null;
        if (array_key_exists('done_by', $data))   $item->done_by = $data['done_by'];

        $item->save();

        return $r->wantsJson() ? response()->json(['ok'=>true,'item'=>$item])
                               : back()->with('ok','Item actualizado');
    }

    public function destroyItem(Request $r, TicketChecklistItem $item)
    {
        $id = $item->id;
        $item->delete();

        return $r->wantsJson() ? response()->json(['ok'=>true,'deleted_id'=>$id])
                               : back()->with('ok','Item eliminado');
    }

    public function reorderItems(Request $r, TicketChecklist $checklist)
    {
        $data = $r->validate([
            'order'   => ['required','array','min:1'], // {"123":1,"124":2}
            'order.*' => ['required','integer','min:1'],
        ]);

        $validIds = $checklist->items()->pluck('id')->all();

        DB::transaction(function () use ($data, $validIds) {
            foreach ($data['order'] as $itemId => $pos) {
                $id = (int) $itemId;
                if (in_array($id, $validIds, true)) {
                    TicketChecklistItem::where('id', $id)->update(['position' => (int)$pos]);
                }
            }
        });

        return response()->json(['ok'=>true]);
    }

    public function toggleAll(Request $r, TicketChecklist $checklist)
    {
        $data = $r->validate(['done'=>['required','boolean']]);
        $checklist->items()->update(['is_done' => (bool)$data['done']]);

        return $r->wantsJson() ? response()->json(['ok'=>true])
                               : back()->with('ok', $data['done'] ? 'Todos marcados' : 'Todos desmarcados');
    }

    public function exportPdf(TicketChecklist $checklist)
    {
        $checklist->load(['items','ticket']);
        if (!class_exists(\Barryvdh\DomPDF\ServiceProvider::class) && !function_exists('PDF')) {
            abort(501, 'PDF no disponible. Instala barryvdh/laravel-dompdf.');
        }
        $pdf = PDF::loadView('tickets.exports.checklist_pdf', compact('checklist'));
        return $pdf->download("Checklist-{$checklist->id}.pdf");
    }

    public function exportWord(TicketChecklist $checklist)
    {
        $checklist->load(['items','ticket']);
        if (!class_exists(PhpWord::class)) {
            abort(501, 'Word no disponible. Instala phpoffice/phpword.');
        }

        $phpWord  = new PhpWord();
        $section  = $phpWord->addSection();
        $section->addTitle("Checklist: {$checklist->title}", 1);
        $section->addText("Ticket: {$checklist->ticket->folio} - {$checklist->ticket->title}");

        foreach ($checklist->items()->orderBy('position')->get() as $it) {
            $prefix = $it->is_done ? '✅ ' : '☐ ';
            $val    = is_array($it->value) ? json_encode($it->value, JSON_UNESCAPED_UNICODE) : (string)$it->value;
            $section->addText($prefix . $it->label . ($val ? " — {$val}" : ''));
        }

        $file = storage_path("app/tmp/Checklist-{$checklist->id}.docx");
        @mkdir(dirname($file), 0775, true);
        $phpWord->save($file);

        return Response::download($file)->deleteFileAfterSend(true);
    }

    /** ===== IA: Generar (SIN fallback) ===== */
    public function suggestFromPrompt(Request $r, Ticket $ticket, TicketStage $stage, AiDynamicChecklistService $ai)
    {
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $data = $r->validate(['prompt' => ['required','string','max:1500']]);

        // Llama al servicio (debe devolver JSON con title, instructions, items[])
        $resp = $ai->checklistFor($data['prompt'], [
            'ticket'=>[
                'folio'    => $ticket->folio,
                'type'     => $ticket->type,
                'priority' => $ticket->priority,
                'client'   => $ticket->client_name,
            ],
            'stage'=>[
                'name' => $stage->name,
                'pos'  => $stage->position,
            ],
        ]);

        $title = (string) ($resp['title'] ?? '');
        $instructions = (string) ($resp['instructions'] ?? '');
        $items = collect($resp['items'] ?? [])
            ->map(fn($it) => trim((string)($it['text'] ?? '')))
            ->filter()
            ->values()
            ->all();

        if ($title === '' || count($items) < 8 || count($items) > 12) {
            throw ValidationException::withMessages([
                'prompt' => 'La IA debe devolver título y entre 8–12 acciones medibles. Ajusta el prompt o revisa tu API key/modelo.',
            ]);
        }

        return response()->json([
            'ok'           => true,
            'title'        => $title,
            'instructions' => $instructions,
            'items'        => $items,
        ]);
    }

    /** Crear checklist a partir del resultado de IA */
    public function createFromAi(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'stage_id' => ['required','integer','exists:ticket_stages,id'],
            'title'    => ['nullable','string','max:160'],
            'items'    => ['required','array','min:8','max:12'],
            'items.*'  => ['required','string','max:500'],
        ]);

        $stage = TicketStage::findOrFail($data['stage_id']);
        abort_unless($stage->ticket_id === $ticket->id, 404);

        $check = TicketChecklist::create([
            'ticket_id' => $ticket->id,
            'stage_id'  => $stage->id,
            'title'     => $data['title'] ?: 'Checklist IA',
            'meta'      => ['source'=>'ai'],
        ]);

        $pos = 1; $bulk = [];
        foreach ($data['items'] as $text) {
            $bulk[] = [
                'checklist_id' => $check->id,
                'label'        => trim($text),
                'type'         => 'checkbox',
                'position'     => $pos++,
                'is_done'      => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }
        TicketChecklistItem::insert($bulk);

        return response()->json(['ok'=>true,'checklist_id'=>$check->id]);
    }
}
