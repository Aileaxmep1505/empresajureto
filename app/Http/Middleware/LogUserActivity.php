<?php

namespace App\Http\Middleware;

use App\Models\UserActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LogUserActivity
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        // ✅ Request ID para correlación
        $requestId = (string) Str::uuid();
        $request->attributes->set('activity_request_id', $requestId);

        // ✅ Respuesta normal
        $response = $next($request);

        // ✅ Duración
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        // ✅ Evitar ruido (assets, storage, livewire, etc.)
        if ($this->shouldIgnore($request)) {
            return $response;
        }

        // ✅ Meta default (sin guardar datos sensibles)
        $meta = [
            'query' => $this->sanitize($request->query()),
        ];

        // Para métodos con body, solo guardamos keys, no valores
        if (in_array(strtoupper($request->method()), ['POST','PUT','PATCH','DELETE'], true)) {
            $meta['has_body'] = true;
            $meta['keys'] = array_slice(array_keys($request->all() ?? []), 0, 40);
        }

        // ✅ Guardar actividad
        $this->storeActivity($request, $response, $durationMs, $requestId, $meta);

        return $response;
    }

    private function storeActivity(Request $request, $response, int $durationMs, string $requestId, array $meta): void
    {
        $user = auth()->user();

        $payload = [
            'user_id'     => $user?->id,
            'company_id'  => $user->company_id ?? null,

            // si no aplica, se queda null (pero NO rompe tu esquema)
            'document_id' => null,

            // ✅ acción general para navegación / requests
            'action'      => 'http_request',

            'route'       => optional($request->route())->getName(),
            'path'        => '/'.ltrim($request->path(), '/'),
            'method'      => strtoupper($request->method()),
            'status_code' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,

            'duration_ms' => $durationMs,
            'referer'     => substr((string) $request->headers->get('referer'), 0, 500),

            'ip'          => $request->ip(),
            'user_agent'  => substr((string) $request->userAgent(), 0, 512),

            'session_id'  => substr((string) $request->session()->getId(), 0, 120),
            'request_id'  => $requestId,

            'meta'        => $this->sanitizeMeta($meta),

            // ✅ subject genérico (por default null)
            'subject_type' => $request->attributes->get('activity_subject_type'),
            'subject_id'   => $request->attributes->get('activity_subject_id'),
        ];

        // ✅ Hash encadenado anti-manipulación
        DB::transaction(function () use ($payload) {
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
                'duration_ms' => $payload['duration_ms'],
                'referer'     => $payload['referer'],
                'ip'          => $payload['ip'],
                'ua'          => $payload['user_agent'],
                'session_id'  => $payload['session_id'],
                'request_id'  => $payload['request_id'],
                'subject_type'=> $payload['subject_type'],
                'subject_id'  => $payload['subject_id'],
                'meta'        => $payload['meta'],
                'previous'    => $previousHash,
                'ts'          => microtime(true),
            ], JSON_UNESCAPED_UNICODE);

            $payload['current_hash'] = hash('sha256', (string) $hashBase);

            UserActivity::create($payload);
        });
    }

    private function shouldIgnore(Request $request): bool
    {
        $path = ltrim($request->path(), '/');
        $method = strtoupper($request->method());

        // Ignorar OPTIONS
        if ($method === 'OPTIONS') return true;

        // Ignorar assets/ruido
        $ignorePrefixes = [
            'storage/', 'build/', 'assets/', 'vendor/', 'css/', 'js/', 'images/',
            '_debugbar', 'telescope', 'livewire', 'broadcasting/auth',
        ];

        foreach ($ignorePrefixes as $prefix) {
            $prefix = trim($prefix, '/');
            if ($prefix !== '' && str_starts_with($path, $prefix)) return true;
        }

        // Si quieres ignorar health checks:
        // if ($path === 'up') return true;

        return false;
    }

    private function sanitize(array $arr): array
    {
        // Sanitiza query params (valores sensibles)
        $sensitive = [
            'password','password_confirmation','token','access_token','refresh_token',
            'authorization','cookie','x-csrf-token','csrf_token','nip','pin',
            'ine','rfc','curp','secret','api_key',
        ];

        $out = [];
        foreach ($arr as $k => $v) {
            $key = strtolower((string) $k);
            if (in_array($key, $sensitive, true)) {
                $out[$k] = '[REDACTED]';
            } else {
                // recorta strings largos
                if (is_string($v) && mb_strlen($v) > 200) $v = mb_substr($v, 0, 200) . '…';
                $out[$k] = $v;
            }
        }
        return $out;
    }

    private function sanitizeMeta(array $meta): array
    {
        // ✅ aplanar y limitar
        $dot = Arr::dot($meta);
        $dot = array_slice($dot, 0, 40, true);

        $sensitiveKeys = [
            'password','password_confirmation','token','access_token','refresh_token',
            'authorization','cookie','x-csrf-token','csrf_token','nip','pin',
            'ine','rfc','curp','secret','api_key',
            'file','documento','archivo',
        ];

        $out = [];
        foreach ($dot as $k => $v) {
            $kLower = strtolower((string) $k);

            // redactar si key sensible
            foreach ($sensitiveKeys as $s) {
                if (str_contains($kLower, $s)) {
                    $out[$k] = '[REDACTED]';
                    continue 2;
                }
            }

            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }

            $v = (string) $v;
            if (mb_strlen($v) > 500) $v = mb_substr($v, 0, 500) . '…';

            $out[$k] = $v;
        }

        return $out;
    }
}