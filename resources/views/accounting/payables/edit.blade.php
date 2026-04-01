@php
  $account = $account ?? $item ?? null;
@endphp

@include('accounting.partials.account-form', [
  'type' => 'payable',
  'mode' => 'edit',
  'action' => route('accounting.payables.update', $account),
  'method' => 'PUT',
  'companies' => $companies ?? [],
  'companyId' => $account->company_id ?? null,
  'item' => $account,
  'account' => $account,
])