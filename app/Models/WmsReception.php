<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WmsReception extends Model
{
    use HasFactory;

    protected $table = 'wms_receptions';

    protected $fillable = [
        'folio',
        'deliverer_user_id',
        'receiver_user_id',
        'deliverer_name',
        'receiver_name',
        'reception_date',
        'observations',
        'status',
        'signature_token',
        'delivered_signature',
        'received_signature',
        'created_by',
    ];

    protected $casts = [
        'reception_date' => 'datetime',
    ];

    public function delivererUser()
    {
        return $this->belongsTo(User::class, 'deliverer_user_id');
    }

    public function receiverUser()
    {
        return $this->belongsTo(User::class, 'receiver_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(WmsReceptionLine::class, 'reception_id')->orderBy('id');
    }

    public function getIsSignedAttribute(): bool
    {
        return !empty($this->delivered_signature) && !empty($this->received_signature);
    }

    public function refreshSignatureStatus(): void
    {
        $newStatus = $this->is_signed ? 'firmado' : 'pendiente';

        if ($this->status !== $newStatus) {
            $this->status = $newStatus;
            $this->save();
        }
    }
}