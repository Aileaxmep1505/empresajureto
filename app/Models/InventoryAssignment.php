<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAssignment extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'user_id',
        'quantity',
        'signature',
        'assigned_at',
        'folio',
        'notes',
        'status',
        'return_reason',
        'return_details',
        'return_condition',
        'returned_at',
        // ✅ Firma por QR / link
        'sign_token',
        'signature_status',
        'signed_at',
        'signer_name',
        'signature_image',
        'delivery_checklist',
        // ✅ Quién entrega / quién recibe
        'delivered_by',
        'received_by',
        // ✅ Devolución ampliada
        'return_checklist',
        'return_images',
    ];

    protected $casts = [
        'assigned_at'        => 'datetime',
        'returned_at'        => 'datetime',
        'signed_at'          => 'datetime',
        'delivery_checklist' => 'array',
        'return_checklist'   => 'array',
        'return_images'      => 'array',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getIsSignedAttribute(): bool
    {
        return $this->signature_status === 'signed';
    }
}