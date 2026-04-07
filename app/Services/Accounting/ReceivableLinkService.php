<?php

namespace App\Services\Accounting;

use App\Models\AccountReceivable;
use App\Models\AgendaEvent;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ReceivableLinkService
{
    public function __construct(
        private AccountStateService $state
    ) {}

    public function syncFromSource(Model $source, array $payload): AccountReceivable
    {
        $sourceType = get_class($source);
        $sourceId   = (int) $source->getKey();

        $amount     = (float) ($payload['amount'] ?? 0);
        $amountPaid = (float) ($payload['amount_paid'] ?? 0);
        $status     = $this->normalizeStatus((string) ($payload['status'] ?? 'pendiente'), $amount, $amountPaid);

        $issueDate  = $payload['issue_date'] ?? now()->toDateString();
        $dueDate    = $payload['due_date'] ?? $issueDate;

        $creditDays = 0;
        try {
            $creditDays = max(
                Carbon::parse($issueDate)->diffInDays(Carbon::parse($dueDate), false),
                0
            );
        } catch (\Throwable $e) {
            $creditDays = 0;
        }

        $receivable = AccountReceivable::query()->firstOrNew([
            'source_type' => $sourceType,
            'source_id'   => $sourceId,
        ]);

        $data = [
            'company_id'            => (int) ($payload['company_id'] ?? 0),

            'source_module'         => (string) ($payload['source_module'] ?? 'sales'),
            'source_type'           => $sourceType,
            'source_id'             => $sourceId,
            'source_folio'          => $payload['source_folio'] ?? null,

            'title'                 => $payload['title'] ?? null,
            'client_name'           => $payload['client_name'] ?? 'Sin cliente',
            'client_id'             => $payload['client_id'] ?? null,
            'folio'                 => $payload['folio'] ?? null,
            'reference'             => $payload['reference'] ?? null,
            'invoice_number'        => $payload['invoice_number'] ?? null,
            'description'           => $payload['description'] ?? null,

            'document_type'         => $payload['document_type'] ?? 'factura',
            'category'              => $payload['category'] ?? 'factura',

            'amount'                => $amount,
            'amount_paid'           => $amountPaid,
            'currency'              => $payload['currency'] ?? 'MXN',

            'issue_date'            => $issueDate,
            'due_date'              => $dueDate,
            'paid_at'               => $status === 'cobrado' ? ($payload['paid_at'] ?? now()->toDateString()) : null,

            'status'                => $status,
            'priority'              => $payload['priority'] ?? 'media',

            'payment_method'        => $payload['payment_method'] ?? null,
            'bank_reference'        => $payload['bank_reference'] ?? null,

            'credit_days'           => $payload['credit_days'] ?? $creditDays,
            'interest_rate'         => $payload['interest_rate'] ?? 0,

            'assigned_to'           => $payload['assigned_to'] ?? null,
            'collection_status'     => $payload['collection_status'] ?? 'sin_gestion',

            'notes'                 => $payload['notes'] ?? null,
            'reminder_days_before'  => (int) ($payload['reminder_days_before'] ?? 5),
            'tags'                  => $payload['tags'] ?? null,
        ];

        $data = $this->filterToExistingColumns($data);

        if (!$receivable->exists) {
            $data['created_by'] = Auth::user()?->email;
        }

        $receivable->fill($data);
        $receivable->save();

        $this->state->recalc('receivable', $receivable->id);
        $this->syncAgendaForReceivable($receivable->fresh('company'));

        return $receivable;
    }

    public function cancelFromSource(Model $source): void
    {
        $receivable = AccountReceivable::query()
            ->where('source_type', get_class($source))
            ->where('source_id', (int) $source->getKey())
            ->first();

        if (!$receivable) {
            return;
        }

        $receivable->status = 'cancelado';
        $receivable->save();

        $this->state->recalc('receivable', $receivable->id);
        $this->syncAgendaForReceivable($receivable->fresh('company'));
    }

    public function deleteFromSource(Model $source): void
    {
        $receivable = AccountReceivable::query()
            ->where('source_type', get_class($source))
            ->where('source_id', (int) $source->getKey())
            ->first();

        if (!$receivable) {
            return;
        }

        $this->deleteAgendaForReceivable($receivable);
        $receivable->delete();
    }

    private function normalizeStatus(string $status, float $amount, float $amountPaid): string
    {
        $status = match ($status) {
            'pending'   => 'pendiente',
            'partial'   => 'parcial',
            'paid'      => 'cobrado',
            'overdue'   => 'vencido',
            'cancelled' => 'cancelado',
            default     => $status,
        };

        if ($status === 'cancelado') {
            return 'cancelado';
        }

        if ($amount > 0 && $amountPaid >= $amount) {
            return 'cobrado';
        }

        if ($amountPaid > 0 && $amountPaid < $amount) {
            return 'parcial';
        }

        return 'pendiente';
    }

    private function filterToExistingColumns(array $data): array
    {
        $table = (new AccountReceivable())->getTable();
        $columns = Schema::getColumnListing($table);

        return array_filter(
            $data,
            fn ($key) => in_array($key, $columns, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function syncAgendaForReceivable(AccountReceivable $receivable): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $event = $this->findAgendaEventForReceivable($receivable);

        if (
            empty($receivable->due_date) ||
            in_array((string) $receivable->status, ['cobrado', 'cancelado'], true)
        ) {
            if ($event) {
                $event->delete();
            }
            return;
        }

        $startAt = Carbon::parse($receivable->due_date, 'America/Mexico_City')->setTime(9, 0, 0);

        $title = 'Cobro';
        if (!empty($receivable->client_name)) {
            $title .= ': '.$receivable->client_name;
        }
        if (!empty($receivable->folio)) {
            $title .= ' · Folio '.$receivable->folio;
        }

        $companyName = $receivable->relationLoaded('company')
            ? ($receivable->company?->name ?? '—')
            : (Company::query()->whereKey($receivable->company_id)->value('name') ?? '—');

        $marker = $this->agendaMarkerForReceivable($receivable->id);

        $descriptionParts = array_filter([
            'Evento generado automáticamente desde Ventas/Cuentas por Cobrar.',
            'ID cobro: '.$receivable->id,
            'Compañía: '.$companyName,
            'Cliente: '.($receivable->client_name ?: '—'),
            !empty($receivable->folio) ? 'Folio: '.$receivable->folio : null,
            'Monto: $'.number_format((float) $receivable->amount, 2).' '.($receivable->currency ?: 'MXN'),
            'Saldo actual: $'.number_format(max((float) $receivable->amount - (float) $receivable->amount_paid, 0), 2).' '.($receivable->currency ?: 'MXN'),
            'Vencimiento: '.$startAt->format('d/m/Y H:i'),
            !empty($receivable->notes) ? 'Notas: '.$receivable->notes : null,
            $marker,
        ]);

        $data = [
            'title' => mb_substr($title, 0, 180),
            'description' => mb_substr(implode("\n", $descriptionParts), 0, 2000),
            'start_at' => $startAt->format('Y-m-d H:i:s'),
            'timezone' => 'America/Mexico_City',
            'repeat_rule' => 'none',
            'remind_offset_minutes' => max(((int) ($receivable->reminder_days_before ?? 5)) * 1440, 1),
            'user_ids' => [$userId],
            'send_email' => true,
            'send_whatsapp' => true,
        ];

        if ($event) {
            $event->fill($data);
        } else {
            $event = new AgendaEvent($data);
        }

        $event->computeNextReminder();
        $event->save();
    }

    private function deleteAgendaForReceivable(AccountReceivable $receivable): void
    {
        $event = $this->findAgendaEventForReceivable($receivable);

        if ($event) {
            $event->delete();
        }
    }

    private function findAgendaEventForReceivable(AccountReceivable $receivable): ?AgendaEvent
    {
        $marker = $this->agendaMarkerForReceivable($receivable->id);

        return AgendaEvent::query()
            ->where('description', 'like', '%'.$marker.'%')
            ->latest('id')
            ->first();
    }

    private function agendaMarkerForReceivable(int $receivableId): string
    {
        return '[AUTO_RECEIVABLE_ID:'.$receivableId.']';
    }
}