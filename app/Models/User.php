<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'approved_at',
        'avatar_path',

        // ✅ PIN/NIP de aprobación (se guarda como hash)
        'approval_pin_hash',
    ];

    /**
     * Campos ocultos al serializar.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code_hash',

        // ✅ ocultar hash del pin
        'approval_pin_hash',
    ];

    /**
     * Casts de atributos.
     */
    protected $casts = [
        'password'                        => 'hashed',
        'email_verified_at'               => 'datetime',
        'approved_at'                     => 'datetime',
        'email_verification_expires_at'   => 'datetime',
        'email_verification_code_sent_at' => 'datetime',
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
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            $ts = Storage::disk('public')->lastModified($this->avatar_path);

            // Ruta controlada recomendada
            return route('media.show', ['path' => $this->avatar_path, 'v' => $ts]);

            // Alternativa symlink /storage:
            // return Storage::disk('public')->url($this->avatar_path).'?v='.$ts;
        }

        $hash = md5(strtolower(trim($this->email ?? '')));
        return "https://www.gravatar.com/avatar/{$hash}?s=300&d=mp";
    }

    /**
     * Accessor virtual: avatar_updated_at desde mtime.
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

    /* ---------------- Relaciones existentes ---------------- */

    public function shippingAddresses()
    {
        return $this->hasMany(\App\Models\ShippingAddress::class);
    }

    public function defaultShippingAddress()
    {
        return $this->hasOne(\App\Models\ShippingAddress::class)->where('is_default', true);
    }

    public function billingProfiles()
    {
        return $this->hasMany(\App\Models\BillingProfile::class);
    }

    public function defaultBillingProfile()
    {
        return $this->hasOne(\App\Models\BillingProfile::class)->where('is_default', true);
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\Comment::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(\App\Models\CatalogItem::class, 'favorites')->withTimestamps();
    }

    /* ---------------- ✅ Relación con expenses (creados / aprobados) ---------------- */

    public function expensesCreated()
    {
        return $this->hasMany(\App\Models\Expense::class, 'created_by');
    }

    public function expensesApproved()
    {
        return $this->hasMany(\App\Models\Expense::class, 'nip_approved_by');
    }

    /* ---------------- ✅ PIN / NIP helpers (6 dígitos EXACTOS) ---------------- */

    /**
     * Verifica PIN con soporte bcrypt / argon2 / md5 legacy opcional.
     * ✅ Solo acepta exactamente 6 dígitos.
     */
    public function checkApprovalPin(string $plain): bool
    {
        $plain = trim($plain);
        if (!preg_match('/^\d{6}$/', $plain)) {
            return false;
        }

        $stored = $this->approval_pin_hash;
        if (!$stored) return false;

        if (Str::startsWith($stored, ['$2y$', '$2a$', '$2b$'])) {
            return Hash::check($plain, $stored); // bcrypt
        }

        if (Str::startsWith($stored, ['$argon2id$', '$argon2i$'])) {
            return password_verify($plain, $stored); // argon2
        }

        // md5 legacy opcional (solo si lo habilitas)
        if (config('app.allow_legacy_md5', false) && preg_match('/^[a-f0-9]{32}$/i', $stored)) {
            return hash_equals(strtolower($stored), md5($plain));
        }

        return false;
    }

    /**
     * Asigna/actualiza el PIN guardándolo como bcrypt.
     * ✅ Solo acepta exactamente 6 dígitos.
     */
    public function setApprovalPin(string $plain): void
    {
        $plain = trim($plain);

        if (!preg_match('/^\d{6}$/', $plain)) {
            throw new \InvalidArgumentException('El NIP debe ser exactamente de 6 dígitos.');
        }

        $this->approval_pin_hash = Hash::make($plain);
        $this->save();
    }

    /**
     * Verifica y si hace falta, migra a bcrypt (rehash).
     * ✅ Solo acepta exactamente 6 dígitos.
     */
    public function assertApprovalPinOrFail(string $plain): void
    {
        $plain = trim($plain);

        if (!preg_match('/^\d{6}$/', $plain)) {
            abort(422, 'NIP inválido.');
        }

        if (!$this->checkApprovalPin($plain)) {
            abort(422, 'NIP incorrecto.');
        }

        $stored = (string) $this->approval_pin_hash;

        // Migrar a bcrypt si no es bcrypt
        if (!Str::startsWith($stored, ['$2y$', '$2a$', '$2b$'])) {
            $this->approval_pin_hash = Hash::make($plain);
            $this->save();
            return;
        }

        // Rehash si requiere
        if (Hash::needsRehash($stored)) {
            $this->approval_pin_hash = Hash::make($plain);
            $this->save();
        }
    }
}
