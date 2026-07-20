<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateClarificationsReportJob;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectChatMessage;
use App\Models\ProjectChecklistAttachment;
use App\Models\ProjectChecklistItem;
use App\Models\ProjectChecklistNote;
use App\Models\User;
use App\Services\PythonProjectProcessor;
use App\Services\OpenAiStructurerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use ZipArchive;

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
        $showArchived = $request->boolean('archived');

        $projects = Project::with('assignee')
            ->where('user_id', Auth::id())
            ->when(Schema::hasColumn('projects', 'archived_at'), function ($query) use ($showArchived) {
                if ($showArchived) {
                    $query->whereNotNull('archived_at');
                } else {
                    $query->whereNull('archived_at');
                }
            })
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

        $assignableUsers = User::query()
            ->select(['id', 'name', 'email', 'avatar_path', 'status'])
            ->when(Schema::hasColumn('users', 'status'), function ($query) {
                $query->where(function ($sub) {
                    $sub->whereNull('status')
                        ->orWhere('status', 'approved');
                });
            })
            ->orderBy('name')
            ->get()
            ->map(function (User $user) {
                $initial = Str::upper(Str::substr(trim((string) $user->name), 0, 1));
                if ($initial === '') {
                    $initial = Str::upper(Str::substr(trim((string) $user->email), 0, 1));
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name ?: $user->email,
                    'email' => $user->email,
                    'initial' => $initial ?: 'U',
                    'avatar_url' => $user->avatar_url,
                ];
            })
            ->values();

        return view('projects.index', compact('projects', 'columns', 'openColumns', 'viewMode', 'assignableUsers'));
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
                $result = $this->normalizeProcessorResult(
                    $processor->process($project, $paths)
                );

                if (!$result['ok']) {
                    throw new \RuntimeException(
                        $result['error'] ?: 'El procesador no pudo analizar los documentos.'
                    );
                }

                $this->persistProcessorDocuments(
                    $project->fresh('documents'),
                    $result['documents']
                );

                $structured = $result['structured_data'];
                $newChecklist = $this->processorChecklist($structured, $project);

                $project->structured_data = $structured;
                $project->checklist = $newChecklist;
                $project->status = 'ready';
                $project->error_message = null;
                $project->save();

                if (!empty($newChecklist)) {
                    $this->syncChecklistItemsFromArray($project, $newChecklist, true);
                }

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

        $notes = Schema::hasTable('project_notes')
            ? DB::table('project_notes')
                ->leftJoin('users', 'users.id', '=', 'project_notes.user_id')
                ->where('project_notes.project_id', $project->id)
                ->when(Schema::hasColumn('project_notes', 'archived_at'), fn ($query) => $query->whereNull('project_notes.archived_at'))
                ->orderByDesc(Schema::hasColumn('project_notes', 'is_pinned') ? 'project_notes.is_pinned' : 'project_notes.created_at')
                ->orderByDesc('project_notes.created_at')
                ->select([
                    'project_notes.id',
                    'project_notes.content',
                    'project_notes.created_at',
                    'project_notes.updated_at',
                    'project_notes.user_id',
                    DB::raw(Schema::hasColumn('project_notes', 'is_pinned') ? 'project_notes.is_pinned as is_pinned' : '0 as is_pinned'),
                    DB::raw('users.name as user_name'),
                    DB::raw('users.email as user_email'),
                ])
                ->get()
            : collect();

        $tasks = Schema::hasTable('project_tasks')
            ? DB::table('project_tasks')
                ->leftJoin('users', 'users.id', '=', 'project_tasks.assigned_to')
                ->where('project_tasks.project_id', $project->id)
                ->when(Schema::hasColumn('project_tasks', 'archived_at'), fn ($query) => $query->whereNull('project_tasks.archived_at'))
                ->orderByDesc(Schema::hasColumn('project_tasks', 'is_pinned') ? 'project_tasks.is_pinned' : 'project_tasks.created_at')
                ->orderBy('project_tasks.completed')
                ->orderByDesc('project_tasks.created_at')
                ->select([
                    'project_tasks.id',
                    'project_tasks.title',
                    'project_tasks.priority',
                    'project_tasks.completed',
                    'project_tasks.assigned_to',
                    'project_tasks.due_date',
                    'project_tasks.created_at',
                    'project_tasks.updated_at',
                    DB::raw(Schema::hasColumn('project_tasks', 'is_pinned') ? 'project_tasks.is_pinned as is_pinned' : '0 as is_pinned'),
                    DB::raw('users.name as assigned_name'),
                    DB::raw('users.email as assigned_email'),
                ])
                ->get()
            : collect();

        $dashboardUsers = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('projects.dashboard', compact('project', 'notes', 'tasks', 'dashboardUsers'));
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

        // Recupera automáticamente el checklist de proyectos que fueron
        // analizados antes de existir la tabla relacional.
        $this->ensureChecklistItemsExist($project);

        $project->load([
            'checklistItems.notes.user',
            'checklistItems.attachments',
            'checklistItems.responsible',
            'checklistItems.reviewer',
            'checklistItems.sourceDocument',
        ]);

        $documentLibrary = $this->projectDocumentLibrary($project);
        $checklist = $this->checklistPayload($project)['items'];

        return view('projects.analisis', [
            'project' => $project,
            'documentLibrary' => $documentLibrary,
            'checklist' => $checklist,
        ]);
    }

    /**
     * Normaliza la respuesta del procesador Python.
     *
     * El script puede devolver `structured` o `structured_data`. Esta capa evita
     * que el proyecto quede sin ficha, checklist o texto por una diferencia de nombre.
     */
    private function normalizeProcessorResult(array $result): array
    {
        $structured = $result['structured_data']
            ?? $result['structured']
            ?? data_get($result, 'data.structured_data')
            ?? data_get($result, 'data.structured')
            ?? [];

        if (!is_array($structured)) {
            $structured = [];
        }

        $documents = $result['documents']
            ?? data_get($result, 'data.documents')
            ?? [];

        if (!is_array($documents)) {
            $documents = [];
        }

        return [
            'ok' => (bool) ($result['ok'] ?? true),
            'error' => $result['error'] ?? null,
            'structured_data' => $structured,
            'documents' => $documents,
            'raw_text_combined' => is_string($result['raw_text_combined'] ?? null)
                ? $result['raw_text_combined']
                : '',
        ];
    }

    /**
     * Obtiene el checklist desde cualquiera de las rutas históricas usadas por
     * el procesador o por proyectos antiguos.
     */
    private function processorChecklist(array $structured, ?Project $project = null): array
    {
        $candidates = [
            data_get($structured, 'checklist_sugerido'),
            data_get($structured, 'checklist'),
            data_get($structured, 'analisis.checklist_sugerido'),
            data_get($structured, 'analisis.checklist'),
        ];

        if ($project) {
            $candidates[] = $project->checklist;
            $candidates[] = data_get($project->structured_data ?? [], 'checklist_sugerido');
            $candidates[] = data_get($project->structured_data ?? [], 'checklist');
        }

        foreach ($candidates as $candidate) {
            if (is_array($candidate) && !empty($candidate)) {
                return array_values(array_filter($candidate, 'is_array'));
            }
        }

        return [];
    }

    /**
     * Guarda en cada ProjectDocument el texto real extraído por Azure/Python.
     */
    private function persistProcessorDocuments(Project $project, array $processorDocuments): void
    {
        if (empty($processorDocuments)) {
            return;
        }

        $documentCollection = $project->documents()->get();

        $documents = $documentCollection->keyBy(
            fn (ProjectDocument $document) => mb_strtolower(trim(basename((string) $document->filename)))
        );

        foreach ($processorDocuments as $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $filename = trim((string) ($payload['file'] ?? $payload['filename'] ?? ''));
            if ($filename === '') {
                continue;
            }

            /** @var ProjectDocument|null $document */
            $document = $documents->get(mb_strtolower(basename($filename)));

            if (!$document) {
                $document = $documentCollection->first(function (ProjectDocument $candidate) use ($filename) {
                    return mb_strtolower(basename((string) $candidate->file_path))
                        === mb_strtolower(basename($filename));
                });
            }

            if (!$document) {
                continue;
            }

            $status = (string) ($payload['status'] ?? 'ok');

            $document->extracted_text = $payload['extracted_text']
                ?? $payload['text']
                ?? $document->extracted_text;

            $raw = $payload['extracted_raw'] ?? [];
            if (!is_array($raw)) {
                $raw = ['raw' => $raw];
            }

            $raw['text_length'] = $payload['text_length'] ?? mb_strlen((string) $document->extracted_text);
            $raw['raw_preview'] = $payload['raw_preview'] ?? null;
            $raw['processor_status'] = $status;
            $raw['processor_error'] = $payload['error'] ?? null;

            $document->extracted_raw = $raw;
            $document->status = $status === 'ok' ? 'procesado' : ($status === 'empty' ? 'sin_contenido' : 'error');
            $document->processed_at = now();
            $document->save();
        }
    }

    /**
     * Une el texto extraído de todos los documentos, conservando fuente y páginas.
     */
    private function projectGroundedDocumentText(Project $project, int $maxCharacters = 140000): string
    {
        $project->loadMissing('documents');

        $parts = [];

        foreach ($project->documents as $document) {
            $text = trim((string) $document->extracted_text);

            if ($text === '') {
                $raw = is_array($document->extracted_raw) ? $document->extracted_raw : [];

                foreach (['text', 'content', 'raw_text', 'raw_preview', 'summary', 'resumen'] as $key) {
                    $candidate = $raw[$key] ?? null;

                    if (is_string($candidate) && trim($candidate) !== '') {
                        $text = trim($candidate);
                        break;
                    }
                }
            }

            if ($text === '') {
                continue;
            }

            $parts[] = "--- DOCUMENTO: {$document->filename} ---\n{$text}";
        }

        return mb_substr(implode("\n\n", $parts), 0, $maxCharacters);
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
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        $project->loadMissing('documents');

        $documentText = $this->projectGroundedDocumentText($project);

        if ($documentText === '') {
            $structuredFallback = json_encode(
                is_array($project->structured_data) ? $project->structured_data : [],
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            );

            if (!$structuredFallback || trim($structuredFallback) === '[]') {
                return response()->json([
                    'ok' => false,
                    'message' => 'Los documentos todavía no tienen texto extraído. Ejecuta el reanálisis del proyecto.',
                ], 422);
            }

            $documentText = "--- DATOS RECUPERADOS DEL ANÁLISIS DEL PROYECTO ---\n"
                . $structuredFallback;
        }

        ProjectChatMessage::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'role' => 'user',
            'content' => $data['message'],
        ]);

        $history = ProjectChatMessage::where('project_id', $project->id)
            ->orderByDesc('id')
            ->take(12)
            ->get()
            ->reverse()
            ->values();

        $structured = json_encode(
            $project->structured_data ?? [],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        $systemContext = <<<PROMPT
Eres SAM, un asistente experto en licitaciones públicas mexicanas.

REGLAS OBLIGATORIAS:
1. Responde EXCLUSIVAMENTE con base en los DOCUMENTOS DEL PROYECTO incluidos abajo.
2. Los datos estructurados son un índice auxiliar; cuando exista conflicto, manda el texto literal del documento.
3. No uses conocimiento general para completar datos faltantes.
4. No inventes números, fechas, requisitos, cantidades, marcas, instituciones, sanciones ni condiciones.
5. Si la respuesta no aparece en los documentos, responde exactamente:
   "No encontré esa información en los documentos cargados en este proyecto."
6. Cuando encuentres la respuesta, menciona al final:
   Fuente: nombre_exacto_del_archivo, página X.
7. Usa párrafos breves. Solo usa tablas cuando el usuario pida comparar o listar varios elementos.
8. No uses emojis ni caracteres decorativos.

PROYECTO:
{$project->name}

DATOS ESTRUCTURADOS:
{$structured}

DOCUMENTOS DEL PROYECTO:
{$documentText}
PROMPT;

        $messages = [
            ['role' => 'system', 'content' => $systemContext],
        ];

        foreach ($history as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        try {
            $reply = trim((string) $ai->chatRaw($messages));
        } catch (\Throwable $e) {
            Log::error('Project grounded chat failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'No se pudo procesar la pregunta en este momento.',
            ], 500);
        }

        if ($reply === '') {
            $reply = 'No encontré esa información en los documentos cargados en este proyecto.';
        }

        $assistant = ProjectChatMessage::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return response()->json([
            'ok' => true,
            'assistant_message' => [
                'content' => $assistant->content,
                'time' => $assistant->created_at->format('H:i'),
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

            $result = $this->normalizeProcessorResult(
                $processor->process($project, $paths)
            );

            if (!$result['ok']) {
                throw new \RuntimeException(
                    $result['error'] ?: 'No se pudo reanalizar el checklist.'
                );
            }

            $this->persistProcessorDocuments(
                $project->fresh('documents'),
                $result['documents']
            );

            $structured = $result['structured_data'];
            $newChecklist = $this->processorChecklist($structured, $project);

            if (!empty($structured)) {
                $sd = is_array($project->structured_data) ? $project->structured_data : [];
                $project->structured_data = array_replace_recursive($sd, $structured);
            }

            $project->checklist = $newChecklist;
            $project->status = 'ready';
            $project->error_message = null;
            $project->save();

            if (!empty($newChecklist)) {
                $this->syncChecklistItemsFromArray($project, $newChecklist, true);
            }

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

        $legacy = $this->processorChecklist(
            is_array($project->structured_data) ? $project->structured_data : [],
            $project
        );

        if (!empty($legacy)) {
            $this->syncChecklistItemsFromArray($project, $legacy, false);
        }
    }

    private function checklistTextValue($value, string $fallback = ''): string
    {
        if (is_null($value)) {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        if (is_scalar($value)) {
            $text = trim((string) $value);
            return $text !== '' ? $text : $fallback;
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        if (is_array($value)) {
            foreach (['respuesta', 'valor', 'texto', 'descripcion', 'nombre', 'titulo', 'label', 'content'] as $key) {
                if (array_key_exists($key, $value)) {
                    $candidate = $this->checklistTextValue($value[$key]);
                    if ($candidate !== '') {
                        return $candidate;
                    }
                }
            }

            $parts = [];
            foreach ($value as $item) {
                $candidate = $this->checklistTextValue($item);
                if ($candidate !== '') {
                    $parts[] = $candidate;
                }
            }

            $text = trim(implode(' ', array_unique($parts)));
            return $text !== '' ? $text : $fallback;
        }

        return $fallback;
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

            $requirement = $this->checklistTextValue(
                $raw['requisito'] ?? $raw['item'] ?? $raw['text'] ?? 'Sin nombre',
                'Sin nombre'
            );
            $key = mb_strtolower(trim($requirement));

            $item = $existing->get($key) ?: new ProjectChecklistItem([
                'project_id' => $project->id,
                'position'   => $position,
            ]);

            $this->fillChecklistItem($item, [
                'requisito'             => $requirement,
                'descripcion'           => $this->checklistTextValue($raw['descripcion'] ?? ''),
                'criterio_cumplimiento' => $this->checklistTextValue($raw['criterio_cumplimiento'] ?? ''),
                'formato'               => $this->checklistTextValue($raw['formato'] ?? 'No aplica', 'No aplica'),
                'categoria'             => $this->checklistTextValue($raw['categoria'] ?? 'Legal-Administrativo', 'Legal-Administrativo'),
                'aplicabilidad'         => $this->checklistTextValue($raw['aplicabilidad'] ?? 'Único', 'Único'),
                'obligatorio'           => $this->checklistTextValue($raw['obligatorio'] ?? 'Sí', 'Sí'),
                'cumplimiento'          => $this->checklistTextValue($raw['cumplimiento'] ?? '-', '-'),
                'status'                => $this->checklistTextValue($raw['status'] ?? 'Pendiente', 'Pendiente'),
                'prioridad'             => $this->checklistTextValue($raw['prioridad'] ?? 'Media', 'Media'),
                'fecha_limite'          => $raw['fecha_limite'] ?? null,
                'fuente'                => $this->checklistTextValue($raw['fuente'] ?? ''),
                'pagina'                => $raw['pagina'] ?? null,
                'cita'                  => $this->checklistTextValue($raw['cita'] ?? $raw['evidencia'] ?? $raw['fragmento'] ?? ''),
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
        $structured = json_encode(
            is_array($project->structured_data) ? $project->structured_data : [],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        $checklistJson = json_encode(
            $checklist,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        /*
         * El resumen ejecutivo debe sustentarse en el contenido real de los
         * documentos y no solamente en structured_data. Esto permite recuperar
         * citas, plazos, certificaciones, contradicciones y fuentes concretas.
         */
        $documentsText = $this->projectGroundedDocumentText($project, 125000);

        if (trim($documentsText) === '') {
            $documentsText = 'No existe texto documental extraído. Usa únicamente los datos estructurados y el checklist, sin inventar información.';
        }

        $name = $project->name;

        return <<<PROMPT
Eres un consultor senior especializado en licitaciones públicas mexicanas, análisis contractual, cumplimiento documental, operación logística y viabilidad comercial.

Genera un RESUMEN EJECUTIVO PROFUNDO, útil para que la dirección de una empresa decida si participa o no en el proyecto "{$name}".

FUENTES Y VERACIDAD
1. Usa primero el texto literal de los DOCUMENTOS DEL PROYECTO.
2. Usa DATOS ESTRUCTURADOS y CHECKLIST como apoyo y para cruzar cumplimiento.
3. No inventes datos, fechas, porcentajes, normas, marcas, requisitos, páginas, archivos, conclusiones ni citas.
4. Cuando exista una contradicción, indícala claramente y menciona los documentos involucrados.
5. Cuando un dato no esté disponible, escribe de forma ejecutiva: "No se encontró evidencia suficiente en los documentos revisados".
6. No escribas `null`, rutas internas, nombres de recursos técnicos ni archivos de identidad como header.png, footer.png, storage/, public/ o informacion_de_la_empresa/.
7. No confundas documentos corporativos internos con documentos de las bases de la licitación.
8. Cada cita debe conservar el sentido literal del documento. No fabriques páginas.
9. No uses emojis, iconos decorativos, markdown, bloques de código ni texto fuera del HTML.
10. El resultado debe ser HTML limpio y editable.

OBJETIVO DEL DOCUMENTO
El resultado debe parecer un dictamen ejecutivo profesional, semejante al siguiente nivel de profundidad:
- explicar el objeto real y su modalidad contractual;
- determinar alineación con capacidad y portafolio usando el checklist disponible;
- señalar alcance operativo y exigencias posteriores a la entrega;
- identificar certificaciones o normas aplicables;
- explicar partidas, conceptos y restricciones técnicas;
- detectar plazos críticos y contradicciones;
- emitir recomendación de participación con justificación;
- presentar riesgos ejecutivos concretos y accionables;
- identificar las fuentes documentales de cada conclusión.

ESTRUCTURA HTML OBLIGATORIA

<article class="pjd-report-doc pjd-executive-report">
  <header class="pjd-report-cover">
    <h1>Resumen Ejecutivo</h1>
    <p>Proyecto: {$name}</p>
  </header>

  <section class="pjd-report-section">
    <h2>Objeto y Dictamen de Alineación</h2>
    <p>Redacta de dos a cuatro párrafos explicando el objeto exacto, convocante, modalidad contractual, número de partidas, forma de suministro, alcance real y cualquier inconsistencia editorial o contractual detectada.</p>

    <ul>
      <li><strong>Alineación con portafolio:</strong> Determina la alineación usando únicamente evidencia disponible en los documentos, datos estructurados y checklist. Distingue entre capacidad confirmada, capacidad no acreditada y falta de información.</li>
      <li><strong>Alcance y exigencia operativa:</strong> Explica cobertura geográfica, cantidades o partidas cuando estén disponibles, garantías, reposiciones, entregas, instalación, soporte, muestras, personal, almacenes u otras obligaciones operativas.</li>
      <li><strong>Señal de viabilidad:</strong> Clasifica como ALTA, MEDIA o BAJA y justifica con cumplimiento documental, capacidad operativa, restricciones técnicas y condiciones económicas.</li>
    </ul>
  </section>

  <section class="pjd-report-section">
    <h2>Certificaciones aplicables</h2>
    <ul>
      <li>Incluye únicamente NOM, NMX, ISO, registros, certificados, autorizaciones o normas expresamente mencionadas en los documentos.</li>
      <li>Para cada norma explica brevemente a qué producto, documento o requisito se aplica cuando exista esa evidencia.</li>
      <li>Si no existen certificaciones detectadas, indícalo expresamente.</li>
    </ul>
  </section>

  <section class="pjd-report-section">
    <h2>Estructura y Alcance del Objeto</h2>
    <p>Describe partidas, lotes, conceptos, modalidad abierta o cerrada, adjudicación, entrega única o parcial y alcance técnico.</p>
    <ul>
      <li><strong>Restricciones técnicas:</strong> Identifica medidas, materiales, colores, capacidades, marcas de referencia, moldes, compatibilidades, contenido nacional, muestras u otros candados.</li>
      <li><strong>Impacto en la participación:</strong> Explica si esas restricciones limitan proveedores, elevan costos o requieren aclaración.</li>
    </ul>

    <h3>Fuentes</h3>
    <ul>
      <li>Lista únicamente nombres reales de documentos usados para esta sección.</li>
    </ul>
  </section>

  <section class="pjd-report-section">
    <h2>Plazos Operativos Críticos</h2>
    <p><strong>Existen plazos críticos:</strong> Sí o No.</p>
    <p>Explica la relación entre publicación, aclaraciones, fallo, entrega, vigencia, pago, garantías, reposiciones y cualquier plazo documental posterior a la adjudicación. Señala contradicciones o márgenes insuficientes.</p>

    <h3>Detalle de plazos</h3>
    <ul>
      <li>
        <strong>Tipo de plazo:</strong> valor exacto.<br>
        <strong>Cita:</strong> fragmento documental breve y fiel.<br>
        <strong>Ubicación:</strong> nombre real del archivo y página o sección únicamente si está disponible.<br>
        <strong>Comentario:</strong> impacto ejecutivo, operativo, financiero o documental.
      </li>
    </ul>

    <h3>Fuentes de plazos</h3>
    <ul>
      <li>Lista únicamente archivos que realmente sustentan los plazos.</li>
    </ul>
  </section>

  <section class="pjd-report-section">
    <h2>Recomendación de Participación</h2>
    <ul>
      <li><strong>Recomendación:</strong> SÍ, SÍ CONDICIONADA o NO.</li>
      <li><strong>Justificación:</strong> Explica los factores determinantes: cumplimiento, documentos faltantes, experiencia, certificaciones, restricciones técnicas, contenido nacional, costos, pago, entrega y riesgo contractual.</li>
      <li><strong>Condiciones para reconsiderar:</strong> Presenta acciones concretas, numeradas y ejecutables. Incluye preguntas para junta de aclaraciones cuando correspondan.</li>
    </ul>
  </section>

  <section class="pjd-report-section">
    <h2>Riesgos Ejecutivos</h2>
    <p><strong>Nivel de riesgo global:</strong> ALTO, MEDIO o BAJO.</p>
    <p>Redacta un análisis integrado de los riesgos de cumplimiento, comerciales, financieros, contractuales, técnicos, documentales y logísticos.</p>

    <ul>
      <li><strong>Riesgo documental:</strong> faltantes y consecuencias.</li>
      <li><strong>Riesgo técnico:</strong> especificaciones, normas, muestras, marcas o compatibilidad.</li>
      <li><strong>Riesgo operativo:</strong> entrega, reposición, cobertura, personal, instalación o soporte.</li>
      <li><strong>Riesgo financiero:</strong> pago, fianzas, penalizaciones, capital de trabajo y vigencia.</li>
      <li><strong>Riesgo contractual:</strong> rescisión, penas, deducciones, garantías y obligaciones posteriores.</li>
    </ul>
  </section>

  <section class="pjd-report-section">
    <h2>Fuentes documentales consultadas</h2>
    <table>
      <thead>
        <tr>
          <th>Documento</th>
          <th>Página o sección</th>
          <th>Información utilizada</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Nombre real del archivo</td>
          <td>Página o sección disponible</td>
          <td>Resumen breve de la evidencia utilizada</td>
        </tr>
      </tbody>
    </table>
  </section>
</article>

CRITERIOS DE REDACCIÓN
- Escribe en español de México.
- Mantén tono directivo, preciso y profesional.
- Evita frases genéricas y repeticiones.
- No copies literalmente grandes bloques: sintetiza y conserva citas breves solo donde aporten evidencia.
- No agregues secciones vacías con datos ficticios.
- No muestres instrucciones, razonamiento interno ni advertencias sobre IA.
- Los encabezados deben coincidir exactamente con la estructura solicitada.
- No agregues logos ni rutas de imágenes; el editor ya aplica la identidad visual.

=== DATOS ESTRUCTURADOS ===
{$structured}

=== CHECKLIST RELACIONAL Y ESTADO DE CUMPLIMIENTO ===
{$checklistJson}

=== TEXTO EXTRAÍDO DE LOS DOCUMENTOS DEL PROYECTO ===
{$documentsText}
PROMPT;
    }

    private function normalizeReportType(?string $type): string
    {
        return match ($type) {
            'finance', 'finanzas', 'financial' => 'finance',
            'logistics', 'logistica', 'logistica_operativa' => 'logistics',
            'technical', 'soporte_tecnico', 'soporte', 'tecnico' => 'technical',
            'clarifications', 'junta', 'junta_aclaraciones', 'aclaraciones', 'ja' => 'clarifications',
            default => 'analysis',
        };
    }

    private function reportTitleForType(string $type): string
    {
        return match ($type) {
            'finance' => 'Reporte Financiero de la Licitación',
            'logistics' => 'Reporte Logístico de la Licitación',
            'technical' => 'Reporte Técnico de Soporte y Puesta en Marcha',
            'clarifications' => 'Junta de Aclaraciones - Preguntas Estratégicas',
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

    private function clarificationTemplateContext(Request $request, Project $project): array
    {
        if (!$request->hasFile('template_file')) {
            return [
                'mode' => 'estandar',
                'filename' => null,
                'stored_path' => null,
                'content' => '',
            ];
        }

        $request->validate([
            'template_file' => ['file', 'mimes:doc,docx,pdf,xls,xlsx', 'max:25600'],
        ]);

        $file = $request->file('template_file');
        $filename = $file->getClientOriginalName();
        $extension = mb_strtolower($file->getClientOriginalExtension(), 'UTF-8');
        $storedPath = $file->store("projects/{$project->id}/templates/junta-aclaraciones", 'public');
        $absolutePath = storage_path('app/public/' . $storedPath);

        $content = match ($extension) {
            'docx' => $this->extractTextFromDocxTemplate($absolutePath),
            'xlsx' => $this->extractTextFromXlsxTemplate($absolutePath),
            'pdf' => $this->extractTextFromPdfTemplate($absolutePath),
            default => '',
        };

        if (trim($content) === '') {
            $content = "No fue posible extraer automáticamente el contenido interno del archivo {$filename}. "
                . "Conserva como mínimo una tabla editable con las columnas del formato y usa el nombre del archivo como referencia.";
        }

        return [
            'mode' => 'formato_especifico',
            'filename' => $filename,
            'stored_path' => $storedPath,
            'content' => Str::limit($content, 6000, '\n[FORMATO RECORTADO]'),
        ];
    }

    private function extractTextFromDocxTemplate(string $path): string
    {
        if (!class_exists(ZipArchive::class) || !is_file($path)) {
            return '';
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if ($xml === '') {
            return '';
        }

        $xml = str_replace(
            ['</w:p>', '</w:tr>', '</w:tc>', '<w:tab/>', '<w:br/>'],
            ["\n", "\n", "\t", "\t", "\n"],
            $xml
        );

        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return trim(preg_replace('/[ \t]+/u', ' ', preg_replace('/\n{3,}/u', "\n\n", $text)));
    }

    private function extractTextFromXlsxTemplate(string $path): string
    {
        if (!class_exists(ZipArchive::class) || !is_file($path)) {
            return '';
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return '';
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml') ?: '';

        if ($sharedXml !== '') {
            $sharedXml = str_replace(['</si>', '</t>'], ["\n", ' '], $sharedXml);
            $sharedStrings = array_values(array_filter(array_map(
                fn ($value) => trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_XML1, 'UTF-8')),
                preg_split('/\n+/u', $sharedXml)
            )));
        }

        $parts = [];

        for ($index = 1; $index <= 12; $index++) {
            $sheetXml = $zip->getFromName("xl/worksheets/sheet{$index}.xml");

            if (!$sheetXml) {
                continue;
            }

            $sheetXml = preg_replace_callback(
                '/<c[^>]*t="s"[^>]*>.*?<v>(\d+)<\/v>.*?<\/c>/s',
                function ($matches) use ($sharedStrings) {
                    return $sharedStrings[(int) $matches[1]] ?? '';
                },
                $sheetXml
            );

            $sheetXml = str_replace(['</row>', '</c>'], ["\n", "\t"], $sheetXml);
            $parts[] = html_entity_decode(strip_tags($sheetXml), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        $zip->close();

        return trim(preg_replace('/[ \t]+/u', ' ', preg_replace('/\n{3,}/u', "\n\n", implode("\n", $parts))));
    }

    private function extractTextFromPdfTemplate(string $path): string
    {
        if (!is_file($path)) {
            return '';
        }

        try {
            $process = new Process(['pdftotext', '-layout', $path, '-']);
            $process->setTimeout(8);
            $process->run();

            if ($process->isSuccessful()) {
                return trim((string) $process->getOutput());
            }
        } catch (\Throwable $error) {
            Log::info('No fue posible extraer el formato PDF con pdftotext.', [
                'path' => $path,
                'error' => $error->getMessage(),
            ]);
        }

        return '';
    }

    private function buildClarificationsReportPrompt(Project $project, array $checklist, array $options = []): string
    {
        $structured = json_encode(
            is_array($project->structured_data) ? $project->structured_data : [],
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        $checklistJson = json_encode(
            $checklist,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        $documentsText = $this->projectGroundedDocumentText($project, 25000);

        if (trim($documentsText) === '') {
            $documentsText = 'No existe texto documental extraído. Usa únicamente datos estructurados y checklist, sin inventar información.';
        }

        $riskLevels = collect($options['risk_levels'] ?? ['alto', 'medio', 'no_cumple'])
            ->map(fn ($value) => mb_strtoupper(trim((string) $value), 'UTF-8'))
            ->filter()
            ->implode(', ');

        $instructions = trim((string) ($options['instructions'] ?? ''));
        $formatMode = trim((string) ($options['format_mode'] ?? 'estandar'));
        $templateFilename = trim((string) ($options['template_filename'] ?? ''));
        $templateContext = trim((string) ($options['template_context'] ?? ''));
        $projectName = $project->name;

        return <<<PROMPT
Eres un especialista senior en contratación pública de México, licitaciones gubernamentales, LAASSP, su Reglamento, Compras MX, CompraNet, formalización contractual, cumplimiento fiscal y seguridad social, garantías, contenido nacional, evaluación legal, técnica, financiera y logística.

Genera un documento profesional titulado "Junta de Aclaraciones - Preguntas Estratégicas" para el proyecto "{$projectName}".

OBJETIVO
Detectar contradicciones, ambigüedades, errores editoriales, requisitos desproporcionados, referencias cruzadas inconsistentes, fechas incompatibles, posibles restricciones a la libre participación, documentos duplicados, causales de desechamiento poco claras, requisitos técnicos cerrados, marcas o especificaciones limitantes, riesgos fiscales, financieros, contractuales, logísticos y cualquier punto que deba aclararse antes de presentar la propuesta.

CONFIGURACIÓN DEL USUARIO
- Formato solicitado: {$formatMode}
- Archivo de formato: {$templateFilename}
- Niveles prioritarios: {$riskLevels}
- Instrucciones adicionales: {$instructions}

REGLAS PARA EL FORMATO ADJUNTO
- Si el modo es formato_especifico, usa la estructura, encabezados, orden de columnas y secciones detectadas en el archivo adjunto.
- Coloca cada pregunta dentro de esa estructura; no agregues una tabla distinta si el formato ya define una.
- Mantén campos vacíos editables cuando el formato los incluya y no exista un dato confirmado.
- No copies contenido de ejemplo del formato como si fuera evidencia de las bases.
- Si el formato no puede interpretarse completamente, conserva al menos sus títulos y encabezados principales, y adapta las preguntas a ellos.

=== ESTRUCTURA EXTRAÍDA DEL FORMATO ADJUNTO ===
{$templateContext}

REGLAS DE VERACIDAD
1. Usa primero el texto literal extraído de las bases, anexos, modelos de contrato y documentos del proyecto.
2. Cruza la información con datos estructurados y checklist.
3. No inventes numerales, páginas, artículos legales, fechas, porcentajes, nombres de archivos ni citas.
4. Si no puedes confirmar la página exacta, usa "Página no identificada"; nunca fabriques una página.
5. Si una referencia legal aparece en los documentos, puedes citarla. Si propones revisar una disposición general, indícala como "Referencia legal sugerida para validación" y no como hecho documental.
6. No incluyas rutas internas, archivos header.png, footer.png, storage/, public/, informacion_de_la_empresa/ ni valores null.
7. No uses emojis, markdown ni bloques de código.
8. Devuelve únicamente HTML limpio y editable.
9. Redacta preguntas claras, respetuosas, concretas y orientadas a obtener una respuesta vinculante de la convocante.
10. Evita preguntas duplicadas. Consolida asuntos equivalentes y separa los que tengan consecuencias distintas.
11. No propongas sustituir requisitos obligatorios sin explicar el fundamento documental y el riesgo que se pretende resolver.
12. Identifica preguntas potencialmente contraproducentes o que podrían endurecer las bases; colócalas en una sección separada como "Preguntas que requieren autorización interna".

ANÁLISIS OBLIGATORIO
Debes revisar, como mínimo:
- carácter nacional o internacional y referencias a tratados;
- plataformas oficiales: Compras MX, CompraNet, RUPC, PROCURA u otras;
- personalidad jurídica, identificación, poderes, RFC, CURP y documentos equivalentes;
- SAT, IMSS e INFONAVIT;
- experiencia, contratos, CFDI, cartas de recomendación y constancias;
- contenido nacional y forma de acreditación;
- especificaciones técnicas, marcas, modelos, medidas, materiales, muestras y normas;
- bienes nuevos, usados, reacondicionados y condiciones de aceptación;
- subcontratación;
- garantías de cumplimiento, vicios ocultos, responsabilidad civil y seguros;
- vigencia, entrega, reposición, pago, facturación y formalización;
- contradicciones entre convocatoria, anexos, checklist, contrato y formatos;
- requisitos que aparecen solo en una sección o con nombres diferentes;
- causales de desechamiento y documentos subsanables;
- errores de año, ejercicio fiscal o fechas imposibles;
- requisitos que podrían limitar la competencia o generar sobrecostos;
- riesgos no cubiertos por las preguntas ya sugeridas.

ESTRUCTURA HTML OBLIGATORIA
<article class="jrt-report-doc jrt-clarifications-report">
  <header>
    <h1>Junta de Aclaraciones - Preguntas Estratégicas</h1>
    <p>Proyecto: {$projectName}</p>
    <p>Documento de trabajo sujeto a revisión jurídica, técnica, comercial y directiva.</p>
  </header>

  <section>
    <h2>Resumen Ejecutivo de Hallazgos</h2>
    <p>Explica los principales riesgos, contradicciones y temas que justifican presentar preguntas.</p>
    <ul>
      <li><strong>Total de preguntas propuestas:</strong> número.</li>
      <li><strong>Riesgo predominante:</strong> ALTO, MEDIO o BAJO.</li>
      <li><strong>Temas críticos:</strong> lista breve.</li>
      <li><strong>Fecha límite de preguntas:</strong> fecha y hora solo si están documentadas.</li>
    </ul>
  </section>

  <section>
    <h2>Preguntas recomendadas para presentar</h2>
    <table>
      <thead>
        <tr>
          <th>Número</th>
          <th>Prioridad</th>
          <th>Categoría</th>
          <th>Página</th>
          <th>Numeral / Apartado</th>
          <th>Hallazgo detectado</th>
          <th>Pregunta / Aclaración propuesta</th>
          <th>Objetivo de la pregunta</th>
          <th>Riesgo si no se aclara</th>
          <th>Fuente</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td>ALTA</td>
          <td>Legal, técnica, financiera, logística, fiscal, contractual o administrativa</td>
          <td>Página real o Página no identificada</td>
          <td>Numeral real</td>
          <td>Explica la contradicción o ambigüedad encontrada.</td>
          <td>Redacta una pregunta formal, precisa y lista para enviar a la convocante.</td>
          <td>Indica qué confirmación, modificación o criterio se busca obtener.</td>
          <td>Desechamiento, costo, incumplimiento, restricción, penalización u otro riesgo.</td>
          <td>Nombre real del archivo y cita breve.</td>
        </tr>
      </tbody>
    </table>
  </section>

  <section>
    <h2>Preguntas por categoría</h2>
    <h3>Legal y administrativa</h3>
    <ol><li>Incluye preguntas no duplicadas sobre personalidad, poderes, plataformas, documentación y causales de desechamiento.</li></ol>
    <h3>Técnica</h3>
    <ol><li>Incluye especificaciones limitantes, normas, marcas, contenido nacional, muestras y aceptación de bienes.</li></ol>
    <h3>Financiera y contractual</h3>
    <ol><li>Incluye pago, facturación, fianzas, seguros, vigencia, penalizaciones y rescisión.</li></ol>
    <h3>Logística y operación</h3>
    <ol><li>Incluye entrega, horarios, reposición, almacenes, evidencias y coordinación.</li></ol>
    <h3>Fiscal y seguridad social</h3>
    <ol><li>Incluye SAT, IMSS, INFONAVIT, ejercicios fiscales y documentos alternativos únicamente cuando la evidencia lo justifique.</li></ol>
  </section>

  <section>
    <h2>Contradicciones y errores de bases detectados</h2>
    <table>
      <thead><tr><th>#</th><th>Tema</th><th>Documento A</th><th>Documento B</th><th>Contradicción</th><th>Impacto</th><th>Acción sugerida</th></tr></thead>
      <tbody><tr><td>1</td><td></td><td></td><td></td><td></td><td></td><td></td></tr></tbody>
    </table>
  </section>

  <section>
    <h2>Preguntas que requieren autorización interna</h2>
    <p>Incluye preguntas sensibles, negociadoras o potencialmente contraproducentes. Explica por qué deben revisarse antes de presentarse.</p>
  </section>

  <section>
    <h2>Preguntas descartadas o consolidadas</h2>
    <table>
      <thead><tr><th>Pregunta original</th><th>Decisión</th><th>Motivo</th><th>Pregunta consolidada relacionada</th></tr></thead>
      <tbody><tr><td></td><td>Descartada o consolidada</td><td></td><td></td></tr></tbody>
    </table>
  </section>

  <section>
    <h2>Matriz de prioridad</h2>
    <table>
      <thead><tr><th>Prioridad</th><th>Criterio</th><th>Preguntas</th><th>Responsable sugerido</th></tr></thead>
      <tbody>
        <tr><td>ALTA</td><td>Puede provocar desechamiento, pérdida económica o imposibilidad de cumplimiento.</td><td>Números de preguntas.</td><td>Jurídico, técnico, finanzas, operaciones o dirección.</td></tr>
        <tr><td>MEDIA</td><td>Genera ambigüedad, costo adicional o dificultad operativa.</td><td>Números de preguntas.</td><td>Área responsable.</td></tr>
        <tr><td>BAJA</td><td>Mejora claridad documental sin cambiar la viabilidad.</td><td>Números de preguntas.</td><td>Área responsable.</td></tr>
      </tbody>
    </table>
  </section>

  <section>
    <h2>Fuentes documentales</h2>
    <table>
      <thead><tr><th>#</th><th>Documento</th><th>Página / Sección</th><th>Numeral</th><th>Uso en el análisis</th></tr></thead>
      <tbody><tr><td>1</td><td>Nombre real del archivo</td><td></td><td></td><td></td></tr></tbody>
    </table>
  </section>

  <section>
    <h2>Revisión previa al envío</h2>
    <ul>
      <li>Verificar que cada página, numeral y cita corresponda al documento original.</li>
      <li>Eliminar preguntas duplicadas.</li>
      <li>Confirmar que ninguna pregunta revele una incapacidad innecesaria del licitante.</li>
      <li>Validar las preguntas legales con el área jurídica.</li>
      <li>Validar especificaciones y equivalencias con el área técnica.</li>
      <li>Confirmar fecha, hora, plataforma y formato oficial de presentación.</li>
    </ul>
  </section>
</article>

DATOS ESTRUCTURADOS
{$structured}

CHECKLIST Y ESTADO DE CUMPLIMIENTO
{$checklistJson}

TEXTO EXTRAÍDO DE LOS DOCUMENTOS
{$documentsText}
PROMPT;
    }

    private function buildSpecializedReportPrompt(Project $project, array $checklist, string $type, array $options = []): string
    {
        if ($type === 'analysis') {
            return $this->buildExecutiveReportPrompt($project, $checklist);
        }

        if ($type === 'clarifications') {
            return $this->buildClarificationsReportPrompt($project, $checklist, $options);
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
            'clarifications' => '<h2>Resumen Ejecutivo de Hallazgos</h2><p>No fue posible completar el análisis automático. Revise manualmente las bases y agregue preguntas sustentadas.</p><h2>Preguntas recomendadas para presentar</h2><table><thead><tr><th>Número</th><th>Prioridad</th><th>Categoría</th><th>Página</th><th>Numeral / Apartado</th><th>Hallazgo detectado</th><th>Pregunta / Aclaración propuesta</th><th>Objetivo</th><th>Riesgo</th><th>Fuente</th></tr></thead><tbody><tr><td>1</td><td>ALTA</td><td>Por revisar</td><td>Página no identificada</td><td>Por revisar</td><td>Validación manual pendiente.</td><td>Redactar pregunta después de validar la evidencia.</td><td>Aclarar el requisito.</td><td>Por determinar.</td><td>Documentos del proyecto.</td></tr></tbody></table><h2>Contradicciones y errores detectados</h2><p>Requiere revisión manual de convocatoria, anexos, contrato, formatos y checklist.</p><h2>Revisión previa al envío</h2><ul><li>Validar páginas, numerales y citas.</li><li>Eliminar duplicados.</li><li>Revisar con las áreas jurídica, técnica y comercial.</li></ul>',
            default => '',
        };

        return '<article class="jrt-report-doc"><h1>' . $title . '</h1><section><h2>🔹 Resumen Ejecutivo</h2><p>Reporte generado para el proyecto ' . $projectName . '. No se pudo completar el análisis automático, por lo que se muestra la estructura base para edición.</p></section><section><h2>🗂️ Detalle por Categoría</h2>' . $categoryHtml . '</section><section><h2>📚 Fuente de información</h2><table><thead><tr><th>#</th><th>Documento</th><th>Sección / Numeral</th><th>Página</th><th>Descripción breve</th></tr></thead><tbody><tr><td>1</td><td>Documentos del proyecto</td><td>Sin sección específica</td><td>—</td><td>Información pendiente de extracción o validación.</td></tr></tbody></table></section><section><h2>🧩 Observaciones finales</h2><ul><li>⚠️ Requiere validación manual de fuentes y numerales.</li><li>⚠️ Completar los datos marcados como no explícitos si se encuentran en anexos o contrato.</li></ul></section></article>';
    }

    public function generateReport(Request $request, Project $project, OpenAiStructurerService $ai)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        try {
            $action = (string) $request->input('action', 'generate');

            if ($action === 'clarifications_status') {
                $jobId = trim((string) $request->input('job_id', ''));

                if ($jobId === '') {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Falta el identificador del proceso.',
                    ], 422);
                }

                $status = Cache::get("project-report-job:{$jobId}");

                if (!is_array($status)) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'El proceso ya no existe o expiró.',
                    ], 404);
                }

                return response()->json(array_merge(['ok' => true], $status));
            }

            $type = $this->normalizeReportType($request->input('report_type', 'analysis'));
            $title = $request->input('report_title') ?: $this->reportTitleForType($type);

            if ($action === 'save') {
                $content = $request->input('report_content', $request->input('draft_content', ''));
                $this->saveProjectReport($project, $type, $content, $title);

                return response()->json([
                    'ok' => true,
                    'saved_at' => now()->format('H:i:s'),
                ]);
            }

            $project->loadMissing(['documents']);
            $checklist = $this->projectChecklistReportArray($project);

            $template = $type === 'clarifications'
                ? $this->clarificationTemplateContext($request, $project)
                : [
                    'mode' => $request->input('format_mode', 'estandar'),
                    'filename' => null,
                    'stored_path' => null,
                    'content' => '',
                ];

            $options = [
                'format_mode' => $template['mode'] ?? $request->input('format_mode', 'estandar'),
                'template_filename' => $template['filename'] ?? '',
                'template_context' => $template['content'] ?? '',
                'risk_levels' => $request->input('risk_levels', ['alto', 'medio', 'no_cumple']),
                'instructions' => $request->input('instructions', ''),
            ];

            if ($type === 'clarifications') {
                $jobId = (string) Str::uuid();
                $cacheKey = "project-report-job:{$jobId}";

                Cache::put($cacheKey, [
                    'status' => 'queued',
                    'message' => 'Proceso enviado a la cola. Esperando trabajador...',
                    'progress' => 10,
                    'html' => '<article class="jrt-report-doc jrt-clarifications-report"><header><h1>Junta de Aclaraciones - Preguntas Estratégicas</h1><p>Proyecto: ' . e($project->name) . '</p></header><section><h2>Preparando análisis</h2><p>El proceso fue enviado a la cola. El documento se actualizará automáticamente conforme se generen las secciones.</p></section></article>',
                    'report_type' => 'clarifications',
                    'report_title' => $title,
                    'completed_sections' => [],
                    'template_name' => $template['filename'] ?? null,
                ], now()->addHour());

                $projectId = (int) $project->id;
                $userId = Auth::id();

                GenerateClarificationsReportJob::dispatch(
                    $projectId,
                    $userId,
                    $title,
                    $options,
                    $cacheKey
                )->onQueue('reports');

                return response()->json([
                    'ok' => true,
                    'queued' => true,
                    'job_id' => $jobId,
                    'message' => 'La generación comenzó en segundo plano.',
                    'template_name' => $template['filename'] ?? null,
                ], 202);
            }

            $prompt = $this->buildSpecializedReportPrompt($project, $checklist, $type, $options);
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
                'template_name' => $template['filename'] ?? null,
                'template_path' => $template['stored_path'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Report generation failed', [
                'project_id' => $project->id,
                'err' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Documento visible desde el primer segundo mientras se generan las secciones.
     */
    private function buildClarificationsLiveHtml(
        Project $project,
        array $sections,
        string $currentMessage,
        int $progress,
        bool $completed = false
    ): string {
        $projectName = e($project->name);
        $status = $completed ? 'Documento finalizado' : 'Generación en progreso';
        $safeMessage = e($currentMessage);
        $safeProgress = max(0, min(100, $progress));

        $html = '<article class="jrt-report-doc jrt-clarifications-report jrt-live-report">';
        $html .= '<header>';
        $html .= '<h1>Junta de Aclaraciones - Preguntas Estratégicas</h1>';
        $html .= '<p><strong>Proyecto:</strong> ' . $projectName . '</p>';
        $html .= '<p>Documento de trabajo sujeto a revisión jurídica, técnica, comercial y directiva.</p>';
        $html .= '</header>';

        $html .= '<section class="jrt-live-progress" contenteditable="false">';
        $html .= '<h2>' . e($status) . '</h2>';
        $html .= '<p>' . $safeMessage . '</p>';
        $html .= '<div style="height:10px;border-radius:999px;background:#e6f0ff;overflow:hidden;margin:10px 0 6px;">';
        $html .= '<div style="width:' . $safeProgress . '%;height:100%;background:#007aff;transition:width .25s ease;"></div>';
        $html .= '</div>';
        $html .= '<p><strong>Avance:</strong> ' . $safeProgress . '%</p>';
        $html .= '</section>';

        if (empty($sections)) {
            $html .= '<section><h2>Preparando análisis</h2><p>Se están revisando las bases, anexos, contrato, checklist y las instrucciones proporcionadas.</p></section>';
        } else {
            foreach ($sections as $sectionHtml) {
                if (is_string($sectionHtml) && trim($sectionHtml) !== '') {
                    $html .= $sectionHtml;
                }
            }
        }

        if (!$completed) {
            $html .= '<section class="jrt-live-pending" contenteditable="false">';
            $html .= '<h2>Siguientes secciones</h2>';
            $html .= '<p>El documento seguirá actualizándose automáticamente conforme termine cada bloque.</p>';
            $html .= '</section>';
        }

        $html .= '</article>';

        return $html;
    }

    /**
     * Define los bloques progresivos de Junta de Aclaraciones.
     */
    private function clarificationGenerationSections(): array
    {
        /*
         * Tres llamadas en lugar de seis.
         * Reduce aproximadamente a la mitad el tiempo total sin perder el avance.
         */
        return [
            [
                'key' => 'diagnosis',
                'title' => 'Diagnóstico y preguntas legales',
                'progress' => 35,
                'instruction' => 'Genera: 1) <section><h2>Resumen Ejecutivo de Hallazgos</h2>...</section>; y 2) <section><h2>Preguntas legales y administrativas</h2>...</section>. Incluye únicamente preguntas relevantes y no duplicadas. Máximo 10 preguntas en este bloque.',
            ],
            [
                'key' => 'core',
                'title' => 'Preguntas técnicas, financieras y operativas',
                'progress' => 72,
                'instruction' => 'Genera tres secciones: <h2>Preguntas técnicas</h2>, <h2>Preguntas financieras y contractuales</h2> y <h2>Preguntas logísticas, fiscales y de seguridad social</h2>. Usa tablas compactas con Número, Prioridad, Página, Numeral/Apartado, Hallazgo, Pregunta/Aclaración, Objetivo, Riesgo y Fuente. Máximo 18 preguntas en total, solo las que tengan evidencia o riesgo real.',
            ],
            [
                'key' => 'closing',
                'title' => 'Contradicciones, prioridad y fuentes',
                'progress' => 96,
                'instruction' => 'Genera las secciones finales: <h2>Contradicciones y errores de bases detectados</h2>, <h2>Preguntas que requieren autorización interna</h2>, <h2>Matriz de prioridad</h2>, <h2>Fuentes documentales</h2> y <h2>Revisión previa al envío</h2>. No repitas preguntas anteriores. Mantén el contenido breve y ejecutivo.',
            ],
        ];
    }

    private function cleanGeneratedReportHtml(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^```html\s*/i', '', $html);
        $html = preg_replace('/^```\s*/', '', $html);
        $html = preg_replace('/```$/', '', trim($html));

        return trim((string) $html);
    }

    private function buildClarificationsSectionPrompt(
        Project $project,
        array $checklist,
        array $options,
        array $section,
        array $generatedSections
    ): string {
        /*
         * Contexto compacto: antes se reenviaba el prompt completo y hasta
         * 25,000 caracteres documentales en cada una de seis llamadas.
         */
        $structuredData = is_array($project->structured_data) ? $project->structured_data : [];

        // Excluye reportes previos para no inflar el contexto.
        unset($structuredData['generated_reports'], $structuredData['generated_documents']);

        $structured = json_encode(
            $structuredData,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $checklistCompact = collect($checklist)
            ->take(80)
            ->map(fn ($item) => [
                'requisito' => $item['requisito'] ?? $item['requirement'] ?? '',
                'descripcion' => $item['descripcion'] ?? $item['description'] ?? '',
                'cumplimiento' => $item['cumplimiento'] ?? '-',
                'prioridad' => $item['prioridad'] ?? 'Media',
                'fuente' => $item['fuente'] ?? '',
                'pagina' => $item['pagina'] ?? null,
                'cita' => Str::limit((string) ($item['cita'] ?? ''), 280),
            ])
            ->values()
            ->all();

        $checklistJson = json_encode(
            $checklistCompact,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        // 12,000 caracteres suelen ser suficientes para detectar los puntos críticos.
        $documentsText = $this->projectGroundedDocumentText($project, 12000);

        $riskLevels = collect($options['risk_levels'] ?? ['alto', 'medio', 'no_cumple'])
            ->map(fn ($value) => mb_strtoupper(trim((string) $value), 'UTF-8'))
            ->filter()
            ->implode(', ');

        $instructions = Str::limit(trim((string) ($options['instructions'] ?? '')), 1200);
        $formatMode = trim((string) ($options['format_mode'] ?? 'estandar'));
        $templateFilename = trim((string) ($options['template_filename'] ?? ''));
        $templateContext = Str::limit(trim((string) ($options['template_context'] ?? '')), 2500);
        $previousTitles = collect($generatedSections)->keys()->implode(', ');
        $projectName = $project->name;

        return <<<PROMPT
Eres especialista senior en licitaciones públicas mexicanas y juntas de aclaraciones.

Genera SOLO el bloque HTML solicitado para el proyecto "{$projectName}".

BLOQUE ACTUAL
{$section['instruction']}

CONFIGURACIÓN
- Riesgos prioritarios: {$riskLevels}
- Instrucciones del usuario: {$instructions}
- Formato: {$formatMode}
- Archivo de formato: {$templateFilename}
- Estructura del formato: {$templateContext}

REGLAS
1. Usa únicamente la evidencia incluida abajo.
2. No inventes páginas, numerales, leyes, fechas, porcentajes ni citas.
3. Si una referencia legal no aparece en la evidencia, escribe "Referencia legal sugerida para validación".
4. Respeta el tono solicitado por el usuario, pero mantén claridad profesional.
5. Evita preguntas duplicadas y asuntos sin impacto real.
6. Devuelve únicamente secciones HTML, sin <article>, markdown ni explicaciones.
7. Las preguntas deben ser concretas y listas para presentar.
8. Si no existe evidencia suficiente para una pregunta, no la incluyas.
9. Secciones ya generadas: {$previousTitles}.

DATOS ESTRUCTURADOS
{$structured}

CHECKLIST COMPACTO
{$checklistJson}

EXTRACTO DOCUMENTAL PRIORITARIO
{$documentsText}
PROMPT;
    }

    public function processClarificationsReportAsync(
        int $projectId,
        ?int $userId,
        string $title,
        array $options,
        string $cacheKey
    ): void {
        ignore_user_abort(true);
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ini_set('memory_limit', '768M');

        try {
            $project = Project::query()->findOrFail($projectId);

            if ($userId !== 1 && (int) $project->user_id !== (int) $userId) {
                throw new \RuntimeException('No tienes permiso para procesar este proyecto.');
            }

            $project->loadMissing(['documents']);
            $checklist = $this->projectChecklistReportArray($project);
            $generatedSections = [];

            $initialHtml = $this->buildClarificationsLiveHtml(
                $project,
                [],
                'Analizando bases, anexos, checklist e instrucciones...',
                12,
                false
            );

            Cache::put($cacheKey, [
                'status' => 'processing',
                'message' => 'Analizando bases, anexos, checklist e instrucciones...',
                'progress' => 12,
                'html' => $initialHtml,
                'report_type' => 'clarifications',
                'report_title' => $title,
                'completed_sections' => [],
                'template_name' => $options['template_filename'] ?? null,
            ], now()->addHours(3));

            $ai = app(OpenAiStructurerService::class);
            $sections = $this->clarificationGenerationSections();

            foreach ($sections as $section) {
                $beforeHtml = $this->buildClarificationsLiveHtml(
                    $project,
                    $generatedSections,
                    'Generando ' . $section['title'] . '...',
                    max(12, (int) $section['progress'] - 8),
                    false
                );

                Cache::put($cacheKey, [
                    'status' => 'processing',
                    'message' => 'Generando ' . $section['title'] . '...',
                    'progress' => max(12, (int) $section['progress'] - 8),
                    'html' => $beforeHtml,
                    'report_type' => 'clarifications',
                    'report_title' => $title,
                    'current_section' => $section['key'],
                    'completed_sections' => array_keys($generatedSections),
                    'template_name' => $options['template_filename'] ?? null,
                ], now()->addHours(3));

                $prompt = $this->buildClarificationsSectionPrompt(
                    $project,
                    $checklist,
                    $options,
                    $section,
                    $generatedSections
                );

                $messages = [
                    [
                        'role' => 'system',
                        'content' => 'Eres un especialista en licitaciones públicas mexicanas. Devuelve únicamente la sección HTML solicitada, verificable, sustentada y lista para editar.',
                    ],
                    ['role' => 'user', 'content' => $prompt],
                ];

                try {
                    $sectionHtml = $this->cleanGeneratedReportHtml(
                        (string) $ai->chatRaw($messages)
                    );
                } catch (\Throwable $sectionError) {
                    Log::warning('Clarifications progressive section failed', [
                        'project_id' => $projectId,
                        'section' => $section['key'],
                        'error' => $sectionError->getMessage(),
                    ]);

                    $sectionHtml = '<section><h2>' . e($section['title']) . '</h2><p>No fue posible completar automáticamente este bloque. Requiere revisión manual.</p></section>';
                }

                if ($sectionHtml === '' || trim(strip_tags($sectionHtml)) === '') {
                    $sectionHtml = '<section><h2>' . e($section['title']) . '</h2><p>No se encontró evidencia suficiente para generar contenido en este bloque.</p></section>';
                }

                $generatedSections[$section['key']] = $sectionHtml;

                $partialHtml = $this->buildClarificationsLiveHtml(
                    $project,
                    $generatedSections,
                    $section['title'] . ' completado. Continuando con el siguiente bloque...',
                    (int) $section['progress'],
                    false
                );

                // Guarda también el avance para que pueda descargarse o editarse.
                $this->saveProjectReport($project->fresh(), 'clarifications', $partialHtml, $title);

                Cache::put($cacheKey, [
                    'status' => 'processing',
                    'message' => $section['title'] . ' completado.',
                    'progress' => (int) $section['progress'],
                    'html' => $partialHtml,
                    'report_type' => 'clarifications',
                    'report_title' => $title,
                    'current_section' => null,
                    'completed_sections' => array_keys($generatedSections),
                    'saved_at' => now()->format('H:i:s'),
                    'template_name' => $options['template_filename'] ?? null,
                ], now()->addHours(3));
            }

            $finalHtml = $this->buildClarificationsLiveHtml(
                $project,
                $generatedSections,
                'Junta de Aclaraciones terminada.',
                100,
                true
            );

            $this->saveProjectReport($project->fresh(), 'clarifications', $finalHtml, $title);

            Cache::put($cacheKey, [
                'status' => 'completed',
                'message' => 'Junta de Aclaraciones generada correctamente.',
                'progress' => 100,
                'html' => $finalHtml,
                'report_type' => 'clarifications',
                'report_title' => $title,
                'completed_sections' => array_keys($generatedSections),
                'saved_at' => now()->format('H:i:s'),
                'template_name' => $options['template_filename'] ?? null,
            ], now()->addHours(3));
        } catch (\Throwable $e) {
            Log::error('Async clarifications report generation failed', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $cached = Cache::get($cacheKey, []);

            Cache::put($cacheKey, [
                'status' => 'failed',
                'message' => $e->getMessage(),
                'progress' => (int) ($cached['progress'] ?? 100),
                'html' => $cached['html'] ?? null,
                'report_type' => 'clarifications',
                'report_title' => $title,
                'completed_sections' => $cached['completed_sections'] ?? [],
                'template_name' => $options['template_filename'] ?? null,
            ], now()->addHours(3));
        }
    }


    /* ============================================================
     |  ACCIONES RAPIDAS DEL PROYECTO DESDE EL MENU
     |  - Cambiar nombre
     |  - Cambiar color
     |  - Archivar
     |  - Eliminar
     * ============================================================ */
    public function quickUpdate(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $data = $request->validate([
            'name'  => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);

            if ($name === '') {
                return response()->json([
                    'ok' => false,
                    'message' => 'El nombre del proyecto no puede estar vacio.',
                ], 422);
            }

            $project->name = $name;
        }

        if (array_key_exists('color', $data)) {
            $color = trim((string) $data['color']);

            if ($color !== '' && !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El color seleccionado no es valido.',
                ], 422);
            }

            if ($color !== '') {
                $project->color = $color;
            }
        }

        $project->save();

        return response()->json([
            'ok' => true,
            'message' => 'Proyecto actualizado correctamente.',
            'name' => $project->name,
            'color' => $project->color,
        ]);
    }

    public function archive(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        if (Schema::hasColumn('projects', 'archived_at')) {
            $project->archived_at = now();
            $project->save();
        } else {
            $project->status = 'archived';
            $project->save();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Proyecto archivado correctamente.',
        ]);
    }


    public function restore(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        if (Schema::hasColumn('projects', 'archived_at')) {
            $project->archived_at = null;
            $project->save();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Proyecto activado correctamente.',
        ]);
    }

    public function destroy(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $project->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Proyecto eliminado correctamente.',
        ]);
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
     |  PRIORIDAD DEL PROYECTO
     * ============================================================ */
    public function updatePriority(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $data = $request->validate([
            'priority' => ['required', 'string', 'in:alta,media,baja,normal,Alta,Media,Baja,Normal'],
        ]);

        $priority = strtolower(trim((string) $data['priority']));

        $labels = [
            'alta' => 'Alta',
            'media' => 'Media',
            'baja' => 'Baja',
            'normal' => 'Normal',
        ];

        $project->priority = $labels[$priority] ?? 'Normal';
        $project->save();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Prioridad actualizada correctamente.',
                'priority' => strtolower($project->priority),
                'priority_label' => $project->priority,
            ]);
        }

        return back()->with('success', 'Prioridad actualizada correctamente.');
    }

    /* ============================================================
     |  ASIGNADO DEL PROYECTO
     |  - Vinculado a tabla users mediante projects.assigned_to
     * ============================================================ */
    public function updateAssignee(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $data = $request->validate([
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::query()->findOrFail((int) $data['assigned_user_id']);

        $initial = Str::upper(Str::substr(trim((string) $user->name), 0, 1));
        if ($initial === '') {
            $initial = Str::upper(Str::substr(trim((string) $user->email), 0, 1));
        }
        $initial = $initial ?: 'U';

        if (Schema::hasColumn('projects', 'assigned_to')) {
            $project->assigned_to = $user->id;
        }

        // Campos legacy para que no se rompan vistas/reportes antiguos.
        if (Schema::hasColumn('projects', 'assigned')) {
            $project->assigned = $initial;
        }

        if (Schema::hasColumn('projects', 'assigned_name')) {
            $project->assigned_name = $user->name;
        }

        if (Schema::hasColumn('projects', 'assigned_email')) {
            $project->assigned_email = $user->email;
        }

        $project->save();

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Usuario asignado correctamente.',
                'assigned_user_id' => $user->id,
                'assigned' => $initial,
                'assigned_name' => $user->name,
                'assigned_email' => $user->email,
                'avatar_url' => $user->avatar_url,
            ]);
        }

        return back()->with('success', 'Usuario asignado correctamente.');
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


    /* ============================================================
     |  DASHBOARD NOTES
     * ============================================================ */
    public function storeDashboardNote(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        abort_unless(Schema::hasTable('project_notes'), 500, 'La tabla project_notes no existe. Ejecuta la migración.');

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $now = now();

        $insert = [
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'content' => trim((string) $data['content']),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('project_notes', 'is_pinned')) {
            $insert['is_pinned'] = false;
        }

        if (Schema::hasColumn('project_notes', 'archived_at')) {
            $insert['archived_at'] = null;
        }

        $id = DB::table('project_notes')->insertGetId($insert);

        $user = Auth::user();

        return response()->json([
            'ok' => true,
            'message' => 'Nota agregada correctamente.',
            'note' => [
                'id' => $id,
                'content' => trim((string) $data['content']),
                'user_name' => $user?->name ?? 'Usuario',
                'user_email' => $user?->email,
                'date' => $now->format('j M Y'),
                'update_url' => route('projects.notes.update', [$project, $id]),
                'convert_url' => route('projects.notes.convert-task', [$project, $id]),
                'pin_url' => route('projects.notes.pin', [$project, $id]),
                'delete_url' => route('projects.notes.destroy', [$project, $id]),
            ],
        ]);
    }


    public function updateDashboardNote(Request $request, Project $project, int $note)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);
        abort_unless(Schema::hasTable('project_notes'), 500, 'La tabla project_notes no existe. Ejecuta la migración.');

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        DB::table('project_notes')
            ->where('project_id', $project->id)
            ->where('id', $note)
            ->update([
                'content' => trim((string) $data['content']),
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'message' => 'Nota actualizada correctamente.',
            'note' => [
                'id' => $note,
                'content' => trim((string) $data['content']),
            ],
        ]);
    }

    public function pinDashboardNote(Project $project, int $note)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);
        abort_unless(Schema::hasTable('project_notes'), 500, 'La tabla project_notes no existe. Ejecuta la migración.');
        abort_unless(Schema::hasColumn('project_notes', 'is_pinned'), 500, 'Falta la columna is_pinned en project_notes.');

        $current = DB::table('project_notes')
            ->where('project_id', $project->id)
            ->where('id', $note)
            ->value('is_pinned');

        $next = !$current;

        DB::table('project_notes')
            ->where('project_id', $project->id)
            ->where('id', $note)
            ->update([
                'is_pinned' => $next,
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'is_pinned' => $next,
            'message' => $next ? 'Nota fijada correctamente.' : 'Nota desfijada correctamente.',
        ]);
    }

    public function convertDashboardNoteToTask(Project $project, int $note)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);
        abort_unless(Schema::hasTable('project_notes') && Schema::hasTable('project_tasks'), 500, 'Faltan tablas de notas o tareas. Ejecuta la migración.');

        $noteRow = DB::table('project_notes')
            ->where('project_id', $project->id)
            ->where('id', $note)
            ->first();

        abort_if(!$noteRow, 404, 'No se encontró la nota.');

        $now = now();

        $insert = [
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'assigned_to' => Auth::id(),
            'title' => Str::limit(trim((string) $noteRow->content), 500, ''),
            'priority' => 'normal',
            'completed' => false,
            'due_date' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('project_tasks', 'is_pinned')) {
            $insert['is_pinned'] = false;
        }

        if (Schema::hasColumn('project_tasks', 'archived_at')) {
            $insert['archived_at'] = null;
        }

        $taskId = DB::table('project_tasks')->insertGetId($insert);

        return response()->json([
            'ok' => true,
            'message' => 'Nota convertida en tarea.',
            'task' => $this->dashboardTaskPayload($project, $taskId),
        ]);
    }


    public function destroyDashboardNote(Project $project, int $note)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        abort_unless(Schema::hasTable('project_notes'), 500, 'La tabla project_notes no existe. Ejecuta la migración.');

        DB::table('project_notes')
            ->where('project_id', $project->id)
            ->where('id', $note)
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Nota eliminada correctamente.',
        ]);
    }

    /* ============================================================
     |  DASHBOARD TASKS
     * ============================================================ */
    private function dashboardTaskPayload(Project $project, int $taskId): array
    {
        $task = DB::table('project_tasks')
            ->leftJoin('users', 'users.id', '=', 'project_tasks.assigned_to')
            ->where('project_tasks.project_id', $project->id)
            ->where('project_tasks.id', $taskId)
            ->select([
                'project_tasks.id',
                'project_tasks.title',
                'project_tasks.priority',
                'project_tasks.completed',
                'project_tasks.assigned_to',
                'project_tasks.due_date',
                'project_tasks.created_at',
                'project_tasks.updated_at',
                DB::raw('users.name as assigned_name'),
                DB::raw('users.email as assigned_email'),
            ])
            ->first();

        abort_if(!$task, 404, 'No se encontró la tarea.');

        $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date) : null;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority ?: 'normal',
            'completed' => (bool) $task->completed,
            'assigned_to' => $task->assigned_to,
            'assigned_name' => $task->assigned_name,
            'due_date' => $task->due_date,
            'due_date_label' => $dueDate ? $dueDate->format('d M') : 'Sin fecha',
            'update_url' => route('projects.tasks.update', [$project, $task->id]),
            'convert_url' => route('projects.tasks.convert-note', [$project, $task->id]),
            'pin_url' => route('projects.tasks.pin', [$project, $task->id]),
            'archive_url' => route('projects.tasks.archive', [$project, $task->id]),
            'delete_url' => route('projects.tasks.destroy', [$project, $task->id]),
        ];
    }

    public function storeDashboardTask(Request $request, Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        abort_unless(Schema::hasTable('project_tasks'), 500, 'La tabla project_tasks no existe. Ejecuta la migración.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'priority' => ['nullable', 'in:normal,baja,media,alta'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $now = now();

        $insert = [
            'project_id' => $project->id,
            'created_by' => Auth::id(),
            'assigned_to' => $data['assigned_to'] ?? null,
            'title' => trim((string) $data['title']),
            'priority' => $data['priority'] ?? 'normal',
            'completed' => false,
            'due_date' => $data['due_date'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('project_tasks', 'is_pinned')) {
            $insert['is_pinned'] = false;
        }

        if (Schema::hasColumn('project_tasks', 'archived_at')) {
            $insert['archived_at'] = null;
        }

        $id = DB::table('project_tasks')->insertGetId($insert);

        return response()->json([
            'ok' => true,
            'message' => 'Tarea agregada correctamente.',
            'task' => $this->dashboardTaskPayload($project, $id),
        ]);
    }

    public function updateDashboardTask(Request $request, Project $project, int $task)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        abort_unless(Schema::hasTable('project_tasks'), 500, 'La tabla project_tasks no existe. Ejecuta la migración.');

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:500'],
            'priority' => ['sometimes', 'nullable', 'in:normal,baja,media,alta'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        $update = [];

        foreach (['title', 'priority', 'assigned_to', 'due_date', 'completed'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $field === 'title' ? trim((string) $data[$field]) : $data[$field];
            }
        }

        $update['updated_at'] = now();

        DB::table('project_tasks')
            ->where('project_id', $project->id)
            ->where('id', $task)
            ->update($update);

        return response()->json([
            'ok' => true,
            'message' => 'Tarea actualizada correctamente.',
            'task' => $this->dashboardTaskPayload($project, $task),
        ]);
    }


    public function pinDashboardTask(Project $project, int $task)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);
        abort_unless(Schema::hasTable('project_tasks'), 500, 'La tabla project_tasks no existe. Ejecuta la migración.');
        abort_unless(Schema::hasColumn('project_tasks', 'is_pinned'), 500, 'Falta la columna is_pinned en project_tasks.');

        $current = DB::table('project_tasks')
            ->where('project_id', $project->id)
            ->where('id', $task)
            ->value('is_pinned');

        $next = !$current;

        DB::table('project_tasks')
            ->where('project_id', $project->id)
            ->where('id', $task)
            ->update([
                'is_pinned' => $next,
                'updated_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'is_pinned' => $next,
            'message' => $next ? 'Tarea fijada correctamente.' : 'Tarea desfijada correctamente.',
        ]);
    }

    public function archiveDashboardTask(Project $project, int $task)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);
        abort_unless(Schema::hasTable('project_tasks'), 500, 'La tabla project_tasks no existe. Ejecuta la migración.');

        if (Schema::hasColumn('project_tasks', 'archived_at')) {
            DB::table('project_tasks')
                ->where('project_id', $project->id)
                ->where('id', $task)
                ->update([
                    'archived_at' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('project_tasks')
                ->where('project_id', $project->id)
                ->where('id', $task)
                ->delete();
        }

        return response()->json([
            'ok' => true,
            'message' => 'Tarea archivada correctamente.',
        ]);
    }

    public function convertDashboardTaskToNote(Project $project, int $task)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);
        abort_unless(Schema::hasTable('project_notes') && Schema::hasTable('project_tasks'), 500, 'Faltan tablas de notas o tareas. Ejecuta la migración.');

        $taskRow = DB::table('project_tasks')
            ->where('project_id', $project->id)
            ->where('id', $task)
            ->first();

        abort_if(!$taskRow, 404, 'No se encontró la tarea.');

        $now = now();

        $insert = [
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'content' => trim((string) $taskRow->title),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (Schema::hasColumn('project_notes', 'is_pinned')) {
            $insert['is_pinned'] = false;
        }

        if (Schema::hasColumn('project_notes', 'archived_at')) {
            $insert['archived_at'] = null;
        }

        $noteId = DB::table('project_notes')->insertGetId($insert);
        $user = Auth::user();

        return response()->json([
            'ok' => true,
            'message' => 'Tarea convertida en nota.',
            'note' => [
                'id' => $noteId,
                'content' => trim((string) $taskRow->title),
                'user_name' => $user?->name ?? 'Usuario',
                'user_email' => $user?->email,
                'date' => $now->format('j M Y'),
                'update_url' => route('projects.notes.update', [$project, $noteId]),
                'convert_url' => route('projects.notes.convert-task', [$project, $noteId]),
                'pin_url' => route('projects.notes.pin', [$project, $noteId]),
                'delete_url' => route('projects.notes.destroy', [$project, $noteId]),
            ],
        ]);
    }


    public function destroyDashboardTask(Project $project, int $task)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        abort_unless(Schema::hasTable('project_tasks'), 500, 'La tabla project_tasks no existe. Ejecuta la migración.');

        DB::table('project_tasks')
            ->where('project_id', $project->id)
            ->where('id', $task)
            ->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Tarea eliminada correctamente.',
        ]);
    }


    /* ============================================================
     |  FICHA DE RESUMEN
     |  - Rebuscar IA en documentos
     |  - Descargar Word con ficha + fechas
     * ============================================================ */
    public function reanalyzeFicha(Request $request, Project $project, PythonProjectProcessor $processor)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $project->loadMissing('documents');

        $paths = $project->documents
            ->map(fn ($document) => $document->file_path ? storage_path('app/public/' . $document->file_path) : null)
            ->filter(fn ($path) => $path && file_exists($path))
            ->values()
            ->all();

        if (empty($paths)) {
            $message = 'No hay documentos disponibles para rebuscar la ficha.';

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $project->status = 'processing';
        $project->error_message = null;
        $project->save();

        $projectId = (int) $project->id;
        $documentPaths = $paths;

        dispatch(function () use ($projectId, $documentPaths) {
            @set_time_limit(0);
            @ini_set('memory_limit', '1024M');

            try {
                $project = Project::with('documents')->find($projectId);

                if (!$project) {
                    return;
                }

                $processor = app(PythonProjectProcessor::class);
                $controller = app(self::class);
                $result = $controller->normalizeProcessorResult(
                    $processor->process($project, $documentPaths)
                );

                if (!$result['ok']) {
                    throw new \RuntimeException(
                        $result['error'] ?: 'No se pudo reanalizar la ficha.'
                    );
                }

                $controller->persistProcessorDocuments(
                    $project->fresh('documents'),
                    $result['documents']
                );

                $structured = $result['structured_data'];

                if (is_array($structured) && !empty($structured)) {
                    $current = is_array($project->structured_data) ? $project->structured_data : [];
                    $project->structured_data = array_replace_recursive($current, $structured);
                }

                $newChecklist = $controller->processorChecklist($structured, $project);

                if (is_array($newChecklist) && !empty($newChecklist)) {
                    $project->checklist = $newChecklist;
                }

                $project->status = 'ready';
                $project->error_message = null;
                $project->save();

                if (is_array($newChecklist) && !empty($newChecklist)) {
                    $controller->syncChecklistItemsFromArray($project, $newChecklist, true);
                }

                ProjectDocument::where('project_id', $project->id)
                    ->whereIn('file_path', $project->documents->pluck('file_path')->filter()->values()->all())
                    ->update([
                        'status' => 'procesado',
                        'processed_at' => now(),
                    ]);
            } catch (\Throwable $e) {
                Log::error('Reanalyze ficha background failed', [
                    'project_id' => $projectId,
                    'error' => $e->getMessage(),
                ]);

                if ($project = Project::find($projectId)) {
                    $project->status = 'error';
                    $project->error_message = $e->getMessage();
                    $project->save();
                }
            }
        })->afterResponse();

        $message = 'La ficha se está rebuscando en segundo plano. Recarga la vista en unos segundos.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'queued' => true,
                'message' => $message,
            ]);
        }

        return redirect()
            ->route('projects.analisis', $project)
            ->withFragment('ficha')
            ->with('success', $message);
    }

    private function fichaWordValue(array $data, array $paths, string $fallback = 'No se encontró información'): string
    {
        foreach ($paths as $path) {
            $value = data_get($data, $path);

            if (is_array($value)) {
                $value = collect($value)
                    ->filter(fn ($item) => is_scalar($item) && trim((string) $item) !== '')
                    ->implode(', ');
            }

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return $fallback;
    }

    private function fichaWordRowsHtml(array $rows): string
    {
        $html = '<table class="jrt-ficha-table"><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<th>' . e($row['label']) . '</th>';
            $html .= '<td>' . nl2br(e($row['value'])) . '</td>';
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    public function downloadFichaWord(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $project->loadMissing('documents');

        $data = is_array($project->structured_data) ? $project->structured_data : [];

        $fichaRows = [
            [
                'label' => 'Número de licitación',
                'value' => $this->fichaWordValue($data, [
                    'ficha.numero_licitacion',
                    'ficha_general.numero_licitacion',
                    'numero_licitacion',
                    'licitacion.numero',
                    'procedimiento.numero',
                ], 'En los documentos revisados, no se encontró información sobre el número de licitación.'),
            ],
            [
                'label' => 'Tipo de evento',
                'value' => $this->fichaWordValue($data, [
                    'ficha.tipo_evento',
                    'ficha_general.tipo_evento',
                    'tipo_evento',
                    'procedimiento.tipo_evento',
                    'tipo_procedimiento',
                ]),
            ],
            [
                'label' => 'Organismo',
                'value' => $this->fichaWordValue($data, [
                    'ficha.organismo',
                    'ficha_general.organismo',
                    'organismo',
                    'dependencia',
                    'convocante',
                ]),
            ],
            [
                'label' => '¿Cuál es el objeto de la licitación?',
                'value' => $this->fichaWordValue($data, [
                    'ficha.objeto_licitacion',
                    'ficha.objeto',
                    'ficha_general.objeto_licitacion',
                    'objeto_licitacion',
                    'objeto',
                    'procedimiento.objeto',
                ], 'No se encontró información sobre objeto'),
            ],
            [
                'label' => '¿Cuál es el medio de participación?',
                'value' => $this->fichaWordValue($data, [
                    'ficha.medio_participacion',
                    'ficha_general.medio_participacion',
                    'medio_participacion',
                    'procedimiento.medio_participacion',
                ]),
            ],
            [
                'label' => '¿En qué moneda se realizará el pago?',
                'value' => $this->fichaWordValue($data, [
                    'ficha.moneda_pago',
                    'ficha.moneda',
                    'ficha_general.moneda_pago',
                    'moneda_pago',
                    'condiciones_pago.moneda',
                    'pago.moneda',
                ], 'Sin dato'),
            ],
            [
                'label' => 'Condiciones y forma de pago',
                'value' => $this->fichaWordValue($data, [
                    'ficha.condiciones_pago',
                    'ficha.forma_pago',
                    'ficha_general.condiciones_pago',
                    'condiciones_pago',
                    'forma_pago',
                    'pago.condiciones',
                    'pago.forma_pago',
                ], 'Sin dato'),
            ],
        ];

        $fechaRows = [
            [
                'label' => 'Fecha de publicación',
                'value' => $this->fichaWordValue($data, [
                    'fechas_clave.fecha_publicacion',
                    'fecha_publicacion',
                ], 'En los documentos revisados, no se encontró información sobre la fecha de publicación del resumen de la convocatoria.'),
            ],
            [
                'label' => 'Junta de aclaraciones',
                'value' => $this->fichaWordValue($data, [
                    'fechas_clave.junta_aclaraciones',
                    'junta_aclaraciones',
                ]),
            ],
            [
                'label' => 'Presentación y apertura de proposiciones',
                'value' => $this->fichaWordValue($data, [
                    'fechas_clave.presentacion_apertura',
                    'fechas_clave.presentacion_y_apertura',
                    'presentacion_apertura',
                    'fecha_presentacion_apertura',
                ]),
            ],
            [
                'label' => 'Fallo',
                'value' => $this->fichaWordValue($data, [
                    'fechas_clave.fallo',
                    'fallo',
                    'fecha_fallo',
                ]),
            ],
            [
                'label' => 'Vigencia del contrato',
                'value' => $this->fichaWordValue($data, [
                    'fechas_clave.vigencia_contrato',
                    'vigencia_contrato',
                    'contrato.vigencia',
                ]),
            ],
        ];

        $created = optional($project->created_at)->format('d/m/Y, H:i:s');
        $filename = 'ficha-' . Str::slug($project->name ?: 'proyecto') . '-' . now()->format('Ymd-His') . '.doc';

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Ficha</title>';
        $html .= '<style>';
        $html .= 'body{font-family:Arial,Helvetica,sans-serif;color:#111;font-size:12pt;line-height:1.35;}';
        $html .= 'h1{font-size:20pt;margin:0 0 8px;} h2{font-size:15pt;margin:22px 0 8px;}';
        $html .= '.jrt-meta{margin:0 0 18px;color:#444;}';
        $html .= '.jrt-ficha-table{width:100%;border-collapse:collapse;margin-bottom:14px;}';
        $html .= '.jrt-ficha-table th{width:32%;text-align:left;vertical-align:top;background:#f3f6fb;border:1px solid #d9dee7;padding:8px;font-weight:bold;}';
        $html .= '.jrt-ficha-table td{vertical-align:top;border:1px solid #d9dee7;padding:8px;}';
        $html .= '</style></head><body>';
        $html .= '<h1>FICHA</h1>';
        $html .= '<p class="jrt-meta"><strong>Proyecto:</strong> ' . e($project->name) . '<br><strong>Creado:</strong> ' . e($created) . '</p>';
        $html .= '<h2>Ficha de Resumen</h2>' . $this->fichaWordRowsHtml($fichaRows);
        $html .= '<h2>Fechas Clave</h2>' . $this->fichaWordRowsHtml($fechaRows);
        $html .= '</body></html>';

        return Response::make($html, 200, [
            'Content-Type' => 'application/vnd.ms-word; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }



    public function control(Request $request)
    {
        $period = (string) $request->input('period', 'all');
        $assigneeId = (string) $request->input('assignee', 'all');
        $label = trim((string) $request->input('label', ''));

        \Carbon\Carbon::setLocale('es');

        $dateFrom = null;
        $dateTo = null;

        if (in_array($period, ['30', 'last_month'], true)) {
            $dateFrom = now()->subMonth()->startOfDay();
        } elseif ($period === 'previous_month') {
            $dateFrom = now()->subMonthNoOverflow()->startOfMonth();
            $dateTo = now()->subMonthNoOverflow()->endOfMonth();
        } elseif (in_array($period, ['60', 'bimestre'], true)) {
            $dateFrom = now()->subMonths(2)->startOfDay();
        } elseif (in_array($period, ['90', 'trimestre'], true)) {
            $dateFrom = now()->subMonths(3)->startOfDay();
        } elseif ($period === 'semestre') {
            $dateFrom = now()->subMonths(6)->startOfDay();
        } elseif ($period === 'year') {
            $dateFrom = now()->subYear()->startOfDay();
        }

        $baseQuery = Project::with('assignee')
            ->where('user_id', Auth::id())
            ->when(Schema::hasColumn('projects', 'archived_at'), function ($query) {
                $query->whereNull('archived_at');
            })
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->where(function ($sub) use ($dateFrom) {
                    if (Schema::hasColumn('projects', 'start_date')) {
                        $sub->whereDate('start_date', '>=', $dateFrom->toDateString());
                    }

                    if (Schema::hasColumn('projects', 'created_at')) {
                        $sub->orWhereDate('created_at', '>=', $dateFrom->toDateString());
                    }
                });
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo->toDateString());
            })
            ->when($assigneeId !== '' && $assigneeId !== 'all', function ($query) use ($assigneeId) {
                if (Schema::hasColumn('projects', 'assigned_to')) {
                    $query->where('assigned_to', $assigneeId);
                }
            })
            ->when($label !== '', function ($query) use ($label) {
                $query->where('labels', 'like', '%"' . addcslashes($label, '%_\\') . '"%');
            });

        $projects = $baseQuery->latest()->get();

        $columns = collect($this->defaultColumns());

        $totalProjects = $projects->count();

        $stageCards = $columns->map(function (array $column) use ($projects, $totalProjects) {
            $statuses = $column['workflow_statuses'] ?? [$column['id']];

            $count = $projects->filter(function (Project $project) use ($statuses, $column) {
                $status = $project->workflow_status ?: 'analisis_bases';

                if ($column['id'] === 'participa') {
                    return in_array($status, ['participa', 'junta_aclaraciones', 'armado_propuesta', 'entrega'], true);
                }

                return in_array($status, $statuses, true);
            })->count();

            return [
                'id' => $column['id'],
                'name' => $column['name'],
                'color' => $column['color'],
                'count' => $count,
                'percentage' => $totalProjects > 0 ? round(($count / $totalProjects) * 100, 1) : 0,
            ];
        })->values();

        $distributionLabels = $stageCards->pluck('name')->values();
        $distributionValues = $stageCards->pluck('count')->values();

        $monthKeys = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));
        $monthLabels = $monthKeys->map(fn ($key) => \Carbon\Carbon::createFromFormat('Y-m', $key)->translatedFormat('M. y'))->values();

        $evolutionSeries = $stageCards->map(function (array $stage) use ($projects, $monthKeys) {
            $statuses = collect($this->defaultColumns())
                ->firstWhere('id', $stage['id'])['workflow_statuses'] ?? [$stage['id']];

            if ($stage['id'] === 'participa') {
                $statuses = ['participa', 'junta_aclaraciones', 'armado_propuesta', 'entrega'];
            }

            $values = $monthKeys->map(function ($monthKey) use ($projects, $statuses) {
                return $projects->filter(function (Project $project) use ($monthKey, $statuses) {
                    $status = $project->workflow_status ?: 'analisis_bases';
                    $date = $project->start_date ?? $project->created_at;

                    if (!$date) {
                        return false;
                    }

                    return in_array($status, $statuses, true)
                        && optional($date)->format('Y-m') === $monthKey;
                })->count();
            })->values();

            return [
                'label' => $stage['name'],
                'color' => $stage['color'],
                'data' => $values,
            ];
        })->values();

        $labelSourceProjects = Project::query()
            ->where('user_id', Auth::id())
            ->when(Schema::hasColumn('projects', 'archived_at'), function ($query) {
                $query->whereNull('archived_at');
            })
            ->get(['id', 'labels']);

        $allLabels = $labelSourceProjects
            ->flatMap(function (Project $project) {
                $labels = $project->labels;

                if (is_string($labels)) {
                    $decoded = json_decode($labels, true);
                    $labels = is_array($decoded) ? $decoded : [];
                }

                return collect($labels ?: [])
                    ->map(fn ($item) => trim((string) $item))
                    ->filter(fn ($item) => $item !== '')
                    ->values();
            })
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->values();

        $users = User::query()
            ->select('id', 'name', 'email', 'avatar_path')
            ->orderBy('name')
            ->get();

        $recentProjects = $projects->take(8)->map(function (Project $project) {
            return [
                'name' => $project->name,
                'slug' => $project->slug,
                'assignee' => $project->assignee?->name,
                'date' => optional($project->start_date ?? $project->created_at)->locale('es')->isoFormat('D MMM YYYY'),
                'priority' => $project->priority ?: 'normal',
                'favorite' => (bool) $project->favorite,
            ];
        })->values();

        $upcomingEvents = $projects
            ->filter(function (Project $project) {
                return !empty($project->deadline_at) || !empty($project->start_date);
            })
            ->sortBy(function (Project $project) {
                return $project->deadline_at ?? $project->start_date ?? $project->created_at;
            })
            ->take(6)
            ->map(function (Project $project) {
                $date = $project->deadline_at ?? $project->start_date ?? $project->created_at;

                $eventDate = $date ? \Carbon\Carbon::parse($date)->locale('es') : null;

                return [
                    'day' => $eventDate?->format('d') ?? '—',
                    'month' => $eventDate ? Str::upper($eventDate->isoFormat('MMM')) : '',
                    'title' => $project->deadline_at ? 'Vencimiento' : 'Seguimiento',
                    'project' => $project->name,
                    'date' => $eventDate?->isoFormat('D [de] MMMM [de] YYYY') ?? 'Sin fecha',
                    'due' => $eventDate ? $eventDate->diffForHumans(null, true) : 'Sin fecha',
                ];
            })
            ->values();

        $notes = $projects
            ->filter(fn (Project $project) => !blank($project->no_participa_reason))
            ->take(6)
            ->map(function (Project $project) {
                return [
                    'project' => $project->name,
                    'slug' => $project->slug,
                    'author' => $project->assignee?->name ?: 'Sistema',
                    'initials' => Str::of($project->assignee?->name ?: 'S')->explode(' ')->filter()->take(2)->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode(''),
                    'body' => $project->no_participa_reason,
                    'date' => optional($project->updated_at)->locale('es')->diffForHumans(),
                ];
            })
            ->values();

        return view('projects.control', [
            'period' => $period,
            'assigneeId' => $assigneeId,
            'label' => $label,
            'users' => $users,
            'labels' => $allLabels,
            'totalProjects' => $totalProjects,
            'stageCards' => $stageCards,
            'distributionLabels' => $distributionLabels,
            'distributionValues' => $distributionValues,
            'monthLabels' => $monthLabels,
            'evolutionSeries' => $evolutionSeries,
            'recentProjects' => $recentProjects,
            'upcomingEvents' => $upcomingEvents,
            'notes' => $notes,
            'currentDateLabel' => Str::ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY, h:mm a')),
        ]);
    }



    public function downloadControlPdf(Request $request)
    {
        $view = $this->control($request);
        $data = method_exists($view, 'getData') ? $view->getData() : [];

        $period = (string) ($data['period'] ?? $request->input('period', 'all'));
        $assigneeId = (string) ($data['assigneeId'] ?? $request->input('assignee', 'all'));
        $label = trim((string) ($data['label'] ?? $request->input('label', '')));

        $periodLabels = [
            'all' => 'Todos',
            '30' => 'Último mes',
            'last_month' => 'Último mes',
            'previous_month' => 'Mes anterior',
            '60' => 'Bimestre',
            'bimestre' => 'Bimestre',
            '90' => 'Trimestre',
            'trimestre' => 'Trimestre',
            'semestre' => 'Semestre',
            'year' => 'Año',
        ];

        $totalProjects = (int) ($data['totalProjects'] ?? 0);
        $stageCards = collect($data['stageCards'] ?? []);
        $recentProjects = collect($data['recentProjects'] ?? []);
        $upcomingEvents = collect($data['upcomingEvents'] ?? []);
        $notes = collect($data['notes'] ?? []);
        $currentDateLabel = (string) ($data['currentDateLabel'] ?? Str::ucfirst(now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY, h:mm a')));

        $stageRows = $stageCards->map(function ($stage) {
            $name = e((string) ($stage['name'] ?? 'Etapa'));
            $count = (int) ($stage['count'] ?? 0);
            $percentage = (float) ($stage['percentage'] ?? 0);

            return '<tr><td>' . $name . '</td><td class="num">' . $count . '</td><td class="num">' . $percentage . '%</td></tr>';
        })->implode('');

        $recentRows = $recentProjects->take(12)->map(function ($project) {
            return '<tr>'
                . '<td>' . e((string) ($project['name'] ?? 'Proyecto')) . '</td>'
                . '<td>' . e((string) ($project['assignee'] ?? 'Sin asignar')) . '</td>'
                . '<td>' . e((string) ($project['date'] ?? 'Sin fecha')) . '</td>'
                . '</tr>';
        })->implode('');

        $eventRows = $upcomingEvents->take(12)->map(function ($event) {
            return '<tr>'
                . '<td>' . e((string) ($event['title'] ?? 'Evento')) . '</td>'
                . '<td>' . e((string) ($event['project'] ?? 'Sin proyecto')) . '</td>'
                . '<td>' . e((string) ($event['due'] ?? ($event['date'] ?? ($event['when'] ?? 'Sin fecha')))) . '</td>'
                . '</tr>';
        })->implode('');

        $noteRows = $notes->take(12)->map(function ($note) {
            return '<tr>'
                . '<td>' . e((string) ($note['body'] ?? ($note['text'] ?? 'Sin nota'))) . '</td>'
                . '<td>' . e((string) ($note['project'] ?? 'Sin proyecto')) . '</td>'
                . '</tr>';
        })->implode('');

        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><style>
            @page { margin: 28px; }
            body { font-family: DejaVu Sans, sans-serif; color:#1c2024; font-size:12px; line-height:1.45; }
            .head { border-bottom:1px solid #e6e9ee; padding-bottom:14px; margin-bottom:18px; }
            h1 { margin:0; font-size:22px; font-weight:700; }
            h2 { margin:22px 0 10px; font-size:15px; }
            .meta { color:#5b6470; margin-top:6px; }
            .cards { display: table; width:100%; table-layout: fixed; margin: 12px 0 18px; }
            .card { display: table-cell; border:1px solid #e6e9ee; border-radius:12px; padding:12px; }
            .card + .card { margin-left:10px; }
            .kpi { font-size:28px; font-weight:700; color:#007aff; }
            .muted { color:#5b6470; }
            table { width:100%; border-collapse:collapse; margin-bottom:14px; }
            th { text-align:left; background:#f6f8fb; color:#5b6470; font-weight:700; }
            th, td { border:1px solid #e6e9ee; padding:8px 10px; vertical-align:top; }
            .num { text-align:right; }
            .filter { display:inline-block; padding:4px 9px; border-radius:999px; background:#e6f0ff; color:#007aff; font-weight:700; margin-right:6px; }
        </style></head><body>'
            . '<div class="head">'
            . '<h1>Centro de Control</h1>'
            . '<div class="meta">Generado: ' . e($currentDateLabel) . '</div>'
            . '<div class="meta"><span class="filter">Período: ' . e($periodLabels[$period] ?? $period) . '</span>'
            . '<span class="filter">Etiqueta: ' . e($label !== '' ? $label : 'Todas') . '</span>'
            . '<span class="filter">Asignado: ' . e($assigneeId !== 'all' ? $assigneeId : 'Todos') . '</span></div>'
            . '</div>'
            . '<div class="cards"><div class="card"><div class="muted">Total de proyectos</div><div class="kpi">' . $totalProjects . '</div></div></div>'
            . '<h2>Distribución por etapa</h2><table><thead><tr><th>Etapa</th><th class="num">Proyectos</th><th class="num">Porcentaje</th></tr></thead><tbody>' . ($stageRows ?: '<tr><td colspan="3">Sin datos</td></tr>') . '</tbody></table>'
            . '<h2>Proyectos recientes</h2><table><thead><tr><th>Proyecto</th><th>Asignado</th><th>Fecha</th></tr></thead><tbody>' . ($recentRows ?: '<tr><td colspan="3">Sin proyectos</td></tr>') . '</tbody></table>'
            . '<h2>Eventos próximos</h2><table><thead><tr><th>Evento</th><th>Proyecto</th><th>Fecha</th></tr></thead><tbody>' . ($eventRows ?: '<tr><td colspan="3">Sin eventos</td></tr>') . '</tbody></table>'
            . '<h2>Notas recientes</h2><table><thead><tr><th>Nota</th><th>Proyecto</th></tr></thead><tbody>' . ($noteRows ?: '<tr><td colspan="2">Sin notas</td></tr>') . '</tbody></table>'
            . '</body></html>';

        $filename = 'centro-de-control-' . now()->format('Y-m-d-His') . '.pdf';

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('letter', 'portrait')
                ->download($filename);
        }

        return Response::make($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="centro-de-control.html"',
        ]);
    }
   
/**
 * Buscador de oportunidades con datos reales.
 * 1) Si existe una tabla de procedimientos/licitaciones, usa esa tabla.
 * 2) Si no existe, usa los proyectos reales del usuario como fuente base.
 */
public function search(Request $request)
{
    $parseDate = function ($value) {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // Intenta el siguiente formato.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    };

    $normalizeAmount = function ($value) {
        if (is_null($value) || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return is_numeric($clean) ? (float) $clean : 0.0;
    };

    $table = collect([
        'procurement_procedures',
        'licitaciones',
        'procedures',
        'opportunities',
        'compranet_procedures',
        'search_opportunities',
    ])->first(fn ($name) => Schema::hasTable($name));

    if (!$table) {
        $base = Project::query()
            ->where('user_id', Auth::id())
            ->when(Schema::hasColumn('projects', 'archived_at'), fn ($query) => $query->whereNull('archived_at'));

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $base->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $status = Str::lower((string) $request->query('status'));
            $workflow = match (true) {
                Str::contains($status, 'adjud') => 'ganado',
                Str::contains($status, 'desiert') => 'desierta',
                Str::contains($status, 'perdid') => 'perdido',
                Str::contains($status, 'no participa') => 'no_participa',
                default => null,
            };

            if ($workflow) {
                $base->where('workflow_status', $workflow);
            }
        }

        $projectPaginator = (clone $base)
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        $projectItems = $projectPaginator->getCollection()->map(function ($project) use ($normalizeAmount) {
            $ficha = data_get($project->structured_data ?? [], 'ficha', []);
            $workflowStatus = $project->workflow_status ?? $project->status ?? '';

            $status = match ($workflowStatus) {
                'ganado' => 'ADJUDICADO',
                'perdido' => 'PERDIDO',
                'desierta' => 'DESIERTO',
                'no_participa' => 'NO PARTICIPA',
                'participa', 'junta_aclaraciones', 'armado_propuesta', 'entrega' => 'VIGENTE',
                default => 'VIGENTE',
            };

            return [
                'title' => $project->name,
                'code' => data_get($ficha, 'numero_licitacion') ?: $project->slug,
                'status' => $status,
                'dependency' => data_get($ficha, 'organismo') ?: data_get($ficha, 'dependencia') ?: '—',
                'state' => data_get($ficha, 'entidad_federativa') ?: data_get($ficha, 'estado') ?: '—',
                'published_at' => data_get($ficha, 'fecha_publicacion') ?: optional($project->created_at)->toDateString(),
                'amount' => $normalizeAmount(data_get($ficha, 'importe_asociado') ?: data_get($ficha, 'importe') ?: data_get($ficha, 'monto') ?: 0),
            ];
        });

        $projectPaginator->setCollection($projectItems);

        $allItems = (clone $base)->get()->map(function ($project) use ($normalizeAmount) {
            $ficha = data_get($project->structured_data ?? [], 'ficha', []);
            $workflowStatus = $project->workflow_status ?? $project->status ?? '';

            return [
                'status' => match ($workflowStatus) {
                    'ganado' => 'ADJUDICADO',
                    'desierta' => 'DESIERTO',
                    'perdido' => 'PERDIDO',
                    'no_participa' => 'NO PARTICIPA',
                    default => 'VIGENTE',
                },
                'dependency' => data_get($ficha, 'organismo') ?: data_get($ficha, 'dependencia') ?: '—',
                'state' => data_get($ficha, 'entidad_federativa') ?: data_get($ficha, 'estado') ?: '—',
                'amount' => $normalizeAmount(data_get($ficha, 'importe_asociado') ?: data_get($ficha, 'importe') ?: data_get($ficha, 'monto') ?: 0),
            ];
        });

        return view('projects.search', [
            'procedures' => $projectPaginator,
            'stats' => [
                'total_procedures' => $projectPaginator->total(),
                'awarded_procedures' => $allItems->filter(fn ($item) => Str::contains(Str::lower($item['status']), 'adjud'))->count(),
                'active_procedures' => $allItems->filter(fn ($item) => Str::contains(Str::lower($item['status']), 'vigente'))->count(),
                'total_amount' => $allItems->sum('amount'),
                'distinct_dependencies' => $allItems->pluck('dependency')->filter(fn ($value) => filled($value) && $value !== '—')->unique()->count(),
            ],
            'topDependencies' => $allItems
                ->filter(fn ($item) => filled($item['dependency']) && $item['dependency'] !== '—')
                ->groupBy('dependency')
                ->map(fn ($rows, $name) => [
                    'name' => $name,
                    'count' => $rows->count(),
                    'amount' => $rows->sum('amount'),
                ])
                ->sortByDesc('amount')
                ->take(8)
                ->values(),
            'statusOptions' => $allItems->pluck('status')->filter()->unique()->sort()->values(),
            'dependencyOptions' => $allItems->pluck('dependency')->filter(fn ($value) => filled($value) && $value !== '—')->unique()->sort()->values(),
            'stateOptions' => $allItems->pluck('state')->filter(fn ($value) => filled($value) && $value !== '—')->unique()->sort()->values(),
        ]);
    }

    $columns = Schema::getColumnListing($table);
    $has = fn ($column) => in_array($column, $columns, true);
    $pick = fn (array $names, ?string $fallback = null) => collect($names)->first(fn ($name) => $has($name)) ?: $fallback;

    $titleCol = $pick(['title', 'name', 'procedure', 'procedimiento', 'object', 'objeto', 'descripcion'], 'id');
    $codeCol = $pick(['code', 'procedure_number', 'folio', 'numero_procedimiento', 'numero_licitacion', 'expediente'], 'id');
    $statusCol = $pick(['status', 'estatus', 'estado'], 'id');
    $dependencyCol = $pick(['dependency', 'dependencia', 'organismo', 'unidad_compradora'], 'id');
    $stateCol = $pick(['state', 'entidad_federativa', 'estado_entidad', 'entidad'], 'id');
    $dateCol = $pick(['published_at', 'fecha_publicacion', 'publication_date', 'created_at'], 'id');
    $amountCol = $pick(['amount', 'importe_asociado', 'importe', 'total_amount', 'monto'], null);

    $wrap = fn ($column) => '`' . str_replace('`', '``', $column) . '`';
    $amountExpression = $amountCol ? 'CAST(REPLACE(REPLACE(REPLACE(' . $wrap($amountCol) . ', "$", ""), ",", ""), " ", "") AS DECIMAL(20,2))' : '0';

    $query = DB::table($table);

    if ($request->filled('q')) {
        $term = trim((string) $request->query('q'));
        $query->where(function ($sub) use ($term, $titleCol, $codeCol, $dependencyCol) {
            $sub->where($titleCol, 'like', "%{$term}%")
                ->orWhere($codeCol, 'like', "%{$term}%")
                ->orWhere($dependencyCol, 'like', "%{$term}%");
        });
    }

    if ($request->filled('status')) {
        $query->where($statusCol, $request->query('status'));
    }

    if ($request->filled('dependency')) {
        $query->where($dependencyCol, $request->query('dependency'));
    }

    if ($request->filled('state')) {
        $query->where($stateCol, $request->query('state'));
    }

    $from = $parseDate($request->query('from', $request->query('date_from')));
    $to = $parseDate($request->query('to', $request->query('date_to')));

    if ($from && $dateCol !== 'id') {
        $query->whereDate($dateCol, '>=', $from);
    }

    if ($to && $dateCol !== 'id') {
        $query->whereDate($dateCol, '<=', $to);
    }

    $filtered = clone $query;

    $procedures = (clone $query)
        ->selectRaw($wrap($titleCol) . ' as title')
        ->selectRaw($wrap($codeCol) . ' as code')
        ->selectRaw($wrap($statusCol) . ' as status')
        ->selectRaw($wrap($dependencyCol) . ' as dependency')
        ->selectRaw($wrap($stateCol) . ' as state')
        ->selectRaw($wrap($dateCol) . ' as published_at')
        ->selectRaw($amountExpression . ' as amount')
        ->when($dateCol !== 'id', fn ($q) => $q->orderByDesc($dateCol))
        ->paginate(20)
        ->withQueryString();

    return view('projects.search', [
        'procedures' => $procedures,
        'stats' => [
            'total_procedures' => (clone $filtered)->count(),
            'awarded_procedures' => (clone $filtered)->where($statusCol, 'like', '%ADJUDIC%')->count(),
            'active_procedures' => (clone $filtered)->where($statusCol, 'like', '%VIGENTE%')->count(),
            'total_amount' => (float) (clone $filtered)->selectRaw('COALESCE(SUM(' . $amountExpression . '), 0) as aggregate')->value('aggregate'),
            'distinct_dependencies' => (clone $filtered)->whereNotNull($dependencyCol)->distinct()->count($dependencyCol),
        ],
        'topDependencies' => (clone $filtered)
            ->selectRaw($wrap($dependencyCol) . ' as name')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('COALESCE(SUM(' . $amountExpression . '), 0) as amount')
            ->whereNotNull($dependencyCol)
            ->groupBy($dependencyCol)
            ->orderByDesc('amount')
            ->limit(8)
            ->get(),
        'statusOptions' => DB::table($table)->select($statusCol)->distinct()->pluck($statusCol)->filter()->sort()->values(),
        'dependencyOptions' => DB::table($table)->select($dependencyCol)->distinct()->pluck($dependencyCol)->filter()->sort()->values(),
        'stateOptions' => DB::table($table)->select($stateCol)->distinct()->pluck($stateCol)->filter()->sort()->values(),
    ]);
}

}
