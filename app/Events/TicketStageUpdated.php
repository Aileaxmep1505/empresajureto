<?php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class TicketStageUpdated implements ShouldBroadcast
{
  use SerializesModels;

  public function __construct(public int $ticketId, public int $stageId){}

  public function broadcastOn(){ return new PrivateChannel("tickets.{$this->ticketId}"); }

  public function broadcastAs(){ return 'TicketStageUpdated'; }

  public function broadcastWith(){ return ['ticket_id'=>$this->ticketId,'stage_id'=>$this->stageId]; }
}
