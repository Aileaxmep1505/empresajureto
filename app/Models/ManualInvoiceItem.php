<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'manual_invoice_id',
        'product_id',
        'description',
        'sku',
        'unit',
        'unit_code',
        'product_key',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
        'tax',
        'total',
        'tax_rate',
    ];

    /* =====================
       Relaciones
    ======================*/

    public function invoice()
    {
        return $this->belongsTo(ManualInvoice::class, 'manual_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
