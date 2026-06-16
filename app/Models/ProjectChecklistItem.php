<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectChecklistItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const COMPLIANCE_SIN_REVISAR = 'sin_revisar';
    public const COMPLIANCE_CUMPLE = 'cumple';
    public const COMPLIANCE_PARCIAL = 'parcial';
    public const COMPLIANCE_NO_CUMPLE = 'no_cumple';

    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_EN_REVISION = 'en_revision';
    public const STATUS_APROBADO = 'aprobado';

    public const PRIORITY_ALTA = 'alta';
    public const PRIORITY_MEDIA = 'media';
    public const PRIORITY_BAJA = 'baja';

    protected $fillable = [
        'project_id',
        'source_document_id',
        'source_item_id',

        'requirement',
        'description',
        'compliance_criteria',
        'format',
        'category',
        'applicability',
        'mandatory',

        'compliance_status',
        'review_status',
        'priority',
        'due_date',

        'responsible_user_id',
        'reviewer_user_id',

        'source_name',
        'source_page',
        'source_quote',

        'position',
        'metadata',
    ];

    protected $casts = [
        'mandatory'   => 'boolean',
        'due_date'    => 'date',
        'source_page' => 'integer',
        'position'    => 'integer',
        'metadata'    => 'array',
    ];

    protected $appends = [
        'cumplimiento_label',
        'status_label',
        'priority_label',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(ProjectDocument::class, 'source_document_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectChecklistNote::class)
            ->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectChecklistAttachment::class)
            ->latest();
    }

    public function getCumplimientoLabelAttribute(): string
    {
        return match ($this->compliance_status) {
            self::COMPLIANCE_CUMPLE => 'Cumple',
            self::COMPLIANCE_PARCIAL => 'Parcial',
            self::COMPLIANCE_NO_CUMPLE => 'No Cumple',
            default => '-',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->review_status) {
            self::STATUS_EN_REVISION => 'En revisión',
            self::STATUS_APROBADO => 'Aprobado',
            default => 'Pendiente',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_ALTA => 'Alta',
            self::PRIORITY_BAJA => 'Baja',
            default => 'Media',
        };
    }

    public function toChecklistArray(): array
    {
        return [
            'id'                    => $this->id,
            'requisito'             => $this->requirement,
            'descripcion'           => $this->description,
            'criterio_cumplimiento' => $this->compliance_criteria,
            'formato'               => $this->format ?: 'No aplica',
            'categoria'             => $this->category ?: 'Legal-Administrativo',
            'aplicabilidad'         => $this->applicability ?: 'Único',
            'obligatorio'           => $this->mandatory ? 'Sí' : 'No',

            'cumplimiento'          => $this->cumplimiento_label,
            'cumplimiento_key'      => $this->compliance_status,

            'status'                => $this->status_label,
            'status_key'            => $this->review_status,

            'prioridad'             => $this->priority_label,
            'prioridad_key'         => $this->priority,

            'fecha_limite'          => optional($this->due_date)->format('Y-m-d'),

            'responsable_id'        => $this->responsible_user_id,
            'responsable'           => $this->responsible?->name,

            'revisor_id'            => $this->reviewer_user_id,
            'revisor'               => $this->reviewer?->name,

            'fuente'                => $this->source_name,
            'pagina'                => $this->source_page,
            'cita'                  => $this->source_quote,

            'notas'                 => $this->notes->map(fn ($note) => [
                'id'         => $note->id,
                'body'       => $note->body,
                'user_id'    => $note->user_id,
                'user_name'  => $note->user?->name,
                'created_at' => optional($note->created_at)->format('Y-m-d H:i:s'),
            ])->values()->all(),

            'adjuntos'              => $this->attachments->map(fn ($attachment) => [
                'id'          => $attachment->id,
                'name'        => $attachment->original_name,
                'url'         => $attachment->url,
                'mime'        => $attachment->mime_type,
                'size'        => $attachment->size,
                'uploaded_at' => optional($attachment->created_at)->format('Y-m-d H:i:s'),
            ])->values()->all(),

            'position'              => $this->position,
            'metadata'              => $this->metadata ?? [],
            'created_at'            => optional($this->created_at)->format('Y-m-d H:i:s'),
            'updated_at'            => optional($this->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}