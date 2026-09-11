<?php

namespace App\Traits;

use App\Models\UserActivity;
use Illuminate\Support\Str;

/**
 * Registra automáticamente en user_activities cuándo un registro se crea,
 * actualiza o elimina, y quién lo hizo.
 *
 * Uso: agrega `use \App\Traits\LogsModelActivity;` en cualquier modelo Eloquent
 * que quieras auditar con detalle (productos, documentos, inventario, etc.).
 *
 * Complementa al middleware LogUserActivity (que registra cada request HTTP):
 * aquí queda el detalle legible por registro ("Usuario eliminó Producto «X» #5").
 */
trait LogsModelActivity
{
    public static function bootLogsModelActivity(): void
    {
        static::created(fn ($model) => $model->logModelActivity('created'));
        static::updated(fn ($model) => $model->logModelActivity('updated'));
        static::deleted(fn ($model) => $model->logModelActivity('deleted'));
    }

    public function logModelActivity(string $event): void
    {
        try {
            $modelName = class_basename($this);

            $verbo = [
                'created' => 'creó',
                'updated' => 'actualizó',
                'deleted' => 'eliminó',
            ][$event] ?? $event;

            $titulo = $this->activityTitle();

            $descripcion = trim(
                'Usuario ' . $verbo . ' ' . $modelName
                . ($titulo ? ' «' . $titulo . '»' : '')
                . ' #' . $this->getKey()
            );

            UserActivity::create([
                'user_id'     => auth()->id(),
                'company_id'  => auth()->user()->company_id ?? ($this->company_id ?? null),
                'document_id' => $this->activityDocumentId(),

                'action'      => 'model_' . $event, // model_created / model_updated / model_deleted
                'module'      => $modelName,
                'description' => $descripcion,

                'route'       => optional(request()->route())->getName(),
                'path'        => request() ? request()->path() : null,
                'method'      => request() ? request()->method() : null,
                'status_code' => 200,

                'meta'        => $this->activityMeta($event),

                'ip'          => request() ? request()->ip() : null,
                'user_agent'  => substr((string) (request() ? request()->userAgent() : ''), 0, 512),
                'session_id'  => session()->getId(),
                'request_id'  => (string) Str::uuid(),
            ]);
        } catch (\Throwable $e) {
            // Nunca romper la operación del usuario por un fallo de auditoría.
            report($e);
        }
    }

    /**
     * Busca un título legible del registro entre atributos comunes.
     */
    protected function activityTitle(): ?string
    {
        foreach (['name', 'nombre', 'title', 'titulo', 'filename', 'original_name', 'sku', 'codigo', 'folio', 'numero'] as $attr) {
            $value = $this->getAttribute($attr);
            if (!empty($value) && is_scalar($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * Si el modelo ES un documento o referencia uno, lo enlaza.
     */
    protected function activityDocumentId(): ?int
    {
        if (class_basename($this) === 'Document') {
            return (int) $this->getKey();
        }

        $docId = $this->getAttribute('document_id');

        return is_numeric($docId) ? (int) $docId : null;
    }

    /**
     * Metadatos: qué cambió (en updates) sin exponer datos sensibles.
     */
    protected function activityMeta(string $event): array
    {
        $meta = [
            'model' => static::class,
            'id'    => $this->getKey(),
        ];

        if ($event === 'updated') {
            $sensitive = ['password', 'remember_token', 'token', 'secret', 'api_key', 'nip', 'pin'];
            $meta['changed'] = array_values(array_diff(array_keys($this->getChanges()), $sensitive));
        }

        return $meta;
    }
}
