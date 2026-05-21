<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectChatMessage;
use App\Services\AzureDocumentIntelligenceService;
use App\Services\OpenAiStructurerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectBoardController extends Controller
{
    /**
     * Configuración fija de columnas (etapas del board).
     */
    protected array $columnsConfig = [
        1 => ['name' => 'Análisis de Bases', 'color' => 'blue'],
        2 => ['name' => 'Revisión',          'color' => 'orange'],
        3 => ['name' => 'Participa',         'color' => 'green'],
        4 => ['name' => 'No participa',      'color' => 'red'],
        5 => ['name' => 'Ganado',            'color' => 'purple'],
        6 => ['name' => 'Perdido',           'color' => 'gray'],
        7 => ['name' => 'Desierta',          'color' => 'rose'],
    ];

    public function index(Request $request)
    {
        $query = Project::query()->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('assigned_to', 'like', "%{$q}%");
            });
        }

        $allProjects = $query->get();

        $columns = collect($this->columnsConfig)
            ->map(function ($conf, $id) use ($allProjects) {
                $items = $allProjects->where('column_id', $id)->values();

                return [
                    'id'    => $id,
                    'name'  => $conf['name'],
                    'color' => $conf['color'],
                    'count' => $items->count(),
                    'projects' => $items->map(function ($p) {
                        return [
                            'id'         => $p->id,
                            'slug'       => $p->slug,
                            'name'       => $p->name,
                            'priority'   => $p->priority,
                            'start_date' => optional($p->start_date)->format('Y-m-d'),
                            'assigned'   => $p->assigned_to ?: '·',
                            'labels'     => $p->labels ?: [],
                            'starred'    => (bool) $p->favorite,
                            'status'     => $p->status,
                        ];
                    })->all(),
                ];
            })
            ->values();

        return view('projects.index', [
            'columns'      => $columns,
            'openColumnId' => (int) $request->get('open', 1),
        ]);
    }

    /**
     * Crear proyecto desde el modal.
     * Procesa los PDFs SINCRÓNICAMENTE (cambiar a job si tarda mucho).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'              => ['nullable', 'string', 'max:255'],
            'start_date'        => ['nullable', 'date'],
            'color'             => ['nullable', 'string', 'max:20'],
            'favorite'          => ['nullable'],
            'without_documents' => ['nullable'],
            'documents'         => ['nullable', 'array', 'max:9'],
            'documents.*'       => ['file', 'mimes:pdf,doc,docx', 'max:51200'],
        ], [
            'documents.max'   => 'Solo puedes subir un máximo de 9 archivos.',
            'documents.*.mimes' => 'Solo se permiten archivos PDF, DOC o DOCX.',
        ]);

        $name = $request->input('name') ?: 'Mi proyecto ' . now()->format('Y-m-d H:i:s');
        $withoutDocs = $request->boolean('without_documents');

        $project = DB::transaction(function () use ($request, $name, $withoutDocs) {
            $project = Project::create([
                'name'       => $name,
                'slug'       => Str::slug($name) . '-' . Str::lower(Str::random(6)),
                'user_id'    => auth()->id(),
                'column_id'  => 1,
                'start_date' => $request->input('start_date') ?: now()->toDateString(),
                'color'      => $request->input('color'),
                'favorite'   => $request->boolean('favorite'),
                'status'     => $withoutDocs ? 'ready' : 'processing',
            ]);

            if (!$withoutDocs && $request->hasFile('documents')) {
                foreach ($request->file('documents') as $file) {
                    $path = $file->store("projects/{$project->id}", 'public');

                    $project->documents()->create([
                        'filename'  => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'status'    => 'pending',
                    ]);
                }
            }

            return $project;
        });

        // Procesar (Azure + OpenAI) si hay documentos
        if (!$withoutDocs) {
            try {
                @ini_set('max_execution_time', 600);
                $this->processProject($project);
            } catch (\Throwable $e) {
                \Log::error('Project processing failed', ['project_id' => $project->id, 'error' => $e->getMessage()]);
                $project->update([
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        $redirect = route('projects.show', $project);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'project_id' => $project->id,
                'redirect_url' => $redirect,
            ]);
        }

        return redirect()->to($redirect);
    }

    /**
     * Vista de detalle con tabs.
     */
    public function show(Project $project)
    {
        $project->load(['documents', 'chatMessages']);

        return view('projects.show', compact('project'));
    }

    /**
     * Endpoint para chat (tab Análisis de Bases).
     */
    public function chat(Request $request, Project $project)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $project->loadMissing('documents');

        $rawText = $project->documents
            ->pluck('extracted_text')
            ->filter()
            ->implode("\n\n--- DOCUMENTO ---\n\n");

        if (!$rawText) {
            return response()->json([
                'ok' => false,
                'message' => 'Este proyecto no tiene documentos procesados.',
            ], 422);
        }

        // Guardar mensaje del usuario
        $userMsg = $project->chatMessages()->create([
            'user_id' => auth()->id(),
            'role'    => 'user',
            'content' => $data['message'],
        ]);

        // Historial reciente para contexto
        $history = $project->chatMessages()
            ->where('id', '<', $userMsg->id)
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        try {
            $reply = app(OpenAiStructurerService::class)
                ->chat($rawText, $history, $data['message']);
        } catch (\Throwable $e) {
            $reply = 'Ocurrió un error consultando el asistente. Intenta de nuevo.';
            \Log::error('Project chat failed', ['error' => $e->getMessage()]);
        }

        $assistantMsg = $project->chatMessages()->create([
            'role'    => 'assistant',
            'content' => $reply,
        ]);

        return response()->json([
            'ok' => true,
            'user_message'      => ['id' => $userMsg->id, 'content' => $userMsg->content, 'time' => $userMsg->created_at->format('H:i')],
            'assistant_message' => ['id' => $assistantMsg->id, 'content' => $assistantMsg->content, 'time' => $assistantMsg->created_at->format('H:i')],
        ]);
    }

    /**
     * Guardar borrador (WYSIWYG).
     */
    public function saveDraft(Request $request, Project $project)
    {
        $data = $request->validate([
            'draft_content' => ['nullable', 'string'],
        ]);

        $project->update(['draft_content' => $data['draft_content'] ?? null]);

        return response()->json(['ok' => true]);
    }

    /**
     * Reset chat.
     */
    public function resetChat(Project $project)
    {
        $project->chatMessages()->delete();
        return response()->json(['ok' => true]);
    }

    // =============== Procesamiento Azure + OpenAI ===============

    protected function processProject(Project $project): void
    {
        $azure = app(AzureDocumentIntelligenceService::class);
        $project->loadMissing('documents');

        foreach ($project->documents as $doc) {
            try {
                $doc->update(['status' => 'processing']);

                $publicUrl = asset(Storage::url($doc->file_path));
                $result = $azure->analyzeLayoutFromUrl($publicUrl);

                $content = data_get($result, 'analyzeResult.content', '');

                $doc->update([
                    'status'         => 'done',
                    'extracted_text' => $content,
                    'extracted_raw'  => $result['analyzeResult'] ?? null,
                    'processed_at'   => now(),
                ]);
            } catch (\Throwable $e) {
                $doc->update([
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        // Combinar todo y estructurar con OpenAI
        $combined = $project->documents()
            ->where('status', 'done')
            ->pluck('extracted_text')
            ->filter()
            ->implode("\n\n--- DOCUMENTO ---\n\n");

        if (!$combined) {
            $project->update(['status' => 'error', 'error_message' => 'No se pudo extraer texto de ningún documento.']);
            return;
        }

        try {
            $structured = app(OpenAiStructurerService::class)->structureProject($combined);

            $project->update([
                'structured_data' => $structured,
                'checklist'       => $structured['checklist_sugerido'] ?? [],
                'status'          => 'ready',
            ]);
        } catch (\Throwable $e) {
            $project->update(['status' => 'partial', 'error_message' => $e->getMessage()]);
        }
    }
}