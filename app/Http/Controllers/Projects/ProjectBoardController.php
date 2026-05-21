<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectChatMessage;
use App\Models\User;
use App\Services\PythonProjectProcessor;
use App\Services\OpenAiStructurerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectBoardController extends Controller
{
    /* ============================================================
     |  INDEX
     * ============================================================ */
    public function index()
    {
        $projects = Project::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    /* ============================================================
     |  STORE  (crea proyecto + sube docs + dispara extracción)
     * ============================================================ */
    public function store(Request $request, PythonProjectProcessor $processor)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'files'     => 'required|array|min:1|max:9',
            'files.*'   => 'file|mimes:pdf,docx,doc|max:25600', // 25 MB
        ]);

        $project = Project::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name).'-'.Str::random(6),
            'user_id'     => Auth::id(),
            'status'      => 'procesando',
            'priority'    => $request->priority ?? 'media',
            'color'       => $request->color    ?? '#1e3a5f',
        ]);

        // Guardar documentos
        $paths = [];
        foreach ($request->file('files') as $file) {
            $stored = $file->store("projects/{$project->id}/source", 'public');
            $doc = ProjectDocument::create([
                'project_id'  => $project->id,
                'filename'    => $file->getClientOriginalName(),
                'file_path'   => $stored,
                'mime_type'   => $file->getMimeType(),
                'file_size'   => $file->getSize(),
                'status'      => 'pendiente',
            ]);
            $paths[] = storage_path('app/public/'.$stored);
        }

        // Procesar con Python
        try {
            $result = $processor->process($project, $paths);
            $project->structured_data = $result['structured_data'] ?? null;
            $project->checklist       = data_get($result, 'structured_data.checklist_sugerido', []);
            $project->status          = 'listo';
            $project->save();

            // Marcar documentos como procesados
            ProjectDocument::where('project_id', $project->id)
                ->update(['status' => 'procesado', 'processed_at' => now()]);

        } catch (\Throwable $e) {
            Log::error('Project processing failed', ['project'=>$project->id,'error'=>$e->getMessage()]);
            $project->status        = 'error';
            $project->error_message = $e->getMessage();
            $project->save();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'redirect' => route('projects.show', $project),
                'project'  => $project,
            ]);
        }
        return redirect()->route('projects.show', $project);
    }

    /* ============================================================
     |  SHOW
     * ============================================================ */
    public function show(Project $project)
    {
        abort_if($project->user_id !== Auth::id() && Auth::id() !== 1, 403);

        $documents = ProjectDocument::where('project_id', $project->id)->get();
        $messages  = ProjectChatMessage::where('project_id', $project->id)
                        ->orderBy('id')
                        ->get();
        $users     = User::orderBy('name')->get(['id','name']);

        return view('projects.show', compact('project','documents','messages','users'));
    }

    /* ============================================================
     |  CHAT
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

        // Construir contexto: estructura del proyecto + últimas N interacciones
        $history = ProjectChatMessage::where('project_id', $project->id)
            ->orderBy('id','desc')->take(12)->get()->reverse()->values();

        $systemContext = "Eres un asistente experto en licitaciones públicas mexicanas. "
            . "Estás analizando el proyecto: \"{$project->name}\".\n\n"
            . "Datos estructurados del proyecto (JSON):\n"
            . json_encode($project->structured_data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n\n"
            . "Cuando la respuesta sea comparativa, listada o tabular, devuélvela en formato de tabla Markdown "
            . "(con encabezado y separador `|---|`). Usa lenguaje claro y profesional.";

        $messages = [['role'=>'system','content'=>$systemContext]];
        foreach ($history as $m) {
            $messages[] = ['role'=>$m->role, 'content'=>$m->content];
        }

        try {
            $reply = $ai->chat($messages);
        } catch (\Throwable $e) {
            Log::error('Chat error', ['err'=>$e->getMessage()]);
            $reply = 'Lo siento, hubo un error al procesar tu pregunta: '.$e->getMessage();
        }

        ProjectChatMessage::create([
            'project_id' => $project->id,
            'user_id'    => Auth::id(),
            'role'       => 'assistant',
            'content'    => $reply,
        ]);

        return response()->json(['reply' => $reply]);
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
        $request->validate(['draft' => 'nullable|string']);
        $project->draft_content = $request->draft;
        $project->save();
        return response()->json(['ok' => true]);
    }

    /* ============================================================
     |  CHECKLIST
     * ============================================================ */
    public function updateChecklist(Request $request, Project $project)
    {
        $request->validate(['checklist' => 'present|array']);
        $project->checklist = $request->checklist;
        $project->save();
        return response()->json(['ok' => true]);
    }

    public function attachChecklist(Request $request, Project $project)
    {
        $request->validate([
            'files'   => 'required|array',
            'files.*' => 'file|max:20480',
            'item_id' => 'required|string',
        ]);

        $saved = [];
        foreach ($request->file('files', []) as $file) {
            $path = $file->store("projects/{$project->id}/checklist", 'public');
            $saved[] = [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'url'  => asset('storage/'.$path),
                'path' => $path,
                'mime' => $file->getMimeType(),
            ];
        }
        return response()->json(['adjuntos' => $saved]);
    }

    public function reanalyzeChecklist(Project $project, PythonProjectProcessor $processor)
    {
        try {
            $paths = ProjectDocument::where('project_id', $project->id)
                ->get()
                ->map(fn($d) => storage_path('app/public/'.$d->file_path))
                ->filter(fn($p) => file_exists($p))
                ->values()
                ->all();

            if (empty($paths)) {
                return response()->json(['error' => 'No hay documentos para reanalizar.'], 422);
            }

            $result = $processor->process($project, $paths);
            $newChk = data_get($result, 'structured_data.checklist_sugerido', []);

            // Mezclar: conservar adjuntos/notas/responsables del antiguo cuando coincida requisito
            $old = collect($project->checklist ?? [])->keyBy(function($it){
                return strtolower(trim($it['requisito'] ?? ''));
            });
            $merged = collect($newChk)->map(function($item) use ($old){
                $key = strtolower(trim($item['requisito'] ?? ''));
                if ($old->has($key)) {
                    $prev = $old->get($key);
                    foreach (['notas','responsable_id','revisor_id','fecha_limite','adjuntos','prioridad','cumplimiento','status'] as $k) {
                        if (!empty($prev[$k]) && empty($item[$k])) {
                            $item[$k] = $prev[$k];
                        }
                    }
                }
                return $item;
            })->all();

            $project->checklist = $merged;
            if (!empty($result['structured_data'])) {
                $sd = $project->structured_data ?? [];
                $sd = array_merge($sd, $result['structured_data']);
                $project->structured_data = $sd;
            }
            $project->save();

            return response()->json(['checklist' => $merged]);
        } catch (\Throwable $e) {
            Log::error('Reanalyze checklist failed', ['err'=>$e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /* ============================================================
     |  REPORTE
     * ============================================================ */
    public function generateReport(Project $project, OpenAiStructurerService $ai)
    {
        try {
            $context = json_encode($project->structured_data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            $checklist = json_encode($project->checklist, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);

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
                . "Aplica estilos inline en <table> (border-collapse, padding, border #e5e7eb, encabezado azul #1e3a5f con texto blanco).";

            $messages = [
                ['role'=>'system','content'=>'Eres un generador de reportes HTML profesionales.'],
                ['role'=>'user','content'=>$prompt],
            ];

            $html = $ai->chat($messages);
            // Limpieza por si vienen fences
            $html = preg_replace('/^```html\s*/i','', trim($html));
            $html = preg_replace('/```$/','', trim($html));

            $project->report_content = $html;
            $project->save();

            return response()->json(['html' => $html]);
        } catch (\Throwable $e) {
            Log::error('Report generation failed', ['err'=>$e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}