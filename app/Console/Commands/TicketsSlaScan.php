<?php
// app/Console/Commands/TicketsSlaScan.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{Ticket, TicketStage, TicketSlaEvent};
use Illuminate\Support\Facades\Notification;
use App\Notifications\TicketMentioned; // o crea TicketSlaAlert

class TicketsSlaScan extends Command
{
  protected $signature = 'tickets:sla-scan';
  protected $description = 'Escanea vencimientos SLA y dispara alertas';

  public function handle()
  {
    $now = now();

    // Etapas por vencer en <=24h
    $dueSoon = TicketStage::whereNull('finished_at')
      ->whereNotNull('due_at')
      ->where('due_at','>', $now)
      ->whereRaw('TIMESTAMPDIFF(HOUR, ?, due_at) <= 24', [$now])
      ->get();

    foreach ($dueSoon as $st){
      TicketSlaEvent::firstOrCreate([
        'ticket_id'=>$st->ticket_id,'stage_id'=>$st->id,'event'=>'due_soon'
      ], ['fired_at'=>now()]);
      // TODO: notificar a $st->assignee_id o $st->ticket->owner_id
    }

    // Etapas vencidas
    $overdue = TicketStage::whereNull('finished_at')
      ->whereNotNull('due_at')
      ->where('due_at','<=', $now)
      ->get();

    foreach ($overdue as $st){
      TicketSlaEvent::firstOrCreate([
        'ticket_id'=>$st->ticket_id,'stage_id'=>$st->id,'event'=>'overdue'
      ], ['fired_at'=>now()]);
      // TODO: notificar
    }

    $this->info('SLA scan done');
    return 0;
  }
}
