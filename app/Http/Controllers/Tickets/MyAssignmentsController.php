<?php
namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\TicketStage;

class MyAssignmentsController extends Controller
{
  public function index(){
    $stages = TicketStage::with('ticket')
      ->where('assignee_id', auth()->id())
      ->whereIn('status',['pendiente','en_progreso'])
      ->orderBy('due_at')->get();

    return view('tickets.my', compact('stages'));
  }
}
