<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;

class TicketWorkController extends Controller
{
    public function show(Ticket $ticket)
    {
        // ✅ Carga SOLO lo que existe en el sistema simple
        $ticket->load([
            'assignee',
            'creator',
            'documents.uploader',
        ]);

        // Para el mapa id->name en la vista (historial / asignado)
        $users = User::orderBy('name')->get(['id','name']);

        return view('tickets.work', [
            'ticket'     => $ticket,
            'users'      => $users,
            'statuses'   => TicketController::STATUSES,
            'priorities' => TicketController::PRIORITIES,
            'areas'      => TicketController::AREAS,
        ]);
    }
}