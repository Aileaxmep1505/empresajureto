<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CuentasDashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request)
    {
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Filtro de compañía
        |--------------------------------------------------------------------------
        */

        $companyId = $request->filled('company_id')
            ? (int) $request->input('company_id')
            : null;

        $companies = Company::query()
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Consultas principales
        |--------------------------------------------------------------------------
        |
        | Se eliminó el filtro por created_by porque probablemente era lo que
        | provocaba que el dashboard no mostrara ningún registro.
        |
        */

        $receivablesQuery = AccountReceivable::query()
            ->with('company');

        $payablesQuery = AccountPayable::query()
            ->with('company');

        if ($companyId) {
            $receivablesQuery->where('company_id', $companyId);
            $payablesQuery->where('company_id', $companyId);
        }

        $receivables = $receivablesQuery
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        $payables = $payablesQuery
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Funciones auxiliares
        |--------------------------------------------------------------------------
        */

        $normalizeStatus = static function ($status): string {
            return mb_strtolower(trim((string) $status));
        };

        $parseDate = static function ($value): ?Carbon {
            if (blank($value)) {
                return null;
            }

            try {
                return Carbon::parse($value)->startOfDay();
            } catch (\Throwable $exception) {
                return null;
            }
        };

        $receivableBalance = static function ($receivable): float {
            $amount = (float) ($receivable->amount ?? 0);
            $amountPaid = (float) ($receivable->amount_paid ?? 0);

            return max($amount - $amountPaid, 0);
        };

        $payableBalance = static function ($payable): float {
            $amount = (float) ($payable->amount ?? 0);
            $amountPaid = (float) ($payable->amount_paid ?? 0);

            return max($amount - $amountPaid, 0);
        };

        $isReceivableClosed = static function ($receivable) use ($normalizeStatus): bool {
            return in_array(
                $normalizeStatus($receivable->status ?? ''),
                ['cobrado', 'pagado', 'cancelado'],
                true
            );
        };

        $isPayableClosed = static function ($payable) use ($normalizeStatus): bool {
            return in_array(
                $normalizeStatus($payable->status ?? ''),
                ['pagado', 'cobrado', 'cancelado'],
                true
            );
        };

        /*
        |--------------------------------------------------------------------------
        | KPI: total por cobrar
        |--------------------------------------------------------------------------
        */

        $totalPorCobrar = $receivables
            ->reject($isReceivableClosed)
            ->sum($receivableBalance);

        /*
        |--------------------------------------------------------------------------
        | KPI: total por pagar
        |--------------------------------------------------------------------------
        */

        $totalPorPagar = $payables
            ->reject($isPayableClosed)
            ->sum($payableBalance);

        $totalPorCobrar = (float) $totalPorCobrar;
        $totalPorPagar = (float) $totalPorPagar;
        $balanceNeto = $totalPorCobrar - $totalPorPagar;

        /*
        |--------------------------------------------------------------------------
        | Cobrado durante el mes actual
        |--------------------------------------------------------------------------
        */

        $cobradoMes = $receivables
            ->filter(function ($receivable) use (
                $normalizeStatus,
                $parseDate,
                $today
            ) {
                $status = $normalizeStatus($receivable->status ?? '');

                if (!in_array($status, ['cobrado', 'pagado'], true)) {
                    return false;
                }

                $paymentDate = $parseDate($receivable->payment_date ?? null);

                if (!$paymentDate) {
                    return false;
                }

                return $paymentDate->year === $today->year
                    && $paymentDate->month === $today->month;
            })
            ->sum(function ($receivable) {
                /*
                 * Si deseas sumar únicamente lo efectivamente cobrado,
                 * se usa amount_paid. Si está vacío, se toma amount.
                 */
                $amountPaid = (float) ($receivable->amount_paid ?? 0);

                return $amountPaid > 0
                    ? $amountPaid
                    : (float) ($receivable->amount ?? 0);
            });

        /*
        |--------------------------------------------------------------------------
        | Pagado durante el mes actual
        |--------------------------------------------------------------------------
        */

        $pagadoMes = $payables
            ->filter(function ($payable) use (
                $normalizeStatus,
                $parseDate,
                $today
            ) {
                $status = $normalizeStatus($payable->status ?? '');

                if (!in_array($status, ['pagado', 'cobrado'], true)) {
                    return false;
                }

                $paymentDate = $parseDate($payable->payment_date ?? null);

                if (!$paymentDate) {
                    return false;
                }

                return $paymentDate->year === $today->year
                    && $paymentDate->month === $today->month;
            })
            ->sum(function ($payable) {
                $amountPaid = (float) ($payable->amount_paid ?? 0);

                return $amountPaid > 0
                    ? $amountPaid
                    : (float) ($payable->amount ?? 0);
            });

        $cobradoMes = (float) $cobradoMes;
        $pagadoMes = (float) $pagadoMes;

        /*
        |--------------------------------------------------------------------------
        | Pagos urgentes o atrasados
        |--------------------------------------------------------------------------
        */

        $urgentPayments = $payables
            ->filter(function ($payable) use (
                $isPayableClosed,
                $normalizeStatus,
                $parseDate,
                $today
            ) {
                if ($isPayableClosed($payable)) {
                    return false;
                }

                $status = $normalizeStatus($payable->status ?? '');
                $dueDate = $parseDate($payable->due_date ?? null);

                /*
                 * Se considera urgente si:
                 * - El estado dice urgente o atrasado.
                 * - La fecha ya venció.
                 * - Vence durante los próximos tres días.
                 */
                if (in_array($status, ['urgente', 'atrasado', 'vencido'], true)) {
                    return true;
                }

                if (!$dueDate) {
                    return false;
                }

                if ($dueDate->lt($today)) {
                    return true;
                }

                $daysUntilDue = $today->diffInDays($dueDate, false);

                return $daysUntilDue >= 0 && $daysUntilDue <= 3;
            })
            ->sortBy(function ($payable) use ($parseDate) {
                return $parseDate($payable->due_date ?? null)?->timestamp
                    ?? PHP_INT_MAX;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Cobros vencidos
        |--------------------------------------------------------------------------
        */

        $overdueReceivables = $receivables
            ->filter(function ($receivable) use (
                $isReceivableClosed,
                $parseDate,
                $today
            ) {
                if ($isReceivableClosed($receivable)) {
                    return false;
                }

                $dueDate = $parseDate($receivable->due_date ?? null);

                return $dueDate && $dueDate->lt($today);
            })
            ->sortBy(function ($receivable) use ($parseDate) {
                return $parseDate($receivable->due_date ?? null)?->timestamp
                    ?? PHP_INT_MAX;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Próximos pagos: siguientes 15 días
        |--------------------------------------------------------------------------
        */

        $upcomingPayments = $payables
            ->filter(function ($payable) use (
                $isPayableClosed,
                $parseDate,
                $today
            ) {
                if ($isPayableClosed($payable)) {
                    return false;
                }

                $dueDate = $parseDate($payable->due_date ?? null);

                if (!$dueDate || $dueDate->lt($today)) {
                    return false;
                }

                $daysUntilDue = $today->diffInDays($dueDate, false);

                return $daysUntilDue >= 0 && $daysUntilDue <= 15;
            })
            ->sortBy(function ($payable) use ($parseDate) {
                return $parseDate($payable->due_date ?? null)?->timestamp
                    ?? PHP_INT_MAX;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Próximos cobros: siguientes 15 días
        |--------------------------------------------------------------------------
        */

        $upcomingReceivables = $receivables
            ->filter(function ($receivable) use (
                $isReceivableClosed,
                $parseDate,
                $today
            ) {
                if ($isReceivableClosed($receivable)) {
                    return false;
                }

                $dueDate = $parseDate($receivable->due_date ?? null);

                if (!$dueDate || $dueDate->lt($today)) {
                    return false;
                }

                $daysUntilDue = $today->diffInDays($dueDate, false);

                return $daysUntilDue >= 0 && $daysUntilDue <= 15;
            })
            ->sortBy(function ($receivable) use ($parseDate) {
                return $parseDate($receivable->due_date ?? null)?->timestamp
                    ?? PHP_INT_MAX;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Antigüedad de cartera
        |--------------------------------------------------------------------------
        |
        | Estas claves ahora coinciden con las que busca la vista Blade:
        | Al corriente, 1-30, 31-60, 61-90 y 90+.
        |
        */

        $aging = [
            'Al corriente' => 0.0,
            '1-30' => 0.0,
            '31-60' => 0.0,
            '61-90' => 0.0,
            '90+' => 0.0,
        ];

        foreach ($receivables as $receivable) {
            if ($isReceivableClosed($receivable)) {
                continue;
            }

            $balance = $receivableBalance($receivable);

            if ($balance <= 0) {
                continue;
            }

            $dueDate = $parseDate($receivable->due_date ?? null);

            /*
             * Sin fecha o todavía no vencida.
             */
            if (!$dueDate || $dueDate->gte($today)) {
                $aging['Al corriente'] += $balance;
                continue;
            }

            $daysOverdue = $dueDate->diffInDays($today);

            if ($daysOverdue <= 30) {
                $aging['1-30'] += $balance;
            } elseif ($daysOverdue <= 60) {
                $aging['31-60'] += $balance;
            } elseif ($daysOverdue <= 90) {
                $aging['61-90'] += $balance;
            } else {
                $aging['90+'] += $balance;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Flujo proyectado: próximos 30 días
        |--------------------------------------------------------------------------
        */

        $labels = [];
        $inByDay = [];
        $outByDay = [];
        $netByDay = [];

        /*
         * Se indexan los registros por fecha para evitar recorrer todas las
         * colecciones nuevamente por cada uno de los 31 días.
         */

        $receivablesByDate = $receivables
            ->reject($isReceivableClosed)
            ->filter(fn ($receivable) => filled($receivable->due_date))
            ->groupBy(function ($receivable) use ($parseDate) {
                return $parseDate($receivable->due_date ?? null)?->toDateString()
                    ?? 'invalid-date';
            });

        $payablesByDate = $payables
            ->reject($isPayableClosed)
            ->filter(fn ($payable) => filled($payable->due_date))
            ->groupBy(function ($payable) use ($parseDate) {
                return $parseDate($payable->due_date ?? null)?->toDateString()
                    ?? 'invalid-date';
            });

        for ($day = 0; $day <= 30; $day++) {
            $date = $today->copy()->addDays($day);
            $dateKey = $date->toDateString();

            $labels[] = $date->format('d/m');

            $incoming = collect($receivablesByDate->get($dateKey, []))
                ->sum($receivableBalance);

            $outgoing = collect($payablesByDate->get($dateKey, []))
                ->sum($payableBalance);

            $incoming = (float) $incoming;
            $outgoing = (float) $outgoing;

            $inByDay[] = $incoming;
            $outByDay[] = $outgoing;
            $netByDay[] = $incoming - $outgoing;
        }

        /*
        |--------------------------------------------------------------------------
        | Total de alertas
        |--------------------------------------------------------------------------
        */

        $alertsCount = $urgentPayments->count()
            + $overdueReceivables->count();

        return view('accounting.cuentas_dashboard', compact(
            'companies',
            'companyId',
            'totalPorCobrar',
            'totalPorPagar',
            'balanceNeto',
            'cobradoMes',
            'pagadoMes',
            'alertsCount',
            'urgentPayments',
            'overdueReceivables',
            'upcomingPayments',
            'upcomingReceivables',
            'aging',
            'labels',
            'inByDay',
            'outByDay',
            'netByDay'
        ));
    }
}