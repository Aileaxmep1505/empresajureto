<?php
// app/Http/Controllers/Tickets/TicketDocumentController.php
namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{Ticket, TicketDocument, TicketAudit};

class TicketDocumentController extends Controller
{
  public function store(Request $r, Ticket $ticket){
    $data = $r->validate([
      'name'=>['required','string','max:180'],
      'category'=>['nullable','string','max:60'],
      'stage_id'=>['nullable','integer'],
      'file'=>['nullable','file','max:25600'], // 25MB
      'external_url'=>['nullable','url'],
    ]);

    $version = (int) (TicketDocument::where('ticket_id',$ticket->id)->where('name',$data['name'])->max('version') ?? 0) + 1;

    $path = null;
    if ($r->hasFile('file')) {
      $path = $r->file('file')->store("tickets/{$ticket->id}");
    }

    $doc = TicketDocument::create([
      'ticket_id'=>$ticket->id,
      'uploaded_by'=>auth()->id(),
      'stage_id'=>$data['stage_id'] ?? null,
      'category'=>$data['category'] ?? null,
      'name'=>$data['name'],
      'path'=>$path,
      'external_url'=>$data['external_url'] ?? null,
      'version'=>$version,
    ]);

    TicketAudit::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'action'=>'doc_uploaded','diff'=>['document_id'=>$doc->id,'version'=>$version]]);
    return back()->with('ok','Documento agregado');
  }

  public function download(Ticket $ticket, TicketDocument $doc){
    abort_unless($doc->ticket_id === $ticket->id, 404);
    if ($doc->path && Storage::exists($doc->path)) {
      return Storage::download($doc->path, $doc->name . "_v{$doc->version}." . pathinfo($doc->path, PATHINFO_EXTENSION));
    }
    return back()->with('err','No hay archivo descargable');
  }
}
