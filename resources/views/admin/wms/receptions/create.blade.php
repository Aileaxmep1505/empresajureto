@extends('layouts.app')

@section('title', 'Nueva recepción')
@section('titulo', 'Nueva recepción')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<style>
:root {
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
  --warning: #b45309;
  --warning-soft: #fff7e6;
}

* { box-sizing: border-box; }

body {
  background: var(--bg);
  font-family: 'Quicksand', system-ui, -apple-system, sans-serif;
  color: var(--ink);
}

.reception-shell {
  max-width: 1100px;
  margin: 0 auto;
  padding: 32px 18px 64px;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 18px;
  margin-bottom: 26px;
  flex-wrap: wrap;
}

.page-head h1 {
  margin: 0;
  font-size: 30px;
  line-height: 1.1;
  color: #111111;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.page-head p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 14px;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: var(--card);
  border: 1px solid var(--line);
  color: #111111;
  padding: 10px 16px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.badge span { color: var(--blue); }

.card {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
  margin-bottom: 24px;
  transition: transform .2s ease, box-shadow .2s ease;
}

.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.03);
}

.card-body { padding: 24px; }

.card-title {
  margin: 0 0 20px;
  color: #111111;
  font-size: 18px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 8px;
}

.grid-2 {
  display: grid;
  grid-template-columns: repeat(2, minmax(0,1fr));
  gap: 18px;
}

.form-group { margin-bottom: 18px; }

.form-label {
  display: block;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #111111;
}

.form-input,
.form-textarea,
.form-select {
  width: 100%;
  min-height: 46px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  padding: 11px 14px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 600;
  color: var(--ink);
  outline: none;
  transition: all 0.2s ease;
}

.form-textarea {
  min-height: 110px;
  resize: vertical;
}

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px var(--blue-soft);
}

.search-select { position: relative; }

.search-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 14px;
  box-shadow: 0 18px 30px rgba(0,0,0,.06);
  z-index: 50;
  max-height: 300px;
  overflow: auto;
  opacity: 0;
  visibility: hidden;
  transform: translateY(-8px) scale(0.98);
  transition: all .22s ease;
}

.search-dropdown.is-open {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
}

.search-item {
  padding: 14px 18px;
  border-bottom: 1px solid #f4f4f4;
  cursor: pointer;
  transition: .15s ease;
}

.search-item:last-child { border-bottom: none; }
.search-item:hover { background: #fafcff; }

.search-item strong {
  display: block;
  color: #111111;
  font-size: 14px;
  margin-bottom: 4px;
}

.search-item span {
  display: block;
  color: var(--muted);
  font-size: 12px;
}

.product-search-wrapper {
  position: relative;
  margin-bottom: 24px;
}

.product-search-input-container {
  display: flex;
  align-items: center;
  gap: 12px;
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 12px 16px;
  background: #fff;
  transition: all 0.2s ease;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.product-search-input-container:focus-within {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px var(--blue-soft);
}

.product-search-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 15px;
  font-weight: 600;
  color: #111111;
  outline: none;
}

.product-search-input::placeholder { color: var(--muted); }

.product-dropdown-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 18px;
  border-bottom: 1px solid var(--line);
  cursor: pointer;
}

.product-dropdown-item:hover { background: #fafcff; }

.product-icon-box {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: #f7f7f8;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  flex-shrink: 0;
}

.product-info-flex { flex: 1; }

.product-stock-badge {
  font-size: 13px;
  font-weight: 700;
  color: var(--success);
  background: var(--success-soft);
  padding: 7px 10px;
  border-radius: 999px;
}

.dropdown-footer {
  padding: 10px 18px;
  font-size: 12px;
  color: var(--muted);
  display: flex;
  justify-content: space-between;
  background: #fbfbfb;
  border-radius: 0 0 14px 14px;
}

.selected-products-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.product-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 14px 16px;
  border: 1px solid var(--line);
  border-radius: 16px;
  background: #fff;
  transition: all .2s ease;
  animation: slideInRow .22s ease forwards;
  box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

.product-row:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 18px rgba(0,0,0,0.03);
}

.lote-input {
  width: 130px;
  height: 38px;
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 0 10px;
  font-size: 13px;
  font-family: inherit;
  font-weight: 600;
  outline: none;
  transition: .2s ease;
  background: #fff;
}

.lote-input:focus {
  border-color: var(--blue);
  box-shadow: 0 0 0 3px var(--blue-soft);
}

.status-select {
  height: 38px;
  padding: 0 30px 0 12px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 700;
  border: none;
  outline: none;
  cursor: pointer;
  appearance: none;
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 14px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23888888'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
}

.status-select[data-val="bueno"] {
  background-color: var(--success-soft);
  color: var(--success);
}

.status-select[data-val="dañado"] {
  background-color: var(--danger-soft);
  color: var(--danger);
}

.status-select[data-val="revision"] {
  background-color: var(--warning-soft);
  color: var(--warning);
}

.qty-control {
  display: flex;
  align-items: center;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 10px;
  overflow: hidden;
  height: 38px;
}

.qty-btn {
  width: 34px;
  height: 100%;
  border: none;
  background: transparent;
  color: #111111;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: .15s ease;
}

.qty-btn:hover { background: #f7f7f8; }

.qty-input {
  width: 44px;
  height: 100%;
  border: none;
  border-left: 1px solid var(--line);
  border-right: 1px solid var(--line);
  text-align: center;
  font-weight: 700;
  font-size: 14px;
  background: #fff;
  -moz-appearance: textfield;
  outline: none;
}

.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.btn-remove-row {
  color: var(--muted);
  border: none;
  background: transparent;
  cursor: pointer;
  padding: 8px;
  border-radius: 10px;
  transition: .2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-remove-row:hover {
  background: var(--danger-soft);
  color: var(--danger);
}

.list-footer {
  display: flex;
  justify-content: space-between;
  padding-top: 16px;
  margin-top: 16px;
  border-top: 1px dashed var(--line);
  font-size: 14px;
  color: var(--muted);
}

.list-footer strong {
  color: #111111;
  font-weight: 700;
}

@keyframes slideInRow {
  0% { opacity: 0; transform: translateY(-10px); }
  100% { opacity: 1; transform: translateY(0); }
}

.btn-primary,
.btn-ghost,
.btn-outline {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 44px;
  padding: 0 20px;
  border-radius: 8px;
  font-family: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: all .18s ease;
  text-decoration: none;
}

.btn-primary {
  background: var(--blue);
  color: #fff;
  border: 0;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 18px rgba(0,122,255,0.18);
}

.btn-primary:active { transform: scale(0.98); }

.btn-outline {
  background: #fff;
  border: 1px solid var(--blue);
  color: var(--blue);
}

.btn-outline:hover {
  background: var(--blue-soft);
  transform: translateY(-1px);
}

.btn-outline:active { transform: scale(0.98); }

.btn-ghost {
  background: transparent;
  border: 0;
  color: #555555;
}

.btn-ghost:hover {
  background: #f9fafb;
  transform: translateY(-1px);
}

.btn-ghost:active { transform: scale(0.98); }

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(17,17,17,.22);
  backdrop-filter: blur(6px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  z-index: 100;
  opacity: 0;
  visibility: hidden;
  transition: all .25s ease;
}

.modal-backdrop.is-open {
  opacity: 1;
  visibility: visible;
}

.modal {
  width: 100%;
  max-width: 600px;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 16px;
  box-shadow: 0 18px 40px rgba(0,0,0,0.08);
  transform: translateY(16px) scale(.98);
  transition: transform .25s ease;
}

.modal-backdrop.is-open .modal {
  transform: translateY(0) scale(1);
}

.modal-head,
.modal-body,
.modal-foot {
  padding: 24px;
}

.modal-head {
  border-bottom: 1px solid var(--line);
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.modal-head h3 {
  margin: 0;
  font-size: 18px;
  color: #111111;
}

.modal-head p {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 13px;
}

.modal-close {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  border: 0;
  background: #f7f7f8;
  cursor: pointer;
  transition: .2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover { background: #efefef; }

.modal-foot {
  border-top: 1px solid var(--line);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.toast-container {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.toast {
  min-width: 280px;
  background: rgba(255, 255, 255, 0.98);
  border: 1px solid var(--line);
  border-radius: 14px;
  padding: 14px 16px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.06);
  display: flex;
  align-items: center;
  gap: 12px;
  animation: slideInRightTop .35s ease forwards;
}

.toast-icon-wrap {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.toast.success .toast-icon-wrap {
  background: var(--success-soft);
  color: var(--success);
}

.toast.error .toast-icon-wrap {
  background: var(--danger-soft);
  color: var(--danger);
}

.toast.warning .toast-icon-wrap {
  background: var(--warning-soft);
  color: var(--warning);
}

.toast-content { flex: 1; }
.toast-title {
  font-size: 14px;
  font-weight: 700;
  color: #111111;
  margin-bottom: 2px;
}

.toast-message {
  font-size: 13px;
  color: var(--muted);
  line-height: 1.3;
}

@keyframes slideInRightTop {
  from { transform: translateX(120%) translateY(-20px); opacity: 0; }
  to { transform: translateX(0) translateY(0); opacity: 1; }
}

@keyframes fadeOutUp {
  to { transform: translateY(-20px); opacity: 0; }
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

.required { color: var(--danger); }

.big-screen-modal {
  max-width: 920px;
  background: transparent;
  border: none;
  box-shadow: none;
  display: flex;
  gap: 24px;
  align-items: stretch;
  flex-wrap: wrap;
  padding: 0;
}

.big-screen-left,
.big-screen-right {
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 24px;
  box-shadow: 0 18px 40px rgba(0,0,0,0.08);
}

.big-screen-left {
  flex: 1;
  min-width: 300px;
  padding: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.big-screen-img {
  max-width: 100%;
  max-height: 350px;
  object-fit: contain;
  border-radius: 12px;
}

.big-screen-right {
  flex: 1.2;
  min-width: 320px;
  padding: 36px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
}

.big-location-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 999px;
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 20px;
  width: fit-content;
}

.loc-found {
  background: var(--success-soft);
  color: var(--success);
}

.loc-none {
  background: #f6f7f8;
  color: #666666;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 24px;
}

.detail-item span {
  display: block;
  font-size: 12px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .5px;
  font-weight: 700;
  margin-bottom: 4px;
}

.detail-item strong {
  display: block;
  font-size: 15px;
  color: #111111;
}

@media (max-width: 768px) {
  .grid-2,
  .detail-grid {
    grid-template-columns: 1fr;
  }

  .product-row { flex-wrap: wrap; }
  .lote-input { flex: 1; }
  .big-screen-modal { flex-direction: column; }
}
</style>

<script>
window.WMS_LOCATIONS = @json(
    ($locations ?? collect())->map(fn($loc) => [
        'id' => (int) $loc->id,
        'code' => (string) $loc->code,
    ])->values()
);
</script>

<div class="reception-shell">
  <div class="page-head">
    <div>
      <h1>Nueva Recepción</h1>
      <p>Registra el ingreso de mercancía y deja la ubicación lista desde el inicio.</p>
    </div>
    <div class="badge">Folio: <span>{{ $folio }}</span></div>
  </div>

  @if($errors->any())
    <div class="card" style="border-color: var(--danger); background: var(--danger-soft);">
      <div class="card-body" style="padding:16px 20px;">
        <div style="color: var(--danger); font-weight:700; margin-bottom:8px;">Revisa los siguientes errores:</div>
        <ul style="margin:0; padding-left:18px; color: var(--danger); font-size: 14px;">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  <form id="receptionForm" method="POST" action="{{ route('admin.wms.receptions.store') }}">
    @csrf
    <input type="hidden" name="folio" value="{{ old('folio', $folio) }}">

    <div class="card">
      <div class="card-body">
        <div class="grid-2" style="margin-bottom: 0;">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Persona que entrega (Proveedor/Usuario)</label>
            <div class="search-select">
              <input type="hidden" name="deliverer_user_id" id="deliverer_user_id">
              <input type="text" class="form-input" id="deliverer_search" placeholder="Buscar por nombre o correo" autocomplete="off">
              <div class="search-dropdown" id="deliverer_dropdown"></div>
            </div>
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Usuario que recibe (Almacenista)</label>
            <div class="search-select">
              <input type="hidden" name="receiver_user_id" id="receiver_user_id">
              <input type="text" class="form-input" id="receiver_search" placeholder="Buscar usuario interno" autocomplete="off">
              <div class="search-dropdown" id="receiver_dropdown"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="card-title">Productos a Recibir</h2>

        <div class="product-search-wrapper" id="globalSearchWrapper">
          <div class="product-search-input-container">
            <svg width="20" height="20" fill="none" stroke="var(--muted)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" id="globalProductSearch" class="product-search-input" placeholder="Escanea código de barras o busca por nombre, SKU, marca, modelo..." autocomplete="off">
          </div>
          <div class="search-dropdown" id="globalProductDropdown" style="padding:0;"></div>
        </div>

        <div class="selected-products-container" id="selectedProductsList"></div>

        <div id="emptyTableState" style="padding: 30px; text-align: center; color: var(--muted); font-size: 14px;">
          Busca o escanea un producto en la barra superior.
        </div>

        <div class="list-footer">
          <div><strong id="summaryCount">0</strong> producto(s)</div>
          <div><strong id="summaryTotal">0</strong> unidades totales</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="card-title">Observaciones</h2>
        <textarea name="observations" class="form-textarea" placeholder="Notas adicionales sobre las condiciones de la entrega, sellos, discrepancias...">{{ old('observations') }}</textarea>
      </div>
    </div>

    <div class="footer-actions">
      <a href="{{ route('admin.wms.movements.view') }}" class="btn-ghost">Cancelar</a>
      <button type="submit" class="btn-primary">Procesar Recepción</button>
    </div>
  </form>
</div>

<div class="modal-backdrop" id="bigScreenModal">
  <div class="modal big-screen-modal">
    <div class="big-screen-left">
      <img id="bs_img" class="big-screen-img" src="" alt="Producto">
    </div>

    <div class="big-screen-right">
      <button type="button" class="modal-close" id="closeBigScreenModal" style="position: absolute; top: 16px; right: 16px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>

      <div id="bs_location_container" class="big-location-badge loc-none">
        <svg id="bs_loc_icon" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span id="bs_location_text">Buscando ubicación...</span>
      </div>

      <h2 id="bs_name" style="margin: 0 0 8px; font-size: 24px; color: #111111; font-weight: 700; line-height: 1.2;">-</h2>
      <p id="bs_desc" style="margin: 0 0 24px; color: var(--muted); font-size: 14px; line-height: 1.5;">-</p>

      <div class="detail-grid">
        <div class="detail-item"><span>SKU / Código</span><strong id="bs_sku">-</strong></div>
        <div class="detail-item"><span>Código Barras</span><strong id="bs_code">-</strong></div>
        <div class="detail-item"><span>Marca</span><strong id="bs_brand">-</strong></div>
        <div class="detail-item"><span>Modelo</span><strong id="bs_model">-</strong></div>
      </div>

      <div style="display: flex; gap: 12px; margin-top: auto; padding-top: 24px;">
        <button type="button" class="btn-outline" id="cancelBigScreenBtn" style="flex: 1;">Cancelar (Esc)</button>
        <button type="button" class="btn-primary" id="confirmBigScreenBtn" style="flex: 2; height: 48px; font-size: 16px;">
          Agregar a la lista (Enter)
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="quickProductModal">
  <div class="modal">
    <div class="modal-head">
      <div>
        <h3>Registrar Nuevo Producto</h3>
        <p>Registra el artículo faltante en el catálogo rápidamente.</p>
      </div>
      <button type="button" class="modal-close" id="closeQuickProductModal">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div class="modal-body">
      <div class="grid-2">
        <div class="form-group"><label class="form-label">SKU / Código <span class="required">*</span></label><input type="text" class="form-input" id="qp_sku"></div>
        <div class="form-group"><label class="form-label">Nombre del producto <span class="required">*</span></label><input type="text" class="form-input" id="qp_name"></div>
      </div>
      <div class="grid-2" style="margin-bottom:0;">
        <div class="form-group" style="margin-bottom:0;"><label class="form-label">Marca</label><input type="text" class="form-input" id="qp_brand_name"></div>
        <div class="form-group" style="margin-bottom:0;"><label class="form-label">Modelo</label><input type="text" class="form-input" id="qp_model_name"></div>
      </div>
    </div>
    <div class="modal-foot">
      <button type="button" class="btn-ghost" id="cancelQuickProductModal">Cancelar</button>
      <button type="button" class="btn-primary" id="saveQuickProductBtn">Guardar y Agregar</button>
    </div>
  </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script>
(() => {
  const csrfToken = @json(csrf_token());
  let lineIndex = 0;
  let currentPendingProduct = null;
  let bigScreenTimeout = null;
  let bigScreenInterval = null;

  const productsList = document.getElementById('selectedProductsList');
  const emptyState = document.getElementById('emptyTableState');
  const summaryCount = document.getElementById('summaryCount');
  const summaryTotal = document.getElementById('summaryTotal');

  const globalInput = document.getElementById('globalProductSearch');
  const globalDropdown = document.getElementById('globalProductDropdown');
  const globalWrapper = document.getElementById('globalSearchWrapper');

  const bigScreenModal = document.getElementById('bigScreenModal');
  const confirmBigScreenBtn = document.getElementById('confirmBigScreenBtn');
  const quickProductModal = document.getElementById('quickProductModal');

  function showToast(title, message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;

    let icon = type === 'success'
      ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
      : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';

    if (type === 'warning') {
      icon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>';
    }

    toast.innerHTML = `
      <div class="toast-icon-wrap"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icon}</svg></div>
      <div class="toast-content"><div class="toast-title">${title}</div><div class="toast-message">${message}</div></div>
    `;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.animation = 'fadeOutUp 0.3s forwards';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    })[m]);
  }

  function debounce(fn, wait = 250) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  function updateFooters() {
    const rows = productsList.querySelectorAll('.product-row');
    let totalQty = 0;

    rows.forEach(row => {
      const qtyInput = row.querySelector('.qty-input');
      if (qtyInput) totalQty += parseInt(qtyInput.value || 0, 10);
    });

    summaryCount.textContent = rows.length;
    summaryTotal.textContent = totalQty;
    emptyState.style.display = rows.length === 0 ? 'block' : 'none';
  }

  function openBigScreen(product) {
    currentPendingProduct = product;

    document.getElementById('bs_img').src = product.image_url || 'https://via.placeholder.com/400x400.png?text=Sin+Imagen';
    document.getElementById('bs_name').textContent = product.name;
    document.getElementById('bs_desc').textContent = product.description || 'Sin descripción detallada.';
    document.getElementById('bs_sku').textContent = product.sku || 'N/A';
    document.getElementById('bs_code').textContent = product.gtin || 'N/A';
    document.getElementById('bs_brand').textContent = product.brand_name || '—';
    document.getElementById('bs_model').textContent = product.model_name || '—';

    const locContainer = document.getElementById('bs_location_container');
    const locText = document.getElementById('bs_location_text');

    if (product.recommended && product.recommended.code) {
      locContainer.className = 'big-location-badge loc-found';
      locText.textContent = `Ubicación sugerida: ${product.recommended.code}`;
    } else {
      locContainer.className = 'big-location-badge loc-none';
      locText.textContent = 'Sin ubicación sugerida';
    }

    bigScreenModal.classList.add('is-open');

    let timeLeft = 3;
    const updateBtnText = () => {
      confirmBigScreenBtn.innerHTML = `Agregar (Enter) <span style="opacity:0.7; font-size:13px; margin-left:8px;">Auto en ${timeLeft}s</span>`;
    };

    updateBtnText();
    clearTimeout(bigScreenTimeout);
    clearInterval(bigScreenInterval);

    bigScreenInterval = setInterval(() => {
      timeLeft -= 1;
      if (timeLeft > 0) updateBtnText();
    }, 1000);

    bigScreenTimeout = setTimeout(() => {
      clearInterval(bigScreenInterval);
      if (currentPendingProduct) addProductToTable(currentPendingProduct);
      closeBigScreen();
    }, 3000);

    setTimeout(() => confirmBigScreenBtn.focus(), 100);
  }

  function closeBigScreen() {
    clearTimeout(bigScreenTimeout);
    clearInterval(bigScreenInterval);
    bigScreenModal.classList.remove('is-open');
    currentPendingProduct = null;
    globalInput.focus();
  }

  confirmBigScreenBtn.addEventListener('click', () => {
    clearTimeout(bigScreenTimeout);
    clearInterval(bigScreenInterval);
    if (currentPendingProduct) addProductToTable(currentPendingProduct);
    closeBigScreen();
  });

  document.getElementById('cancelBigScreenBtn').addEventListener('click', closeBigScreen);
  document.getElementById('closeBigScreenModal').addEventListener('click', closeBigScreen);

  function openQuickModal(sku = '') {
    quickProductModal.classList.add('is-open');
    document.getElementById('qp_sku').value = sku;
    setTimeout(() => document.getElementById(sku ? 'qp_name' : 'qp_sku').focus(), 100);
  }

  function closeQuickModal() {
    quickProductModal.classList.remove('is-open');
    globalInput.focus();
  }

  document.getElementById('closeQuickProductModal').addEventListener('click', closeQuickModal);
  document.getElementById('cancelQuickProductModal').addEventListener('click', closeQuickModal);

  document.addEventListener('keydown', (e) => {
    if (bigScreenModal.classList.contains('is-open')) {
      if (e.key === 'Escape') {
        e.preventDefault();
        closeBigScreen();
      }
      if (e.key === 'Enter' && document.activeElement !== confirmBigScreenBtn) {
        e.preventDefault();
        confirmBigScreenBtn.click();
      }
    } else if (quickProductModal.classList.contains('is-open') && e.key === 'Escape') {
      closeQuickModal();
    }
  });

  function initUserSearch(inputId, hiddenId, dropdownId) {
    const input = document.getElementById(inputId);
    const hidden = document.getElementById(hiddenId);
    const dropdown = document.getElementById(dropdownId);

    const search = debounce(async (q) => {
      if (q.length < 2) return dropdown.classList.remove('is-open');

      const res = await fetch(`{{ route('admin.wms.receptions.users') }}?q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await res.json();

      dropdown.innerHTML = (!json.items || !json.items.length)
        ? `<div class="search-item"><strong>Sin resultados</strong></div>`
        : json.items.map(i => `<div class="search-item" data-id="${i.id}" data-name="${escapeHtml(i.name)}"><strong>${escapeHtml(i.name)}</strong><span>${escapeHtml(i.email ?? '')}</span></div>`).join('');

      dropdown.classList.add('is-open');
    }, 250);

    input.addEventListener('input', (e) => search(e.target.value));

    dropdown.addEventListener('click', (e) => {
      const item = e.target.closest('.search-item[data-id]');
      if (!item) return;
      input.value = item.dataset.name;
      hidden.value = item.dataset.id;
      dropdown.classList.remove('is-open');
    });

    document.addEventListener('click', (e) => {
      if (!input.closest('.search-select').contains(e.target)) {
        dropdown.classList.remove('is-open');
      }
    });
  }

  initUserSearch('deliverer_search', 'deliverer_user_id', 'deliverer_dropdown');
  initUserSearch('receiver_search', 'receiver_user_id', 'receiver_dropdown');

  async function searchProducts(q) {
    const res = await fetch(`{{ route('admin.wms.receptions.products') }}?q=${encodeURIComponent(q)}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    return await res.json();
  }

  function renderGlobalDropdown(items, query) {
    if (!items.length) {
      globalDropdown.innerHTML = `
        <div class="product-dropdown-item" data-action="new" data-sku="${escapeHtml(query)}" style="color:var(--blue);">
          <div class="product-icon-box" style="background:var(--blue-soft); color:var(--blue);"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg></div>
          <div class="product-info-flex"><strong>Registrar producto no encontrado</strong><span style="display:block; font-size:12px;">Buscado: ${escapeHtml(query)}</span></div>
        </div>`;
    } else {
      globalDropdown.innerHTML = items.map(item => {
        let brandStr = item.brand_name ? ` · ${escapeHtml(item.brand_name)}` : '';
        let modelStr = item.model_name ? ` ${escapeHtml(item.model_name)}` : '';

        return `
        <div class="product-dropdown-item js-add-product" data-json='${escapeHtml(JSON.stringify(item))}'>
          <div class="product-icon-box"><svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg></div>
          <div class="product-info-flex">
            <strong style="color:#111111; display:block; font-size:14px;">${escapeHtml(item.name)}</strong>
            <span style="color:var(--muted); font-size:12px;">SKU: ${escapeHtml(item.sku ?? '—')}${brandStr}${modelStr}</span>
          </div>
          <div class="product-stock-badge">${item.stock ?? 0} uds</div>
        </div>
      `}).join('') + `<div class="dropdown-footer"><span>${items.length} resultados</span><span>Enter para seleccionar rápido</span></div>`;
    }

    globalDropdown.classList.add('is-open');
  }

  const doGlobalSearch = debounce(async () => {
    const q = globalInput.value.trim();
    if (q.length < 2) return globalDropdown.classList.remove('is-open');
    const json = await searchProducts(q);
    renderGlobalDropdown(json.items || [], q);
  }, 250);

  globalInput.addEventListener('input', doGlobalSearch);

  globalInput.addEventListener('keydown', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      const q = globalInput.value.trim();
      if (!q) return;

      globalDropdown.classList.remove('is-open');
      const json = await searchProducts(q);
      const exact = (json.items || []).find(i =>
        String(i.sku).toLowerCase() === q.toLowerCase() ||
        String(i.meli_gtin).toLowerCase() === q.toLowerCase()
      );

      if (exact) openBigScreen(exact);
      else openQuickModal(q);
    }
  });

  globalDropdown.addEventListener('click', (e) => {
    const itemEl = e.target.closest('.js-add-product');
    const newItem = e.target.closest('[data-action="new"]');

    if (newItem) {
      globalDropdown.classList.remove('is-open');
      openQuickModal(newItem.dataset.sku);
    } else if (itemEl) {
      globalDropdown.classList.remove('is-open');
      const productData = JSON.parse(itemEl.dataset.json);
      openBigScreen(productData);
    }
  });

  document.addEventListener('click', (e) => {
    if (!globalWrapper.contains(e.target)) globalDropdown.classList.remove('is-open');
  });

  function addProductToTable(product) {
    emptyState.style.display = 'none';

    let exists = false;

    document.querySelectorAll('#selectedProductsList .product-row').forEach(row => {
      if (row.dataset.sku === product.sku) {
        const qtyInput = row.querySelector('.qty-input');
        qtyInput.value = parseInt(qtyInput.value || 0, 10) + 1;

        const locationInput = row.querySelector('.js-line-location-id');
        const locationSelect = row.querySelector('.js-line-location-select');

        if (locationInput && !locationInput.value && product.recommended?.location_id) {
          locationInput.value = String(product.recommended.location_id);
        }

        if (locationSelect && !locationSelect.value && product.recommended?.location_id) {
          locationSelect.value = String(product.recommended.location_id);
        }

        exists = true;
        row.style.borderColor = 'var(--blue)';
        setTimeout(() => row.style.borderColor = 'var(--line)', 500);
      }
    });

    if (!exists) {
      const div = document.createElement('div');
      div.className = 'product-row';
      div.dataset.sku = product.sku || '';

      const allLocations = Array.isArray(product.all_locations) && product.all_locations.length
        ? product.all_locations
        : (Array.isArray(window.WMS_LOCATIONS) ? window.WMS_LOCATIONS : []);

      let locationText = '';
      if (product.recommended && product.recommended.code) {
        locationText = `<span style="font-size:11px; font-weight:700; color:var(--success); background:var(--success-soft); padding:4px 8px; border-radius:999px; margin-left:8px;">${escapeHtml(product.recommended.code)}</span>`;
      }

      let extraInfo = [];
      if (product.brand_name) extraInfo.push(`Marca: ${escapeHtml(product.brand_name)}`);
      if (product.model_name) extraInfo.push(`Mod: ${escapeHtml(product.model_name)}`);
      let extraInfoStr = extraInfo.length > 0 ? `<span style="margin-left: 8px; opacity: 0.8;">| ${extraInfo.join(' - ')}</span>` : '';

      const locationOptions = [
        `<option value="">Seleccionar ubicación</option>`,
        ...allLocations.map(loc => `
          <option value="${loc.id}" ${String(product.recommended?.location_id ?? '') === String(loc.id) ? 'selected' : ''}>
            ${escapeHtml(loc.code)}
          </option>
        `)
      ].join('');

      div.innerHTML = `
        <div class="product-icon-box">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
        </div>

        <div class="product-info-flex">
          <input type="hidden" name="lines[${lineIndex}][catalog_item_id]" value="${product.id}">
          <input type="hidden" class="js-line-location-id" name="lines[${lineIndex}][location_id]" value="${product.recommended?.location_id ?? ''}">
          <input type="hidden" name="lines[${lineIndex}][sku]" value="${escapeHtml(product.sku)}">
          <input type="hidden" name="lines[${lineIndex}][description]" value="${escapeHtml(product.name)}">

          <strong style="display:flex; align-items:center; color:#111111; font-size:14px; flex-wrap:wrap;">
            ${escapeHtml(product.name)} ${locationText}
          </strong>
          <span style="display:block; color:var(--muted); font-size:12px; margin-top:2px;">
            SKU: ${escapeHtml(product.sku)} ${extraInfoStr}
          </span>

          <div style="margin-top:10px; max-width:220px;">
            <label style="display:block; font-size:11px; font-weight:700; color:var(--muted); margin-bottom:6px;">
              Ubicación
            </label>
            <select class="form-select js-line-location-select" style="min-height:38px; height:38px; font-size:13px; padding:6px 10px;">
              ${locationOptions}
            </select>
          </div>
        </div>

        <input type="text" name="lines[${lineIndex}][lot]" class="lote-input" placeholder="Lote / Serie">

        <select name="lines[${lineIndex}][condition]" class="status-select js-status-change" data-val="bueno">
          <option value="bueno">Bueno</option>
          <option value="dañado">Dañado</option>
          <option value="revision">Revisión</option>
        </select>

        <div class="qty-control">
          <button type="button" class="qty-btn js-qty-minus">-</button>
          <input type="number" name="lines[${lineIndex}][quantity]" class="qty-input" value="1" min="1">
          <button type="button" class="qty-btn js-qty-plus">+</button>
        </div>

        <button type="button" class="btn-remove-row js-remove-row">
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
        </button>
      `;

      const select = div.querySelector('.js-line-location-select');
      const hidden = div.querySelector('.js-line-location-id');

      if (select && hidden) {
        select.addEventListener('change', () => {
          hidden.value = select.value;
        });
      }

      productsList.prepend(div);
      lineIndex++;
    }

    updateFooters();
    showToast('Añadido a recepción', `Se registró: ${product.name}`, 'success');
    globalInput.value = '';
    globalInput.focus();
  }

  productsList.addEventListener('click', (e) => {
    const row = e.target.closest('.product-row');
    if (!row) return;

    if (e.target.closest('.js-remove-row')) {
      row.style.opacity = '0';
      row.style.transform = 'translateY(10px)';
      setTimeout(() => {
        row.remove();
        updateFooters();
      }, 300);
      return;
    }

    const qtyInput = row.querySelector('.qty-input');
    if (e.target.closest('.js-qty-minus')) {
      let val = parseInt(qtyInput.value || 1, 10);
      if (val > 1) qtyInput.value = val - 1;
      updateFooters();
    }

    if (e.target.closest('.js-qty-plus')) {
      let val = parseInt(qtyInput.value || 0, 10);
      qtyInput.value = val + 1;
      updateFooters();
    }
  });

  productsList.addEventListener('input', (e) => {
    if (e.target.classList.contains('qty-input')) updateFooters();
  });

  productsList.addEventListener('change', (e) => {
    if (e.target.classList.contains('js-status-change')) e.target.dataset.val = e.target.value;
  });

  document.getElementById('saveQuickProductBtn').addEventListener('click', async () => {
    const payload = {
      sku: document.getElementById('qp_sku').value.trim(),
      name: document.getElementById('qp_name').value.trim(),
      brand_name: document.getElementById('qp_brand_name').value.trim(),
      model_name: document.getElementById('qp_model_name').value.trim(),
    };

    if (!payload.sku || !payload.name) {
      return showToast('Error', 'SKU y Nombre son obligatorios.', 'error');
    }

    try {
      const res = await fetch(`{{ route('admin.wms.receptions.products.quick-store') }}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });

      const json = await res.json();
      if (!json.ok) throw new Error();

      addProductToTable({
        id: json.item.id,
        name: json.item.name,
        sku: json.item.sku,
        brand_name: json.item.brand_name || payload.brand_name,
        model_name: json.item.model_name || payload.model_name,
        recommended: json.item.recommended || null,
        all_locations: json.item.all_locations || window.WMS_LOCATIONS || []
      });

      ['qp_sku','qp_name','qp_brand_name','qp_model_name'].forEach(id => {
        document.getElementById(id).value = '';
      });

      closeQuickModal();
    } catch (e) {
      showToast('Error', 'No se pudo registrar el producto.', 'error');
    }
  });

  setTimeout(() => globalInput.focus(), 300);
})();
</script>
@endsection