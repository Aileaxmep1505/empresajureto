@include('accounting.partials.account-form', [
  'type' => 'receivable',
  'mode' => 'create',
  'action' => route('accounting.receivables.store'),
  'method' => 'POST',
  'companies' => $companies,
  'companyId' => $companyId ?? null,
  'account' => null,
  'existingFiles' => [],
])