<?php
// app/Http/Controllers/Tickets/DashboardController.php
namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
  public function index(){
    $byStatus = Ticket::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c','status');
    $byPriority = Ticket::select('priority', DB::raw('count(*) as c'))->groupBy('priority')->pluck('c','priority');
    $avgResolution = Ticket::whereNotNull('closed_at')->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, opened_at, closed_at)) as avg_hours'))->value('avg_hours') ?? 0;

    return view('tickets.dashboard', compact('byStatus','byPriority','avgResolution'));
  }
}
