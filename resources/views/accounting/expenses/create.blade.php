{{-- resources/views/accounting/expenses/create.blade.php --}}
@extends('layouts.app')
@section('title','Nuevo registro')
@section('titulo','Nuevo registro')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

@php
  $v = function($key, $default = null) { return old($key) ?? $default; };
  $defaultKind = $v('entry_kind','gasto');
  $defaultExpenseType = $v('expense_type','vehiculo');
  $defaultCashTab = $v('cash_tab','fondo');

  $payrollCats = [
    'bono' => 'Bono',
    'pago_quincenal' => 'Pago quincenal',
    'finiquito' => 'Finiquito',
    'aguinaldo' => 'Aguinaldo',
    'vacaciones' => 'Vacaciones',
    'prima_vacacional' => 'Prima vacacional',
    'otro' => 'Otro',
  ];

  $vehicleCats = [
    'gasolina' => 'Gasolina',
    'tags' => 'Tags',
    'casetas' => 'Casetas',
    'servicio' => 'Servicio',
    'refacciones' => 'Refacciones',
    'seguro' => 'Seguro',
    'tenencia' => 'Tenencia',
    'verificacion' => 'Verificación',
    'lavado' => 'Lavado',
    'estacionamiento' => 'Estacionamiento',
    'otro' => 'Otro',
  ];

  $people   = $people   ?? collect();
  $managers = $managers ?? collect();
  $now      = $now ?? now()->format('Y-m-d\TH:i');
@endphp

<style>
:root {
  --bg: #f9fafb;
  --card: #ffffff;
  --ink: #333333;
  --title: #111111;
  --muted: #888888;
  --line: #ebebeb;
  --blue: #007aff;
  --blue-soft: #e6f0ff;
  --success: #15803d;
  --success-soft: #e6ffe6;
  --danger: #ff4a4a;
  --danger-soft: #ffebeb;
  --shadow: 0 4px 12px rgba(0,0,0,0.02);
}

* { box-sizing: border-box; }

.expenses-page {
  font-family: 'Quicksand', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--ink);
  min-height: calc(100vh - 80px);
  padding: 34px 18px 56px;
}

.expenses-shell {
  width: min(1180px, 100%);
  margin: 0 auto;
}

.expenses-hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 22px;
}

.expenses-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 7px 12px;
  border-radius: 999px;
  background: var(--blue-soft);
  color: var(--blue);
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 12px;
}

.expenses-title {
  margin: 0;
  color: var(--title);
  font-size: clamp(28px, 4vw, 44px);
  line-height: 1.05;
  letter-spacing: -0.04em;
  font-weight: 700;
}

.expenses-subtitle {
  margin: 10px 0 0;
  color: var(--muted);
  font-size: 15px;
  line-height: 1.7;
  max-width: 690px;
}

.card-clean {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 16px;
  box-shadow: var(--shadow);
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.card-clean:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0,0,0,0.04);
}

.main-card { padding: 24px; }
.section-card { padding: 22px; margin-top: 16px; }

.section-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 18px;
}

.section-title {
  color: var(--title);
  font-weight: 700;
  font-size: 19px;
  letter-spacing: -0.02em;
  margin: 0;
}

.section-sub {
  color: var(--muted);
  font-size: 13px;
  line-height: 1.55;
  margin: 5px 0 0;
}

.grid {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 16px;
}

.col-12 { grid-column: span 12; }
.col-8 { grid-column: span 8; }
.col-6 { grid-column: span 6; }
.col-4 { grid-column: span 4; }

.field label,
.form-label {
  display: block;
  color: var(--title);
  font-size: 13px;
  font-weight: 700;
  margin-bottom: 8px;
}

.form-control,
.form-select,
textarea,
input,
select {
  width: 100%;
  appearance: none;
  background: #ffffff;
  color: var(--ink);
  border: 1px solid var(--line);
  border-radius: 8px;
  min-height: 44px;
  padding: 11px 12px;
  font: 600 14px/1.3 'Quicksand', sans-serif;
  outline: none;
  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}

textarea { min-height: 120px; resize: vertical; }

.form-control:focus,
.form-select:focus,
textarea:focus,
input:focus,
select:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px var(--blue-soft);
}

.input-money { position: relative; }
.input-money span {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-weight: 700;
  font-size: 13px;
  pointer-events: none;
}
.input-money input { padding-left: 54px; }

.type-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.type-tab,
.btn-clean {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 42px;
  padding: 10px 16px;
  border-radius: 999px;
  border: 1px solid var(--line);
  background: #ffffff;
  color: #555555;
  font: 700 14px/1 'Quicksand', sans-serif;
  cursor: pointer;
  text-decoration: none;
  transition: transform .16s ease, background .16s ease, color .16s ease, border-color .16s ease, box-shadow .16s ease;
}

.type-tab:hover,
.btn-ghost:hover { background: #f9fafb; }
.type-tab:active,
.btn-clean:active { transform: scale(0.98); }
.type-tab.active {
  border-color: var(--blue);
  color: var(--blue);
  background: var(--blue-soft);
}

.btn-primary-clean {
  background: var(--blue);
  color: #ffffff;
  border-color: var(--blue);
}
.btn-primary-clean:hover { box-shadow: 0 12px 22px rgba(0,122,255,0.15); transform: translateY(-1px); }

.btn-outline-clean {
  background: #ffffff;
  color: var(--blue);
  border-color: var(--blue);
}
.btn-outline-clean:hover { background: var(--blue-soft); transform: translateY(-1px); }

.btn-success-clean {
  background: var(--success-soft);
  color: var(--success);
  border-color: var(--success-soft);
}
.btn-danger-clean {
  background: var(--danger-soft);
  color: var(--danger);
  border-color: var(--danger-soft);
}
.btn-ghost { background: transparent; color: #555555; }

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 999px;
  animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 18px;
}

.badge-clean {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  border-radius: 999px;
  padding: 7px 11px;
  font-size: 12px;
  font-weight: 700;
}
.badge-info { background: var(--blue-soft); color: var(--blue); }
.badge-success { background: var(--success-soft); color: var(--success); }
.badge-danger { background: var(--danger-soft); color: var(--danger); }

.notice {
  border-radius: 12px;
  padding: 14px 16px;
  border: 1px solid var(--line);
  background: #ffffff;
  color: var(--ink);
  font-size: 14px;
  line-height: 1.55;
  margin-bottom: 16px;
}
.notice.info { background: var(--blue-soft); border-color: #d7e6ff; }
.notice.warn { background: #fff7ed; border-color: #ffedd5; }

.sig-wrap {
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 12px;
  background: #ffffff;
}
.sig-canvas {
  width: 100%;
  height: 180px;
  display: block;
  border: 1px dashed #d9d9d9;
  border-radius: 12px;
  background: #ffffff;
}
.sig-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-top: 10px;
  color: var(--muted);
  font-size: 13px;
}

.dropzone {
  display: block;
  width: 100%;
  border: 1px dashed #d8d8d8;
  border-radius: 16px;
  padding: 18px;
  background: #ffffff;
  cursor: pointer;
  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.dropzone:hover,
.dropzone.dragover {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px var(--blue-soft);
}
.dropzone-title { color: var(--ink); font-weight: 700; }
.dropzone-sub { color: var(--muted); font-size: 13px; margin-top: 4px; }

.file-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}
.file-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  background: var(--blue-soft);
  color: var(--blue);
  padding: 7px 10px;
  font-size: 12px;
  font-weight: 700;
}

.switch-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: #ffffff;
}
.switch-row input { width: 18px; min-height: 18px; accent-color: var(--blue); }

.error { color: var(--danger); font-size: 12px; font-weight: 700; margin-top: 6px; }
.muted { color: var(--muted); }
.d-none { display: none !important; }
.text-end { text-align: right; }
.qr-box { display: flex; flex-direction: column; align-items: center; gap: 12px; }
.qr-card { padding: 20px; width: 100%; }

.toast-wrap {
  position: fixed;
  top: 18px;
  right: 18px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.toast-clean {
  min-width: min(360px, calc(100vw - 36px));
  padding: 14px 16px;
  border-radius: 14px;
  border: 1px solid var(--line);
  background: #ffffff;
  box-shadow: 0 18px 45px rgba(0,0,0,0.08);
  font-weight: 700;
  opacity: 0;
  pointer-events: none;
  transform: translateY(-8px);
  transition: opacity .18s ease, transform .18s ease;
}
.toast-clean.show { opacity: 1; transform: translateY(0); }
.toast-clean.ok { color: var(--success); background: var(--success-soft); border-color: #d7f8d7; }
.toast-clean.err { color: var(--danger); background: var(--danger-soft); border-color: #ffd6d6; }

@media (max-width: 850px) {
  .expenses-hero { flex-direction: column; }
  .main-card, .section-card { padding: 18px; }
  .col-8, .col-6, .col-4 { grid-column: span 12; }
  .actions { justify-content: stretch; }
  .actions .btn-clean { width: 100%; }
}
</style>

<div class="expenses-page">
  <div class="expenses-shell">
    <header class="expenses-hero">
      <div>
        <div class="expenses-eyebrow">Registro financiero</div>
        <h1 class="expenses-title">Nuevo registro</h1>
        <p class="expenses-subtitle">Selecciona si quieres crear un gasto normal o un movimiento de caja. Cada flujo guarda con sus propias validaciones para evitar el error 422.</p>
      </div>
      <a href="{{ route('expenses.index') }}" class="btn-clean btn-ghost">Regresar</a>
    </header>

    @if ($errors->any())
      <div class="notice warn card-clean" style="margin-bottom:16px;">
        <strong>Revisa estos campos:</strong>
        <ul style="margin:8px 0 0 18px;">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form id="expenseForm" class="card-clean main-card" action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
      @csrf

      <div class="field">
        <label>¿Qué vas a registrar?</label>
        <div class="type-tabs" id="kindTabs">
          <button type="button" class="type-tab" data-kind="gasto">Gasto</button>
          <button type="button" class="type-tab" data-kind="caja">Movimiento de caja</button>
        </div>
        <input type="hidden" name="entry_kind" id="entry_kind" value="{{ $defaultKind }}">
        <p class="section-sub">Movimiento de caja se guarda con sus botones propios: Fondo, Entrega o Devolución.</p>
      </div>

      <section id="gastoSection" class="card-clean section-card">
        <div class="section-head">
          <div>
            <h2 class="section-title">Datos del gasto</h2>
            <p class="section-sub">Solo vehículo o nómina. Para movimiento de caja usa la pestaña correspondiente.</p>
          </div>
          <span class="badge-clean badge-info">Gasto</span>
        </div>

        <div class="field" style="margin-bottom:16px;">
          <label>Tipo de gasto</label>
          <div class="type-tabs" id="expenseTypeTabs">
            <button type="button" class="type-tab" data-type="vehiculo">Vehículo</button>
            <button type="button" class="type-tab" data-type="nomina">Nómina</button>
          </div>
          <input type="hidden" name="expense_type" id="expense_type" value="{{ $defaultExpenseType }}">
        </div>

        <div class="grid">
          <div class="field col-8">
            <label for="concept">Concepto *</label>
            <input type="text" name="concept" id="concept" value="{{ $v('concept') }}" required maxlength="180" placeholder="Ej. Gasolina / Refacciones / Pago quincenal">
            @error('concept')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="field col-4">
            <label for="expense_date">Fecha *</label>
            <input type="date" name="expense_date" id="expense_date" value="{{ $v('expense_date') }}" required>
            @error('expense_date')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="field col-4">
            <label for="amount">Monto *</label>
            <div class="input-money"><span>MXN</span><input type="number" step="0.01" min="0" name="amount" id="amount" value="{{ $v('amount') }}" required></div>
            @error('amount')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="field col-4">
            <label for="payment_method">Método</label>
            @php $methods = ['cash'=>'Efectivo','card'=>'Tarjeta','transfer'=>'Transferencia','other'=>'Otro']; @endphp
            <select name="payment_method" id="payment_method">
              @foreach($methods as $k=>$lbl)
                <option value="{{ $k }}" @selected($v('payment_method','transfer')===$k)>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>

          <div class="field col-4">
            <label for="status">Estado</label>
            @php $statuses = ['paid'=>'Pagado','pending'=>'Pendiente','canceled'=>'Cancelado']; @endphp
            <select name="status" id="status">
              @foreach($statuses as $k=>$lbl)
                <option value="{{ $k }}" @selected($v('status','paid')===$k)>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>

          <div class="field col-6" id="vehBox">
            <label for="vehicle_id">Vehículo *</label>
            <select name="vehicle_id" id="vehicle_id">
              <option value="">Selecciona…</option>
              @foreach(($vehicles ?? []) as $vv)
                <option value="{{ $vv->id }}" @selected((string)$v('vehicle_id')===(string)$vv->id)>{{ $vv->plate_label ?? ($vv->plate ?? $vv->placas ?? ('#'.$vv->id)) }}</option>
              @endforeach
            </select>
            @error('vehicle_id')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="field col-6" id="vehCatBox">
            <label for="vehicle_category">Categoría vehículo *</label>
            <select name="vehicle_category" id="vehicle_category">
              <option value="">Selecciona…</option>
              @foreach($vehicleCats as $k=>$lbl)
                <option value="{{ $k }}" @selected((string)$v('vehicle_category')===(string)$k)>{{ $lbl }}</option>
              @endforeach
            </select>
            @error('vehicle_category')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="field col-6 d-none" id="payrollCatBox">
            <label for="payroll_category">Categoría nómina *</label>
            <select name="payroll_category" id="payroll_category">
              <option value="">Selecciona…</option>
              @foreach($payrollCats as $k=>$lbl)
                <option value="{{ $k }}" @selected((string)$v('payroll_category')===(string)$k)>{{ $lbl }}</option>
              @endforeach
            </select>
            @error('payroll_category')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="field col-6 d-none" id="payrollPeriodBox">
            <label for="payroll_period">Periodo *</label>
            <select name="payroll_period" id="payroll_period"></select>
            @error('payroll_period')<div class="error">{{ $message }}</div>@enderror
          </div>

          <div class="field col-12">
            <label for="description">Descripción</label>
            <textarea name="description" id="description" rows="4">{{ $v('description') }}</textarea>
          </div>
        </div>
      </section>

      <section id="cajaSection" class="card-clean section-card d-none">
        <div class="section-head">
          <div>
            <h2 class="section-title">Movimientos de caja</h2>
            <p class="section-sub">Estos movimientos se guardan por AJAX y no se envían al formulario normal de gastos.</p>
          </div>
          <span class="badge-clean badge-success">Caja</span>
        </div>

        <div class="type-tabs" id="cashTabs" style="margin-bottom:16px;">
          <button type="button" class="type-tab" data-cash="fondo">Fondo para caja</button>
          <button type="button" class="type-tab" data-cash="entrega">Entrega</button>
          <button type="button" class="type-tab" data-cash="devolucion">Devolución</button>
        </div>
        <input type="hidden" id="cash_tab" value="{{ $defaultCashTab }}">

        <div id="cashPaneFondo">
          <div class="notice info">Úsalo cuando se asigna dinero para caja chica o gastos operativos.</div>
          <div class="grid">
            <div class="field col-4">
              <label>Responsable *</label>
              <select id="fondo_manager_id">
                @foreach($managers as $m)
                  <option value="{{ $m->id }}" @selected(auth()->id()==$m->id)>{{ $m->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field col-4">
              <label>Quien asigna *</label>
              <select id="fondo_boss_id">
                @foreach($managers as $m)
                  <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field col-4">
              <label>Fecha y hora</label>
              <input type="datetime-local" id="fondo_performed_at" value="{{ $now }}">
            </div>
            <div class="field col-4">
              <label>Monto *</label>
              <div class="input-money"><span>MXN</span><input type="number" step="0.01" min="0.01" id="fondo_amount"></div>
            </div>
            <div class="field col-8">
              <label>Motivo</label>
              <input type="text" id="fondo_purpose" maxlength="255" placeholder="Caja chica, gastos operativos, etc.">
            </div>
            <div class="field col-12">
              <label>Firma *</label>
              <div class="sig-wrap">
                <canvas id="fondoAdminCanvas" class="sig-canvas"></canvas>
                <div class="sig-actions"><span>Firma aquí</span><button type="button" class="btn-clean btn-ghost" data-clear="#fondoAdminCanvas">Limpiar</button></div>
              </div>
            </div>
            <div class="col-12 text-end">
              <button class="btn-clean btn-success-clean" id="btnFondo" type="button"><span class="btn-text">Guardar fondo</span><span class="btn-spinner d-none"></span></button>
            </div>
          </div>
        </div>

        <div id="cashPaneEntrega" class="d-none">
          <div class="notice info">Elige Directo para firmar aquí o QR para que el receptor firme desde su celular.</div>
          <div class="grid">
            <div class="field col-4">
              <label>Usuario que autoriza *</label>
              <select id="entrega_manager_id">
                @foreach($managers as $m)
                  <option value="{{ $m->id }}" @selected(auth()->id()==$m->id)>{{ $m->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field col-4">
              <label>Fecha y hora</label>
              <input type="datetime-local" id="entrega_performed_at" value="{{ $now }}">
            </div>
            <div class="field col-4">
              <label>Monto *</label>
              <div class="input-money"><span>MXN</span><input type="number" step="0.01" min="0.01" id="entrega_amount"></div>
            </div>
            <div class="field col-12">
              <label>Motivo *</label>
              <input type="text" id="entrega_purpose" maxlength="255" placeholder="Entrega para compras, viáticos, etc.">
            </div>
            <div class="field col-12">
              <label>Evidencia *</label>
              <label class="dropzone" id="entregaDrop">
                <input type="file" id="entregaEvidence" multiple accept="image/*,application/pdf" class="d-none">
                <div class="dropzone-title">Toca o arrastra para seleccionar archivos</div>
                <div class="dropzone-sub">PDF, JPG o PNG.</div>
              </label>
              <div id="entregaFileList" class="file-list"></div>
            </div>
            <div class="field col-6">
              <label>Recepción</label>
              <div class="switch-row">
                <input type="checkbox" id="entrega_self_receive">
                <div><strong>Yo lo pagaré</strong><div class="section-sub">Si está activo, tú eres el receptor.</div></div>
              </div>
            </div>
            <div class="field col-6" id="entregaReceiverPickBox">
              <label>Usuario que recibe *</label>
              <select id="entrega_receiver_id">
                <option value="">Selecciona…</option>
                @foreach($people as $u)
                  <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="field col-12">
              <label>Modo</label>
              <div class="type-tabs">
                <button type="button" class="type-tab active" id="entregaModeDirect">Directo</button>
                <button type="button" class="type-tab" id="entregaModeQr">Con QR</button>
              </div>
            </div>
            <div id="entregaDirectBox" class="col-12">
              <div class="grid">
                <div class="field col-4"><label>NIP *</label><input type="password" id="entrega_nip" inputmode="numeric" maxlength="8" placeholder="4 a 8 dígitos"></div>
                <div class="field col-12"><label>Firma del receptor *</label><div class="sig-wrap"><canvas id="entregaReceiverCanvas" class="sig-canvas"></canvas><div class="sig-actions"><span>Firma aquí</span><button type="button" class="btn-clean btn-ghost" data-clear="#entregaReceiverCanvas">Limpiar</button></div></div></div>
                <div class="col-12 text-end"><button class="btn-clean btn-success-clean" id="btnEntregaDirect" type="button"><span class="btn-text">Guardar entrega</span><span class="btn-spinner d-none"></span></button></div>
              </div>
            </div>
            <div id="entregaQrBox" class="col-12 d-none">
              <div class="grid">
                <div class="field col-4"><label>NIP *</label><input type="password" id="entrega_qr_nip" inputmode="numeric" maxlength="8" placeholder="4 a 8 dígitos"></div>
                <div class="col-12"><button class="btn-clean btn-primary-clean" id="btnEntregaStartQr" type="button"><span class="btn-text">Generar QR</span><span class="btn-spinner d-none"></span></button></div>
                <div class="col-12 d-none" id="entregaQrPanel"><div class="card-clean qr-card qr-box"><div id="entrega_qrcode"></div><span class="badge-clean badge-info" id="entregaQrStatus">Esperando firma…</span><button class="btn-clean btn-outline-clean" id="btnEntregaCopyLink" type="button">Copiar link</button></div></div>
                <div class="col-12 d-none" id="entregaQrSuccess"><div class="notice" style="background:var(--success-soft);color:var(--success);text-align:center;font-weight:700;">Autorizado por el usuario. Firma recibida correctamente.</div></div>
              </div>
            </div>
          </div>
        </div>

        <div id="cashPaneDevolucion" class="d-none">
          <div class="notice warn">Úsalo cuando un usuario devuelve dinero de una entrega anterior.</div>
          <div class="grid">
            <div class="field col-4"><label>Responsable *</label><select id="dev_manager_id">@foreach($managers as $m)<option value="{{ $m->id }}" @selected(auth()->id()==$m->id)>{{ $m->name }}</option>@endforeach</select></div>
            <div class="field col-4"><label>Usuario que devuelve *</label><select id="dev_user_id"><option value="">Selecciona…</option>@foreach($people as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
            <div class="field col-4"><label>Fecha y hora</label><input type="datetime-local" id="dev_performed_at" value="{{ $now }}"></div>
            <div class="field col-4"><label>Monto devuelto *</label><div class="input-money"><span>MXN</span><input type="number" step="0.01" min="0.01" id="dev_amount"></div></div>
            <div class="field col-8"><label>Motivo *</label><input type="text" id="dev_purpose" maxlength="255" placeholder="Devolución parcial, sobrante, etc."></div>
            <div class="field col-12"><label>Evidencias *</label><label class="dropzone" id="devDrop"><input type="file" id="devEvidence" multiple accept="image/*,application/pdf" class="d-none"><div class="dropzone-title">Toca o arrastra para seleccionar archivos</div></label><div id="devFileList" class="file-list"></div></div>
            <div class="field col-6"><label>Firma del usuario *</label><div class="sig-wrap"><canvas id="devUserCanvas" class="sig-canvas"></canvas><div class="sig-actions"><span>Firma del usuario</span><button type="button" class="btn-clean btn-ghost" data-clear="#devUserCanvas">Limpiar</button></div></div></div>
            <div class="field col-6"><label>Firma del responsable *</label><div class="sig-wrap"><canvas id="devAdminCanvas" class="sig-canvas"></canvas><div class="sig-actions"><span>Firma del responsable</span><button type="button" class="btn-clean btn-ghost" data-clear="#devAdminCanvas">Limpiar</button></div></div></div>
            <div class="col-12 text-end"><button class="btn-clean btn-success-clean" id="btnDevolucion" type="button"><span class="btn-text">Guardar devolución</span><span class="btn-spinner d-none"></span></button></div>
          </div>
        </div>
      </section>

      <section id="signSection" class="card-clean section-card">
        <div class="section-head"><div><h2 class="section-title">Firma y autorización</h2><p class="section-sub">Solo para gasto normal.</p></div></div>
        <div class="grid">
          <div class="field col-6"><label for="gasto_nip">NIP *</label><input type="password" name="nip" id="gasto_nip" inputmode="numeric" maxlength="8" placeholder="4 a 8 dígitos" value="{{ $v('nip') }}" required>@error('nip')<div class="error">{{ $message }}</div>@enderror</div>
          <div class="field col-12"><label>Firma del responsable *</label><div class="sig-wrap"><canvas id="gastoAdminCanvas" class="sig-canvas"></canvas><div class="sig-actions"><span>Firma aquí</span><button type="button" class="btn-clean btn-ghost" data-clear="#gastoAdminCanvas">Limpiar</button></div><input type="hidden" name="admin_signature" id="admin_signature" value="{{ $v('admin_signature') }}"></div></div>
        </div>
      </section>

      <section id="evidenceSection" class="card-clean section-card">
        <div class="field">
          <label>Evidencia opcional</label>
          <label class="dropzone" id="dropzone">
            <input id="fileInput" class="d-none" type="file" name="attachment" accept="*/*">
            <div class="dropzone-title">Toca o arrastra para seleccionar un archivo</div>
            <div class="dropzone-sub" id="fileMeta">PDF, imagen o comprobante.</div>
          </label>
          @error('attachment')<div class="error">{{ $message }}</div>@enderror
        </div>
      </section>

      <div class="actions">
        <a href="{{ route('expenses.index') }}" class="btn-clean btn-ghost">Cancelar</a>
        <button type="submit" class="btn-clean btn-primary-clean" id="btnSave"><span class="btn-text">Guardar gasto</span><span class="btn-spinner d-none"></span></button>
      </div>
    </form>
  </div>
</div>

<div class="toast-wrap">
  <div id="toastOK" class="toast-clean ok">Listo.</div>
  <div id="toastERR" class="toast-clean err">Error</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(function(){
  const csrf = document.querySelector('input[name="_token"]').value;
  const $ = (s, r=document) => r.querySelector(s);
  const $$ = (s, r=document) => Array.from(r.querySelectorAll(s));

  function showToast(id, msg){
    const el = $(id);
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 3200);
  }
  const ok = (m) => showToast('#toastOK', m);
  const err = (m) => showToast('#toastERR', m);

  function setLoadingBtn(btn,on=true){
    if(!btn) return;
    btn.disabled = on;
    btn.querySelector('.btn-text')?.classList.toggle('d-none', on);
    btn.querySelector('.btn-spinner')?.classList.toggle('d-none', !on);
  }

  function monthName(m){ return ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][m]; }
  function fillPayrollPeriods(){
    const year = new Date().getFullYear();
    const select = $('#payroll_period');
    select.innerHTML = '<option value="">Selecciona…</option>';
    for(let m=0; m<12; m++){
      const month = String(m+1).padStart(2,'0');
      select.insertAdjacentHTML('beforeend', `<option value="${year}-${month}-Q1">Primera quincena de ${monthName(m)} ${year}</option>`);
      select.insertAdjacentHTML('beforeend', `<option value="${year}-${month}-Q2">Segunda quincena de ${monthName(m)} ${year}</option>`);
    }
    const oldVal = @json($v('payroll_period'));
    if(oldVal) select.value = oldVal;
  }

  const pads = {};
  function fitCanvas(canvas, pad){
    if(!canvas) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    if(rect.width === 0) return;
    canvas.width = rect.width * ratio;
    canvas.height = 180 * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    pad?.clear();
  }
  function makePad(id){
    const canvas = document.getElementById(id);
    if(!canvas || !window.SignaturePad) return null;
    const pad = new SignaturePad(canvas, { minWidth: 0.8, maxWidth: 2.2, penColor: '#111111' });
    pads[id] = pad;
    setTimeout(() => fitCanvas(canvas, pad), 60);
    return pad;
  }
  const gastoAdminPad = makePad('gastoAdminCanvas');
  const fondoAdminPad = makePad('fondoAdminCanvas');
  const entregaReceiverPad = makePad('entregaReceiverCanvas');
  const devUserPad = makePad('devUserCanvas');
  const devAdminPad = makePad('devAdminCanvas');
  window.addEventListener('resize', () => Object.entries(pads).forEach(([id,pad]) => fitCanvas(document.getElementById(id), pad)));
  $$('[data-clear]').forEach(btn => btn.addEventListener('click', () => pads[(btn.dataset.clear || '').replace('#','')]?.clear()));
  const toData = (pad) => (!pad || pad.isEmpty()) ? '' : pad.toDataURL('image/png');

  function setDisabledInside(root, disabled){
    if(!root) return;
    $$('input, select, textarea, button', root).forEach(el => {
      if(el.hasAttribute('data-clear')) return;
      if(el.closest('#kindTabs')) return;
      el.disabled = disabled;
    });
  }

  const kindInput = $('#entry_kind');
  const gastoSection = $('#gastoSection');
  const cajaSection = $('#cajaSection');
  const signSection = $('#signSection');
  const evidenceSection = $('#evidenceSection');
  const btnSave = $('#btnSave');

  function setKind(k){
    const isCaja = k === 'caja';
    kindInput.value = k;
    $$('#kindTabs [data-kind]').forEach(x => x.classList.toggle('active', x.dataset.kind === k));

    if(isCaja){
      kindInput.removeAttribute('name');
      btnSave.disabled = true;
    } else {
      kindInput.setAttribute('name','entry_kind');
      btnSave.disabled = false;
    }

    gastoSection.classList.toggle('d-none', isCaja);
    cajaSection.classList.toggle('d-none', !isCaja);
    signSection.classList.toggle('d-none', isCaja);
    btnSave.classList.toggle('d-none', isCaja);

    setDisabledInside(gastoSection, isCaja);
    setDisabledInside(signSection, isCaja);
    setDisabledInside(evidenceSection, isCaja);
    setDisabledInside(cajaSection, !isCaja);

    $('#concept').required = !isCaja;
    $('#expense_date').required = !isCaja;
    $('#amount').required = !isCaja;
    $('#gasto_nip').required = !isCaja;

    setExpenseType($('#expense_type').value || 'vehiculo');
    setTimeout(()=>window.dispatchEvent(new Event('resize')), 80);
  }
  $$('#kindTabs [data-kind]').forEach(x => x.addEventListener('click', () => setKind(x.dataset.kind)));

  function setExpenseType(t){
    $('#expense_type').value = t;
    $$('#expenseTypeTabs [data-type]').forEach(x => x.classList.toggle('active', x.dataset.type === t));
    const isVeh = t === 'vehiculo';
    const isNom = t === 'nomina';

    $('#vehBox').classList.toggle('d-none', !isVeh);
    $('#vehCatBox').classList.toggle('d-none', !isVeh);
    $('#payrollCatBox').classList.toggle('d-none', !isNom);
    $('#payrollPeriodBox').classList.toggle('d-none', !isNom);

    $('#vehicle_id').required = isVeh && kindInput.value !== 'caja';
    $('#vehicle_category').required = isVeh && kindInput.value !== 'caja';
    $('#payroll_category').required = isNom && kindInput.value !== 'caja';
    $('#payroll_period').required = isNom && kindInput.value !== 'caja';

    evidenceSection.classList.toggle('d-none', isNom || kindInput.value === 'caja');
    if(isNom) fillPayrollPeriods();
    setTimeout(()=>window.dispatchEvent(new Event('resize')), 60);
  }
  $$('#expenseTypeTabs [data-type]').forEach(x => x.addEventListener('click', () => setExpenseType(x.dataset.type)));

  const form = $('#expenseForm');
  form.addEventListener('keydown', e => { if(kindInput.value === 'caja' && e.key === 'Enter') e.preventDefault(); });
  form.addEventListener('submit', e => {
    if(kindInput.value === 'caja'){
      e.preventDefault();
      err('Los movimientos de caja se guardan con sus botones: Guardar fondo, entrega o devolución.');
      return;
    }
    const nip = ($('#gasto_nip').value || '').trim();
    if(!/^\d{4,8}$/.test(nip)){
      e.preventDefault();
      err('NIP inválido. Debe tener de 4 a 8 dígitos.');
      return;
    }
    const sig = toData(gastoAdminPad);
    if(!sig){
      e.preventDefault();
      err('Falta la firma del responsable.');
      return;
    }
    $('#admin_signature').value = sig;
    setLoadingBtn(btnSave, true);
    ok('Enviando…');
  });

  $('#fileInput').addEventListener('change', e => {
    const f = e.target.files?.[0];
    $('#fileMeta').textContent = f ? f.name : 'PDF, imagen o comprobante.';
  });
  $('#dropzone').addEventListener('click', () => $('#fileInput').click());

  function setCashTab(t){
    $('#cash_tab').value = t;
    $$('#cashTabs [data-cash]').forEach(x => x.classList.toggle('active', x.dataset.cash === t));
    $('#cashPaneFondo').classList.toggle('d-none', t !== 'fondo');
    $('#cashPaneEntrega').classList.toggle('d-none', t !== 'entrega');
    $('#cashPaneDevolucion').classList.toggle('d-none', t !== 'devolucion');
    setTimeout(()=>window.dispatchEvent(new Event('resize')), 80);
  }
  $$('#cashTabs [data-cash]').forEach(x => x.addEventListener('click', () => setCashTab(x.dataset.cash)));

  function syncEntregaSelf(){
    const on = $('#entrega_self_receive').checked;
    $('#entregaReceiverPickBox').classList.toggle('d-none', on);
    if(on) $('#entrega_receiver_id').value = '';
  }
  $('#entrega_self_receive').addEventListener('change', syncEntregaSelf);

  $('#entregaModeDirect').addEventListener('click', () => {
    $('#entregaModeDirect').classList.add('active');
    $('#entregaModeQr').classList.remove('active');
    $('#entregaDirectBox').classList.remove('d-none');
    $('#entregaQrBox').classList.add('d-none');
    setTimeout(()=>window.dispatchEvent(new Event('resize')), 60);
  });
  $('#entregaModeQr').addEventListener('click', () => {
    $('#entregaModeQr').classList.add('active');
    $('#entregaModeDirect').classList.remove('active');
    $('#entregaDirectBox').classList.add('d-none');
    $('#entregaQrBox').classList.remove('d-none');
  });

  function renderFiles(input, list){
    const box = $(list);
    box.innerHTML = '';
    Array.from(input.files || []).forEach(f => box.insertAdjacentHTML('beforeend', `<span class="file-pill">${f.name}</span>`));
  }
  $('#entregaDrop').addEventListener('click', () => $('#entregaEvidence').click());
  $('#entregaEvidence').addEventListener('change', e => renderFiles(e.target, '#entregaFileList'));
  $('#devDrop').addEventListener('click', () => $('#devEvidence').click());
  $('#devEvidence').addEventListener('change', e => renderFiles(e.target, '#devFileList'));

  async function postForm(url, fd){
    const res = await fetch(url, { method:'POST', body:fd, headers:{ 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' } });
    const data = await res.json().catch(() => ({}));
    if(!res.ok) throw new Error(data.message || 'No se pudo guardar.');
    return data;
  }

  $('#btnFondo').addEventListener('click', async function(){
    const amount = parseFloat($('#fondo_amount').value || '0');
    if(isNaN(amount) || amount < 0.01) return err('Monto inválido.');
    const sig = toData(fondoAdminPad);
    if(!sig) return err('Falta la firma.');
    const fd = new FormData();
    fd.append('manager_id', $('#fondo_manager_id').value);
    fd.append('boss_id', $('#fondo_boss_id').value);
    fd.append('performed_at', $('#fondo_performed_at').value);
    fd.append('amount', $('#fondo_amount').value);
    fd.append('purpose', ($('#fondo_purpose').value || '').trim());
    fd.append('manager_signature', sig);
    try { setLoadingBtn(this,true); const r = await postForm("{{ route('expenses.movement.allocation.store', [], false) }}", fd); ok(`Fondo registrado (#${r.id})`); location.href = "{{ route('expenses.index', [], false) }}"; }
    catch(e){ err(e.message); } finally { setLoadingBtn(this,false); }
  });

  function entregaCommonValidate(){
    const amount = parseFloat($('#entrega_amount').value || '0');
    if(isNaN(amount) || amount < 0.01){ err('Monto inválido.'); return null; }
    const purpose = ($('#entrega_purpose').value || '').trim();
    if(purpose.length < 3){ err('Motivo inválido.'); return null; }
    if(!$('#entrega_self_receive').checked && !$('#entrega_receiver_id').value){ err('Selecciona el usuario que recibe.'); return null; }
    const files = $('#entregaEvidence').files;
    if(!files || files.length === 0){ err('Agrega al menos una evidencia.'); return null; }
    return { files };
  }

  $('#btnEntregaDirect').addEventListener('click', async function(){
    const base = entregaCommonValidate(); if(!base) return;
    const nip = ($('#entrega_nip').value || '').trim();
    if(!/^\d{4,8}$/.test(nip)) return err('NIP inválido.');
    const sig = toData(entregaReceiverPad);
    if(!sig) return err('Falta la firma del receptor.');
    const fd = new FormData();
    fd.append('manager_id', $('#entrega_manager_id').value);
    fd.append('receiver_id', $('#entrega_self_receive').checked ? '' : $('#entrega_receiver_id').value);
    fd.append('self_receive', $('#entrega_self_receive').checked ? '1' : '0');
    fd.append('performed_at', $('#entrega_performed_at').value);
    fd.append('amount', $('#entrega_amount').value);
    fd.append('purpose', $('#entrega_purpose').value);
    fd.append('nip', nip);
    fd.append('counterparty_signature', sig);
    Array.from(base.files).forEach(f => fd.append('evidence[]', f));
    try { setLoadingBtn(this,true); const r = await postForm("{{ route('expenses.movement.disbursement.direct', [], false) }}", fd); ok(`Entrega guardada (#${r.id})`); location.href = "{{ route('expenses.index', [], false) }}"; }
    catch(e){ err(e.message); } finally { setLoadingBtn(this,false); }
  });

  let pollTimer = null, activeToken = null, lastQrUrl = '';
  function stopPolling(){ if(pollTimer){ clearInterval(pollTimer); pollTimer = null; } }

  $('#btnEntregaStartQr').addEventListener('click', async function(){
    const base = entregaCommonValidate(); if(!base) return;
    const nip = ($('#entrega_qr_nip').value || '').trim();
    if(!/^\d{4,8}$/.test(nip)) return err('NIP inválido.');
    const fd = new FormData();
    fd.append('manager_id', $('#entrega_manager_id').value);
    fd.append('receiver_id', $('#entrega_self_receive').checked ? '' : $('#entrega_receiver_id').value);
    fd.append('self_receive', $('#entrega_self_receive').checked ? '1' : '0');
    fd.append('performed_at', $('#entrega_performed_at').value);
    fd.append('amount', $('#entrega_amount').value);
    fd.append('purpose', $('#entrega_purpose').value);
    fd.append('nip', nip);
    Array.from(base.files).forEach(f => fd.append('evidence[]', f));
    try {
      setLoadingBtn(this,true);
      const r = await postForm("{{ route('expenses.movement.disbursement.qr.start', [], false) }}", fd);
      $('#entregaQrPanel').classList.remove('d-none');
      $('#entregaQrSuccess').classList.add('d-none');
      $('#entrega_qrcode').innerHTML = '';
      new QRCode($('#entrega_qrcode'), { text:r.url, width:230, height:230 });
      $('#entregaQrStatus').className = 'badge-clean badge-info';
      $('#entregaQrStatus').textContent = 'Esperando firma del usuario…';
      activeToken = r.token; lastQrUrl = r.url;
      stopPolling();
      pollTimer = setInterval(async () => {
        try {
          const res = await fetch("{{ url('', [], false) }}/expenses/movements/qr/status/" + activeToken, { headers:{ 'Accept':'application/json' } });
          const s = await res.json();
          if(s.expired){ $('#entregaQrStatus').className = 'badge-clean badge-danger'; $('#entregaQrStatus').textContent = 'QR expirado'; stopPolling(); }
          if(s.acknowledged){ stopPolling(); $('#entregaQrPanel').classList.add('d-none'); $('#entregaQrSuccess').classList.remove('d-none'); ok('Autorizado por el usuario'); }
        } catch(e) {}
      }, 2200);
      ok('QR generado');
    } catch(e){ err(e.message); } finally { setLoadingBtn(this,false); }
  });

  $('#btnEntregaCopyLink').addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(lastQrUrl); ok('Link copiado'); }
    catch(e){ err('No se pudo copiar el link.'); }
  });

  $('#btnDevolucion').addEventListener('click', async function(){
    if(!$('#dev_user_id').value) return err('Selecciona el usuario que devuelve.');
    const amount = parseFloat($('#dev_amount').value || '0');
    if(isNaN(amount) || amount < 0.01) return err('Monto inválido.');
    const purpose = ($('#dev_purpose').value || '').trim();
    if(purpose.length < 3) return err('Motivo inválido.');
    const uSig = toData(devUserPad), aSig = toData(devAdminPad);
    if(!uSig) return err('Falta la firma del usuario.');
    if(!aSig) return err('Falta la firma del responsable.');
    const files = $('#devEvidence').files;
    if(!files || files.length === 0) return err('Agrega al menos una evidencia.');
    const fd = new FormData();
    fd.append('manager_id', $('#dev_manager_id').value);
    fd.append('counterparty_id', $('#dev_user_id').value);
    fd.append('performed_at', $('#dev_performed_at').value);
    fd.append('amount', $('#dev_amount').value);
    fd.append('purpose', $('#dev_purpose').value);
    fd.append('counterparty_signature', uSig);
    fd.append('manager_signature', aSig);
    Array.from(files).forEach(f => fd.append('evidence[]', f));
    try { setLoadingBtn(this,true); const r = await postForm("{{ route('expenses.movement.return.store', [], false) }}", fd); ok(`Devolución guardada (#${r.id})`); location.href = "{{ route('expenses.index', [], false) }}"; }
    catch(e){ err(e.message); } finally { setLoadingBtn(this,false); }
  });

  setKind(kindInput.value || 'gasto');
  setCashTab($('#cash_tab').value || 'fondo');
  syncEntregaSelf();
})();
</script>
@endsection
