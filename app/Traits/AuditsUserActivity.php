<?php

namespace App\Traits;

use App\Models\UserActivity;
use Illuminate\Support\Str;

trait AuditsUserActivity
{
    protected function auditActivity(
        string $action,
        ?int $companyId = null,
        ?int $documentId = null,
        array $meta = [],
        ?int $statusCode = 200,
        ?int $durationMs = null
    ): void {
        try {
            UserActivity::create([
                'user_id' => auth()->id(),
                'company_id' => $companyId,
                'document_id' => $documentId,

                'action' => $action,

                'route' => optional(request()->route())->getName(),
                'path' => request()->path(),
                'method' => request()->method(),
                'status_code' => $statusCode,

                'meta' => array_merge([
                    'auth_user_id' => auth()->id(),
                    'auth_user_name' => auth()->user()?->name,
                    'auth_user_email' => auth()->user()?->email,
                ], $meta),

                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'session_id' => session()->getId(),
                'request_id' => request()->headers->get('X-Request-ID') ?? (string) Str::uuid(),
                'duration_ms' => $durationMs,
                'referer' => request()->headers->get('referer'),

                'previous_hash' => null,
                'current_hash' => null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}