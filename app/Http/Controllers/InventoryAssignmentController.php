<?php

namespace App\Http\Controllers;

use App\Models\InventoryAssignment;
use App\Models\InventoryItem;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InventoryAssignmentController extends Controller
{
    public function index()
    {
        $assignments = InventoryAssignment::with(['item.category', 'user'])
            ->orderByDesc('assigned_at')
            ->orderByDesc('id')
            ->get();

        $items = InventoryItem::with('category')
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        $users = User::orderBy('name')->get();

        $activeCount = $assignments->where('status', 'activa')->count();

        return view('inventory.assignments.index', compact(
            'assignments',
            'items',
            'users',
            'activeCount'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'inventory_item_id'  => 'required|exists:inventory_items,id',
            'user_id'            => 'required|exists:users,id',
            'delivered_by'       => 'required|exists:users,id',
            'received_by'        => 'required|exists:users,id',
            'quantity'           => 'required|integer|min:1',
            'notes'              => 'nullable|string',
            'delivery_checklist' => 'nullable|string', // JSON desde el front
        ]);

        $item = InventoryItem::findOrFail($data['inventory_item_id']);
        $user = User::findOrFail($data['user_id']);

        if ((int) $item->stock < (int) $data['quantity']) {
            return back()->with('bad', 'No hay stock suficiente para asignar.');
        }

        // Checklist (JSON -> array)
        $checklist = null;
        if (!empty($data['delivery_checklist'])) {
            $decoded = json_decode($data['delivery_checklist'], true);
            if (is_array($decoded)) {
                $checklist = $decoded;
            }
        }

        $item->decrement('stock', (int) $data['quantity']);

        if ($item->type === 'activo_fijo') {
            $item->update(['asset_status' => 'asignado']);
        }

        $assignment = InventoryAssignment::create([
            'inventory_item_id'  => $item->id,
            'user_id'            => $user->id,
            'delivered_by'       => $data['delivered_by'],
            'received_by'        => $data['received_by'],
            'quantity'           => $data['quantity'],
            'notes'              => $data['notes'] ?? null,
            'delivery_checklist' => $checklist,
            'signature'          => '', // se firma luego desde el celular (ver signature_image)
            'folio'              => strtoupper(Str::random(8)),
            'sign_token'         => Str::random(48),
            'signature_status'   => 'pending',
            'status'             => 'activa',
            'assigned_at'        => now(),
        ]);

        // Volvemos al índice y abrimos automáticamente el modal de firma (QR)
        return redirect()
            ->route('assets.assignments.index')
            ->with('open_sign', $assignment->id);
    }

    /* =======================================================
       FIRMA PÚBLICA (por token, sin login) — celular
       ======================================================= */

    public function signShow($token)
    {
        $assignment = InventoryAssignment::with(['item.category', 'user'])
            ->where('sign_token', $token)
            ->firstOrFail();

        return view('inventory.assignments.sign', compact('assignment'));
    }

    public function signStore(Request $request, $token)
    {
        $assignment = InventoryAssignment::where('sign_token', $token)->firstOrFail();

        if ($assignment->signature_status === 'signed') {
            return redirect()->route('assignments.public.show', $token);
        }

        $data = $request->validate([
            'signature'   => 'required|string',
            'signer_name' => 'nullable|string|max:120',
        ]);

        $assignment->update([
            'signature_image'  => $data['signature'],
            'signer_name'      => $data['signer_name'] ?? ($assignment->user->name ?? null),
            'signature_status' => 'signed',
            'signed_at'        => now(),
        ]);

        return redirect()->route('assignments.public.show', $token);
    }

    /* =======================================================
       POLLING (escritorio admin) — estado en tiempo real
       ======================================================= */

    public function signStatus(InventoryAssignment $assignment)
    {
        return response()->json([
            'status'    => $assignment->signature_status,
            'signed'    => $assignment->signature_status === 'signed',
            'signature' => $assignment->signature_image,
            'signed_at' => optional($assignment->signed_at)->format('d/m/Y H:i'),
            'signer'    => $assignment->signer_name,
        ]);
    }

    /* =======================================================
       DEVOLUCIÓN — imágenes + checklist + quién entrega/recibe
       ======================================================= */

    public function returnAsset(Request $request, InventoryAssignment $assignment)
    {
        $data = $request->validate([
            'delivered_by'     => 'required|exists:users,id',
            'received_by'      => 'required|exists:users,id',
            'return_reason'    => 'required|string',
            'return_details'   => 'required|string',
            'return_condition' => 'required|in:excelente,bueno,regular,malo,dañado',
            'return_checklist' => 'nullable|array',
            'return_images'    => 'nullable|array|max:3',
            'return_images.*'  => 'nullable|image|max:5120', // 5 MB c/u
        ]);

        if ($assignment->status !== 'activa') {
            return back()->with('bad', 'Esta asignación ya fue devuelta.');
        }

        $item = InventoryItem::findOrFail($assignment->inventory_item_id);

        $item->increment('stock', (int) $assignment->quantity);

        if ($item->type === 'activo_fijo') {
            $item->update([
                'asset_status' => 'disponible',
                'condition'    => $data['return_condition'] === 'dañado' ? 'malo' : ($item->condition ?? 'bueno'),
            ]);
        }

        // Guardar hasta 3 imágenes en storage/app/public/returns
        $paths = [];
        if ($request->hasFile('return_images')) {
            foreach ($request->file('return_images') as $img) {
                if ($img && $img->isValid()) {
                    $paths[] = $img->store('returns', 'public');
                }
            }
        }

        $assignment->update([
            'status'           => 'devuelta',
            'delivered_by'     => $data['delivered_by'],
            'received_by'      => $data['received_by'],
            'return_reason'    => $data['return_reason'],
            'return_details'   => $data['return_details'],
            'return_condition' => $data['return_condition'],
            'return_checklist' => $data['return_checklist'] ?? [],
            'return_images'    => $paths,
            'returned_at'      => now(),
        ]);

        return redirect()->route('assets.assignments.index')->with('ok', 'Activo devuelto correctamente.');
    }

    public function pdf(InventoryAssignment $assignment)
    {
        $assignment->load(['item.category', 'user', 'deliveredBy', 'receivedBy']);

        $pdf = Pdf::loadView('inventory.assignments.pdf', compact('assignment'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('carta_responsiva_' . $assignment->folio . '.pdf');
    }
}