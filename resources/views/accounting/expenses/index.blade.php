@extends('layouts.app')

@section('title','Gastos')
@section('titulo','Gastos')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  :root{
    --bg:#f6f8fb; --panel:#ffffff; --text:#0f172a; --muted:#667085; --border:#e7eaf0;
    --pblue:#dbeafe; --pblue-strong:#60a5fa; --pblue-700:#1d4ed8;
    --pgreen:#dcfce7; --pgreen-strong:#34d399; --pgreen-700:#059669;
    --pred:#ffe4e6; --pred-strong:#ef4444;
    --pteal:#e0f2fe; --pteal-strong:#0ea5e9;
    --shadow:0 10px 30px rgba(2,6,23,.06);
  }
  body{ background:var(--bg); color:var(--text); }
  .page-wrap{ max-width:1200px; }

  .hero{
    background: radial-gradient(1200px 150px at 0% 0%, rgba(96,165,250,.18), transparent 40%),
                radial-gradient(1200px 150px at 100% 0%, rgba(14,165,233,.14), transparent 40%),
                #fff;
    border:1px solid var(--border);
    border-radius:18px; padding:16px 18px; box-shadow:var(--shadow);
  }
  .hero h1{ font-weight:900; letter-spacing:-.02em; }
  .subtle{ color:var(--muted) }

  .nav-tabs{ border:0; gap:.4rem }
  .nav-tabs .nav-link{
    border:1px solid var(--border); border-radius:12px; font-weight:800; color:#475467; background:#fff;
    transition:transform .15s ease, box-shadow .2s ease, color .2s
  }
  .nav-tabs .nav-link:hover{ transform:translateY(-1px); box-shadow:0 8px 20px rgba(29,78,216,.12) }
  .nav-tabs .nav-link.active{
    color:#0b2a4a; background:linear-gradient(135deg, var(--pblue), #eff6ff); border-color:#dbeafe
  }

  .btn-pastel-blue{
    color:#0b2a4a; background:var(--pblue); border:1px solid rgba(96,165,250,.45); border-radius:14px; font-weight:900;
    box-shadow:0 10px 22px rgba(96,165,250,.22); transition:transform .12s, box-shadow .2s, filter .2s;
  }
  .btn-pastel-blue:hover{ transform:translateY(-1px); filter:brightness(1.02); box-shadow:0 12px 26px rgba(96,165,250,.30) }

  .btn-pastel-green{
    color:#064e3b; background:var(--pgreen); border:1px solid rgba(52,211,153,.45); border-radius:14px; font-weight:900;
    box-shadow:0 10px 22px rgba(52,211,153,.18); transition:transform .12s, box-shadow .2s, filter .2s;
  }
  .btn-pastel-green:hover{ transform:translateY(-1px); filter:brightness(1.02); box-shadow:0 12px 26px rgba(52,211,153,.26) }

  .btn-outline-soft{
    border-radius:12px; font-weight:800; border:1px solid var(--border); color:#334155; background:#fff;
    transition:transform .12s, box-shadow .2s, background .2s;
  }
  .btn-outline-soft:hover{ background:#f8fafc; box-shadow:0 10px 24px rgba(2,6,23,.06); transform:translateY(-1px) }

  .lift:hover{ transform:translateY(-2px) }

  .card{ border:1px solid var(--border); border-radius:16px; box-shadow:var(--shadow); background:var(--panel) }
  .card .card-header{ background:#fff; border-bottom:1px solid var(--border); color:var(--muted); font-weight:800; }

  .form-label{ color:var(--muted); font-weight:700; font-size:.86rem; }
  .form-control,.form-select{
    border:1px solid var(--border); border-radius:14px; padding:.85rem .95rem;
    transition:border-color .2s, box-shadow .2s, transform .1s
  }
  .form-control:focus,.form-select:focus{
    border-color:#bfdbfe; box-shadow:0 0 0 .25rem rgba(96,165,250,.18); transform:translateY(-1px)
  }

  .metrics-grid{ display:grid; gap:12px; grid-template-columns: repeat(4, 1fr); }
  @media (max-width: 992px){ .metrics-grid{ grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 576px){ .metrics-grid{ grid-template-columns: 1fr; } }
  .metric-card{ padding:14px }
  .metric-title{ color:var(--muted); font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; font-weight:800; }
  .metric-value{ font-weight:1000; font-size:1.6rem; line-height:1.1; }
  .metric-pill{ display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .55rem; border-radius:999px; font-size:.75rem; font-weight:900; }
  .pill-in{ background:var(--pgreen); color:#065f46; border:1px solid rgba(52,211,153,.5) }
  .pill-out{ background:var(--pred); color:#7f1d1d; border:1px solid rgba(239,68,68,.45) }
  .pill-ret{ background:var(--pteal); color:#0c4a6e; border:1px solid rgba(14,165,233,.45) }

  .expected-ok{ background:#f1fff7; }
  .expected-bad{ background:#fff1f2; }

  .skeleton{ position:relative; overflow:hidden; background:#eef2f8; border-radius:10px; min-height:22px }
  .skeleton::after{
    content:""; position:absolute; inset:0; transform:translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.6), transparent);
    animation: shimmer 1.1s infinite;
  }
  @keyframes shimmer{ 100% { transform: translateX(100%); } }

  .grid{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px; }
  @media(max-width: 992px){ .grid{ grid-template-columns:1fr } }

  .xcard{
    border:1px solid var(--border); border-radius:16px; background:#fff; box-shadow:var(--shadow);
    padding:14px; display:flex; gap:14px; align-items:flex-start;
  }
  .thumb{
    width:112px; min-width:112px; height:112px; border-radius:14px;
    background:#f7fbff; border:1px solid #dbeafe;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    color:var(--pblue-700);
  }
  .thumb .small{ color:#64748b }
  .title{
    font-weight:1000; letter-spacing:-.01em; font-size:15px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
  }
  .amount{ font-weight:1000; }
  .meta2{ display:flex; flex-wrap:wrap; gap:8px; color:#667085; font-size:12px; }
  .tag{
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 10px; border-radius:999px; border:1px solid #e7eaf0; background:#fff;
    font-weight:800;
  }
  .tag.soft{ background:#f8fafc; }
  .empty{
    padding:48px 16px; text-align:center; color:#667085;
    background: rgba(255,255,255,.75); border: 1px dashed rgba(17,24,39,.15); border-radius: 18px;
  }

  #chart{ width:100%; height:330px; }

  .mini-error{ background:#fff3cd; border:1px solid #ffe69c; color:#664d03; padding:.6rem .8rem; border-radius:10px; font-size:.9rem; }

  .fab{
    position: fixed; right: 16px; bottom: 18px; z-index: 30;
    border-radius: 999px; padding:.9rem 1.05rem;
    background: var(--pblue-strong); color:#fff; box-shadow: 0 12px 28px rgba(29,78,216,.28);
    display:none; text-decoration:none; font-weight:900;
  }
  .fab:hover{ filter:brightness(1.05); transform: translateY(-1px); }
  @media (max-width: 768px){ .fab{ display:inline-flex; align-items:center; gap:.5rem; } }

  .modal-content{ border:1px solid var(--border); border-radius:18px; box-shadow:var(--shadow) }
</style>

<div class="container page-wrap" >

  {{-- HERO --}}
  <div class="hero mt-2 mb-3 d-flex align-items-center justify-content-between flex-wrap">
    <div class="d-flex align-items-center gap-3">
      <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white border"
           style="width:44px;height:44px;border-color:#dce7ff">
        <i class="bi bi-receipt-cutoff" style="font-size:1.2rem;color:var(--pblue-700)"></i>
      </div>
      <div>
        <h1 class="h4 mb-0">Gastos</h1>
        <div class="small subtle">KPIs, gráfica y movimientos en tiempo real (tipo CashTransaction).</div>
      </div>
    </div>

    <div class="mt-2 mt-md-0 d-none d-md-flex gap-2">
      <a class="btn btn-pastel-green" href="{{ route('expenses.create') }}">
        <i class="bi bi-plus-lg me-1"></i> Nuevo gasto
      </a>
      <button class="btn btn-pastel-blue" type="button" id="btnRefresh">
        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
      </button>
    </div>
  </div>

  {{-- FAB mobile --}}
  <a href="{{ route('expenses.create') }}" class="fab">
    <i class="bi bi-plus-lg"></i> Gasto
  </a>

  {{-- TABS --}}
  <ul class="nav nav-tabs" id="expTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pane-dash" type="button" role="tab">
        <i class="bi bi-speedometer2 me-1"></i> Dashboard
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-filters" type="button" role="tab">
        <i class="bi bi-funnel me-1"></i> Filtros
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#pane-list" type="button" role="tab">
        <i class="bi bi-grid-3x3-gap me-1"></i> Listado
      </button>
    </li>
  </ul>

  {{-- AVISO API --}}
  <div id="warn" class="mini-error mt-3 d-none"><i class="bi bi-wifi-off me-1"></i> No se pudo contactar a la API.</div>

  <div class="tab-content mt-3">

    {{-- =================== TAB DASHBOARD =================== --}}
    <div class="tab-pane fade show active" id="pane-dash" role="tabpanel" tabindex="0">

      {{-- KPIs --}}
      <div class="metrics-grid">
        <div class="card metric-card">
          <div class="metric-title">Total pagado <span class="metric-pill pill-out ms-1"><i class="bi bi-arrow-up-right"></i> OUT</span></div>
          <div class="metric-value" id="kPaid"><span class="skeleton" style="height:28px;width:140px;display:inline-block"></span></div>
        </div>

        <div class="card metric-card">
          <div class="metric-title">Total pendiente <span class="metric-pill pill-ret ms-1"><i class="bi bi-hourglass-split"></i> PEND</span></div>
          <div class="metric-value" id="kPending"><span class="skeleton" style="height:28px;width:140px;display:inline-block"></span></div>
        </div>

        <div class="card metric-card">
          <div class="metric-title">Cancelado <span class="metric-pill pill-out ms-1" style="background:var(--pred);border-color:rgba(239,68,68,.45)"><i class="bi bi-x-circle"></i> CAN</span></div>
          <div class="metric-value" id="kCanceled"><span class="skeleton" style="height:28px;width:140px;display:inline-block"></span></div>
        </div>

        <div class="card metric-card expected-ok" id="cardTotal">
          <div class="metric-title">Total general</div>
          <div class="metric-value" id="kTotal"><span class="skeleton" style="height:28px;width:140px;display:inline-block"></span></div>
        </div>
      </div>

      {{-- CHART --}}
      <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span>Gasto por día</span>
          <small class="subtle">Apila pagado / pendiente / cancelado</small>
        </div>
        <div class="card-body">
          <div style="position:relative;height:330px">
            <canvas id="chart"></canvas>
          </div>
        </div>
      </div>

      {{-- RANKING --}}
      <div class="card mt-3">
        <div class="card-header">Top proveedores</div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Proveedor</th>
                  <th class="text-end">Gastado</th>
                  <th class="text-center"># Movs</th>
                </tr>
              </thead>
              <tbody id="rankingBody">
                <tr><td colspan="3" class="p-3"><span class="skeleton" style="height:18px;display:block;"></span></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- MOVIMIENTOS --}}
      <div class="card mt-3">
        <div class="card-header d-flex align-items-center justify-content-between">
          <span>Movimientos</span>
          <small class="text-muted d-none d-md-inline">Actualiza cada 20s</small>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Fecha</th>
                  <th>Tipo</th>
                  <th>Detalle</th>
                  <th class="text-end">Monto</th>
                  <th>Estatus</th>
                  <th>PDF</th>
                </tr>
              </thead>
              <tbody id="tbody">
                <tr>
                  <td colspan="7" class="p-3">
                    <div class="skeleton" style="height:18px;margin-bottom:6px;"></div>
                    <div class="skeleton" style="height:18px;width:70%;"></div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <button class="btn btn-outline-soft btn-sm" id="prevPage" type="button"><i class="bi bi-chevron-left"></i></button>
          <div id="pageInfo" class="small text-muted">Página 1</div>
          <button class="btn btn-outline-soft btn-sm" id="nextPage" type="button"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>

    </div>

    {{-- =================== TAB FILTROS =================== --}}
    <div class="tab-pane fade" id="pane-filters" role="tabpanel" tabindex="0">
      <div class="card">
        <div class="card-body">
          <div class="row g-3">

            <div class="col-12 col-md-3">
              <label class="form-label">Desde</label>
              <input id="from" type="date" class="form-control">
            </div>

            <div class="col-12 col-md-3">
              <label class="form-label">Hasta</label>
              <input id="to" type="date" class="form-control">
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Buscar</label>
              <div class="input-group">
                <span class="input-group-text bg-white border" style="border-radius:14px 0 0 14px;border-color:var(--border)">
                  <i class="bi bi-search"></i>
                </span>
                <input id="q" class="form-control" placeholder="concepto, proveedor, descripción…"
                       style="border-radius:0 14px 14px 0">
              </div>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Categoría (gastos)</label>
              <select id="cat" class="form-select">
                <option value="">Todas</option>
                @foreach($categories as $c)
                  <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Vehículo (gastos)</label>
              <select id="veh" class="form-select">
                <option value="">Todos</option>
                @foreach($vehicles as $v)
                  <option value="{{ $v->id }}">{{ $v->plate_label ?? ($v->plate ?? $v->placas ?? ('#'.$v->id)) }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Nómina (gastos)</label>
              <select id="period" class="form-select">
                <option value="">Todas</option>
                @foreach(($periods ?? []) as $p)
                  <option value="{{ $p->id }}">{{ $p->title ?? $p->name ?? ('Periodo #'.$p->id) }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label">Estatus</label>
              <select id="status" class="form-select">
                <option value="">Todos</option>
                <option value="paid">paid</option>
                <option value="pending">pending</option>
                <option value="canceled">canceled</option>
              </select>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
              <button class="btn btn-outline-soft" type="button" id="btnClear">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar
              </button>
              <button class="btn btn-pastel-blue" type="button" id="btnApply">
                <i class="bi bi-funnel-fill me-1"></i> Aplicar
              </button>
            </div>

          </div>
        </div>
      </div>

      <div class="text-muted small mt-2">
        Tip: aplica filtros y vuelve a “Dashboard”; se actualiza solo.
      </div>
    </div>

    {{-- =================== TAB LISTADO (cards) =================== --}}
    <div class="tab-pane fade" id="pane-list" role="tabpanel" tabindex="0">

      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <span class="tag soft"><i class="bi bi-hash"></i> <span id="kpiCount">0</span> registros</span>
          <span class="tag"><i class="bi bi-currency-dollar"></i> <span id="kpiSum">$0.00</span> total</span>
        </div>

        <div class="d-flex gap-2 align-items-center">
          <select id="perPage" class="form-select" style="width:140px">
            <option value="10">10 / pág</option>
            <option value="20" selected>20 / pág</option>
            <option value="30">30 / pág</option>
            <option value="50">50 / pág</option>
            <option value="60">60 / pág</option>
          </select>

          <div class="btn-group">
            <button class="btn btn-outline-soft btn-sm" id="btnPrev" type="button" title="Anterior">
              <i class="bi bi-chevron-left"></i>
            </button>
            <button class="btn btn-outline-soft btn-sm" id="btnNext" type="button" title="Siguiente">
              <i class="bi bi-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

      <div id="grid" class="grid">
        <div class="empty">Cargando…</div>
      </div>

      <div class="text-center text-muted small mt-3">
        Página <span id="pageNow">1</span> de <span id="pageLast">1</span> • Total: <span id="totalAll">0</span>
      </div>
    </div>

  </div>
</div>

{{-- MODAL Evidencia + Recibo/Firmas --}}
<div class="modal fade" id="evidenceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div>
          <div class="fw-bold" id="evTitle">Detalle</div>
          <div class="text-muted small" id="evSub">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body pt-0">
        <div class="d-flex flex-wrap gap-2 mb-3" id="evBadges"></div>

        <div class="row g-3">
          <div class="col-12">
            <div class="fw-bold mb-2">Evidencia</div>
            <div id="evBody" class="text-center text-muted">—</div>
          </div>

          <div class="col-12">
            <div class="fw-bold mb-2">Firmas</div>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="border rounded-4 p-2" style="border-color:var(--border)!important;background:#fff">
                  <div class="small text-muted mb-2">Firma (admin)</div>
                  <div id="sigMgr" class="text-muted small">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <div class="border rounded-4 p-2" style="border-color:var(--border)!important;background:#fff">
                  <div class="small text-muted mb-2">Firma (contraparte)</div>
                  <div id="sigCtp" class="text-muted small">—</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12">
            <div class="fw-bold mb-2">Recibo</div>
            <div id="rcBody" class="text-muted small">—</div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 d-flex justify-content-between">
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-pastel-blue d-none" id="evDownload" href="#" target="_blank" rel="noopener">
            <i class="bi bi-download me-1"></i> Descargar evidencia
          </a>
          <a class="btn btn-pastel-green d-none" id="rcDownload" href="#" target="_blank" rel="noopener">
            <i class="bi bi-filetype-pdf me-1"></i> Ver recibo
          </a>
        </div>
        <button class="btn btn-outline-soft" data-bs-dismiss="modal" type="button">
          Cerrar
        </button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
(() => {
  const API_LIST    = "{{ route('expenses.api.list', [], false) }}";
  const API_METRICS = "{{ route('expenses.api.metrics', [], false) }}";

  const API_CHART   = "{{ \Illuminate\Support\Facades\Route::has('expenses.api.chart') ? route('expenses.api.chart', [], false) : '' }}";
  const API_RANKING = "{{ \Illuminate\Support\Facades\Route::has('expenses.api.ranking') ? route('expenses.api.ranking', [], false) : '' }}";

  const $ = (id)=>document.getElementById(id);

  let state = {
    page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    rows: [],
    timer: null,
  };

  function esc(s){
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function money(n, currency='MXN'){
    const x = Number(n || 0);
    try{ return x.toLocaleString('es-MX', {style:'currency', currency: currency || 'MXN'}); }
    catch(_){ return '$' + x.toFixed(2); }
  }
  function normalizeDate(d){
    if(!d) return null;
    const s = String(d).slice(0,10);
    if(!/^\d{4}-\d{2}-\d{2}$/.test(s)) return null;
    if(s.startsWith('0000')) return null;
    return s;
  }
  function fmtDate(d){
    const s = normalizeDate(d);
    if(!s) return '—';
    const dt = new Date(s + 'T00:00:00');
    return dt.toLocaleDateString('es-MX', {day:'2-digit', month:'short', year:'numeric'});
  }
  function fmtDateTime(d){
    if(!d) return null;
    const s = String(d).replace(' ', 'T');
    const dt = new Date(s);
    if (isNaN(dt.getTime())) return null;
    return dt.toLocaleString('es-MX', {year:'numeric', month:'short', day:'2-digit', hour:'2-digit', minute:'2-digit'});
  }

  function warn(show, msg){
    const w = $('warn');
    if(!w) return;
    if(show){
      w.classList.remove('d-none');
      w.innerHTML = `<i class="bi bi-wifi-off me-1"></i> ${esc(msg || 'No se pudo contactar a la API.')}`;
    }else{
      w.classList.add('d-none');
    }
  }

  function params(extra = {}){
    const p = new URLSearchParams();
    const q = $('q')?.value?.trim() || '';
    const cat = $('cat')?.value?.trim() || '';
    const veh = $('veh')?.value?.trim() || '';
    const period = $('period')?.value?.trim() || '';
    const status = $('status')?.value?.trim() || '';
    const from = $('from')?.value?.trim() || '';
    const to = $('to')?.value?.trim() || '';

    if(q) p.set('q', q);
    if(cat) p.set('category_id', cat);
    if(veh) p.set('vehicle_id', veh);

    // Nómina: si tu API filtra por payroll_period_id úsalo; si guardas string, tu API puede usar payroll_period
    if(period) {
      p.set('payroll_period_id', period);
      p.set('payroll_period', period);
    }

    if(status) p.set('status', status);
    if(from) p.set('from', from);
    if(to) p.set('to', to);

    p.set('per_page', String(state.per_page));
    p.set('page', String(state.page));
    p.set('_t', String(Date.now()));

    Object.entries(extra).forEach(([k,v])=>{
      if(v === null || v === undefined || v === '') return;
      p.set(k, String(v));
    });

    return p;
  }

  // Chart init
  const chartEl = document.getElementById('chart');
  const chart = chartEl ? new Chart(chartEl, {
    type:'bar',
    data:{ labels:[], datasets:[
      {label:'Pagado',    data:[], backgroundColor:'rgba(239,68,68,.80)',  borderWidth:0, borderRadius:7, borderSkipped:false},
      {label:'Pendiente', data:[], backgroundColor:'rgba(14,165,233,.80)', borderWidth:0, borderRadius:7, borderSkipped:false},
      {label:'Cancelado', data:[], backgroundColor:'rgba(148,163,184,.75)', borderWidth:0, borderRadius:7, borderSkipped:false},
    ]},
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{ legend:{ labels:{ boxWidth:10, boxHeight:10, usePointStyle:true } } },
      scales:{
        x:{ stacked:true, grid:{ display:false }, ticks:{ maxRotation:0, autoSkip:true, color:'#475569' } },
        y:{ stacked:true, grid:{ color:'rgba(148,163,184,.25)' }, ticks:{ color:'#475569' } }
      }
    }
  }) : null;

  async function loadMetrics(){
    const url = API_METRICS + '?' + params({page:null}).toString().replace(/(^|&)page=\d+(&|$)/,'$1').replace(/^&|&$/g,'');
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok){ warn(true, 'No se pudo cargar métricas.'); return null; }
    const data = await res.json().catch(()=>null);
    if(!data){ warn(true, 'Métricas inválidas.'); return null; }

    warn(false);

    $('kpiCount').textContent = String(data.count ?? 0);
    $('kpiSum').textContent = money(data.sum ?? 0, data.currency ?? 'MXN');

    const currency = data.currency ?? 'MXN';
    const paid = data.paid_sum ?? null;
    const pending = data.pending_sum ?? null;
    const canceled = data.canceled_sum ?? null;

    if(paid !== null || pending !== null || canceled !== null){
      $('kPaid').textContent    = money(paid ?? 0, currency);
      $('kPending').textContent = money(pending ?? 0, currency);
      $('kCanceled').textContent = money(canceled ?? 0, currency);
      $('kTotal').textContent   = money((paid??0)+(pending??0)+(canceled??0), currency);
    }else{
      $('kPaid').textContent     = money(0, currency);
      $('kPending').textContent  = money(0, currency);
      $('kCanceled').textContent = money(0, currency);
      $('kTotal').textContent    = money(data.sum ?? 0, currency);
    }

    return data;
  }

  async function loadChart(){
    if(!chart || !API_CHART) return;
    const url = API_CHART + '?' + params({page:null, per_page:null}).toString();
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok) return;

    const rows = await res.json().catch(()=>[]);
    if(!Array.isArray(rows) || !rows.length){
      chart.data.labels = [];
      chart.data.datasets[0].data = [];
      chart.data.datasets[1].data = [];
      chart.data.datasets[2].data = [];
      chart.update();
      return;
    }

    chart.data.labels = rows.map(r=>r.date);
    chart.data.datasets[0].data = rows.map(r=>Number(r.paid||0));
    chart.data.datasets[1].data = rows.map(r=>Number(r.pending||0));
    chart.data.datasets[2].data = rows.map(r=>Number(r.canceled||0));
    chart.update();
  }

  async function loadRanking(){
    const rb = $('rankingBody');
    if(!rb) return;

    if(!API_RANKING){
      rb.innerHTML = `<tr><td colspan="3" class="text-center p-3 text-muted">Ranking no disponible (ruta expenses.api.ranking no existe).</td></tr>`;
      return;
    }

    const url = API_RANKING + '?' + params({page:null, per_page:null}).toString();
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok){
      rb.innerHTML = `<tr><td colspan="3" class="text-center p-3 text-muted">No se pudo cargar ranking.</td></tr>`;
      return;
    }

    const rows = await res.json().catch(()=>[]);
    rb.innerHTML = '';

    if(!Array.isArray(rows) || !rows.length){
      rb.innerHTML = `<tr><td colspan="3" class="text-center p-3 text-muted">Sin datos.</td></tr>`;
      return;
    }

    rows.forEach(r=>{
      rb.insertAdjacentHTML('beforeend', `
        <tr>
          <td>${esc(r.vendor || '—')}</td>
          <td class="text-end">${money(r.sum || 0, r.currency || 'MXN')}</td>
          <td class="text-center">${esc(r.count ?? 0)}</td>
        </tr>
      `);
    });
  }

  async function loadList(){
    const url = API_LIST + '?' + params().toString();
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok){
      warn(true, `Error cargando lista (status ${res.status}).`);
      $('grid').innerHTML = `<div class="empty">Error cargando.</div>`;
      return;
    }
    const data = await res.json().catch(()=>null);
    if(!data){
      warn(true, 'Respuesta inválida.');
      $('grid').innerHTML = `<div class="empty">Respuesta inválida.</div>`;
      return;
    }

    warn(false);

    state.rows = data.data || [];
    state.page = data.meta?.page || 1;
    state.last_page = data.meta?.last_page || 1;
    state.total = data.meta?.total || 0;

    $('pageNow').textContent = String(state.page);
    $('pageLast').textContent = String(state.last_page);
    $('totalAll').textContent = String(state.total);

    renderCards();
    renderTable(data);
  }

  function openEvidence(e){
    const modal = new bootstrap.Modal(document.getElementById('evidenceModal'));

    $('evTitle').textContent = e.concept || 'Detalle';

    const t = e.movement_kind || e.entry_kind || e.expense_type || (e.is_movement ? 'movimiento' : 'gasto');
    const line1 = [
      t ? `Tipo: ${t}` : null,
      e.category?.name ? `Categoría: ${e.category.name}` : null,
      e.vehicle?.plate ? `Vehículo: ${e.vehicle.plate}` : null,
      e.vehicle?.plate_label ? `Vehículo: ${e.vehicle.plate_label}` : null,
      e.payroll_period ? `Nómina: ${e.payroll_period}` : null,
    ].filter(Boolean).join(' • ');
    $('evSub').textContent = line1 || '—';

    const badges = [];
    const when = fmtDateTime(e.performed_at) || fmtDateTime(e.expense_date) || fmtDate(e.expense_date);
    if (when) badges.push(`<span class="tag soft"><i class="bi bi-clock"></i> ${esc(when)}</span>`);
    if (e.status) badges.push(`<span class="tag"><i class="bi bi-activity"></i> ${esc(e.status)}</span>`);
    if (e.payment_method) badges.push(`<span class="tag soft"><i class="bi bi-credit-card"></i> ${esc(e.payment_method)}</span>`);
    if (e.nip_approved_at) badges.push(`<span class="tag"><i class="bi bi-shield-check"></i> Aprobado</span>`);
    $('evBadges').innerHTML = badges.join('');

    $('evDownload').classList.toggle('d-none', !e.evidence_url);
    if(e.evidence_url) $('evDownload').href = e.evidence_url;

    const body = $('evBody');
    body.innerHTML = '';

    if(!e.evidence_url){
      body.innerHTML = `<div class="text-muted">Sin evidencia</div>`;
    } else {
      const mime = (e.evidence_mime || '').toLowerCase();
      if(mime.includes('pdf')){
        body.innerHTML = `
          <div class="ratio ratio-16x9">
            <iframe src="${esc(e.evidence_url)}" style="border:0;border-radius:12px"></iframe>
          </div>
        `;
      } else if(mime.includes('image')){
        body.innerHTML = `
          <img src="${esc(e.evidence_url)}" alt="evidencia" class="img-fluid" style="border-radius:14px;border:1px solid #e8eef6">
        `;
      } else {
        body.innerHTML = `<div class="text-muted">Archivo no previsualizable.</div>`;
      }
    }

    const mgrBox = $('sigMgr');
    const ctpBox = $('sigCtp');

    if (e.manager_signature_url){
      mgrBox.innerHTML = `<img src="${esc(e.manager_signature_url)}" alt="firma admin" class="img-fluid" style="max-height:140px;border-radius:12px;border:1px solid #e8eef6">`;
    } else if (e.admin_signature_url){
      mgrBox.innerHTML = `<img src="${esc(e.admin_signature_url)}" alt="firma admin" class="img-fluid" style="max-height:140px;border-radius:12px;border:1px solid #e8eef6">`;
    } else {
      mgrBox.textContent = '—';
    }

    if (e.counterparty_signature_url){
      ctpBox.innerHTML = `<img src="${esc(e.counterparty_signature_url)}" alt="firma contraparte" class="img-fluid" style="max-height:140px;border-radius:12px;border:1px solid #e8eef6">`;
    } else if (e.receiver_signature_url){
      ctpBox.innerHTML = `<img src="${esc(e.receiver_signature_url)}" alt="firma contraparte" class="img-fluid" style="max-height:140px;border-radius:12px;border:1px solid #e8eef6">`;
    } else {
      ctpBox.textContent = '—';
    }

    $('rcDownload').classList.toggle('d-none', !e.pdf_url);
    if (e.pdf_url){
      $('rcDownload').href = e.pdf_url;
      $('rcBody').innerHTML = `<span class="text-muted">Recibo disponible.</span>`;
    } else {
      $('rcBody').textContent = '—';
    }

    modal.show();
  }

  function renderCards(){
    const rows = state.rows;
    const grid = $('grid');

    if(!rows.length){
      grid.innerHTML = `<div class="empty">Sin resultados</div>`;
      return;
    }

    grid.innerHTML = rows.map(e => {
      const title  = e.concept || 'Gasto';
      const amount = money(e.amount, e.currency || 'MXN');
      const date   = fmtDate(e.expense_date);

      const type = e.movement_kind || e.entry_kind || e.expense_type || (e.is_movement ? 'movimiento' : 'gasto');
      const cat  = e.category?.name || e.vehicle_category || e.payroll_category || '—';

      const veh    = e.vehicle?.plate || e.vehicle?.plate_label || null;
      const period = e.payroll_period || e.payroll_period_label || null;

      const chips = [
        `<span class="tag soft"><i class="bi bi-layers"></i> ${esc(type)}</span>`,
        `<span class="tag soft"><i class="bi bi-tag"></i> ${esc(cat)}</span>`,
        veh ? `<span class="tag"><i class="bi bi-truck"></i> ${esc(veh)}</span>` : '',
        period ? `<span class="tag soft"><i class="bi bi-calendar2-week"></i> ${esc(period)}</span>` : '',
        e.status ? `<span class="tag"><i class="bi bi-activity"></i> ${esc(e.status)}</span>` : '',
        e.payment_method ? `<span class="tag soft"><i class="bi bi-credit-card"></i> ${esc(e.payment_method)}</span>` : '',
        e.has_evidence ? `<span class="tag"><i class="bi bi-paperclip"></i> Evidencia</span>` : `<span class="tag soft"><i class="bi bi-paperclip"></i> Sin evidencia</span>`,
        e.pdf_url ? `<span class="tag"><i class="bi bi-filetype-pdf"></i> Recibo</span>` : '',
        e.nip_approved_at ? `<span class="tag soft"><i class="bi bi-shield-check"></i> Aprobado</span>` : '',
      ].filter(Boolean).join('');

      return `
        <div class="xcard">
          <div class="thumb">
            <i class="bi bi-cash-stack" style="font-size:1.2rem"></i>
            <div class="small mt-1">${esc(date)}</div>
          </div>

          <div class="flex-grow-1" style="min-width:0">
            <div class="d-flex justify-content-between gap-2">
              <div class="title" title="${esc(title)}">${esc(title)}</div>
              <div class="amount">${esc(amount)}</div>
            </div>

            <div class="meta2 mt-2">${chips}</div>

            <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap">
              <button class="btn btn-outline-soft btn-sm" type="button" data-detail="${e.id}">
                <i class="bi bi-eye me-1"></i> Detalle
              </button>

              <a class="btn btn-outline-soft btn-sm" href="{{ url('/expenses') }}/${e.id}">
                <i class="bi bi-box-arrow-up-right me-1"></i> Ver
              </a>
              <a class="btn btn-outline-soft btn-sm" href="{{ url('/expenses') }}/${e.id}/edit">
                <i class="bi bi-pencil-square me-1"></i> Editar
              </a>
            </div>
          </div>
        </div>
      `;
    }).join('');

    document.querySelectorAll('[data-detail]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = Number(btn.getAttribute('data-detail'));
        const row = state.rows.find(x => Number(x.id) === id);
        if(row) openEvidence(row);
      });
    });
  }

  function renderTable(listResponse){
    const tb = document.getElementById('tbody');
    if(!tb) return;

    tb.innerHTML = '';
    const rows = state.rows || [];

    if(!rows.length){
      tb.innerHTML = `<tr><td colspan="7" class="text-center p-3 text-muted">Sin movimientos.</td></tr>`;
    } else {
      rows.forEach(e=>{
        const date = fmtDate(e.expense_date);

        const type = e.movement_kind || e.entry_kind || e.expense_type || (e.is_movement ? 'movimiento' : 'gasto');
        const detail = e.category?.name || e.vehicle_category || e.payroll_category || e.description || '-';

        const amount = money(e.amount, e.currency || 'MXN');
        const status = e.status || '-';
        const pdfBtn = e.pdf_url
          ? `<a target="_blank" rel="noopener" class="btn btn-sm btn-outline-soft" href="${esc(e.pdf_url)}"><i class="bi bi-file-earmark-pdf"></i></a>`
          : '-';

        tb.insertAdjacentHTML('beforeend', `
          <tr>
            <td>${esc(e.id)}</td>
            <td>${esc(date)}</td>
            <td>${esc(type)}</td>
            <td style="max-width:320px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(detail)}</td>
            <td class="text-end">${esc(amount)}</td>
            <td>${esc(status)}</td>
            <td>${pdfBtn}</td>
          </tr>
        `);
      });
    }

    const meta = listResponse?.meta || {};
    const pp = meta.per_page ?? state.per_page;
    const info = document.getElementById('pageInfo');
    if(info){
      info.textContent = `Página ${meta.page ?? state.page} de ${meta.last_page ?? state.last_page} — ${meta.total ?? state.total} movs${pp?` · ${pp}/pág`:''}`;
    }
    document.getElementById('prevPage')?.toggleAttribute('disabled', state.page<=1);
    document.getElementById('nextPage')?.toggleAttribute('disabled', state.page>=state.last_page);
  }

  async function refreshAll(){
    try{
      await Promise.all([loadMetrics(), loadList(), loadChart(), loadRanking()]);
    }catch(e){
      console.error(e);
      warn(true, 'Error al refrescar.');
    }
  }

  // Eventos UI
  $('btnRefresh')?.addEventListener('click', ()=>{ state.page = 1; refreshAll(); });

  $('btnApply')?.addEventListener('click', ()=>{
    state.page = 1;
    refreshAll();
    const tab = new bootstrap.Tab(document.querySelector('[data-bs-target="#pane-dash"]'));
    tab.show();
  });

  $('btnClear')?.addEventListener('click', ()=>{
    ['from','to','q','cat','veh','period','status'].forEach(id=>{
      const el = $(id); if(!el) return; el.value = '';
    });
    state.page = 1;
    refreshAll();
  });

  $('perPage')?.addEventListener('change', ()=>{
    state.per_page = Number($('perPage').value || 20);
    state.page = 1;
    refreshAll();
  });

  $('btnPrev')?.addEventListener('click', ()=>{
    if(state.page <= 1) return;
    state.page--;
    refreshAll();
  });

  $('btnNext')?.addEventListener('click', ()=>{
    if(state.page >= state.last_page) return;
    state.page++;
    refreshAll();
  });

  document.getElementById('prevPage')?.addEventListener('click', ()=>{
    if(state.page <= 1) return;
    state.page--;
    refreshAll();
  });

  document.getElementById('nextPage')?.addEventListener('click', ()=>{
    if(state.page >= state.last_page) return;
    state.page++;
    refreshAll();
  });

  $('q')?.addEventListener('keydown', (e)=>{
    if(e.key === 'Enter'){
      e.preventDefault();
      state.page = 1;
      refreshAll();
      const tab = new bootstrap.Tab(document.querySelector('[data-bs-target="#pane-dash"]'));
      tab.show();
    }
  });

  if(state.timer) clearInterval(state.timer);
  state.timer = setInterval(()=>refreshAll(), 20000);

  refreshAll();
})();
</script>
@endsection
