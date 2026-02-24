<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\{Ticket, TicketDocument, TicketAudit};

class TicketDocumentController extends Controller
{
    public function store(Request $r, Ticket $ticket)
    {
        $data = $r->validate([
            'name'         => ['nullable','string','max:180'],
            'category'     => ['nullable','string','max:60'], // evidencia / doc / link / etc
            'file'         => ['nullable','file','max:40960',
                'mimetypes:image/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'
            ],
            'external_url' => ['nullable','url'],
        ]);

        if (!$r->hasFile('file') && empty($data['external_url'])) {
            return back()->with('err', 'Debes subir un archivo o agregar un link.');
        }

        $baseName = trim((string)($data['name'] ?? 'Adjunto'));
        $category = $data['category'] ?? 'adjunto';

        // ✅ versionado por ticket + nombre base
        $version = (int) (TicketDocument::where('ticket_id',$ticket->id)->where('name',$baseName)->max('version') ?? 0) + 1;

        $path = null;
        if ($r->hasFile('file')) {
            $path = $r->file('file')->store("tickets/{$ticket->id}/attachments");
            if ($baseName === 'Adjunto') {
                $baseName = $r->file('file')->getClientOriginalName();
            }
        }

        $doc = TicketDocument::create([
            'ticket_id'    => $ticket->id,
            'uploaded_by'  => auth()->id(),
            'category'     => $category,
            'name'         => $baseName,
            'path'         => $path,
            'external_url' => $data['external_url'] ?? null,
            'version'      => $version,
            'meta'         => ['type' => $category],
        ]);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'attachment_added',
            'diff'      => ['document_id'=>$doc->id,'version'=>$version],
        ]);

        return back()->with('ok', 'Adjunto agregado.');
    }

    public function download(Ticket $ticket, TicketDocument $doc)
    {
        abort_unless($doc->ticket_id === $ticket->id, 404);

        if ($doc->path && Storage::exists($doc->path)) {
            $ext = pathinfo($doc->path, PATHINFO_EXTENSION);
            $filename = $doc->name . "_v{$doc->version}" . ($ext ? ".{$ext}" : '');
            return Storage::download($doc->path, $filename);
        }

        return back()->with('err','No hay archivo descargable.');
    }

    public function destroy(Ticket $ticket, TicketDocument $doc)
    {
        abort_unless($doc->ticket_id === $ticket->id, 404);

        if ($doc->path && Storage::exists($doc->path)) {
            Storage::delete($doc->path);
        }

        $id = $doc->id;
        $doc->delete();

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'action'    => 'attachment_deleted',
            'diff'      => ['document_id'=>$id],
        ]);

        return back()->with('ok','Adjunto eliminado.');
    }
}