<?php
namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TicketUpdated implements ShouldBroadcast
{
  public function __construct(public int $ticketId){}
  public function broadcastOn(){ return new PrivateChannel("tickets.{$this->ticketId}"); }
  public function broadcastAs(){ return 'TicketUpdated'; }
}
