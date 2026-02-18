<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentSection;
use App\Models\DocumentSubtype;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class PartContableController extends Controller
{
    /**
     * ✅ Minutos de desbloqueo por sesión (por compañía)
     */
    private int $pinTtlMinutes = 30;

    private function pinSessionKey(int $companyId): string
    {
        return "pc_pin_unlocked_{$companyId}";
    }

    private function welcomeSessionKey(int $companyId): string
    {
        return "pc_welcome_{$companyId}";
    }

    private function isPinUnlocked(int $companyId): bool
    {
        $until = session($this->pinSessionKey($companyId));
        if (!$until) return false;

        $untilTs = is_numeric($until) ? (int) $until : strtotime((string) $until);
        return $untilTs && $untilTs >= now()->timestamp;
    }

    private function setPinUnlocked(int $companyId): void
    {
        session([
            $this->pinSessionKey($companyId) => now()->addMinutes($this->pinTtlMinutes)->timestamp
        ]);
    }

    private function emptyPaginator(Request $request, int $perPage = 12): LengthAwarePaginator
    {
        $page = (int) $request->get('page', 1);

        $p = new LengthAwarePaginator([], 0, $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        return $p->appends($request->query());
    }

    /**
     * ✅ Obtiene PIN/NIP del usuario (incluye tu columna real)
     */
    private function getUserPinValue($user): ?string
    {
        $candidates = [
            'approval_pin_hash', // ✅ tu columna real
            'pin', 'nip',
            'pin_code', 'nip_code',
            'pin_hash', 'nip_hash',
            'security_pin', 'security_pin_hash',
        ];

        foreach ($candidates as $col) {
            if (isset($user->{$col}) && $user->{$col} !== null && $user->{$col} !== '') {
                return (string) $user->{$col};
            }
        }

        return null;
    }

    private function normalizePin(string $value): string
    {
        $value = trim($value);
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function looksHashed(string $stored): bool
    {
        $stored = trim($stored);

        if (preg_match('/^\$2[aby]\$/', $stored)) return true; // bcrypt
        if (str_starts_with($stored, '$argon2i$') || str_starts_with($stored, '$argon2id$')) return true;
        if (str_starts_with($stored, '$') && strlen($stored) > 20) return true;

        return false;
    }

    private function verifyPin(string $inputRaw, string $storedRaw): bool
    {
        $input  = $this->normalizePin($inputRaw);
        $stored = trim((string) $storedRaw);

        if ($input === '' || $stored === '') return false;

        if ($this->looksHashed($stored)) {
            return Hash::check($input, $stored);
        }

        $storedNormalized = $this->normalizePin($stored);

        if (hash_equals($storedNormalized, $input)) {
            return true;
        }

        if (ctype_digit($storedNormalized) && ctype_digit($input)) {
            return ((int) $storedNormalized) === ((int) $input);
        }

        return false;
    }

    /**
     * ✅ LOG CENTRAL: guarda actividad del usuario
     */
    private function logActivity(Request $request, string $action, ?int $companyId = null, ?int $documentId = null, array $meta = []): void
    {
        try {
            UserActivity::create([
                'user_id'     => optional($request->user())->id,
                'company_id'  => $companyId,
                'document_id' => $documentId,
                'action'      => $action,
                'meta'        => $meta ?: null,
                'ip'          => $request->ip(),
                'user_agent'  => substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('UserActivity log failed: '.$e->getMessage());
        }
    }

    // ===============================
    // Index de empresas (grid)
    // ===============================
    public function index()
    {
        $companies = Company::all();
        return view('partcontable.index', compact('companies'));
    }

    // ===============================
    // Mostrar formulario para subir
    // ===============================
    public function createDocument(Company $company)
    {
        $sections = DocumentSection::with('subtypes')->orderBy('name')->get();
        $subtypes = DocumentSubtype::orderBy('name')->get();
        $defaultSection = $sections->first();

        return view('partcontable.create', compact('company', 'sections', 'subtypes', 'defaultSection'));
    }

    // ============================================================
    // ✅ Vista de empresa con documentos filtrados (PIDE PIN ANTES)
    // ============================================================
    public function showCompany(Request $request, Company $company)
    {
        if (!$this->isPinUnlocked($company->id)) {
            $redirectTo = $request->fullUrl();

            return view('partcontable.pin', [
                'company'    => $company,
                'redirectTo' => $redirectTo,
            ]);
        }

        $sections = DocumentSection::with('subtypes')->orderBy('name')->get();

        if ($sections->isEmpty()) {
            return view('partcontable.company', [
                'company'           => $company,
                'sections'          => $sections,
                'section'           => null,
                'documents'         => $this->emptyPaginator($request, 12),
                'subtypes'          => collect(),
                'year'              => $request->get('year'),
                'month'             => $request->get('month'),
                'currentSectionKey' => null,
                'currentSubKey'     => null,
                'currentSubLabel'   => '',
                'pinUnlocked'       => true,
            ])->with('warning', 'No hay secciones configuradas. Crea secciones en el panel.');
        }

        $sectionKey = $request->get('section', 'declaracion_anual');
        $section = $sections->firstWhere('key', $sectionKey) ?: $sections->first();

        $defaultSubtabBySection = [
            'declaracion_anual'   => 'acuse_anual',
            'declaracion_mensual' => 'acuse_mensual',
            'constancias'         => 'csf',
            'estados_financieros' => 'balance_general',

            // ✅ NUEVO: ISN-3%
            'isn_3'               => 'pago_isn',
        ];

        $sectionSubtypes = $section->subtypes->keyBy('key');

        $subtipoKey = $request->get(
            'subtipo',
            $defaultSubtabBySection[$section->key] ?? optional($sectionSubtypes->first())->key
        );

        if (!$subtipoKey || !$sectionSubtypes->has($subtipoKey)) {
            $subtipoKey = optional($sectionSubtypes->first())->key;
        }

        $currentSubtype = $sectionSubtypes->get($subtipoKey);

        $year  = $request->get('year');
        $month = $request->get('month');

        $query = Document::where('company_id', $company->id)
            ->where('section_id', $section->id);

        if ($currentSubtype) {
            $query->where('subtype_id', $currentSubtype->id);
        }

        if ($year)  $query->whereYear('date', $year);
        if ($month) $query->whereMonth('date', $month);

        // ✅ IMPORTANTE: ordena por date y fallback por created_at
        $documents = $query->orderByDesc('date')->orderByDesc('id')->paginate(12)->appends($request->query());
        $subtypes  = $sectionSubtypes->values();

        return view('partcontable.company', [
            'company'           => $company,
            'sections'          => $sections,
            'section'           => $section,
            'documents'         => $documents,
            'subtypes'          => $subtypes,
            'year'              => $year,
            'month'             => $month,
            'currentSectionKey' => $section->key,
            'currentSubKey'     => $subtipoKey,
            'currentSubLabel'   => $currentSubtype->name ?? '',
            'pinUnlocked'       => true,
        ]);
    }

    // ============================================================
    // ✅ VALIDAR PIN Y ENTRAR (registra acceso)
    // ============================================================
    public function unlockWithPin(Request $request, Company $company)
    {
        $request->validate([
            'pin'        => ['required', 'string', 'regex:/^\d{6}$/'],
            'redirectTo' => ['nullable', 'string'],
        ], [
            'pin.regex' => 'El NIP debe ser exactamente de 6 dígitos.',
        ]);

        $user = $request->user();
        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'No autenticado.'], 401)
                : redirect()->route('login');
        }

        $stored = $this->getUserPinValue($user);

        if (!$stored) {
            $msg = 'Tu usuario no tiene NIP configurado.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('warning', $msg);
        }

        $input = $this->normalizePin((string) $request->pin);

        // ✅ usa helper del modelo si existe
        if (method_exists($user, 'checkApprovalPin')) {
            if (!$user->checkApprovalPin($input)) {
                $this->logActivity($request, 'pc_unlock_failed', $company->id, null, ['reason' => 'invalid_pin']);

                $msg = 'NIP incorrecto.';
                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'message' => $msg], 422)
                    : back()->with('warning', $msg);
            }
        } else {
            if (!$this->verifyPin($input, $stored)) {
                $this->logActivity($request, 'pc_unlock_failed', $company->id, null, ['reason' => 'invalid_pin']);

                $msg = 'NIP incorrecto.';
                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'message' => $msg], 422)
                    : back()->with('warning', $msg);
            }
        }

        // ✅ Unlock por compañía
        $this->setPinUnlocked($company->id);

        // ✅ Guardar “bienvenida”
        session([
            $this->welcomeSessionKey($company->id) => [
                'at'      => now()->toIso8601String(),
                'user_id' => $user->id,
                'name'    => $user->name,
                'company' => $company->name,
            ],
        ]);

        // ✅ Log acceso OK
        $this->logActivity($request, 'pc_unlock', $company->id, null, [
            'user_name' => $user->name,
            'ttl_min'   => $this->pinTtlMinutes,
        ]);

        $redirectTo = $request->input('redirectTo') ?: route('partcontable.company', $company->slug);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'Acceso concedido.'])
            : redirect()->to($redirectTo);
    }

    // ============================================================
    // ✅ BLOQUEAR (logout de PIN)
    // ============================================================
    public function lockPin(Request $request, Company $company)
    {
        session()->forget($this->pinSessionKey($company->id));
        session()->forget($this->welcomeSessionKey($company->id));

        $this->logActivity($request, 'pc_lock', $company->id, null, []);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'Bloqueado.'])
            : back()->with('success', 'Bloqueado.');
    }

    // ===============================
    // Store uploaded document(s) + LOG
    // ===============================
    public function storeDocument(Request $request, Company $company)
    {
        $isSingle = $request->hasFile('file') && !$request->hasFile('files');
        $isMulti  = $request->hasFile('files');

        if (!$isSingle && !$isMulti) {
            return response()->json(['ok' => false, 'message' => 'No se detectó archivo.'], 422);
        }

        $request->validate([
            'section_id'         => 'required|exists:document_sections,id',
            'subtype_id'         => 'nullable|exists:document_subtypes,id',
            'title'              => 'nullable|string|max:255',
            'title_global'       => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'description_global' => 'nullable|string',
            'date'               => 'nullable|date',
        ]);

        $section         = DocumentSection::findOrFail($request->input('section_id'));
        $metaTitle       = $request->input('title') ?: $request->input('title_global');
        $metaDescription = $request->input('description') ?: $request->input('description_global');
        $metaDate        = $request->input('date') ?: now()->toDateString();

        $storedDocs = [];
        DB::beginTransaction();

        try {
            $filesToProcess = $isSingle ? [$request->file('file')] : $request->file('files');

            foreach ($filesToProcess as $file) {
                if (!$file->isValid()) {
                    throw new \Exception('Archivo inválido: ' . $file->getClientOriginalName());
                }

                $allowed = ['jpg','jpeg','png','gif','webp','svg','mp4','mov','pdf','doc','docx','xls','xlsx'];
                $ext = strtolower($file->getClientOriginalExtension());

                if (!in_array($ext, $allowed)) {
                    throw new \Exception('Formato no permitido: ' . $file->getClientOriginalName());
                }
                if ($file->getSize() > 30 * 1024 * 1024) {
                    throw new \Exception('Archivo muy grande: ' . $file->getClientOriginalName());
                }

                $d     = \Carbon\Carbon::parse($metaDate);
                $year  = $d->year;
                $month = $d->month;

                $slug     = Str::slug($metaTitle ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
                $filename = $slug . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $subdir   = "partcontable/{$company->id}/{$section->key}/{$year}/{$month}";

                $storedPath = $file->storeAs($subdir, $filename, 'public');

                $mime = $file->getClientMimeType() ?: 'application/octet-stream';
                $type = $this->detectType($mime);

                $document = Document::create([
                    'company_id'  => $company->id,
                    'section_id'  => $section->id,
                    'subtype_id'  => $request->input('subtype_id') ?: null,
                    'title'       => $metaTitle ?: $file->getClientOriginalName(),
                    'description' => $metaDescription,
                    'file_path'   => $storedPath,
                    'file_type'   => $type,
                    'mime_type'   => $mime,
                    'date'        => $metaDate,
                    'uploaded_by' => auth()->id() ?? null,
                ]);

                $storedDocs[] = $document;

                $this->logActivity($request, 'pc_upload', $company->id, $document->id, [
                    'title'      => $document->title,
                    'mime'       => $document->mime_type,
                    'file_path'  => $document->file_path,
                    'section_id' => $document->section_id,
                    'subtype_id' => $document->subtype_id,
                    'date'       => $document->date,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($storedDocs as $sd) {
                Storage::disk('public')->delete($sd->file_path);
                $sd->delete();
            }

            \Log::error('storeDocument error: ' . $e->getMessage());

            return response()->json([
                'ok'      => false,
                'message' => 'Error al subir: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok'        => true,
            'uploaded'  => count($storedDocs),
            'documents' => collect($storedDocs)->map->only([
                'id','title','file_path','file_type','mime_type'
            ])->all(),
        ]);
    }

    protected function detectType($mime)
    {
        if (str_starts_with($mime, 'image/')) return 'foto';
        if (str_starts_with($mime, 'video/')) return 'video';
        return 'documento';
    }

    // ===============================
    // ✅ SUBIR FICTICIO (ligado al documento)
    // ===============================
    public function uploadFicticio(Request $request, Document $document)
    {
        // ✅ solo permitir ficticio en Declaración anual/mensual y sus 3 subtipos
        $allowedSectionKeys = ['declaracion_anual', 'declaracion_mensual'];
        $allowedSubtypeKeys = [
            'acuse_anual','pago_anual','declaracion_anual',
            'acuse_mensual','pago_mensual','declaracion_mensual',
        ];

        $sectionKey = optional($document->section)->key;
        $subKey     = optional($document->subtype)->key;

        if (!in_array($sectionKey, $allowedSectionKeys, true) || !in_array($subKey, $allowedSubtypeKeys, true)) {
            return response()->json(['ok'=>false,'message'=>'Este documento no admite ficticio.'], 422);
        }

        $request->validate([
            'file' => ['required','file','max:51200', 'mimes:pdf,jpg,jpeg,png,webp,gif,svg,mp4,mov,doc,docx,xls,xlsx'],
        ]);

        $file = $request->file('file');

        $originalName = $file->getClientOriginalName();
        $mime = $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream');

        // ✅ mismo árbol por compañía / sección / fecha (si existe date)
        $d = $document->date ? \Carbon\Carbon::parse($document->date) : now();
        $year  = $d->year;
        $month = $d->month;

        $subdir = "partcontable/{$document->company_id}/{$sectionKey}/{$year}/{$month}/ficticios";
        $slug   = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $ext    = strtolower($file->getClientOriginalExtension());
        $name   = "{$slug}_ficticio_" . time() . "_" . uniqid() . ".{$ext}";

        $storedPath = $file->storeAs($subdir, $name, 'public');

        // ✅ borrar el ficticio anterior si existe
        if ($document->ficticio_file_path && Storage::disk('public')->exists($document->ficticio_file_path)) {
            Storage::disk('public')->delete($document->ficticio_file_path);
        }

        $document->update([
            'ficticio_file_path'    => $storedPath,
            'ficticio_filename'     => $originalName,
            'ficticio_mime_type'    => $mime,
            'ficticio_uploaded_by'  => auth()->id(),
        ]);

        $this->logActivity($request, 'pc_upload_ficticio', $document->company_id, $document->id, [
            'ficticio_path' => $storedPath,
            'mime'          => $mime,
            'section_key'   => $sectionKey,
            'subtype_key'   => $subKey,
        ]);

        return response()->json([
            'ok'           => true,
            'message'      => 'Ficticio subido.',
            'ficticio_url' => Storage::disk('public')->url($storedPath),
            'download_url' => route('partcontable.documents.ficticio.download', $document),
        ]);
    }

    // ===============================
    // ✅ DESCARGAR FICTICIO
    // ===============================
    public function downloadFicticio(Request $request, Document $document)
    {
        if (!$document->ficticio_file_path || !Storage::disk('public')->exists($document->ficticio_file_path)) {
            abort(404);
        }

        $this->logActivity($request, 'pc_download_ficticio', $document->company_id ?? null, $document->id, [
            'path' => $document->ficticio_file_path,
            'mime' => $document->ficticio_mime_type,
        ]);

        $name = $document->ficticio_filename ?: basename($document->ficticio_file_path);

        return Storage::disk('public')->download($document->ficticio_file_path, $name);
    }

    public function download(Request $request, Document $document)
    {
        $this->logActivity($request, 'pc_download', $document->company_id ?? null, $document->id, [
            'title' => $document->title,
            'path'  => $document->file_path,
            'mime'  => $document->mime_type,
        ]);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404);
        }

        $filename = $document->title . '.' . pathinfo($document->file_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($document->file_path, $filename);
    }

    public function preview(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        $this->logActivity($request, 'pc_preview', $document->company_id ?? null, $document->id, [
            'title' => $document->title,
            'mime'  => $document->mime_type,
            'path'  => $document->file_path,
        ]);

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'El archivo no existe.');
        }

        $welcome = null;
        if ($document->company_id) {
            $welcome = session($this->welcomeSessionKey((int)$document->company_id));
        }

        return view('partcontable.preview', compact('document', 'welcome'));
    }

    public function destroy(Request $request, Document $document)
    {
        $path = $document->file_path;

        DB::beginTransaction();
        try {
            $documentId = $document->id;
            $companyId  = $document->company_id ?? null;
            $title      = $document->title;
            $mime       = $document->mime_type;

            // ✅ borra ficticio si existe
            if ($document->ficticio_file_path && Storage::disk('public')->exists($document->ficticio_file_path)) {
                Storage::disk('public')->delete($document->ficticio_file_path);
            }

            $document->delete();
            Storage::disk('public')->delete($path);
            DB::commit();

            $this->logActivity($request, 'pc_delete', $companyId, $documentId, [
                'title' => $title,
                'mime'  => $mime,
                'path'  => $path,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json(['ok' => false, 'message' => 'No se pudo eliminar.'], 500);
            }

            return back()->withErrors(['file' => 'No se pudo eliminar.']);
        }

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Documento eliminado.');
    }

    public function activityAll(Request $request)
    {
        $q        = trim((string) $request->get('q', ''));
        $action   = trim((string) $request->get('action', ''));
        $userId   = $request->get('user_id');
        $companyId= $request->get('company_id');

        $rows = \App\Models\UserActivity::with([
                'user:id,name,email',
                'company:id,name,slug',
                'document:id,title'
            ])
            ->when($action !== '', fn($qq) => $qq->where('action', $action))
            ->when($userId, fn($qq) => $qq->where('user_id', $userId))
            ->when($companyId, fn($qq) => $qq->where('company_id', $companyId))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('action', 'like', "%{$q}%")
                      ->orWhere('ip', 'like', "%{$q}%")
                      ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$q}%")->orWhere('email','like',"%{$q}%"))
                      ->orWhereHas('company', fn($c) => $c->where('name', 'like', "%{$q}%"))
                      ->orWhereHas('document', fn($d) => $d->where('title', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        $actions = \App\Models\UserActivity::select('action')->distinct()->orderBy('action')->pluck('action')->values();

        $users = \App\Models\UserActivity::whereNotNull('user_id')
            ->with('user:id,name')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $companies = \App\Models\UserActivity::whereNotNull('company_id')
            ->with('company:id,name')
            ->get()
            ->pluck('company')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $this->logActivity($request, 'pc_view_activity_all', null, null, [
            'filters' => [
                'q' => $q, 'action' => $action, 'user_id' => $userId, 'company_id' => $companyId
            ],
        ]);

        return view('partcontable.activity_all', compact(
            'rows','actions','users','companies','q','action','userId','companyId'
        ));
    }
}
