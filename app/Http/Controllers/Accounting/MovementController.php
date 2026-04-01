<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountMovement;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Services\Accounting\AccountStateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class MovementController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function __construct(private AccountStateService $state)
    {
        // Laravel 12: sin $this->middleware()
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'related_type'  => ['required', 'in:receivable,payable'],
            'related_id'    => ['required', 'integer', 'min:1'],
            'movement_date' => ['required', 'date'],
            'amount'        => ['required', 'numeric', 'min:0.01'],
            'currency'      => ['required', 'in:MXN,USD,EUR'],
            'method'        => ['nullable', 'in:transferencia,efectivo,tarjeta,cheque,otro'],
            'reference'     => ['nullable', 'string', 'max:255'],
            'notes'         => ['nullable', 'string'],
            'expense_id'    => ['nullable', 'integer', 'min:1'],
        ]);

        $email = Auth::user()?->email;
        $data['created_by'] = $email;

        if ($data['related_type'] === 'receivable') {
            $rel = AccountReceivable::findOrFail((int) $data['related_id']);

            if ($email && $rel->created_by && $rel->created_by !== $email) {
                abort(403);
            }

            $data['company_id'] = $rel->company_id;
            $data['direction'] = 'incoming';
        } else {
            $rel = AccountPayable::findOrFail((int) $data['related_id']);

            if ($email && $rel->created_by && $rel->created_by !== $email) {
                abort(403);
            }

            $data['company_id'] = $rel->company_id;
            $data['direction'] = 'outgoing';
        }

        if ($request->hasFile('evidence')) {
            $path = $request->file('evidence')->store('accounting/movements/evidence', 'public');
            $data['evidence_url'] = Storage::disk('public')->url($path);
        }

        if ($request->hasFile('documents')) {
            $urls = [];
            $names = [];

            foreach ((array) $request->file('documents') as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }

                $path = $file->store('accounting/movements', 'public');
                $urls[] = Storage::disk('public')->url($path);
                $names[] = $file->getClientOriginalName();
            }

            $data['documents'] = $urls ?: null;
            $data['document_names'] = $names ?: null;
        }

        DB::transaction(function () use ($data) {
            AccountMovement::create($data);
        });

        $this->state->recalc($data['related_type'], (int) $data['related_id']);

        return back()->with('success', 'Movimiento aplicado correctamente.');
    }

    public function destroy(AccountMovement $movement)
    {
        $email = Auth::user()?->email;

        if ($email && $movement->created_by && $movement->created_by !== $email) {
            abort(403);
        }

        $relatedType = $movement->related_type;
        $relatedId = (int) $movement->related_id;

        $movement->delete();

        $this->state->recalc($relatedType, $relatedId);

        return back()->with('success', 'Movimiento eliminado.');
    }
}