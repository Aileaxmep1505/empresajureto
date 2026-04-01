<?php

namespace App\Services\Accounting;

use App\Models\AccountMovement;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountStateService
{
    public function recalc(string $relatedType, int $relatedId): void
    {
        if ($relatedType === 'receivable') {
            $r = AccountReceivable::query()->findOrFail($relatedId);
            $this->recalcReceivable($r);
            return;
        }

        if ($relatedType === 'payable') {
            $p = AccountPayable::query()->findOrFail($relatedId);
            $this->recalcPayable($p);
            return;
        }

        throw new \InvalidArgumentException("related_type inválido: {$relatedType}");
    }

    public function recalcReceivable(AccountReceivable $r): void
    {
        DB::transaction(function () use ($r) {
            $paid = (float) AccountMovement::query()
                ->where('related_type', 'receivable')
                ->where('related_id', $r->id)
                ->where('status', 'aplicado')
                ->sum('amount');

            $r->amount_paid = $paid;

            if ($r->status !== 'cancelado') {
                $today = Carbon::today();
                $due = $r->due_date ? Carbon::parse($r->due_date) : null;

                if ($paid <= 0) {
                    $r->status = ($due && $today->gt($due)) ? 'vencido' : 'pendiente';
                    $r->payment_date = null;
                } elseif ($paid + 0.0001 < (float) $r->amount) {
                    $r->status = ($due && $today->gt($due)) ? 'vencido' : 'parcial';
                    $r->payment_date = null;
                } else {
                    $r->status = 'cobrado';
                    $last = AccountMovement::query()
                        ->where('related_type', 'receivable')
                        ->where('related_id', $r->id)
                        ->where('status', 'aplicado')
                        ->orderByDesc('movement_date')
                        ->first();
                    $r->payment_date = $last?->movement_date;
                }
            }

            $r->save();
        });
    }

    public function recalcPayable(AccountPayable $p): void
    {
        DB::transaction(function () use ($p) {
            $paid = (float) AccountMovement::query()
                ->where('related_type', 'payable')
                ->where('related_id', $p->id)
                ->where('status', 'aplicado')
                ->sum('amount');

            $p->amount_paid = $paid;

            if ($p->status !== 'cancelado') {
                $today = Carbon::today();
                $due = $p->due_date ? Carbon::parse($p->due_date) : null;

                // urgencia: si faltan <= reminder_days_before y no está pagado completo
                $daysBefore = (int) ($p->reminder_days_before ?? 3);
                $isNearDue = $due ? $today->diffInDays($due, false) <= $daysBefore : false;

                if ($paid <= 0) {
                    if ($due && $today->gt($due)) $p->status = 'atrasado';
                    else $p->status = $isNearDue ? 'urgente' : 'pendiente';
                    $p->payment_date = null;
                } elseif ($paid + 0.0001 < (float) $p->amount) {
                    $p->status = ($due && $today->gt($due)) ? 'atrasado' : 'parcial';
                    $p->payment_date = null;
                } else {
                    $p->status = 'pagado';
                    $last = AccountMovement::query()
                        ->where('related_type', 'payable')
                        ->where('related_id', $p->id)
                        ->where('status', 'aplicado')
                        ->orderByDesc('movement_date')
                        ->first();
                    $p->payment_date = $last?->movement_date;
                }
            }

            $p->save();
        });
    }
}