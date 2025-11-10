<?php
// app/Models/Ticket.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'folio','title','created_by','client_id','client_name','type','priority','status',
        'opened_at','closed_at','owner_id','due_at','progress','meta',
        'numero_licitacion','monto_propuesta','fecha_entrega_docs',
        'fecha_apertura_tecnica','fecha_apertura_economica','estatus_adjudicacion'
    ];

    protected $casts = [
        'opened_at'               => 'datetime',
        'closed_at'               => 'datetime',
        'due_at'                  => 'datetime',
        'progress'                => 'integer',
        'monto_propuesta'         => 'decimal:2',
        'meta'                    => 'array',
        'fecha_entrega_docs'      => 'date',
        'fecha_apertura_tecnica'  => 'date',
        'fecha_apertura_economica'=> 'date',
    ];

    public function stages(): HasMany   { return $this->hasMany(TicketStage::class)->orderBy('position'); }
    public function comments(): HasMany  { return $this->hasMany(TicketComment::class)->latest(); }
    public function documents(): HasMany { return $this->hasMany(TicketDocument::class)->latest(); }
    public function links(): HasMany     { return $this->hasMany(TicketLink::class); }
    public function audits(): HasMany    { return $this->hasMany(TicketAudit::class)->latest(); }
    public function followers(): HasMany { return $this->hasMany(TicketFollower::class); }
    public function slaEvents(): HasMany { return $this->hasMany(TicketSlaEvent::class); }
    public function creator()            { return $this->belongsTo(\App\Models\User::class,'created_by'); }
    public function client()             { return $this->belongsTo(\App\Models\Client::class,'client_id'); }

    // Accessor: úsalo como $ticket->sla_signal
    public function getSlaSignalAttribute(): string
    {
        if (!$this->due_at) return 'neutral';
        $now = now();
        if ($now->gt($this->due_at)) return 'overdue';
        if ($now->diffInHours($this->due_at) <= 24) return 'due_soon';
        return 'ok';
    }

    public function refreshProgress(): void
    {
        $total = max(1, $this->stages()->count());
        $done  = $this->stages()->where('status','terminado')->count();
        $p = (int) round(($done * 100) / $total);
        $this->update(['progress' => $p]);
    }
}
