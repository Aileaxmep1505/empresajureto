<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicitacionRequestItem extends Model
{
    use HasFactory;

    protected $table = 'licitacion_request_items';

    protected $fillable = [
        'licitacion_id',
        'requisicion_id',
        'licitacion_pdf_page_id',
        'line_raw',
        'descripcion',
        'cantidad',
        'unidad',
        'renglon',
        'status',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    /**
     * Página del PDF de donde salió este renglón.
     */
    public function page()
    {
        return $this->belongsTo(LicitacionPdfPage::class, 'licitacion_pdf_page_id');
    }

    /**
     * Renglón correspondiente en la propuesta económica.
     */
    public function propuestaItem()
    {
        return $this->hasOne(LicitacionPropuestaItem::class, 'licitacion_request_item_id');
    }
}
