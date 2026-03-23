@extends('layouts.app')

@section('title', 'WMS · Ubicaciones')

@section('content')
@php
  $whId = (int)($warehouseId ?? request('warehouse_id', 1));

  $heatmapViewUrl = \Illuminate\Support\Facades\Route::has('admin.wms.heatmap.view')
      ? route('admin.wms.heatmap.view', ['warehouse_id' => $whId])
      : url('/admin/wms/heatmap?warehouse_id='.$whId);

  $layoutEditorUrl = \Illuminate\Support\Facades\Route::has('admin.wms.layout.editor')
      ? route('admin.wms.layout.editor', ['warehouse_id' => $whId])
      : $heatmapViewUrl;

  $heatmapDataUrl = \Illuminate\Support\Facades\Route::has('admin.wms.heatmap.data')
      ? route('admin.wms.heatmap.data')
      : url('/admin/wms/heatmap/data');

  $layoutUpsertUrl = \Illuminate\Support\Facades\Route::has('admin.wms.layout.cell.upsert')
      ? route('admin.wms.layout.cell.upsert')
      : url('/admin/wms/layout/cell');

  $layoutAvailableUrl = \Illuminate\Support\Facades\Route::has('admin.wms.layout.available-options')
      ? route('admin.wms.layout.available-options')
      : url('/admin/wms/layout/available-options');

  $layoutDeleteUrl = \Illuminate\Support\Facades\Route::has('admin.wms.layout.delete')
      ? route('admin.wms.layout.delete')
      : url('/admin/wms/layout/delete');

  $showBaseUrl = url('/admin/wms/locations');
@endphp

<div class="loc-wrap">
  <div class="loc-head">
    <div>
      <div class="loc-title">Ubicaciones</div>
      <div class="loc-sub">Vista sincronizada con el heatmap real del almacén.</div>
    </div>

    <div class="loc-head-actions">
      <select id="warehouseSelect" class="loc-select loc-select-head">
        @forelse(($warehouses ?? []) as $w)
          <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>
            {{ $w->name ?? ('Bodega #'.$w->id) }}
          </option>
        @empty
          <option value="{{ $whId }}">Bodega actual</option>
        @endforelse
      </select>

      <a href="{{ $layoutEditorUrl }}" class="loc-btn loc-btn-ghost">
        <span class="loc-btn-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 6a2 2 0 0 1 2-2h4v6H4V6Zm10-2h4a2 2 0 0 1 2 2v4h-6V4ZM4 14h6v6H6a2 2 0 0 1-2-2v-4Zm10 0h6v4a2 2 0 0 1-2 2h-4v-6Z" stroke="currentColor" stroke-width="1.6"/>
          </svg>
        </span>
        Layout
      </a>

      <button type="button" class="loc-btn loc-btn-primary" id="btnOpenCreate">
        <span class="loc-btn-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
        </span>
        Nueva Ubicación
      </button>
    </div>
  </div>

  <div class="loc-tabs">
    <a href="javascript:void(0)" class="loc-tab is-active">
      <span class="loc-tab-icon">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 21s6-5.686 6-11a6 6 0 1 0-12 0c0 5.314 6 11 6 11Z" stroke="currentColor" stroke-width="1.9"/>
          <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.9"/>
        </svg>
      </span>
      Ubicaciones
    </a>

    <a href="{{ $heatmapViewUrl }}" class="loc-tab">
      <span class="loc-tab-icon">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M12 3v18M8.5 6v12M15.5 6v12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        </svg>
      </span>
      Mapa de Calor
    </a>

    <a href="{{ $layoutEditorUrl }}" class="loc-tab">
      <span class="loc-tab-icon">
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 6a2 2 0 0 1 2-2h4v6H4V6Zm10-2h4a2 2 0 0 1 2 2v4h-6V4ZM4 14h6v6H6a2 2 0 0 1-2-2v-4Zm10 0h6v4a2 2 0 0 1-2 2h-4v-6Z" stroke="currentColor" stroke-width="1.6"/>
        </svg>
      </span>
      Layout
    </a>
  </div>

  <div class="loc-card">
    <div class="loc-toolbar">
      <div class="loc-search">
        <span class="loc-search-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.9"/>
            <path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
          </svg>
        </span>
        <input id="q" type="text" placeholder="Buscar por código, zona, rack, nivel, bin o coordenadas...">
      </div>

      <div class="loc-toolbar-right">
        <select id="metricSelect" class="loc-select">
          <option value="inv_qty" selected>Inventario por ubicación</option>
          <option value="primary_stock">Stock primario</option>
        </select>

        <select id="filterZone" class="loc-select">
          <option value="">Todas las zonas</option>
        </select>
      </div>
    </div>

    <div class="loc-summary-bar" id="summaryBar">
      Cargando ubicaciones...
    </div>

    <div class="loc-table-wrap">
      <table class="loc-table">
        <thead>
          <tr>
            <th>Ubicación</th>
            <th>Zona</th>
            <th>Tipo</th>
            <th>Heatmap</th>
            <th>Ocupación</th>
            <th>Estado</th>
            <th class="ta-r">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr>
            <td colspan="7" class="loc-empty">Cargando ubicaciones...</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="loc-modal-backdrop" id="detailModal">
  <div class="loc-modal loc-modal-xl">
    <div class="loc-modal-head detail-head">
      <div>
        <div class="loc-modal-title" id="detailTitle">Ubicación</div>
        <div class="loc-modal-sub" id="detailSub">—</div>
      </div>

      <button class="loc-close" type="button" data-close="#detailModal" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <div class="loc-detail-layout">
      <div class="loc-detail-side">
        <div class="loc-pane">
          <div class="loc-pane-title">Resumen</div>
          <div class="loc-summary-list" id="detailSummary"></div>
        </div>
      </div>

      <div class="loc-detail-main">
        <div class="loc-pane">
          <div class="loc-pane-title">Contenido actual</div>
          <div id="detailInventory">
            <div class="loc-empty-card">Sin información.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="loc-modal-backdrop" id="qrModal">
  <div class="loc-modal loc-modal-qr">
    <div class="loc-modal-head loc-modal-head-qr">
      <div class="loc-qr-title-wrap">
        <span class="loc-qr-head-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 21s6-5.686 6-11a6 6 0 1 0-12 0c0 5.314 6 11 6 11Z" stroke="currentColor" stroke-width="1.9"/>
            <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.9"/>
          </svg>
        </span>

        <div>
          <div class="loc-modal-title" id="qrTitle">QR Ubicación</div>
        </div>
      </div>

      <button class="loc-close" type="button" data-close="#qrModal" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <div class="loc-qr-clean">
      <div class="loc-qr-box">
        <img id="qrImage" src="" alt="QR de ubicación" class="loc-qr-image">
      </div>

      <div class="loc-qr-info">
        <div class="loc-qr-info-row">
          <div class="loc-qr-label">Código</div>
          <div class="loc-qr-value" id="qrCodeValue">—</div>
        </div>

        <div class="loc-qr-info-row">
          <div class="loc-qr-label">Zona</div>
          <div class="loc-qr-value" id="qrZoneValue">—</div>
        </div>

        <div class="loc-qr-info-row">
          <div class="loc-qr-label">Posición</div>
          <div class="loc-qr-value" id="qrPosValue">—</div>
        </div>

        <div class="loc-qr-info-row">
          <div class="loc-qr-label">Estado</div>
          <div class="loc-qr-value" id="qrStatusValue">—</div>
        </div>
      </div>

      <a id="qrDownloadBtn" href="#" target="_blank" class="loc-btn loc-btn-primary loc-btn-block" download>
        <span class="loc-btn-icon">
          <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 4v10m0 0 4-4m-4 4-4-4M5 19h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </span>
        Descargar QR
      </a>
    </div>
  </div>
</div>

<div class="loc-modal-backdrop" id="createModal">
  <div class="loc-modal loc-modal-form">
    <div class="loc-modal-head">
      <div>
        <div class="loc-modal-title">Nueva ubicación</div>
        <div class="loc-modal-sub">Selecciona únicamente posiciones reales disponibles.</div>
      </div>

      <button class="loc-close" type="button" data-close="#createModal" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <form id="createForm" class="loc-form">
      @csrf

      <input type="hidden" name="warehouse_id" id="create_warehouse_id" value="{{ $whId }}">
      <input type="hidden" name="meta_x" id="create_meta_x" value="0">
      <input type="hidden" name="meta_y" id="create_meta_y" value="0">
      <input type="hidden" name="meta_w" id="create_meta_w" value="48">
      <input type="hidden" name="meta_h" id="create_meta_h" value="30">

      <div class="loc-form-grid clean-form-grid">
        <div class="loc-field">
          <label>Tipo *</label>
          <select name="type" id="create_type" required></select>
        </div>

        <div class="loc-field" id="createSlotWrap">
          <label>Ubicación disponible *</label>
          <select id="create_slot_select"></select>
        </div>

        <div class="loc-field">
          <label>Código generado</label>
          <input type="text" name="code" id="create_code" readonly>
        </div>

        <div class="loc-field">
          <label>Nombre generado</label>
          <input type="text" name="name" id="create_name" readonly>
        </div>

        <div class="loc-field">
          <label>Rack</label>
          <input type="text" name="rack" id="create_rack" readonly>
        </div>

        <div class="loc-field">
          <label>Nivel</label>
          <input type="text" name="level" id="create_level" readonly>
        </div>

        <div class="loc-field">
          <label>Posición</label>
          <input type="text" name="bin" id="create_bin" readonly>
        </div>

        <div class="loc-field">
          <label>Heatmap asignado</label>
          <input type="text" id="create_heatmap_preview" readonly>
        </div>

        <div class="loc-field">
          <label>Capacidad</label>
          <input type="number" name="meta_capacity" min="0" step="1" value="100">
        </div>

        <div class="loc-field">
          <label>Color heatmap</label>
          <input type="text" name="meta_color" placeholder="Ej. #2563eb">
        </div>

        <div class="loc-field loc-field-full">
          <label>Resumen selección</label>
          <div class="loc-empty-card" id="createSummaryCard">Cargando opciones disponibles...</div>
        </div>

        <div class="loc-field loc-field-full">
          <label>Notas</label>
          <textarea name="meta_notes" rows="3" placeholder="Notas internas de la ubicación..."></textarea>
        </div>
      </div>

      <div class="loc-form-actions">
        <button type="button" class="loc-btn loc-btn-ghost" data-close="#createModal">Cancelar</button>
        <button type="submit" class="loc-btn loc-btn-primary" id="createSubmitBtn">Guardar ubicación</button>
      </div>
    </form>
  </div>
</div>

<div class="loc-modal-backdrop" id="editModal">
  <div class="loc-modal loc-modal-form">
    <div class="loc-modal-head">
      <div>
        <div class="loc-modal-title">Editar ubicación</div>
        <div class="loc-modal-sub">Actualiza datos reales del layout y del heatmap.</div>
      </div>

      <button class="loc-close" type="button" data-close="#editModal" aria-label="Cerrar">
        <svg viewBox="0 0 24 24" fill="none">
          <path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
    </div>

    <form id="editForm" class="loc-form">
      @csrf
      <input type="hidden" name="id" id="edit_id">
      <input type="hidden" name="warehouse_id" id="edit_warehouse_id" value="{{ $whId }}">

      <div class="loc-form-grid clean-form-grid">
        <div class="loc-field">
          <label>Código *</label>
          <input type="text" name="code" id="edit_code" required>
        </div>

        <div class="loc-field">
          <label>Nombre</label>
          <input type="text" name="name" id="edit_name">
        </div>

        <div class="loc-field">
          <label>Tipo *</label>
          <select name="type" id="edit_type" required>
            <option value="bin">Bin / Rack</option>
            <option value="picking">Picking</option>
            <option value="fast_flow">Fast Flow</option>
            <option value="incoming">Entrante</option>
            <option value="dispatch">Despacho</option>
            <option value="other">General</option>
          </select>
        </div>

        <div class="loc-field">
          <label>Color heatmap</label>
          <input type="text" name="meta_color" id="edit_meta_color" placeholder="Ej. #2563eb">
        </div>

        <div class="loc-field">
          <label>Pasillo</label>
          <input type="text" name="aisle" id="edit_aisle">
        </div>

        <div class="loc-field">
          <label>Stand</label>
          <input type="text" name="stand" id="edit_stand">
        </div>

        <div class="loc-field">
          <label>Sección</label>
          <input type="text" name="section" id="edit_section">
        </div>

        <div class="loc-field">
          <label>Rack</label>
          <input type="text" name="rack" id="edit_rack">
        </div>

        <div class="loc-field">
          <label>Nivel</label>
          <input type="text" name="level" id="edit_level">
        </div>

        <div class="loc-field">
          <label>Bin / Posición</label>
          <input type="text" name="bin" id="edit_bin">
        </div>

        <div class="loc-field">
          <label>Heatmap X *</label>
          <input type="number" name="meta_x" id="edit_meta_x" min="0" step="1" required>
        </div>

        <div class="loc-field">
          <label>Heatmap Y *</label>
          <input type="number" name="meta_y" id="edit_meta_y" min="0" step="1" required>
        </div>

        <div class="loc-field">
          <label>Heatmap W *</label>
          <input type="number" name="meta_w" id="edit_meta_w" min="1" step="1" required>
        </div>

        <div class="loc-field">
          <label>Heatmap H *</label>
          <input type="number" name="meta_h" id="edit_meta_h" min="1" step="1" required>
        </div>

        <div class="loc-field">
          <label>Capacidad</label>
          <input type="number" name="meta_capacity" id="edit_meta_capacity" min="0" step="1">
        </div>

        <div class="loc-field loc-field-full">
          <label>Notas</label>
          <textarea name="meta_notes" id="edit_meta_notes" rows="3"></textarea>
        </div>
      </div>

      <div class="loc-form-actions">
        <button type="button" class="loc-btn loc-btn-ghost" data-close="#editModal">Cancelar</button>
        <button type="submit" class="loc-btn loc-btn-primary">Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/locations.css') }}?v={{ time() }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(() => {
  const API_HEATMAP_DATA       = @json($heatmapDataUrl);
  const API_LAYOUT_UPSERT      = @json($layoutUpsertUrl);
  const API_AVAILABLE_OPTIONS  = @json($layoutAvailableUrl);
  const API_LAYOUT_DELETE      = @json($layoutDeleteUrl);
  const SHOW_BASE              = @json($showBaseUrl);
  const PAGE_BASE              = @json($showBaseUrl);
  const INITIAL_WAREHOUSE      = @json($whId);

  const tbody                  = document.getElementById('tbody');
  const qInp                   = document.getElementById('q');
  const filterZone             = document.getElementById('filterZone');
  const metricSelect           = document.getElementById('metricSelect');
  const warehouseSelect        = document.getElementById('warehouseSelect');
  const summaryBar             = document.getElementById('summaryBar');

  const detailModal            = document.getElementById('detailModal');
  const qrModal                = document.getElementById('qrModal');
  const createModal            = document.getElementById('createModal');
  const editModal              = document.getElementById('editModal');

  const detailTitle            = document.getElementById('detailTitle');
  const detailSub              = document.getElementById('detailSub');
  const detailSummary          = document.getElementById('detailSummary');
  const detailInventory        = document.getElementById('detailInventory');

  const qrTitle                = document.getElementById('qrTitle');
  const qrImage                = document.getElementById('qrImage');
  const qrCodeValue            = document.getElementById('qrCodeValue');
  const qrZoneValue            = document.getElementById('qrZoneValue');
  const qrPosValue             = document.getElementById('qrPosValue');
  const qrStatusValue          = document.getElementById('qrStatusValue');
  const qrDownloadBtn          = document.getElementById('qrDownloadBtn');

  const createForm             = document.getElementById('createForm');
  const editForm               = document.getElementById('editForm');
  const createType             = document.getElementById('create_type');
  const createSlotSelect       = document.getElementById('create_slot_select');
  const createSlotWrap         = document.getElementById('createSlotWrap');
  const createSummaryCard      = document.getElementById('createSummaryCard');
  const createSubmitBtn        = document.getElementById('createSubmitBtn');

  let ALL = [];
  let CREATE_OPTIONS = { slots: [], zones: [], next_slot: null };

  function esc(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;',
      '<':'&lt;',
      '>':'&gt;',
      '"':'&quot;',
      "'":'&#039;'
    }[m]));
  }

  function csrfToken(){
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  function getWarehouseId(){
    return Number(warehouseSelect?.value || INITIAL_WAREHOUSE || 1);
  }

  function syncWarehouseHiddenInputs(){
    const wid = getWarehouseId();
    const c = document.getElementById('create_warehouse_id');
    const e = document.getElementById('edit_warehouse_id');
    if (c) c.value = String(wid);
    if (e) e.value = String(wid);
  }

  function getItemById(id){
    return ALL.find(x => String(x.id) === String(id)) || null;
  }

  function prettyType(type){
    const map = {
      bin: 'Bin / Rack',
      picking: 'Picking',
      fast_flow: 'Fast Flow',
      incoming: 'Entrante',
      dispatch: 'Despacho',
      other: 'General'
    };

    const key = String(type || '').toLowerCase().trim();
    return map[key] || (key ? key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ') : '—');
  }

  function normalizeStatus(status){
    const s = String(status || '').toLowerCase().trim();

    if (['full', 'lleno'].includes(s)) return 'full';
    if (['high', 'alta', 'alta_ocupacion'].includes(s)) return 'high';
    if (['medium', 'medio'].includes(s)) return 'medium';
    if (['reserved', 'reservada', 'reservado'].includes(s)) return 'reserved';
    if (['occupied', 'ocupado'].includes(s)) return 'occupied';
    if (['empty', 'vacia', 'vacío', 'vacio'].includes(s)) return 'empty';

    return 'low';
  }

  function statusBadge(status){
    const s = normalizeStatus(status);

    if (s === 'full') return `<span class="loc-status loc-status-danger">Lleno</span>`;
    if (s === 'high') return `<span class="loc-status loc-status-warn">Alta ocupación</span>`;
    if (s === 'medium') return `<span class="loc-status loc-status-info">Ocupación media</span>`;
    if (s === 'reserved') return `<span class="loc-status loc-status-warn">Reservada</span>`;
    if (s === 'occupied') return `<span class="loc-status loc-status-info">Con inventario</span>`;
    if (s === 'empty') return `<span class="loc-status loc-status-empty">Vacía</span>`;

    return `<span class="loc-status loc-status-success">Disponible</span>`;
  }

  function statusInline(status){
    const s = normalizeStatus(status);

    if (s === 'full') return `<span class="loc-status-inline danger">Lleno</span>`;
    if (s === 'high') return `<span class="loc-status-inline warn">Alta ocupación</span>`;
    if (s === 'medium') return `<span class="loc-status-inline info">Ocupación media</span>`;
    if (s === 'reserved') return `<span class="loc-status-inline warn">Reservada</span>`;
    if (s === 'occupied') return `<span class="loc-status-inline info">Con inventario</span>`;
    if (s === 'empty') return `<span class="loc-status-inline empty">Vacía</span>`;

    return `<span class="loc-status-inline success">Disponible</span>`;
  }

  function occupancyPercent(item){
    const value = Number(item.value || 0);
    const cap = Number(item.max_capacity || 0);
    if (cap <= 0) return value > 0 ? 100 : 0;
    return Math.max(0, Math.min(100, Math.round((value / cap) * 100)));
  }

  function buildPosition(item){
    if (!item) return 'Sin posición';

    const parts = [];
    if (item.aisle) parts.push(`Pasillo ${item.aisle}`);
    if (item.stand) parts.push(`Stand ${item.stand}`);
    if (item.rack) parts.push(`Rack ${item.rack}`);
    if (item.level) parts.push(`Nivel ${item.level}`);
    if (item.bin) parts.push(`Bin ${item.bin}`);

    return parts.length ? parts.join(' · ') : 'Sin posición';
  }

  function buildQrPosition(item){
    if (!item) return 'Sin posición';
    const parts = [item.aisle, item.rack, item.level, item.bin].filter(Boolean);
    return parts.length ? parts.join(' - ') : `X ${item.x || 0} · Y ${item.y || 0}`;
  }

  function heatmapLabel(item){
    return `X ${Number(item.x || 0)} · Y ${Number(item.y || 0)} · ${Number(item.w || 1)}×${Number(item.h || 1)}`;
  }

  function buildZoneOptions(list){
    const zones = [...new Set(list.map(x => x.zone_label).filter(Boolean))].sort((a, b) => String(a).localeCompare(String(b)));
    filterZone.innerHTML =
      `<option value="">Todas las zonas</option>` +
      zones.map(z => `<option value="${esc(z)}">${esc(z)}</option>`).join('');
  }

  function sortByHeatmap(list){
    return [...list].sort((a, b) => {
      const ay = Number(a.y || 0);
      const by = Number(b.y || 0);
      if (ay !== by) return ay - by;

      const ax = Number(a.x || 0);
      const bx = Number(b.x || 0);
      if (ax !== bx) return ax - bx;

      return String(a.code || '').localeCompare(String(b.code || ''));
    });
  }

  function render(list){
    if (!list.length) {
      tbody.innerHTML = `<tr><td colspan="7" class="loc-empty">No se encontraron ubicaciones con esos filtros.</td></tr>`;
      return;
    }

    tbody.innerHTML = sortByHeatmap(list).map(l => {
      const percent  = occupancyPercent(l);
      const capacity = Number(l.max_capacity || 0);
      const qtyTotal = Number(l.value || 0);

      const chips = [
        `<span class="loc-chip loc-chip-info">${esc(heatmapLabel(l))}</span>`,
        l.inferred
          ? `<span class="loc-chip loc-chip-warn">Posición inferida</span>`
          : `<span class="loc-chip loc-chip-ok">Posición real</span>`
      ].join('');

      return `
        <tr>
          <td>
            <div class="loc-row-main">
              <div class="loc-pin">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 21s6-5.686 6-11a6 6 0 1 0-12 0c0 5.314 6 11 6 11Z" stroke="currentColor" stroke-width="1.9"/>
                  <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.9"/>
                </svg>
              </div>

              <div>
                <div class="loc-code">${esc(l.code || '—')}</div>
                <div class="loc-code-sub">${esc(buildPosition(l))}</div>
                <div class="loc-code-meta">${chips}</div>
              </div>
            </div>
          </td>

          <td><span class="loc-zone-chip">${esc(l.zone_label || 'General')}</span></td>
          <td><div class="loc-type">${esc(prettyType(l.type))}</div></td>

          <td>
            <div class="loc-type">
              ${esc(`X ${Number(l.x || 0)} · Y ${Number(l.y || 0)}`)}<br>
              <span style="color:#94a3b8">${esc(`W ${Number(l.w || 1)} · H ${Number(l.h || 1)}`)}</span>
            </div>
          </td>

          <td>
            <div class="loc-occ">
              <div class="loc-occ-line">
                <span class="loc-occ-qty"><strong>${qtyTotal}</strong>/${capacity}</span>
                <div class="loc-progress">
                  <span style="width:${percent}%"></span>
                </div>
                <span class="loc-occ-percent">${percent}%</span>
              </div>
            </div>
          </td>

          <td>${statusBadge(l.status)}</td>

          <td class="ta-r">
            <div class="loc-actions">
              <button class="loc-icon-btn show" type="button" data-act="show" data-id="${l.id}" title="Ver detalle">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" stroke="currentColor" stroke-width="1.8"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>
                </svg>
              </button>

              <button class="loc-icon-btn qr" type="button" data-act="qr" data-id="${l.id}" title="Ver QR">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm12 0v2m0 4v-2m-2-2h2m4 0h-2m-4 4h2m2 0h2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>

              <button class="loc-icon-btn edit" type="button" data-act="edit" data-id="${l.id}" title="Editar">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M4 20h4l10.5-10.5a2.121 2.121 0 0 0-3-3L5 17v3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                </svg>
              </button>

              <button class="loc-icon-btn delete" type="button" data-act="delete" data-id="${l.id}" title="Eliminar">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M5 7h14M10 11v6M14 11v6M8 7V5h8v2m-9 0 1 12h8l1-12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }

  function updateSummaryBar(list){
    const counts = list.reduce((acc, item) => {
      acc.total++;
      acc[item.zone || 'other'] = (acc[item.zone || 'other'] || 0) + 1;
      if (item.inferred) acc.inferred++;
      if (Number(item.value || 0) > 0) acc.withStock++;
      return acc;
    }, { total: 0, inferred: 0, withStock: 0 });

    summaryBar.textContent =
      `Total: ${counts.total} · Rack: ${counts.rack || 0} · Picking: ${counts.picking || 0} · Fast Flow: ${counts.fast_flow || 0} · Entrante: ${counts.incoming || 0} · Despacho: ${counts.dispatch || 0} · Con inventario: ${counts.withStock || 0} · Posición inferida: ${counts.inferred || 0}`;
  }

  function applyFilter(){
    const q = (qInp.value || '').trim().toLowerCase();
    const zone = (filterZone.value || '').trim().toLowerCase();

    const filtered = ALL.filter(l => {
      const text = [
        l.code, l.name, l.type, l.zone, l.zone_label, l.aisle, l.stand, l.section, l.rack, l.level, l.bin,
        l.product_name, l.product_sku,
        `x ${l.x}`, `y ${l.y}`, `w ${l.w}`, `h ${l.h}`
      ].filter(Boolean).join(' ').toLowerCase();

      const okQ = !q || text.includes(q);
      const okZone = !zone || String(l.zone_label || '').toLowerCase() === zone;

      return okQ && okZone;
    });

    render(filtered);
  }

  async function load(){
    tbody.innerHTML = `<tr><td colspan="7" class="loc-empty">Cargando ubicaciones...</td></tr>`;
    summaryBar.textContent = 'Cargando ubicaciones...';

    try {
      syncWarehouseHiddenInputs();

      const url = new URL(API_HEATMAP_DATA, window.location.origin);
      url.searchParams.set('warehouse_id', String(getWarehouseId()));
      url.searchParams.set('metric', String(metricSelect?.value || 'inv_qty'));

      const res = await fetch(url.toString(), {
        headers: { 'Accept':'application/json' }
      });

      const data = await res.json().catch(() => ({}));
      const list = Array.isArray(data?.cells) ? data.cells : [];

      ALL = list.map(item => ({
        id: Number(item.id || 0),
        code: String(item.code || ''),
        name: String(item.name || item.display_name || item.code || ''),
        type: String(item.type || 'other'),
        zone: String(item.zone || 'other'),
        zone_label: String(item.zone_label || 'General'),
        aisle: item.aisle || '',
        section: item.section || '',
        stand: item.stand || '',
        rack: item.rack || '',
        rack_key: item.rack_key || '',
        level: item.level || '',
        position: item.position || '',
        bin: item.bin || '',
        x: Number(item.x || 0),
        y: Number(item.y || 0),
        w: Number(item.w || 1),
        h: Number(item.h || 1),
        value: Number(item.value || 0),
        inv_qty: Number(item.inv_qty || 0),
        primary_stock: Number(item.primary_stock || 0),
        max_capacity: Number(item.max_capacity || 0),
        status: String(item.status || 'empty'),
        product_name: String(item.product_name || ''),
        product_sku: String(item.product_sku || ''),
        product_count: Number(item.product_count || 0),
        notes: String(item.notes || ''),
        color: item.color || '',
        inferred: !!item.inferred,
      }));

      buildZoneOptions(ALL);
      updateSummaryBar(ALL);
      applyFilter();
    } catch (e) {
      tbody.innerHTML = `<tr><td colspan="7" class="loc-empty">No se pudo cargar la información.</td></tr>`;
      summaryBar.textContent = 'No se pudo cargar la información.';
    }
  }

  async function loadAvailableOptions(ignoreId = null){
    const url = new URL(API_AVAILABLE_OPTIONS, window.location.origin);
    url.searchParams.set('warehouse_id', String(getWarehouseId()));
    if (ignoreId) url.searchParams.set('id', String(ignoreId));

    const res = await fetch(url.toString(), {
      headers: { 'Accept':'application/json' }
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data?.ok) {
      throw new Error(data?.message || data?.error || 'No se pudieron cargar las opciones disponibles.');
    }

    return {
      slots: Array.isArray(data.slots) ? data.slots : [],
      zones: Array.isArray(data.zones) ? data.zones : [],
      next_slot: data.next_slot || null,
    };
  }

  function fillCreateTypeOptions(){
    const slots = CREATE_OPTIONS.slots || [];
    const zones = CREATE_OPTIONS.zones || [];
    const zoneMap = Object.fromEntries(zones.map(z => [String(z.type), z]));
    const current = createType.value || 'bin';

    const options = [
      { value: 'bin', label: `Bin / Rack disponible${slots.length === 0 ? ' · sin espacios' : ''}`, disabled: slots.length === 0 },
      { value: 'picking', label: `Picking${zoneMap.picking?.available === false ? ' · ya ocupado' : ''}`, disabled: zoneMap.picking?.available === false },
      { value: 'fast_flow', label: `Fast Flow${zoneMap.fast_flow?.available === false ? ' · ya ocupado' : ''}`, disabled: zoneMap.fast_flow?.available === false },
      { value: 'incoming', label: `Entrante${zoneMap.incoming?.available === false ? ' · ya ocupado' : ''}`, disabled: zoneMap.incoming?.available === false },
      { value: 'dispatch', label: `Despacho${zoneMap.dispatch?.available === false ? ' · ya ocupado' : ''}`, disabled: zoneMap.dispatch?.available === false },
    ];

    createType.innerHTML = options.map(opt => `
      <option value="${esc(opt.value)}" ${opt.disabled ? 'disabled' : ''}>
        ${esc(opt.label)}
      </option>
    `).join('');

    const stillValid = options.find(opt => opt.value === current && !opt.disabled);
    if (stillValid) {
      createType.value = current;
      return;
    }

    const firstAvailable = options.find(opt => !opt.disabled);
    createType.value = firstAvailable ? firstAvailable.value : 'bin';
  }

  function populateCreateSlotSelect(){
    const slots = CREATE_OPTIONS.slots || [];
    const nextCode = CREATE_OPTIONS.next_slot?.code || '';

    createSlotSelect.innerHTML = slots.map(slot => `
      <option value="${esc(slot.code)}">
        ${esc(`${slot.label} · ${slot.code}`)}
      </option>
    `).join('');

    if (!slots.length) {
      createSlotSelect.innerHTML = `<option value="">Sin slots disponibles</option>`;
      return;
    }

    if (nextCode && slots.some(slot => String(slot.code) === String(nextCode))) {
      createSlotSelect.value = nextCode;
    } else {
      createSlotSelect.selectedIndex = 0;
    }
  }

  function getCreateSlot(){
    return (CREATE_OPTIONS.slots || []).find(s => String(s.code) === String(createSlotSelect.value)) || null;
  }

  function getCreateZone(type){
    return (CREATE_OPTIONS.zones || []).find(z => String(z.type) === String(type)) || null;
  }

  function setCreateField(id, value){
    const el = document.getElementById(id);
    if (el) el.value = value ?? '';
  }

  function updateCreateSelectionView(){
    const type = String(createType.value || 'bin');

    if (type === 'bin') {
      createSlotWrap.style.display = '';
      const slot = getCreateSlot();

      if (!slot) {
        setCreateField('create_code', '');
        setCreateField('create_name', '');
        setCreateField('create_rack', '');
        setCreateField('create_level', '');
        setCreateField('create_bin', '');
        setCreateField('create_meta_x', 0);
        setCreateField('create_meta_y', 0);
        setCreateField('create_meta_w', 48);
        setCreateField('create_meta_h', 30);
        setCreateField('create_heatmap_preview', 'Sin slot disponible');
        createSummaryCard.innerHTML = 'No hay slots disponibles para crear una ubicación tipo rack.';
        createSubmitBtn.disabled = true;
        return;
      }

      setCreateField('create_code', slot.code);
      setCreateField('create_name', slot.label);
      setCreateField('create_rack', slot.rack);
      setCreateField('create_level', slot.level);
      setCreateField('create_bin', slot.position);
      setCreateField('create_meta_x', slot.x);
      setCreateField('create_meta_y', slot.y);
      setCreateField('create_meta_w', slot.w);
      setCreateField('create_meta_h', slot.h);
      setCreateField('create_heatmap_preview', `X ${slot.x} · Y ${slot.y} · ${slot.w}×${slot.h}`);

      createSummaryCard.innerHTML = `
        <strong>Slot seleccionado:</strong> ${esc(slot.label)}<br>
        <strong>Código:</strong> ${esc(slot.code)}<br>
        <strong>Heatmap:</strong> ${esc(`X ${slot.x} · Y ${slot.y} · W ${slot.w} · H ${slot.h}`)}
      `;
      createSubmitBtn.disabled = false;
      return;
    }

    createSlotWrap.style.display = 'none';
    const zone = getCreateZone(type);

    if (!zone || zone.available === false) {
      setCreateField('create_code', '');
      setCreateField('create_name', '');
      setCreateField('create_rack', '');
      setCreateField('create_level', '');
      setCreateField('create_bin', '');
      setCreateField('create_meta_x', 0);
      setCreateField('create_meta_y', 0);
      setCreateField('create_meta_w', 64);
      setCreateField('create_meta_h', 42);
      setCreateField('create_heatmap_preview', 'Zona no disponible');
      createSummaryCard.innerHTML = 'La zona seleccionada ya está ocupada.';
      createSubmitBtn.disabled = true;
      return;
    }

    setCreateField('create_code', zone.code);
    setCreateField('create_name', zone.zone_label);
    setCreateField('create_rack', '');
    setCreateField('create_level', '');
    setCreateField('create_bin', '');
    setCreateField('create_meta_x', zone.x);
    setCreateField('create_meta_y', zone.y);
    setCreateField('create_meta_w', zone.w);
    setCreateField('create_meta_h', zone.h);
    setCreateField('create_heatmap_preview', `X ${zone.x} · Y ${zone.y} · ${zone.w}×${zone.h}`);

    createSummaryCard.innerHTML = `
      <strong>Zona disponible:</strong> ${esc(zone.zone_label)}<br>
      <strong>Código sugerido:</strong> ${esc(zone.code)}<br>
      <strong>Heatmap:</strong> ${esc(`X ${zone.x} · Y ${zone.y} · W ${zone.w} · H ${zone.h}`)}
    `;
    createSubmitBtn.disabled = false;
  }

  async function prepareCreateModal(){
    syncWarehouseHiddenInputs();
    createForm.reset();
    setCreateField('create_warehouse_id', String(getWarehouseId()));
    setCreateField('create_meta_x', 0);
    setCreateField('create_meta_y', 0);
    setCreateField('create_meta_w', 48);
    setCreateField('create_meta_h', 30);
    setCreateField('create_heatmap_preview', '');
    createSummaryCard.innerHTML = 'Cargando opciones disponibles...';
    createSubmitBtn.disabled = true;

    try {
      CREATE_OPTIONS = await loadAvailableOptions();
      fillCreateTypeOptions();
      populateCreateSlotSelect();
      updateCreateSelectionView();
      openModal(createModal);
    } catch (e) {
      Swal.fire({
        icon:'error',
        title:'No se pudieron cargar opciones',
        text:e.message || 'Ocurrió un problema al consultar slots disponibles.',
        confirmButtonText:'Aceptar',
        customClass:{
          popup:'swal-minimal',
          title:'swal-title-clean',
          htmlContainer:'swal-text-clean',
          confirmButton:'swal-btn-confirm'
        }
      });
    }
  }

  function openModal(el){
    el.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(el){
    el.classList.remove('is-open');
    if(!document.querySelector('.loc-modal-backdrop.is-open')){
      document.body.style.overflow = '';
    }
  }

  function fillDetailFromItem(item){
    if (!item) return;

    detailTitle.textContent = `Ubicación ${item.code || ''}`;
    detailSub.textContent = `${item.zone_label || 'Zona'} · ${prettyType(item.type)}`;

    const percent = occupancyPercent(item);

    detailSummary.innerHTML = `
      <div class="loc-summary-item">
        <div class="loc-summary-label">Código</div>
        <div class="loc-summary-value">${esc(item.code || '—')}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Nombre</div>
        <div class="loc-summary-value">${esc(item.name || 'Sin nombre')}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Zona</div>
        <div class="loc-summary-value">${esc(item.zone_label || 'General')}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Tipo</div>
        <div class="loc-summary-value">${esc(prettyType(item.type))}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Posición</div>
        <div class="loc-summary-value">${esc(buildPosition(item))}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Heatmap</div>
        <div class="loc-summary-value">${esc(heatmapLabel(item))}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Capacidad</div>
        <div class="loc-summary-value">${esc(item.max_capacity || 0)}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Valor métrico</div>
        <div class="loc-summary-value">${esc(item.value || 0)}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Ocupación</div>
        <div class="loc-summary-value">${esc(percent)}%</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Estado</div>
        <div class="loc-summary-value">${statusBadge(item.status)}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Producto</div>
        <div class="loc-summary-value">${esc(item.product_name || '—')}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">SKU</div>
        <div class="loc-summary-value">${esc(item.product_sku || '—')}</div>
      </div>

      <div class="loc-summary-item">
        <div class="loc-summary-label">Notas</div>
        <div class="loc-summary-value">${esc(item.notes || '—')}</div>
      </div>
    `;

    if (item.product_name || Number(item.value || 0) > 0) {
      detailInventory.innerHTML = `
        <table class="loc-inv-table">
          <thead>
            <tr>
              <th>Producto</th>
              <th>SKU</th>
              <th>Valor</th>
              <th>Tipo métrica</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>${esc(item.product_name || 'Sin producto principal')}</td>
              <td>${esc(item.product_sku || '—')}</td>
              <td>${esc(item.value || 0)}</td>
              <td>${esc(metricSelect?.value || 'inv_qty')}</td>
            </tr>
          </tbody>
        </table>
      `;
    } else {
      detailInventory.innerHTML = `<div class="loc-empty-card">Esta ubicación no tiene inventario principal visible para la métrica actual.</div>`;
    }
  }

  async function openDetail(id){
    const item = getItemById(id);

    detailTitle.textContent = 'Ubicación';
    detailSub.textContent = 'Cargando...';
    detailSummary.innerHTML = '';
    detailInventory.innerHTML = `<div class="loc-empty-card">Cargando inventario...</div>`;
    openModal(detailModal);

    if (item) {
      fillDetailFromItem(item);
    }

    try{
      const res = await fetch(`${SHOW_BASE}/${encodeURIComponent(id)}`, {
        headers:{ 'Accept':'application/json' }
      });

      const data = await res.json().catch(() => ({}));

      if(!data?.ok) return;

      const location  = data.location || {};
      const summary   = data.summary || {};
      const inventory = Array.isArray(data.inventory) ? data.inventory : [];

      if (location?.code) {
        detailTitle.textContent = `Ubicación ${location.code || ''}`;
      }

      if (summary?.zone || location?.type) {
        detailSub.textContent = `${summary.zone || item?.zone_label || 'Zona'} · ${location.type || item?.type || 'Tipo'}`;
      }

      if (inventory.length) {
        detailInventory.innerHTML = `
          <table class="loc-inv-table">
            <thead>
              <tr>
                <th>Producto</th>
                <th>SKU</th>
                <th>Cantidad</th>
                <th>Ubicación principal</th>
              </tr>
            </thead>
            <tbody>
              ${inventory.map(row => `
                <tr>
                  <td>${esc(row?.item?.name || row?.name || 'Producto')}</td>
                  <td>${esc(row?.item?.sku || row?.sku || '—')}</td>
                  <td>${esc(row?.qty || row?.quantity || 0)}</td>
                  <td>${(row?.item?.primary_location_id || row?.primary_location_id) ? 'Sí' : 'No'}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        `;
      }
    }catch(e){}
  }

  function openQr(id){
    const item = getItemById(id) || {};

    const code = item.code || '—';
    const zone = item.zone_label || 'General';
    const position = buildQrPosition(item);
    const qrUrl = `${PAGE_BASE}/${encodeURIComponent(id)}/qr`;

    qrTitle.textContent = `QR Ubicación ${code}`;
    qrImage.src = qrUrl;
    qrImage.alt = `QR de ubicación ${code}`;

    qrCodeValue.textContent = code;
    qrZoneValue.textContent = zone;
    qrPosValue.textContent = position;
    qrStatusValue.innerHTML = statusInline(item.status);

    qrDownloadBtn.href = qrUrl;
    qrDownloadBtn.setAttribute('download', `${code}-qr.png`);

    openModal(qrModal);
  }

  function openEdit(id){
    const item = getItemById(id);
    if (!item) return;

    document.getElementById('edit_id').value = item.id || '';
    document.getElementById('edit_code').value = item.code || '';
    document.getElementById('edit_name').value = item.name || '';
    document.getElementById('edit_type').value = item.type || 'bin';
    document.getElementById('edit_aisle').value = item.aisle || '';
    document.getElementById('edit_stand').value = item.stand || '';
    document.getElementById('edit_section').value = item.section || '';
    document.getElementById('edit_rack').value = item.rack || '';
    document.getElementById('edit_level').value = item.level || '';
    document.getElementById('edit_bin').value = item.bin || '';
    document.getElementById('edit_meta_x').value = Number(item.x || 0);
    document.getElementById('edit_meta_y').value = Number(item.y || 0);
    document.getElementById('edit_meta_w').value = Number(item.w || 1);
    document.getElementById('edit_meta_h').value = Number(item.h || 1);
    document.getElementById('edit_meta_capacity').value = Number(item.max_capacity || 0);
    document.getElementById('edit_meta_notes').value = item.notes || '';
    document.getElementById('edit_meta_color').value = item.color || '';
    document.getElementById('edit_warehouse_id').value = String(getWarehouseId());

    openModal(editModal);
  }

  function buildUpsertPayload(fd){
    const capacity = Number(fd.get('meta_capacity') || 0);

    return {
      id: fd.get('id') || null,
      warehouse_id: Number(fd.get('warehouse_id') || getWarehouseId() || 1),
      code: String(fd.get('code') || '').trim() || null,
      name: String(fd.get('name') || '').trim() || null,
      type: String(fd.get('type') || 'bin').trim(),
      aisle: String(fd.get('aisle') || '').trim() || null,
      section: String(fd.get('section') || '').trim() || null,
      stand: String(fd.get('stand') || '').trim() || null,
      rack: String(fd.get('rack') || '').trim() || null,
      level: String(fd.get('level') || '').trim() || null,
      bin: String(fd.get('bin') || '').trim() || null,
      meta: {
        x: Number(fd.get('meta_x') || 0),
        y: Number(fd.get('meta_y') || 0),
        w: Number(fd.get('meta_w') || 1),
        h: Number(fd.get('meta_h') || 1),
        color: String(fd.get('meta_color') || '').trim() || null,
        notes: String(fd.get('meta_notes') || '').trim() || null,
        capacity: capacity,
        max_capacity: capacity
      }
    };
  }

  async function submitUpsert(payload){
    const res = await fetch(API_LAYOUT_UPSERT, {
      method:'POST',
      headers:{
        'Content-Type':'application/json',
        'Accept':'application/json',
        'X-CSRF-TOKEN': csrfToken()
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json().catch(() => ({}));

    if(!res.ok || !data?.ok){
      throw new Error(data?.message || data?.error || 'No se pudo guardar la ubicación.');
    }

    return data;
  }

  async function createLocation(ev){
    ev.preventDefault();

    const fd = new FormData(ev.currentTarget);
    const payload = buildUpsertPayload(fd);

    try{
      await submitUpsert(payload);

      closeModal(createModal);
      await load();

      Swal.fire({
        icon:'success',
        title:'Ubicación creada',
        text:'La ubicación ya quedó sincronizada con el heatmap.',
        timer:1800,
        showConfirmButton:false,
        customClass:{
          popup:'swal-minimal',
          title:'swal-title-clean',
          htmlContainer:'swal-text-clean'
        }
      });
    }catch(e){
      Swal.fire({
        icon:'error',
        title:'No se pudo guardar',
        text:e.message || 'Revisa la información e intenta de nuevo.',
        confirmButtonText:'Aceptar',
        customClass:{
          popup:'swal-minimal',
          title:'swal-title-clean',
          htmlContainer:'swal-text-clean',
          confirmButton:'swal-btn-confirm'
        }
      });
    }
  }

  async function updateLocation(ev){
    ev.preventDefault();

    const fd = new FormData(ev.currentTarget);
    const payload = buildUpsertPayload(fd);

    try{
      await submitUpsert(payload);

      closeModal(editModal);
      await load();

      Swal.fire({
        icon:'success',
        title:'Cambios guardados',
        text:'La ubicación fue actualizada en el layout real.',
        timer:1700,
        showConfirmButton:false,
        customClass:{
          popup:'swal-minimal',
          title:'swal-title-clean',
          htmlContainer:'swal-text-clean'
        }
      });
    }catch(e){
      Swal.fire({
        icon:'error',
        title:'No se pudo actualizar',
        text:e.message || 'Revisa la información e intenta nuevamente.',
        confirmButtonText:'Aceptar',
        customClass:{
          popup:'swal-minimal',
          title:'swal-title-clean',
          htmlContainer:'swal-text-clean',
          confirmButton:'swal-btn-confirm'
        }
      });
    }
  }

  async function destroyLocation(id){
    const item = getItemById(id);
    const label = item?.code || 'esta ubicación';

    const result = await Swal.fire({
      title:'Eliminar ubicación',
      text:`Se eliminará ${label}. Esta acción no se puede deshacer.`,
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Sí, eliminar',
      cancelButtonText:'Cancelar',
      reverseButtons:true,
      focusCancel:true,
      customClass:{
        popup:'swal-minimal',
        title:'swal-title-clean',
        htmlContainer:'swal-text-clean',
        confirmButton:'swal-btn-confirm',
        cancelButton:'swal-btn-cancel'
      }
    });

    if (!result.isConfirmed) return;

    try{
      const res = await fetch(API_LAYOUT_DELETE, {
        method:'POST',
        headers:{
          'Content-Type':'application/json',
          'Accept':'application/json',
          'X-CSRF-TOKEN': csrfToken()
        },
        body: JSON.stringify({
          warehouse_id: getWarehouseId(),
          id: id
        })
      });

      const data = await res.json().catch(() => ({}));

      if(!res.ok || !data?.ok){
        throw new Error(data?.message || data?.error || 'La ubicación no pudo eliminarse.');
      }

      await load();

      Swal.fire({
        icon:'success',
        title:'Ubicación eliminada',
        text:'El registro fue eliminado correctamente.',
        timer:1600,
        showConfirmButton:false,
        customClass:{
          popup:'swal-minimal',
          title:'swal-title-clean',
          htmlContainer:'swal-text-clean'
        }
      });
    }catch(e){
      Swal.fire({
        icon:'error',
        title:'No se pudo eliminar',
        text:e.message || 'Ocurrió un problema al eliminar la ubicación.',
        confirmButtonText:'Aceptar',
        customClass:{
          popup:'swal-minimal',
          title:'swal-title-clean',
          htmlContainer:'swal-text-clean',
          confirmButton:'swal-btn-confirm'
        }
      });
    }
  }

  qInp?.addEventListener('input', applyFilter);
  filterZone?.addEventListener('change', applyFilter);
  metricSelect?.addEventListener('change', load);
  warehouseSelect?.addEventListener('change', async () => {
    syncWarehouseHiddenInputs();
    await load();
  });

  createType?.addEventListener('change', updateCreateSelectionView);
  createSlotSelect?.addEventListener('change', updateCreateSelectionView);

  document.getElementById('btnOpenCreate')?.addEventListener('click', prepareCreateModal);

  createForm?.addEventListener('submit', createLocation);
  editForm?.addEventListener('submit', updateLocation);

  document.addEventListener('click', (e) => {
    const closeBtn = e.target.closest('[data-close]');
    if (closeBtn) {
      const sel = closeBtn.getAttribute('data-close');
      const modal = document.querySelector(sel);
      if (modal) closeModal(modal);
      return;
    }

    if (e.target.classList.contains('loc-modal-backdrop')) {
      closeModal(e.target);
      return;
    }

    const btn = e.target.closest('[data-act]');
    if(!btn) return;

    const id = btn.getAttribute('data-id');
    const act = btn.getAttribute('data-act');

    if(act === 'show'){
      openDetail(id);
      return;
    }

    if(act === 'qr'){
      openQr(id);
      return;
    }

    if(act === 'edit'){
      openEdit(id);
      return;
    }

    if(act === 'delete'){
      destroyLocation(id);
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.loc-modal-backdrop.is-open').forEach(closeModal);
    }
  });

  syncWarehouseHiddenInputs();
  load();
})();
</script>
@endpush