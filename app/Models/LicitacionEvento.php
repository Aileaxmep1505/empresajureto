<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicitacionEvento extends Model
{
    use HasFactory;

    protected $fillable = [
        'licitacion_id',
        'agenda_event_id',
        'tipo',
    ];

    public function licitacion()
    {
        return $this->belongsTo(Licitacion::class);
    }

    public function agendaEvent()
    {
        return $this->belongsTo(AgendaEvent::class);
    }
}
