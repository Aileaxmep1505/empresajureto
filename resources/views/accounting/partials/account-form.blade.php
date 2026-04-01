@extends('layouts.app')

@section('title', ($mode ?? 'create') === 'edit'
    ? (($type ?? 'receivable') === 'payable' ? 'Editar cuenta por pagar' : 'Editar cuenta por cobrar')
    : (($type ?? 'receivable') === 'payable' ? 'Nueva cuenta por pagar' : 'Nueva cuenta por cobrar'))

@section('content')
@include('accounting.partials.ui')

@php
  $type = $type ?? 'receivable'; // receivable | payable
  $mode = $mode ?? 'create';     // create | edit
  $isEdit = $mode === 'edit';

  $model = $account ?? $item ?? null;

  $normalizeStatus = function ($value, $type) {
      $value = (string) $value;

      return match ($value) {
          'pending'   => 'pendiente',
          'partial'   => 'parcial',
          'paid'      => $type === 'payable' ? 'pagado' : 'cobrado',
          'overdue'   => 'vencido',
          'cancelled' => 'cancelado',
          default     => $value !== '' ? $value : 'pendiente',
      };
  };

  $normalizeFrequency = function ($value) {
      $value = (string) $value;

      return match ($value) {
          'one_time'   => 'unico',
          'weekly'     => 'semanal',
          'biweekly'   => 'quincenal',
          'monthly'    => 'mensual',
          'bimonthly'  => 'bimestral',
          'quarterly'  => 'trimestral',
          'semiannual' => 'semestral',
          'annual'     => 'anual',
          default      => $value !== '' ? $value : 'mensual',
      };
  };

  $normalizeReceivableCategory = function ($value) {
      $value = (string) $value;

      return match ($value) {
          'cliente'     => 'factura',
          'servicio'    => 'servicios',
          'proyecto'    => 'servicios',
          'anticipo'    => 'otro',
          'suscripcion' => 'servicios',
          'otros'       => 'otro',
          default       => $value !== '' ? $value : 'factura',
      };
  };

  $normalizePayableCategory = function ($value) {
      $value = (string) $value;

      return match ($value) {
          'otros' => 'otros',
          default => $value !== '' ? $value : 'proveedores',
      };
  };

  $titleValue = old('title', $model->title ?? '');
  $descriptionValue = old('description', $model->description ?? '');
  $amountValue = old('amount', isset($model->amount) ? number_format((float)$model->amount, 2, '.', '') : '');
  $amountPaidValue = old('amount_paid', isset($model->amount_paid) ? number_format((float)$model->amount_paid, 2, '.', '') : '0.00');
  $currencyValue = old('currency', $model->currency ?? 'MXN');

  $statusValue = $normalizeStatus(old('status', $model->status ?? 'pendiente'), $type);
  $frequencyValue = $normalizeFrequency(old('frequency', $model->frequency ?? 'mensual'));
  $categoryValue = $type === 'payable'
      ? $normalizePayableCategory(old('category', $model->category ?? 'proveedores'))
      : $normalizeReceivableCategory(old('category', $model->category ?? 'factura'));

  $dueDateValue = old('due_date', !empty($model?->due_date) ? \Illuminate\Support\Carbon::parse($model->due_date)->format('Y-m-d') : '');
  $paidAtValue = old('paid_at', !empty($model?->paid_at) ? \Illuminate\Support\Carbon::parse($model->paid_at)->format('Y-m-d') : '');
  $issuedAtValue = old('issued_at', !empty($model?->issued_at) ? \Illuminate\Support\Carbon::parse($model->issued_at)->format('Y-m-d') : '');
  $reminderDaysValue = old('reminder_days_before', $model->reminder_days_before ?? 3);
  $referenceValue = old('reference', $model->reference ?? '');
  $notesValue = old('notes', $model->notes ?? '');
  $companyIdValue = old('company_id', $model->company_id ?? ($companyId ?? ''));

  $action = $action ?? '#';
  $method = $method ?? 'POST';

  $pageTitle = $type === 'payable'
      ? ($isEdit ? 'Editar cuenta por pagar' : 'Nueva cuenta por pagar')
      : ($isEdit ? 'Editar cuenta por cobrar' : 'Nueva cuenta por cobrar');

  $pageSubtitle = $type === 'payable'
      ? 'Gestión de obligaciones, impuestos, proveedores y compromisos corporativos.'
      : 'Control de ingresos, facturación, cuentas de clientes y saldos a favor.';

  $statusOptions = $type === 'payable'
      ? [
          'pendiente' => 'Pendiente',
          'parcial'   => 'Parcial',
          'pagado'    => 'Pagado',
          'vencido'   => 'Vencido',
          'cancelado' => 'Cancelado',
      ]
      : [
          'pendiente' => 'Pendiente',
          'parcial'   => 'Parcial',
          'cobrado'   => 'Cobrado',
          'vencido'   => 'Vencido',
          'cancelado' => 'Cancelado',
      ];

  $categoryOptionsPayable = [
      'impuestos'   => 'Impuestos',
      'servicios'   => 'Servicios',
      'nomina'      => 'Nómina',
      'seguros'     => 'Seguros',
      'retenciones' => 'Retenciones',
      'proveedores' => 'Proveedores',
      'renta'       => 'Renta',
      'otros'       => 'Otros',
  ];

  $categoryOptionsReceivable = [
      'factura'    => 'Factura',
      'honorarios' => 'Honorarios',
      'renta'      => 'Renta',
      'servicios'  => 'Servicios',
      'producto'   => 'Producto',
      'otro'       => 'Otro',
  ];

  $categoryOptions = $type === 'payable' ? $categoryOptionsPayable : $categoryOptionsReceivable;

  $frequencyOptions = [
      'unico'      => 'Único',
      'semanal'    => 'Semanal',
      'quincenal'  => 'Quincenal',
      'mensual'    => 'Mensual',
      'bimestral'  => 'Bimestral',
      'trimestral' => 'Trimestral',
      'semestral'  => 'Semestral',
      'anual'      => 'Anual',
  ];
@endphp

<style>
  :root {
    --acc-bg-page: #f8fafc;
    --acc-surface: #ffffff;
    --acc-text-primary: #0f172a;
    --acc-text-secondary: #475569;
    --acc-text-tertiary: #94a3b8;
    --acc-border: #e2e8f0;
    --acc-border-hover: #cbd5e1;
    --acc-accent-color: #4f46e5;
    --acc-focus-ring: rgba(79, 70, 229, 0.15);
    --acc-shadow-card: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    --acc-shadow-btn: 0 1px 2px rgba(0,0,0,0.05);
    --acc-shadow-btn-primary: 0 4px 6px -1px rgba(15, 23, 42, 0.1), 0 2px 4px -2px rgba(15, 23, 42, 0.05);
    --acc-shadow-pop: 0 18px 45px rgba(15, 23, 42, 0.14);
    --acc-radius-xl: 16px;
    --acc-radius-lg: 12px;
    --acc-radius-md: 10px;
    --acc-radius-sm: 8px;
    --acc-transition: all .22s cubic-bezier(.4,0,.2,1);
  }

  body {
    background-color: var(--acc-bg-page);
  }

  .acc-form-page {
    max-width: 1080px;
    margin: 0 auto;
    padding: 32px 16px 64px;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    color: var(--acc-text-primary);
  }

  .acc-form-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 32px;
  }

  .acc-form-head h1 {
    margin: 0;
    font-size: 1.75rem;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: -0.025em;
    color: var(--acc-text-primary);
  }

  .acc-form-head p {
    margin: 4px 0 0;
    color: var(--acc-text-secondary);
    font-size: 0.95rem;
    font-weight: 400;
  }

  .acc-form-actions-top {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }

  .acc-top-btn,
  .acc-cancelbtn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    background: var(--acc-surface);
    border: 1px solid var(--acc-border);
    color: var(--acc-text-primary);
    border-radius: var(--acc-radius-md);
    padding: 8px 16px;
    font-size: 0.875rem;
    font-weight: 500;
    transition: var(--acc-transition);
    box-shadow: var(--acc-shadow-btn);
    cursor: pointer;
  }

  .acc-top-btn:hover,
  .acc-cancelbtn:hover {
    background: #f8fafc;
    border-color: var(--acc-border-hover);
    transform: translateY(-1px);
  }

  .acc-switcher {
    display: inline-flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: var(--acc-radius-lg);
    margin-bottom: 24px;
    border: 1px solid var(--acc-border);
  }

  .acc-switcher a {
    text-decoration: none;
    padding: 6px 20px;
    border-radius: var(--acc-radius-md);
    color: var(--acc-text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
    text-align: center;
    transition: var(--acc-transition);
  }

  .acc-switcher a:hover:not(.active) {
    color: var(--acc-text-primary);
  }

  .acc-switcher a.active {
    background: var(--acc-surface);
    color: var(--acc-text-primary);
    font-weight: 600;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
  }

  .acc-form-shell {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 340px;
    gap: 32px;
    align-items: start;
  }

  .acc-main-card,
  .acc-side-card {
    background: var(--acc-surface);
    border: 1px solid var(--acc-border);
    border-radius: var(--acc-radius-xl);
    box-shadow: var(--acc-shadow-card);
  }

  .acc-main-card {
    padding: 40px;
  }

  .acc-side-card {
    padding: 32px;
    position: sticky;
    top: 32px;
  }

  .acc-section + .acc-section {
    margin-top: 40px;
  }

  .acc-section-title {
    margin: 0 0 24px;
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--acc-text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .acc-section-title::after {
    content: "";
    flex: 1;
    height: 1px;
    background: var(--acc-border);
    margin-left: 12px;
  }

  .acc-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 20px 24px;
  }

  .acc-col-2 {
    grid-column: span 2;
  }

  .acc-field label {
    display: block;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
    color: var(--acc-text-secondary);
    margin-bottom: 8px;
  }

  .acc-req {
    color: #e11d48;
  }

  .acc-input,
  .acc-select,
  .acc-textarea,
  .acc-file {
    width: 100%;
    border: 1px solid var(--acc-border);
    background: var(--acc-surface);
    border-radius: var(--acc-radius-md);
    padding: 10px 14px;
    outline: none;
    font-size: 0.95rem;
    color: var(--acc-text-primary);
    font-weight: 400;
    transition: var(--acc-transition);
    box-shadow: 0 1px 2px rgba(0,0,0,0.02);
  }

  .acc-input::placeholder,
  .acc-textarea::placeholder {
    color: var(--acc-text-tertiary);
  }

  .acc-input:hover,
  .acc-select:hover,
  .acc-textarea:hover {
    border-color: var(--acc-border-hover);
  }

  .acc-input:focus,
  .acc-select:focus,
  .acc-textarea:focus {
    border-color: var(--acc-accent-color);
    box-shadow: 0 0 0 4px var(--acc-focus-ring);
  }

  .acc-textarea {
    resize: vertical;
    min-height: 100px;
    line-height: 1.5;
  }

  .acc-help {
    margin-top: 8px;
    color: var(--acc-text-secondary);
    font-size: 0.8rem;
  }

  .acc-upload {
    border: 2px dashed var(--acc-border);
    border-radius: var(--acc-radius-lg);
    padding: 24px;
    background: #fafbfc;
    text-align: center;
    transition: var(--acc-transition);
    cursor: pointer;
  }

  .acc-upload:hover {
    background: #f1f5f9;
    border-color: var(--acc-border-hover);
  }

  .acc-upload:focus-within {
    border-color: var(--acc-accent-color);
    background: #eef2ff;
  }

  .acc-file {
    background: transparent;
    border: none;
    box-shadow: none;
    padding: 0;
    cursor: pointer;
    font-size: 0.875rem;
  }

  .acc-summary-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-radius: 999px;
    margin-bottom: 24px;
  }

  .acc-summary-badge.payable {
    background: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
  }

  .acc-summary-badge.receivable {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
  }

  .acc-side-title {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 600;
    color: var(--acc-text-tertiary);
    margin-bottom: 16px;
  }

  .acc-side-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .acc-side-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .acc-side-row small {
    color: var(--acc-text-secondary);
    font-size: 0.9rem;
    font-weight: 500;
  }

  .acc-side-row strong {
    color: var(--acc-text-primary);
    font-size: 0.95rem;
    font-weight: 600;
    text-align: right;
  }

  .acc-side-row.total-row {
    margin-top: 8px;
    padding-top: 24px;
    border-top: 1px solid var(--acc-border);
  }

  .acc-side-row.total-row strong {
    font-size: 1.5rem;
    color: var(--acc-text-primary);
    font-weight: 700;
    letter-spacing: -0.02em;
  }

  .acc-submitbar {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 48px;
    padding-top: 24px;
    border-top: 1px solid var(--acc-border);
  }

  .acc-submitbtn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #0f172a;
    border-radius: var(--acc-radius-md);
    padding: 10px 24px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--acc-transition);
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1), var(--acc-shadow-btn-primary);
  }

  .acc-submitbtn:hover {
    background: #0f172a;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1), 0 6px 10px -2px rgba(15, 23, 42, 0.15);
    transform: translateY(-1px);
  }

  /* custom select */
  .acc-modern-select {
    position: relative;
  }

  .acc-modern-native {
    position: absolute;
    inset: 0;
    opacity: 0;
    pointer-events: none;
    width: 100%;
    height: 100%;
  }

  .acc-modern-trigger {
    width: 100%;
    border: 1px solid var(--acc-border);
    background: linear-gradient(180deg, #ffffff 0%, #fcfdff 100%);
    border-radius: 14px;
    min-height: 48px;
    padding: 12px 44px 12px 16px;
    text-align: left;
    font-size: 0.97rem;
    color: var(--acc-text-primary);
    cursor: pointer;
    transition: var(--acc-transition);
    position: relative;
    box-shadow: 0 1px 2px rgba(0,0,0,.02);
  }

  .acc-modern-trigger:hover {
    border-color: var(--acc-border-hover);
    transform: translateY(-1px);
  }

  .acc-modern-select.open .acc-modern-trigger {
    border-color: var(--acc-accent-color);
    box-shadow: 0 0 0 4px var(--acc-focus-ring);
  }

  .acc-modern-trigger::after {
    content: "";
    position: absolute;
    right: 16px;
    top: 50%;
    width: 10px;
    height: 10px;
    border-right: 2px solid #64748b;
    border-bottom: 2px solid #64748b;
    transform: translateY(-60%) rotate(45deg);
    transition: transform .22s ease;
  }

  .acc-modern-select.open .acc-modern-trigger::after {
    transform: translateY(-35%) rotate(-135deg);
  }

  .acc-modern-panel {
    position: absolute;
    left: 0;
    right: 0;
    top: calc(100% + 10px);
    background: rgba(255,255,255,.98);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(226,232,240,.9);
    border-radius: 16px;
    box-shadow: var(--acc-shadow-pop);
    padding: 8px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(8px) scale(.98);
    transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
    z-index: 60;
    overflow: visible;
  }

  .acc-modern-select.open .acc-modern-panel {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
  }

  .acc-modern-option {
    width: 100%;
    border: none;
    background: transparent;
    border-radius: 12px;
    padding: 12px 14px;
    text-align: left;
    font-size: 1rem;
    color: var(--acc-text-primary);
    cursor: pointer;
    transition: background .16s ease, transform .16s ease, color .16s ease;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
  }

  .acc-modern-option:hover {
    background: #f8fafc;
  }

  .acc-modern-option.is-selected {
    background: #eef2ff;
    color: #1e1b4b;
    font-weight: 600;
  }

  .acc-modern-option.is-selected::after {
    content: "✓";
    color: #111827;
    font-weight: 700;
  }

  @media (max-width: 980px) {
    .acc-form-shell {
      grid-template-columns: 1fr;
    }

    .acc-side-card {
      position: static;
    }
  }

  @media (max-width: 640px) {
    .acc-grid {
      grid-template-columns: 1fr;
    }

    .acc-col-2 {
      grid-column: span 1;
    }

    .acc-form-head {
      flex-direction: column;
      align-items: flex-start;
    }

    .acc-submitbar {
      flex-direction: column-reverse;
    }

    .acc-submitbtn,
    .acc-cancelbtn {
      width: 100%;
    }
  }
</style>

<div class="acc-form-page">
  <div class="acc-form-head">
    <div>
      <h1>{{ $pageTitle }}</h1>
      <p>{{ $pageSubtitle }}</p>
    </div>

    <div class="acc-form-actions-top">
      @if(\Illuminate\Support\Facades\Route::has('accounting.dashboard'))
        <a class="acc-top-btn" href="{{ route('accounting.dashboard') }}">Dashboard</a>
      @endif

      @if($type === 'payable' && \Illuminate\Support\Facades\Route::has('accounting.payables.index'))
        <a class="acc-top-btn" href="{{ route('accounting.payables.index', ['company_id' => $companyIdValue ?: null]) }}">Volver</a>
      @elseif($type === 'receivable' && \Illuminate\Support\Facades\Route::has('accounting.receivables.index'))
        <a class="acc-top-btn" href="{{ route('accounting.receivables.index', ['company_id' => $companyIdValue ?: null]) }}">Volver</a>
      @endif
    </div>
  </div>

  <div class="acc-switcher">
    @if(\Illuminate\Support\Facades\Route::has('accounting.receivables.create'))
      <a href="{{ route('accounting.receivables.create', ['company_id' => $companyIdValue ?: null]) }}"
         class="{{ $type === 'receivable' ? 'active' : '' }}">
        Cobro (CxC)
      </a>
    @endif

    @if(\Illuminate\Support\Facades\Route::has('accounting.payables.create'))
      <a href="{{ route('accounting.payables.create', ['company_id' => $companyIdValue ?: null]) }}"
         class="{{ $type === 'payable' ? 'active' : '' }}">
        Pago (CxP)
      </a>
    @endif
  </div>

  @if ($errors->any())
    <div style="background:#fff1f2; border:1px solid #fecdd3; color:#be123c; padding:16px; border-radius:8px; margin-bottom:24px;">
      <strong style="font-size:0.9rem;">Por favor, corrige los siguientes errores:</strong>
      <ul style="margin:8px 0 0; padding-left:20px; font-size:0.875rem;">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
    @csrf
    @if(strtoupper($method) !== 'POST')
      @method($method)
    @endif

    <input type="hidden" name="type" value="{{ $type }}">

    <div class="acc-form-shell">
      <div class="acc-main-card">

        <div class="acc-section" style="margin-top: 0;">
          <h3 class="acc-section-title">Información general</h3>

          <div class="acc-grid">
            <div class="acc-field acc-col-2">
              <label for="title">
                {{ $type === 'payable' ? 'Concepto del pago' : 'Concepto del cobro' }}
                <span class="acc-req">*</span>
              </label>
              <input
                id="title"
                name="title"
                type="text"
                class="acc-input"
                value="{{ $titleValue }}"
                placeholder="{{ $type === 'payable' ? 'Ej: Licencias de software, renta corporativa...' : 'Ej: Factura INV-001, anticipo consultoría...' }}"
                required
              >
            </div>

            <div class="acc-field">
              <label for="company_id">Entidad corporativa <span class="acc-req">*</span></label>
              <select id="company_id" name="company_id" class="acc-select" required>
                <option value="">Selecciona una entidad</option>
                @foreach(($companies ?? []) as $company)
                  <option value="{{ $company->id }}" @selected((string)$companyIdValue === (string)$company->id)>
                    {{ $company->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="acc-field">
              <label for="category">Categoría contable <span class="acc-req">*</span></label>
              <select id="category" name="category" class="acc-select js-modern-select" required>
                <option value="">Selecciona</option>
                @foreach($categoryOptions as $value => $label)
                  <option value="{{ $value }}" @selected($categoryValue === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="acc-field">
              <label for="frequency">Recurrencia <span class="acc-req">*</span></label>
              <select id="frequency" name="frequency" class="acc-select js-modern-select" required>
                @foreach($frequencyOptions as $value => $label)
                  <option value="{{ $value }}" @selected($frequencyValue === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="acc-field">
              <label for="status">Estado actual <span class="acc-req">*</span></label>
              <select id="status" name="status" class="acc-select js-modern-select" required>
                @foreach($statusOptions as $value => $label)
                  <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                @endforeach
              </select>
            </div>

            <div class="acc-field acc-col-2">
              <label for="description">Descripción detallada</label>
              <textarea
                id="description"
                name="description"
                class="acc-textarea"
                placeholder="Añade contexto adicional para el equipo financiero..."
              >{{ $descriptionValue }}</textarea>
            </div>
          </div>
        </div>

        <div class="acc-section">
          <h3 class="acc-section-title">Valores y vencimientos</h3>

          <div class="acc-grid">
            <div class="acc-field">
              <label for="amount">Monto total <span class="acc-req">*</span></label>
              <input id="amount" name="amount" type="number" step="0.01" min="0" class="acc-input" value="{{ $amountValue }}" placeholder="0.00" required>
            </div>

            <div class="acc-field">
              <label for="amount_paid">{{ $type === 'payable' ? 'Monto ejecutado' : 'Monto recaudado' }}</label>
              <input id="amount_paid" name="amount_paid" type="number" step="0.01" min="0" class="acc-input" value="{{ $amountPaidValue }}" placeholder="0.00">
            </div>

            <div class="acc-field">
              <label for="currency">Divisa</label>
              <select id="currency" name="currency" class="acc-select js-modern-select">
                <option value="MXN" @selected($currencyValue === 'MXN')>MXN</option>
                <option value="USD" @selected($currencyValue === 'USD')>USD</option>
                <option value="EUR" @selected($currencyValue === 'EUR')>EUR</option>
              </select>
            </div>

            <div class="acc-field">
              <label for="due_date">Fecha límite <span class="acc-req">*</span></label>
              <input id="due_date" name="due_date" type="date" class="acc-input" value="{{ $dueDateValue }}" required>
            </div>

            <div class="acc-field" id="paidAtWrap">
              <label for="paid_at">{{ $type === 'payable' ? 'Fecha de liquidación' : 'Fecha de ingreso' }}</label>
              <input id="paid_at" name="paid_at" type="date" class="acc-input" value="{{ $paidAtValue }}">
            </div>

            <div class="acc-field">
              <label for="reminder_days_before">Alerta previa (días)</label>
              <input id="reminder_days_before" name="reminder_days_before" type="number" min="0" max="60" class="acc-input" value="{{ $reminderDaysValue }}">
            </div>

            <div class="acc-field acc-col-2">
              <label for="notes">Observaciones internas</label>
              <textarea
                id="notes"
                name="notes"
                class="acc-textarea"
                placeholder="Exclusivo para uso del área contable o administrativa..."
              >{{ $notesValue }}</textarea>
            </div>
          </div>
        </div>

        <div class="acc-section">
          <h3 class="acc-section-title">Documentación de respaldo</h3>

          <div class="acc-grid">
            <div class="acc-field acc-col-2">
              <label for="evidence_file">
                {{ $type === 'payable' ? 'Comprobante de egreso' : 'Comprobante de ingreso' }}
              </label>
              <div class="acc-upload">
                <input id="evidence_file" name="evidence_file" type="file" class="acc-file" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xlsx">
                <div class="acc-help">Archivos aceptados: PDF, JPG, PNG, DOCX, XLSX.</div>
              </div>
            </div>

            <div class="acc-field acc-col-2">
              <label for="documents">Anexos corporativos</label>
              <div class="acc-upload">
                <input id="documents" name="documents[]" type="file" class="acc-file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xlsx,.zip">
                <div class="acc-help">Adjunte PO, contratos, facturas fiscales o minutas.</div>
              </div>
            </div>
          </div>
        </div>

        <div class="acc-submitbar">
          <a href="{{ url()->previous() }}" class="acc-cancelbtn">Cancelar operación</a>
          <button type="submit" class="acc-submitbtn">
            {{ $isEdit ? 'Guardar actualización' : 'Registrar movimiento' }}
          </button>
        </div>
      </div>

      <div class="acc-side-card">
        <div class="acc-summary-badge {{ $type === 'payable' ? 'payable' : 'receivable' }}">
          {{ $type === 'payable' ? 'Egresos (CxP)' : 'Ingresos (CxC)' }}
        </div>

        <div class="acc-side-title">Resumen de operación</div>

        <div class="acc-side-list">
          <div class="acc-side-row">
            <small>Flujo</small>
            <strong>{{ $type === 'payable' ? 'Salida' : 'Entrada' }}</strong>
          </div>

          <div class="acc-side-row">
            <small>Estatus</small>
            <strong id="previewStatusText">{{ $statusOptions[$statusValue] ?? 'Pendiente' }}</strong>
          </div>

          <div class="acc-side-row">
            <small>Términos</small>
            <strong id="previewFrequencyText">{{ $frequencyOptions[$frequencyValue] ?? 'Mensual' }}</strong>
          </div>

          <div class="acc-side-row">
            <small>Divisa</small>
            <strong id="previewCurrencyText">{{ $currencyValue }}</strong>
          </div>

          <div class="acc-side-row">
            <small>Vencimiento</small>
            <strong id="previewDueDateText">{{ $dueDateValue ?: '--/--/----' }}</strong>
          </div>

          <div class="acc-side-row total-row">
            <small>Total a operar</small>
            <strong id="previewAmountText">{{ $amountValue !== '' ? '$' . number_format((float)$amountValue, 2) : '$0.00' }}</strong>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
  (function () {
    const status = document.getElementById('status');
    const frequency = document.getElementById('frequency');
    const currency = document.getElementById('currency');
    const amount = document.getElementById('amount');
    const dueDate = document.getElementById('due_date');
    const paidAtWrap = document.getElementById('paidAtWrap');

    const previewStatusText = document.getElementById('previewStatusText');
    const previewFrequencyText = document.getElementById('previewFrequencyText');
    const previewCurrencyText = document.getElementById('previewCurrencyText');
    const previewAmountText = document.getElementById('previewAmountText');
    const previewDueDateText = document.getElementById('previewDueDateText');

    const statusMap = @json($statusOptions);
    const frequencyMap = @json($frequencyOptions);

    function formatMoney(value) {
      const n = Number(value || 0);
      return '$' + n.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function syncPreview() {
      if (previewStatusText && status) previewStatusText.textContent = statusMap[status.value] || status.value || 'Pendiente';
      if (previewFrequencyText && frequency) previewFrequencyText.textContent = frequencyMap[frequency.value] || frequency.value || '—';
      if (previewCurrencyText && currency) previewCurrencyText.textContent = currency.value || 'MXN';
      if (previewAmountText && amount) previewAmountText.textContent = formatMoney(amount.value);

      if (previewDueDateText && dueDate) {
        previewDueDateText.textContent = dueDate.value || '--/--/----';
      }

      if (paidAtWrap && status) {
        const showPaidDate = ['cobrado', 'pagado'].includes(status.value);
        paidAtWrap.style.display = showPaidDate ? '' : 'none';
      }
    }

    function closeAllModernSelects(except) {
      document.querySelectorAll('.acc-modern-select.open').forEach(el => {
        if (el !== except) el.classList.remove('open');
      });
    }

    function initModernSelect(select) {
      if (!select || select.dataset.enhanced === '1') return;

      const wrapper = document.createElement('div');
      wrapper.className = 'acc-modern-select';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'acc-modern-trigger';

      const panel = document.createElement('div');
      panel.className = 'acc-modern-panel';

      select.parentNode.insertBefore(wrapper, select);
      wrapper.appendChild(select);
      wrapper.appendChild(trigger);
      wrapper.appendChild(panel);

      select.classList.add('acc-modern-native');
      select.dataset.enhanced = '1';

      function selectedOption() {
        return select.options[select.selectedIndex] || null;
      }

      function renderOptions() {
        panel.innerHTML = '';

        Array.from(select.options).forEach(option => {
          if (option.disabled) return;

          const item = document.createElement('button');
          item.type = 'button';
          item.className = 'acc-modern-option' + (option.selected ? ' is-selected' : '');
          item.textContent = option.textContent;

          item.addEventListener('click', function () {
            select.value = option.value;
            Array.from(select.options).forEach(o => o.selected = o.value === option.value);
            renderOptions();
            renderTrigger();
            wrapper.classList.remove('open');
            select.dispatchEvent(new Event('change', { bubbles: true }));
            select.dispatchEvent(new Event('input', { bubbles: true }));
          });

          panel.appendChild(item);
        });
      }

      function renderTrigger() {
        const current = selectedOption();
        trigger.textContent = current ? current.textContent : 'Selecciona';
      }

      trigger.addEventListener('click', function () {
        const willOpen = !wrapper.classList.contains('open');
        closeAllModernSelects(wrapper);
        wrapper.classList.toggle('open', willOpen);
      });

      trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
          wrapper.classList.remove('open');
        }
      });

      select.addEventListener('change', function () {
        renderTrigger();
        renderOptions();
      });

      document.addEventListener('click', function (e) {
        if (!wrapper.contains(e.target)) {
          wrapper.classList.remove('open');
        }
      });

      renderTrigger();
      renderOptions();
    }

    document.querySelectorAll('.js-modern-select').forEach(initModernSelect);

    [status, frequency, currency, amount, dueDate].forEach(el => {
      if (el) el.addEventListener('input', syncPreview);
      if (el) el.addEventListener('change', syncPreview);
    });

    syncPreview();
  })();
</script>
@endsection