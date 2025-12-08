<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketStage;

class MyAssignmentsController extends Controller
{
    /**
     * Lista de etapas asignadas al usuario actual (vista ejecutor).
     * Ruta sugerida: route('tickets.my')
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $status = $request->string('status')->toString(); // pendiente / en_progreso / terminado / ''

        $query = TicketStage::with(['ticket', 'assignee'])
            ->where('assignee_id', $userId)
            ->whereHas('ticket', function ($q) {
                $q->where('status', '!=', 'cerrado');
            });

        if ($status !== '') {
            $query->where('status', $status);
        }

        $stages = $query
            ->orderByRaw("FIELD(status,'en_progreso','pendiente','terminado')")
            ->orderBy('due_at')
            ->paginate(20)
            ->withQueryString();

        return view('tickets.my', compact('stages', 'status'));
    }
}
