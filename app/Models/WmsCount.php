<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Conteo de inventario (cíclico, por ubicación, críticos, aleatorio o total). */
class WmsCount extends Model
{
    public const SCOPES = [
        'ubicaciones' => 'Por ubicaciones',
        'criticos'    => 'Productos con stock crítico',
        'aleatorio'   => 'Muestra aleatoria',
        'todo'        => 'Todo el almacén',
    ];

    protected $fillable = [
        'folio', 'warehouse_id', 'scope', 'status', 'blind',
        'assigned_user_id', 'created_by', 'closed_by', 'closed_at', 'notes', 'meta',
    ];

    protected $casts = [
        'blind' => 'boolean',
        'closed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function lines(): HasMany { return $this->hasMany(WmsCountLine::class, 'count_id'); }
    public function warehouse(): BelongsTo { return $this->belongsTo(Warehouse::class); }
    public function assignedUser(): BelongsTo { return $this->belongsTo(User::class, 'assigned_user_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getScopeLabelAttribute(): string
    {
        return self::SCOPES[$this->scope] ?? ucfirst((string) $this->scope);
    }

    public function isOpen(): bool
    {
        return $this->status === 'abierto';
    }

    /** Folio consecutivo del mes: CNT-202609-001 */
    public static function nextFolio(): string
    {
        $prefijo = 'CNT-' . now()->format('Ym') . '-';
        $ultimo = static::where('folio', 'like', $prefijo . '%')->orderByDesc('folio')->value('folio');
        $n = $ultimo ? ((int) substr($ultimo, -3)) + 1 : 1;

        return $prefijo . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }
}
