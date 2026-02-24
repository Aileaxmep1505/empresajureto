<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;

class MyAssignmentsController extends Controller
{
    public function index(Request $request)
    {
        $userId  = auth()->id();
        $status  = trim((string) $request->string('status'));
        $area    = trim((string) $request->string('area'));
        $q       = trim((string) $request->string('q'));

        $query = Ticket::query()
            ->with(['assignee','creator'])
            ->where('assignee_id', $userId)
            ->when($status !== '', fn($qq) => $qq->where('status', $status))
            ->when($area !== '', fn($qq) => $qq->where('area', $area))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('title','like',"%{$q}%")
                      ->orWhere('description','like',"%{$q}%")
                      ->orWhere('folio','like',"%{$q}%");
                });
            })
            ->orderByRaw("FIELD(status,'progreso','revision','pendiente','bloqueado','pruebas','completado','cancelado')")
            ->orderBy('due_at')
            ->latest();

        $tickets = $query->paginate(20)->withQueryString();

        return view('tickets.my', compact('tickets','status','area','q'));
    }
}