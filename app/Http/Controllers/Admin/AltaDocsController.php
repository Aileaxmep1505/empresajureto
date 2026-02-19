<?php
// app/Http/Controllers/Admin/AltaDocsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AltaDoc;
use App\Models\AgendaEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AltaDocsController extends Controller
{
    /**
     * Tamaño máximo permitido por archivo (EN KB)
     * - 512000  = 500 MB
     * - 1048576 = 1 GB
     */
    private int $maxFileKb = 512000;

    public function __construct()
    {
        $env = (int) env('ALTA_DOCS_MAX_KB', 0);
        if ($env > 0) $this->maxFileKb = $env;
    }

    public function showPinForm()
    {
        return view('secure.alta_docs_pin');
    }

    public function checkPin(Request $request)
    {
        $user = $request->user();
        if (!$user) abort(403);

        $data = $request->validate([
            'pin' => ['required', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'Debes capturar tu NIP.',
            'pin.regex'    => 'El NIP debe tener exactamente 6 dígitos numéricos.',
        ]);

        $enteredPin = trim((string) $data['pin']);

        Log::info('AltaDocs: intento de NIP (por usuario)', [
            'user_id'      => $user->id,
            'ip'           => $request->ip(),
            'has_pin_hash' => !empty($user->approval_pin_hash),
        ]);

        if (empty($user->approval_pin_hash)) {
            Log::warning('AltaDocs: usuario sin NIP configurado', [
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ]);

            return back()
                ->with('warning', 'Aún no tienes NIP configurado. Ve a tu perfil y configúralo.')
                ->withInput();
        }

        if (!$user->checkApprovalPin($enteredPin)) {
            Log::warning('AltaDocs: NIP inválido (por usuario)', [
                'user_id' => $user->id,
                'ip'      => $request->ip(),
            ]);

            return back()
                ->withErrors(['pin' => 'NIP incorrecto'])
                ->withInput();
        }

        $request->session()->put('alta_docs_unlocked', true);
        $request->session()->put('alta_docs_unlocked_user_id', $user->id);
        $request->session()->put('alta_docs_unlocked_at', now()->toDateTimeString());

        Log::info('AltaDocs: NIP correcto, sesión desbloqueada', [
            'user_id' => $user->id,
            'ip'      => $request->ip(),
        ]);

        return redirect()
            ->route('alta.docs.index')
            ->with('ok', 'Acceso a documentación confidencial habilitado.');
    }

    public function logoutPin(Request $request)
    {
        $request->session()->forget('alta_docs_unlocked');
        $request->session()->forget('alta_docs_unlocked_user_id');
        $request->session()->forget('alta_docs_unlocked_at');

        Log::info('AltaDocs: sesión de documentación cerrada', [
            'user_id' => $request->user()->id ?? null,
            'ip'      => $request->ip(),
        ]);

        return redirect()
            ->route('secure.alta-docs.pin.show')
            ->with('ok', 'Sesión de documentación cerrada.');
    }

    /**
     * INDEX con filtros:
     * - q (buscador)
     * - category
     */
    public function index(Request $request)
    {
        $q        = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $query = AltaDoc::query()->orderByDesc('id');

        if ($category !== '' && in_array($category, AltaDoc::CATEGORIES, true)) {
            $query->where('category', $category);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%")
                    ->orWhere('original_name', 'like', "%{$q}%");
            });
        }

        $docs = $query->paginate(15)->appends([
            'q'        => $q,
            'category' => $category,
        ]);

        return view('secure.alta_docs_index', [
            'docs'      => $docs,
            'q'         => $q,
            'category'  => $category,
            'catLabels' => AltaDoc::categoryLabels(),
        ]);
    }

    /**
     * SHOW (detalle)
     * - Semáforo: amarillo desde 30 días (1 mes) antes.
     */
    public function show(AltaDoc $doc)
    {
        $expiryRaw  = $doc->expires_at ?? null;
        $expiryDate = $expiryRaw ? Carbon::parse($expiryRaw) : null;

        [$semaforo, $daysToExpire] = $this->computeSemaforo($expiryDate);

        $linkUrl  = $doc->link_url ?? null;
        $linkPass = $doc->link_password ?? null;

        $diskName = $doc->disk ?: 'local';
        $disk     = Storage::disk($diskName);

        $exists = $doc->path ? $disk->exists($doc->path) : false;

        $filename = $doc->original_name ?? basename((string) $doc->path);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $mime = $doc->mime ?: ($exists ? ($disk->mimeType($doc->path) ?: 'application/octet-stream') : 'application/octet-stream');

        $isPdf   = ($ext === 'pdf') || str_contains((string)$mime, 'pdf');
        $isImage = str_starts_with((string)$mime, 'image/');
        $isVideo = str_starts_with((string)$mime, 'video/');

        return view('secure.alta_docs_show', [
            'doc'          => $doc,
            'exists'       => $exists,
            'mime'         => $mime,
            'ext'          => $ext,
            'isPdf'        => $isPdf,
            'isImage'      => $isImage,
            'isVideo'      => $isVideo,
            'semaforo'     => $semaforo,
            'daysToExpire' => $daysToExpire,
            'linkUrl'      => $linkUrl,
            'linkPass'     => $linkPass,
        ]);
    }

    public function store(Request $request)
    {
        try {
            Log::info('AltaDocs DEBUG upload', [
                'has_files'    => $request->hasFile('files'),
                'content_type' => $request->header('Content-Type'),
                'php_fileinfo' => extension_loaded('fileinfo'),
                'files_count'  => is_array($request->file('files')) ? count($request->file('files')) : null,
                'user_id'      => $request->user()->id ?? null,
                'ip'           => $request->ip(),
                'max_kb'       => $this->maxFileKb,
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $i => $f) {
                    if (!$f) continue;
                    Log::info("AltaDocs DEBUG file #{$i}", [
                        'name'        => $f->getClientOriginalName(),
                        'client_ext'  => $f->getClientOriginalExtension(),
                        'client_mime' => $f->getClientMimeType(),
                        'server_mime' => $f->getMimeType(),
                        'guess_ext'   => $f->guessExtension(),
                        'size'        => $f->getSize(),
                        'is_valid'    => $f->isValid(),
                    ]);
                }
            }

            $data = $request->validate([
                'category'      => ['required', 'in:' . implode(',', AltaDoc::CATEGORIES)],
                'title'         => ['required', 'string', 'max:160'],
                'doc_date'      => ['required', 'date'],

                'expires_at'    => ['nullable', 'date'],
                'link_url'      => ['nullable', 'string', 'max:500'],
                'link_password' => ['nullable', 'string', 'max:180'],

                'files'         => ['required', 'array', 'min:1'],
                'files.*'       => ['required', 'file', 'max:' . $this->maxFileKb],

                'notes'         => ['nullable', 'string', 'max:500'],
            ], [
                'category.required' => 'Selecciona un tipo.',
                'category.in'       => 'El tipo seleccionado no es válido.',
                'title.required'    => 'Captura un título.',
                'doc_date.required' => 'Selecciona una fecha.',
                'files.required'    => 'Debes seleccionar al menos un archivo.',
                'files.min'         => 'Debes seleccionar al menos un archivo.',
                'files.*.required'  => 'Debes seleccionar al menos un archivo válido.',
                'files.*.file'      => 'Uno de los archivos no es válido.',
                'files.*.max'       => 'Uno de los archivos excede el tamaño permitido.',
                'expires_at.date'   => 'La vigencia (vencimiento) no es válida.',
                'link_url.max'      => 'El enlace es demasiado largo.',
                'link_password.max' => 'La contraseña es demasiado larga.',
            ]);

            $files = $request->file('files', []);
            if (empty($files)) {
                return back()->withErrors(['files' => 'Debes seleccionar al menos un archivo.'])->withInput();
            }

            $disk = 'local';
            $created = 0;

            foreach ($files as $file) {
                if (!$file) continue;

                if (!$file->isValid()) {
                    return back()->withErrors(['files' => 'Archivo inválido o corrupto.'])->withInput();
                }

                $path = $file->store('alta_docs', $disk);

                $doc = AltaDoc::create([
                    'category'      => $data['category'],
                    'title'         => $data['title'],
                    'doc_date'      => $data['doc_date'],

                    'expires_at'    => $data['expires_at'] ?? null,
                    'link_url'      => $data['link_url'] ?? null,
                    'link_password' => $data['link_password'] ?? null,

                    'original_name' => $file->getClientOriginalName(),
                    'stored_name'   => basename($path),
                    'disk'          => $disk,
                    'path'          => $path,

                    'mime'          => $file->getMimeType() ?: ($file->getClientMimeType() ?: 'application/octet-stream'),
                    'size'          => $file->getSize(),
                    'notes'         => $data['notes'] ?? null,
                    'uploaded_by'   => $request->user()->id ?? null,
                ]);

                $created++;

                // ✅ Agenda: crear/actualizar recordatorios 30d y 7d antes
                $this->syncAgendaForExpiry($doc, $request);

                Log::info('AltaDocs: documento subido', [
                    'doc_id'  => $doc->id,
                    'file'    => $doc->original_name,
                    'type'    => $doc->category,
                    'user_id' => $request->user()->id ?? null,
                    'ip'      => $request->ip(),
                    'mime'    => $doc->mime,
                    'size'    => $doc->size,
                ]);
            }

            return redirect()
                ->route('alta.docs.index')
                ->with('ok', "Se cargaron {$created} documento(s) correctamente.");
        } catch (\Throwable $e) {
            Log::error('AltaDocs: error al subir documentos', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return back()
                ->with('error', 'No se pudo subir el documento. Revisa el log del servidor.')
                ->withInput();
        }
    }

    public function download(AltaDoc $doc)
    {
        $diskName = $doc->disk ?: 'local';
        $disk = Storage::disk($diskName);

        if (!$doc->path || !$disk->exists($doc->path)) {
            return back()->with('error', 'El archivo ya no existe en el servidor.');
        }

        Log::info('AltaDocs: descarga de documento', [
            'doc_id'  => $doc->id,
            'file'    => $doc->original_name,
            'user_id' => auth()->id(),
            'ip'      => request()->ip(),
        ]);

        return $disk->download($doc->path, $doc->original_name);
    }

    public function destroy(Request $request, AltaDoc $doc)
    {
        try {
            // ✅ borrar eventos agenda ligados
            $this->deleteAgendaForDoc($doc);

            $diskName = $doc->disk ?: 'local';
            $disk = Storage::disk($diskName);

            if ($doc->path && $disk->exists($doc->path)) {
                $disk->delete($doc->path);
            }

            $docId   = $doc->id;
            $docName = $doc->original_name;

            $doc->delete();

            Log::warning('AltaDocs: documento eliminado', [
                'doc_id'  => $docId,
                'file'    => $docName,
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return redirect()
                ->route('alta.docs.index')
                ->with('ok', 'Documento eliminado correctamente.');
        } catch (\Throwable $e) {
            Log::error('AltaDocs: error al eliminar documento', [
                'doc_id'  => $doc->id,
                'error'   => $e->getMessage(),
                'user_id' => $request->user()->id ?? null,
                'ip'      => $request->ip(),
            ]);

            return back()->with('error', 'No se pudo eliminar el documento.');
        }
    }

    /**
     * PREVIEW (inline)
     */
    public function preview(Request $request, AltaDoc $doc)
    {
        $diskName = $doc->disk ?: 'local';
        $disk = Storage::disk($diskName);

        if (!$doc->path || !$disk->exists($doc->path)) {
            abort(404);
        }

        $filename = $doc->original_name ?? basename((string) $doc->path);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $mime = $doc->mime ?: ($disk->mimeType($doc->path) ?: 'application/octet-stream');

        $isGeneric = in_array(strtolower((string)$mime), ['application/octet-stream', 'binary/octet-stream'], true);

        if ($isGeneric) {
            $map = [
                'pdf'  => 'application/pdf',
                'png'  => 'image/png',
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'gif'  => 'image/gif',
                'webp' => 'image/webp',
                'svg'  => 'image/svg+xml',
                'mp4'  => 'video/mp4',
                'webm' => 'video/webm',
                'mov'  => 'video/quicktime',
                'mkv'  => 'video/x-matroska',
            ];
            if (isset($map[$ext])) {
                $mime = $map[$ext];
            }
        }

        $absolutePath = $disk->path($doc->path);
        $safeName = str_replace(['"', "\n", "\r"], '', (string) $filename);

        return response()->file($absolutePath, [
            'Content-Type'              => $mime,
            'Content-Disposition'       => 'inline; filename="' . $safeName . '"',
            'X-Content-Type-Options'    => 'nosniff',
            'Cache-Control'             => 'private, max-age=0, no-store, no-cache, must-revalidate',
            'Pragma'                    => 'no-cache',
        ]);
    }

    /**
     * Semáforo:
     * - bad  : vencido (< 0)
     * - warn : desde 30 días antes (<= 30)  ✅ 1 mes antes
     * - ok   : > 30
     * - none : sin vigencia
     */
    private function computeSemaforo(?Carbon $expiryDate): array
    {
        if (!$expiryDate) return ['none', null];

        $now = Carbon::now()->startOfDay();
        $exp = $expiryDate->copy()->startOfDay();

        $daysToExpire = $now->diffInDays($exp, false);

        if ($daysToExpire < 0) return ['bad', $daysToExpire];
        if ($daysToExpire <= 30) return ['warn', $daysToExpire]; // ✅ 1 mes antes
        return ['ok', $daysToExpire];
    }

    /**
     * Crea / actualiza en Agenda:
     * - 30 días antes
     * - 7 días antes
     *
     * Se amarra por title tag:
     *  [ALTA_DOC:{id}:30D] y [ALTA_DOC:{id}:7D]
     */
    private function syncAgendaForExpiry(AltaDoc $doc, Request $request): void
    {
        try {
            $expiryRaw = $doc->expires_at ?? null;
            $expiry = $expiryRaw ? Carbon::parse($expiryRaw)->startOfDay() : null;

            // si no hay vigencia, borramos eventos ligados y salimos
            if (!$expiry) {
                $this->deleteAgendaForDoc($doc);
                return;
            }

            $userId = $request->user()->id ?? null;
            if (!$userId) return; // sin usuario logueado, no creamos agenda

            $tz = 'America/Mexico_City';

            // hora default del evento (09:00)
            $at30 = $expiry->copy()->subDays(30)->setTime(9, 0, 0);
            $at7  = $expiry->copy()->subDays(7)->setTime(9, 0, 0);

            // si ya pasó la fecha del recordatorio, igual lo guardamos (tú decides),
            // aquí lo guardo de todos modos por trazabilidad.
            $docTitle = $doc->title ?: ($doc->original_name ?: 'Documento');
            $showUrl = route('alta.docs.show', $doc);

            $baseDesc =
                "Documento: {$docTitle}\n".
                "Vence: ".$expiry->format('Y-m-d')."\n".
                "Ver: {$showUrl}";

            $this->upsertAgendaEvent(
                title: "Vigencia por vencer (30 días) [ALTA_DOC:{$doc->id}:30D] {$docTitle}",
                description: $baseDesc,
                startAt: $at30,
                tz: $tz,
                userId: (int)$userId
            );

            $this->upsertAgendaEvent(
                title: "Vigencia por vencer (7 días) [ALTA_DOC:{$doc->id}:7D] {$docTitle}",
                description: $baseDesc,
                startAt: $at7,
                tz: $tz,
                userId: (int)$userId
            );
        } catch (\Throwable $e) {
            Log::warning('AltaDocs: no se pudo sincronizar agenda', [
                'doc_id' => $doc->id ?? null,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    private function upsertAgendaEvent(string $title, string $description, Carbon $startAt, string $tz, int $userId): void
    {
        // estos campos existen según tu AgendaEventController
        $payload = [
            'title'                 => $title,
            'description'           => $description,
            'start_at'              => $startAt->copy()->setTimezone($tz)->format('Y-m-d H:i:s'),
            'timezone'              => $tz,
            'repeat_rule'           => 'none',
            'remind_offset_minutes' => 60,     // 1 hora antes (puedes cambiar)
            'user_ids'              => [$userId],
            'send_email'            => true,
            'send_whatsapp'         => true,
        ];

        $event = AgendaEvent::query()->where('title', $title)->first();

        if ($event) {
            $event->fill($payload);
        } else {
            $event = new AgendaEvent($payload);
        }

        // tu modelo tiene computeNextReminder()
        if (method_exists($event, 'computeNextReminder')) {
            $event->computeNextReminder();
        }

        $event->save();
    }

    private function deleteAgendaForDoc(AltaDoc $doc): void
    {
        try {
            $id = $doc->id;
            if (!$id) return;

            AgendaEvent::query()
                ->where('title', 'like', "%[ALTA_DOC:{$id}:%")
                ->delete();
        } catch (\Throwable $e) {
            Log::warning('AltaDocs: no se pudo borrar agenda ligada', [
                'doc_id' => $doc->id ?? null,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
