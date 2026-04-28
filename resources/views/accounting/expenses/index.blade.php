@extends('layouts.app')

@section('title','Gastos')
@section('titulo','Gastos')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
  /* =========================================================
     ✅ ESTILOS ENCAPSULADOS (NO TOCAN BODY / :root / MENÚ)
     ========================================================= */
  #expensesPage{
    --bg:#f6f8fb; --panel:#ffffff; --text:#0f172a; --muted:#667085; --border:#e7eaf0;
    --pblue:#dbeafe; --pblue-strong:#60a5fa; --pblue-700:#1d4ed8;
    --pgreen:#dcfce7; --pgreen-strong:#34d399; --pgreen-700:#059669;
    --pred:#ffe4e6; --pred-strong:#ef4444;
    --pteal:#e0f2fe; --pteal-strong:#0ea5e9;
    --shadow: 0 10px 40px -10px rgba(15, 23, 42, 0.08); /* Sombra más premium y difusa */
    color: var(--text);
  }

  /* =========================================================
     ✨ ANIMACIONES PREMIUM
     ========================================================= */
  @keyframes fadeInUp {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .animate-fade-in {
    opacity: 0;
    animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }

  @keyframes pulseSoft {
    0% { transform: scale(1); }
    50% { transform: scale(1.02); }
    100% { transform: scale(1); }
  }

  /* Fondo de la vista */
  #expensesPage .page-bg{
    background: var(--bg);
    border-radius: 24px;
    padding: 16px;
    transition: all 0.3s ease;
  }

  #expensesPage .page-wrap{ max-width:1200px; margin: 0 auto; }

  #expensesPage .hero{
    background: radial-gradient(1200px 150px at 0% 0%, rgba(96,165,250,.12), transparent 50%),
                radial-gradient(1200px 150px at 100% 0%, rgba(14,165,233,.08), transparent 50%),
                #fff;
    border:1px solid var(--border);
    border-radius: 20px; 
    padding: 20px 24px; 
    box-shadow: var(--shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  #expensesPage .hero:hover {
    box-shadow: 0 15px 50px -10px rgba(15, 23, 42, 0.12);
  }
  
  #expensesPage .hero h1{ font-weight:900; letter-spacing:-.02em; color: #0f172a; }
  #expensesPage .subtle{ color:var(--muted); font-weight: 500; }

  /* Botones con transiciones más suaves */
  #expensesPage .btn-pastel-blue{
    color:#0b2a4a; background:var(--pblue); border:1px solid rgba(96,165,250,.45); border-radius:14px; font-weight:800;
    box-shadow:0 8px 20px rgba(96,165,250,.2); transition:all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #expensesPage .btn-pastel-blue:hover{ transform:translateY(-2px); box-shadow:0 12px 25px rgba(96,165,250,.35); background: #eff6ff; }

  #expensesPage .btn-pastel-green{
    color:#064e3b; background:var(--pgreen); border:1px solid rgba(52,211,153,.45); border-radius:14px; font-weight:800;
    box-shadow:0 8px 20px rgba(52,211,153,.15); transition:all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #expensesPage .btn-pastel-green:hover{ transform:translateY(-2px); box-shadow:0 12px 25px rgba(52,211,153,.30); background: #f0fdf4; }

  #expensesPage .btn-outline-soft{
    border-radius:12px; font-weight:700; border:1px solid var(--border); color:#334155; background:#fff;
    transition:all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #expensesPage .btn-outline-soft:hover{ background:#f8fafc; border-color: #cbd5e1; box-shadow:0 8px 20px rgba(15,23,42,.05); transform:translateY(-1px); color: #0f172a; }

  /* Tarjetas */
  #expensesPage .card{ 
    border:1px solid var(--border); 
    border-radius:20px; 
    box-shadow:var(--shadow); 
    background:var(--panel);
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #expensesPage .card:hover {
    box-shadow: 0 15px 50px -10px rgba(15, 23, 42, 0.12);
  }
  #expensesPage .card .card-header{ background:transparent; border-bottom:1px solid var(--border); color:#1e293b; font-weight:800; padding: 18px 20px; }

  /* Formularios */
  #expensesPage .form-label{ color:var(--muted); font-weight:700; font-size:.85rem; text-transform: uppercase; letter-spacing: 0.02em; }
  #expensesPage .form-control,
  #expensesPage .form-select{
    border:1px solid var(--border); border-radius:14px; padding:.85rem 1rem; font-weight: 500;
    transition:all 0.25s ease; background: #fdfdfd;
  }
  #expensesPage .form-control:focus,
  #expensesPage .form-select:focus{
    background: #fff; border-color:var(--pblue-strong); box-shadow:0 0 0 4px rgba(96,165,250,.15); transform:translateY(-1px); outline: none;
  }

  /* KPIs */
  #expensesPage .metrics-grid{ display:grid; gap:16px; grid-template-columns: repeat(4, 1fr); }
  @media (max-width: 992px){ #expensesPage .metrics-grid{ grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 576px){ #expensesPage .metrics-grid{ grid-template-columns: 1fr; } }
  
  #expensesPage .metric-card{ padding:20px; border-radius: 20px; border: 1px solid var(--border); }
  #expensesPage .metric-title{ color:var(--muted); font-size:.75rem; text-transform:uppercase; letter-spacing:.05em; font-weight:800; display:flex; align-items:center; justify-content:space-between; gap:10px; }
  #expensesPage .metric-value{ font-weight:900; font-size:1.85rem; line-height:1.2; margin-top:8px; color: var(--text); letter-spacing: -0.02em; }
  #expensesPage .metric-sub{ color:#64748b; font-weight:700; font-size:.85rem; margin-top:4px; }
  
  #expensesPage .metric-pill{ display:inline-flex; align-items:center; gap:.4rem; padding:.3rem .6rem; border-radius:999px; font-size:.7rem; font-weight:800; border:1px solid transparent; }
  #expensesPage .pill-out{ background:var(--pred); color:#991b1b; border-color:rgba(239,68,68,.3) }
  #expensesPage .pill-ret{ background:var(--pteal); color:#075985; border-color:rgba(14,165,233,.3) }
  #expensesPage .pill-can{ background:#f1f5f9; color:#475569; border-color:rgba(148,163,184,.4) }
  #expensesPage .pill-count{ background:#f8fafc; color:#334155; border:1px solid rgba(148,163,184,.35); padding:.35rem .75rem; font-weight:900; }

  #expensesPage .expected-ok{ background: linear-gradient(145deg, #ffffff, #f0fdf4); border-color: rgba(52,211,153,.3); }

  /* Skeletons */
  #expensesPage .skeleton{ position:relative; overflow:hidden; background:#f1f5f9; border-radius:8px; min-height:24px }
  #expensesPage .skeleton::after{
    content:""; position:absolute; inset:0; transform:translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.8), transparent);
    animation: expensesShimmer 1.2s infinite ease-in-out;
  }
  @keyframes expensesShimmer{ 100% { transform: translateX(100%); } }

  /* Listado Grid */
  #expensesPage .grid{ display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
  @media(max-width: 992px){ #expensesPage .grid{ grid-template-columns:1fr } }

  #expensesPage .xcard{
    border:1px solid var(--border); border-radius:20px; background:#fff; box-shadow:var(--shadow);
    padding:16px; display:flex; gap:16px; align-items:flex-start;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  }
  #expensesPage .xcard:hover{
    transform: translateY(-3px); box-shadow: 0 15px 45px -10px rgba(15, 23, 42, 0.1); border-color: var(--pblue-strong);
  }
  #expensesPage .thumb{
    width:100px; min-width:100px; height:100px; border-radius:16px;
    background: linear-gradient(135deg, #f8fafc, #eff6ff); border:1px solid #e2e8f0;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    color:var(--pblue-700); transition: all 0.3s ease;
  }
  #expensesPage .xcard:hover .thumb { background: var(--pblue); border-color: var(--pblue-strong); }
  
  #expensesPage .thumb .small{ color:#475569; font-weight: 700; margin-top: 4px; }
  #expensesPage .title{ font-weight:800; letter-spacing:-.01em; font-size:16px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color: #0f172a; }
  #expensesPage .amount{ font-weight:900; font-size: 1.1rem; color: #0f172a; }
  #expensesPage .meta2{ display:flex; flex-wrap:wrap; gap:8px; color:#64748b; font-size:12px; margin-top: 8px; }
  
  #expensesPage .tag{
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 12px; border-radius:999px; border:1px solid var(--border); background:#fff;
    font-weight:700; color: #334155; transition: all 0.2s ease;
  }
  #expensesPage .tag.soft{ background:#f8fafc; color: #475569; border-color: transparent; }
  
  #expensesPage .empty{ padding:60px 20px; text-align:center; color:var(--muted); font-weight: 600; background: rgba(255,255,255,.6); border: 2px dashed var(--border); border-radius: 20px; }

  /* Charts */
  #expensesPage #chart{ width:100%; height:340px; }
  #expensesPage #pieChart{ width:100%; height:330px; }

  #expensesPage .mini-error{ background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:.8rem 1rem; border-radius:12px; font-size:.9rem; font-weight: 600; }

  /* Tabs Navigation */
  #expensesPage .tabs-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:16px; }
  #expensesPage .tabs-pills{ display:flex; gap:10px; flex-wrap:wrap; background: #fff; padding: 6px; border-radius: 999px; border: 1px solid var(--border); box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
  #expensesPage .tab-pill{
    border: none; background:transparent; color:#64748b; border-radius:999px; padding:.6rem 1.2rem;
    font-weight:700; font-size: 0.9rem; display:inline-flex; align-items:center; gap:.5rem;
    transition:all 0.3s cubic-bezier(0.16, 1, 0.3, 1); user-select:none;
  }
  #expensesPage .tab-pill:hover{ color: #0f172a; background: #f1f5f9; }
  #expensesPage .tab-pill.active{
    background:var(--text); color:#fff; box-shadow:0 8px 20px rgba(15,23,42,.2); transform: translateY(-1px);
  }

  #expensesPage .mobile-switch{ display:none; margin-top:16px; }
  @media (max-width: 767.98px){
    #expensesPage .tabs-row{ display:none; }
    #expensesPage .mobile-switch{ display:block; }
    #expensesPage .mobile-switch .form-select{ border-radius:14px; font-weight:800; background-color: #fff; }
  }

  /* Modals */
  .modal.expenses-scope .modal-content{ border:none; border-radius:24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
  .modal.expenses-scope .modal-header { padding: 24px 24px 16px; }
  .modal.expenses-scope .modal-body { padding: 16px 24px 24px; }
  .modal.expenses-scope .modal-footer { padding: 16px 24px 24px; background: #f8fafc; border-radius: 0 0 24px 24px; }

  /* Chart Toolbar */
  #expensesPage .chart-toolbar{ display:flex; align-items:center; gap:6px; background: #f1f5f9; padding: 4px; border-radius: 999px; }
  #expensesPage .chart-toggle{
    border:none; background:transparent; color:#64748b; border-radius:999px; padding:.4rem .8rem;
    font-weight:700; font-size:.8rem; transition:all 0.2s ease;
  }
  #expensesPage .chart-toggle:hover{ color: #0f172a; }
  #expensesPage .chart-toggle.active{ background:#fff; color:#0f172a; box-shadow:0 4px 10px rgba(0,0,0,.05); }

  /* Toasts */
  #expensesToasts .toast{ border:none; border-radius:16px; box-shadow: 0 20px 40px rgba(15,23,42,.15); overflow:hidden; }
  #expensesToasts .toast .toast-body{ font-weight:600; font-size: 0.95rem; padding: 1rem; }
  
  /* Tablas elegantes */
  .table-hover tbody tr { transition: background-color 0.2s ease; }
  .table-hover tbody tr:hover { background-color: #f8fafc; }
  .table-light th { background-color: #f1f5f9; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; border-bottom: none; padding: 12px 16px; }
  .table td { padding: 16px; vertical-align: middle; border-bottom: 1px solid var(--border); font-weight: 500; color: #334155; }
</style>

<div id="expensesPage">
  <div class="page-bg animate-fade-in">
    <div class="container page-wrap">

      {{-- HERO --}}
      <div class="hero mt-2 mb-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white border"
                 style="width:56px;height:56px;border-color:var(--pblue); box-shadow: 0 4px 15px rgba(96,165,250,.15);">
              <i class="bi bi-receipt-cutoff" style="font-size:1.5rem;color:var(--pblue-700)"></i>
            </div>
            <div>
              <h1 class="h3 mb-1">Control de Gastos</h1>
              <div class="small subtle">Métricas precisas y movimientos en tiempo real.</div>
            </div>
          </div>

          <div class="d-none d-md-flex gap-2">
            <a class="btn btn-pastel-green px-4" href="{{ route('expenses.create') }}">
              <i class="bi bi-plus-lg me-2"></i> Nuevo gasto
            </a>
            <button class="btn btn-pastel-blue px-4" type="button" id="btnRefresh">
              <i class="bi bi-arrow-clockwise me-2"></i> Actualizar
            </button>
          </div>
        </div>

        {{-- Desktop pills --}}
        <div class="tabs-row">
          <div class="tabs-pills" role="tablist" aria-label="Secciones">
            <button class="tab-pill active" data-bs-toggle="tab" data-bs-target="#pane-dash" type="button" role="tab" aria-controls="pane-dash" aria-selected="true">
              <i class="bi bi-grid-1x2-fill"></i> Resumen
            </button>
            <button class="tab-pill" data-bs-toggle="tab" data-bs-target="#pane-filters" type="button" role="tab" aria-controls="pane-filters" aria-selected="false">
              <i class="bi bi-funnel-fill"></i> Filtros
            </button>
            <button class="tab-pill" data-bs-toggle="tab" data-bs-target="#pane-list" type="button" role="tab" aria-controls="pane-list" aria-selected="false">
              <i class="bi bi-list-columns-reverse"></i> Movimientos
            </button>
          </div>

          <div class="d-flex align-items-center gap-2 text-muted small fw-bold">
            <i class="bi bi-activity text-success"></i> Sincronización activa
          </div>
        </div>

        {{-- Mobile switcher --}}
        <div class="mobile-switch">
          <div class="d-flex gap-2">
            <select id="mobileTab" class="form-select flex-grow-1">
              <option value="#pane-dash" selected>📊 Resumen</option>
              <option value="#pane-filters">🔎 Filtros</option>
              <option value="#pane-list">🧾 Movimientos</option>
            </select>
            <button class="btn btn-pastel-blue" type="button" id="btnRefreshM">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
            <a class="btn btn-pastel-green" href="{{ route('expenses.create') }}">
              <i class="bi bi-plus-lg"></i>
            </a>
          </div>
        </div>
      </div>

      {{-- AVISO API --}}
      <div id="warn" class="mini-error mt-2 mb-3 d-none animate-fade-in"><i class="bi bi-wifi-off me-2"></i> No se pudo contactar a la API.</div>

      <div class="tab-content mt-2">

        {{-- =================== TAB DASHBOARD =================== --}}
        <div class="tab-pane fade show active" id="pane-dash" role="tabpanel" tabindex="0">

          {{-- KPIs --}}
          <div class="metrics-grid">
            <div class="card metric-card animate-fade-in" style="animation-delay: 0.1s;">
              <div class="metric-title">
                <span>PAGADO</span>
                <span class="metric-pill pill-out"><i class="bi bi-check-circle-fill"></i> OUT</span>
              </div>
              <div class="metric-value" id="kPaid"><span class="skeleton" style="height:32px;width:140px;display:inline-block"></span></div>
              <div class="metric-sub" id="kPaidPct"><span class="skeleton" style="height:16px;width:100px;display:inline-block"></span></div>
            </div>

            <div class="card metric-card animate-fade-in" style="animation-delay: 0.2s;">
              <div class="metric-title">
                <span>PENDIENTE</span>
                <span class="metric-pill pill-ret"><i class="bi bi-clock-fill"></i> PEND</span>
              </div>
              <div class="metric-value" id="kPending"><span class="skeleton" style="height:32px;width:140px;display:inline-block"></span></div>
              <div class="metric-sub" id="kPendingPct"><span class="skeleton" style="height:16px;width:100px;display:inline-block"></span></div>
            </div>

            <div class="card metric-card animate-fade-in" style="animation-delay: 0.3s;">
              <div class="metric-title">
                <span>CANCELADO</span>
                <span class="metric-pill pill-can"><i class="bi bi-x-circle-fill"></i> CAN</span>
              </div>
              <div class="metric-value" id="kCanceled"><span class="skeleton" style="height:32px;width:140px;display:inline-block"></span></div>
              <div class="metric-sub" id="kCanceledPct"><span class="skeleton" style="height:16px;width:100px;display:inline-block"></span></div>
            </div>

            <div class="card metric-card expected-ok animate-fade-in" style="animation-delay: 0.4s;" id="cardTotal">
              <div class="metric-title">
                <span>TOTAL FILTRADO</span>
                <span class="metric-pill pill-count"># <span id="kCount">0</span> regs</span>
              </div>
              <div class="metric-value" id="kTotal"><span class="skeleton" style="height:32px;width:140px;display:inline-block"></span></div>
              <div class="metric-sub fw-bold text-success" id="kTotalHint">—</div>
            </div>
          </div>

          {{-- Tendencia --}}
          <div class="card mt-4 animate-fade-in" style="animation-delay: 0.5s;">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
              <div>
                <span class="fs-5"><i class="bi bi-bar-chart-fill me-2 text-primary"></i><span id="chartTitle">Tendencia de gastos (Fecha de pago)</span></span>
                <div class="small subtle mt-1" id="chartRangeHint">Basado estrictamente en fecha de pago registrada</div>
              </div>

              <div class="chart-toolbar" aria-label="Filtro de gráfica">
                <button class="chart-toggle active" type="button" data-chart-group="day">Día</button>
                <button class="chart-toggle" type="button" data-chart-group="week">Sem</button>
                <button class="chart-toggle" type="button" data-chart-group="month">Mes</button>
              </div>
            </div>
            <div class="card-body">
              <div style="position:relative;height:340px">
                <canvas id="chart"></canvas>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pb-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
              <div class="fw-bold text-muted small" id="trend7">Cargando tendencia...</div>
            </div>
          </div>

          {{-- Distribución por detalle --}}
          <div class="card mt-4 animate-fade-in" style="animation-delay: 0.6s;">
            <div class="card-header d-flex align-items-center justify-content-between">
              <span class="fs-5"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Distribución de impacto</span>
              <small class="subtle">Top detalles y conceptos</small>
            </div>
            <div class="card-body p-4">
              <div class="row g-4 align-items-center">
                <div class="col-12 col-lg-6">
                  <div style="position:relative;height:330px">
                    <canvas id="pieChart"></canvas>
                  </div>
                  <div class="small text-center text-muted mt-3 fw-bold" id="pieHint">—</div>
                </div>
                <div class="col-12 col-lg-6">
                  <div class="bg-light p-4 rounded-4 border">
                    <div class="fw-bold mb-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.05em;">Top Detalles</div>
                    <div id="topDetails" class="d-grid gap-3">
                      <div class="skeleton" style="height:20px"></div>
                      <div class="skeleton" style="height:20px;width:85%"></div>
                      <div class="skeleton" style="height:20px;width:70%"></div>
                    </div>

                    <hr class="my-4 border-secondary opacity-25">

                    <div class="fw-bold mb-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.05em;">Top Conceptos</div>
                    <div id="topConcepts" class="d-grid gap-3">
                      <div class="skeleton" style="height:20px"></div>
                      <div class="skeleton" style="height:20px;width:80%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- MOVIMIENTOS RECIENTES --}}
          <div class="card mt-4 animate-fade-in" style="animation-delay: 0.7s;">
            <div class="card-header d-flex align-items-center justify-content-between">
              <span class="fs-5"><i class="bi bi-table me-2 text-primary"></i>Últimos Movimientos</span>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                  <thead class="table-light">
                    <tr>
                      <th class="ps-4">ID</th>
                      <th>Fecha de Pago</th>
                      <th>Tipo</th>
                      <th>Detalle</th>
                      <th class="text-end">Monto</th>
                      <th>Estatus</th>
                      <th class="pe-4 text-center">Recibo</th>
                    </tr>
                  </thead>
                  <tbody id="tbody">
                    <tr>
                      <td colspan="7" class="p-4">
                        <div class="skeleton mb-2" style="height:20px;"></div>
                        <div class="skeleton" style="height:20px;width:60%;"></div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="card-footer bg-transparent py-3 px-4 d-flex justify-content-between align-items-center">
              <button class="btn btn-outline-soft btn-sm px-3" id="prevPage" type="button"><i class="bi bi-chevron-left"></i> Anterior</button>
              <div id="pageInfo" class="small fw-bold text-muted">Página 1</div>
              <button class="btn btn-outline-soft btn-sm px-3" id="nextPage" type="button">Siguiente <i class="bi bi-chevron-right"></i></button>
            </div>
          </div>

        </div>

        {{-- =================== TAB FILTROS =================== --}}
        <div class="tab-pane fade" id="pane-filters" role="tabpanel" tabindex="0">
          <div class="card animate-fade-in">
            <div class="card-body p-4">
              <div class="row g-4">

                <div class="col-12 col-md-3">
                  <label class="form-label">Fecha Pago (Desde)</label>
                  <input id="from" type="date" class="form-control">
                </div>

                <div class="col-12 col-md-3">
                  <label class="form-label">Fecha Pago (Hasta)</label>
                  <input id="to" type="date" class="form-control">
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label">Búsqueda Global</label>
                  <div class="input-group" style="box-shadow: 0 2px 5px rgba(0,0,0,0.02); border-radius: 14px;">
                    <span class="input-group-text bg-white border-end-0 text-muted" style="border-radius:14px 0 0 14px; border-color:var(--border);">
                      <i class="bi bi-search"></i>
                    </span>
                    <input id="q" class="form-control border-start-0 ps-0" placeholder="Buscar por concepto, detalle..."
                           style="border-radius:0 14px 14px 0; background: #fff;">
                  </div>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Categoría</label>
                  <select id="cat" class="form-select">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $c)
                      <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Vehículo</label>
                  <select id="veh" class="form-select">
                    <option value="">Todos los vehículos</option>
                    @foreach($vehicles as $v)
                      <option value="{{ $v->id }}">{{ $v->plate_label ?? ($v->plate ?? $v->placas ?? ('#'.$v->id)) }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Estatus</label>
                  <select id="status" class="form-select">
                    <option value="">Todos los estatus</option>
                    <option value="paid">Pagado</option>
                    <option value="pending">Pendiente</option>
                    <option value="canceled">Cancelado</option>
                  </select>
                </div>

                <div class="col-12 d-flex justify-content-end gap-3 mt-5">
                  <button class="btn btn-outline-soft px-4" type="button" id="btnClear">
                    <i class="bi bi-eraser-fill me-2"></i> Limpiar
                  </button>
                  <button class="btn btn-pastel-blue px-5" type="button" id="btnApply">
                    <i class="bi bi-funnel-fill me-2"></i> Aplicar Filtros
                  </button>
                </div>

              </div>
            </div>
          </div>
        </div>

        {{-- =================== TAB LISTADO (cards) =================== --}}
        <div class="tab-pane fade" id="pane-list" role="tabpanel" tabindex="0">

          <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 bg-white p-3 rounded-4 border shadow-sm animate-fade-in">
            <div class="d-flex flex-wrap gap-3 align-items-center">
              <div class="badge bg-light text-dark border px-3 py-2 rounded-pill fs-6"><i class="bi bi-hash text-muted me-1"></i> <span id="kpiCount">0</span> regs</div>
              <div class="badge bg-light text-success border border-success-subtle px-3 py-2 rounded-pill fs-6"><i class="bi bi-currency-dollar me-1"></i> <span id="kpiSum" class="fw-bold">$0.00</span></div>
            </div>

            <div class="d-flex gap-3 align-items-center">
              <select id="perPage" class="form-select form-select-sm rounded-pill px-3 py-2 fw-bold" style="width:140px; background-color: #f8fafc;">
                <option value="10">10 / pág</option>
                <option value="20" selected>20 / pág</option>
                <option value="50">50 / pág</option>
              </select>

              <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm px-3 rounded-start-pill" id="btnPrev" type="button" title="Anterior">
                  <i class="bi bi-chevron-left"></i>
                </button>
                <button class="btn btn-outline-secondary btn-sm px-3 rounded-end-pill" id="btnNext" type="button" title="Siguiente">
                  <i class="bi bi-chevron-right"></i>
                </button>
              </div>
            </div>
          </div>

          <div id="grid" class="grid">
            <div class="empty animate-fade-in">Cargando movimientos...</div>
          </div>

          <div class="text-center fw-bold text-muted small mt-4 pb-4 animate-fade-in">
            Página <span id="pageNow" class="text-dark">1</span> de <span id="pageLast">1</span> • Total general: <span id="totalAll">0</span>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- ✅ TOAST CONTAINER --}}
<div class="toast-container position-fixed bottom-0 end-0 p-4" id="expensesToasts" style="z-index: 3000;"></div>

{{-- MODAL Detalle --}}
<div class="modal fade expenses-scope" id="evidenceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 bg-white">
        <div>
          <div class="fw-black fs-4" id="evTitle">Detalle del Gasto</div>
          <div class="text-muted small fw-bold mt-1" id="evSub">—</div>
        </div>
        <button type="button" class="btn-close bg-light rounded-circle p-2" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body pt-0">
        <div class="d-flex flex-wrap gap-2 mb-4" id="evBadges"></div>

        <div class="row g-3">
          <div class="col-12" id="evDetailsSection">
            <div class="fw-bold mb-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.05em;">Información Exacta</div>
            <div id="evFields" class="row g-3"></div>
          </div>

          <div class="col-12 d-none mt-4" id="evEvidenceSection">
            <div class="fw-bold mb-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.05em;">Evidencia Adjunta</div>
            <div id="evBody" class="text-center text-muted p-4 bg-light rounded-4 border dashed">—</div>
          </div>

          <div class="col-12 d-none mt-4" id="sigSection">
            <div class="fw-bold mb-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.05em;">Firmas Digitales</div>
            <div class="row g-3">
              <div class="col-12 col-md-6" id="sigMgrCol">
                <div class="border rounded-4 p-3 bg-light text-center">
                  <div class="small text-muted mb-3 fw-bold">Autorización (Admin)</div>
                  <div id="sigMgr" class="text-muted small">—</div>
                </div>
              </div>
              <div class="col-12 col-md-6" id="sigCtpCol">
                <div class="border rounded-4 p-3 bg-light text-center">
                  <div class="small text-muted mb-3 fw-bold">Recepción (Contraparte)</div>
                  <div id="sigCtp" class="text-muted small">—</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-12 d-none mt-4" id="rcSection">
            <div class="fw-bold mb-2 text-uppercase text-muted" style="font-size: 0.8rem;">Recibo Oficial</div>
            <div id="rcBody" class="text-muted small bg-light p-3 rounded-3 border">—</div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0 d-flex justify-content-between">
        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-pastel-blue fw-bold d-none px-4" id="evDownload" href="#" target="_blank" rel="noopener">
            <i class="bi bi-cloud-download-fill me-2"></i> Descargar
          </a>
          <a class="btn btn-pastel-green fw-bold d-none px-4" id="rcDownload" href="#" target="_blank" rel="noopener">
            <i class="bi bi-file-earmark-pdf-fill me-2"></i> Ver PDF
          </a>
        </div>
        <button class="btn btn-outline-secondary px-4 fw-bold rounded-pill" data-bs-dismiss="modal" type="button">Cerrar</button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL Editar Estatus --}}
<div class="modal fade expenses-scope" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <div class="fw-bold fs-5" id="stTitle">Modificar Estatus</div>
          <div class="text-muted small mt-1" id="stSub">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-4">
        <input type="hidden" id="stId" value="">
        <label class="form-label">Nuevo Estatus del Gasto</label>
        <select id="stStatus" class="form-select form-select-lg fs-6">
          <option value="paid">Pagado (Afecta cuentas)</option>
          <option value="pending">Pendiente (Por liquidar)</option>
          <option value="canceled">Cancelado (Sin efecto)</option>
        </select>
        <div class="mini-error mt-3 d-none" id="stErr"></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-pastel-blue rounded-pill px-4" type="button" id="stSave">
          <i class="bi bi-check2-circle me-1"></i> Guardar Cambios
        </button>
      </div>
    </div>
  </div>
</div>

{{-- MODAL Confirmar Eliminación --}}
<div class="modal fade expenses-scope" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header border-0 pb-0">
        <div class="fw-bold fs-5 text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i> Eliminar Registro</div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-4">
        <input type="hidden" id="delId" value="">
        <div class="text-muted fw-bold small mb-2">Se eliminará permanentemente:</div>
        <div class="border rounded-4 p-3 bg-light">
          <div class="fw-black text-dark" id="delTitle">—</div>
          <div class="text-muted small mt-1" id="delSub">—</div>
        </div>
        <div class="mini-error mt-3 d-none" id="delErr"></div>
        <div class="alert alert-danger mt-4 border-0 mb-0 small">
          <i class="bi bi-shield-lock-fill me-1"></i> Esta acción es destructiva y no se puede deshacer.
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-danger rounded-pill px-4 fw-bold" style="box-shadow: 0 4px 15px rgba(220,38,38,.3);" type="button" id="delConfirm">
          Eliminar definitivamente
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

  const UPDATE_URL_TPL = "{{ \Illuminate\Support\Facades\Route::has('expenses.update') ? route('expenses.update', ['expense' => '__ID__'], false) : '' }}";
  const DESTROY_URL_TPL = "{{ \Illuminate\Support\Facades\Route::has('expenses.destroy') ? route('expenses.destroy', ['expense' => '__ID__'], false) : '' }}";
  const FALLBACK_BASE = "{{ url('/expenses') }}";

  const $ = (id)=>document.getElementById(id);

  let state = {
    page: 1, last_page: 1, per_page: 20, total: 0, rows: [], timer: null, lastChartRows: null, chart_group: 'day',
  };

  function esc(s){ return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

  function statusLabel(st){
    const s = String(st || '').toLowerCase();
    if(s === 'paid') return 'Pagado';
    if(s === 'pending') return 'Pendiente';
    if(s === 'canceled' || s === 'cancelled') return 'Cancelado';
    return (st ? String(st) : '—');
  }

  function money(n, currency='MXN'){
    const x = Number(n || 0);
    try{ return x.toLocaleString('es-MX', {style:'currency', currency: currency || 'MXN'}); }
    catch(_){ return '$' + x.toFixed(2); }
  }
  
  function pct(part, total){
    const t = Number(total || 0);
    if(t <= 0) return 0;
    return Math.round((Number(part||0) / t) * 1000) / 10;
  }

  function csrfToken(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''; }

  /* =========================
     ✅ TOAST SYSTEM
     ========================= */
  function toast(type, message, opts = {}){
    const container = document.getElementById('expensesToasts');
    if(!container) return;

    const id = 't_' + Math.random().toString(16).slice(2);
    const icon = (type === 'success') ? 'bi-check-circle-fill text-success' : (type === 'danger')  ? 'bi-x-circle-fill text-danger' : (type === 'warning') ? 'bi-exclamation-triangle-fill text-warning' : 'bi-info-circle-fill text-primary';
    const bg = 'bg-white text-dark';
    const delay = Number(opts.delay ?? 3000);

    container.insertAdjacentHTML('beforeend', `
      <div id="${id}" class="toast ${bg} border-0 animate-fade-in" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${delay}">
        <div class="d-flex align-items-center p-2">
          <div class="toast-body d-flex align-items-center fs-6">
            <i class="bi ${icon} me-3 fs-4"></i>
            <span>${esc(message || '')}</span>
          </div>
          <button type="button" class="btn-close me-3 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    `);

    const el = document.getElementById(id);
    const t = new bootstrap.Toast(el);
    el.addEventListener('hidden.bs.toast', ()=> el.remove());
    t.show();
  }

  function setMiniError(id, show, msg){
    const el = document.getElementById(id);
    if(!el) return;
    if(show){ el.classList.remove('d-none'); el.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${esc(msg || 'Error')}`; }
    else{ el.classList.add('d-none'); el.textContent = ''; }
  }

  function endpointFromTemplate(tpl, id){ return (tpl && tpl.includes('__ID__')) ? tpl.replace('__ID__', String(id)) : `${FALLBACK_BASE}/${id}`; }

  async function apiUpdateStatus(id, status){
    const url = endpointFromTemplate(UPDATE_URL_TPL, id);
    const res = await fetch(url, { method: 'PATCH', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ status }) });
    if(!res.ok) throw new Error(`No se pudo actualizar (HTTP ${res.status}).`);
    return res.json().catch(()=> ({}));
  }

  async function apiDeleteExpense(id){
    const url = endpointFromTemplate(DESTROY_URL_TPL, id);
    const res = await fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'X-Requested-With': 'XMLHttpRequest' } });
    if(!res.ok) throw new Error(`No se pudo eliminar (HTTP ${res.status}).`);
    return res.json().catch(()=> ({}));
  }

  // ✅ FORZAR ESTRICTAMENTE FECHAS DE PAGO (EXPENSE_DATE)
  function normalizeDate(d){
    if(!d) return null;
    const s = String(d).slice(0,10);
    if(!/^\d{4}-\d{2}-\d{2}$/.test(s) || s.startsWith('0000')) return null;
    return s;
  }
  function fmtDate(d){
    const s = normalizeDate(d);
    if(!s) return 'Fecha no registrada';
    const dt = new Date(s + 'T00:00:00');
    return dt.toLocaleDateString('es-MX', {day:'2-digit', month:'short', year:'numeric'});
  }

  function warn(show, msg){
    const w = $('warn');
    if(!w) return;
    if(show){ w.classList.remove('d-none'); w.innerHTML = `<i class="bi bi-wifi-off me-2"></i> ${esc(msg)}`; }
    else{ w.classList.add('d-none'); }
  }

  function params(extra = {}){
    const p = new URLSearchParams();
    const map = {q:'q', cat:'category_id', veh:'vehicle_id', status:'status', from:'from', to:'to'};
    for(const [id, key] of Object.entries(map)){
      const val = $(id)?.value?.trim();
      if(val) p.set(key, val);
    }
    p.set('per_page', String(state.per_page));
    p.set('page', String(state.page));
    p.set('_t', String(Date.now()));
    Object.entries(extra).forEach(([k,v])=>{ if(v != null && v !== '') p.set(k, String(v)); });
    return p;
  }

  function num(v){
    if (v == null || v === '') return 0;
    if (typeof v === 'number') return v;
    const n = parseFloat(String(v).replace(/[^0-9.\-]+/g, ''));
    return Number.isFinite(n) ? n : 0;
  }
  function pick(obj, keys, fallback=null){
    for(const k of keys){ if(obj && obj[k] != null && obj[k] !== '') return obj[k]; }
    return fallback;
  }

  // Charts
  Chart.defaults.font.family = "'Inter', 'Segoe UI', system-ui, sans-serif";
  Chart.defaults.color = '#64748b';

  const chartEl = document.getElementById('chart');
  const chart = chartEl ? new Chart(chartEl, {
    type:'bar',
    data:{ labels:[], datasets:[
      {label:'Pagado',    data:[], backgroundColor:'rgba(96, 165, 250, 0.9)', borderWidth:0, borderRadius:6},
      {label:'Pendiente', data:[], backgroundColor:'rgba(14, 165, 233, 0.6)', borderWidth:0, borderRadius:6},
      {label:'Cancelado', data:[], backgroundColor:'rgba(203, 213, 225, 0.8)', borderWidth:0, borderRadius:6},
    ]},
    options:{
      responsive:true, maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{ legend:{ labels:{ usePointStyle:true, padding: 20, font: { weight: 'bold' } } } },
      scales:{
        x:{ stacked:true, grid:{ display:false } },
        y:{ stacked:true, border:{dash: [4, 4]}, grid:{ color:'rgba(148,163,184,.15)' } }
      }
    }
  }) : null;

  const pieEl = document.getElementById('pieChart');
  const pie = pieEl ? new Chart(pieEl, {
    type: 'doughnut',
    data: { labels: [], datasets: [{
      data: [],
      backgroundColor: ['#60a5fa', '#34d399', '#fbbf24', '#f472b6', '#0ea5e9', '#94a3b8'],
      borderWidth: 2, borderColor: '#ffffff', hoverOffset: 4
    }]},
    options: {
      responsive:true, maintainAspectRatio:false, cutout: '70%',
      plugins: {
        legend: { position: 'bottom', labels:{ usePointStyle:true, padding: 20 } },
        tooltip: {
          backgroundColor: 'rgba(15, 23, 42, 0.9)',
          padding: 12, cornerRadius: 8,
          callbacks:{
            label: (ctx)=>{
              const val = Number(ctx.parsed||0);
              const total = ctx.dataset.data.reduce((a,b)=>a+Number(b||0),0) || 1;
              return ` ${ctx.label}: ${money(val)} (${Math.round((val/total)*100)}%)`;
            }
          }
        }
      }
    }
  }) : null;

  function setPercentLine(elId, part, total){
    const el = $(elId);
    if(el) el.textContent = `${pct(part, total)}% del total`;
  }

  function setTrend7FromChartRows(rows, currency='MXN'){
    const el = $('trend7');
    if(!el) return;
    if(!Array.isArray(rows) || rows.length < 1){ el.textContent = 'Sin datos visibles en el periodo'; return; }

    const values = rows.map(r => Number(r.paid||0) + Number(r.pending||0) + Number(r.canceled||0));
    const last = values[values.length - 1] || 0;
    const prev = values.length > 1 ? (values[values.length - 2] || 0) : 0;

    let arrow = '<i class="bi bi-arrow-right text-muted"></i>';
    if(prev > 0){
      const diffPct = ((last - prev) / prev) * 100;
      arrow = diffPct > 0 ? '<i class="bi bi-arrow-up-right text-danger"></i>' : (diffPct < 0 ? '<i class="bi bi-arrow-down-right text-success"></i>' : arrow);
    } else if (last > 0) arrow = '<i class="bi bi-arrow-up-right text-danger"></i>';

    el.innerHTML = `Último periodo: <span class="text-dark">${money(last, currency)}</span> ${arrow}`;
  }

  async function loadMetrics(){
    const url = API_METRICS + '?' + params({page:null}).toString().replace(/(^|&)page=\d+(&|$)/,'$1').replace(/^&|&$/g,'');
    const res = await fetch(url, {headers:{'Accept':'application/json'}});
    if(!res.ok){ warn(true, 'Error contactando API métricas.'); return null; }
    const data = await res.json().catch(()=>null);
    if(!data) return null;

    warn(false);
    const currency = data.currency ?? 'MXN';
    const paid = Number(data.paid_sum ?? 0);
    const pending = Number(data.pending_sum ?? 0);
    const canceled = Number(data.canceled_sum ?? 0);
    const total = (data.paid_sum != null) ? (paid + pending + canceled) : Number(data.sum ?? 0);

    // Animación de entrada de texto
    ['kPaid','kPending','kCanceled','kTotal'].forEach(id => { $(id).style.animation = 'none'; $(id).offsetHeight; $(id).style.animation = 'fadeInUp 0.4s ease-out forwards'; });

    $('kPaid').textContent = money(paid, currency);
    $('kPending').textContent = money(pending, currency);
    $('kCanceled').textContent = money(canceled, currency);
    $('kTotal').textContent = money(total, currency);
    $('kCount').textContent = String(data.count ?? 0);
    $('kTotalHint').innerHTML = total > 0 ? '<i class="bi bi-check-circle-fill me-1"></i> Totales calculados' : 'Sin registros para este filtro';

    setPercentLine('kPaidPct', paid, total);
    setPercentLine('kPendingPct', pending, total);
    setPercentLine('kCanceledPct', canceled, total);
    $('kpiCount').textContent = String(data.count ?? 0);
    $('kpiSum').textContent = money(total, currency);

    if(state.lastChartRows) setTrend7FromChartRows(state.lastChartRows, currency);
    return data;
  }

  function chartBucketLabel(date, group){
    const raw = String(date || '');
    if(group === 'month' && /^\d{4}-\d{2}$/.test(raw)){
      const [y,m] = raw.split('-').map(Number);
      return new Date(y, m - 1, 1).toLocaleDateString('es-MX', {month:'short', year:'numeric'});
    }
    if(/^\d{4}-\d{2}-\d{2}$/.test(raw)){
      const dt = new Date(raw + 'T00:00:00');
      return dt.toLocaleDateString('es-MX', {day:'2-digit', month:'short'});
    }
    return raw || '—';
  }

  function buildLocalChartRows(src){
    const group = state.chart_group || 'day';
    const map = new Map();

    for(const e of src){
      // 🛑 OJO: AQUÍ SOLO USAMOS expense_date. IGNORAMOS performed_at (subida).
      const rawDate = String(e.expense_date || '').slice(0,10); 
      if(!/^\d{4}-\d{2}-\d{2}$/.test(rawDate)) continue;

      let key = rawDate;
      if(group === 'month') key = rawDate.slice(0,7);

      const st = String(e.status || 'paid').toLowerCase();
      const amt = num(e.amount);

      if(!map.has(key)) map.set(key, {date:key, label: chartBucketLabel(key, group), group, paid:0, pending:0, canceled:0});
      const row = map.get(key);
      if(st === 'pending') row.pending += amt;
      else if(st === 'canceled' || st === 'cancelled') row.canceled += amt;
      else row.paid += amt;
    }
    return Array.from(map.values()).sort((a,b)=> String(a.date).localeCompare(String(b.date)));
  }

  async function loadChart(){
    if(!chart) return;
    let rows = [];
    if(API_CHART){
      const url = API_CHART + '?' + params({page:null, per_page:null, group: state.chart_group}).toString();
      const res = await fetch(url, {headers:{'Accept':'application/json'}});
      if(res.ok){
        const json = await res.json().catch(()=>null);
        rows = Array.isArray(json) ? json : (Array.isArray(json?.data) ? json.data : []);
      }
    }
    if(!rows.length) rows = buildLocalChartRows(state.rows);

    const labels = rows.map(r => pick(r, ['label','x']) || chartBucketLabel(pick(r, ['date','day'], ''), state.chart_group));
    const paid   = rows.map(r => num(pick(r, ['paid','paid_sum'], 0)));
    const pending= rows.map(r => num(pick(r, ['pending','pending_sum'], 0)));
    const canceled=rows.map(r => num(pick(r, ['canceled','canceled_sum'], 0)));

    state.lastChartRows = rows.map((r, i)=>({ date: labels[i], paid: paid[i], pending: pending[i], canceled: canceled[i] }));
    chart.data.labels = labels;
    chart.data.datasets[0].data = paid;
    chart.data.datasets[1].data = pending;
    chart.data.datasets[2].data = canceled;
    chart.update();
  }

  function openEvidence(e){
    const modal = new bootstrap.Modal($('evidenceModal'));
    $('evTitle').textContent = e.concept || 'Gasto no detallado';
    $('evSub').textContent = [e.expense_type, e.vehicle?.plate].filter(Boolean).join(' • ') || 'Registro estándar';

    const badges = [];
    // 🛑 STRICTO: Mostrar SOLO fecha de gasto/pago.
    const when = fmtDate(e.expense_date); 
    badges.push(`<span class="tag soft bg-light border"><i class="bi bi-calendar-event text-primary"></i> ${esc(when)}</span>`);
    if (e.status) badges.push(`<span class="tag border"><i class="bi bi-circle-fill small me-1 ${e.status==='paid'?'text-success':(e.status==='pending'?'text-warning':'text-secondary')}"></i> ${esc(statusLabel(e.status))}</span>`);
    $('evBadges').innerHTML = badges.join('');

    const fields = [];
    const pushField = (label, value) => {
      if(value == null || String(value).trim() === '' || String(value) === '—') return;
      fields.push(`
        <div class="col-12 col-md-6 col-lg-4">
          <div class="border rounded-4 p-3 h-100 bg-light">
            <div class="small text-muted mb-1 fw-bold">${esc(label)}</div>
            <div class="fw-black text-dark" style="word-break:break-word; font-size:1.05rem;">${esc(String(value))}</div>
          </div>
        </div>
      `);
    };

    pushField('ID Registro', e.id);
    pushField('Fecha de Pago', fmtDate(e.expense_date)); // 🛑 Clarificado que es fecha de pago
    pushField('Monto Total', money(e.amount, e.currency || 'MXN'));
    pushField('Concepto', e.concept);
    pushField('Descripción', (e.description || '').trim());
    pushField('Categoría', e.category?.name);
    pushField('Vehículo', e.vehicle?.plate);
    pushField('Método', e.payment_method);

    $('evFields').innerHTML = fields.join('');
    
    const evSection = $('evEvidenceSection'); const body = $('evBody'); const evDown = $('evDownload');
    if(!e.evidence_url){ evSection.classList.add('d-none'); evDown.classList.add('d-none'); }
    else {
      evSection.classList.remove('d-none'); evDown.classList.remove('d-none'); evDown.href = e.evidence_url;
      const mime = (e.evidence_mime || '').toLowerCase();
      body.innerHTML = mime.includes('pdf') ? `<iframe src="${esc(e.evidence_url)}" style="width:100%; height:400px; border:none; border-radius:12px"></iframe>` 
                     : (mime.includes('image') ? `<img src="${esc(e.evidence_url)}" class="img-fluid rounded-4 shadow-sm">` : 'No previsualizable.');
    }

    $('rcSection').classList.toggle('d-none', !e.pdf_url);
    $('rcDownload').classList.toggle('d-none', !e.pdf_url);
    if(e.pdf_url){ $('rcDownload').href = e.pdf_url; $('rcBody').innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i> Documento PDF adjunto y validado.`; }

    modal.show();
  }

  let statusModalInst = null, deleteModalInst = null;
  function openEditStatus(e){
    if(!statusModalInst) statusModalInst = new bootstrap.Modal($('statusModal'));
    $('stId').value = e.id; $('stSub').textContent = `${esc(e.concept)} • ${esc(money(e.amount))}`;
    $('stStatus').value = (String(e.status).toLowerCase() === 'cancelled') ? 'canceled' : String(e.status||'paid').toLowerCase();
    setMiniError('stErr', false); statusModalInst.show();
  }
  function openDelete(e){
    if(!deleteModalInst) deleteModalInst = new bootstrap.Modal($('deleteModal'));
    $('delId').value = e.id; $('delTitle').textContent = e.concept; $('delSub').textContent = `${fmtDate(e.expense_date)} • ${money(e.amount)}`;
    setMiniError('delErr', false); deleteModalInst.show();
  }

  $('stSave')?.addEventListener('click', async ()=>{
    try{ await apiUpdateStatus($('stId').value, $('stStatus').value); statusModalInst.hide(); toast('success', `Estatus actualizado.`); refreshAll(); }
    catch(err){ setMiniError('stErr', true, err.message); }
  });
  $('delConfirm')?.addEventListener('click', async ()=>{
    try{ await apiDeleteExpense($('delId').value); deleteModalInst.hide(); toast('success', `Registro eliminado.`); refreshAll(); }
    catch(err){ setMiniError('delErr', true, err.message); }
  });

  function renderCards(){
    const rows = state.rows; const grid = $('grid');
    if(!rows.length){ grid.innerHTML = `<div class="empty animate-fade-in"><i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>No se encontraron movimientos.</div>`; return; }

    grid.innerHTML = rows.map((e, index) => {
      // 🛑 STRICTO: Usar fecha de gasto
      const date = fmtDate(e.expense_date);
      const title = e.concept || 'Gasto';
      const amount = money(e.amount, e.currency);
      const detail = (e.description || '').trim();
      
      const chips = [
        detail ? `<span class="tag soft"><i class="bi bi-card-text text-muted"></i> Detalle</span>` : '',
        e.status ? `<span class="tag border-0 bg-light"><i class="bi bi-circle-fill small ${e.status==='paid'?'text-success':'text-secondary'}"></i> ${esc(statusLabel(e.status))}</span>` : ''
      ].filter(Boolean).join('');

      // 👇 Aquí aplicamos la animación staggered (en cascada)
      return `
        <div class="xcard animate-fade-in" style="animation-delay: ${index * 0.05}s;">
          <div class="thumb">
            <i class="bi bi-wallet2 fs-2"></i>
            <div class="small">${esc(date.split(' ')[0])}</div>
          </div>
          <div class="flex-grow-1" style="min-width:0">
            <div class="d-flex justify-content-between gap-2 align-items-start">
              <div class="title" title="${esc(title)}">${esc(title)}</div>
              <div class="amount px-2 py-1 bg-light rounded-3 border">${esc(amount)}</div>
            </div>
            <div class="meta2">${chips}</div>
            <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap border-top pt-3">
              <button class="btn btn-light border btn-sm fw-bold px-3" data-detail="${e.id}"><i class="bi bi-eye"></i></button>
              <button class="btn btn-light border btn-sm fw-bold px-3" data-edit="${e.id}"><i class="bi bi-pencil-square"></i></button>
              <button class="btn btn-light border text-danger btn-sm fw-bold px-3" data-delete="${e.id}"><i class="bi bi-trash3"></i></button>
            </div>
          </div>
        </div>`;
    }).join('');

    document.querySelectorAll('[data-detail]').forEach(b=>b.addEventListener('click', ()=>openEvidence(state.rows.find(x=>x.id==b.dataset.detail))));
    document.querySelectorAll('[data-edit]').forEach(b=>b.addEventListener('click', ()=>openEditStatus(state.rows.find(x=>x.id==b.dataset.edit))));
    document.querySelectorAll('[data-delete]').forEach(b=>b.addEventListener('click', ()=>openDelete(state.rows.find(x=>x.id==b.dataset.delete))));
  }

  function renderTable(res){
    const tb = $('tbody'); if(!tb) return;
    if(!state.rows.length){ tb.innerHTML = `<tr><td colspan="7" class="text-center p-5 text-muted fw-bold">Sin movimientos.</td></tr>`; }
    else {
      tb.innerHTML = state.rows.map((e, index) => {
        const date = fmtDate(e.expense_date); // 🛑 STRICTO
        const pdf = e.pdf_url ? `<a target="_blank" class="btn btn-sm btn-light border rounded-circle" href="${esc(e.pdf_url)}"><i class="bi bi-file-earmark-pdf text-danger"></i></a>` : '-';
        return `
          <tr class="animate-fade-in" style="animation-delay: ${index * 0.03}s;">
            <td class="ps-4 fw-bold text-muted">#${esc(e.id)}</td>
            <td class="fw-bold"><i class="bi bi-calendar2-minus text-primary me-2"></i>${esc(date)}</td>
            <td><span class="badge bg-light text-dark border">${esc(e.expense_type||'-')}</span></td>
            <td class="text-truncate" style="max-width:250px;">${esc(e.concept||'-')}</td>
            <td class="text-end fw-black text-dark fs-6">${esc(money(e.amount))}</td>
            <td><span class="badge ${e.status==='paid'?'bg-success-subtle text-success border border-success-subtle':'bg-light text-dark border'}">${esc(statusLabel(e.status))}</span></td>
            <td class="pe-4 text-center">${pdf}</td>
          </tr>`;
      }).join('');
    }
    const meta = res?.meta || {};
    $('pageInfo').textContent = `Pág. ${meta.page||state.page} de ${meta.last_page||state.last_page} — ${meta.total||state.total} regs`;
    $('prevPage')?.toggleAttribute('disabled', state.page<=1); $('nextPage')?.toggleAttribute('disabled', state.page>=state.last_page);
  }

  function buildInsightsFromRows(rows){
    if(!rows.length){ 
      if(pie) { pie.data.labels=[]; pie.data.datasets[0].data=[]; pie.update(); }
      $('topDetails').innerHTML = '<div class="text-muted small">Sin datos</div>'; 
      return; 
    }
    const dMap = new Map(); const cMap = new Map();
    rows.forEach(e=>{ 
      const a=Number(e.amount||0); 
      const d=(e.description||'').trim()||(e.concept||'').trim()||'Sin detalle'; 
      const c=(e.concept||'').trim()||'Sin concepto';
      dMap.set(d,(dMap.get(d)||0)+a); cMap.set(c,(cMap.get(c)||0)+a);
    });
    
    const renderTop = (map, el) => {
      if(!$(el)) return;
      $(el).innerHTML = [...map.entries()].sort((a,b)=>b[1]-a[1]).slice(0,5).map(([n,s], i) => `
        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 hover-bg animate-fade-in" style="animation-delay: ${i*0.1}s">
          <div class="text-truncate fw-bold text-dark" style="max-width: 60%;"><i class="bi bi-dot text-primary"></i> ${esc(n)}</div>
          <div class="fw-black text-primary">${esc(money(s))}</div>
        </div>
      `).join('');
    };
    renderTop(dMap, 'topDetails'); renderTop(cMap, 'topConcepts');

    const top6 = [...dMap.entries()].sort((a,b)=>b[1]-a[1]);
    const labels = top6.slice(0,5).map(x=>x[0]); const values = top6.slice(0,5).map(x=>x[1]);
    const others = top6.slice(5).reduce((a,b)=>a+b[1],0);
    if(others>0){ labels.push('Otros'); values.push(others); }
    
    if(pie){ pie.data.labels=labels; pie.data.datasets[0].data=values; pie.update(); }
    $('pieHint').innerHTML = `Analizando <span class="text-dark fw-bold">${money(values.reduce((a,b)=>a+b,0))}</span> en total.`;
  }

  async function loadList(){
    $('grid').innerHTML = `<div class="empty"><div class="spinner-border text-primary" role="status"></div><div class="mt-2 fw-bold text-muted">Sincronizando...</div></div>`;
    const res = await fetch(API_LIST + '?' + params().toString(), {headers:{'Accept':'application/json'}});
    const data = await res.json().catch(()=>null);
    if(!data) return;
    
    state.rows = data.data || []; state.page = data.meta?.page || 1; state.last_page = data.meta?.last_page || 1; state.total = data.meta?.total || 0;
    $('pageNow').textContent = state.page; $('pageLast').textContent = state.last_page; $('totalAll').textContent = state.total;
    
    renderCards(); renderTable(data); buildInsightsFromRows(state.rows);
  }

  async function refreshAll(){
    try{ await Promise.all([loadList(), loadChart(), loadMetrics()]); }
    catch(e){ toast('danger', 'Error de sincronización.'); }
  }

  // Eventos UI
  const syncTabs = (t) => document.querySelectorAll('.tab-pill').forEach(b => b.classList.toggle('active', b.dataset.bsTarget === t));
  
  $('btnRefresh')?.addEventListener('click', ()=>{ state.page=1; refreshAll(); });
  $('btnApply')?.addEventListener('click', ()=>{ state.page=1; refreshAll(); new bootstrap.Tab($('[data-bs-target="#pane-dash"]')).show(); syncTabs('#pane-dash'); toast('success', 'Filtros aplicados.'); });
  $('btnClear')?.addEventListener('click', ()=>{ ['from','to','q','cat','veh','status'].forEach(id=>$(id).value=''); state.page=1; refreshAll(); toast('info', 'Filtros restaurados.'); });
  
  document.querySelectorAll('[data-chart-group]').forEach(btn=>btn.addEventListener('click', ()=>{
    document.querySelectorAll('[data-chart-group]').forEach(b=>b.classList.toggle('active', b===btn));
    state.chart_group = btn.dataset.chartGroup; loadChart();
  }));

  ['perPage', 'mobileTab'].forEach(id => $(id)?.addEventListener('change', (e)=>{ 
    if(id==='perPage'){ state.per_page=Number(e.target.value); state.page=1; refreshAll(); }
    if(id==='mobileTab'){ const t = e.target.value; new bootstrap.Tab($(`[data-bs-target="${t}"]`)).show(); syncTabs(t); }
  }));

  ['btnPrev', 'prevPage'].forEach(id=>$(id)?.addEventListener('click', ()=>{ if(state.page>1){ state.page--; refreshAll(); } }));
  ['btnNext', 'nextPage'].forEach(id=>$(id)?.addEventListener('click', ()=>{ if(state.page<state.last_page){ state.page++; refreshAll(); } }));
  
  document.querySelectorAll('.tab-pill').forEach(btn=>btn.addEventListener('click', ()=>syncTabs(btn.dataset.bsTarget)));
  
  if(state.timer) clearInterval(state.timer);
  state.timer = setInterval(()=>refreshAll(), 30000); // 30s para no saturar

  refreshAll();
})();
</script>
@endsection