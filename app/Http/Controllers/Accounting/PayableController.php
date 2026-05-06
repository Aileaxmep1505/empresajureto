<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountPayable;
use App\Models\AccountReceivable;
use App\Models\Company;
use App\Models\Publication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AlertsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $email = $user?->email;
        $userId = $user?->id;

        $today = Carbon::today();

        $companies = Company::orderBy('name')->get();
        $companyId = $request->filled('company_id') ? (int) $request->company_id : null;

        /*
        |--------------------------------------------------------------------------
        | Sincronizar publicaciones de tipo venta a cuentas por cobrar
        |--------------------------------------------------------------------------
        | Si una Publication es venta, debe existir como AccountReceivable
        | para que pueda aparecer en alertas, vencimientos y cuentas por cobrar.
        */
        $this->syncVentaPublicationsToReceivables($companyId, $email, $userId);

        $rx = AccountReceivable::query()->with('company');
        $px = AccountPayable::query()->with('company');

        /*
        |--------------------------------------------------------------------------
        | Filtro por usuario
        |--------------------------------------------------------------------------
        | Compatible con created_by guardado como email o como ID de usuario.
        */
        if ($userId || $email) {
            $rx->where(function ($q) use ($userId, $email) {
                if ($userId) {
                    $q->orWhere('created_by', $userId);
                }

                if ($email) {
                    $q->orWhere('created_by', $email);
                }
            });

            $px->where(function ($q) use ($userId, $email) {
                if ($userId) {
                    $q->orWhere('created_by', $userId);
                }

                if ($email) {
                    $q->orWhere('created_by', $email);
                }
            });
        }

        if ($companyId) {
            $rx->where('company_id', $companyId);
            $px->where('company_id', $companyId);
        }

        $receivables = $rx->get();
        $payables = $px->get();

        $urgentPayments = $payables->filter(function ($p) use ($today) {
            if (in_array($p->status, ['pagado', 'cancelado'], true)) {
                return false;
            }

            $due = $p->due_date ? Carbon::parse($p->due_date) : null;

            if (!$due) {
                return false;
            }

            return in_array($p->status, ['urgente', 'atrasado'], true) || $due->lt($today);
        })->sortBy(fn($p) => $p->due_date)->values();

        $overdueReceivables = $receivables->filter(function ($r) use ($today) {
            if (in_array($r->status, ['cobrado', 'cancelado'], true)) {
                return false;
            }

            $due = $r->due_date ? Carbon::parse($r->due_date) : null;

            return $due ? $due->lt($today) : false;
        })->sortBy(fn($r) => $r->due_date)->values();

        return view('accounting.alerts', compact(
            'companies',
            'companyId',
            'urgentPayments',
            'overdueReceivables'
        ));
    }

    private function syncVentaPublicationsToReceivables(?int $companyId, ?string $email, $userId): void
    {
        if (!class_exists(Publication::class)) {
            return;
        }

        $publicationModel = new Publication();
        $publicationTable = $publicationModel->getTable();

        $receivableModel = new AccountReceivable();
        $receivableTable = $receivableModel->getTable();

        if (!Schema::hasTable($publicationTable) || !Schema::hasTable($receivableTable)) {
            return;
        }

        $publicationColumns = Schema::getColumnListing($publicationTable);
        $receivableColumns = Schema::getColumnListing($receivableTable);

        /*
        |--------------------------------------------------------------------------
        | Detectar columna que indica si la publicación es venta
        |--------------------------------------------------------------------------
        */
        $saleColumn = collect([
            'type',
            'publication_type',
            'operation_type',
            'operation',
            'transaction_type',
            'business_type',
            'category',
        ])->first(fn($column) => in_array($column, $publicationColumns, true));

        if (!$saleColumn) {
            return;
        }

        $query = Publication::query()
            ->whereIn($saleColumn, [
                'venta',
                'Venta',
                'VENTA',
                'sale',
                'Sale',
                'SALE',
            ]);

        if ($companyId && in_array('company_id', $publicationColumns, true)) {
            $query->where('company_id', $companyId);
        }

        if (($userId || $email) && in_array('created_by', $publicationColumns, true)) {
            $query->where(function ($q) use ($userId, $email) {
                if ($userId) {
                    $q->orWhere('created_by', $userId);
                }

                if ($email) {
                    $q->orWhere('created_by', $email);
                }
            });
        }

        $publications = $query->get();

        foreach ($publications as $publication) {
            $reference = 'PUB-' . $publication->id;

            $existsQuery = AccountReceivable::query();

            $existsQuery->where(function ($q) use ($receivableColumns, $publication, $reference) {
                if (in_array('publication_id', $receivableColumns, true)) {
                    $q->orWhere('publication_id', $publication->id);
                }

                if (in_array('reference', $receivableColumns, true)) {
                    $q->orWhere('reference', $reference);
                }
            });

            $exists = $existsQuery->exists();

            if ($exists) {
                continue;
            }

            $amount = $this->firstAvailableValue($publication, [
                'amount',
                'price',
                'total',
                'sale_price',
                'selling_price',
                'final_price',
                'value',
            ], 0);

            if ((float) $amount <= 0) {
                continue;
            }

            $receivable = new AccountReceivable();

            $payload = [
                'publication_id' => $publication->id,
                'company_id'    => $publication->company_id ?? null,

                'client_name'   => $this->firstAvailableValue($publication, [
                    'client_name',
                    'customer_name',
                    'buyer_name',
                    'contact_name',
                    'name',
                    'title',
                ], 'Cliente / Venta'),

                'title'         => $this->firstAvailableValue($publication, [
                    'title',
                    'name',
                    'description',
                ], 'Venta de publicación'),

                'description'   => $this->firstAvailableValue($publication, [
                    'description',
                    'details',
                    'notes',
                    'title',
                    'name',
                ], 'Venta generada desde publicación'),

                'reference'     => $reference,
                'amount'        => (float) $amount,
                'amount_paid'   => 0,
                'status'        => 'pendiente',

                'due_date'      => $this->firstAvailableValue($publication, [
                    'due_date',
                    'payment_due_date',
                    'expires_at',
                    'created_at',
                ], now()->addDays(15)),

                'created_by'    => $publication->created_by ?? $email ?? $userId,
            ];

            foreach ($payload as $column => $value) {
                if (in_array($column, $receivableColumns, true)) {
                    $receivable->{$column} = $value;
                }
            }

            $receivable->save();
        }
    }

    private function firstAvailableValue($model, array $fields, $default = null)
    {
        foreach ($fields as $field) {
            if (isset($model->{$field}) && $model->{$field} !== '') {
                return $model->{$field};
            }
        }

        return $default;
    }
}