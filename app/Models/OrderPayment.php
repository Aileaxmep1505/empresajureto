<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $table = 'order_payments';

    protected $fillable = [
        'order_id','provider','amount','currency','status',
        'provider_session_id','provider_payment_intent','raw_payload'
    ];

    protected $casts = [
        'amount' => 'float',
        'raw_payload' => 'array',
    ];

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
