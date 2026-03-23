@extends('layouts.app')

@section('title', 'WMS · Heatmap 3D')

@section('content')
@php
  $whId = (int)($warehouseId ?? 1);
@endphp

<div class="hm-wrap">
  <div class="hm-head">
    <div>
      <div class="hm-title">Mapa de calor 3D</div>
      <div class="hm-sub">Vista interactiva profesional del almacén con recorrido entre pasillos.</div>
    </div>

    <div class="hm-actions">
      <a class="btn btn-ghost" href="{{ route('admin.wms.home') }}">← WMS</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.layout.editor', ['warehouse_id'=>$whId]) }}">Layout</a>

      <form method="GET" action="{{ route('admin.wms.heatmap.view') }}" class="wh-form">
        <select name="warehouse_id" class="inp" onchange="this.form.submit()">
          @foreach(($warehouses ?? []) as $w)
            <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>
              {{ $w->name ?? ('Bodega #'.$w->id) }}
            </option>
          @endforeach
        </select>
      </form>

      <select id="metric" class="inp">
        <option value="inv_qty" selected>inv_qty</option>
        <option value="primary_stock">primary_stock</option>
      </select>

      <select id="viewPreset" class="inp">
        <option value="iso">Vista isométrica</option>
        <option value="front">Vista frontal</option>
        <option value="aisle">Pasillo central</option>
        <option value="top">Vista superior</option>
      </select>

      <button class="btn btn-ghost" type="button" id="btnMode">Modo: Órbita</button>
      <button class="btn btn-ghost" type="button" id="btnLabels">Etiquetas: ON</button>
      <button class="btn btn-ghost" type="button" id="btnZones">Zonas: ON</button>
      <button class="btn btn-primary" id="btnReload" type="button">Actualizar</button>
    </div>
  </div>

  <div class="hm-legend-row">
    <span class="hm-legend-title">
      Mouse: rotar o mirar · Scroll: zoom · Click: detalle · WASD: desplazamiento · Shift: movimiento rápido
    </span>
  </div>

  <div class="hm-legend">
    <div class="hm-legend-item"><span class="lg lg-low"></span> Bajo (&lt;30%)</div>
    <div class="hm-legend-item"><span class="lg lg-mid"></span> Medio (30-60%)</div>
    <div class="hm-legend-item"><span class="lg lg-high"></span> Alto (60-90%)</div>
    <div class="hm-legend-item"><span class="lg lg-full"></span> Lleno (&gt;90%)</div>
    <div class="hm-legend-item"><span class="lg lg-empty"></span> Vacío</div>
  </div>

  <div class="hm-card">
    <div class="hm-meta" id="metaLine">Cargando...</div>

    <div class="hm-toolbar">
      <div class="hm-toolbar-group">
        <button class="tool-btn" type="button" id="btnZoomIn">+</button>
        <button class="tool-btn" type="button" id="btnZoomOut">−</button>
        <button class="tool-btn" type="button" id="btnReset">Reset</button>
      </div>

      <div class="hm-toolbar-group hm-status" id="hmStatusBar">
        <span>Modo: <strong id="hmModeText">Órbita</strong></span>
        <span>Posición: <strong id="hmPosText">0, 0, 0</strong></span>
        <span>Rotación: <strong id="hmRotText">0°, 0°</strong></span>
      </div>
    </div>

    <div class="hm-stage-wrap">
      <div class="hm-empty" id="hmEmpty" style="display:none;">
        <div>
          <div class="hm-empty-tt">No hay ubicaciones para renderizar</div>
          <div class="hm-empty-sub">
            Verifica que existan ubicaciones en esa bodega y que tengan rack, level o bin, o zonas como picking / fast_flow / incoming.
          </div>
        </div>
      </div>

      <div class="hm-stage" id="hmStage">
        <canvas id="hmCanvas3d"></canvas>

        <div class="hm-hint">
          <span>W A S D = movimiento</span>
          <span>Mouse = rotar / mirar</span>
          <span>Scroll = zoom</span>
          <span>Click = detalle</span>
        </div>
      </div>

      <div class="hm-tip" id="hmTip" style="display:none;">
        <div class="hm-tip-code" id="hmTipCode">—</div>
        <div class="hm-tip-name" id="hmTipName" style="display:none;"></div>

        <div class="hm-tip-grid">
          <div class="hm-tip-item">
            <span class="lab">Métrica</span>
            <span class="val" id="hmTipMetric">—</span>
          </div>
          <div class="hm-tip-item">
            <span class="lab">Valor</span>
            <span class="val" id="hmTipVal">—</span>
          </div>
          <div class="hm-tip-item">
            <span class="lab">Rack</span>
            <span class="val" id="hmTipRack">—</span>
          </div>
          <div class="hm-tip-item">
            <span class="lab">Nivel</span>
            <span class="val" id="hmTipLevel">—</span>
          </div>
          <div class="hm-tip-item">
            <span class="lab">Posición</span>
            <span class="val" id="hmTipPos">—</span>
          </div>
          <div class="hm-tip-item">
            <span class="lab">Zona</span>
            <span class="val" id="hmTipZone">—</span>
          </div>
        </div>

        <div class="hm-tip-sub" id="hmTipSub">Click para abrir ubicación.</div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --bg:#030817;
    --panel:#08142c;
    --panel-2:#0b1734;
    --line:#1e293b;
    --text:#e2e8f0;
    --muted:#94a3b8;
    --blue:#3b82f6;
  }

  .hm-wrap{max-width:1320px;margin:0 auto;padding:18px 14px 26px}
  .hm-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
  .hm-title{font-weight:900;color:#0f172a;font-size:1.2rem}
  .hm-sub{color:#64748b;font-size:.9rem;margin-top:2px}
  .hm-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

  .btn{
    border:0;border-radius:999px;padding:10px 14px;font-weight:900;font-size:.9rem;
    cursor:pointer;display:inline-flex;gap:8px;align-items:center;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease;
    text-decoration:none
  }
  .btn:hover{transform:translateY(-1px)}
  .btn-primary{background:#2563eb;color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.25)}
  .btn-ghost{background:#fff;border:1px solid #e5e7eb;color:#0f172a;box-shadow:0 10px 25px rgba(2,6,23,.04)}
  .inp{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;min-height:42px}

  .hm-legend-row{margin-top:14px}
  .hm-legend-title{font-size:.95rem;color:#cbd5e1}

  .hm-legend{
    margin-top:10px;
    display:flex;
    gap:18px;
    flex-wrap:wrap;
    align-items:center;
    color:#cbd5e1;
    font-size:.9rem;
  }
  .hm-legend-item{display:flex;align-items:center;gap:8px}
  .lg{width:16px;height:16px;border-radius:6px;display:inline-block}
  .lg-low{background:#10b981}
  .lg-mid{background:#3b82f6}
  .lg-high{background:#f97316}
  .lg-full{background:#ef4444}
  .lg-empty{background:#475569}

  .hm-card{
    margin-top:14px;
    background:var(--bg);
    border:1px solid #0f172a;
    border-radius:20px;
    overflow:hidden;
    box-shadow:0 25px 60px rgba(2,6,23,.25);
  }

  .hm-meta{
    padding:12px 16px;
    color:#94a3b8;
    font-size:.86rem;
    border-bottom:1px solid rgba(30,41,59,.7);
    background:rgba(2,6,23,.75);
  }

  .hm-toolbar{
    display:flex;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    align-items:center;
    padding:10px 16px;
    border-bottom:1px solid rgba(30,41,59,.7);
    background:rgba(2,6,23,.58);
  }

  .hm-toolbar-group{
    display:flex;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
  }

  .tool-btn{
    min-width:42px;
    height:38px;
    border-radius:10px;
    border:1px solid rgba(71,85,105,.8);
    background:rgba(30,41,59,.95);
    color:#fff;
    font-weight:900;
    cursor:pointer;
    padding:0 12px;
  }
  .tool-btn:hover{background:#334155}

  .hm-status{
    color:#cbd5e1;
    font-size:.82rem;
  }
  .hm-status span{
    background:rgba(15,23,42,.65);
    border:1px solid rgba(51,65,85,.8);
    border-radius:999px;
    padding:7px 10px;
  }

  .hm-stage-wrap{position:relative}
  .hm-stage{
    position:relative;
    height:74vh;
    min-height:640px;
    background:
      radial-gradient(circle at top, rgba(59,130,246,.10), transparent 36%),
      linear-gradient(180deg,#04102a 0%, #07152f 100%);
    overflow:hidden;
  }

  #hmCanvas3d{
    display:block;
    width:100%;
    height:100%;
    cursor:grab;
    touch-action:none;
  }

  .hm-empty{
    position:absolute;inset:0;
    display:flex;align-items:center;justify-content:center;
    background:rgba(2,6,23,.78);
    backdrop-filter:blur(6px);
    z-index:20;
    text-align:center;
    padding:24px;
  }
  .hm-empty-tt{font-weight:900;color:#fff;font-size:1.06rem}
  .hm-empty-sub{margin-top:8px;color:#94a3b8;font-size:.9rem;line-height:1.45}

  .hm-tip{
    position:fixed;
    z-index:9999;
    width:320px;
    background:rgba(15,23,42,.96);
    border:1px solid rgba(71,85,105,.7);
    border-radius:16px;
    box-shadow:0 24px 60px rgba(2,6,23,.3);
    backdrop-filter:blur(10px);
    padding:14px;
    pointer-events:none;
    color:#fff;
  }
  .hm-tip-code{font-weight:900;font-size:1rem;color:#60a5fa}
  .hm-tip-name{margin-top:4px;font-size:.85rem;color:#e2e8f0;line-height:1.35}
  .hm-tip-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:12px}
  .hm-tip-item{background:rgba(30,41,59,.75);border:1px solid rgba(51,65,85,.9);border-radius:12px;padding:8px 10px}
  .hm-tip-item .lab{display:block;font-size:.68rem;color:#94a3b8;margin-bottom:3px}
  .hm-tip-item .val{display:block;font-size:.84rem;font-weight:800;color:#fff}
  .hm-tip-sub{margin-top:10px;font-size:.76rem;color:#60a5fa}

  .hm-hint{
    position:absolute;
    left:18px;
    bottom:16px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    font-size:.76rem;
    color:#94a3b8;
    z-index:5;
    pointer-events:none;
  }
  .hm-hint span{
    background:rgba(2,6,23,.42);
    border:1px solid rgba(30,41,59,.75);
    border-radius:999px;
    padding:7px 10px;
  }

  @media (max-width: 900px){
    .hm-stage{min-height:520px}
    .hm-hint{display:none}
    .hm-tip{width:min(320px, calc(100vw - 20px))}
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const whId = @json((int)$whId);
  const dataUrl = @json(route('admin.wms.heatmap.data'));
  const locationPageBase = @json(url('/admin/wms/locations'));

  const CELL_W = 54;
  const CELL_H = 34;
  const CELL_D = 34;

  const RACK_SPACING_Z = 210;
  const RACK_START_Z = -220;
  const FLOOR_Y = 130;

  const ZONE_Z_MAP = {
    picking: 320,
    fast_flow: 400,
    incoming: 480,
    dispatch: 560,
  };

  const canvas = document.getElementById('hmCanvas3d');
  const stage = document.getElementById('hmStage');
  const metricSel = document.getElementById('metric');
  const metaLine = document.getElementById('metaLine');
  const empty = document.getElementById('hmEmpty');
  const btnReload = document.getElementById('btnReload');
  const btnZoomIn = document.getElementById('btnZoomIn');
  const btnZoomOut = document.getElementById('btnZoomOut');
  const btnReset = document.getElementById('btnReset');
  const btnMode = document.getElementById('btnMode');
  const btnLabels = document.getElementById('btnLabels');
  const btnZones = document.getElementById('btnZones');
  const viewPreset = document.getElementById('viewPreset');

  const hmModeText = document.getElementById('hmModeText');
  const hmPosText = document.getElementById('hmPosText');
  const hmRotText = document.getElementById('hmRotText');

  const tip = document.getElementById('hmTip');
  const tipCode = document.getElementById('hmTipCode');
  const tipName = document.getElementById('hmTipName');
  const tipMetric = document.getElementById('hmTipMetric');
  const tipVal = document.getElementById('hmTipVal');
  const tipRack = document.getElementById('hmTipRack');
  const tipLevel = document.getElementById('hmTipLevel');
  const tipPos = document.getElementById('hmTipPos');
  const tipZone = document.getElementById('hmTipZone');
  const tipSub = document.getElementById('hmTipSub');

  let locations = [];
  let rackGroups = [];
  let zoneGroups = [];
  let interactiveCells = [];
  let hoveredCell = null;
  let dragging = false;
  let movedWhileDragging = false;
  let lastMouse = { x:0, y:0 };
  let keys = new Set();
  let raf = null;

  const state = {
    mode: 'orbit',
    showLabels: true,
    showZones: true,
    zoom: 1.0,
    focal: 980,
    panX: 0,
    panY: 16,
    panZ: 0,
    rotX: -18,
    rotY: 28
  };

  function clamp(v, min, max){
    return Math.max(min, Math.min(max, v));
  }

  function updateStatusBar(){
    hmModeText.textContent = state.mode === 'orbit' ? 'Órbita' : 'Recorrido';
    hmPosText.textContent = `${Math.round(state.panX)}, ${Math.round(state.panY)}, ${Math.round(state.panZ)}`;
    hmRotText.textContent = `${Math.round(state.rotX)}°, ${Math.round(state.rotY)}°`;

    btnMode.textContent = state.mode === 'orbit' ? 'Modo: Órbita' : 'Modo: Recorrido';
    btnLabels.textContent = `Etiquetas: ${state.showLabels ? 'ON' : 'OFF'}`;
    btnZones.textContent = `Zonas: ${state.showZones ? 'ON' : 'OFF'}`;
  }

  function applyPreset(name){
    if (name === 'front') {
      state.mode = 'orbit';
      state.zoom = 0.98;
      state.panX = 0;
      state.panY = 12;
      state.panZ = -20;
      state.rotX = -8;
      state.rotY = 0;
    } else if (name === 'aisle') {
      state.mode = 'walk';
      state.zoom = 1.18;
      state.panX = 0;
      state.panY = 10;
      state.panZ = -50;
      state.rotX = -4;
      state.rotY = 0;
    } else if (name === 'top') {
      state.mode = 'orbit';
      state.zoom = 0.88;
      state.panX = 0;
      state.panY = 40;
      state.panZ = 20;
      state.rotX = -58;
      state.rotY = 0;
    } else {
      state.mode = 'orbit';
      state.zoom = 1.0;
      state.panX = 0;
      state.panY = 16;
      state.panZ = 0;
      state.rotX = -18;
      state.rotY = 28;
    }

    updateStatusBar();
    hideTip();
    draw();
  }

  function normalizeLocation(loc){
    return {
      id: Number(loc.id || 0),
      zone: String(loc.zone || 'rack').toLowerCase(),
      zone_label: String(loc.zone_label || 'General'),
      location_code: String(loc.location_code || loc.code || '—'),
      code: String(loc.code || loc.location_code || '—'),
      name: String(loc.name || loc.display_name || loc.code || ''),
      product_name: String(loc.product_name || ''),
      product_sku: String(loc.product_sku || ''),
      quantity: Number(loc.value ?? loc.qty_total ?? 0),
      max_capacity: Number(loc.max_capacity ?? loc.capacity ?? 100),
      status: String(loc.status || 'empty').toLowerCase(),
      rack_number: Number(loc.rack_number || loc.rack || 0),
      level: Number(loc.level || 0),
      position: Number(loc.position || loc.bin || 0),
      x: Number(loc.x || 0),
      y: Number(loc.y || 0),
      w: Number(loc.w || 48),
      h: Number(loc.h || 30),
      color: String(loc.color || '').trim(),
      notes: String(loc.notes || ''),
      inferred: !!loc.inferred,
    };
  }

  function buildRackGroups(){
    const map = new Map();

    locations
      .filter(l => l.zone === 'rack' && l.rack_number > 0 && l.level > 0 && l.position > 0)
      .forEach(loc => {
        if (!map.has(loc.rack_number)) map.set(loc.rack_number, []);
        map.get(loc.rack_number).push(loc);
      });

    return [...map.entries()]
      .sort((a, b) => a[0] - b[0])
      .map(([rackNumber, items]) => {
        items.sort((a, b) => {
          if (a.level !== b.level) return a.level - b.level;
          if (a.position !== b.position) return a.position - b.position;
          return a.id - b.id;
        });

        const positions = [...new Set(items.map(x => x.position))].sort((a, b) => a - b);
        const levels = [...new Set(items.map(x => x.level))].sort((a, b) => a - b);

        const minPos = positions[0] || 1;
        const maxPos = positions[positions.length - 1] || 1;
        const maxLevel = levels[levels.length - 1] || 1;

        return {
          rackNumber,
          items,
          positions,
          levels,
          minPos,
          maxPos,
          maxLevel,
          width: Math.max(CELL_W, ((maxPos - minPos) + 1) * CELL_W),
          height: Math.max(CELL_H, maxLevel * CELL_H),
        };
      });
  }

  function buildZoneGroups(){
    const order = ['picking', 'fast_flow', 'incoming', 'dispatch'];

    return order
      .map(key => ({
        key,
        items: locations
          .filter(l => l.zone === key)
          .sort((a, b) => (a.x - b.x) || (a.y - b.y) || (a.id - b.id))
      }))
      .filter(group => group.items.length > 0);
  }

  function parseColor(str){
    const value = String(str || '').trim();
    if (!value) return null;

    const hex = value.replace('#', '').trim();

    if (/^[0-9a-fA-F]{3}$/.test(hex)) {
      return {
        r: parseInt(hex[0] + hex[0], 16) / 255,
        g: parseInt(hex[1] + hex[1], 16) / 255,
        b: parseInt(hex[2] + hex[2], 16) / 255,
      };
    }

    if (/^[0-9a-fA-F]{6}$/.test(hex)) {
      return {
        r: parseInt(hex.slice(0, 2), 16) / 255,
        g: parseInt(hex.slice(2, 4), 16) / 255,
        b: parseInt(hex.slice(4, 6), 16) / 255,
      };
    }

    const rgbMatch = value.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i);
    if (rgbMatch) {
      return {
        r: Math.min(255, Number(rgbMatch[1])) / 255,
        g: Math.min(255, Number(rgbMatch[2])) / 255,
        b: Math.min(255, Number(rgbMatch[3])) / 255,
      };
    }

    return null;
  }

  function rgba(c, shade = 1, alpha = 1){
    return `rgba(${Math.round(c.r * 255 * shade)},${Math.round(c.g * 255 * shade)},${Math.round(c.b * 255 * shade)},${alpha})`;
  }

  function zoneBaseColor(zone){
    if (zone === 'picking') return { r:0.10, g:0.63, b:0.31 };
    if (zone === 'fast_flow') return { r:0.88, g:0.56, b:0.08 };
    if (zone === 'incoming') return { r:0.22, g:0.46, b:0.88 };
    if (zone === 'dispatch') return { r:0.88, g:0.25, b:0.25 };
    return { r:0.45, g:0.51, b:0.60 };
  }

  function occupancyColor(quantity, status, maxCapacity){
    if (status === 'reserved') return { r:0.68, g:0.54, b:0.12 };
    if (!quantity || status === 'empty') return { r:0.24, g:0.28, b:0.33 };

    const ratio = quantity / Math.max(1, maxCapacity || 100);

    if (ratio >= 0.90) return { r:0.90, g:0.24, b:0.24 };
    if (ratio >= 0.60) return { r:0.92, g:0.56, b:0.10 };
    if (ratio >= 0.30) return { r:0.23, g:0.52, b:0.92 };
    return { r:0.10, g:0.68, b:0.38 };
  }

  function rackCellColor(loc){
    const custom = parseColor(loc.color);
    if (custom) return custom;
    return occupancyColor(loc.quantity, loc.status, loc.max_capacity);
  }

  function zoneCellColor(loc, zone){
    const custom = parseColor(loc.color);
    if (custom) return custom;

    if (loc.quantity > 0) {
      return occupancyColor(loc.quantity, loc.status, loc.max_capacity);
    }

    return zoneBaseColor(zone);
  }

  function getRackZ(rackNumber){
    return RACK_START_Z + ((rackNumber - 1) * RACK_SPACING_Z);
  }

  function getRackCellX(group, loc){
    const centerPos = (group.minPos + group.maxPos) / 2;
    return (loc.position - centerPos) * CELL_W;
  }

  function getRackCellY(group, loc){
    return 10 + ((group.maxLevel / 2) - loc.level) * CELL_H;
  }

  function worldToCamera(x, y, z){
    x += state.panX;
    y += state.panY;
    z += state.panZ;

    const ry = state.rotY * Math.PI / 180;
    const rx = state.rotX * Math.PI / 180;

    let nx = x * Math.cos(ry) - z * Math.sin(ry);
    let nz = x * Math.sin(ry) + z * Math.cos(ry);
    let ny = y * Math.cos(rx) - nz * Math.sin(rx);
    nz = y * Math.sin(rx) + nz * Math.cos(rx);

    return { x:nx, y:ny, z:nz };
  }

  function project(x, y, z, cx, cy){
    const cam = worldToCamera(x, y, z);
    const denom = state.focal + cam.z;
    const scale = denom <= 10 ? 0.0001 : (state.focal * state.zoom) / denom;

    return {
      px: cx + cam.x * scale,
      py: cy + cam.y * scale,
      depth: cam.z,
      scale
    };
  }

  function boxPoints(x, y, z, w, h, d, cx, cy){
    return [
      project(x - w/2, y - h/2, z - d/2, cx, cy),
      project(x + w/2, y - h/2, z - d/2, cx, cy),
      project(x + w/2, y + h/2, z - d/2, cx, cy),
      project(x - w/2, y + h/2, z - d/2, cx, cy),
      project(x - w/2, y - h/2, z + d/2, cx, cy),
      project(x + w/2, y - h/2, z + d/2, cx, cy),
      project(x + w/2, y + h/2, z + d/2, cx, cy),
      project(x - w/2, y + h/2, z + d/2, cx, cy),
    ];
  }

  function makeBox(x, y, z, w, h, d, color, opts = {}){
    const centerCam = worldToCamera(x, y, z);

    return {
      type: 'box',
      x, y, z, w, h, d,
      color,
      alpha: opts.alpha ?? 1,
      strokeAlpha: opts.strokeAlpha ?? 0.28,
      noStroke: opts.noStroke ?? false,
      label: opts.label ?? null,
      labelSize: opts.labelSize ?? 9,
      labelColor: opts.labelColor ?? 'rgba(255,255,255,.92)',
      location: opts.location ?? null,
      pickRadius: opts.pickRadius ?? 16,
      sortDepth: centerCam.z
    };
  }

  function makeLabel(x, y, z, text, color, size = 12){
    const centerCam = worldToCamera(x, y, z);

    return {
      type: 'label',
      x, y, z, text, color, size,
      sortDepth: centerCam.z
    };
  }

  function drawFace(ctx, pts, indices, shade, color, alpha, strokeAlpha, noStroke){
    ctx.beginPath();
    indices.forEach((vi, i) => {
      if (i === 0) ctx.moveTo(pts[vi].px, pts[vi].py);
      else ctx.lineTo(pts[vi].px, pts[vi].py);
    });
    ctx.closePath();
    ctx.fillStyle = rgba(color, shade, alpha);
    ctx.fill();

    if (!noStroke) {
      ctx.strokeStyle = `rgba(30,41,59,${strokeAlpha})`;
      ctx.lineWidth = 0.8;
      ctx.stroke();
    }
  }

  function renderBox(ctx, primitive, cx, cy){
    const pts = boxPoints(primitive.x, primitive.y, primitive.z, primitive.w, primitive.h, primitive.d, cx, cy);

    const faces = [
      { idx:[0,1,2,3], shade:1.00 },
      { idx:[4,5,6,7], shade:0.82 },
      { idx:[0,1,5,4], shade:0.93 },
      { idx:[2,3,7,6], shade:0.68 },
      { idx:[1,2,6,5], shade:0.86 },
      { idx:[0,3,7,4], shade:0.60 },
    ];

    faces.sort((a, b) => {
      const da = a.idx.reduce((s, i) => s + pts[i].depth, 0) / 4;
      const db = b.idx.reduce((s, i) => s + pts[i].depth, 0) / 4;
      return db - da;
    });

    for (const face of faces) {
      drawFace(ctx, pts, face.idx, face.shade, primitive.color, primitive.alpha, primitive.strokeAlpha, primitive.noStroke);
    }

    const centerPt = project(primitive.x, primitive.y, primitive.z, cx, cy);

    if (primitive.location) {
      interactiveCells.push({
        location: primitive.location,
        cx: centerPt.px,
        cy: centerPt.py,
        radius: Math.max(11, primitive.pickRadius * Math.max(0.65, centerPt.scale)),
        depth: centerPt.depth
      });
    }

    if (state.showLabels && primitive.label) {
      ctx.fillStyle = primitive.labelColor;
      ctx.font = `bold ${Math.max(8, primitive.labelSize * Math.max(0.8, centerPt.scale))}px Inter, Arial, sans-serif`;
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(primitive.label, centerPt.px, centerPt.py + 1);
    }
  }

  function renderLabel(ctx, primitive, cx, cy){
    if (!state.showLabels) return;

    const pt = project(primitive.x, primitive.y, primitive.z, cx, cy);
    if (pt.scale <= 0) return;

    ctx.fillStyle = primitive.color;
    ctx.font = `bold ${Math.max(10, primitive.size * Math.max(0.82, pt.scale))}px Inter, Arial, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(primitive.text, pt.px, pt.py);
  }

  function findCell(mx, my){
    const found = interactiveCells
      .filter(c => {
        const dist = Math.sqrt((mx - c.cx) ** 2 + (my - c.cy) ** 2);
        return dist <= c.radius;
      })
      .sort((a, b) => a.depth - b.depth);

    return found[0] || null;
  }

  function resizeCanvas(){
    const rect = stage.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    const ctx = canvas.getContext('2d');

    canvas.width = Math.max(1, Math.floor(rect.width * dpr));
    canvas.height = Math.max(1, Math.floor(rect.height * dpr));
    canvas.style.width = rect.width + 'px';
    canvas.style.height = rect.height + 'px';

    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(dpr, dpr);
  }

  function drawGround(ctx, cx, cy){
    ctx.strokeStyle = 'rgba(37,99,235,0.10)';
    ctx.lineWidth = 0.7;

    for (let gx = -620; gx <= 620; gx += 44) {
      const p1 = project(gx, FLOOR_Y, -420, cx, cy);
      const p2 = project(gx, FLOOR_Y, 640, cx, cy);
      ctx.beginPath();
      ctx.moveTo(p1.px, p1.py);
      ctx.lineTo(p2.px, p2.py);
      ctx.stroke();
    }

    for (let gz = -420; gz <= 640; gz += 44) {
      const p1 = project(-620, FLOOR_Y, gz, cx, cy);
      const p2 = project(620, FLOOR_Y, gz, cx, cy);
      ctx.beginPath();
      ctx.moveTo(p1.px, p1.py);
      ctx.lineTo(p2.px, p2.py);
      ctx.stroke();
    }

    const aisleLeft1 = project(-260, FLOOR_Y - 1, -340, cx, cy);
    const aisleLeft2 = project(-260, FLOOR_Y - 1, 460, cx, cy);
    const aisleRight1 = project(260, FLOOR_Y - 1, -340, cx, cy);
    const aisleRight2 = project(260, FLOOR_Y - 1, 460, cx, cy);

    ctx.strokeStyle = 'rgba(56,189,248,0.18)';
    ctx.lineWidth = 1.2;

    ctx.beginPath();
    ctx.moveTo(aisleLeft1.px, aisleLeft1.py);
    ctx.lineTo(aisleLeft2.px, aisleLeft2.py);
    ctx.stroke();

    ctx.beginPath();
    ctx.moveTo(aisleRight1.px, aisleRight1.py);
    ctx.lineTo(aisleRight2.px, aisleRight2.py);
    ctx.stroke();
  }

  function buildScene(){
    const items = [];

    items.push(
      makeBox(0, FLOOR_Y - 1, 110, 1200, 2, 1200, { r:0.07, g:0.14, b:0.24 }, {
        alpha: 1,
        strokeAlpha: 0.08,
        noStroke: true
      })
    );

    rackGroups.forEach(group => {
      const rackZ = getRackZ(group.rackNumber);
      const rackWidth = group.width + 18;
      const rackHeight = group.height + 18;

      items.push(
        makeBox(0, 10, rackZ, rackWidth + 10, rackHeight, 8, { r:0.10, g:0.23, b:0.57 }, {
          alpha: 0.97,
          strokeAlpha: 0.16
        })
      );

      for (let pos = group.minPos; pos <= group.maxPos + 1; pos++) {
        const center = (group.minPos + group.maxPos + 1) / 2;
        const colX = (pos - center) * CELL_W;

        items.push(
          makeBox(colX, 10, rackZ, 6, rackHeight, 8, { r:0.15, g:0.33, b:0.72 }, {
            alpha: 0.98,
            strokeAlpha: 0.14
          })
        );
      }

      for (let level = 1; level <= group.maxLevel; level++) {
        const beamY = 10 + ((group.maxLevel / 2) - level) * CELL_H + (CELL_H / 2) - 2;

        items.push(
          makeBox(0, beamY, rackZ, rackWidth + 2, 5, CELL_D + 4, { r:0.92, g:0.62, b:0.10 }, {
            alpha: 0.95,
            strokeAlpha: 0.16
          })
        );
      }

      group.items.forEach(loc => {
        const x = getRackCellX(group, loc);
        const y = getRackCellY(group, loc);
        const isHovered = hoveredCell && loc && hoveredCell.id === loc.id;
        const color = rackCellColor(loc);

        const realW = Math.max(28, Math.min(CELL_W - 4, Number(loc.w || 48)));
        const realH = Math.max(18, Math.min(CELL_H - 2, Number(loc.h || 30)));
        const boxW = isHovered ? realW + 4 : realW;
        const boxH = isHovered ? realH + 4 : realH;

        items.push(
          makeBox(x, y, rackZ, boxW, boxH, CELL_D - 6, color, {
            alpha: isHovered ? 1 : 0.99,
            strokeAlpha: 0.22,
            location: loc,
            pickRadius: 18,
            label: state.showLabels && state.zoom > 0.70
              ? (loc.quantity > 0 ? String(loc.quantity) : String(loc.position))
              : null,
            labelSize: 8
          })
        );
      });

      items.push(
        makeLabel(
          0,
          -(group.maxLevel * CELL_H / 2) - 30,
          rackZ,
          `RACK ${group.rackNumber}`,
          'rgba(226,232,240,.94)',
          13
        )
      );
    });

    if (state.showZones) {
      zoneGroups.forEach(group => {
        const zoneZ = Number(ZONE_Z_MAP[group.key] || 620);
        const baseColor = zoneBaseColor(group.key);
        const count = group.items.length;
        const laneW = Math.max(180, (count * 76) + 90);

        items.push(
          makeBox(0, 104, zoneZ, laneW, 10, 52, baseColor, {
            alpha: 0.42,
            strokeAlpha: 0.10
          })
        );

        group.items.forEach((loc, idx) => {
          const x = (idx - ((count - 1) / 2)) * 76;
          const isHovered = hoveredCell && hoveredCell.id === loc.id;
          const color = zoneCellColor(loc, group.key);

          const zoneW = Math.max(42, Math.min(88, Number(loc.w || 64)));
          const zoneH = Math.max(24, Math.min(56, Number(loc.h || 42)));

          items.push(
            makeBox(x, 84, zoneZ, zoneW, isHovered ? zoneH + 4 : zoneH, 44, color, {
              alpha: isHovered ? 1 : 0.98,
              strokeAlpha: 0.20,
              location: loc,
              pickRadius: 16,
              label: state.showLabels && state.zoom > 0.70
                ? (loc.quantity > 0 ? String(loc.quantity) : loc.location_code)
                : null,
              labelSize: 8
            })
          );
        });

        items.push(
          makeLabel(
            -Math.max(180, (count * 76) + 90) / 2 + 42,
            65,
            zoneZ,
            String(group.items[0]?.zone_label || group.key).toUpperCase(),
            rgba(baseColor, 1, 0.96),
            11
          )
        );
      });
    }

    return items;
  }

  function draw(){
    const ctx = canvas.getContext('2d');
    const W = canvas.width / (window.devicePixelRatio || 1);
    const H = canvas.height / (window.devicePixelRatio || 1);
    const cx = W / 2;
    const cy = H / 2 + 64;

    ctx.clearRect(0, 0, W, H);
    interactiveCells = [];

    drawGround(ctx, cx, cy);

    const scene = buildScene();
    scene.sort((a, b) => b.sortDepth - a.sortDepth);

    for (const item of scene) {
      if (item.type === 'box') renderBox(ctx, item, cx, cy);
      else if (item.type === 'label') renderLabel(ctx, item, cx, cy);
    }

    updateStatusBar();
  }

  function hideTip(){
    tip.style.display = 'none';
  }

  function showTipAt(x, y, loc){
    if (!loc) return;

    tip.style.display = 'block';
    tipCode.textContent = loc.location_code || loc.code || '—';

    if (loc.product_name) {
      tipName.style.display = 'block';
      tipName.textContent = loc.product_name;
    } else {
      tipName.style.display = 'none';
      tipName.textContent = '';
    }

    tipMetric.textContent = metricSel.value || 'inv_qty';
    tipVal.textContent = String(loc.quantity || 0);
    tipRack.textContent = loc.rack_number || '—';
    tipLevel.textContent = loc.level || '—';
    tipPos.textContent = loc.position || '—';
    tipZone.textContent = loc.zone_label || loc.zone || '—';
    tipSub.textContent = loc.product_sku
      ? ('SKU: ' + loc.product_sku + ' · Click para abrir ubicación.')
      : 'Click para abrir ubicación.';

    const pad = 14;
    const width = 320;
    const height = 170;

    let left = x + pad;
    let top = y + pad;

    if (left + width > window.innerWidth - 8) left = x - width - pad;
    if (top + height > window.innerHeight - 8) top = y - height - pad;

    tip.style.left = Math.max(8, left) + 'px';
    tip.style.top = Math.max(8, top) + 'px';
  }

  function getCanvasPos(e){
    const rect = canvas.getBoundingClientRect();
    return { x:e.clientX - rect.left, y:e.clientY - rect.top };
  }

  function handleMouseDown(e){
    dragging = true;
    movedWhileDragging = false;
    lastMouse = { x:e.clientX, y:e.clientY };
    canvas.style.cursor = 'grabbing';
  }

  function handleMouseUp(){
    dragging = false;
    canvas.style.cursor = hoveredCell ? 'pointer' : 'grab';
  }

  function handleMouseMove(e){
    const { x:mx, y:my } = getCanvasPos(e);

    if (dragging) {
      const dx = e.clientX - lastMouse.x;
      const dy = e.clientY - lastMouse.y;

      if (Math.abs(dx) > 1 || Math.abs(dy) > 1) {
        movedWhileDragging = true;
      }

      state.rotY += dx * 0.32;
      state.rotX = clamp(state.rotX + dy * 0.25, -70, 16);

      lastMouse = { x:e.clientX, y:e.clientY };
      hideTip();
      draw();
      return;
    }

    const found = findCell(mx, my);
    hoveredCell = found?.location || null;
    canvas.style.cursor = found ? 'pointer' : 'grab';
    draw();

    if (found?.location) showTipAt(e.clientX, e.clientY, found.location);
    else hideTip();
  }

  function handleClick(e){
    if (movedWhileDragging) return;

    const { x:mx, y:my } = getCanvasPos(e);
    const found = findCell(mx, my);

    if (found?.location?.id) {
      window.location.href = `${locationPageBase}/${encodeURIComponent(found.location.id)}/page`;
    }
  }

  function handleWheel(e){
    e.preventDefault();
    state.zoom = clamp(state.zoom - e.deltaY * 0.0011, 0.50, 2.80);
    hideTip();
    draw();
  }

  function handleTouchStart(e){
    if (e.touches.length === 1) {
      dragging = true;
      movedWhileDragging = false;
      lastMouse = { x:e.touches[0].clientX, y:e.touches[0].clientY };
    }
  }

  function handleTouchMove(e){
    if (e.touches.length === 1 && dragging) {
      const dx = e.touches[0].clientX - lastMouse.x;
      const dy = e.touches[0].clientY - lastMouse.y;

      state.rotY += dx * 0.30;
      state.rotX = clamp(state.rotX + dy * 0.24, -70, 16);

      lastMouse = { x:e.touches[0].clientX, y:e.touches[0].clientY };
      hideTip();
      draw();
    }
  }

  function moveForward(step){
    const angle = state.rotY * Math.PI / 180;
    state.panX -= Math.sin(angle) * step;
    state.panZ -= Math.cos(angle) * step;
  }

  function moveSide(step){
    const angle = state.rotY * Math.PI / 180;
    state.panX += Math.cos(angle) * step;
    state.panZ -= Math.sin(angle) * step;
  }

  function updateMovement(){
    let changed = false;
    const fast = keys.has('shift') ? 12 : 6;

    if (keys.has('w') || keys.has('arrowup')) {
      moveForward(fast);
      changed = true;
    }
    if (keys.has('s') || keys.has('arrowdown')) {
      moveForward(-fast);
      changed = true;
    }
    if (keys.has('a') || keys.has('arrowleft')) {
      moveSide(-fast);
      changed = true;
    }
    if (keys.has('d') || keys.has('arrowright')) {
      moveSide(fast);
      changed = true;
    }
    if (keys.has('q')) {
      state.panY -= 4;
      changed = true;
    }
    if (keys.has('e')) {
      state.panY += 4;
      changed = true;
    }

    state.panX = clamp(state.panX, -420, 420);
    state.panY = clamp(state.panY, -170, 160);
    state.panZ = clamp(state.panZ, -340, 340);

    if (changed) {
      hideTip();
      draw();
    }

    raf = requestAnimationFrame(updateMovement);
  }

  async function load(){
    metaLine.textContent = 'Cargando...';
    empty.style.display = 'none';
    hideTip();
    hoveredCell = null;
    locations = [];
    rackGroups = [];
    zoneGroups = [];
    interactiveCells = [];

    try {
      const metric = metricSel.value || 'inv_qty';
      const res = await fetch(`${dataUrl}?warehouse_id=${encodeURIComponent(whId)}&metric=${encodeURIComponent(metric)}`, {
        headers: { 'Accept':'application/json' }
      });

      const json = await res.json().catch(() => ({}));

      if (!json || !json.ok) {
        metaLine.textContent = 'Error cargando heatmap';
        empty.style.display = 'flex';
        draw();
        return;
      }

      const rawCells = Array.isArray(json.cells) ? json.cells : [];

      locations = rawCells
        .filter(loc => Number(loc.id || 0) > 0)
        .map(normalizeLocation);

      rackGroups = buildRackGroups();
      zoneGroups = buildZoneGroups();

      const rackLocationsCount = locations.filter(x => x.zone === 'rack').length;
      const maxPosition = rackLocationsCount
        ? Math.max(...locations.filter(x => x.zone === 'rack').map(x => Number(x.position || 0)))
        : 0;

      metaLine.textContent =
        `Métrica: ${metric} · Ubicaciones reales: ${locations.length} · Racks reales: ${rackGroups.length} · Posición máxima real: ${maxPosition || 0} · Picking: ${locations.filter(x => x.zone === 'picking').length} · Fast Flow: ${locations.filter(x => x.zone === 'fast_flow').length} · Entrante: ${locations.filter(x => x.zone === 'incoming').length} · Despacho: ${locations.filter(x => x.zone === 'dispatch').length}`;

      if (!locations.length) {
        empty.style.display = 'flex';
      }

      draw();
    } catch (e) {
      metaLine.textContent = 'Error de red cargando heatmap';
      empty.style.display = 'flex';
      draw();
    }
  }

  resizeCanvas();
  updateStatusBar();
  applyPreset('iso');

  canvas.addEventListener('mousedown', handleMouseDown);
  canvas.addEventListener('mousemove', handleMouseMove);
  canvas.addEventListener('mouseup', handleMouseUp);
  canvas.addEventListener('mouseleave', function(){
    handleMouseUp();
    hideTip();
  });
  canvas.addEventListener('click', handleClick);
  canvas.addEventListener('wheel', handleWheel, { passive:false });

  canvas.addEventListener('touchstart', handleTouchStart, { passive:true });
  canvas.addEventListener('touchmove', handleTouchMove, { passive:true });
  canvas.addEventListener('touchend', handleMouseUp, { passive:true });

  btnZoomIn?.addEventListener('click', function(){
    state.zoom = clamp(state.zoom + 0.10, 0.50, 2.80);
    hideTip();
    draw();
  });

  btnZoomOut?.addEventListener('click', function(){
    state.zoom = clamp(state.zoom - 0.10, 0.50, 2.80);
    hideTip();
    draw();
  });

  btnReset?.addEventListener('click', function(){
    applyPreset(viewPreset?.value || 'iso');
  });

  btnMode?.addEventListener('click', function(){
    state.mode = state.mode === 'orbit' ? 'walk' : 'orbit';
    updateStatusBar();
    draw();
  });

  btnLabels?.addEventListener('click', function(){
    state.showLabels = !state.showLabels;
    updateStatusBar();
    draw();
  });

  btnZones?.addEventListener('click', function(){
    state.showZones = !state.showZones;
    updateStatusBar();
    draw();
  });

  viewPreset?.addEventListener('change', function(){
    applyPreset(this.value || 'iso');
  });

  btnReload?.addEventListener('click', load);
  metricSel?.addEventListener('change', load);

  window.addEventListener('keydown', function(e){
    const key = String(e.key || '').toLowerCase();
    if (['w','a','s','d','q','e','arrowup','arrowdown','arrowleft','arrowright','shift'].includes(key)) {
      keys.add(key);
      e.preventDefault();
    }
  });

  window.addEventListener('keyup', function(e){
    const key = String(e.key || '').toLowerCase();
    keys.delete(key);
  });

  window.addEventListener('resize', function(){
    resizeCanvas();
    hideTip();
    draw();
  }, { passive:true });

  window.addEventListener('scroll', hideTip, { passive:true });

  updateMovement();
  load();
})();
</script>
@endpush