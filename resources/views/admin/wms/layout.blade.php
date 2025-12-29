@extends('layouts.app')

@section('title', 'WMS · Layout Builder')

@section('content')
@php
  use Illuminate\Support\Facades\Route;
  $whId = (int)($warehouseId ?? ($warehouse->id ?? 1));
  $hasDelete = Route::has('admin.wms.layout.delete');
@endphp

<div class="lb-wrap">
  <div class="lb-head">
    <div>
      <div class="lb-tt">Layout Builder</div>
      <div class="lb-sub">
        Seleccionar: clic / arrastrar para mover / manija para tamaño ·
        <b>ESC</b> cancela · <b>DEL/Supr</b> elimina (si existe la ruta delete).
      </div>
    </div>

    <div class="lb-actions">
      <a class="btn btn-ghost" href="{{ route('admin.wms.home') }}">← WMS</a>
      <a class="btn btn-ghost" href="{{ route('admin.wms.heatmap.view', ['warehouse_id'=>$whId]) }}">🔥 Heatmap</a>

      <form method="GET" action="{{ route('admin.wms.layout.editor') }}" class="wh-form">
        <select name="warehouse_id" class="inp" onchange="this.form.submit()">
          @foreach(($warehouses ?? []) as $w)
            <option value="{{ $w->id }}" @selected((int)$w->id === $whId)>{{ $w->name ?? ('Bodega #'.$w->id) }}</option>
          @endforeach
        </select>
      </form>

      <select id="drawKind" class="inp" title="Qué dibujar">
        <option value="bin">Ubicación (bin)</option>
        <option value="zone">Zona (rect)</option>
        <option value="wall">Muro (línea)</option>
        <option value="door">Puerta (línea + arco)</option>
      </select>

      <input id="lineThick" class="inp mono" type="number" min="1" max="10" value="2" style="width:78px" title="Grosor línea">

      <div class="seg" role="tablist" aria-label="Modo">
        <button class="segbtn is-on" id="btnModeSelect" type="button">Seleccionar</button>
        <button class="segbtn" id="btnModeDraw" type="button">Dibujar</button>
        <button class="segbtn" id="btnModeLine" type="button">Línea</button>
      </div>

      <button class="btn btn-primary" id="btnNewCell" type="button">＋ Nueva ubicación</button>
      <button class="btn btn-dark" id="btnGenRack" type="button">⚙️ Generar racks</button>
    </div>
  </div>

  <div class="lb-grid">
    <!-- Canvas -->
    <div class="canvas-card">
      <div class="canvas-top">
        <div class="canvas-badges">
          <span class="chip">Bodega: #{{ $whId }}</span>
          <span class="chip chip-soft" id="chipCount">0 objetos</span>
          <span class="chip chip-soft" id="chipMode">Modo: Seleccionar</span>
          @if(!$hasDelete)
            <span class="chip chip-warn">⚠️ No existe ruta delete (admin.wms.layout.delete)</span>
          @endif
        </div>

        <div class="canvas-tools">
          <button class="tool" id="btnZoomOut" title="Zoom -" type="button">－</button>
          <button class="tool" id="btnZoomIn" title="Zoom +" type="button">＋</button>
          <button class="tool" id="btnFit" title="Ajustar" type="button">⤢</button>
          <button class="tool" id="btnReload" title="Recargar" type="button">↻</button>
        </div>
      </div>

      <div class="stage" id="stage">
        <div class="grid-bg"></div>

        {{-- SVG de muros/puertas --}}
        <svg class="svg-lines" id="svgLines" xmlns="http://www.w3.org/2000/svg"></svg>

        {{-- Rectángulos (ubicaciones/zonas) --}}
        <div class="canvas" id="canvas"></div>

        {{-- Ghost para “dibujar rect” --}}
        <div class="ghost" id="ghost" style="display:none;"></div>
      </div>

      <div class="canvas-foot">
        <div class="muted">Tip: selecciona un objeto y edítalo a la derecha. Arrastra para mover. Manija esquina para tamaño. Líneas: arrastra endpoints.</div>
        <div class="muted">Puertas = café (línea + arco) · Muros = gris (línea sólida)</div>
      </div>
    </div>

    <!-- Inspector -->
    <div class="side-card">
      <div class="side-title">Inspector</div>
      <div class="side-sub">Edita el objeto seleccionado. También auto-guarda al soltar (drag/resize).</div>

      <div class="empty" id="emptyState">
        <div class="empty-ic">🧭</div>
        <div class="empty-tt">Selecciona un objeto</div>
        <div class="empty-tx">Clic en un rectángulo (ubicación/zona) o en una línea (muro/puerta).</div>
      </div>

      {{-- Inspector: Rect --}}
      <form id="cellForm" class="form" style="display:none;">
        <input type="hidden" name="id" id="f_id">
        <input type="hidden" name="warehouse_id" value="{{ $whId }}">

        <div class="row">
          <div class="col">
            <label class="lbl">Tipo</label>
            <select class="inp" name="type" id="f_type">
              <option value="aisle">aisle</option>
              <option value="stand">stand</option>
              <option value="rack">rack</option>
              <option value="bin">bin</option>
              <option value="zone">zone</option>
            </select>
          </div>
          <div class="col">
            <label class="lbl">Código *</label>
            <input class="inp" name="code" id="f_code" required>
          </div>
        </div>

        <label class="lbl">Nombre (opcional)</label>
        <input class="inp" name="name" id="f_name">

        <div class="hr"></div>

        <div class="row">
          <div class="col">
            <label class="lbl">Pasillo</label>
            <input class="inp" name="aisle" id="f_aisle">
          </div>
          <div class="col">
            <label class="lbl">Sección</label>
            <input class="inp" name="section" id="f_section">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">Stand</label>
            <input class="inp" name="stand" id="f_stand">
          </div>
          <div class="col">
            <label class="lbl">Rack</label>
            <input class="inp" name="rack" id="f_rack">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">Nivel</label>
            <input class="inp" name="level" id="f_level">
          </div>
          <div class="col">
            <label class="lbl">Bin</label>
            <input class="inp" name="bin" id="f_bin">
          </div>
        </div>

        <div class="hr"></div>

        <div class="row">
          <div class="col">
            <label class="lbl">X</label>
            <input class="inp mono" type="number" name="meta[x]" id="f_x" min="0" step="1">
          </div>
          <div class="col">
            <label class="lbl">Y</label>
            <input class="inp mono" type="number" name="meta[y]" id="f_y" min="0" step="1">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">W</label>
            <input class="inp mono" type="number" name="meta[w]" id="f_w" min="1" step="1">
          </div>
          <div class="col">
            <label class="lbl">H</label>
            <input class="inp mono" type="number" name="meta[h]" id="f_h" min="1" step="1">
          </div>
        </div>

        <label class="lbl">Notas</label>
        <textarea class="inp" rows="3" name="meta[notes]" id="f_notes"></textarea>

        <div class="btns">
          <button type="button" class="btn btn-ghost" id="btnCopyQr">📎 Copiar URL QR</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-danger" id="btnDeleteRect">Eliminar</button>
        </div>

        <div class="hint" id="saveHint"></div>
      </form>

      {{-- Inspector: Line --}}
      <form id="lineForm" class="form" style="display:none;">
        <input type="hidden" name="id" id="l_id">
        <input type="hidden" name="warehouse_id" value="{{ $whId }}">

        <div class="row">
          <div class="col">
            <label class="lbl">Tipo</label>
            <select class="inp" name="type" id="l_type">
              <option value="wall">wall</option>
              <option value="door">door</option>
            </select>
          </div>
          <div class="col">
            <label class="lbl">Código *</label>
            <input class="inp" name="code" id="l_code" required>
          </div>
        </div>

        <label class="lbl">Nombre (opcional)</label>
        <input class="inp" name="name" id="l_name">

        <div class="hr"></div>

        <div class="row">
          <div class="col">
            <label class="lbl">X1</label>
            <input class="inp mono" type="number" name="meta[x1]" id="l_x1" min="0" step="1">
          </div>
          <div class="col">
            <label class="lbl">Y1</label>
            <input class="inp mono" type="number" name="meta[y1]" id="l_y1" min="0" step="1">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">X2</label>
            <input class="inp mono" type="number" name="meta[x2]" id="l_x2" min="0" step="1">
          </div>
          <div class="col">
            <label class="lbl">Y2</label>
            <input class="inp mono" type="number" name="meta[y2]" id="l_y2" min="0" step="1">
          </div>
        </div>

        <div class="row">
          <div class="col">
            <label class="lbl">Grosor</label>
            <input class="inp mono" type="number" name="meta[thick]" id="l_thick" min="1" max="10" step="1">
          </div>
        </div>

        <div class="btns">
          <button type="submit" class="btn btn-primary">Guardar</button>
          <button type="button" class="btn btn-danger" id="btnDeleteLine">Eliminar</button>
        </div>

        <div class="hint" id="lineHint"></div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Generador -->
<div class="modal" id="rackModal" aria-hidden="true">
  <div class="modal__overlay" data-close></div>
  <div class="modal__panel">
    <div class="modal__head">
      <div>
        <div class="modal__title">Generar racks / bins</div>
        <div class="modal__sub">Crea ubicaciones en lote con posiciones (x,y) automáticas.</div>
      </div>
      <button class="modal__x" data-close type="button">✕</button>
    </div>

    <form id="rackForm" class="modal__body">
      <input type="hidden" name="warehouse_id" value="{{ $whId }}">

      <div class="row">
        <div class="col">
          <label class="lbl">Prefijo pasillo *</label>
          <input class="inp" name="prefix" value="A" required>
        </div>
        <div class="col">
          <label class="lbl">Stand (opcional)</label>
          <input class="inp" name="stand" placeholder="01">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label class="lbl"># Racks *</label>
          <input class="inp" type="number" name="rack_count" value="5" min="1" max="200" required>
        </div>
        <div class="col">
          <label class="lbl">Niveles *</label>
          <input class="inp" type="number" name="levels" value="3" min="1" max="10" required>
        </div>
        <div class="col">
          <label class="lbl">Bins por nivel *</label>
          <input class="inp" type="number" name="bins" value="4" min="1" max="10" required>
        </div>
      </div>

      <div class="hr"></div>

      <div class="row">
        <div class="col">
          <label class="lbl">Start X *</label>
          <input class="inp" type="number" name="start_x" value="10" min="0" required>
        </div>
        <div class="col">
          <label class="lbl">Start Y *</label>
          <input class="inp" type="number" name="start_y" value="10" min="0" required>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label class="lbl">Cell W *</label>
          <input class="inp" type="number" name="cell_w" value="8" min="1" required>
        </div>
        <div class="col">
          <label class="lbl">Cell H *</label>
          <input class="inp" type="number" name="cell_h" value="6" min="1" required>
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label class="lbl">Gap X *</label>
          <input class="inp" type="number" name="gap_x" value="3" min="0" required>
        </div>
        <div class="col">
          <label class="lbl">Gap Y *</label>
          <input class="inp" type="number" name="gap_y" value="3" min="0" required>
        </div>
      </div>

      <label class="lbl">Dirección *</label>
      <select class="inp" name="direction" required>
        <option value="right">→ hacia la derecha</option>
        <option value="down">↓ hacia abajo</option>
      </select>

      <div class="modal__foot">
        <div class="hint" id="genHint"></div>
        <button type="button" class="btn btn-ghost" data-close>Cancelar</button>
        <button type="submit" class="btn btn-dark">Generar</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --bg:#f6f8fc; --card:#fff; --ink:#0f172a; --muted:#64748b;
    --line:#e5e7eb; --line2:#eef2f7; --brand:#2563eb; --brand2:#1d4ed8;
    --dark:#0b1220; --shadow:0 18px 55px rgba(2,6,23,.08);
    --r:18px; --r2:14px;
  }
  .lb-wrap{max-width:1380px;margin:0 auto;padding:18px 14px 26px}
  .lb-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}
  .lb-tt{font-weight:950;color:var(--ink);font-size:1.15rem}
  .lb-sub{color:var(--muted);font-size:.88rem;margin-top:2px}
  .lb-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .wh-form{margin:0}

  .btn{border:0;border-radius:999px;padding:10px 14px;font-weight:900;font-size:.9rem;cursor:pointer;display:inline-flex;gap:8px;align-items:center;transition:transform .12s ease, box-shadow .12s ease}
  .btn:hover{transform:translateY(-1px)}
  .btn-primary{background:var(--brand);color:#fff;box-shadow:0 14px 30px rgba(37,99,235,.25)}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink);box-shadow:0 10px 25px rgba(2,6,23,.04)}
  .btn-dark{background:var(--dark);color:#fff;box-shadow:0 14px 30px rgba(2,6,23,.18)}
  .btn-danger{background:#991b1b;color:#fff;box-shadow:0 14px 30px rgba(153,27,27,.18)}
  .btn-danger:hover{background:#7f1d1d}

  .inp{background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-height:42px;font-size:.92rem;color:var(--ink)}
  .inp:focus{outline:none;border-color:#93c5fd;box-shadow:0 0 0 2px rgba(147,197,253,.45);background:#fff}
  .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}
  .muted{color:var(--muted);font-size:.82rem}
  .hint{color:var(--muted);font-size:.8rem;margin-top:8px}

  .seg{display:inline-flex;border:1px solid var(--line);background:#fff;border-radius:999px;overflow:hidden}
  .segbtn{border:0;background:transparent;padding:10px 12px;font-weight:950;cursor:pointer;color:#0f172a}
  .segbtn.is-on{background:#0b1220;color:#fff}

  .lb-grid{display:grid;grid-template-columns:1.55fr .65fr;gap:14px;margin-top:14px}
  @media(max-width: 1060px){.lb-grid{grid-template-columns:1fr}}

  .canvas-card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden}
  .canvas-top{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:12px 12px;border-bottom:1px solid var(--line2)}
  .canvas-badges{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .chip{background:#f1f5f9;border:1px solid #e2e8f0;color:#0f172a;border-radius:999px;padding:4px 10px;font-weight:950;font-size:.75rem}
  .chip-soft{background:#eff6ff;border-color:#dbeafe;color:#1d4ed8}
  .chip-warn{background:#fff7ed;border-color:#fed7aa;color:#9a3412}

  .canvas-tools{display:flex;gap:6px}
  .tool{width:40px;height:40px;border-radius:12px;border:1px solid var(--line);background:#fff;cursor:pointer;font-weight:950}
  .tool:hover{box-shadow:0 10px 25px rgba(2,6,23,.06);transform:translateY(-1px)}

  .stage{
    position:relative;
    height:66vh; min-height:560px;
    background:linear-gradient(180deg,#fbfdff,#f6f8fc);
    overflow:auto;
  }
  .grid-bg{
    position:absolute;inset:0;
    background-image:
      linear-gradient(to right, rgba(148,163,184,.20) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(148,163,184,.20) 1px, transparent 1px);
    background-size: 22px 22px;
    opacity:.7;
    pointer-events:none;
  }

  /* IMPORTANTE: overlays NO atrapan clicks */
  .svg-lines{
    position:absolute;inset:0;
    width:3200px;height:2200px;
    pointer-events:none;
  }
  .canvas{
    position:absolute;inset:0;
    width:3200px;height:2200px;
    transform-origin:0 0;
    pointer-events:none;
  }
  .ghost{
    position:absolute;border-radius:14px;border:2px dashed rgba(37,99,235,.6);
    background:rgba(37,99,235,.08);pointer-events:none;
  }

  /* Rect objects */
  .cell{
    position:absolute;
    border-radius:16px;
    border:1px solid rgba(148,163,184,.55);
    background:rgba(255,255,255,.92);
    box-shadow:0 12px 26px rgba(2,6,23,.06);
    padding:10px 12px;
    cursor:pointer;
    user-select:none;
    pointer-events:auto; /* ✅ sí reciben eventos */
  }
  .cell:hover{border-color:#60a5fa;box-shadow:0 16px 34px rgba(37,99,235,.12)}
  .cell.is-active{border-color:#2563eb;background:#eff6ff;box-shadow:0 18px 40px rgba(37,99,235,.18)}
  .cell .c-code{font-weight:950;color:#0f172a}
  .cell .c-type{font-size:.78rem;color:#64748b;margin-top:2px}
  .cell .c-mini{font-size:.78rem;color:#475569;margin-top:6px;line-height:1.15}

  .handle{
    position:absolute;right:8px;bottom:8px;width:14px;height:14px;border-radius:5px;
    border:1px solid rgba(148,163,184,.9);
    background:rgba(255,255,255,.95);
    box-shadow:0 6px 14px rgba(2,6,23,.08);
    cursor:nwse-resize;
  }

  /* Lines styles */
  .ln-wall{stroke:#6b7280;stroke-linecap:round}
  .ln-door{stroke:#8b5a2b;stroke-linecap:round}
  .ln-arc{fill:none;stroke:#8b5a2b;opacity:.95}
  .ln-sel{filter: drop-shadow(0 2px 6px rgba(37,99,235,.35));}

  .ln-hit{pointer-events:stroke;stroke:transparent;fill:none;cursor:pointer}
  .pt{pointer-events:all;fill:#fff;stroke:rgba(148,163,184,.8);stroke-width:1}
  .pt.sel{stroke:#2563eb;stroke-width:2}

  .canvas-foot{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:10px 12px;border-top:1px solid var(--line2);background:#fff}

  /* Inspector */
  .side-card{background:var(--card);border:1px solid var(--line);border-radius:var(--r);box-shadow:var(--shadow);padding:12px}
  .side-title{font-weight:950;color:var(--ink);font-size:1rem}
  .side-sub{color:var(--muted);font-size:.86rem;margin-top:2px}
  .hr{border-top:1px dashed #e5e7eb;margin:12px 0}
  .row{display:flex;gap:10px;flex-wrap:wrap}
  .col{flex:1 1 140px}
  .lbl{display:block;font-weight:950;color:var(--ink);margin:10px 0 6px;font-size:.85rem}
  .btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}

  .empty{border:1px dashed #e5e7eb;border-radius:16px;padding:14px;margin-top:12px;background:#f9fafb}
  .empty-ic{font-size:1.6rem}
  .empty-tt{font-weight:950;color:var(--ink);margin-top:4px}
  .empty-tx{color:var(--muted);font-size:.86rem;margin-top:2px}

  /* Modal */
  .modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:9999}
  .modal.is-open{display:flex}
  .modal__overlay{position:absolute;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(10px)}
  .modal__panel{position:relative;background:#fff;border-radius:18px;border:1px solid rgba(148,163,184,.3);width:min(720px,92vw);box-shadow:0 30px 80px rgba(2,6,23,.35);overflow:hidden}
  .modal__head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;padding:14px 14px;border-bottom:1px solid var(--line2)}
  .modal__title{font-weight:950;color:var(--ink)}
  .modal__sub{color:var(--muted);font-size:.85rem;margin-top:2px}
  .modal__x{border:1px solid var(--line);background:#fff;border-radius:12px;width:40px;height:40px;cursor:pointer;font-weight:950}
  .modal__body{padding:12px 14px}
  .modal__foot{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:10px}
</style>
@endpush

@push('scripts')
<script>
(function(){
  const CSRF = @json(csrf_token());
  const whId = @json((int)$whId);

  const ROUTES = {
    data:   @json(route('admin.wms.layout.data')),
    upsert: @json(route('admin.wms.layout.cell')),
    gen:    @json(route('admin.wms.layout.generate-rack')),
    del:    @json($hasDelete ? route('admin.wms.layout.delete') : null),
    qrBase: @json(url('/admin/wms/locations')),
  };

  const U = 22; // px por unidad
  const WORLD_W = 3200; // px
  const WORLD_H = 2200; // px

  const stage = document.getElementById('stage');
  const canvas = document.getElementById('canvas');
  const svgLines = document.getElementById('svgLines');
  const ghost = document.getElementById('ghost');

  svgLines.setAttribute('width', WORLD_W);
  svgLines.setAttribute('height', WORLD_H);

  const drawKind = document.getElementById('drawKind');
  const lineThick = document.getElementById('lineThick');

  const chipCount = document.getElementById('chipCount');
  const chipMode  = document.getElementById('chipMode');

  let zoom = 1;

  // State
  let objects = []; // all from API (locations rows)
  let selected = null; // {id, kind:'rect'|'line'}
  let mode = 'select'; // select|draw|line

  // Drag/Resize
  let dragging = null; // {id, startGX,startGY, origX,origY}
  let resizing = null; // {id, startGX,startGY, origW,origH}
  let lineDrag = null; // {id, which:'p1'|'p2'|'body', start, orig}

  // Draw new
  let isDrawingRect = false;
  let rectStart = null;
  let isDrawingLine = false;
  let lineStart = null;
  let linePreview = null;

  // ---------------- utils ----------------
  function setZoom(z){
    zoom = Math.max(0.45, Math.min(2.2, z));
    canvas.style.transform = `scale(${zoom})`;
    svgLines.style.transform = `scale(${zoom})`;
    svgLines.style.transformOrigin = '0 0';
  }

  function svgEl(tag, attrs){
    const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for(const k in attrs) el.setAttribute(k, String(attrs[k]));
    return el;
  }

  function escapeHtml(str){
    return String(str ?? '')
      .replace(/&/g,"&amp;")
      .replace(/</g,"&lt;")
      .replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;")
      .replace(/'/g,"&#039;");
  }

  function clamp(n, a, b){ return Math.max(a, Math.min(b, n)); }

  function stageToGrid(ev){
    const r = stage.getBoundingClientRect();
    const sx = (ev.clientX - r.left + stage.scrollLeft) / zoom;
    const sy = (ev.clientY - r.top  + stage.scrollTop) / zoom;
    const gx = Math.max(0, Math.round(sx / U));
    const gy = Math.max(0, Math.round(sy / U));
    return {sx, sy, gx, gy};
  }

  function px(n){ return (n|0) + 'px'; }

  function isLineObj(o){
    const t = String(o.type || '');
    const m = o.meta || {};
    return (t === 'wall' || t === 'door' || ('x1' in m) || ('y1' in m) || ('x2' in m) || ('y2' in m));
  }

  function rectMeta(o){
    const m = o.meta || {};
    return {
      x: Number(m.x ?? 0),
      y: Number(m.y ?? 0),
      w: Math.max(1, Number(m.w ?? 6)),
      h: Math.max(1, Number(m.h ?? 5)),
    };
  }

  function lineMeta(o){
    const m = o.meta || {};
    return {
      x1: Number(m.x1 ?? 0),
      y1: Number(m.y1 ?? 0),
      x2: Number(m.x2 ?? 0),
      y2: Number(m.y2 ?? 0),
      thick: Math.max(1, Number(m.thick ?? 2)),
    };
  }

  function codeRand(prefix){
    return prefix + '-' + Math.random().toString(16).slice(2,6).toUpperCase();
  }

  async function postJson(url, body){
    const res = await fetch(url, {
      method:'POST',
      headers:{
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': CSRF,
      },
      body: JSON.stringify(body || {})
    });
    const data = await res.json().catch(()=> ({}));
    if(!res.ok) data._http_error = true;
    return data;
  }

  function setMode(m){
    mode = m;
    chipMode.textContent = 'Modo: ' + (m === 'select' ? 'Seleccionar' : (m === 'draw' ? 'Dibujar' : 'Línea'));
    document.getElementById('btnModeSelect').classList.toggle('is-on', m==='select');
    document.getElementById('btnModeDraw').classList.toggle('is-on', m==='draw');
    document.getElementById('btnModeLine').classList.toggle('is-on', m==='line');
    cancelDrawing();
  }

  function cancelDrawing(){
    isDrawingRect = false;
    rectStart = null;
    ghost.style.display = 'none';

    isDrawingLine = false;
    lineStart = null;
    if(linePreview){ linePreview.remove(); linePreview = null; }
  }

  function selectObj(kind, id){
    selected = (id ? {kind, id:Number(id)} : null);
    render();
    fillInspector();
  }

  function selectedObject(){
    if(!selected) return null;
    return objects.find(o => Number(o.id) === Number(selected.id)) || null;
  }

  // ---------------- render ----------------
  function render(){
    canvas.innerHTML = '';
    while(svgLines.firstChild) svgLines.removeChild(svgLines.firstChild);

    const rects = objects.filter(o => !isLineObj(o));
    const lines = objects.filter(o => isLineObj(o));

    chipCount.textContent = `${objects.length} objetos`;

    // lines first (behind)
    lines.forEach(o => renderLine(o));

    // rects
    rects.forEach(o => renderRect(o));
  }

  function renderRect(o){
    const m = rectMeta(o);

    const d = document.createElement('div');
    d.className = 'cell' + (selected?.kind === 'rect' && selected?.id === o.id ? ' is-active' : '');
    d.style.left = px(m.x * U);
    d.style.top  = px(m.y * U);
    d.style.width  = px(m.w * U);
    d.style.height = px(m.h * U);

    const mini = [
      o.aisle ? `Pasillo ${o.aisle}` : null,
      o.section ? `Sección ${o.section}` : null,
      o.stand ? `Stand ${o.stand}` : null,
      o.rack ? `R${o.rack}` : null,
      o.level ? `L${o.level}` : null,
      o.bin ? `B${o.bin}` : null,
    ].filter(Boolean).join(' · ');

    d.innerHTML = `
      <div class="c-code">${escapeHtml(o.code || '')}</div>
      <div class="c-type">${escapeHtml(o.type || '')}</div>
      ${mini ? `<div class="c-mini">${escapeHtml(mini)}</div>` : ``}
      <div class="handle" data-handle="1" title="Cambiar tamaño"></div>
    `;

    // select
    d.addEventListener('pointerdown', (ev)=>{
      ev.stopPropagation();
      selectObj('rect', o.id);

      // resize handle?
      if(ev.target && ev.target.getAttribute && ev.target.getAttribute('data-handle')){
        const g = stageToGrid(ev);
        resizing = { id:o.id, startGX:g.gx, startGY:g.gy, origW:m.w, origH:m.h };
        stage.setPointerCapture(ev.pointerId);
        ev.preventDefault();
        return;
      }

      // drag body
      if(mode === 'select'){
        const g = stageToGrid(ev);
        dragging = { id:o.id, startGX:g.gx, startGY:g.gy, origX:m.x, origY:m.y };
        stage.setPointerCapture(ev.pointerId);
        ev.preventDefault();
      }
    });

    canvas.appendChild(d);
  }

  function renderLine(o){
    const t = String(o.type || (o.meta?.kind || 'wall'));
    const m = lineMeta(o);

    const x1 = m.x1 * U, y1 = m.y1 * U, x2 = m.x2 * U, y2 = m.y2 * U;
    const thick = m.thick;

    const g = svgEl('g', {'data-id': o.id});
    const isSel = (selected?.kind === 'line' && selected?.id === o.id);

    // main line
    const ln = svgEl('line', {
      x1, y1, x2, y2,
      'stroke-width': thick,
      class: (t === 'door') ? 'ln-door' : 'ln-wall'
    });
    if(isSel) ln.classList.add('ln-sel');

    g.appendChild(ln);

    // door arc
    if(t === 'door'){
      const arc = makeDoorArcPath(x1,y1,x2,y2);
      const p = svgEl('path', { d: arc, 'stroke-width': Math.max(1, Math.round(thick*0.9)), class:'ln-arc' });
      if(isSel) p.classList.add('ln-sel');
      g.appendChild(p);
    }

    // hit area (click/drag)
    const hit = svgEl('line', { x1, y1, x2, y2, class:'ln-hit' });
    hit.addEventListener('pointerdown', (ev)=>{
      ev.stopPropagation();
      selectObj('line', o.id);
      if(mode !== 'select') return;

      const gg = stageToGrid(ev);
      lineDrag = {
        id:o.id, which:'body',
        start:{gx:gg.gx, gy:gg.gy},
        orig:{...m}
      };
      stage.setPointerCapture(ev.pointerId);
      ev.preventDefault();
    });
    g.appendChild(hit);

    // endpoints
    const p1 = svgEl('circle', { cx:x1, cy:y1, r:6, class: 'pt' + (isSel?' sel':'') });
    const p2 = svgEl('circle', { cx:x2, cy:y2, r:6, class: 'pt' + (isSel?' sel':'') });

    function startPtDrag(which, ev){
      ev.stopPropagation();
      selectObj('line', o.id);
      if(mode !== 'select') return;

      const gg = stageToGrid(ev);
      lineDrag = {
        id:o.id, which,
        start:{gx:gg.gx, gy:gg.gy},
        orig:{...m}
      };
      stage.setPointerCapture(ev.pointerId);
      ev.preventDefault();
    }

    p1.addEventListener('pointerdown', startPtDrag.bind(null, 'p1'));
    p2.addEventListener('pointerdown', startPtDrag.bind(null, 'p2'));

    if(isSel){
      g.appendChild(p1);
      g.appendChild(p2);
    }

    svgLines.appendChild(g);

    // selecting line by clicking stroke
    g.addEventListener('pointerdown', (ev)=>{
      // si ya lo manejó hit/pt, ignora
      if(ev.target === hit || ev.target === p1 || ev.target === p2) return;
      ev.stopPropagation();
      selectObj('line', o.id);
    });
  }

  function makeDoorArcPath(x1,y1,x2,y2){
    // Hinge at (x1,y1), leaf to (x2,y2). Draw 90° arc with radius = length.
    const dx = x2-x1, dy = y2-y1;
    const r = Math.max(10, Math.hypot(dx,dy));

    // decide arc direction (consistent)
    // if mostly horizontal => arc downward; if vertical => arc right
    let ex, ey, sweep = 1;
    if(Math.abs(dx) >= Math.abs(dy)){
      ex = x1; ey = y1 + (dy >= 0 ? r : -r);
      sweep = (dy >= 0) ? 1 : 0;
    }else{
      ex = x1 + (dx >= 0 ? r : -r); ey = y1;
      sweep = (dx >= 0) ? 1 : 0;
    }

    // SVG arc: M endLeaf A r r 0 0 sweep endArc
    // We want arc between leaf end and perpendicular end.
    // We can approximate: start at leaf end and arc to ex,ey with radius r.
    return `M ${x2} ${y2} A ${r} ${r} 0 0 ${sweep} ${ex} ${ey}`;
  }

  // ---------------- data load/save ----------------
  async function load(){
    const res = await fetch(`${ROUTES.data}?warehouse_id=${encodeURIComponent(whId)}`, { headers:{'Accept':'application/json'} });
    const json = await res.json().catch(()=> ({}));

    if(!json.ok){
      alert(json.error || 'No se pudo cargar layout.data');
      return;
    }

    objects = (json.locations || []).map(x => ({
      ...x,
      meta: (x.meta && typeof x.meta === 'object') ? x.meta : (x.meta ? x.meta : {})
    }));

    // ensure meta defaults for rect
    objects.forEach(o=>{
      if(!isLineObj(o)){
        o.meta = o.meta || {};
        if(o.meta.x === undefined) o.meta.x = 0;
        if(o.meta.y === undefined) o.meta.y = 0;
        if(o.meta.w === undefined) o.meta.w = 6;
        if(o.meta.h === undefined) o.meta.h = 5;
      }else{
        o.meta = o.meta || {};
        if(o.meta.thick === undefined) o.meta.thick = 2;
      }
    });

    // keep selection if exists
    if(selected){
      const still = objects.find(o => Number(o.id) === Number(selected.id));
      if(!still) selected = null;
    }

    render();
    fillInspector();
  }

  async function saveObject(o){
    const payload = {
      id: o.id || null,
      warehouse_id: whId,
      type: o.type || 'bin',
      code: o.code || codeRand('OBJ'),
      name: o.name || null,
      aisle: o.aisle || null,
      section: o.section || null,
      stand: o.stand || null,
      rack: o.rack || null,
      level: o.level || null,
      bin: o.bin || null,
      meta: o.meta || {}
    };

    const data = await postJson(ROUTES.upsert, payload);
    if(!data.ok){
      console.error(data);
      return {ok:false, error: data.error || 'No se pudo guardar.'};
    }

    // update local object id if was new
    if(!o.id && data.location?.id) o.id = data.location.id;

    // refresh list to avoid drift
    await load();
    if(o.id){
      // reselect
      selectObj(isLineObj(o) ? 'line' : 'rect', o.id);
    }
    return {ok:true};
  }

  async function deleteSelected(){
    if(!selected) return;
    if(!ROUTES.del){
      alert('No existe la ruta admin.wms.layout.delete. Agrega la ruta y recarga.');
      return;
    }

    const o = selectedObject();
    if(!o) return;

    if(!confirm(`¿Eliminar ${o.code || ('#'+o.id)}?`)) return;

    const data = await postJson(ROUTES.del, {warehouse_id: whId, id: o.id});
    if(!data.ok){
      alert(data.error || 'No se pudo eliminar.');
      return;
    }
    selected = null;
    await load();
  }

  // ---------------- inspector fill ----------------
  function fillInspector(){
    const empty = document.getElementById('emptyState');
    const cellForm = document.getElementById('cellForm');
    const lineForm = document.getElementById('lineForm');

    const o = selectedObject();
    if(!o){
      empty.style.display = 'block';
      cellForm.style.display = 'none';
      lineForm.style.display = 'none';
      return;
    }

    empty.style.display = 'none';

    if(isLineObj(o)){
      cellForm.style.display = 'none';
      lineForm.style.display = 'block';

      const m = lineMeta(o);
      document.getElementById('l_id').value = o.id || '';
      document.getElementById('l_type').value = (o.type === 'door') ? 'door' : 'wall';
      document.getElementById('l_code').value = o.code || '';
      document.getElementById('l_name').value = o.name || '';
      document.getElementById('l_x1').value = m.x1;
      document.getElementById('l_y1').value = m.y1;
      document.getElementById('l_x2').value = m.x2;
      document.getElementById('l_y2').value = m.y2;
      document.getElementById('l_thick').value = m.thick;
      document.getElementById('lineHint').textContent = '';
      return;
    }

    // rect
    lineForm.style.display = 'none';
    cellForm.style.display = 'block';

    const m = rectMeta(o);
    document.getElementById('f_id').value = o.id || '';
    document.getElementById('f_type').value = o.type || 'bin';
    document.getElementById('f_code').value = o.code || '';
    document.getElementById('f_name').value = o.name || '';

    document.getElementById('f_aisle').value = o.aisle || '';
    document.getElementById('f_section').value = o.section || '';
    document.getElementById('f_stand').value = o.stand || '';
    document.getElementById('f_rack').value = o.rack || '';
    document.getElementById('f_level').value = o.level || '';
    document.getElementById('f_bin').value = o.bin || '';

    document.getElementById('f_x').value = m.x;
    document.getElementById('f_y').value = m.y;
    document.getElementById('f_w').value = m.w;
    document.getElementById('f_h').value = m.h;

    document.getElementById('f_notes').value = (o.meta?.notes ?? '');
    document.getElementById('saveHint').textContent = '';
  }

  // ---------------- interactions ----------------
  function isClickOnObject(ev){
    if(ev.target.closest && ev.target.closest('.cell')) return true;
    if(ev.target.closest && ev.target.closest('g[data-id]')) return true;
    return false;
  }

  // stage pointerdown (CAPTURE) so it always fires (even with svg/canvas)
  stage.addEventListener('pointerdown', (ev)=>{
    // if clicked object, ignore (object handlers handle selection/drag)
    if(isClickOnObject(ev)) return;

    if(mode === 'draw'){
      const kind = drawKind.value || 'bin';
      // if user chose wall/door but is in draw mode, switch to line mode
      if(kind === 'wall' || kind === 'door'){
        setMode('line');
        return;
      }
      isDrawingRect = true;
      rectStart = stageToGrid(ev);
      ghost.style.display = 'block';
      ghost.style.left = px(rectStart.gx*U);
      ghost.style.top  = px(rectStart.gy*U);
      ghost.style.width = px(1*U);
      ghost.style.height= px(1*U);
      stage.setPointerCapture(ev.pointerId);
      ev.preventDefault();
      return;
    }

    if(mode === 'line'){
      const kind = drawKind.value || 'wall';
      if(kind !== 'wall' && kind !== 'door'){
        alert('En modo Línea solo se dibuja Muro o Puerta. Elige “Muro” o “Puerta” arriba.');
        return;
      }
      isDrawingLine = true;
      lineStart = stageToGrid(ev);

      if(linePreview) linePreview.remove();
      linePreview = svgEl('line', {
        x1: lineStart.gx*U, y1: lineStart.gy*U,
        x2: lineStart.gx*U, y2: lineStart.gy*U,
        'stroke-width': Math.max(1, parseInt(lineThick.value||'2',10)||2),
        class: (kind==='door') ? 'ln-door' : 'ln-wall'
      });
      svgLines.appendChild(linePreview);

      stage.setPointerCapture(ev.pointerId);
      ev.preventDefault();
      return;
    }

    // select mode: click empty -> deselect
    if(mode === 'select'){
      selectObj(null, null);
    }
  }, true);

  stage.addEventListener('pointermove', (ev)=>{
    // drag rect
    if(dragging){
      const o = objects.find(x=> Number(x.id)===Number(dragging.id));
      if(!o) return;
      const g = stageToGrid(ev);
      const dx = g.gx - dragging.startGX;
      const dy = g.gy - dragging.startGY;
      o.meta = o.meta || {};
      o.meta.x = clamp(dragging.origX + dx, 0, 9999);
      o.meta.y = clamp(dragging.origY + dy, 0, 9999);
      render();
      return;
    }

    // resize rect
    if(resizing){
      const o = objects.find(x=> Number(x.id)===Number(resizing.id));
      if(!o) return;
      const g = stageToGrid(ev);
      const dw = g.gx - resizing.startGX;
      const dh = g.gy - resizing.startGY;
      o.meta = o.meta || {};
      o.meta.w = Math.max(1, resizing.origW + dw);
      o.meta.h = Math.max(1, resizing.origH + dh);
      render();
      return;
    }

    // line drag
    if(lineDrag){
      const o = objects.find(x=> Number(x.id)===Number(lineDrag.id));
      if(!o) return;
      const g = stageToGrid(ev);

      const dx = g.gx - lineDrag.start.gx;
      const dy = g.gy - lineDrag.start.gy;

      const m = o.meta || {};
      const orig = lineDrag.orig;

      if(lineDrag.which === 'p1'){
        m.x1 = clamp(orig.x1 + dx, 0, 9999);
        m.y1 = clamp(orig.y1 + dy, 0, 9999);
      }else if(lineDrag.which === 'p2'){
        m.x2 = clamp(orig.x2 + dx, 0, 9999);
        m.y2 = clamp(orig.y2 + dy, 0, 9999);
      }else{
        m.x1 = clamp(orig.x1 + dx, 0, 9999);
        m.y1 = clamp(orig.y1 + dy, 0, 9999);
        m.x2 = clamp(orig.x2 + dx, 0, 9999);
        m.y2 = clamp(orig.y2 + dy, 0, 9999);
      }

      o.meta = m;
      render();
      return;
    }

    // draw rect preview
    if(isDrawingRect && rectStart){
      const g = stageToGrid(ev);
      const x1 = Math.min(rectStart.gx, g.gx);
      const y1 = Math.min(rectStart.gy, g.gy);
      const x2 = Math.max(rectStart.gx, g.gx);
      const y2 = Math.max(rectStart.gy, g.gy);
      const w = Math.max(1, (x2 - x1) + 1);
      const h = Math.max(1, (y2 - y1) + 1);
      ghost.style.left = px(x1*U);
      ghost.style.top  = px(y1*U);
      ghost.style.width = px(w*U);
      ghost.style.height= px(h*U);
      return;
    }

    // draw line preview
    if(isDrawingLine && lineStart && linePreview){
      const g = stageToGrid(ev);
      linePreview.setAttribute('x2', g.gx*U);
      linePreview.setAttribute('y2', g.gy*U);
      return;
    }
  });

  stage.addEventListener('pointerup', async (ev)=>{
    // end rect drag/resize
    if(dragging){
      const o = objects.find(x=> Number(x.id)===Number(dragging.id));
      dragging = null;
      if(o){ await saveObject(o); }
      return;
    }
    if(resizing){
      const o = objects.find(x=> Number(x.id)===Number(resizing.id));
      resizing = null;
      if(o){ await saveObject(o); }
      return;
    }

    // end line drag
    if(lineDrag){
      const o = objects.find(x=> Number(x.id)===Number(lineDrag.id));
      lineDrag = null;
      if(o){ await saveObject(o); }
      return;
    }

    // finish draw rect -> create object
    if(isDrawingRect && rectStart){
      const g = stageToGrid(ev);
      const x1 = Math.min(rectStart.gx, g.gx);
      const y1 = Math.min(rectStart.gy, g.gy);
      const x2 = Math.max(rectStart.gx, g.gx);
      const y2 = Math.max(rectStart.gy, g.gy);
      const w = Math.max(1, (x2 - x1) + 1);
      const h = Math.max(1, (y2 - y1) + 1);

      const kind = drawKind.value || 'bin';
      const obj = {
        id: null,
        warehouse_id: whId,
        type: (kind === 'zone') ? 'zone' : 'bin',
        code: (kind === 'zone') ? codeRand('ZONE') : codeRand('NEW'),
        name: null,
        meta: { x:x1, y:y1, w:w, h:h }
      };

      isDrawingRect = false;
      rectStart = null;
      ghost.style.display = 'none';

      const r = await saveObject(obj);
      if(r.ok) setMode('select');
      return;
    }

    // finish draw line -> create wall/door
    if(isDrawingLine && lineStart){
      const g = stageToGrid(ev);
      const kind = drawKind.value || 'wall';
      const thick = Math.max(1, parseInt(lineThick.value||'2',10)||2);

      // ignore tiny
      if(lineStart.gx === g.gx && lineStart.gy === g.gy){
        cancelDrawing();
        return;
      }

      const obj = {
        id: null,
        warehouse_id: whId,
        type: (kind === 'door') ? 'door' : 'wall',
        code: (kind === 'door') ? codeRand('DOOR') : codeRand('WALL'),
        name: (kind === 'door') ? 'door' : 'wall',
        meta: { x1: lineStart.gx, y1: lineStart.gy, x2: g.gx, y2: g.gy, thick: thick }
      };

      cancelDrawing();

      const r = await saveObject(obj);
      if(r.ok) setMode('select');
      return;
    }
  });

  // ---------------- toolbar ----------------
  document.getElementById('btnModeSelect').addEventListener('click', ()=> setMode('select'));
  document.getElementById('btnModeDraw').addEventListener('click', ()=> setMode('draw'));
  document.getElementById('btnModeLine').addEventListener('click', ()=> setMode('line'));

  document.getElementById('btnZoomIn').addEventListener('click', ()=> setZoom(zoom + 0.1));
  document.getElementById('btnZoomOut').addEventListener('click', ()=> setZoom(zoom - 0.1));
  document.getElementById('btnFit').addEventListener('click', ()=> setZoom(1));
  document.getElementById('btnReload').addEventListener('click', load);

  // New rect quick
  document.getElementById('btnNewCell').addEventListener('click', async ()=>{
    const obj = {
      id:null,
      warehouse_id: whId,
      type:'bin',
      code: codeRand('NEW'),
      name:null,
      meta:{x:10,y:10,w:8,h:6}
    };
    const r = await saveObject(obj);
    if(r.ok) setMode('select');
  });

  // Keyboard
  document.addEventListener('keydown', async (e)=>{
    if(e.key === 'Escape'){
      cancelDrawing();
      setMode('select');
    }
    if(e.key === 'Delete' || e.key === 'Backspace'){
      // avoid deleting when typing in input
      const tag = (document.activeElement?.tagName || '').toLowerCase();
      if(tag === 'input' || tag === 'textarea' || tag === 'select') return;
      await deleteSelected();
    }
  });

  // ---------------- inspector handlers ----------------
  document.getElementById('cellForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const o = selectedObject();
    if(!o) return;

    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());

    o.type = payload.type || 'bin';
    o.code = payload.code || o.code;
    o.name = payload.name || null;
    o.aisle = payload.aisle || null;
    o.section = payload.section || null;
    o.stand = payload.stand || null;
    o.rack = payload.rack || null;
    o.level = payload.level || null;
    o.bin = payload.bin || null;

    o.meta = o.meta || {};
    o.meta.x = Number(payload['meta[x]'] || 0);
    o.meta.y = Number(payload['meta[y]'] || 0);
    o.meta.w = Math.max(1, Number(payload['meta[w]'] || 6));
    o.meta.h = Math.max(1, Number(payload['meta[h]'] || 5));
    o.meta.notes = payload['meta[notes]'] || '';

    document.getElementById('saveHint').textContent = 'Guardando...';
    const r = await saveObject(o);
    document.getElementById('saveHint').textContent = r.ok ? 'Guardado ✅' : (r.error || 'No se pudo guardar.');
  });

  document.getElementById('lineForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const o = selectedObject();
    if(!o) return;

    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());

    o.type = payload.type || 'wall';
    o.code = payload.code || o.code;
    o.name = payload.name || null;

    o.meta = o.meta || {};
    o.meta.x1 = Number(payload['meta[x1]'] || 0);
    o.meta.y1 = Number(payload['meta[y1]'] || 0);
    o.meta.x2 = Number(payload['meta[x2]'] || 0);
    o.meta.y2 = Number(payload['meta[y2]'] || 0);
    o.meta.thick = Math.max(1, Number(payload['meta[thick]'] || 2));

    document.getElementById('lineHint').textContent = 'Guardando...';
    const r = await saveObject(o);
    document.getElementById('lineHint').textContent = r.ok ? 'Guardado ✅' : (r.error || 'No se pudo guardar.');
  });

  document.getElementById('btnDeleteRect').addEventListener('click', deleteSelected);
  document.getElementById('btnDeleteLine').addEventListener('click', deleteSelected);

  document.getElementById('btnCopyQr').addEventListener('click', async ()=>{
    const id = document.getElementById('f_id').value;
    if(!id) return alert('Guarda primero para obtener ID.');
    const url = `${ROUTES.qrBase}/${id}/page`;
    try{
      await navigator.clipboard.writeText(url);
      document.getElementById('saveHint').textContent = 'URL copiada ✅';
    }catch(e){
      prompt('Copia la URL:', url);
    }
  });

  // ---------------- modal racks ----------------
  const modal = document.getElementById('rackModal');
  function openModal(){ modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); }
  function closeModal(){ modal.classList.remove('is-open'); modal.setAttribute('aria-hidden','true'); }

  document.getElementById('btnGenRack').addEventListener('click', openModal);
  modal.querySelectorAll('[data-close]').forEach(el => el.addEventListener('click', closeModal));

  document.getElementById('rackForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());

    const hint = document.getElementById('genHint');
    hint.textContent = 'Generando...';

    const data = await postJson(ROUTES.gen, payload);
    if(!data.ok){
      hint.textContent = data.error || 'No se pudo generar.';
      return;
    }

    hint.textContent = `Listo ✅ creados: ${data.created || 0}`;
    await load();
    setTimeout(closeModal, 450);
  });

  // init
  setZoom(1);
  setMode('select');
  load();
})();
</script>
@endpush
