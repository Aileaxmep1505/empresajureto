@include('accounting.partials.account-form', [
  'type' => 'payable',
  'mode' => 'create',
  'action' => route('accounting.payables.store'),
  'method' => 'POST',
  'companies' => $companies,
  'companyId' => $companyId ?? null,
  'account' => null,
  'existingFiles' => [],
])