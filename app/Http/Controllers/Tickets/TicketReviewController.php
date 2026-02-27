<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TicketReviewController extends Controller
{
    /**
     * ✅ Aprobar y cerrar (calificación)
     */
    public function approve(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'review_rating'  => ['required','integer','min:1','max:5'],
            'review_comment' => ['nullable','string','max:5000'],
        ]);

        DB::transaction(function() use ($ticket, $data, $request){

            // Marca como aprobado / completado (ajusta a tus columnas reales)
            // Si no tienes columnas, al menos cambia estatus y guarda en reopen_reason/review_comment.
            if (schema_has('tickets','review_status')) {
                $ticket->review_status = 'approved';
            }
            if (schema_has('tickets','review_approved_at')) {
                $ticket->review_approved_at = now();
            }
            if (schema_has('tickets','review_rating')) {
                $ticket->review_rating = (int)$data['review_rating'];
            }
            if (schema_has('tickets','review_comment')) {
                $ticket->review_comment = $data['review_comment'] ?? null;
            }

            // ✅ deja el ticket como completado
            $ticket->status = 'completado';
            if (schema_has('tickets','completed_at')) {
                $ticket->completed_at = now();
            }

            $ticket->save();

            // ✅ Audit (si tienes relación audits/bitácora, aquí lo llamas)
            if (method_exists($ticket, 'audits')) {
                $ticket->audits()->create([
                    'action' => 'ticket_review_approved',
                    'user_id' => auth()->id(),
                    'diff' => [
                        'review_rating' => (int)$data['review_rating'],
                        'review_comment' => $data['review_comment'] ?? null,
                    ],
                ]);
            }
        });

        return back()->with('ok', 'Ticket aprobado y cerrado.');
    }

    /**
     * ✅ Reabrir para corrección (motivo + evidencias)
     */
    public function forceReopen(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'reason'      => ['required','string','min:5','max:5000'],
            'assignee_id' => ['nullable','integer','exists:users,id'],
            'back_to'     => ['required', Rule::in(['reabierto','progreso','revision','pruebas','pendiente','bloqueado'])],
            'files.*'     => ['nullable','file','max:51200'], // 50MB c/u
        ]);

        DB::transaction(function() use ($ticket, $data, $request){

            // ✅ Estatus de regreso
            $ticket->status = $data['back_to'];

            // ✅ Guardar motivo (usa la columna que tengas)
            if (schema_has('tickets','reopen_reason')) {
                $ticket->reopen_reason = $data['reason'];
            } elseif (schema_has('tickets','cancel_reason')) {
                // fallback por si no existe reopen_reason
                $ticket->cancel_reason = $data['reason'];
            }

            // ✅ Cambiar asignado (si aplica)
            if (!empty($data['assignee_id'])) {
                $ticket->assignee_id = (int)$data['assignee_id'];
            }

            // ✅ Si tienes columnas de revisión, marcar como rechazado/reabierto
            if (schema_has('tickets','review_status')) {
                $ticket->review_status = 'rejected';
            }
            if (schema_has('tickets','review_decision')) {
                $ticket->review_decision = 'rejected';
            }

            // ✅ Quitar completado/cancelado si tienes timestamps
            if (schema_has('tickets','completed_at')) $ticket->completed_at = null;
            if (schema_has('tickets','cancelled_at')) $ticket->cancelled_at = null;

            $ticket->save();

            // ✅ Guardar evidencias (si tú manejas tabla documents, aquí puedes integrarlo)
            // Guardado simple en storage (ajusta carpeta)
            $paths = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $f) {
                    if (!$f) continue;
                    $paths[] = $f->store("tickets/{$ticket->id}/reopen", 'public');
                }
            }

            // ✅ Audit
            if (method_exists($ticket, 'audits')) {
                $ticket->audits()->create([
                    'action' => 'ticket_force_reopened',
                    'user_id' => auth()->id(),
                    'diff' => [
                        'reason' => $data['reason'],
                        'back_to' => $data['back_to'],
                        'assignee_id' => $data['assignee_id'] ?? null,
                        'files' => $paths,
                    ],
                ]);
            }
        });

        return back()->with('ok', 'Ticket reabierto para corrección.');
    }
}

/**
 * ✅ helper local (sin romper si Schema no está importado en este archivo)
 */
function schema_has(string $table, string $column): bool
{
    try {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    } catch (\Throwable $e) {
        return false;
    }
}