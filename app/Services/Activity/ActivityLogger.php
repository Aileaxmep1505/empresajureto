<?php

namespace App\Services\Activity;

use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * Log de ACCIÓN real (crear/editar/eliminar/descargar/etc).
     */
    public function log(string $action, array $meta = [], ?Request $request = null, array $extra = []): ?UserActivity
    {
        $request = $request ?: request();

        if ($this->shouldIgnore($request)) {
            return null;
        }

        $user = auth()->user();

        $payload = [
            'user_id'      => $extra['user_id'] ?? ($user?->id),
            'company_id'   => $extra['company_id'] ?? ($user->company_id ?? null),
            'document_id'  => $extra['document_id'] ?? null,

            'action'       => $action,

            // contexto de request (útil para auditoría)
            'route'        => optional($request->route())->getName(),
            'path'         => '/'.ltrim($request->path(), '/'),
            'method'       => strtoupper($request->method()),
            'status_code'  => $extra['status_code'] ?? null,

            // seguridad
            'ip'           => $request->ip(),
            'user_agent'   => substr((string) $request->userAgent(), 0, 512),

            // correlación
            'session_id'   => substr((string) $request->session()->getId(), 0, 120),
            'request_id'   => $extra['request_id'] ?? (string) Str::uuid(),

            // performance
            'duration_ms'  => $extra['duration_ms'] ?? null,
            'referer'      => substr((string) $request->headers->get('referer'), 0, 500),

            // subject genérico (para TODO: Ticket, Cotización, Venta, AltaDoc, etc.)
            'subject_type' => $extra['subject_type'] ?? $request->attributes->get('activity_subject_type'),
            'subject_id'   => $extra['subject_id']   ?? $request->attributes->get('activity_subject_id'),

            // pantalla / módulo (opcional, pero súper útil)
            'screen'       => $extra['screen'] ?? null,
            'module'       => $extra['module'] ?? null,

            // meta SANITIZADA
            'meta'         => $this->sanitizeMeta($meta),
        ];

        return DB::transaction(function () use ($payload) {
            $prev = UserActivity::query()->latest('id')->lockForUpdate()->first();
            $previousHash = $prev?->current_hash;

            $payload['previous_hash'] = $previousHash;

            $hashBase = json_encode([
                'user_id'     => $payload['user_id'],
                'company_id'  => $payload['company_id'],
                'document_id' => $payload['document_id'],
                'action'      => $payload['action'],
                'route'       => $payload['route'],
                'path'        => $payload['path'],
                'method'      => $payload['method'],
                'status_code' => $payload['status_code'],
                'ip'          => $payload['ip'],
                'ua'          => $payload['user_agent'],
                'session_id'  => $payload['session_id'],
                'request_id'  => $payload['request_id'],
                'duration_ms' => $payload['duration_ms'],
                'referer'     => $payload['referer'],
                'subject_type'=> $payload['subject_type'],
                'subject_id'  => $payload['subject_id'],
                'screen'      => $payload['screen'],
                'module'      => $payload['module'],
                'meta'        => $payload['meta'],
                'previous'    => $previousHash,
                'ts'          => microtime(true),
            ], JSON_UNESCAPED_UNICODE);

            $payload['current_hash'] = hash('sha256', (string) $hashBase);

            return UserActivity::create($payload);
        });
    }

    /**
     * Log de entrada a pantalla: solo si la ruta está en config('activity.screens')
     * y con dedup para no repetir.
     */
    public function logScreenView(?Request $request = null): ?UserActivity
    {
        $request = $request ?: request();

        if ($this->shouldIgnore($request)) return null;

        $routeName = optional($request->route())->getName();
        if (!$routeName) return null;

        $screens = (array) config('activity.screens', []);
        if (!array_key_exists($routeName, $screens)) {
            return null; // ✅ solo pantallas que tú declares
        }

        $userId = auth()->id() ?: 0;

        $screenName = (string) $screens[$routeName];
        $dedupSeconds = (int) config('activity.screen_dedup_seconds', 30);

        // dedup: mismo usuario + misma pantalla
        $key = 'ua:screen:' . hash('sha1', $userId.'|'.$routeName.'|'.$screenName);
        if ($dedupSeconds > 0 && Cache::has($key)) {
            return null;
        }
        if ($dedupSeconds > 0) {
            Cache::put($key, 1, $dedupSeconds);
        }

        return $this->log(
            action: 'screen_view',
            meta: [
                'screen' => $screenName,
                'route'  => $routeName,
            ],
            request: $request,
            extra: [
                'screen' => $screenName,
                'module' => $this->guessModuleFromRoute($routeName),
            ]
        );
    }

    private function guessModuleFromRoute(string $routeName): ?string
    {
        $prefix = explode('.', $routeName)[0] ?? '';
        return $prefix !== '' ? $prefix : null;
    }

    private function shouldIgnore(Request $request): bool
    {
        $cfg = config('activity');

        $path = ltrim($request->path(), '/');
        $routeName = optional($request->route())->getName();
        $method = strtoupper($request->method());

        if ($method === 'OPTIONS') return true;

        foreach (($cfg['ignore_paths'] ?? []) as $prefix) {
            $prefix = trim($prefix, '/');
            if ($prefix !== '' && str_starts_with($path, $prefix)) return true;
        }

        foreach (($cfg['ignore_route_names'] ?? []) as $pattern) {
            if ($routeName && Str::is($pattern, $routeName)) return true;
        }

        return false;
    }

    private function sanitizeMeta(array $meta): array
    {
        $cfg = config('activity');

        $sensitive = array_map('strtolower', (array)($cfg['sensitive_keys'] ?? []));
        $maxLen  = (int) ($cfg['max_value_length'] ?? 500);
        $maxKeys = (int) ($cfg['max_meta_keys'] ?? 40);

        $meta = array_slice($meta, 0, $maxKeys, true);

        $out = [];
        foreach ($meta as $k => $v) {
            $key = (string) $k;
            $keyLower = strtolower($key);

            // key sensible => redactar
            foreach ($sensitive as $s) {
                if ($s !== '' && str_contains($keyLower, $s)) {
                    $out[$key] = '[REDACTED]';
                    continue 2;
                }
            }

            if (is_array($v)) {
                $v = Arr::dot($v);
                $v = array_slice($v, 0, 25, true);
            } elseif (is_object($v)) {
                $v = '[OBJECT]';
            }

            $str = is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            $str = (string) $str;

            if (mb_strlen($str) > $maxLen) $str = mb_substr($str, 0, $maxLen) . '…';

            $out[$key] = $str;
        }

        return $out;
    }
}