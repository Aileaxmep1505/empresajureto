<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Ticket;

class TicketWorkController extends Controller
{
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'client',
            'owner',
            'links',
            'stages' => fn($q) => $q->orderBy('position'),
            'stages.assignee',
            'stages.checklists' => fn($q) => $q->orderBy('id'),
            'stages.checklists.items' => fn($q) => $q->orderBy('position'),
        ]);

        return view('tickets.work', compact('ticket'));
    }
}
