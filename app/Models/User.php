<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Campos asignables masivamente.
     * (No incluimos campos del OTP para mantenerlos protegidos;
     * se actualizan con forceFill() en el controlador).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'approved_at',
        'avatar_path',
        // agrega aquí otros campos persistentes que manejes (p.ej. phone, role, etc.)
    ];

    /**
     * Campos ocultos al serializar.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code_hash', // hash del OTP
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'password'                           => 'hashed',
        'email_verified_at'                  => 'datetime',
        'approved_at'                        => 'datetime',
        'email_verification_expires_at'      => 'datetime', // OTP
        'email_verification_code_sent_at'    => 'datetime', // OTP
        // 'avatar_updated_at' => 'datetime', // solo si existe columna; normalmente calculamos por mtime
    ];

    /**
     * ¿Cuenta aprobada por un admin?
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * URL pública del avatar con fallback estable.
     * - Si existe en disco 'public', se sirve por la ruta /media (media.show) con cache-busting (?v=mtime).
     * - Si no hay archivo, usa Gravatar (default mp).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            $ts = Storage::disk('public')->lastModified($this->avatar_path);

            // Ruta controlada recomendada (debes tener la ruta 'media.show' definida)
            return route('media.show', ['path' => $this->avatar_path, 'v' => $ts]);

            // Alternativa si usas symlink /storage:
            // $url = Storage::disk('public')->url($this->avatar_path);
            // return $url.'?v='.$ts;
        }

        $hash = md5(strtolower(trim($this->email ?? '')));
        return "https://www.gravatar.com/avatar/{$hash}?s=300&d=mp";
    }

    /**
     * Accessor virtual: avatar_updated_at (Carbon|null) calculado desde mtime.
     * Útil para invalidar caché en vistas aunque no exista la columna en BD.
     */
    public function getAvatarUpdatedAtAttribute()
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            try {
                $ts = Storage::disk('public')->lastModified($this->avatar_path);
                return Carbon::createFromTimestamp($ts);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Relaciones de direcciones de envío.
     */
    public function shippingAddresses()
    {
        return $this->hasMany(\App\Models\ShippingAddress::class);
    }

    public function defaultShippingAddress()
    {
        return $this->hasOne(\App\Models\ShippingAddress::class)
            ->where('is_default', true);
    }

    /**
     * Perfiles de facturación (CFDI).
     */
    public function billingProfiles()
    {
        return $this->hasMany(\App\Models\BillingProfile::class);
    }

    public function defaultBillingProfile()
    {
        return $this->hasOne(\App\Models\BillingProfile::class)
            ->where('is_default', true);
    }

    /**
     * Comentarios (si manejas reviews u observaciones).
     */
    public function comments()
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    /**
     * Favoritos (pivot 'favorites' con catalog_items).
     */
    public function favorites()
    {
        return $this->belongsToMany(\App\Models\CatalogItem::class, 'favorites')->withTimestamps();
    }
}
