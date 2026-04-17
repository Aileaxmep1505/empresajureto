@extends('layouts.app')

@section('title', 'Detalle de recepción')
@section('titulo', 'Detalle de recepción')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<style>
.reception-screen {
  --bg: #f9fafb;
  --card: #ffffff;
  --ink: #333333;
  --muted: #888888;
  --line: #ebebeb;
  --blue: #007aff;
  --blue-soft: #e6f0ff;
  --success: #15803d;
  --success-soft: #e6ffe6;
  --danger: #ff4a4a;
  --danger-soft: #ffebeb;
  --warning: #d97706;
  --warning-soft: #fff7ed;

  font-family: 'Quicksand', system-ui, -apple-system, sans-serif;
  color: var(--ink);
  -webkit-font-smoothing: antialiased;

  width: calc(100% + 36px);
  margin: -18px;
  min-height: calc(100vh - var(--topbar-h, 58px));
  background: var(--bg);
  padding: 24px 24px 80px;
}

.reception-wrapper {
  max-width: 1200px;
  margin: 0 auto;
}

.reception-screen,
.reception-screen * {
  box-sizing: border-box;
}

.reception-screen h1,
.reception-screen h2,
.reception-screen h3 {
  color: #111111;
  margin: 0;
}

.reception-screen .reception-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
  margin-bottom: 40px;
}

.reception-screen .reception-header-left {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.reception-screen .title-row {
  display: flex;
  align-items: center;
  gap: 16px;
}

.reception-screen .back-btn {
  color: #333333;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
}

.reception-screen .back-btn:hover {
  color: var(--blue);
  transform: translateX(-2px);
}

.reception-screen .page-title {
  font-size: 32px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.reception-screen .meta-info {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  margin-left: 40px;
}

.reception-screen .date-text {
  color: var(--muted);
  font-size: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.reception-screen .reception-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.reception-screen .btn {
  height: 44px;
  padding: 0 24px;
  border-radius: 999px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  text-decoration: none;
  border: none;
}

.reception-screen .btn:active {
  transform: scale(0.98);
}

.reception-screen .btn-primary {
  background: var(--blue);
  color: #ffffff;
}

.reception-screen .btn-primary:hover {
  background: #006ae6;
}

.reception-screen .btn-outline {
  background: transparent;
  border: 1.5px solid var(--blue);
  color: var(--blue);
}

.reception-screen .btn-outline:hover {
  background: var(--blue-soft);
}

.reception-screen .card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  margin-bottom: 24px;
  transition: all 0.3s ease;
}

.reception-screen .card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.04);
}

.reception-screen .card-body {
  padding: 32px;
}

.reception-screen .people-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 32px;
}

.reception-screen .person-item {
  display: flex;
  align-items: center;
  gap: 16px;
}

.reception-screen .person-icon {
  color: var(--muted);
  display: flex;
  align-items: center;
  justify-content: center;
}

.reception-screen .person-info small {
  display: block;
  font-size: 12px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 4px;
}

.reception-screen .person-info strong {
  display: block;
  font-size: 18px;
  color: #111111;
}

.reception-screen .section-title {
  font-size: 18px;
  font-weight: 700;
  margin-bottom: 24px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.reception-screen .section-title svg {
  color: var(--muted);
}

.reception-screen .table-wrapper {
  border: 1px solid var(--line);
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.reception-screen .table-clean {
  width: 100%;
  border-collapse: collapse;
}

.reception-screen .table-clean th {
  background: var(--bg);
  color: var(--muted);
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  padding: 16px 24px;
  border-bottom: 1px solid var(--line);
}

.reception-screen .table-clean td {
  padding: 16px 24px;
  border-bottom: 1px solid var(--line);
  font-size: 14px;
  font-weight: 600;
  color: var(--ink);
}

.reception-screen .table-clean tr:last-child td {
  border-bottom: none;
}

.reception-screen .badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
}

.reception-screen .badge-info { background: var(--blue-soft); color: var(--blue); }
.reception-screen .badge-success { background: var(--success-soft); color: var(--success); }
.reception-screen .badge-danger { background: var(--danger-soft); color: var(--danger); }
.reception-screen .badge-warning { background: var(--warning-soft); color: var(--warning); }

.reception-screen .qr-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 24px 0;
}

.reception-screen .qr-box {
  width: 220px;
  height: 220px;
  padding: 16px;
  border-radius: 16px;
  border: 1px solid var(--line);
  margin-bottom: 24px;
  background: #fff;
}

.reception-screen .qr-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.reception-screen .qr-instruction {
  color: var(--muted);
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 24px;
}

.reception-screen .signature-status-group {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
}

.reception-screen .completed-area { display: none; text-align: center; }
.reception-screen .completed-area.is-active { display: block; }
.reception-screen .success-icon-large { color: var(--success); margin-bottom: 16px; }
.reception-screen .signatures-grid { display: flex; gap: 32px; justify-content: center; margin-top: 32px; }

.reception-screen .signature-preview-box {
  width: 200px;
  height: 100px;
  border: 1px solid var(--line);
  border-radius: 12px;
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px;
  background: #fff;
}

.reception-screen .signature-preview-box img {
  max-width: 100%;
  max-height: 100%;
  mix-blend-mode: multiply;
}

.reception-screen .hidden { display: none !important; }

.labels-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.6);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  padding: 24px;
}

.labels-modal-overlay.is-active {
  opacity: 1;
  pointer-events: auto;
}

.labels-modal-content {
  background: #f8fafc;
  width: 95%;
  max-width: 1000px;
  max-height: 90vh;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
  overflow: hidden;
}

.labels-modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--line);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fff;
}

.labels-modal-header h2 {
  font-size: 20px;
  font-weight: 700;
  color: var(--ink);
  margin: 0;
}

.labels-modal-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-download-labels {
  background: #1e293b;
  color: #fff;
  border: none;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: transform 0.1s;
  text-decoration: none;
}

.btn-download-labels:active {
  transform: scale(0.96);
}

.btn-close-modal {
  background: transparent;
  border: none;
  color: var(--muted);
  cursor: pointer;
  padding: 8px;
  border-radius: 8px;
}

.btn-close-modal:hover {
  background: #f1f5f9;
  color: var(--ink);
}

.labels-modal-body {
  padding: 24px;
  overflow-y: auto;
  flex: 1;
}

.labels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.label-card {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

.label-content {
  display: flex;
  justify-content: space-between;
  align-items: stretch;
  height: 100%;
  gap: 12px;
}

.label-info {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  flex: 1;
}

.lbl-title {
  font-size: 11px;
  color: var(--muted);
  margin-bottom: 2px;
  font-weight: 600;
  text-transform: uppercase;
}

.lbl-value {
  font-size: 14px;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.2;
}

.desc-block {
  margin: 12px 0;
}

.desc-block .lbl-value {
  font-size: 15px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.meta-row {
  display: flex;
  gap: 24px;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--line);
}

.folio-footer {
  font-size: 10px;
  color: var(--muted);
  font-weight: 500;
}

.qr-section {
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
}

.qr-section img {
  width: 85px;
  height: 85px;
  object-fit: contain;
}

@media (max-width: 991.98px) {
  .reception-screen { width: calc(100% + 30px); margin: -15px; padding: 20px 20px 72px; }
}

@media (max-width: 768px) {
  .reception-screen { width: calc(100% + 24px); margin: -12px; padding: 18px 16px 64px; }
  .reception-screen .people-grid { grid-template-columns: 1fr; }
  .reception-screen .reception-header { flex-direction: column; align-items: flex-start; }
  .reception-screen .meta-info { margin-left: 0; }
  .reception-screen .reception-actions { width: 100%; flex-direction: column; }
  .reception-screen .btn { width: 100%; }

  .labels-modal-overlay {
    padding: 16px;
  }

  .labels-modal-content {
    width: 100%;
    max-height: 92vh;
  }

  .labels-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="reception-screen">
  <div class="reception-wrapper">

    <div class="reception-header">
      <div class="reception-header-left">
        <div class="title-row">
          <a href="{{ url()->previous() }}" class="back-btn" aria-label="Volver">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
          </a>
          <h1 class="page-title">{{ $reception->folio }}</h1>
        </div>

        <div class="meta-info">
          <div id="globalStatusBadge" class="badge {{ $reception->status === 'firmado' ? 'badge-success' : 'badge-info' }}">
            @if($reception->status === 'firmado')
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
              <span>Firmado</span>
            @else
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"></path></svg>
              <span>Pendiente</span>
            @endif
          </div>

          <div class="date-text">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>{{ optional($reception->reception_date)->translatedFormat('j M Y, H:i') }}</span>
          </div>
        </div>
      </div>

      <div class="reception-actions">
        <button type="button" class="btn btn-outline" onclick="openLabelsModal()">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
          </svg>
          Etiquetas
        </button>

        <a href="{{ route('admin.wms.receptions.pdf', $reception->id) }}" class="btn btn-primary">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
          </svg>
          Descargar PDF
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="people-grid">
          <div class="person-item">
            <div class="person-icon">
              <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <div class="person-info">
              <small>Entrega</small>
              <strong>{{ $reception->deliverer_name }}</strong>
            </div>
          </div>

          <div class="person-item">
            <div class="person-icon">
              <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 10-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="person-info">
              <small>Recibe</small>
              <strong>{{ $reception->receiver_name }}</strong>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="section-title">
          <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"></path></svg>
          Productos ({{ $reception->lines->count() }})
        </h2>

        <div class="table-wrapper">
          <table class="table-clean">
            <thead>
              <tr>
                <th style="width:25%;">SKU</th>
                <th>Descripción</th>
                <th style="width:15%;">Lote</th>
                <th style="width:10%; text-align:center;">Cant.</th>
                <th style="width:15%;">Estado</th>
              </tr>
            </thead>
            <tbody>
              @forelse($reception->lines as $product)
                @php
                  $conditionClass = match($product->condition ?? '') {
                    'bueno' => 'badge-success',
                    'dañado' => 'badge-danger',
                    'parcial' => 'badge-warning',
                    default => 'badge-info',
                  };
                @endphp
                <tr>
                  <td style="color: var(--muted); font-family: monospace;">{{ $product->sku ?: '—' }}</td>
                  <td>{{ $product->description ?: ($product->name ?: '—') }}</td>
                  <td>{{ $product->lot ?: '-' }}</td>
                  <td style="text-align:center;">{{ $product->quantity }}</td>
                  <td>
                    <span class="badge {{ $conditionClass }}">
                      {{ ucfirst($product->condition ?: 'Revisión') }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" style="text-align: center; padding: 40px; color: var(--muted);">No hay productos registrados.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    @if(!empty($reception->observations))
      <div class="card">
        <div class="card-body">
          <h2 class="section-title">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h6"></path><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h8a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V5a2 2 0 012-2z"></path></svg>
            Observaciones
          </h2>
          <p style="margin: 0; color: var(--muted); line-height: 1.6;">{{ $reception->observations }}</p>
        </div>
      </div>
    @endif

    <div class="card">
      <div class="card-body">
        <h2 class="section-title" style="justify-content: center; margin-bottom: 8px;">Firma Digital</h2>

        <div id="qrPendingArea" class="qr-container {{ (!empty($reception->delivered_signature) && !empty($reception->received_signature)) ? 'hidden' : '' }}">
          <div class="qr-box">
            <img src="{{ $qrSvg }}" alt="Código QR para firmar">
          </div>
          <p class="qr-instruction">Escanea para firmar desde dispositivo móvil</p>

          <div class="signature-status-group">
            <div class="badge {{ !empty($reception->delivered_signature) ? 'badge-success' : 'badge-info' }}" id="deliveredStatusBadge">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                @if(!empty($reception->delivered_signature))
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                @else
                  <circle cx="12" cy="12" r="9"></circle>
                @endif
              </svg>
              <span>{{ !empty($reception->delivered_signature) ? 'Entrega Firmada' : 'Entrega Pendiente' }}</span>
            </div>

            <div class="badge {{ !empty($reception->received_signature) ? 'badge-success' : 'badge-info' }}" id="receivedStatusBadge">
              <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                @if(!empty($reception->received_signature))
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                @else
                  <circle cx="12" cy="12" r="9"></circle>
                @endif
              </svg>
              <span>{{ !empty($reception->received_signature) ? 'Recepción Firmada' : 'Recepción Pendiente' }}</span>
            </div>
          </div>
        </div>

        <div id="qrCompletedArea" class="completed-area {{ (!empty($reception->delivered_signature) && !empty($reception->received_signature)) ? 'is-active' : '' }}">
          <div class="success-icon-large">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
          </div>
          <h3>Documento Validado</h3>
          <p style="color: var(--muted); font-size: 14px;">Las firmas se han registrado exitosamente.</p>

          <div class="signatures-grid">
            <div>
              <small style="font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase;">Firma de Entrega</small>
              <div class="signature-preview-box" id="completedDelivererPreview">
                @if(!empty($reception->delivered_signature))
                  <img src="{{ $reception->delivered_signature }}" alt="Firma entrega">
                @endif
              </div>
            </div>
            <div>
              <small style="font-size:12px; font-weight:700; color:var(--muted); text-transform:uppercase;">Firma de Recepción</small>
              <div class="signature-preview-box" id="completedReceiverPreview">
                @if(!empty($reception->received_signature))
                  <img src="{{ $reception->received_signature }}" alt="Firma recibe">
                @endif
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <div id="labelsModal" class="labels-modal-overlay">
    <div class="labels-modal-content">
      <div class="labels-modal-header">
        <h2>Etiquetas de Productos</h2>
        <div class="labels-modal-actions">
<a href="{{ route('admin.wms.receptions.labels', $reception->id) }}" class="btn-download-labels">
  <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
  </svg>
  Descargar etiquetas
</a>

          <button type="button" class="btn-close-modal" onclick="closeLabelsModal()" aria-label="Cerrar">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
      </div>

      <div class="labels-modal-body">
        <div class="labels-grid">
          @foreach($reception->lines as $line)
            @php
              $qrText = implode(' | ', array_filter([
                'Folio: '.$reception->folio,
                'SKU: '.($line->sku ?: 'N/A'),
                'Nombre: '.($line->name ?: 'N/A'),
                'Cantidad: '.$line->quantity,
                'Lote: '.($line->lot ?: 'N/A'),
              ]));
              $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(160)->margin(0)->generate($qrText);
              $svgBase64 = 'data:image/svg+xml;base64,' . base64_encode($svg);
            @endphp

            <div class="label-card">
              <div class="label-content">
                <div class="label-info">
                  <div>
                    <div class="lbl-title">SKU</div>
                    <div class="lbl-value">{{ $line->sku ?: 'N/A' }}</div>
                  </div>

                  <div class="desc-block">
                    <div class="lbl-title">Descripción</div>
                    <div class="lbl-value">{{ $line->name ?: 'Sin descripción' }}</div>
                  </div>

                  <div class="meta-row">
                    <div>
                      <div class="lbl-title">Cant.</div>
                      <div class="lbl-value">{{ $line->quantity }}</div>
                    </div>
                    <div>
                      <div class="lbl-title">Lote</div>
                      <div class="lbl-value">{{ $line->lot ?: 'N/A' }}</div>
                    </div>
                  </div>

                  <div class="folio-footer">Folio: {{ $reception->folio }}</div>
                </div>

                <div class="qr-section">
                  <img src="{{ $svgBase64 }}" alt="QR Code">
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

</div>

<script>
function openLabelsModal() {
  document.getElementById('labelsModal').classList.add('is-active');
  document.body.style.overflow = 'hidden';
}

function closeLabelsModal() {
  document.getElementById('labelsModal').classList.remove('is-active');
  document.body.style.overflow = '';
}

document.getElementById('labelsModal').addEventListener('click', function(e) {
  if (e.target === this) closeLabelsModal();
});

(() => {
  const receptionId = @json($reception->id);

  const globalStatusBadge = document.getElementById('globalStatusBadge');
  const deliveredStatusBadge = document.getElementById('deliveredStatusBadge');
  const receivedStatusBadge = document.getElementById('receivedStatusBadge');

  const qrPendingArea = document.getElementById('qrPendingArea');
  const qrCompletedArea = document.getElementById('qrCompletedArea');
  const completedDelivererPreview = document.getElementById('completedDelivererPreview');
  const completedReceiverPreview = document.getElementById('completedReceiverPreview');

  function renderBadge(el, done, textDone, textPending) {
    el.className = 'badge ' + (done ? 'badge-success' : 'badge-info');
    el.innerHTML = `
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
        ${done
          ? '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>'
          : '<circle cx="12" cy="12" r="9"></circle>'
        }
      </svg>
      <span>${done ? textDone : textPending}</span>
    `;
  }

  function renderGlobalStatus(signed) {
    globalStatusBadge.className = 'badge ' + (signed ? 'badge-success' : 'badge-info');
    globalStatusBadge.innerHTML = `
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
        ${signed
          ? '<path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>'
          : '<circle cx="12" cy="12" r="9"></circle><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"></path>'
        }
      </svg>
      <span>${signed ? 'Firmado' : 'Pendiente'}</span>
    `;
  }

  function toggleCompletedState(deliveredSrc, receivedSrc) {
    const signed = !!deliveredSrc && !!receivedSrc;

    if (signed) {
      qrPendingArea.classList.add('hidden');
      qrCompletedArea.classList.add('is-active');

      if (deliveredSrc) completedDelivererPreview.innerHTML = `<img src="${deliveredSrc}" alt="Firma entrega">`;
      if (receivedSrc) completedReceiverPreview.innerHTML = `<img src="${receivedSrc}" alt="Firma recibe">`;
    }
  }

  async function refreshStatus() {
    try {
      const res = await fetch(`{{ url('/admin/wms/receptions') }}/${receptionId}/signature-status`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!res.ok) return;

      const json = await res.json();
      const delivered = !!json.delivered_signature;
      const received = !!json.received_signature;
      const signed = delivered && received;

      renderBadge(deliveredStatusBadge, delivered, 'Entrega Firmada', 'Entrega Pendiente');
      renderBadge(receivedStatusBadge, received, 'Recepción Firmada', 'Recepción Pendiente');
      renderGlobalStatus(signed);
      toggleCompletedState(json.delivered_signature, json.received_signature);

      if (signed && window.statusInterval) {
        clearInterval(window.statusInterval);
      }
    } catch (e) { /* silent fail */ }
  }

  if (!qrCompletedArea.classList.contains('is-active')) {
    window.statusInterval = setInterval(refreshStatus, 3000);
  }
})();
</script>
@endsection