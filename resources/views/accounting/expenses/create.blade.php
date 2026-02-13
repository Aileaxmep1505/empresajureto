{{-- resources/views/accounting/expenses/create.blade.php --}}
@extends('layouts.app')
@section('title','Nuevo registro')
@section('titulo','Nuevo registro')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/expenses.css') }}?v={{ time() }}">

@php
  $v = function($key, $default = null) { return old($key) ?? $default; };

  // Tabs principales
  $defaultKind = $v('entry_kind','gasto'); // gasto | caja

  // Gasto: solo Vehículo / Nómina (SE ELIMINA "General")
  $defaultExpenseType = $v('expense_type','vehiculo'); // vehiculo | nomina

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

  // Movimientos de caja: 3 apartados
  $defaultCashTab = $v('cash_tab','fondo'); // fondo | entrega | devolucion

  $people   = $people   ?? collect();
  $managers = $managers ?? collect();
  $now      = $now ?? now()->format('Y-m-d\TH:i');
@endphp

<div class="container page-wrap">
  <div class="hero mt-2 mb-3 d-flex align-items-center justify-content-between flex-wrap">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white border"
           style="width:42px;height:42px;border-color:#d7e8ff!important">
        <i class="bi bi-journal-text" style="font-size:1.15rem;color:#1f7ae6"></i>
      </div>
      <div>
        <h1 class="h4 mb-0">Nuevo registro</h1>
        <div class="small text-muted">Elige si es <strong>Gasto</strong> o <strong>Movimiento de caja</strong>.</div>
      </div>
    </div>
  </div>

  {{-- FORM GASTO (POST normal) --}}
  <form id="expenseForm" class="card border-0" action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card-body">
      {{-- KIND: Gasto / Caja --}}
      <div class="mb-3">
        <label class="form-label mb-2">¿Qué vas a registrar?</label>
        <div class="type-tabs" id="kindTabs">
          <button type="button" class="type-tab" data-kind="gasto"><i class="bi bi-receipt-cutoff"></i> Gasto</button>
          <button type="button" class="type-tab" data-kind="caja"><i class="bi bi-safe2"></i> Movimiento de caja</button>
        </div>
        <input type="hidden" name="entry_kind" id="entry_kind" value="{{ $defaultKind }}">
        <div class="small text-muted mt-2">Movimiento de caja = Fondo, Entrega, Devolución (firmas + NIP en cada flujo).</div>
      </div>

      {{-- ===================== GASTO (SIN "GENERAL") ===================== --}}
      <div id="gastoSection" class="card section-card border-0 mb-3">
        <div class="card-body">
          <div class="section-title"><i class="bi bi-receipt" style="color:#1f7ae6"></i> Datos del gasto</div>
          <p class="section-sub">Solo Vehículo o Nómina. (Se eliminó “General”).</p>

          <div class="mb-3">
            <label class="form-label mb-2">Tipo de gasto</label>
            <div class="type-tabs" id="expenseTypeTabs">
              <button type="button" class="type-tab" data-type="vehiculo"><i class="bi bi-truck"></i> Vehículo</button>
              <button type="button" class="type-tab" data-type="nomina"><i class="bi bi-people"></i> Nómina</button>
            </div>
            <input type="hidden" name="expense_type" id="expense_type" value="{{ $defaultExpenseType }}">
          </div>

          <div class="row g-3">
            <div class="col-12 col-md-8">
              <label class="form-label">Concepto *</label>
              <input type="text" name="concept" id="concept"
                     class="form-control @error('concept') is-invalid @enderror"
                     value="{{ $v('concept') }}" required
                     placeholder="Ej. Gasolina / Refacciones / Pago quincenal...">
              @error('concept')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Fecha *</label>
              <input type="date" name="expense_date" id="expense_date"
                     class="form-control @error('expense_date') is-invalid @enderror"
                     value="{{ $v('expense_date') }}" required>
              @error('expense_date')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Monto *</label>
              <div class="input-group">
                <span class="input-group-text">MXN</span>
                <input type="number" step="0.01" min="0" name="amount" id="amount"
                       class="form-control @error('amount') is-invalid @enderror"
                       value="{{ $v('amount') }}" required>
              </div>
              @error('amount')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Método</label>
              @php $methods = ['cash'=>'Efectivo','card'=>'Tarjeta','transfer'=>'Transferencia','other'=>'Otro']; @endphp
              <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                @foreach($methods as $k=>$lbl)
                  <option value="{{ $k }}" {{ $v('payment_method','transfer')===$k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
              </select>
              @error('payment_method')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Estado</label>
              @php $statuses = ['paid'=>'Pagado','pending'=>'Pendiente','canceled'=>'Cancelado']; @endphp
              <select name="status" class="form-select @error('status') is-invalid @enderror">
                @foreach($statuses as $k=>$lbl)
                  <option value="{{ $k }}" {{ $v('status','paid')===$k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
              </select>
              @error('status')<div class="error">{{ $message }}</div>@enderror
            </div>

            {{-- Vehículo --}}
            <div class="col-12 col-md-6" id="vehBox">
              <label class="form-label">Vehículo *</label>
              <select name="vehicle_id" id="vehicle_id" class="form-select @error('vehicle_id') is-invalid @enderror">
                <option value="">Selecciona…</option>
                @foreach(($vehicles ?? []) as $vv)
                  <option value="{{ $vv->id }}" {{ (string)$v('vehicle_id')===(string)$vv->id ? 'selected' : '' }}>
                    {{ $vv->plate_label ?? ($vv->plate ?? $vv->placas ?? ('#'.$vv->id)) }}
                  </option>
                @endforeach
              </select>
              @error('vehicle_id')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6" id="vehCatBox">
              <label class="form-label">Categoría (vehículo) *</label>
              <select name="vehicle_category" id="vehicle_category" class="form-select @error('vehicle_category') is-invalid @enderror">
                <option value="" disabled selected>Selecciona…</option>
                @foreach($vehicleCats as $k=>$lbl)
                  <option value="{{ $k }}" {{ (string)$v('vehicle_category')===(string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
              </select>
              @error('vehicle_category')<div class="error">{{ $message }}</div>@enderror
            </div>

            {{-- Nómina --}}
            <div class="col-12 col-md-6 d-none" id="payrollCatBox">
              <label class="form-label">Categoría (nómina) *</label>
              <select name="payroll_category" id="payroll_category" class="form-select @error('payroll_category') is-invalid @enderror">
                <option value="" disabled selected>Selecciona…</option>
                @foreach($payrollCats as $k=>$lbl)
                  <option value="{{ $k }}" {{ (string)$v('payroll_category')===(string)$k ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
              </select>
              @error('payroll_category')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12 col-md-6 d-none" id="payrollPeriodBox">
              <label class="form-label">Periodo (quincena) *</label>
              <select name="payroll_period" id="payroll_period" class="form-select @error('payroll_period') is-invalid @enderror"></select>
              @error('payroll_period')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
              <label class="form-label">Descripción</label>
              <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="4">{{ $v('description') }}</textarea>
              @error('description')<div class="error">{{ $message }}</div>@enderror
            </div>
          </div>
        </div>
      </div>

      {{-- ===================== MOVIMIENTOS DE CAJA (AJAX) ===================== --}}
      <div id="cajaSection" class="card section-card border-0 mb-3 d-none">
        <div class="card-body">
          <div class="section-title"><i class="bi bi-safe2" style="color:#059669"></i> Movimientos de caja</div>
          <p class="section-sub">Registra: Fondo para caja, Entrega, Devolución. (Evidencia y firmas dentro de cada apartado).</p>

          <div class="type-tabs mb-3" id="cashTabs">
            <button type="button" class="type-tab" data-cash="fondo"><i class="bi bi-arrow-down-circle"></i> Fondo para caja</button>
            <button type="button" class="type-tab" data-cash="entrega"><i class="bi bi-arrow-up-right-circle"></i> Entrega</button>
            <button type="button" class="type-tab" data-cash="devolucion"><i class="bi bi-arrow-repeat"></i> Devolución</button>
          </div>
          <input type="hidden" id="cash_tab" value="{{ $defaultCashTab }}">

          {{-- ========== TAB FONDO ========== --}}
          <div id="cashPaneFondo">
            <div class="alert border-0 bg-opacity-25 mb-3" style="background:#eaf4ff">
              <i class="bi bi-info-circle me-2"></i>
              Úsalo cuando te asignan dinero para <strong>caja chica</strong> o <strong>gastos</strong>.
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Responsable (quien firma)</label>
                <select id="fondo_manager_id" class="form-select">
                  @foreach($managers as $m)
                    <option value="{{ $m->id }}" @selected(auth()->id()==$m->id)>{{ $m->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Quien asigna</label>
                <select id="fondo_boss_id" class="form-select">
                  @foreach($managers as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Fecha y hora</label>
                <input type="datetime-local" id="fondo_performed_at" class="form-control" value="{{ $now }}">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Monto (MXN) *</label>
                <div class="input-group">
                  <span class="input-group-text">MXN</span>
                  <input type="number" step="0.01" min="0.01" id="fondo_amount" class="form-control">
                </div>
              </div>

              <div class="col-12 col-md-8">
                <label class="form-label">Motivo</label>
                <input type="text" id="fondo_purpose" class="form-control" maxlength="255" placeholder="Caja chica, gastos operativos, etc.">
              </div>

              <div class="col-12">
                <label class="form-label">Firma *</label>
                <div class="sig-wrap">
                  <canvas id="fondoAdminCanvas" class="sig-canvas"></canvas>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">Firma aquí</small>
                    <button class="btn btn-sm btn-outline-modern btn-outline-secondary lift" type="button" data-clear="#fondoAdminCanvas">
                      <i class="bi bi-eraser"></i> Limpiar
                    </button>
                  </div>
                </div>
              </div>

              <div class="col-12 text-end">
                <button class="btn btn-pastel-green lift" id="btnFondo" type="button" data-loading="false">
                  <span class="btn-text"><i class="bi bi-check2-circle me-1"></i> Guardar fondo</span>
                  <span class="btn-spinner d-none spinner-border spinner-border-sm"></span>
                </button>
              </div>
            </div>
          </div>

          {{-- ========== TAB ENTREGA ========== --}}
          <div id="cashPaneEntrega" class="d-none">
            <div class="alert border-0 bg-opacity-25 mb-3" style="background:#eaf4ff">
              <i class="bi bi-lightning-charge-fill me-2"></i>
              Elige <strong>Directo</strong> (firma aquí + tu NIP) o <strong>con QR</strong> (firma desde el celular).
              <div class="small text-muted mt-1">La evidencia (PDF/imagen) se sube aquí y queda guardada en el movimiento.</div>
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Usuario que autoriza (NIP)</label>
                <select id="entrega_manager_id" class="form-select">
                  @foreach($managers as $m)
                    <option value="{{ $m->id }}" @selected(auth()->id()==$m->id)>{{ $m->name }}</option>
                  @endforeach
                </select>
                <div class="small text-muted mt-1">No necesariamente es quien entrega físicamente, es quien autoriza con su NIP.</div>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Fecha y hora</label>
                <input type="datetime-local" id="entrega_performed_at" class="form-control" value="{{ $now }}">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Monto (MXN) *</label>
                <div class="input-group">
                  <span class="input-group-text">MXN</span>
                  <input type="number" step="0.01" min="0.01" id="entrega_amount" class="form-control">
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Motivo *</label>
                <input type="text" id="entrega_purpose" class="form-control" maxlength="255" placeholder="Entrega para compras, viáticos, etc.">
              </div>

              {{-- Evidencia (obligatoria en entrega) --}}
              <div class="col-12">
                <label class="form-label">Evidencia (PDF/JPG/PNG) *</label>
                <label class="dropzone w-100" id="entregaDrop">
                  <input type="file" id="entregaEvidence" multiple accept="image/*,application/pdf" class="d-none">
                  <div class="text-muted"><i class="bi bi-cloud-arrow-up me-1"></i> Arrastra o toca para seleccionar archivos</div>
                  <div class="small text-muted mt-1">Ej: ticket, factura, comprobante, foto del recibo.</div>
                </label>
                <div id="entregaFileList" class="mt-2"></div>
              </div>

              <div class="col-12 col-lg-6">
                <label class="form-label">¿Quién lo pagará?</label>
                <div class="sw">
                  <input type="checkbox" id="entrega_self_receive">
                  <div>
                    <div style="font-weight:800;color:#0b1220">Yo lo pagaré</div>
                    <div class="small text-muted">Si está activo, el receptor serás tú y no seleccionas a nadie.</div>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-6" id="entregaReceiverPickBox">
                <label class="form-label">Usuario que recibe *</label>
                <select id="entrega_receiver_id" class="form-select">
                  <option value="">Selecciona…</option>
                  @foreach($people as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12">
                <div class="btn-group" role="group">
                  <input type="radio" class="btn-check" name="entregaMode" id="entregaModeDirect" autocomplete="off" checked>
                  <label class="btn btn-outline-modern btn-outline-primary lift" for="entregaModeDirect">
                    <i class="bi bi-person-check me-1"></i> Directo
                  </label>
                  <input type="radio" class="btn-check" name="entregaMode" id="entregaModeQr" autocomplete="off">
                  <label class="btn btn-outline-modern btn-outline-primary lift" for="entregaModeQr">
                    <i class="bi bi-qr-code me-1"></i> Con QR
                  </label>
                </div>
              </div>

              {{-- ENTREGA DIRECTO --}}
              <div id="entregaDirectBox" class="col-12">
                <div class="row g-3">
                  <div class="col-12 col-md-4">
                    <label class="form-label">NIP del que autoriza *</label>
                    <input type="password" id="entrega_nip" class="form-control" placeholder="NIP 4–8 dígitos" inputmode="numeric" maxlength="8">
                  </div>

                  <div class="col-12">
                    <label class="form-label">Firma del receptor *</label>
                    <div class="sig-wrap">
                      <canvas id="entregaReceiverCanvas" class="sig-canvas"></canvas>
                      <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">Firma aquí</small>
                        <button class="btn btn-sm btn-outline-modern btn-outline-secondary lift" type="button" data-clear="#entregaReceiverCanvas">
                          <i class="bi bi-eraser"></i> Limpiar
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 text-end">
                    <button class="btn btn-pastel-green lift" id="btnEntregaDirect" type="button" data-loading="false">
                      <span class="btn-text"><i class="bi bi-send-check me-1"></i> Guardar entrega</span>
                      <span class="btn-spinner d-none spinner-border spinner-border-sm"></span>
                    </button>
                  </div>
                </div>
              </div>

              {{-- ENTREGA QR --}}
              <div id="entregaQrBox" class="col-12 d-none">
                <div class="row g-3">
                  <div class="col-12 col-md-4">
                    <label class="form-label">NIP del que autoriza *</label>
                    <input type="password" id="entrega_qr_nip" class="form-control" placeholder="NIP 4–8 dígitos" inputmode="numeric" maxlength="8">
                  </div>

                  <div class="col-12">
                    <button class="btn btn-pastel-blue lift" id="btnEntregaStartQr" type="button" data-loading="false">
                      <span class="btn-text"><i class="bi bi-qr-code me-1"></i> Generar QR</span>
                      <span class="btn-spinner d-none spinner-border spinner-border-sm"></span>
                    </button>
                  </div>

                  <div class="col-12 d-none" id="entregaQrPanel">
                    <div class="card">
                      <div class="card-body d-flex flex-column align-items-center">
                        <div id="entrega_qrcode" class="mb-2"></div>
                        <div class="text-center">
                          <div class="small text-muted">El usuario escanea, confirma el motivo y firma desde su celular.</div>
                          <div class="mt-2">
                            <span class="badge bg-info d-inline-flex align-items-center gap-1" id="entregaQrStatus">
                              <span class="spinner-border spinner-border-sm"></span> Esperando…
                            </span>
                          </div>
                          <div class="mt-2">
                            <button class="btn btn-sm btn-outline-modern btn-outline-secondary lift" id="btnEntregaCopyLink" type="button">
                              <i class="bi bi-link-45deg"></i> Copiar link
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 d-none" id="entregaQrSuccess">
                    <div class="card border-0" style="background:#f1fff7">
                      <div class="card-body text-center">
                        <i class="bi bi-check2-circle" style="font-size:46px;color:#34d399"></i>
                        <h5 class="mb-1 mt-2">Autorizado por el usuario</h5>
                        <div class="text-muted">Firma recibida correctamente.</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-12">
                    <small class="text-muted">Cuando se confirme, el QR se ocultará y verás la confirmación.</small>
                  </div>
                </div>
              </div>

            </div>
          </div>

          {{-- ========== TAB DEVOLUCIÓN ========== --}}
          <div id="cashPaneDevolucion" class="d-none">
            <div class="alert border-0 bg-opacity-25 mb-3" style="background:#fff7ed">
              <i class="bi bi-arrow-repeat me-2"></i>
              Úsalo cuando un usuario te devuelve dinero de una entrega anterior.
            </div>

            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Responsable (recibe)</label>
                <select id="dev_manager_id" class="form-select">
                  @foreach($managers as $m)
                    <option value="{{ $m->id }}" @selected(auth()->id()==$m->id)>{{ $m->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Usuario (devuelve) *</label>
                <select id="dev_user_id" class="form-select">
                  <option value="">Selecciona…</option>
                  @foreach($people as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Fecha y hora</label>
                <input type="datetime-local" id="dev_performed_at" class="form-control" value="{{ $now }}">
              </div>

              <div class="col-12 col-md-4">
                <label class="form-label">Monto devuelto (MXN) *</label>
                <div class="input-group">
                  <span class="input-group-text">MXN</span>
                  <input type="number" step="0.01" min="0.01" id="dev_amount" class="form-control">
                </div>
              </div>

              <div class="col-12 col-md-8">
                <label class="form-label">Motivo *</label>
                <input type="text" id="dev_purpose" class="form-control" maxlength="255" placeholder="Devolución parcial/total, sobrante, etc.">
              </div>

              <div class="col-12">
                <label class="form-label">Evidencias (JPG/PNG/PDF) *</label>
                <label class="dropzone w-100" id="devDrop">
                  <input type="file" id="devEvidence" multiple accept="image/*,application/pdf" class="d-none">
                  <div class="text-muted"><i class="bi bi-cloud-arrow-up me-1"></i> Arrastra o toca para seleccionar archivos</div>
                </label>
                <div id="devFileList" class="mt-2"></div>
              </div>

              <div class="col-12 col-lg-6">
                <label class="form-label">Firma del usuario *</label>
                <div class="sig-wrap">
                  <canvas id="devUserCanvas" class="sig-canvas"></canvas>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">Firma del usuario</small>
                    <button class="btn btn-sm btn-outline-modern btn-outline-secondary lift" type="button" data-clear="#devUserCanvas">
                      <i class="bi bi-eraser"></i> Limpiar
                    </button>
                  </div>
                </div>
              </div>

              <div class="col-12 col-lg-6">
                <label class="form-label">Firma del responsable *</label>
                <div class="sig-wrap">
                  <canvas id="devAdminCanvas" class="sig-canvas"></canvas>
                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">Firma del responsable</small>
                    <button class="btn btn-sm btn-outline-modern btn-outline-secondary lift" type="button" data-clear="#devAdminCanvas">
                      <i class="bi bi-eraser"></i> Limpiar
                    </button>
                  </div>
                </div>
              </div>

              <div class="col-12 text-end">
                <button class="btn btn-pastel-green lift" id="btnDevolucion" type="button" data-loading="false">
                  <span class="btn-text"><i class="bi bi-arrow-repeat me-1"></i> Guardar devolución</span>
                  <span class="btn-spinner d-none spinner-border spinner-border-sm"></span>
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- ===================== FIRMAS + NIP (SOLO GASTO) ===================== --}}
      <div id="signSection" class="card section-card border-0 mb-3">
        <div class="card-body">
          <div class="section-title"><i class="bi bi-pen" style="color:#1f7ae6"></i> Firmas</div>
          <p class="section-sub">Solo para gasto. (En caja se firma dentro de cada apartado).</p>

          {{-- ✅ NIP SIEMPRE OBLIGATORIO (gasto vehiculo/nomina) --}}
          <div class="row g-3 mb-2">
            <div class="col-12 col-lg-6">
              <label class="form-label">NIP *</label>
              <input type="password"
                     name="nip"
                     id="gasto_nip"
                     class="form-control @error('nip') is-invalid @enderror"
                     inputmode="numeric"
                     maxlength="8"
                     placeholder="NIP 4–8 dígitos"
                     value="{{ $v('nip') }}"
                     required>
              @error('nip')<div class="error">{{ $message }}</div>@enderror
              <div class="small text-muted mt-1">Obligatorio para registrar el gasto.</div>
            </div>
          </div>

          <div class="row g-3">
            {{-- ✅ Solo firma del responsable --}}
            <div class="col-12">
              <label class="form-label">Firma del responsable *</label>
              <div class="sig-wrap">
                <canvas id="gastoAdminCanvas" class="sig-canvas"></canvas>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <div class="sig-help"><i class="bi bi-pencil"></i> Tu firma</div>
                  <button class="btn btn-sm btn-outline-modern btn-outline-secondary lift" type="button" data-clear="#gastoAdminCanvas">
                    <i class="bi bi-eraser"></i> Limpiar
                  </button>
                </div>
                <input type="hidden" name="admin_signature" id="admin_signature" value="{{ $v('admin_signature') }}">
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ✅ Evidencia SOLO para gasto de VEHÍCULO (en nómina NO se solicita; opcional/oculta) --}}
      <div id="evidenceSection" class="mb-2">
        <label class="form-label">Evidencia (opcional)</label>
        <div class="dropzone" id="dropzone">
          <input id="fileInput" class="d-none" type="file" name="attachment" accept="*/*">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="text-muted"><i class="bi bi-file-earmark-arrow-up me-1"></i> Toca o arrastra para seleccionar archivo</div>
            <button class="btn btn-sm btn-outline-modern btn-outline-secondary lift" type="button" id="btnPickFile">
              <i class="bi bi-upload"></i> Seleccionar
            </button>
          </div>
          <div class="small text-muted mt-2" id="fileMeta"></div>
        </div>
        @error('attachment')<div class="error" style="margin-top:8px;">{{ $message }}</div>@enderror
      </div>

    </div>

    {{-- Botones: submit solo para gasto --}}
    <div class="d-flex justify-content-end gap-2 p-3">
      <a href="{{ route('expenses.index') }}" class="btn btn-outline-modern btn-outline-secondary lift">
        <i class="bi bi-arrow-left-short"></i> Cancelar
      </a>
      <button type="submit" class="btn btn-pastel-green lift" id="btnSave">
        <span class="btn-text"><i class="bi bi-check2-circle"></i> Guardar gasto</span>
        <span class="btn-spinner d-none spinner-border spinner-border-sm"></span>
      </button>
    </div>
  </form>
</div>

{{-- Toasts --}}
<div class="toast-container position-fixed top-0 end-0 p-3">
  <div id="toastOK" class="toast align-items-center text-bg-success border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body"><i class="bi bi-check-circle me-1"></i> Listo.</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
  <div id="toastERR" class="toast align-items-center text-bg-danger border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body"><i class="bi bi-exclamation-octagon me-1"></i> <span id="toastERRMsg">Error</span></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.2.0/dist/signature_pad.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
(function(){
  const toastOK  = new bootstrap.Toast('#toastOK',{delay:2200});
  const toastERR = new bootstrap.Toast('#toastERR',{delay:3200});
  const ok  = (m)=>{ document.querySelector('#toastOK .toast-body').innerHTML = '<i class="bi bi-check-circle me-1"></i> '+m; toastOK.show(); }
  const err = (m)=>{ document.getElementById('toastERRMsg').textContent = m; toastERR.show(); }

  function setLoadingBtn(btn,on=true){
    btn.disabled = on;
    btn.querySelector('.btn-text')?.classList.toggle('d-none', on);
    btn.querySelector('.btn-spinner')?.classList.toggle('d-none', !on);
  }

  // ===== Helpers quincenas
  function monthName(m){
    return ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'][m];
  }
  function buildQuincenas(year){
    const opts = [];
    for(let m=0; m<12; m++){
      const mn = monthName(m);
      opts.push({ value:`${year}-${String(m+1).padStart(2,'0')}-Q1`, label:`Primera quincena de ${mn} ${year}` });
      opts.push({ value:`${year}-${String(m+1).padStart(2,'0')}-Q2`, label:`Segunda quincena de ${mn} ${year}` });
    }
    return opts;
  }
  function fillPayrollPeriods(){
    const year = new Date().getFullYear();
    const list = buildQuincenas(year);
    const payrollPeriod = document.getElementById('payroll_period');
    payrollPeriod.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = ''; ph.disabled = true; ph.selected = true; ph.textContent = 'Selecciona…';
    payrollPeriod.appendChild(ph);
    list.forEach(o=>{
      const op = document.createElement('option');
      op.value = o.value; op.textContent = o.label;
      payrollPeriod.appendChild(op);
    });
    const oldVal = @json($v('payroll_period'));
    if(oldVal) payrollPeriod.value = oldVal;
  }

  // ===== Pads
  const pads = {};
  function fitCanvas(canvas, pad){
    const ratio = Math.max(window.devicePixelRatio||1,1);
    const rect  = canvas.getBoundingClientRect();
    const fallbackW = canvas.parentElement ? canvas.parentElement.clientWidth : 600;
    const w = rect.width>0 ? rect.width : (fallbackW>0?fallbackW:600);
    const h = rect.height>0 ? rect.height : 190;
    canvas.width = w * ratio; canvas.height = h * ratio;
    const ctx = canvas.getContext('2d');
    ctx.setTransform(ratio,0,0,ratio,0,0);
    pad.clear();
  }
  function initPad(id){
    const c = document.getElementById(id);
    if(!c) return null;
    const p = new SignaturePad(c, { backgroundColor:'#fff', penColor:'#0f172a' });
    pads[id] = p;
    return p;
  }
  function toData(pad){ return (!pad || pad.isEmpty()) ? null : pad.toDataURL('image/png'); }

  // gasto pads (SOLO responsable)
  const gastoAdminPad    = initPad('gastoAdminCanvas');

  // caja pads
  const fondoAdminPad      = initPad('fondoAdminCanvas');
  const entregaReceiverPad = initPad('entregaReceiverCanvas');
  const devUserPad         = initPad('devUserCanvas');
  const devAdminPad        = initPad('devAdminCanvas');

  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('[data-clear]');
    if(!btn) return;
    const id = btn.getAttribute('data-clear').slice(1);
    pads[id] && pads[id].clear();
  });

  window.addEventListener('resize', ()=>{
    Object.entries(pads).forEach(([id,p])=>{
      const c=document.getElementById(id);
      if(c && c.offsetParent !== null) fitCanvas(c,p);
    });
  });
  setTimeout(()=>window.dispatchEvent(new Event('resize')), 50);

  // ===== KIND: gasto / caja
  const kindInput = document.getElementById('entry_kind');
  const kindTabs  = document.querySelectorAll('#kindTabs [data-kind]');
  const gastoSection = document.getElementById('gastoSection');
  const cajaSection  = document.getElementById('cajaSection');
  const signSection  = document.getElementById('signSection');
  const evidenceSection = document.getElementById('evidenceSection');
  const btnSave = document.getElementById('btnSave');

  function setKind(k){
    kindInput.value = k;
    kindTabs.forEach(x => x.classList.toggle('active', x.getAttribute('data-kind') === k));

    const isCaja = (k === 'caja');
    gastoSection.classList.toggle('d-none', isCaja);
    cajaSection.classList.toggle('d-none', !isCaja);
    signSection.classList.toggle('d-none', isCaja);
    evidenceSection.classList.toggle('d-none', isCaja);
    btnSave.classList.toggle('d-none', isCaja);

    document.getElementById('concept').required = !isCaja;
    document.getElementById('expense_date').required = !isCaja;
    document.getElementById('amount').required = !isCaja;

    setTimeout(()=>window.dispatchEvent(new Event('resize')), 60);
  }
  kindTabs.forEach(x => x.addEventListener('click', ()=> setKind(x.getAttribute('data-kind'))));
  setKind(kindInput.value || 'gasto');

  // ===== GASTO TYPE: vehiculo / nomina
  const expenseTypeInput = document.getElementById('expense_type');
  const expenseTypeTabs  = document.querySelectorAll('#expenseTypeTabs [data-type]');

  const vehBox = document.getElementById('vehBox');
  const vehCatBox = document.getElementById('vehCatBox');
  const payrollCatBox = document.getElementById('payrollCatBox');
  const payrollPeriodBox = document.getElementById('payrollPeriodBox');

  const vehicleId = document.getElementById('vehicle_id');
  const vehicleCategory = document.getElementById('vehicle_category');
  const payrollCategory = document.getElementById('payroll_category');
  const payrollPeriod   = document.getElementById('payroll_period');

  function setExpenseType(t){
    expenseTypeInput.value = t;
    expenseTypeTabs.forEach(x => x.classList.toggle('active', x.getAttribute('data-type') === t));

    const isVeh = (t === 'vehiculo');
    const isNom = (t === 'nomina');

    vehBox.classList.toggle('d-none', !isVeh);
    vehCatBox.classList.toggle('d-none', !isVeh);
    payrollCatBox.classList.toggle('d-none', !isNom);
    payrollPeriodBox.classList.toggle('d-none', !isNom);

    if(vehicleId) vehicleId.required = isVeh;
    if(vehicleCategory) vehicleCategory.required = isVeh;

    if(payrollCategory) payrollCategory.required = isNom;
    if(payrollPeriod) payrollPeriod.required = isNom;

    // ✅ NOMINA: NO SOLICITA EVIDENCIA (opcional/oculta)
    evidenceSection.classList.toggle('d-none', isNom);

    if(isNom) fillPayrollPeriods();

    if(isVeh){
      if(payrollCategory) payrollCategory.value = '';
      if(payrollPeriod) payrollPeriod.innerHTML = '';
    } else {
      if(vehicleId) vehicleId.value = '';
      if(vehicleCategory) vehicleCategory.value = '';
    }

    setTimeout(()=>window.dispatchEvent(new Event('resize')), 50);
  }
  expenseTypeTabs.forEach(x => x.addEventListener('click', ()=> setExpenseType(x.getAttribute('data-type'))));
  setExpenseType(expenseTypeInput.value || 'vehiculo');

  // ===== Evidencia gasto (vehiculo)
  const dz = document.getElementById('dropzone');
  const fileInput = document.getElementById('fileInput');
  const meta = document.getElementById('fileMeta');
  const btnPickFile = document.getElementById('btnPickFile');

  btnPickFile?.addEventListener('click', ()=>fileInput.click());
  dz?.addEventListener('click', ()=>fileInput.click());

  function humanSize(bytes){
    if(!bytes) return '';
    const i = Math.floor(Math.log(bytes)/Math.log(1024));
    return (bytes/Math.pow(1024, i)).toFixed(1) + ' ' + ['B','KB','MB','GB','TB'][i];
  }
  function renderFile(file){
    meta.textContent = file ? `${file.name} • ${humanSize(file.size)}` : '';
  }
  fileInput?.addEventListener('change', e=>{
    const f = e.target.files?.[0]; renderFile(f);
  });
  dz?.addEventListener('drop', e=>{
    e.preventDefault(); e.stopPropagation();
    dz.classList.remove('dragover');
    const f = e.dataTransfer?.files?.[0]; if(!f) return;
    const dt = new DataTransfer(); dt.items.add(f); fileInput.files = dt.files;
    renderFile(f);
  });
  ['dragenter','dragover'].forEach(evt=>{
    dz?.addEventListener(evt, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.add('dragover'); });
  });
  ['dragleave','drop'].forEach(evt=>{
    dz?.addEventListener(evt, e=>{ e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); });
  });

  // ===== Submit gasto (NIP SIEMPRE OBLIGATORIO + firma responsable)
  const form = document.getElementById('expenseForm');
  form.addEventListener('submit', (e)=>{
    if(kindInput.value === 'caja'){
      e.preventDefault();
      err('Los movimientos de caja se guardan con sus botones.');
      return;
    }

    // ✅ NIP obligatorio
    const nip = (document.getElementById('gasto_nip')?.value || '').trim();
    if(!/^\d{4,8}$/.test(nip)){
      e.preventDefault();
      err('NIP inválido. (4 a 8 dígitos)');
      return;
    }

    // ✅ Solo firma del responsable (gasto)
    const aSig = toData(gastoAdminPad);
    if(!aSig){ e.preventDefault(); err('Falta la firma del responsable.'); return; }
    document.getElementById('admin_signature').value = aSig;

    setLoadingBtn(btnSave,true);
    ok('Enviando…');
  });

  // ===== Tabs Caja
  const cashTabs = document.querySelectorAll('#cashTabs [data-cash]');
  const cashTabInput = document.getElementById('cash_tab');

  const paneF = document.getElementById('cashPaneFondo');
  const paneE = document.getElementById('cashPaneEntrega');
  const paneD = document.getElementById('cashPaneDevolucion');

  function setCashTab(t){
    cashTabInput.value = t;
    cashTabs.forEach(x => x.classList.toggle('active', x.getAttribute('data-cash') === t));
    paneF.classList.toggle('d-none', t !== 'fondo');
    paneE.classList.toggle('d-none', t !== 'entrega');
    paneD.classList.toggle('d-none', t !== 'devolucion');
    setTimeout(()=>window.dispatchEvent(new Event('resize')), 60);
  }
  cashTabs.forEach(x => x.addEventListener('click', ()=> setCashTab(x.getAttribute('data-cash'))));
  setCashTab(cashTabInput.value || 'fondo');

  // ===== Entrega self switch
  const entregaSelf = document.getElementById('entrega_self_receive');
  const entregaPickBox = document.getElementById('entregaReceiverPickBox');
  const entregaReceiverSel = document.getElementById('entrega_receiver_id');
  function syncEntregaSelf(){
    const on = !!entregaSelf.checked;
    entregaPickBox.classList.toggle('d-none', on);
    if(on) entregaReceiverSel.value = '';
  }
  entregaSelf.addEventListener('change', syncEntregaSelf);
  syncEntregaSelf();

  // Toggle Entrega Directo/QR
  $('#entregaModeDirect').on('change', ()=>{
    $('#entregaDirectBox').removeClass('d-none'); $('#entregaQrBox').addClass('d-none');
    setTimeout(()=>window.dispatchEvent(new Event('resize')), 50);
  });
  $('#entregaModeQr').on('change', ()=>{
    $('#entregaDirectBox').addClass('d-none'); $('#entregaQrBox').removeClass('d-none');
    setTimeout(()=>window.dispatchEvent(new Event('resize')), 50);
  });

  // ===== ENTREGA evidencias
  $('#entregaDrop').on('click', ()=>$('#entregaEvidence').trigger('click'));
  $('#entregaEvidence').on('change', function(){
    const $list=$('#entregaFileList').empty();
    [...this.files].forEach(f=> $list.append(`<span class="file-pill"><i class="bi bi-paperclip"></i> ${f.name}</span>`));
  });

  // ===== AJAX: FONDO
  $('#btnFondo').on('click', function(){
    const btn=this;

    const amount = parseFloat($('#fondo_amount').val()||'0');
    if(isNaN(amount)||amount<0.01) return err('Monto inválido.');

    const sig = toData(fondoAdminPad);
    if(!sig) return err('Falta la firma.');

    const fd = new FormData();
    fd.append('manager_id', $('#fondo_manager_id').val());
    fd.append('boss_id', $('#fondo_boss_id').val());
    fd.append('performed_at', $('#fondo_performed_at').val());
    fd.append('amount', $('#fondo_amount').val());
    fd.append('purpose', ($('#fondo_purpose').val()||'').trim());
    fd.append('manager_signature', sig);

    setLoadingBtn(btn,true);
    $.ajax({
      url: "{{ route('expenses.movement.allocation.store', [], false) }}",
      method:'POST',
      data:fd, processData:false, contentType:false,
      headers:{'X-CSRF-TOKEN': $('input[name=_token]').val(), 'Accept':'application/json'},
    }).done(r=>{
      ok(`Fondo registrado (#${r.id})`);
      window.location = "{{ route('expenses.index', [], false) }}";
    }).fail(x=>{
      err(x.responseJSON?.message || 'No se pudo guardar el fondo');
    }).always(()=>setLoadingBtn(btn,false));
  });

  // ===== AJAX: ENTREGA (validaciones comunes + evidencia obligatoria)
  function entregaCommonValidate(){
    const amount=parseFloat($('#entrega_amount').val()||'0');
    if(isNaN(amount)||amount<0.01){ err('Monto inválido.'); return null; }

    const purpose=($('#entrega_purpose').val()||'').trim();
    if(purpose.length<3){ err('Motivo inválido.'); return null; }

    if(!entregaSelf.checked){
      if(!$('#entrega_receiver_id').val()){ err('Selecciona el usuario que recibe.'); return null; }
    }

    const files = document.getElementById('entregaEvidence')?.files;
    if(!files || files.length===0){ err('Agrega al menos una evidencia (PDF/imagen).'); return null; }

    return {amount,purpose,files};
  }

  // ===== AJAX: ENTREGA DIRECTO
  $('#btnEntregaDirect').on('click', function(){
    const btn=this;
    const base=entregaCommonValidate(); if(!base) return;

    const nip=($('#entrega_nip').val()||'').trim();
    if(!/^\d{4,8}$/.test(nip)) return err('NIP inválido.');

    const sig=toData(entregaReceiverPad);
    if(!sig) return err('Falta la firma del receptor.');

    const fd=new FormData();
    fd.append('manager_id', $('#entrega_manager_id').val());
    fd.append('receiver_id', entregaSelf.checked ? '' : $('#entrega_receiver_id').val());
    fd.append('self_receive', entregaSelf.checked ? '1' : '0');
    fd.append('performed_at', $('#entrega_performed_at').val());
    fd.append('amount', $('#entrega_amount').val());
    fd.append('purpose', $('#entrega_purpose').val());
    fd.append('nip', nip);
    fd.append('counterparty_signature', sig);
    for(const f of base.files){ fd.append('evidence[]', f); }

    setLoadingBtn(btn,true);
    $.ajax({
      url:"{{ route('expenses.movement.disbursement.direct', [], false) }}",
      method:'POST',
      data:fd, processData:false, contentType:false,
      headers:{'X-CSRF-TOKEN': $('input[name=_token]').val(), 'Accept':'application/json'},
    }).done(r=>{
      ok(`Entrega guardada (#${r.id})`);
      window.location = "{{ route('expenses.index', [], false) }}";
    }).fail(x=>{
      err(x.responseJSON?.message || 'No se pudo guardar la entrega');
    }).always(()=>setLoadingBtn(btn,false));
  });

  // ===== AJAX: ENTREGA QR
  let pollTimer=null, activeToken=null, lastQrUrl='';
  function stopPolling(){ if(pollTimer){ clearInterval(pollTimer); pollTimer=null; } }

  $('#btnEntregaStartQr').on('click', function(){
    const btn=this;

    const base=entregaCommonValidate(); if(!base) return;

    const nip=($('#entrega_qr_nip').val()||'').trim();
    if(!/^\d{4,8}$/.test(nip)) return err('NIP inválido.');

    const fd=new FormData();
    fd.append('manager_id', $('#entrega_manager_id').val());
    fd.append('receiver_id', entregaSelf.checked ? '' : $('#entrega_receiver_id').val());
    fd.append('self_receive', entregaSelf.checked ? '1' : '0');
    fd.append('performed_at', $('#entrega_performed_at').val());
    fd.append('amount', $('#entrega_amount').val());
    fd.append('purpose', $('#entrega_purpose').val());
    fd.append('nip', nip);
    for(const f of base.files){ fd.append('evidence[]', f); }

    setLoadingBtn(btn,true);
    $.ajax({
      url:"{{ route('expenses.movement.disbursement.qr.start', [], false) }}",
      method:'POST',
      data:fd, processData:false, contentType:false,
      headers:{'X-CSRF-TOKEN': $('input[name=_token]').val(), 'Accept':'application/json'},
    }).done(r=>{
      $('#entregaQrPanel').removeClass('d-none');
      $('#entregaQrSuccess').addClass('d-none');
      $('#entrega_qrcode').empty();

      new QRCode(document.getElementById("entrega_qrcode"), { text:r.url, width:230, height:230 });

      $('#entregaQrStatus').removeClass('bg-danger bg-success').addClass('bg-info')
        .html('<span class="spinner-border spinner-border-sm me-1"></span> Esperando firma del usuario…');

      activeToken=r.token; lastQrUrl=r.url;

      stopPolling();
      pollTimer=setInterval(()=>{
        $.getJSON("{{ url('', [], false) }}/expenses/movements/qr/status/"+activeToken, s=>{
          if(s.expired){
            $('#entregaQrStatus').removeClass('bg-info bg-success').addClass('bg-danger').text('QR expirado');
            stopPolling();
          } else if(s.acknowledged){
            stopPolling();
            $('#entregaQrPanel').addClass('d-none');
            $('#entregaQrSuccess').removeClass('d-none');
            ok('Autorizado por el usuario');
          }
        });
      }, 2200);

      ok('QR generado');
    }).fail(x=>{
      err(x.responseJSON?.message || 'No se pudo generar el QR');
    }).always(()=>setLoadingBtn(btn,false));
  });

  $('#btnEntregaCopyLink').on('click', async ()=>{
    try{ await navigator.clipboard.writeText(lastQrUrl); ok('Link copiado'); }
    catch(e){ err('No se pudo copiar el link'); }
  });

  // ===== DEVOLUCIÓN evidencias
  $('#devDrop').on('click', ()=>$('#devEvidence').trigger('click'));
  $('#devEvidence').on('change', function(){
    const $list=$('#devFileList').empty();
    [...this.files].forEach(f=> $list.append(`<span class="file-pill"><i class="bi bi-file-earmark-arrow-up"></i> ${f.name}</span>`));
  });

  // ===== AJAX: DEVOLUCIÓN
  $('#btnDevolucion').on('click', function(){
    const btn=this;

    const userId=$('#dev_user_id').val();
    if(!userId) return err('Selecciona el usuario que devuelve.');

    const amount=parseFloat($('#dev_amount').val()||'0');
    if(isNaN(amount)||amount<0.01) return err('Monto inválido.');

    const purpose=($('#dev_purpose').val()||'').trim();
    if(purpose.length<3) return err('Motivo inválido.');

    const uSig=toData(devUserPad);
    const aSig=toData(devAdminPad);
    if(!uSig) return err('Falta la firma del usuario.');
    if(!aSig) return err('Falta la firma del responsable.');

    const files = document.getElementById('devEvidence').files;
    if(!files || files.length===0) return err('Agrega al menos una evidencia.');

    const fd=new FormData();
    fd.append('manager_id', $('#dev_manager_id').val());
    fd.append('counterparty_id', userId);
    fd.append('performed_at', $('#dev_performed_at').val());
    fd.append('amount', $('#dev_amount').val());
    fd.append('purpose', $('#dev_purpose').val());
    fd.append('counterparty_signature', uSig);
    fd.append('manager_signature', aSig);
    for(const f of files){ fd.append('evidence[]', f); }

    setLoadingBtn(btn,true);
    $.ajax({
      url:"{{ route('expenses.movement.return.store', [], false) }}",
      method:'POST',
      data:fd, processData:false, contentType:false,
      headers:{'X-CSRF-TOKEN': $('input[name=_token]').val(), 'Accept':'application/json'},
    }).done(r=>{
      ok(`Devolución guardada (#${r.id})`);
      window.location = "{{ route('expenses.index', [], false) }}";
    }).fail(x=>{
      err(x.responseJSON?.message || 'No se pudo guardar la devolución');
    }).always(()=>setLoadingBtn(btn,false));
  });

})();
</script>

@endsection