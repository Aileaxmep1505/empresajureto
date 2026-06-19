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
            [
                'id' => 'analisis_bases',
                'name' => 'Análisis de Bases',
                'color' => 'blue',
                'workflow_statuses' => ['analisis_bases'],
            ],
            [
                'id' => 'revision',
                'name' => 'Revisión',
                'color' => 'orange',
                'workflow_statuses' => ['revision'],
            ],
            [
                'id' => 'participa',
                'name' => 'Participa',
                'color' => 'green',
                'workflow_statuses' => ['participa', 'junta_aclaraciones', 'armado_propuesta', 'entrega'],
            ],
            [
                'id' => 'no_participa',
                'name' => 'No participa',
                'color' => 'red',
                'workflow_statuses' => ['no_participa'],
            ],
            [
                'id' => 'ganado',
                'name' => 'Ganado',
                'color' => 'purple',
                'workflow_statuses' => ['ganado'],
            ],
            [
                'id' => 'perdido',
                'name' => 'Perdido',
                'color' => 'gray',
                'workflow_statuses' => ['perdido'],
            ],
            [
                'id' => 'desierta',
                'name' => 'Desierta',
                'color' => 'rose',
                'workflow_statuses' => ['desierta'],
            ],
        ];
    }

    /* ============================================================
     |  INDEX  (vista Kanban + listado)
     * ============================================================ */
    public function index(Request $request)
    {
        $queryText = trim((string) $request->input('q', ''));
        $labelFilter = trim((string) $request->input('label', ''));

        $projects = Project::where('user_id', Auth::id())
            ->when($queryText !== '', function ($query) use ($queryText) {
                $query->where(function ($sub) use ($queryText) {
                    $sub->where('name', 'like', "%{$queryText}%")
                        ->orWhere('priority', 'like', "%{$queryText}%")
                        ->orWhere('labels', 'like', "%{$queryText}%");
                });
            })
            ->when($labelFilter !== '', function ($query) use ($labelFilter) {
                $query->where('labels', 'like', '%"' . addcslashes($labelFilter, '%_\\') . '"%');
            })
            ->latest()
            ->get();

        // IMPORTANTE:
        // Algunas filas antiguas pueden no tener slug.
        // La vista usa el slug para construir las rutas AJAX de etiquetas, favorito y cambio de estado.
        // Si no existe, aquí lo generamos una sola vez para evitar el error:
        // "No se encontró la ruta para guardar etiquetas del proyecto."
        $projects->each(function (Project $project) {
            if (!blank($project->slug)) {
                return;
            }

            $base = Str::slug($project->name ?: 'proyecto');
            $base = $base !== '' ? $base : 'proyecto';

            do {
                $slug = $base . '-' . Str::lower(Str::random(6));
            } while (Project::where('slug', $slug)->whereKeyNot($project->getKey())->exists());

            $project->forceFill(['slug' => $slug])->saveQuietly();
        });

        $knownWorkflowStatuses = collect($this->defaultColumns())
            ->flatMap(fn ($column) => $column['workflow_statuses'] ?? [$column['id']])
            ->unique()
            ->values()
            ->all();

        $columns = collect($this->defaultColumns())->map(function ($column) use ($projects) {
            $statuses = $column['workflow_statuses'] ?? [$column['id']];

            $items = $projects->filter(function ($project) use ($statuses) {
                $workflowStatus = $project->workflow_status ?: 'analisis_bases';
                return in_array($workflowStatus, $statuses, true);
            })->values();

            $column['count'] = $items->count();
            $column['projects'] = $items;

            return $column;
        })->all();

        $orphanProjects = $projects->reject(function ($project) use ($knownWorkflowStatuses) {
            return in_array($project->workflow_status ?: 'analisis_bases', $knownWorkflowStatuses, true);
        })->values();

        if ($orphanProjects->count()) {
            $columns = array_map(function ($column) use ($orphanProjects) {
                if ($column['id'] === 'analisis_bases') {
                    $column['projects'] = $column['projects']->concat($orphanProjects)->values();
                    $column['count'] = $column['projects']->count();
                }

                return $column;
            }, $columns);
        }

        $openColumns = session('projects.open_columns', [
            'analisis_bases',
            'revision',
            'participa',
            'no_participa',
            'ganado',
            'perdido',
            'desierta',
        ]);

        $viewMode = session('projects.view_mode', 'board');

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
            'workflow_status' => 'analisis_bases',
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

        $project->load([
            'documents',
            'user',
            'checklistItems',
        ]);

        return view('projects.dashboard', compact('project'));
    }

    /* ============================================================
     |  REPORTES   ->  Vista independiente de reportes
     * ============================================================ */
    public function reports(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $project->load([
            'documents',
            'user',
            'assignee',
            'checklistItems.responsible',
            'checklistItems.reviewer',
            'checklistItems.notes.user',
            'checklistItems.attachments',
            'checklistItems.sourceDocument',
        ]);

        return view('projects.reports', compact('project'));
    }
    /* ============================================================
     |  ANALISIS   ->  Vista con CHAT + TABS
     * ============================================================ */
    public function analisis(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $project->load([
            'documents',
            'chatMessages',
            'checklistItems.notes.user',
            'checklistItems.attachments',
            'checklistItems.responsible',
            'checklistItems.reviewer',
            'checklistItems.sourceDocument',
        ]);

        $documentLibrary = $this->projectDocumentLibrary($project);

        return view('projects.analisis', [
            'project' => $project,
            'documentLibrary' => $documentLibrary,
        ]);
    }

    private function humanFileSize(?int $bytes): string
    {
        $bytes = max(0, (int) $bytes);

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }

    private function documentPages(ProjectDocument $document): ?int
    {
        $raw = $document->extracted_raw ?? [];

        foreach (['pages_count', 'page_count', 'num_pages', 'total_pages'] as $key) {
            if (isset($raw[$key]) && is_numeric($raw[$key])) {
                return (int) $raw[$key];
            }
        }

        if (isset($raw['pages']) && is_array($raw['pages'])) {
            return count($raw['pages']);
        }

        return null;
    }

    private function documentInsight(ProjectDocument $document): string
    {
        $raw = $document->extracted_raw ?? [];

        foreach (['summary', 'resumen', 'descripcion', 'description'] as $key) {
            if (!empty($raw[$key]) && is_string($raw[$key])) {
                return Str::limit(trim($raw[$key]), 360);
            }
        }

        $text = trim((string) ($document->extracted_text ?? ''));

        if ($text !== '') {
            $text = preg_replace('/\s+/', ' ', $text);
            return Str::limit($text, 360);
        }

        if ($document->status === 'completed') {
            return 'Documento procesado correctamente. La información extraída está disponible para ficha, checklist, borrador y reporte.';
        }

        return 'Documento registrado en el proyecto. Aún no hay información extraída disponible para mostrar.';
    }
    private function projectDocumentLibrary(Project $project): array
    {
        $project->loadMissing([
            'documents',
            'checklistItems.attachments',
        ]);

        $items = [];

        foreach ($project->documents as $document) {
            $raw = is_array($document->extracted_raw) ? $document->extracted_raw : [];

            $pages = $this->documentPages($document);

            $summary = $raw['summary']
                ?? $raw['resumen']
                ?? $raw['document_summary']
                ?? $raw['descripcion']
                ?? $raw['description']
                ?? null;

            if (!$summary && !empty($document->extracted_text)) {
                $summary = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $document->extracted_text))), 260);
            }

            $extension = strtoupper(pathinfo((string) $document->filename, PATHINFO_EXTENSION) ?: 'DOC');
            $url = $document->file_path ? Storage::disk('public')->url($document->file_path) : null;

            $items[] = [
                'id'           => 'source-' . $document->id,
                'type'         => 'source',
                'source_type'  => 'project_document',
                'db_id'        => $document->id,
                'source_id'    => $document->id,
                'title'        => $document->filename ?: 'Documento sin nombre',
                'filename'     => $document->filename ?: 'Documento sin nombre',
                'extension'    => $extension,
                'badge'        => $extension,
                'mime_type'    => $document->mime_type,
                'size'         => (int) ($document->file_size ?? 0),
                'size_label'   => $this->humanFileSize((int) ($document->file_size ?? 0)),
                'status'       => $document->status ?: 'completed',
                'status_label' => $this->documentStatusLabel($document->status),
                'status_class' => $this->documentStatusClass($document->status),
                'date_label'   => optional($document->created_at)->format('d/m/Y'),
                'pages'        => $pages,
                'pages_label'  => $pages ? $pages . ' página(s)' : null,
                'summary'      => $summary ?: 'Documento fuente cargado en el proyecto.',
                'match_label'  => 'Documento fuente del proyecto',
                'source'       => 'Documento del proyecto',
                'related_to'   => null,
                'requirement'  => null,
                'url'          => $url,
                'download_url' => $url,
                'delete_url'   => route('projects.documents.destroy', [$project, $document->id]),
                'can_delete'   => true,
                'created_at'   => optional($document->created_at)->timestamp ?? 0,
            ];
        }

        foreach ($project->checklistItems as $checklistItem) {
            foreach ($checklistItem->attachments as $attachment) {
                $extension = strtoupper(pathinfo((string) $attachment->original_name, PATHINFO_EXTENSION) ?: 'FILE');
                $url = $attachment->file_path ? Storage::disk('public')->url($attachment->file_path) : null;

                $items[] = [
                    'id'           => 'checklist-' . $attachment->id,
                    'type'         => 'checklist_attachment',
                    'source_type'  => 'checklist_attachment',
                    'db_id'        => $attachment->id,
                    'source_id'    => $attachment->id,
                    'title'        => $attachment->original_name ?: 'Evidencia sin nombre',
                    'filename'     => $attachment->original_name ?: 'Evidencia sin nombre',
                    'extension'    => $extension,
                    'badge'        => $extension,
                    'mime_type'    => $attachment->mime_type,
                    'size'         => (int) ($attachment->size ?? 0),
                    'size_label'   => $this->humanFileSize((int) ($attachment->size ?? 0)),
                    'status'       => 'completed',
                    'status_label' => 'Completado',
                    'status_class' => 'success',
                    'date_label'   => optional($attachment->created_at)->format('d/m/Y'),
                    'pages'        => null,
                    'pages_label'  => null,
                    'summary'      => 'Evidencia adjunta al requisito: ' . Str::limit((string) $checklistItem->requirement, 180),
                    'match_label'  => 'Checklist / Evidencia',
                    'source'       => 'Evidencia del checklist',
                    'related_to'   => $checklistItem->requirement,
                    'requirement'  => $checklistItem->requirement,
                    'url'          => $url,
                    'download_url' => $url,
                    'delete_url'   => route('projects.checklist.attachments.destroy', [$project, $attachment->id]),
                    'can_delete'   => true,
                    'created_at'   => optional($attachment->created_at)->timestamp ?? 0,
                ];
            }
        }

        return collect($items)
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    private function documentStatusLabel(?string $status): string
    {
        return match ($status) {
            'completed', 'processed', 'done', 'ok', 'listo' => 'Completado',
            'processing', 'pending', 'pendiente' => 'Procesando',
            'failed', 'error' => 'Error',
            default => 'Completado',
        };
    }

    private function documentStatusClass(?string $status): string
    {
        return match ($status) {
            'failed', 'error' => 'danger',
            'processing', 'pending', 'pendiente' => 'info',
            default => 'success',
        };
    }

    public function destroyProjectDocument(Request $request, Project $project, ProjectDocument $document)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);
        abort_if((int) $document->project_id !== (int) $project->id, 404);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Documento eliminado correctamente.',
                'documents' => $this->projectDocumentLibrary($project->fresh()),
            ]);
        }

        return back()->with('success', 'Documento eliminado.');
    }

    public function destroyChecklistAttachment(Request $request, Project $project, ProjectChecklistAttachment $attachment)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $attachment->load('item');
        abort_if(!$attachment->item || (int) $attachment->item->project_id !== (int) $project->id, 404);

        if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Evidencia eliminada correctamente.',
                'documents' => $this->projectDocumentLibrary($project->fresh()),
            ]);
        }

        return back()->with('success', 'Evidencia eliminada.');
    }

    // Aliases para compatibilidad con rutas anteriores.
    public function deleteDocument(Request $request, Project $project, ProjectDocument $document)
    {
        return $this->destroyProjectDocument($request, $project, $document);
    }

    public function deleteChecklistAttachment(Request $request, Project $project, ProjectChecklistAttachment $attachment)
    {
        return $this->destroyChecklistAttachment($request, $project, $attachment);
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
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $content = $request->input('draft_content', $request->input('draft'));
        $request->merge(['draft_content' => $content]);
        $request->validate(['draft_content' => 'nullable|string']);

        // Borrador y Reporte son el mismo documento editable.
        $project->draft_content  = $content;
        $project->report_content = $content;
        $project->save();

        return response()->json([
            'ok' => true,
            'saved_at' => now()->format('H:i:s'),
        ]);
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
     |  REPORTE EJECUTIVO
     |  - Backend genera el contenido
     |  - Borrador y Reporte son un mismo documento editable
     * ============================================================ */
    private function projectChecklistReportArray(Project $project): array
    {
        $this->ensureChecklistItemsExist($project);

        $project->loadMissing([
            'checklistItems.responsible',
            'checklistItems.reviewer',
            'checklistItems.notes.user',
            'checklistItems.attachments',
            'checklistItems.sourceDocument',
        ]);

        if ($project->checklistItems->isNotEmpty()) {
            return $project->checklistItems
                ->sortBy(['position', 'id'])
                ->map(fn ($item) => $item->toChecklistArray())
                ->values()
                ->all();
        }

        $legacy = $project->checklist ?: data_get($project->structured_data ?? [], 'checklist_sugerido', []);
        return is_array($legacy) ? array_values($legacy) : [];
    }

    private function reportValue(array $data, array $paths, ?string $fallback = null): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($data, $path);

            if (is_array($value)) {
                $value = collect($value)
                    ->filter(fn ($v) => is_scalar($v) && trim((string) $v) !== '')
                    ->implode(', ');
            }

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return $fallback;
    }

    private function reportRowsHtml(array $rows): string
    {
        $html = '<div class="pjd-report-grid">';

        foreach ($rows as $row) {
            $label = e($row['label']);
            $value = e($row['value'] ?: $row['empty']);
            $emptyClass = empty($row['value']) ? ' pjd-report-empty' : '';

            $html .= '<div class="pjd-report-item"><span class="pjd-report-label">' . $label . '</span><p class="pjd-report-value' . $emptyClass . '">' . $value . '</p></div>';
        }

        return $html . '</div>';
    }

    private function checklistCountersForReport(array $checklist): array
    {
        $counters = [
            'total' => count($checklist),
            'sin_revisar' => 0,
            'cumple' => 0,
            'parcial' => 0,
            'no_cumple' => 0,
            'pendiente' => 0,
            'en_revision' => 0,
            'aprobado' => 0,
        ];

        foreach ($checklist as $item) {
            $cumplimiento = $item['cumplimiento'] ?? $item['cumplimiento_label'] ?? '-';
            $status = $item['status'] ?? $item['status_label'] ?? 'Pendiente';

            match ($cumplimiento) {
                'Cumple' => $counters['cumple']++,
                'Parcial' => $counters['parcial']++,
                'No Cumple' => $counters['no_cumple']++,
                default => $counters['sin_revisar']++,
            };

            match ($status) {
                'En revisión' => $counters['en_revision']++,
                'Aprobado' => $counters['aprobado']++,
                default => $counters['pendiente']++,
            };
        }

        return $counters;
    }

    private function buildExecutiveReportFallbackHtml(Project $project, array $checklist): string
    {
        $data = $project->structured_data ?? [];
        $resumen = is_array(data_get($data, 'resumen_ejecutivo')) ? data_get($data, 'resumen_ejecutivo') : [];
        $counters = $this->checklistCountersForReport($checklist);

        $fichaRows = [
            ['label' => 'Número de licitación', 'value' => $this->reportValue($data, ['ficha_general.numero_licitacion', 'numero_licitacion', 'licitacion.numero', 'procedimiento.numero']), 'empty' => 'En los documentos revisados, no se encontró información sobre el número de licitación.'],
            ['label' => 'Tipo de evento', 'value' => $this->reportValue($data, ['ficha_general.tipo_evento', 'tipo_evento', 'procedimiento.tipo_evento', 'tipo_procedimiento']), 'empty' => 'No se encontró información sobre el tipo de evento.'],
            ['label' => 'Organismo', 'value' => $this->reportValue($data, ['ficha_general.organismo', 'organismo', 'dependencia', 'convocante']), 'empty' => 'No se encontró información sobre el organismo convocante.'],
            ['label' => '¿Cuál es el objeto de la licitación?', 'value' => $this->reportValue($data, ['ficha_general.objeto_licitacion', 'objeto_licitacion', 'objeto', 'procedimiento.objeto']), 'empty' => 'No se encontró información sobre objeto.'],
            ['label' => '¿Cuál es el medio de participación?', 'value' => $this->reportValue($data, ['ficha_general.medio_participacion', 'medio_participacion', 'procedimiento.medio_participacion']), 'empty' => 'No se encontró información sobre el medio de participación.'],
        ];

        $fechaRows = [
            ['label' => 'Fecha de publicación', 'value' => $this->reportValue($data, ['fechas_clave.fecha_publicacion', 'fecha_publicacion']), 'empty' => 'En los documentos revisados, no se encontró información sobre la fecha de publicación del resumen de la convocatoria.'],
            ['label' => 'Junta de aclaraciones', 'value' => $this->reportValue($data, ['fechas_clave.junta_aclaraciones', 'junta_aclaraciones']), 'empty' => 'No se encontró información sobre junta de aclaraciones.'],
            ['label' => 'Presentación y apertura de proposiciones', 'value' => $this->reportValue($data, ['fechas_clave.presentacion_apertura', 'presentacion_apertura', 'fecha_presentacion_apertura']), 'empty' => 'No se encontró información sobre la presentación y apertura de proposiciones.'],
            ['label' => 'Fallo', 'value' => $this->reportValue($data, ['fechas_clave.fallo', 'fallo', 'fecha_fallo']), 'empty' => 'No se encontró información sobre la fecha de fallo.'],
            ['label' => 'Vigencia del contrato', 'value' => $this->reportValue($data, ['fechas_clave.vigencia_contrato', 'vigencia_contrato', 'contrato.vigencia']), 'empty' => 'No se encontró información sobre la vigencia del contrato.'],
        ];

        $questions = collect($resumen)->map(function ($row) {
            if (is_array($row)) {
                return [
                    'pregunta' => $row['pregunta'] ?? $row['question'] ?? null,
                    'respuesta' => $row['respuesta'] ?? $row['answer'] ?? null,
                ];
            }

            return null;
        })->filter(fn ($row) => $row && (trim((string) $row['pregunta']) !== '' || trim((string) $row['respuesta']) !== ''))->values();

        if ($questions->isEmpty()) {
            $questions = collect([
                ['pregunta' => '¿Cuánto tiempo tengo para implementar?', 'respuesta' => $this->reportValue($data, ['caracteristicas.tiempo_implementacion', 'tiempo_implementacion'], 'No se encontró información específica sobre el tiempo de implementación.')],
                ['pregunta' => '¿Es necesario demostrar experiencia previa o acreditar experiencia?', 'respuesta' => $this->reportValue($data, ['caracteristicas.experiencia_previa', 'experiencia_previa'], 'No se encontró información específica sobre experiencia previa.')],
                ['pregunta' => '¿Se mencionan penas convencionales, multas, deducciones u otras sanciones en caso de incumplimiento?', 'respuesta' => $this->reportValue($data, ['caracteristicas.sanciones', 'penas_convencionales'], 'No se encontró información suficiente sobre sanciones o penas convencionales.')],
                ['pregunta' => '¿Cuál es el periodo de garantía a ofertar?', 'respuesta' => $this->reportValue($data, ['caracteristicas.garantia', 'periodo_garantia'], 'No se encontró información sobre el periodo de garantía.')],
                ['pregunta' => '¿Cuál es el sistema de evaluación?', 'respuesta' => $this->reportValue($data, ['caracteristicas.sistema_evaluacion', 'sistema_evaluacion'], 'No se encontró información sobre el sistema de evaluación.')],
                ['pregunta' => '¿Se requieren cartas de apoyo?', 'respuesta' => $this->reportValue($data, ['caracteristicas.cartas_apoyo', 'cartas_apoyo'], 'No se encontró información sobre cartas de apoyo.')],
                ['pregunta' => '¿Se deben entregar muestras físicas?', 'respuesta' => $this->reportValue($data, ['caracteristicas.muestras_fisicas', 'muestras_fisicas'], 'No se encontró información sobre entrega de muestras físicas.')],
                ['pregunta' => '¿Es necesario entregar documentación regulatoria?', 'respuesta' => $this->reportValue($data, ['caracteristicas.documentacion_regulatoria', 'documentacion_regulatoria'], 'No se encontró información sobre documentación regulatoria.')],
                ['pregunta' => '¿Cómo se realiza la adjudicación?', 'respuesta' => $this->reportValue($data, ['caracteristicas.adjudicacion', 'forma_adjudicacion'], 'No se encontró información sobre la forma de adjudicación.')],
            ]);
        }

        $criticalItems = collect($checklist)
            ->filter(fn ($item) => !empty($item['requisito']))
            ->take(12)
            ->values();

        $html = '<article class="pjd-report-doc">';
        $html .= '<h1>Reporte ejecutivo: ' . e($project->name) . '</h1>';
        $html .= '<section class="pjd-report-section"><h2>Ficha General</h2>' . $this->reportRowsHtml($fichaRows) . '</section>';
        $html .= '<section class="pjd-report-section"><h2>Fechas Clave</h2>' . $this->reportRowsHtml($fechaRows) . '</section>';

        $html .= '<section class="pjd-report-section"><h2>Características Generales</h2>';
        foreach ($questions as $qa) {
            $html .= '<p><strong>' . e($qa['pregunta']) . ':</strong> ' . e($qa['respuesta'] ?: 'No se encontró información.') . '</p>';
        }
        $html .= '</section>';

        $html .= '<section class="pjd-report-section"><h2>Estado del checklist</h2>';
        $html .= '<table style="width:100%;border-collapse:collapse;"><tbody>';
        foreach ([
            'Total' => $counters['total'],
            'Cumple' => $counters['cumple'],
            'Parcial' => $counters['parcial'],
            'No cumple' => $counters['no_cumple'],
            'Sin revisar' => $counters['sin_revisar'],
        ] as $label => $value) {
            $html .= '<tr><td style="border:1px solid #ebebeb;padding:8px;"><strong>' . e($label) . '</strong></td><td style="border:1px solid #ebebeb;padding:8px;">' . e((string) $value) . '</td></tr>';
        }
        $html .= '</tbody></table></section>';

        $html .= '<section class="pjd-report-section"><h2>Puntos a Considerar</h2><ul>';
        if ($criticalItems->isEmpty()) {
            $html .= '<li>No se encontraron requisitos críticos registrados en el checklist.</li>';
        } else {
            foreach ($criticalItems as $item) {
                $label = $item['requisito'] ?? 'Requisito';
                $status = $item['cumplimiento'] ?? '-';
                $priority = $item['prioridad'] ?? 'Media';
                $html .= '<li><strong>' . e($label) . '</strong> - Cumplimiento: ' . e($status) . '. Prioridad: ' . e($priority) . '.</li>';
            }
        }
        $html .= '</ul></section>';
        $html .= '</article>';

        return $html;
    }

    private function buildExecutiveReportPrompt(Project $project, array $checklist): string
    {
        $structured = json_encode($project->structured_data ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $checklistJson = json_encode($checklist, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $name = $project->name;

        return <<<PROMPT
Eres un consultor experto en licitaciones públicas mexicanas. Genera un REPORTE EJECUTIVO en HTML editable para el proyecto "{$name}".

Usa únicamente la información disponible. Si un dato no existe, escribe una frase clara como: En los documentos revisados, no se encontró información sobre...

Estructura obligatoria:
<article class="pjd-report-doc">
<h1>Reporte ejecutivo: {$name}</h1>
<section><h2>Ficha General</h2> Número de licitación, Tipo de evento, Organismo, Objeto de la licitación y Medio de participación.</section>
<section><h2>Fechas Clave</h2> Fecha de publicación, Junta de aclaraciones, Presentación y apertura de proposiciones, Fallo y Vigencia del contrato.</section>
<section><h2>Características Generales</h2> Preguntas y respuestas ejecutivas sobre implementación, experiencia, sanciones, garantía, sistema de evaluación, cartas de apoyo, muestras, documentación regulatoria, entregas, subrogación, idioma, adjudicación y tratados.</section>
<section><h2>Puntos a Considerar</h2> Lista concreta de riesgos, requisitos críticos y observaciones.</section>
</article>

No incluyas markdown ni bloques de código. No inventes datos. Mantén lenguaje profesional, claro y ejecutivo.

=== DATOS ESTRUCTURADOS ===
{$structured}

=== CHECKLIST RELACIONAL ===
{$checklistJson}
PROMPT;
    }

    private function normalizeReportType(?string $type): string
    {
        return match ($type) {
            'finance', 'finanzas', 'financial' => 'finance',
            'logistics', 'logistica', 'logistica_operativa' => 'logistics',
            'technical', 'soporte_tecnico', 'soporte', 'tecnico' => 'technical',
            default => 'analysis',
        };
    }

    private function reportTitleForType(string $type): string
    {
        return match ($type) {
            'finance' => 'Reporte Financiero de la Licitación',
            'logistics' => 'Reporte Logístico de la Licitación',
            'technical' => 'Reporte Técnico de Soporte y Puesta en Marcha',
            default => 'Reporte de análisis de bases',
        };
    }

    private function storedProjectReports(Project $project): array
    {
        $data = $project->structured_data ?? [];
        $reports = data_get($data, 'generated_reports', []);

        return is_array($reports) ? $reports : [];
    }

    private function saveProjectReport(Project $project, string $type, string $html, ?string $title = null): void
    {
        $data = $project->structured_data ?? [];
        if (!is_array($data)) {
            $data = [];
        }

        $reports = data_get($data, 'generated_reports', []);
        if (!is_array($reports)) {
            $reports = [];
        }

        $reports[$type] = [
            'title' => $title ?: $this->reportTitleForType($type),
            'html' => $html,
            'updated_at' => now()->toDateTimeString(),
        ];

        $data['generated_reports'] = $reports;
        $project->structured_data = $data;

        if ($type === 'analysis') {
            $project->report_content = $html;
            $project->draft_content  = $html;
        }

        $project->save();
    }

    private function buildSpecializedReportPrompt(Project $project, array $checklist, string $type): string
    {
        if ($type === 'analysis') {
            return $this->buildExecutiveReportPrompt($project, $checklist);
        }

        $structured = json_encode($project->structured_data ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $checklistJson = json_encode($checklist, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $documentsJson = json_encode($project->documents->map(fn ($doc) => [
            'filename' => $doc->filename,
            'status' => $doc->status,
            'extracted_text_preview' => Str::limit(trim(preg_replace('/\s+/', ' ', (string) $doc->extracted_text)), 2200),
            'extracted_raw' => $doc->extracted_raw,
        ])->values()->all(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $title = $this->reportTitleForType($type);
        $projectName = $project->name;

        $financialStructure = <<<'TXT'
Estructura obligatoria para REPORTE FINANCIERO:
<article class="jrt-report-doc">
<h1>🧾 Reporte Financiero de la Licitación</h1>
<section><h2>🔹 Resumen Ejecutivo</h2><p>Explica objetivo de la licitación, dependencia, forma y plazo de pago, facturación, garantías, penalizaciones y obligaciones financieras.</p></section>
<section><h2>🗂️ Detalle por Categoría</h2>
<h3>💵 Condiciones de pago</h3><ul><li><strong>💰 Plazo de pago establecido:</strong></li><li><strong>🧾 Modalidad de pago:</strong></li><li><strong>📅 Condiciones de entrega:</strong></li><li><strong>💳 Moneda y método de pago:</strong></li></ul>
<h3>🧾 Facturación</h3><ul><li><strong>🏦 Requisitos de facturación:</strong></li><li><strong>🗓️ Tiempos de entrega y validación de facturas:</strong></li><li><strong>📋 Documentos complementarios exigidos:</strong></li><li><strong>⚙️ Procedimiento de revisión o autorización:</strong></li></ul>
<h3>📑 Garantías, fianzas y seguros</h3><ul><li><strong>🛡️ Tipo de pólizas requeridas:</strong></li><li><strong>💵 Porcentajes o montos exigidos:</strong></li><li><strong>🗓️ Vigencia o plazo de cobertura:</strong></li><li><strong>📄 Condiciones para la liberación o devolución:</strong></li></ul>
<h3>💳 Anticipos y retenciones</h3><ul><li><strong>💸 Porcentaje o monto del anticipo:</strong></li><li><strong>🧾 Requisitos para comprobar el uso del anticipo:</strong></li><li><strong>🔁 Devolución o cancelación de garantía:</strong></li><li><strong>⚠️ Retenciones aplicables:</strong></li></ul>
<h3>📈 Requisitos financieros</h3><ul><li><strong>📊 Documentos solicitados:</strong></li><li><strong>🏦 Criterios de capacidad económica:</strong></li><li><strong>🧾 Formatos o declaraciones exigidas:</strong></li></ul>
<h3>📊 Penalizaciones y deducciones</h3><ul><li><strong>⚠️ Multas por atraso o incumplimiento:</strong></li><li><strong>💰 Deducciones automáticas o ajustes:</strong></li><li><strong>🔒 Causales de rescisión o retención de pagos:</strong></li></ul>
<h3>🏦 Cuentas bancarias y comprobaciones</h3><ul><li><strong>💳 Cuentas destino y mecanismos de pago:</strong></li><li><strong>🧾 Documentación de soporte:</strong></li><li><strong>📤 Validaciones o autorizaciones requeridas:</strong></li></ul>
</section>
<section><h2>📚 Fuente de información</h2><table><thead><tr><th>#</th><th>Documento</th><th>Sección / Numeral</th><th>Página</th><th>Descripción breve</th></tr></thead><tbody><tr><td>1</td><td></td><td></td><td></td><td></td></tr></tbody></table></section>
<section><h2>🧩 Observaciones finales</h2><ul><li>⚠️ ...</li></ul></section>
</article>
TXT;

        $logisticsStructure = <<<'TXT'
Estructura obligatoria para REPORTE LOGÍSTICO:
<article class="jrt-report-doc">
<h1>🧾 Reporte Logístico de la Licitación</h1>
<section><h2>🔹 Resumen Ejecutivo</h2><p>Explica objetivo, dependencia, entrega, almacenes, plazos, condiciones de recepción, embalaje, transporte, penalizaciones y riesgos logísticos.</p></section>
<section><h2>🗂️ Detalle por Categoría</h2>
<h3>📦 Entregas y recepción</h3><ul><li><strong>🏠 Lugar(es) de entrega:</strong></li><li><strong>⏰ Horarios o ventanas:</strong></li><li><strong>📋 Condiciones específicas:</strong></li><li><strong>🧾 Documentos requeridos en la entrega:</strong></li></ul>
<h3>⏱️ Plazos y tiempos</h3><ul><li><strong>📅 Fechas límites o cronogramas:</strong></li></ul>
<h3>🚚 Transporte y distribución</h3><ul><li><strong>🚛 Tipo de transporte solicitado:</strong></li><li><strong>🧴 Condiciones de embalaje y etiquetado:</strong></li><li><strong>⚠️ Penalizaciones o sanciones por retraso:</strong></li></ul>
<h3>🏢 Almacenamiento y condiciones</h3><ul><li><strong>🌡️ Condiciones especiales:</strong></li><li><strong>🏗️ Infraestructura o espacios requeridos:</strong></li></ul>
<h3>📄 Documentación logística</h3><ul><li><strong>📜 Remisiones, guías o manifiestos exigidos:</strong></li><li><strong>🖋️ Requisitos de firma o validación:</strong></li></ul>
<h3>⚠️ Riesgos y penalizaciones</h3><ul><li><strong>💸 Sanciones económicas o administrativas:</strong></li><li><strong>🔄 Devoluciones o reemplazos:</strong></li><li><strong>🚨 Escenarios de incumplimiento logístico:</strong></li></ul>
<h3>🤝 Coordinación y comunicación</h3><ul><li><strong>👥 Responsables de coordinación logística:</strong></li><li><strong>📞 Mecanismos de comunicación y validación:</strong></li><li><strong>📍 Puntos de contacto durante la entrega:</strong></li></ul>
</section>
<section><h2>📚 Fuente de información</h2><table><thead><tr><th>#</th><th>Documento</th><th>Sección / Numeral</th><th>Página</th><th>Descripción breve</th></tr></thead><tbody><tr><td>1</td><td></td><td></td><td></td><td></td></tr></tbody></table></section>
<section><h2>🧩 Observaciones finales</h2><ul><li>⚠️ ...</li></ul></section>
</article>
TXT;

        $technicalStructure = <<<'TXT'
Estructura obligatoria para REPORTE TÉCNICO DE SOPORTE Y PUESTA EN MARCHA:
<article class="jrt-report-doc">
<h1>🧾 Reporte Técnico de Soporte y Puesta en Marcha</h1>
<section><h2>🔹 Resumen Ejecutivo</h2><p>Explica instalación, mantenimiento, soporte, capacitación, servicio postventa, normativas técnicas y riesgos de cumplimiento.</p></section>
<section><h2>🗂️ Detalle por Categoría</h2>
<h3>⚙️ Instalación y puesta en marcha</h3><ul><li><strong>🏗️ Requisitos previos del sitio:</strong></li><li><strong>⚡ Conexiones o infraestructura necesaria:</strong></li><li><strong>🧾 Pruebas o verificaciones iniciales:</strong></li><li><strong>📅 Tiempo estimado de instalación:</strong></li></ul>
<h3>🧩 Puesta a punto y validación técnica</h3><ul><li><strong>🧪 Pruebas funcionales y calibraciones:</strong></li><li><strong>📋 Protocolos o formatos exigidos:</strong></li><li><strong>🧠 Responsables técnicos designados:</strong></li><li><strong>🗓️ Fechas o hitos técnicos:</strong></li></ul>
<h3>🧰 Mantenimiento preventivo</h3><ul><li><strong>🔁 Frecuencia o calendario:</strong></li><li><strong>⚙️ Alcance del servicio:</strong></li><li><strong>🧍‍♂️ Responsable:</strong></li><li><strong>🧾 Reportes o evidencias requeridas:</strong></li></ul>
<h3>🛠️ Mantenimiento correctivo</h3><ul><li><strong>⏱️ Tiempo máximo de respuesta:</strong></li><li><strong>🧰 Tipo de soporte:</strong></li><li><strong>💸 Costos o cobertura de garantía:</strong></li><li><strong>⚠️ Penalizaciones por retraso o incumplimiento:</strong></li></ul>
<h3>👩‍🏫 Capacitaciones</h3><ul><li><strong>🧠 Tipo de capacitación:</strong></li><li><strong>📍 Lugar o modalidad:</strong></li><li><strong>⏰ Duración:</strong></li><li><strong>🧾 Constancias o evaluaciones requeridas:</strong></li></ul>
<h3>📞 Centro de soporte y atención al cliente</h3><ul><li><strong>☎️ Teléfonos o correos de contacto:</strong></li><li><strong>🕒 Horario de atención:</strong></li><li><strong>💬 Niveles de servicio (SLA):</strong></li><li><strong>🤝 Responsables o áreas de contacto:</strong></li></ul>
<h3>📜 Documentación técnica</h3><ul><li><strong>📘 Manuales o guías de operación:</strong></li><li><strong>🧾 Certificados o reportes de calibración:</strong></li><li><strong>🗂️ Formatos de servicio o mantenimiento:</strong></li><li><strong>🧱 Entregables técnicos obligatorios:</strong></li></ul>
</section>
<section><h2>📚 Fuente de información</h2><table><thead><tr><th>#</th><th>Documento</th><th>Sección / Numeral</th><th>Página</th><th>Descripción breve</th></tr></thead><tbody><tr><td>1</td><td></td><td></td><td></td><td></td></tr></tbody></table></section>
<section><h2>🧩 Observaciones finales</h2><ul><li>⚠️ ...</li></ul></section>
<p>Este reporte compila la información necesaria para el seguimiento y cumplimiento adecuado de los requerimientos técnicos estipulados en la licitación.</p>
</article>
TXT;

        $structure = match ($type) {
            'finance' => $financialStructure,
            'logistics' => $logisticsStructure,
            'technical' => $technicalStructure,
            default => $financialStructure,
        };

        return <<<PROMPT
Eres un consultor experto en licitaciones públicas mexicanas. Genera el documento HTML editable titulado: {$title} para el proyecto "{$projectName}".

REGLAS OBLIGATORIAS:
1. Usa únicamente la información disponible en datos estructurados, checklist y textos/documentos del proyecto.
2. No inventes datos. Cuando no exista información, escribe: ⚠️ Información no explícita en los documentos revisados.
3. Respeta exactamente las secciones y categorías indicadas. No omitas categorías aunque falte información.
4. Mantén el estilo ejecutivo, claro, profesional y útil para toma de decisiones.
5. Devuelve SOLO HTML, sin markdown ni bloques de código.
6. Incluye tabla de fuentes con documento, sección/numeral, página y descripción breve cuando puedas inferirlo; si no, deja claro que no se especifica.

{$structure}

=== DATOS ESTRUCTURADOS ===
{$structured}

=== CHECKLIST RELACIONAL ===
{$checklistJson}

=== DOCUMENTOS Y TEXTO EXTRAÍDO ===
{$documentsJson}
PROMPT;
    }

    private function buildSpecializedReportFallbackHtml(Project $project, string $type): string
    {
        $title = e($this->reportTitleForType($type));
        $projectName = e($project->name);

        $categoryHtml = match ($type) {
            'finance' => '<h3>💵 Condiciones de pago</h3><ul><li><strong>💰 Plazo de pago establecido:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧾 Modalidad de pago:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📅 Condiciones de entrega:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>💳 Moneda y método de pago:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🧾 Facturación</h3><ul><li><strong>🏦 Requisitos de facturación:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🗓️ Tiempos de entrega y validación de facturas:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📋 Documentos complementarios exigidos:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⚙️ Procedimiento de revisión o autorización:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>📑 Garantías, fianzas y seguros</h3><ul><li><strong>🛡️ Tipo de pólizas requeridas:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>💵 Porcentajes o montos exigidos:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🗓️ Vigencia o plazo de cobertura:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📄 Condiciones para liberación o devolución:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>💳 Anticipos y retenciones</h3><ul><li><strong>💸 Porcentaje o monto del anticipo:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⚠️ Retenciones aplicables:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>📈 Requisitos financieros</h3><ul><li><strong>📊 Documentos solicitados:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🏦 Criterios de capacidad económica:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>📊 Penalizaciones y deducciones</h3><ul><li><strong>⚠️ Multas por atraso o incumplimiento:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>💰 Deducciones automáticas o ajustes:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🏦 Cuentas bancarias y comprobaciones</h3><ul><li><strong>💳 Cuentas destino y mecanismos de pago:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧾 Documentación de soporte:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul>',
            'logistics' => '<h3>📦 Entregas y recepción</h3><ul><li><strong>🏠 Lugar(es) de entrega:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⏰ Horarios o ventanas:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📋 Condiciones específicas:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧾 Documentos requeridos en la entrega:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>⏱️ Plazos y tiempos</h3><ul><li><strong>📅 Fechas límites o cronogramas:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🚚 Transporte y distribución</h3><ul><li><strong>🚛 Tipo de transporte solicitado:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧴 Condiciones de embalaje y etiquetado:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⚠️ Penalizaciones o sanciones por retraso:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🏢 Almacenamiento y condiciones</h3><ul><li><strong>🌡️ Condiciones especiales:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🏗️ Infraestructura o espacios requeridos:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>📄 Documentación logística</h3><ul><li><strong>📜 Remisiones, guías o manifiestos exigidos:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🖋️ Requisitos de firma o validación:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>⚠️ Riesgos y penalizaciones</h3><ul><li><strong>💸 Sanciones económicas o administrativas:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🔄 Devoluciones o reemplazos:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🤝 Coordinación y comunicación</h3><ul><li><strong>👥 Responsables de coordinación logística:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📞 Mecanismos de comunicación y validación:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul>',
            'technical' => '<h3>⚙️ Instalación y puesta en marcha</h3><ul><li><strong>🏗️ Requisitos previos del sitio:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⚡ Conexiones o infraestructura necesaria:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧾 Pruebas o verificaciones iniciales:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📅 Tiempo estimado de instalación:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🧩 Puesta a punto y validación técnica</h3><ul><li><strong>🧪 Pruebas funcionales y calibraciones:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📋 Protocolos o formatos exigidos:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧠 Responsables técnicos designados:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🧰 Mantenimiento preventivo</h3><ul><li><strong>🔁 Frecuencia o calendario:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⚙️ Alcance del servicio:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>🛠️ Mantenimiento correctivo</h3><ul><li><strong>⏱️ Tiempo máximo de respuesta:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧰 Tipo de soporte:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⚠️ Penalizaciones por retraso o incumplimiento:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>👩‍🏫 Capacitaciones</h3><ul><li><strong>🧠 Tipo de capacitación:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>📍 Lugar o modalidad:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>⏰ Duración:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>📞 Centro de soporte y atención al cliente</h3><ul><li><strong>☎️ Teléfonos o correos de contacto:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>💬 Niveles de servicio (SLA):</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul><h3>📜 Documentación técnica</h3><ul><li><strong>📘 Manuales o guías de operación:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧾 Certificados o reportes de calibración:</strong> ⚠️ Información no explícita en los documentos revisados.</li><li><strong>🧱 Entregables técnicos obligatorios:</strong> ⚠️ Información no explícita en los documentos revisados.</li></ul>',
            default => '',
        };

        return '<article class="jrt-report-doc"><h1>' . $title . '</h1><section><h2>🔹 Resumen Ejecutivo</h2><p>Reporte generado para el proyecto ' . $projectName . '. No se pudo completar el análisis automático, por lo que se muestra la estructura base para edición.</p></section><section><h2>🗂️ Detalle por Categoría</h2>' . $categoryHtml . '</section><section><h2>📚 Fuente de información</h2><table><thead><tr><th>#</th><th>Documento</th><th>Sección / Numeral</th><th>Página</th><th>Descripción breve</th></tr></thead><tbody><tr><td>1</td><td>Documentos del proyecto</td><td>Sin sección específica</td><td>—</td><td>Información pendiente de extracción o validación.</td></tr></tbody></table></section><section><h2>🧩 Observaciones finales</h2><ul><li>⚠️ Requiere validación manual de fuentes y numerales.</li><li>⚠️ Completar los datos marcados como no explícitos si se encuentran en anexos o contrato.</li></ul></section></article>';
    }

    public function generateReport(Request $request, Project $project, OpenAiStructurerService $ai)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        try {
            $type = $this->normalizeReportType($request->input('report_type', 'analysis'));
            $title = $request->input('report_title') ?: $this->reportTitleForType($type);

            if ($request->input('action') === 'save') {
                $content = $request->input('report_content', $request->input('draft_content', ''));
                $this->saveProjectReport($project, $type, $content, $title);

                return response()->json([
                    'ok' => true,
                    'saved_at' => now()->format('H:i:s'),
                ]);
            }

            $project->loadMissing(['documents']);
            $checklist = $this->projectChecklistReportArray($project);
            $prompt = $this->buildSpecializedReportPrompt($project, $checklist, $type);
            $html = null;

            try {
                $messages = [
                    ['role' => 'system', 'content' => 'Eres un generador de reportes HTML profesionales para licitaciones públicas mexicanas. Debes respetar exactamente la estructura solicitada para el tipo de reporte.'],
                    ['role' => 'user', 'content' => $prompt],
                ];

                $html = trim((string) $ai->chatRaw($messages));
                $html = preg_replace('/^```html\s*/i', '', $html);
                $html = preg_replace('/^```\s*/', '', $html);
                $html = preg_replace('/```$/', '', trim($html));
            } catch (\Throwable $aiError) {
                Log::warning('Specialized report AI generation failed; using fallback report builder', [
                    'project_id' => $project->id,
                    'report_type' => $type,
                    'error' => $aiError->getMessage(),
                ]);
            }

            if (!$html || trim(strip_tags($html)) === '') {
                $html = $type === 'analysis'
                    ? $this->buildExecutiveReportFallbackHtml($project, $checklist)
                    : $this->buildSpecializedReportFallbackHtml($project, $type);
            }

            $this->saveProjectReport($project, $type, $html, $title);

            return response()->json([
                'ok' => true,
                'html' => $html,
                'report_type' => $type,
                'saved_at' => now()->format('H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Report generation failed', [
                'project_id' => $project->id,
                'err' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }




    /* ============================================================
     |  ETIQUETAS DEL PROYECTO
     |  - Guarda labels como array JSON en projects.labels
     * ============================================================ */
    public function updateLabels(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $data = $request->validate([
            'labels' => ['nullable', 'array', 'max:30'],
            'labels.*' => ['nullable', 'string', 'max:50'],
            'label_styles' => ['nullable', 'array'],
            'label_styles.*.bg' => ['nullable', 'string', 'max:30'],
            'label_styles.*.border' => ['nullable', 'string', 'max:30'],
            'label_styles.*.text' => ['nullable', 'string', 'max:30'],
        ]);

        $labels = collect($data['labels'] ?? [])
            ->map(fn ($label) => trim(preg_replace('/\s+/', ' ', (string) $label)))
            ->filter(fn ($label) => $label !== '')
            ->unique(fn ($label) => mb_strtolower($label, 'UTF-8'))
            ->take(30)
            ->values()
            ->all();

        $incomingStyles = collect($data['label_styles'] ?? []);
        $existingStyles = collect($project->label_styles ?? []);

        $labelStyles = collect($labels)->mapWithKeys(function ($label) use ($incomingStyles, $existingStyles) {
            $lower = mb_strtolower($label, 'UTF-8');
            $style = $incomingStyles->get($label)
                ?? $incomingStyles->get($lower)
                ?? $existingStyles->get($label)
                ?? $existingStyles->get($lower)
                ?? [];

            return [$label => [
                'bg' => $style['bg'] ?? '#ffebeb',
                'border' => $style['border'] ?? '#ffcaca',
                'text' => $style['text'] ?? '#ff4a4a',
            ]];
        })->all();

        $project->labels = $labels;
        $project->label_styles = $labelStyles;
        $project->save();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Etiquetas actualizadas correctamente.',
                'labels' => $labels,
                'label_styles' => $labelStyles,
            ]);
        }

        return back()->with('success', 'Etiquetas actualizadas correctamente.');
    }

    /* ============================================================
     |  FAVORITO DEL PROYECTO
     * ============================================================ */
    public function updateFavorite(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $data = $request->validate([
            'favorite' => ['required', 'boolean'],
        ]);

        $project->favorite = (bool) $data['favorite'];
        $project->save();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Favorito actualizado correctamente.',
                'favorite' => (bool) $project->favorite,
            ]);
        }

        return back()->with('success', 'Favorito actualizado correctamente.');
    }

    /* ============================================================
     |  WORKFLOW STATUS DEL PROYECTO
     |  - No reemplaza status (processing/ready/error)
     |  - Guarda la etapa comercial/operativa del proyecto
     * ============================================================ */
    private function workflowStatuses(): array
    {
        return [
            'analisis_bases'     => ['label' => 'Análisis de Bases',     'color' => 'blue'],
            'revision'           => ['label' => 'Revisión',              'color' => 'warning'],
            'participa'          => ['label' => 'Participa',             'color' => 'success'],
            'junta_aclaraciones' => ['label' => 'Junta de Aclaraciones', 'color' => 'success-soft'],
            'armado_propuesta'   => ['label' => 'Armado de Propuesta',   'color' => 'success-soft'],
            'entrega'            => ['label' => 'Entrega',               'color' => 'success-soft'],
            'no_participa'       => ['label' => 'No participa',          'color' => 'danger'],
            'ganado'             => ['label' => 'Ganado',                'color' => 'purple'],
            'perdido'            => ['label' => 'Perdido',               'color' => 'gray'],
            'desierta'           => ['label' => 'Desierta',              'color' => 'slate'],
        ];
    }

    public function updateWorkflowStatus(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $allowed = array_keys($this->workflowStatuses());

        $data = $request->validate([
            'workflow_status' => ['required', 'string', 'in:' . implode(',', $allowed)],
        ]);

        $newStatus = $data['workflow_status'];

        $project->workflow_status = $newStatus;

        /*
        |--------------------------------------------------------------------------
        | Reversión automática del dictamen de No participa
        |--------------------------------------------------------------------------
        | Si el usuario vuelve a elegir Revisión, Participa, Junta de Aclaraciones,
        | Armado de Propuesta, Entrega, Ganado, Perdido o Desierta, se limpian los
        | campos reales del dictamen anterior.
        */
        if ($newStatus !== 'no_participa') {
            $project->no_participa_reason = null;
            $project->no_participa_confirmed_at = null;
            $project->no_participa_confirmed_by = null;
        }

        $project->save();

        $meta = $this->workflowStatuses()[$project->workflow_status] ?? $this->workflowStatuses()['analisis_bases'];

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Estado actualizado correctamente.',
                'project' => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'workflow_status' => $project->workflow_status,
                    'workflow_status_label' => $meta['label'],
                    'workflow_status_color' => $meta['color'],
                    'no_participa_reason' => $project->no_participa_reason,
                    'no_participa_confirmed_at' => optional($project->no_participa_confirmed_at)->format('Y-m-d H:i:s'),
                    'no_participa_confirmed_by' => $project->no_participa_confirmed_by,
                ],
            ]);
        }

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    public function updateNoParticipaReason(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:10000'],
            'confirmed' => ['nullable', 'boolean'],
        ]);

        $project->workflow_status = 'no_participa';
        $project->no_participa_reason = trim((string) $data['reason']);

        if ($request->boolean('confirmed')) {
            $project->no_participa_confirmed_at = now();
            $project->no_participa_confirmed_by = Auth::id();
        } else {
            $project->no_participa_confirmed_at = null;
            $project->no_participa_confirmed_by = null;
        }

        $project->save();

        $meta = $this->workflowStatuses()[$project->workflow_status] ?? $this->workflowStatuses()['no_participa'];

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Motivo guardado correctamente.',
                'project' => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'workflow_status' => $project->workflow_status,
                    'workflow_status_label' => $meta['label'],
                    'workflow_status_color' => $meta['color'],
                    'no_participa_reason' => $project->no_participa_reason,
                    'no_participa_confirmed_at' => optional($project->no_participa_confirmed_at)->format('Y-m-d H:i:s'),
                    'no_participa_confirmed_by' => $project->no_participa_confirmed_by,
                ],
            ]);
        }

        return back()->with('success', 'Motivo guardado correctamente.');
    }


}