<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ConfidentialDocument;
use App\Models\User;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConfidentialDocsController extends Controller
{
    /**
     * ✅ Minutos de desbloqueo por sesión (por “vault”)
     */
    private int $pinTtlMinutes = 30;

    // =========================
    // PIN session helpers
    // =========================
    private function pinSessionKey(int $ownerUserId): string
    {
        return "conf_pin_unlocked_{$ownerUserId}";
    }

    private function welcomeSessionKey(int $ownerUserId): string
    {
        return "conf_welcome_{$ownerUserId}";
    }

    private function isPinUnlocked(int $ownerUserId): bool
    {
        $until = session($this->pinSessionKey($ownerUserId));
        if (!$until) return false;

        $untilTs = is_numeric($until) ? (int) $until : strtotime((string) $until);
        return $untilTs && $untilTs >= now()->timestamp;
    }

    private function setPinUnlocked(int $ownerUserId): void
    {
        session([
            $this->pinSessionKey($ownerUserId) => now()->addMinutes($this->pinTtlMinutes)->timestamp
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

    // =========================
    // PIN verification (igual a tu estilo)
    // =========================
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

        if (hash_equals($storedNormalized, $input)) return true;

        if (ctype_digit($storedNormalized) && ctype_digit($input)) {
            return ((int) $storedNormalized) === ((int) $input);
        }

        return false;
    }

    // =========================
    // ✅ Screen/module helpers (para ActivityController)
    // =========================
    private function screenNameFromRequest(Request $request): ?string
    {
        $routeName = optional($request->route())->getName();
        if (!$routeName) return null;

        $map = (array) config('user_activity.screens', []);
        return $map[$routeName] ?? null;
    }

    private function moduleFromRequest(Request $request): string
    {
        $routeName = (string) (optional($request->route())->getName() ?? '');
        if (str_contains($routeName, 'confidential')) return 'Vault confidencial';
        return 'Sistema';
    }

    private function sanitizeMeta(array $meta): array
    {
        // respeta tu config
        $sensitive = (array) config('user_activity.sensitive_keys', []);
        $maxLen    = (int) (config('user_activity.max_value_length', 500));
        $maxKeys   = (int) (config('user_activity.max_meta_keys', 40));

        $out = [];
        $i = 0;

        foreach ($meta as $k => $v) {
            if ($i >= $maxKeys) break;
            $i++;

            $key = (string) $k;
            $lk = strtolower($key);

            foreach ($sensitive as $sk) {
                if ($lk === strtolower((string)$sk)) {
                    $out[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            if (is_array($v)) {
                $v = json_encode($v);
            } elseif (is_object($v)) {
                $v = '[OBJECT]';
            }

            $val = (string) $v;
            if (mb_strlen($val) > $maxLen) {
                $val = mb_substr($val, 0, $maxLen) . '…';
            }

            $out[$key] = $val;
        }

        return $out;
    }

    /**
     * ✅ manda TODO a UserActivity (con module/screen/path/route)
     */
    private function logActivity(Request $request, string $action, ?int $companyId = null, ?int $confDocId = null, array $meta = []): void
    {
        try {
            $routeName = optional($request->route())->getName();
            $screen    = $this->screenNameFromRequest($request);
            $module    = $this->moduleFromRequest($request);

            $payloadMeta = array_merge([
                'confidential_document_id' => $confDocId,
                'vault_owner_id'           => optional($request->route('owner'))->id ?? null,
            ], $meta);

            UserActivity::create([
                'user_id'     => optional($request->user())->id,
                'company_id'  => $companyId,

                // ✅ si tu tabla tiene FK estricta a documents.id, lo dejamos null y usamos meta.
                'document_id' => null,

                // ✅ lo que filtras en ActivityController
                'action'      => $action,
                'module'      => $module,
                'screen'      => $screen,

                // rutas
                'route'       => $routeName,
                'path'        => '/'.ltrim($request->path(), '/'),

                // meta sanitizada (según config)
                'meta'        => $this->sanitizeMeta($payloadMeta) ?: null,

                // huella
                'ip'          => $request->ip(),
                'user_agent'  => substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('UserActivity log failed: ' . $e->getMessage());
        }
    }

    // ============================================================
    // ✅ VISTA VAULT (PIDE PIN ANTES) + ✅ LIVE SEARCH AJAX
    // ============================================================
    public function showVault(Request $request, User $owner)
    {
        // if ((int)$owner->id !== (int)auth()->id() && !auth()->user()->hasRole('admin')) abort(403);

        if (!$this->isPinUnlocked($owner->id)) {
            // ✅ registra intento de acceso bloqueado (sirve en bitácora)
            $this->logActivity($request, 'conf_vault_blocked', null, null, [
                'vault_of' => $owner->id,
                'owner'    => $owner->name,
            ]);

            return view('confidential.pin', [
                'owner'      => $owner,
                'redirectTo' => $request->fullUrl(),
            ]);
        }

        $q = trim((string) $request->get('q', ''));

        // Si viene "subtipo" lo usamos como doc_key.
        $docKey  = trim((string) $request->get('doc_key', ''));
        $subtipo = trim((string) $request->get('subtipo', ''));
        if ($docKey === '' && $subtipo !== '') $docKey = $subtipo;

        $year  = $request->get('year');
        $month = $request->get('month');

        $query = ConfidentialDocument::query()
            ->where('owner_user_id', $owner->id);

        if ($docKey !== '') $query->where('doc_key', $docKey);
        if ($year)  $query->whereYear('date', $year);
        if ($month) $query->whereMonth('date', $month);

        // ✅ Live search: title / doc_key / description / original_name / file_path
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($s) use ($like) {
                $s->where('title', 'like', $like)
                  ->orWhere('doc_key', 'like', $like)
                  ->orWhere('description', 'like', $like)
                  ->orWhere('original_name', 'like', $like)
                  ->orWhere('file_path', 'like', $like);
            });
        }

        $documents = $query->orderByDesc('date')->orderByDesc('id')
            ->paginate(12)
            ->appends($request->query());

        // ✅ AJAX (búsqueda tipo Google sin recargar)
        $isAjax = $request->ajax() || $request->boolean('ajax') || $request->header('X-Vault-Ajax') === '1';
        if ($isAjax) {
            // ✅ registra la acción de búsqueda
            $this->logActivity($request, 'conf_vault_search', null, null, [
                'vault_of' => $owner->id,
                'q'        => $q,
                'doc_key'  => $docKey,
                'year'     => $year,
                'month'    => $month,
                'page'     => (int) $request->get('page', 1),
            ]);

            $suggestions = [];
            if ($q !== '') {
                $suggestions = ConfidentialDocument::query()
                    ->where('owner_user_id', $owner->id)
                    ->when($docKey !== '', fn($qq) => $qq->where('doc_key', $docKey))
                    ->when($year, fn($qq) => $qq->whereYear('date', $year))
                    ->when($month, fn($qq) => $qq->whereMonth('date', $month))
                    ->where(function ($s) use ($q) {
                        $like = '%'.$q.'%';
                        $s->where('title', 'like', $like)
                          ->orWhere('doc_key', 'like', $like)
                          ->orWhere('original_name', 'like', $like);
                    })
                    ->orderByRaw("CASE WHEN title LIKE ? THEN 0 ELSE 1 END", [$q.'%'])
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get(['id','title','doc_key','original_name'])
                    ->map(function($d){
                        $label = trim((string)($d->title ?: $d->original_name ?: 'Documento'));
                        return [
                            'id'    => (int)$d->id,
                            'label' => Str::limit($label, 70),
                            'key'   => Str::limit((string)($d->doc_key ?? ''), 40),
                        ];
                    })
                    ->values()
                    ->all();
            }

            $html = view('confidential.partials.vault_results', [
                'documents' => $documents ?: $this->emptyPaginator($request, 12),
            ])->render();

            return response()->json([
                'html'        => $html,
                'total'       => $documents ? $documents->total() : 0,
                'suggestions' => $suggestions,
            ]);
        }

        // ✅ registra vista normal del vault
        $this->logActivity($request, 'conf_vault_view', null, null, [
            'vault_of' => $owner->id,
            'doc_key'  => $docKey,
            'year'     => $year,
            'month'    => $month,
            'q'        => $q,
        ]);

        return view('confidential.vault', [
            'owner'       => $owner,
            'documents'   => $documents ?: $this->emptyPaginator($request, 12),
            'docKey'      => $docKey,
            'year'        => $year,
            'month'       => $month,
            'q'           => $q,
            'pinUnlocked' => true,
        ]);
    }

    // ============================================================
    // ✅ CREATE VIEW (vista para subir, NO modal)
    // ============================================================
    public function create(Request $request, User $owner)
    {
        if (!$this->isPinUnlocked($owner->id)) {
            $this->logActivity($request, 'conf_create_blocked', null, null, [
                'vault_of' => $owner->id,
            ]);

            return redirect()
                ->route('confidential.vault', $owner->id)
                ->with('warning', 'Debes ingresar NIP primero.');
        }

        $section = (string) $request->get('section', 'efirma');
        $subtipo = (string) $request->get('subtipo', '');

        $this->logActivity($request, 'conf_create_view', null, null, [
            'vault_of' => $owner->id,
            'section'  => $section,
            'subtipo'  => $subtipo,
        ]);

        return view('confidential.create', [
            'owner'   => $owner,
            'section' => $section,
            'subtipo' => $subtipo,
        ]);
    }

    // ============================================================
    // ✅ VALIDAR PIN Y ENTRAR
    // ============================================================
    public function unlockWithPin(Request $request, User $owner)
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

            $this->logActivity($request, 'conf_unlock_failed', null, null, [
                'vault_of' => $owner->id,
                'reason'   => 'no_pin_configured',
            ]);

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('warning', $msg);
        }

        $input = $this->normalizePin((string) $request->pin);

        $ok = false;
        if (method_exists($user, 'checkApprovalPin')) {
            $ok = (bool) $user->checkApprovalPin($input);
        } else {
            $ok = $this->verifyPin($input, $stored);
        }

        if (!$ok) {
            $this->logActivity($request, 'conf_unlock_failed', null, null, [
                'vault_of' => $owner->id,
                'reason'   => 'invalid_pin',
            ]);

            $msg = 'NIP incorrecto.';
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('warning', $msg);
        }

        $this->setPinUnlocked($owner->id);

        session([
            $this->welcomeSessionKey($owner->id) => [
                'at'       => now()->toIso8601String(),
                'user_id'  => $user->id,
                'name'     => $user->name,
                'vault_of' => $owner->name,
            ],
        ]);

        $this->logActivity($request, 'conf_unlock', null, null, [
            'ttl_min'    => $this->pinTtlMinutes,
            'vault_of'   => $owner->id,
            'vault_name' => $owner->name,
        ]);

        $redirectTo = $request->input('redirectTo') ?: route('confidential.vault', $owner->id);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'Acceso concedido.'])
            : redirect()->to($redirectTo);
    }

    // ============================================================
    // ✅ BLOQUEAR (logout de PIN)
    // ============================================================
    public function lockPin(Request $request, User $owner)
    {
        session()->forget($this->pinSessionKey($owner->id));
        session()->forget($this->welcomeSessionKey($owner->id));

        $this->logActivity($request, 'conf_lock', null, null, [
            'vault_of' => $owner->id,
        ]);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => 'Bloqueado.'])
            : back()->with('success', 'Bloqueado.');
    }

    // ============================================================
    // ✅ STORE (subir documento confidencial)
    // ============================================================
    public function store(Request $request, User $owner)
    {
        if (!$this->isPinUnlocked($owner->id)) {
            $this->logActivity($request, 'conf_upload_blocked', null, null, [
                'vault_of' => $owner->id,
            ]);

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Bloqueado. Ingresa NIP.'], 403)
                : back()->with('warning', 'Bloqueado. Ingresa NIP.');
        }

        $allowedExt = ['pdf','jpg','jpeg','png','webp','gif','svg','doc','docx','xls','xlsx','zip'];
        $allowedMimes = [
            'application/pdf',
            'image/jpeg','image/png','image/webp','image/gif','image/svg+xml',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/octet-stream',
        ];

        $request->validate([
            'doc_key'      => 'required|string|max:60',
            'title'        => 'nullable|string|max:255',
            'description'  => 'nullable|string',
            'date'         => 'nullable|date',
            'company_id'   => 'nullable|exists:companies,id',
            'access_level' => 'nullable|in:medio,alto,critico',
            'requires_pin' => 'nullable|boolean',
            'file'         => [
                'required','file','max:51200', // 50MB
                function ($attribute, $value, $fail) use ($allowedExt, $allowedMimes) {
                    /** @var \Illuminate\Http\UploadedFile $value */
                    $origName   = (string) $value->getClientOriginalName();
                    $origExt    = strtolower($value->getClientOriginalExtension() ?: pathinfo($origName, PATHINFO_EXTENSION));
                    $clientMime = (string) ($value->getClientMimeType() ?: '');
                    $realMime   = (string) ($value->getMimeType() ?: '');
                    $guessExt   = strtolower((string) ($value->guessExtension() ?: ''));

                    $okByExt   = $origExt !== '' && in_array($origExt, $allowedExt, true);
                    $okByMime  = ($clientMime !== '' && in_array($clientMime, $allowedMimes, true))
                              || ($realMime !== ''   && in_array($realMime,   $allowedMimes, true));
                    $okByGuess = $guessExt !== '' && in_array($guessExt, $allowedExt, true);

                    $okPdf = ($origExt === 'pdf') || ($clientMime === 'application/pdf') || ($realMime === 'application/pdf') || ($guessExt === 'pdf');

                    if (!($okByExt || $okByMime || $okByGuess || $okPdf)) {
                        $fail('Formato no permitido. Sube PDF, imagen u Office.');
                    }
                },
            ],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION));
        $mime = $file->getClientMimeType() ?: ($file->getMimeType() ?: 'application/octet-stream');
        if ($ext === 'pdf') $mime = 'application/pdf';

        $date  = $request->input('date') ? Carbon::parse($request->input('date')) : now();
        $year  = $date->year;
        $month = $date->month;

        $docKeySlug = Str::slug($request->input('doc_key'), '_');
        $slugTitle  = Str::slug($request->input('title') ?: pathinfo($originalName, PATHINFO_FILENAME)) ?: 'documento';

        $subdir = "confidential/{$owner->id}/{$docKeySlug}/{$year}/{$month}";
        $name   = "{$slugTitle}_" . time() . "_" . uniqid() . ".{$ext}";

        DB::beginTransaction();
        try {
            $storedPath = $file->storeAs($subdir, $name, 'public');

            $doc = ConfidentialDocument::create([
                'owner_user_id' => $owner->id,
                'company_id'    => $request->input('company_id') ?: null,
                'uploaded_by'   => auth()->id(),
                'title'         => $request->input('title') ?: $originalName,
                'doc_key'       => $request->input('doc_key'),
                'description'   => $request->input('description'),
                'file_path'     => $storedPath,
                'original_name' => $originalName,
                'mime_type'     => $mime,
                'size'          => (int) ($file->getSize() ?: 0),
                'date'          => $date->toDateString(),
                'requires_pin'  => $request->has('requires_pin') ? (bool)$request->input('requires_pin') : true,
                'access_level'  => $request->input('access_level') ?: 'alto',
            ]);

            DB::commit();

            $this->logActivity($request, 'conf_upload', $doc->company_id, $doc->id, [
                'title'    => $doc->title,
                'doc_key'  => $doc->doc_key,
                'mime'     => $doc->mime_type,
                'path'     => $doc->file_path,
                'vault_of' => $owner->id,
            ]);

            $return = (string) $request->get('return', '');
            if ($return !== '' && Str::startsWith($return, ['http://','https://'])) {
                return redirect()->to($return)->with('success', 'Documento subido.');
            }

            $section = (string) $request->get('section', '');
            $subtipo = (string) $request->get('subtipo', '');
            $params = [];
            if ($section) $params['section'] = $section;
            if ($subtipo) $params['subtipo'] = $subtipo;

            return redirect()
                ->route('confidential.vault', [$owner->id] + $params)
                ->with('success', 'Documento subido.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('conf.store error: '.$e->getMessage());

            if (!empty($storedPath ?? null)) {
                Storage::disk('public')->delete($storedPath);
            }

            $this->logActivity($request, 'conf_upload_failed', $request->input('company_id') ?: null, null, [
                'vault_of' => $owner->id,
                'error'    => $e->getMessage(),
            ]);

            return $request->expectsJson()
                ? response()->json(['ok'=>false,'message'=>'Error al subir.'], 500)
                : back()->with('warning', 'Error al subir.');
        }
    }

    // ============================================================
    // ✅ DOWNLOAD (pide pin si el doc lo requiere)
    // ============================================================
    public function download(Request $request, ConfidentialDocument $doc)
    {
        if ($doc->requires_pin && !$this->isPinUnlocked((int)$doc->owner_user_id)) {
            $this->logActivity($request, 'conf_download_blocked', $doc->company_id, $doc->id, [
                'vault_of' => $doc->owner_user_id,
            ]);
            abort(403, 'Bloqueado. Ingresa NIP.');
        }

        if (!Storage::disk('public')->exists($doc->file_path)) abort(404);

        $doc->update(['last_accessed_at' => now()]);

        $this->logActivity($request, 'conf_download', $doc->company_id, $doc->id, [
            'title'    => $doc->title,
            'doc_key'  => $doc->doc_key,
            'path'     => $doc->file_path,
            'mime'     => $doc->mime_type,
            'vault_of' => $doc->owner_user_id,
        ]);

        $downloadName = $doc->original_name ?: basename($doc->file_path);

        return Storage::disk('public')->download($doc->file_path, $downloadName);
    }

    // ============================================================
    // ✅ PREVIEW (igual: pide pin si requiere)
    // ============================================================
    public function preview(Request $request, ConfidentialDocument $doc)
    {
        if ($doc->requires_pin && !$this->isPinUnlocked((int)$doc->owner_user_id)) {
            $this->logActivity($request, 'conf_preview_blocked', $doc->company_id, $doc->id, [
                'vault_of' => $doc->owner_user_id,
            ]);
            abort(403, 'Bloqueado. Ingresa NIP.');
        }

        $exists = Storage::disk('public')->exists($doc->file_path);
        if (!$exists) abort(404);

        $doc->update(['last_accessed_at' => now()]);

        $this->logActivity($request, 'conf_preview', $doc->company_id, $doc->id, [
            'title'   => $doc->title,
            'doc_key' => $doc->doc_key,
            'path'    => $doc->file_path,
            'mime'    => $doc->mime_type,
            'vault_of'=> $doc->owner_user_id,
        ]);

        $owner = User::findOrFail((int)$doc->owner_user_id);

        return view('confidential.preview', compact('doc','owner','exists'));
    }

    // ============================================================
    // ✅ DESTROY
    // ============================================================
    public function destroy(Request $request, ConfidentialDocument $doc)
    {
        if ($doc->requires_pin && !$this->isPinUnlocked((int)$doc->owner_user_id)) {
            $this->logActivity($request, 'conf_delete_blocked', $doc->company_id, $doc->id, [
                'vault_of' => $doc->owner_user_id,
            ]);

            return $request->expectsJson()
                ? response()->json(['ok'=>false,'message'=>'Bloqueado. Ingresa NIP.'], 403)
                : back()->with('warning', 'Bloqueado. Ingresa NIP.');
        }

        DB::beginTransaction();
        try {
            $path = $doc->file_path;
            $companyId = $doc->company_id;
            $id = $doc->id;
            $title = $doc->title;
            $ownerId = (int) $doc->owner_user_id;

            $doc->delete();

            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            DB::commit();

            $this->logActivity($request, 'conf_delete', $companyId, $id, [
                'title'   => $title,
                'path'    => $path,
                'vault_of'=> $ownerId,
            ]);

            return $request->expectsJson()
                ? response()->json(['ok'=>true])
                : back()->with('success', 'Documento eliminado.');
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->logActivity($request, 'conf_delete_failed', $doc->company_id, $doc->id, [
                'vault_of' => $doc->owner_user_id,
                'error'    => $e->getMessage(),
            ]);

            return $request->expectsJson()
                ? response()->json(['ok'=>false,'message'=>'No se pudo eliminar.'], 500)
                : back()->with('warning', 'No se pudo eliminar.');
        }
    }
}