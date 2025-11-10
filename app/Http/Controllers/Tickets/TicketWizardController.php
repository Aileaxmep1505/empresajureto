<?php
namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\{Ticket, TicketStage, TicketDocument, TicketAudit};

class TicketWizardController extends Controller
{
  // Ver paso N
  public function show(Request $r, Ticket $ticket, int $position){
    $stage = $ticket->stages()->where('position',$position)->firstOrFail();
    // Bloqueo secuencial: si hay una anterior no terminada → abort 403
    if ($position > 1) {
      $prevDone = $ticket->stages()->where('position',$position-1)->where('status','terminado')->exists();
      abort_unless($prevDone, 403, 'Debes completar la etapa anterior.');
    }
    $ticket->load(['stages','documents'=>fn($q)=>$q->where('stage_id',$stage->id)->latest()]);
    return view('tickets.wizard.step', compact('ticket','stage'));
  }

  // Empezar etapa
  public function start(Request $r, Ticket $ticket, TicketStage $stage){
    abort_unless($stage->ticket_id === $ticket->id, 404);
    // Solo el asignado o admin pueden comenzar
    $this->authorize('update',$stage); // define tu policy si usas Spatie/Policies

    // Verifica bloqueo secuencial
    if ($stage->position > 1) {
      $prevDone = $ticket->stages()->where('position',$stage->position-1)->where('status','terminado')->exists();
      abort_unless($prevDone, 422, 'Completa la etapa previa.');
    }

    if ($stage->status === 'pendiente') {
      $stage->status = 'en_progreso';
      $stage->started_at = now();
      $stage->save();
      TicketAudit::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'action'=>'stage_started','diff'=>['stage_id'=>$stage->id]]);
      event(new \App\Events\TicketStageUpdated($ticket->id, $stage->id));
    }
    return back()->with('ok','Etapa iniciada');
  }

  // Completar etapa (puede exigir evidencia marcada como requerida)
  public function complete(Request $r, Ticket $ticket, TicketStage $stage){
    abort_unless($stage->ticket_id === $ticket->id, 404);
    $this->authorize('update',$stage);

    // Validar que checklist requerido esté completo (si usas meta['require_checklist'])
    if (($stage->meta['require_checklist'] ?? false) === true) {
      $pending = $stage->checklists()->with('items')->get()->flatMap->items->where('is_done',false)->count();
      abort_if($pending>0, 422, 'Faltan items del checklist.');
    }

    $stage->status = 'terminado';
    $stage->finished_at = now();
    $stage->save();

    $ticket->refreshProgress();
    TicketAudit::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'action'=>'stage_completed','diff'=>['stage_id'=>$stage->id]]);
    event(new \App\Events\TicketStageUpdated($ticket->id, $stage->id));

    return redirect()->route('tickets.wizard.show', [$ticket, $stage->position+1])
      ->with('ok','Etapa completada');
  }

  // Subir evidencia (imagen/video/pdf/office) asociada a la etapa
  public function uploadEvidence(Request $r, Ticket $ticket, TicketStage $stage){
    abort_unless($stage->ticket_id === $ticket->id, 404);
    $this->authorize('update',$stage);

    $data = $r->validate([
      'file'=>['required','file','max:40960','mimetypes:image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation']
    ]);

    $path = $r->file('file')->store("tickets/{$ticket->id}/stage_{$stage->id}");
    $doc  = TicketDocument::create([
      'ticket_id'=>$ticket->id,
      'uploaded_by'=>auth()->id(),
      'stage_id'=>$stage->id,
      'category'=>'evidencia',
      'name'=>$r->file('file')->getClientOriginalName(),
      'path'=>$path,
      'version'=>1,
      'meta'=>['type'=>'evidence'],
    ]);

    TicketAudit::create(['ticket_id'=>$ticket->id,'user_id'=>auth()->id(),'action'=>'evidence_uploaded','diff'=>['document_id'=>$doc->id,'stage_id'=>$stage->id]]);
    event(new \App\Events\TicketStageUpdated($ticket->id, $stage->id));

    return back()->with('ok','Evidencia subida');
  }
}
