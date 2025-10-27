<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'approved_at',
        'avatar_path',
        // si quieres, agrega aquí 'avatar_updated_at' si tienes la columna
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'approved_at'       => 'datetime',
        'password'          => 'hashed',
        // 'avatar_updated_at' => 'datetime', // solo si la columna existe
    ];

    /**
     * ¿Cuenta aprobada?
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * URL pública del avatar con fallback estable.
     * - Si existe el archivo en el disco 'public', se sirve por la ruta /media (media.show)
     *   anexando ?v=timestamp para forzar refresh del cache del navegador.
     * - Si no hay archivo, usa Gravatar.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            $ts  = Storage::disk('public')->lastModified($this->avatar_path);

            // Opción estable: ruta controlada /media/{path}
            // Asegúrate de tener una ruta nombrada 'media.show'
            // que lea del disco 'public' y devuelva el archivo.
            return route('media.show', ['path' => $this->avatar_path, 'v' => $ts]);

            // Si prefieres servir /storage (symlink):
            // $url = Storage::disk('public')->url($this->avatar_path);
            // return $url.'?v='.$ts;
        }

        $hash = md5(strtolower(trim($this->email ?? '')));
        return "https://www.gravatar.com/avatar/{$hash}?s=300&d=mp";
    }

    /**
     * Accessor auxiliar: "avatar_updated_at"
     * Devuelve un Carbon (o null) a partir del mtime del archivo del avatar.
     * Útil para cache-busting en vistas incluso si no existe la columna en BD.
     */
    public function getAvatarUpdatedAtAttribute()
    {
        if ($this->avatar_path && Storage::disk('public')->exists($this->avatar_path)) {
            $ts = Storage::disk('public')->lastModified($this->avatar_path);
            try {
                return \Illuminate\Support\Carbon::createFromTimestamp($ts);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }
public function shippingAddresses()
{
    return $this->hasMany(\App\Models\ShippingAddress::class);
}

public function defaultShippingAddress()
{
    return $this->hasOne(\App\Models\ShippingAddress::class)
        ->where('is_default', true);
}
// app/Models/User.php
public function billingProfiles(){
    return $this->hasMany(\App\Models\BillingProfile::class);
}
public function defaultBillingProfile(){
    return $this->hasOne(\App\Models\BillingProfile::class)->where('is_default', true);
}
public function comments()
{
    return $this->hasMany(\App\Models\Comment::class);
}
public function favorites() {
    return $this->belongsToMany(\App\Models\CatalogItem::class, 'favorites')->withTimestamps();
}


}
