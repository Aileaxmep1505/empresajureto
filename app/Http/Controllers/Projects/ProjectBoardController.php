<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectChatMessage;
use App\Models\ProjectChecklistAttachment;
use App\Models\ProjectChecklistItem;
use App\Models\ProjectChecklistNote;
use App\Services\PythonProjectProcessor;
use App\Services\OpenAiStructurerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProjectBoardController extends Controller
{
    /* ============================================================
     |  Columnas Kanban por defecto
     * ============================================================ */
    private function defaultColumns(): array
    {
        return [
            ['id' => 'backlog',     'name' => 'Backlog',       'color' => 'gray'],
            ['id' => 'en_analisis', 'name' => 'En Análisis',   'color' => 'blue'],
            ['id' => 'propuesta',   'name' => 'En Propuesta',  'color' => 'orange'],
            ['id' => 'enviadas',    'name' => 'Enviadas',      'color' => 'purple'],
            ['id' => 'ganadas',     'name' => 'Ganadas',       'color' => 'green'],
            ['id' => 'perdidas',    'name' => 'Perdidas',      'color' => 'red'],
        ];
    }

    /* ============================================================
     |  INDEX  (vista Kanban + listado)
     * ============================================================ */
    public function index(Request $request)
    {
        $projects = Project::where('user_id', Auth::id())
            ->latest()
            ->get();

        $validIds = array_column($this->defaultColumns(), 'id');

        $columns = collect($this->defaultColumns())->map(function ($c) use ($projects) {
            $items = $projects->filter(function ($p) use ($c) {
                $col = $p->column_id ?: 'backlog';
                return $col === $c['id'];
            })->values();

            $c['count']    = $items->count();
            $c['projects'] = $items;
            return $c;
        })->all();

        $huerfanos = $projects->reject(fn ($p) => in_array($p->column_id ?: 'backlog', $validIds))->values();
        if ($huerfanos->count()) {
            $columns = array_map(function ($c) use ($huerfanos) {
                if ($c['id'] === 'backlog') {
                    $c['projects'] = $c['projects']->concat($huerfanos)->values();
                    $c['count']    = $c['projects']->count();
                }
                return $c;
            }, $columns);
        }

        $openColumns = session('projects.open_columns', ['backlog', 'en_analisis', 'propuesta']);
        $viewMode    = session('projects.view_mode', 'board');

        return view('projects.index', compact('projects', 'columns', 'openColumns', 'viewMode'));
    }

    /* ============================================================
     |  STORE  (crea proyecto + sube docs + dispara extracción)
     * ============================================================ */
    public function store(Request $request, PythonProjectProcessor $processor)
    {
        $withoutDocuments = $request->boolean('without_documents');

        $rules = [
            'name'       => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'color'      => 'nullable|string|max:30',
            'favorite'   => 'nullable',
            'column_id'  => 'nullable|integer',
        ];

        if (!$withoutDocuments) {
            $rules['files'] = 'required|array|min:1|max:9';
            $rules['files.*'] = 'file|mimes:pdf,docx,doc|max:25600';
        } else {
            $rules['files'] = 'nullable|array|max:9';
            $rules['files.*'] = 'file|mimes:pdf,docx,doc|max:25600';
        }

        $request->validate($rules);

        $project = Project::create([
            'name'       => $request->name,
            'slug'       => Str::slug($request->name) . '-' . Str::random(6),
            'user_id'    => Auth::id(),
            'status'     => $withoutDocuments ? 'ready' : 'processing',
            'column_id'  => (int) ($request->column_id ?: 1),
            'priority'   => $request->priority ?? 'media',
            'color'      => $request->color ?? '#1e3a5f',
            'start_date' => $request->start_date,
            'favorite'   => $request->boolean('favorite'),
        ]);

        $paths = [];

        foreach ($request->file('files', []) as $file) {
            $stored = $file->store("projects/{$project->id}/source", 'public');

            ProjectDocument::create([
                'project_id' => $project->id,
                'filename'   => $file->getClientOriginalName(),
                'file_path'  => $stored,
                'mime_type'  => $file->getMimeType(),
                'file_size'  => $file->getSize(),
                'status'     => 'pendiente',
            ]);

            $paths[] = storage_path('app/public/' . $stored);
        }

        if (!$withoutDocuments && !empty($paths)) {
            try {
                $result = $processor->process($project, $paths);

                $project->structured_data = $result['structured_data'] ?? null;
                $project->checklist       = data_get($result, 'structured_data.checklist_sugerido', []); // legacy / respaldo
                $project->status          = 'ready';
                $project->save();

                $this->syncChecklistItemsFromArray(
                    $project,
                    data_get($result, 'structured_data.checklist_sugerido', []),
                    true
                );

                ProjectDocument::where('project_id', $project->id)
                    ->update([
                        'status'       => 'procesado',
                        'processed_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                Log::error('Project processing failed', [
                    'project' => $project->id,
                    'error'   => $e->getMessage(),
                ]);

                $project->status        = 'error';
                $project->error_message = $e->getMessage();
                $project->save();
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok'           => true,
                'redirect'     => route('projects.show', $project),
                'redirect_url' => route('projects.show', $project),
                'project'      => $project,
            ]);
        }

        return redirect()->route('projects.show', $project);
    }

    /* ============================================================
     |  SHOW   →  DASHBOARD (vista principal del proyecto)
     * ============================================================ */
    public function show(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $project->load(['documents', 'user']);

        return view('projects.dashboard', compact('project'));
    }

    /* ============================================================
     |  ANALISIS   →  Vista con CHAT + TABS
     * ============================================================ */
    public function analisis(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $project->load(['documents', 'chatMessages']);
        $this->ensureChecklistItemsExist($project);
        $project->load([
            'checklistItems.responsible',
            'checklistItems.reviewer',
            'checklistItems.notes.user',
            'checklistItems.attachments',
            'checklistItems.sourceDocument',
        ]);

        return view('projects.analisis', compact('project'));
    }

    /* ============================================================
     |  CHAT
     |   - Prosa profesional por defecto
     |   - Tablas SOLO cuando el usuario pide comparar/listar datos
     |   - Sin emojis ni caracteres decorativos
     * ============================================================ */
    public function chat(Request $request, Project $project, OpenAiStructurerService $ai)
    {
        $request->validate(['message' => 'required|string|max:4000']);

        ProjectChatMessage::create([
            'project_id' => $project->id,
            'user_id'    => Auth::id(),
            'role'       => 'user',
            'content'    => $request->message,
        ]);

        $history = ProjectChatMessage::where('project_id', $project->id)
            ->orderBy('id', 'desc')->take(12)->get()->reverse()->values();

        $systemContext = "Eres un asistente experto en licitaciones públicas mexicanas. "
            . "Estás analizando el proyecto: \"{$project->name}\".\n\n"
            . "Datos estructurados del proyecto (JSON):\n"
            . json_encode($project->structured_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n\n"
            . "FORMATO DE RESPUESTA (obligatorio):\n"
            . "1. Responde de forma profesional y clara, como un consultor. Usa prosa redactada en parrafos cortos.\n"
            . "2. Por defecto NO uses tablas. Para explicar procedimientos, consecuencias, recomendaciones o respuestas a preguntas, usa parrafos y, si ayuda, listas con vinetas '- ' o listas numeradas '1.'.\n"
            . "3. Usa una TABLA en formato Markdown SOLO cuando el usuario pida explicitamente comparar o listar varios elementos por las mismas columnas (por ejemplo: comparar partidas, listar fechas con sus datos, o un cuadro de varios requisitos). Si la respuesta es una explicacion, NUNCA la pongas en tabla.\n"
            . "4. Puedes resaltar conceptos clave con **negritas** y usar subtitulos con '## '.\n"
            . "5. NO uses emojis ni iconos. NO uses caracteres decorativos. Manten un tono formal y limpio.\n"
            . "6. Se conciso y directo, sin relleno. Cita datos concretos del documento cuando existan.";

        $messages = [['role' => 'system', 'content' => $systemContext]];
        foreach ($history as $m) {
            $messages[] = ['role' => $m->role, 'content' => $m->content];
        }

        try {
            $reply = $ai->chatRaw($messages);
        } catch (\Throwable $e) {
            Log::error('Chat error', ['err' => $e->getMessage()]);
            return response()->json([
                'ok'      => false,
                'message' => 'Lo siento, hubo un error al procesar tu pregunta: ' . $e->getMessage(),
            ], 500);
        }

        $assistant = ProjectChatMessage::create([
            'project_id' => $project->id,
            'user_id'    => Auth::id(),
            'role'       => 'assistant',
            'content'    => $reply,
        ]);

        return response()->json([
            'ok' => true,
            'assistant_message' => [
                'content' => $assistant->content,
                'time'    => $assistant->created_at->format('H:i'),
            ],
        ]);
    }

    public function resetChat(Project $project)
    {
        ProjectChatMessage::where('project_id', $project->id)->delete();
        return response()->json(['ok' => true]);
    }

    /* ============================================================
     |  BORRADOR
     * ============================================================ */
    public function saveDraft(Request $request, Project $project)
    {
        $content = $request->input('draft_content', $request->input('draft'));
        $request->merge(['draft_content' => $content]);
        $request->validate(['draft_content' => 'nullable|string']);

        $project->draft_content = $content;
        $project->save();
        return response()->json(['ok' => true]);
    }

    /* ============================================================
     |  CHECKLIST RELACIONAL
     |  - Guarda en project_checklist_items
     |  - Notas en project_checklist_notes
     |  - Evidencias en project_checklist_attachments
     * ============================================================ */
    public function updateChecklist(Request $request, Project $project, PythonProjectProcessor $processor)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        if ($request->boolean('regenerate')) {
            return $this->reanalyzeChecklist($project, $processor);
        }

        $action = $request->input('action');

        if ($action === 'create') {
            $data = $this->validateChecklistItemRequest($request);
            $position = ((int) $project->checklistItems()->max('position')) + 1;

            $item = new ProjectChecklistItem();
            $item->project_id = $project->id;
            $item->position = $position;
            $this->fillChecklistItem($item, $data);
            $item->save();

            $this->storeInlineNotes($item, $data['notas'] ?? null);

            $item->load(['responsible', 'reviewer', 'notes.user', 'attachments', 'sourceDocument']);

            return response()->json([
                'ok'      => true,
                'item'    => $this->itemToChecklistArray($item),
                'payload' => $this->checklistPayload($project),
            ]);
        }

        if ($action === 'update') {
            $item = $this->findChecklistItemFromRequest($request, $project);
            $data = $this->validateChecklistItemRequest($request);

            $this->fillChecklistItem($item, $data);
            $item->save();


            $item->load(['responsible', 'reviewer', 'notes.user', 'attachments', 'sourceDocument']);

            return response()->json([
                'ok'      => true,
                'item'    => $this->itemToChecklistArray($item),
                'payload' => $this->checklistPayload($project),
            ]);
        }

        if ($action === 'duplicate') {
            $item = $this->findChecklistItemFromRequest($request, $project);
            $item->load(['notes']);

            $copy = $item->replicate();
            $copy->requirement = trim(($item->requirement ?: 'Requisito') . ' copia');
            $copy->compliance_status = ProjectChecklistItem::COMPLIANCE_SIN_REVISAR;
            $copy->review_status = ProjectChecklistItem::STATUS_PENDIENTE;
            $copy->position = ((int) $project->checklistItems()->max('position')) + 1;
            $copy->save();

            foreach ($item->notes as $note) {
                $copy->notes()->create([
                    'user_id' => $note->user_id,
                    'body'    => $note->body,
                ]);
            }

            $copy->load(['responsible', 'reviewer', 'notes.user', 'attachments', 'sourceDocument']);

            return response()->json([
                'ok'      => true,
                'item'    => $this->itemToChecklistArray($copy),
                'payload' => $this->checklistPayload($project),
            ]);
        }

        if ($action === 'delete') {
            $item = $this->findChecklistItemFromRequest($request, $project);
            $item->load('attachments');

            foreach ($item->attachments as $attachment) {
                if ($attachment->file_path) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
            }

            $item->delete();

            return response()->json([
                'ok'      => true,
                'payload' => $this->checklistPayload($project),
            ]);
        }

        if ($action === 'note') {
            $item = $this->findChecklistItemFromRequest($request, $project);

            $request->validate([
                'body' => ['required', 'string', 'max:5000'],
            ]);

            $note = $item->notes()->create([
                'user_id' => Auth::id(),
                'body'    => $request->input('body'),
            ]);

            $note->load('user');
            $item->load(['responsible', 'reviewer', 'notes.user', 'attachments', 'sourceDocument']);

            return response()->json([
                'ok'      => true,
                'note'    => [
                    'id'         => $note->id,
                    'body'       => $note->body,
                    'user_id'    => $note->user_id,
                    'user_name'  => $note->user?->name,
                    'created_at' => optional($note->created_at)->format('Y-m-d H:i:s'),
                ],
                'item'    => $this->itemToChecklistArray($item),
                'payload' => $this->checklistPayload($project),
            ]);
        }

        if ($request->filled('items')) {
            $updates = json_decode($request->input('items'), true) ?: [];

            foreach ($updates as $update) {
                $id = $update['id'] ?? $update['item_id'] ?? $update['idx'] ?? null;
                if (!$id) continue;

                $item = $project->checklistItems()->find($id);
                if (!$item) continue;

                if (array_key_exists('cumplimiento', $update)) {
                    $item->compliance_status = $this->normalizeCompliance($update['cumplimiento']);
                }
                if (array_key_exists('status', $update)) {
                    $item->review_status = $this->normalizeReviewStatus($update['status']);
                }
                if (array_key_exists('prioridad', $update)) {
                    $item->priority = $this->normalizePriority($update['prioridad']);
                }
                if (array_key_exists('fecha_limite', $update)) {
                    $item->due_date = $update['fecha_limite'] ?: null;
                }
                if (array_key_exists('responsable_id', $update)) {
                    $item->responsible_user_id = $update['responsable_id'] ?: null;
                }
                if (array_key_exists('revisor_id', $update)) {
                    $item->reviewer_user_id = $update['revisor_id'] ?: null;
                }

                $meta = $item->metadata ?: [];
                if (array_key_exists('responsable', $update)) $meta['responsable_text'] = $update['responsable'];
                if (array_key_exists('revisor', $update)) $meta['revisor_text'] = $update['revisor'];
                $item->metadata = $meta;

                $item->save();
            }

            return response()->json([
                'ok'      => true,
                'payload' => $this->checklistPayload($project),
            ]);
        }

        return response()->json([
            'ok'      => false,
            'message' => 'Acción de checklist no válida.',
        ], 422);
    }

    public function attachChecklist(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $request->validate([
            'id'      => ['nullable', 'integer'],
            'idx'     => ['nullable', 'integer'],
            'files'   => ['required', 'array'],
            'files.*' => ['file', 'max:20480'],
        ]);

        $item = $this->findChecklistItemFromRequest($request, $project);
        $saved = [];

        foreach ($request->file('files', []) as $file) {
            $path = $file->store("projects/{$project->id}/checklist/{$item->id}", 'public');

            $attachment = $item->attachments()->create([
                'user_id'       => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'file_path'     => $path,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);

            $saved[] = [
                'id'          => $attachment->id,
                'name'        => $attachment->original_name,
                'url'         => $attachment->url,
                'mime'        => $attachment->mime_type,
                'size'        => $attachment->size,
                'uploaded_at' => optional($attachment->created_at)->format('Y-m-d H:i:s'),
            ];
        }

        $item->load(['responsible', 'reviewer', 'notes.user', 'attachments', 'sourceDocument']);

        return response()->json([
            'ok'          => true,
            'attachments' => $saved,
            'item'        => $this->itemToChecklistArray($item),
            'payload'     => $this->checklistPayload($project),
        ]);
    }

    public function exportChecklist(Request $request, Project $project, string $format)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $items = $this->checklistQuery($project)->get()->map(fn ($item) => $this->itemToChecklistArray($item))->values()->all();
        $filenameBase = 'checklist-' . Str::slug($project->name) . '-' . now()->format('Ymd-His');

        if ($format === 'csv' || $format === 'excel') {
            $headers = [
                'Requisito', 'Descripcion', 'Criterio de cumplimiento', 'Formato', 'Categoria',
                'Aplicabilidad', 'Obligatorio', 'Cumplimiento', 'Status', 'Prioridad', 'Fecha limite',
                'Responsable', 'Revisor', 'Fuente', 'Pagina', 'Notas', 'Adjuntos',
            ];

            $callback = function () use ($headers, $items) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, $headers);

                foreach ($items as $item) {
                    $notas = collect($item['notas'] ?? [])->map(fn ($n) => is_array($n) ? ($n['body'] ?? '') : (string) $n)->filter()->implode(' | ');
                    $adjuntos = collect($item['adjuntos'] ?? [])->pluck('name')->filter()->implode(', ');

                    fputcsv($out, [
                        $item['requisito'] ?? '',
                        $item['descripcion'] ?? '',
                        $item['criterio_cumplimiento'] ?? '',
                        $item['formato'] ?? '',
                        $item['categoria'] ?? '',
                        $item['aplicabilidad'] ?? '',
                        $item['obligatorio'] ?? '',
                        $item['cumplimiento'] ?? '',
                        $item['status'] ?? '',
                        $item['prioridad'] ?? '',
                        $item['fecha_limite'] ?? '',
                        $item['responsable'] ?? '',
                        $item['revisor'] ?? '',
                        $item['fuente'] ?? '',
                        $item['pagina'] ?? '',
                        $notas,
                        $adjuntos,
                    ]);
                }

                fclose($out);
            };

            return Response::streamDownload($callback, $filenameBase . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        if ($format === 'pdf') {
            $html = view('projects.exports.checklist-pdf', [
                'project'  => $project,
                'items'    => $items,
                'counters' => $this->checklistCountersFromArray($items),
            ])->render();

            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                    ->setPaper('letter', 'landscape')
                    ->download($filenameBase . '.pdf');
            }

            return response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return response()->json([
            'ok'      => false,
            'message' => 'Formato de exportación no soportado.',
        ], 422);
    }

    public function reanalyzeChecklist(Project $project, PythonProjectProcessor $processor)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        try {
            $paths = ProjectDocument::where('project_id', $project->id)
                ->get()
                ->map(fn ($d) => storage_path('app/public/' . $d->file_path))
                ->filter(fn ($p) => file_exists($p))
                ->values()
                ->all();

            if (empty($paths)) {
                return response()->json(['ok' => false, 'error' => 'No hay documentos para reanalizar.'], 422);
            }

            $result = $processor->process($project, $paths);
            $newChecklist = data_get($result, 'structured_data.checklist_sugerido', []);

            if (!empty($result['structured_data'])) {
                $sd = $project->structured_data ?? [];
                $sd = array_merge($sd, $result['structured_data']);
                $project->structured_data = $sd;
            }

            $project->checklist = $newChecklist; // legacy / respaldo
            $project->save();

            $this->syncChecklistItemsFromArray($project, $newChecklist, true);

            return response()->json([
                'ok'      => true,
                'payload' => $this->checklistPayload($project),
            ]);
        } catch (\Throwable $e) {
            Log::error('Reanalyze checklist failed', ['err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function findChecklistItemFromRequest(Request $request, Project $project): ProjectChecklistItem
    {
        $id = $request->input('id') ?: $request->input('item_id') ?: $request->input('idx');

        if (!$id) {
            abort(422, 'No se recibió el ID del requisito.');
        }

        return $project->checklistItems()->findOrFail($id);
    }

    private function checklistQuery(Project $project)
    {
        return $project->checklistItems()
            ->with(['responsible', 'reviewer', 'notes.user', 'attachments', 'sourceDocument']);
    }

    private function checklistPayload(Project $project): array
    {
        $items = $this->checklistQuery($project)->get();

        return [
            'items'    => $items->map(fn ($item) => $this->itemToChecklistArray($item))->values()->all(),
            'counters' => $this->checklistCountersFromCollection($items),
        ];
    }

    private function checklistCountersFromCollection($items): array
    {
        return [
            'sin_revisar' => $items->where('compliance_status', ProjectChecklistItem::COMPLIANCE_SIN_REVISAR)->count(),
            'no_cumple'   => $items->where('compliance_status', ProjectChecklistItem::COMPLIANCE_NO_CUMPLE)->count(),
            'parcial'     => $items->where('compliance_status', ProjectChecklistItem::COMPLIANCE_PARCIAL)->count(),
            'cumple'      => $items->where('compliance_status', ProjectChecklistItem::COMPLIANCE_CUMPLE)->count(),
            'pendiente'   => $items->where('review_status', ProjectChecklistItem::STATUS_PENDIENTE)->count(),
            'revision'    => $items->where('review_status', ProjectChecklistItem::STATUS_EN_REVISION)->count(),
            'aprobado'    => $items->where('review_status', ProjectChecklistItem::STATUS_APROBADO)->count(),
            'total'       => $items->count(),
        ];
    }

    private function checklistCountersFromArray(array $items): array
    {
        $collection = collect($items);

        return [
            'sin_revisar' => $collection->where('cumplimiento', '-')->count(),
            'no_cumple'   => $collection->where('cumplimiento', 'No Cumple')->count(),
            'parcial'     => $collection->where('cumplimiento', 'Parcial')->count(),
            'cumple'      => $collection->where('cumplimiento', 'Cumple')->count(),
            'pendiente'   => $collection->where('status', 'Pendiente')->count(),
            'revision'    => $collection->where('status', 'En revisión')->count(),
            'aprobado'    => $collection->where('status', 'Aprobado')->count(),
            'total'       => $collection->count(),
        ];
    }

    private function validateChecklistItemRequest(Request $request): array
    {
        $item = $request->input('item');

        if (is_string($item)) {
            $item = json_decode($item, true);
        }

        if (!is_array($item)) {
            $item = $request->all();
        }

        return Validator::make($item, [
            'requisito'             => ['required', 'string', 'max:1000'],
            'descripcion'           => ['nullable', 'string'],
            'criterio_cumplimiento' => ['nullable', 'string'],
            'formato'               => ['nullable', 'string', 'max:255'],
            'categoria'             => ['nullable', 'string', 'max:255'],
            'aplicabilidad'         => ['nullable', 'string', 'max:255'],
            'obligatorio'           => ['nullable', 'string', 'max:20'],
            'cumplimiento'          => ['nullable', 'string'],
            'status'                => ['nullable', 'string'],
            'prioridad'             => ['nullable', 'string'],
            'fecha_limite'          => ['nullable', 'date'],
            'responsable'           => ['nullable', 'string', 'max:255'],
            'responsable_id'        => ['nullable', 'integer', 'exists:users,id'],
            'revisor'               => ['nullable', 'string', 'max:255'],
            'revisor_id'            => ['nullable', 'integer', 'exists:users,id'],
            'notas'                 => ['nullable'],
            'fuente'                => ['nullable', 'string', 'max:500'],
            'pagina'                => ['nullable'],
            'cita'                  => ['nullable', 'string'],
        ])->validate();
    }

    private function fillChecklistItem(ProjectChecklistItem $model, array $data): ProjectChecklistItem
    {
        $model->requirement = $data['requisito'] ?? $model->requirement;
        $model->description = $data['descripcion'] ?? '';
        $model->compliance_criteria = $data['criterio_cumplimiento'] ?? '';
        $model->format = $data['formato'] ?? 'No aplica';
        $model->category = $data['categoria'] ?? 'Legal-Administrativo';
        $model->applicability = $data['aplicabilidad'] ?? 'Único';
        $model->mandatory = ($data['obligatorio'] ?? 'Sí') !== 'No';
        $model->compliance_status = $this->normalizeCompliance($data['cumplimiento'] ?? null);
        $model->review_status = $this->normalizeReviewStatus($data['status'] ?? null);
        $model->priority = $this->normalizePriority($data['prioridad'] ?? null);
        $model->due_date = $data['fecha_limite'] ?? null;
        $model->responsible_user_id = $data['responsable_id'] ?? null;
        $model->reviewer_user_id = $data['revisor_id'] ?? null;
        $model->source_name = $data['fuente'] ?? '';
        $model->source_page = is_numeric($data['pagina'] ?? null) ? (int) $data['pagina'] : null;
        $model->source_quote = $data['cita'] ?? '';

        $meta = $model->metadata ?: [];
        if (array_key_exists('responsable', $data)) $meta['responsable_text'] = $data['responsable'];
        if (array_key_exists('revisor', $data)) $meta['revisor_text'] = $data['revisor'];
        $model->metadata = $meta;

        return $model;
    }

    private function storeInlineNotes(ProjectChecklistItem $item, $notas): void
    {
        if ($notas === null) return;
        if (is_string($notas)) $notas = $notas ? [$notas] : [];
        if (!is_array($notas)) return;

        foreach ($notas as $nota) {
            $body = is_array($nota) ? ($nota['body'] ?? '') : (string) $nota;
            $body = trim($body);
            if ($body === '') continue;
            $item->notes()->create([
                'user_id' => Auth::id(),
                'body'    => $body,
            ]);
        }
    }

    private function normalizeCompliance(?string $value): string
    {
        return match ($value) {
            'Cumple', 'cumple' => ProjectChecklistItem::COMPLIANCE_CUMPLE,
            'Parcial', 'parcial' => ProjectChecklistItem::COMPLIANCE_PARCIAL,
            'No Cumple', 'no_cumple' => ProjectChecklistItem::COMPLIANCE_NO_CUMPLE,
            default => ProjectChecklistItem::COMPLIANCE_SIN_REVISAR,
        };
    }

    private function normalizeReviewStatus(?string $value): string
    {
        return match ($value) {
            'En revisión', 'en_revision' => ProjectChecklistItem::STATUS_EN_REVISION,
            'Aprobado', 'aprobado' => ProjectChecklistItem::STATUS_APROBADO,
            default => ProjectChecklistItem::STATUS_PENDIENTE,
        };
    }

    private function normalizePriority(?string $value): string
    {
        return match ($value) {
            'Alta', 'alta' => ProjectChecklistItem::PRIORITY_ALTA,
            'Baja', 'baja' => ProjectChecklistItem::PRIORITY_BAJA,
            default => ProjectChecklistItem::PRIORITY_MEDIA,
        };
    }

    private function itemToChecklistArray(ProjectChecklistItem $item): array
    {
        if (method_exists($item, 'toChecklistArray')) {
            $array = $item->toChecklistArray();
        } else {
            $array = [];
        }

        $meta = $item->metadata ?: [];

        return array_merge($array, [
            'id'                    => $item->id,
            'requisito'             => $item->requirement,
            'descripcion'           => $item->description,
            'criterio_cumplimiento' => $item->compliance_criteria,
            'formato'               => $item->format ?: 'No aplica',
            'categoria'             => $item->category ?: 'Legal-Administrativo',
            'aplicabilidad'         => $item->applicability ?: 'Único',
            'obligatorio'           => $item->mandatory ? 'Sí' : 'No',
            'cumplimiento'          => $this->complianceLabel($item->compliance_status),
            'status'                => $this->reviewStatusLabel($item->review_status),
            'prioridad'             => $this->priorityLabel($item->priority),
            'fecha_limite'          => optional($item->due_date)->format('Y-m-d'),
            'responsable_id'        => $item->responsible_user_id,
            'responsable'           => $item->responsible?->name ?: ($meta['responsable_text'] ?? ''),
            'revisor_id'            => $item->reviewer_user_id,
            'revisor'               => $item->reviewer?->name ?: ($meta['revisor_text'] ?? ''),
            'fuente'                => $item->source_name,
            'pagina'                => $item->source_page,
            'cita'                  => $item->source_quote,
            'notas'                 => $item->notes->map(fn ($note) => [
                'id'         => $note->id,
                'body'       => $note->body,
                'user_id'    => $note->user_id,
                'user_name'  => $note->user?->name,
                'created_at' => optional($note->created_at)->format('Y-m-d H:i:s'),
            ])->values()->all(),
            'adjuntos'              => $item->attachments->map(fn ($attachment) => [
                'id'          => $attachment->id,
                'name'        => $attachment->original_name,
                'url'         => $attachment->url,
                'mime'        => $attachment->mime_type,
                'size'        => $attachment->size,
                'uploaded_at' => optional($attachment->created_at)->format('Y-m-d H:i:s'),
            ])->values()->all(),
        ]);
    }

    private function complianceLabel(string $value): string
    {
        return match ($value) {
            ProjectChecklistItem::COMPLIANCE_CUMPLE => 'Cumple',
            ProjectChecklistItem::COMPLIANCE_PARCIAL => 'Parcial',
            ProjectChecklistItem::COMPLIANCE_NO_CUMPLE => 'No Cumple',
            default => '-',
        };
    }

    private function reviewStatusLabel(string $value): string
    {
        return match ($value) {
            ProjectChecklistItem::STATUS_EN_REVISION => 'En revisión',
            ProjectChecklistItem::STATUS_APROBADO => 'Aprobado',
            default => 'Pendiente',
        };
    }

    private function priorityLabel(string $value): string
    {
        return match ($value) {
            ProjectChecklistItem::PRIORITY_ALTA => 'Alta',
            ProjectChecklistItem::PRIORITY_BAJA => 'Baja',
            default => 'Media',
        };
    }

    private function ensureChecklistItemsExist(Project $project): void
    {
        if ($project->checklistItems()->exists()) {
            return;
        }

        $legacy = $project->checklist ?: data_get($project->structured_data ?? [], 'checklist_sugerido', []);
        if (is_array($legacy) && !empty($legacy)) {
            $this->syncChecklistItemsFromArray($project, $legacy, false);
        }
    }

    private function syncChecklistItemsFromArray(Project $project, array $items, bool $mergeExisting = true): void
    {
        $existing = $mergeExisting
            ? $project->checklistItems()->with(['notes', 'attachments'])->get()->keyBy(fn ($item) => mb_strtolower(trim($item->requirement)))
            : collect();

        if (!$mergeExisting) {
            $project->checklistItems()->delete();
        }

        foreach (array_values($items) as $position => $raw) {
            if (!is_array($raw)) continue;

            $requirement = $raw['requisito'] ?? $raw['item'] ?? $raw['text'] ?? 'Sin nombre';
            $key = mb_strtolower(trim($requirement));

            $item = $existing->get($key) ?: new ProjectChecklistItem([
                'project_id' => $project->id,
                'position'   => $position,
            ]);

            $this->fillChecklistItem($item, [
                'requisito'             => $requirement,
                'descripcion'           => $raw['descripcion'] ?? '',
                'criterio_cumplimiento' => $raw['criterio_cumplimiento'] ?? '',
                'formato'               => $raw['formato'] ?? 'No aplica',
                'categoria'             => $raw['categoria'] ?? 'Legal-Administrativo',
                'aplicabilidad'         => $raw['aplicabilidad'] ?? 'Único',
                'obligatorio'           => $raw['obligatorio'] ?? 'Sí',
                'cumplimiento'          => $raw['cumplimiento'] ?? '-',
                'status'                => $raw['status'] ?? 'Pendiente',
                'prioridad'             => $raw['prioridad'] ?? 'Media',
                'fecha_limite'          => $raw['fecha_limite'] ?? null,
                'fuente'                => $raw['fuente'] ?? '',
                'pagina'                => $raw['pagina'] ?? null,
                'cita'                  => $raw['cita'] ?? $raw['evidencia'] ?? $raw['fragmento'] ?? '',
            ]);

            $item->project_id = $project->id;
            $item->position = $position;
            $item->source_item_id = $raw['id'] ?? $item->source_item_id;
            $item->save();
        }
    }

    /* ============================================================
     |  REPORTE
     * ============================================================ */
    public function generateReport(Project $project, OpenAiStructurerService $ai)
    {
        try {
            $context   = json_encode($project->structured_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $checklist = json_encode($project->checklist,       JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $prompt = "Eres un consultor experto en licitaciones públicas mexicanas. "
                . "Genera un REPORTE EJECUTIVO COMPLETO en HTML para el proyecto: \"{$project->name}\".\n\n"
                . "Usa SOLO los siguientes datos:\n\n"
                . "=== DATOS ESTRUCTURADOS ===\n{$context}\n\n"
                . "=== CHECKLIST ===\n{$checklist}\n\n"
                . "Devuelve EXCLUSIVAMENTE HTML (sin ```html ni explicaciones). Estructura sugerida:\n"
                . "<h1>Reporte ejecutivo: {NOMBRE}</h1>\n"
                . "<h2>1. Resumen general</h2>\n"
                . "<h2>2. Datos del procedimiento</h2> (usa <table>)\n"
                . "<h2>3. Fechas clave</h2> (usa <table>)\n"
                . "<h2>4. Requisitos críticos</h2> (usa <ul> o <table>)\n"
                . "<h2>5. Estado de cumplimiento del checklist</h2> (usa <table> con conteos)\n"
                . "<h2>6. Riesgos detectados</h2>\n"
                . "<h2>7. Recomendaciones</h2>\n"
                . "Aplica estilos inline en <table> (border-collapse, padding, border #e5e7eb, encabezado #f3f4f6).";

            $messages = [
                ['role' => 'system', 'content' => 'Eres un generador de reportes HTML profesionales.'],
                ['role' => 'user',   'content' => $prompt],
            ];

            $html = $ai->chatRaw($messages);
            $html = preg_replace('/^```html\s*/i', '', trim($html));
            $html = preg_replace('/```$/', '', trim($html));

            $project->report_content = $html;
            $project->save();

            return response()->json(['ok' => true, 'html' => $html]);
        } catch (\Throwable $e) {
            Log::error('Report generation failed', ['err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}