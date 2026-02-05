@extends('layouts.app')
@section('title','Nómina')
@section('titulo','Nómina')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
  #acc{
    --bg:#f7f9fc; --card:#fff; --ink:#0f172a; --muted:#64748b; --line:#e7edf5;
    --shadow:0 12px 30px rgba(15,23,42,.06); --radius:16px; --h:44px;
    --p1:#dbeafe; --p2:#dcfce7; --p3:#fae8ff; --p4:#ffedd5;
    --p1i:#1e3a8a; --p2i:#14532d; --p3i:#581c87; --p4i:#7c2d12;
  }
  #acc .page{ background:var(--bg); border-radius:18px; padding:18px; }
  #acc .card-pro{ background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
  #acc .head{ padding:14px; border-bottom:1px solid var(--line); display:flex; gap:12px; flex-wrap:wrap; justify-content:space-between; align-items:center; }
  #acc .body{ padding:14px; }
  #acc .title{ font-size:18px; font-weight:650; margin:0; color:var(--ink); }
  #acc .subtitle{ margin:0; color:var(--muted); font-size:13px; }
  #acc .btn-pastel{
    height:var(--h); border:0!important; border-radius:12px; padding:0 14px;
    font-weight:650; display:inline-flex; align-items:center; gap:10px;
    box-shadow:0 8px 18px rgba(15,23,42,.06);
    transition:transform .15s ease, background .15s ease, box-shadow .15s ease;
  }
  #acc .btn-pastel:hover{ background:#fff!important; transform:translateY(-1px); box-shadow:0 12px 26px rgba(15,23,42,.10); }
  #acc .btn-p1{ background:var(--p1); color:var(--p1i); }
  #acc .btn-p2{ background:var(--p2); color:var(--p2i); }
  #acc .btn-ghost{ background:#f1f5f9; color:var(--ink); }
  #acc .form-control, #acc .form-select{
    height:var(--h); border-radius:12px; border:1px solid var(--line); background:#fff; color:var(--ink);
  }
  #acc label{ color:var(--muted); font-size:12px; margin-bottom:6px; }
  #acc .table-wrap{ overflow:auto; }
  #acc .table thead th{ font-size:12px; color:var(--muted); font-weight:700; border-bottom:1px solid var(--line); white-space:nowrap; }
  #acc .table tbody td{ border-bottom:1px solid var(--line); vertical-align:middle; white-space:nowrap; }
  #acc .badge-soft{ padding:7px 10px; border-radius:999px; border:1px solid var(--line); font-size:12px; color:var(--muted); background:#fff; }
  #acc .tiny{ font-size:12px; }
  #acc .muted{ color:var(--muted); }
</style>

<div id="acc">
  <div class="page">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <p class="title">Nómina</p>
        <p class="subtitle">Periodos quincenales o mensuales, con entradas por usuario.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <button class="btn-pastel btn-p1" data-bs-toggle="modal" data-bs-target="#periodModal" onclick="openPeriodCreate()">
          <i class="bi bi-plus-lg"></i> Nuevo periodo
        </button>
        <button class="btn-pastel btn-ghost" onclick="refreshPeriods()">
          <i class="bi bi-arrow-clockwise"></i> Actualizar
        </button>
      </div>
    </div>

    <div class="card-pro mb-3">
      <div class="head">
        <div class="d-flex gap-2 flex-wrap">
          <div style="min-width:200px;">
            <label>Frecuencia</label>
            <select id="frequencyFilter" class="form-select" onchange="renderPeriods()">
              <option value="">Todas</option>
              <option value="quincenal">Quincenal</option>
              <option value="mensual">Mensual</option>
            </select>
          </div>
          <div style="min-width:200px;">
            <label>Estatus</label>
            <select id="statusFilter" class="form-select" onchange="refreshPeriods()">
              <option value="">Todos</option>
              <option value="abierto">Abierto</option>
              <option value="cerrado">Cerrado</option>
              <option value="pagado">Pagado</option>
            </select>
          </div>
        </div>

        <span class="badge-soft"><span class="tiny muted">Periodos:</span> <span id="countPeriods" class="fw-semibold">0</span></span>
      </div>

      <div class="body">
        <div class="table-wrap">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Periodo</th>
                <th>Frecuencia</th>
                <th>Estatus</th>
                <th>Usuarios</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody id="periodRows">
              <tr><td colspan="5" class="tiny muted">Cargando...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Modal crear periodo --}}
    <div class="modal fade" id="periodModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; border:1px solid var(--line); overflow:hidden;">
          <div class="modal-header" style="background:#fff; border-bottom:1px solid var(--line);">
            <div>
              <div class="fw-semibold" style="color:var(--ink);" id="periodModalTitle">Nuevo periodo</div>
              <div class="tiny muted">Quincenal o mensual.</div>
            </div>
            <button type="button" class="btn btn-link text-decoration-none" data-bs-dismiss="modal" style="color:var(--muted);">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>

          <div class="modal-body" style="background:var(--bg);">
            <form onsubmit="createPeriod(event)">
              @csrf
              <div class="mb-3">
                <label>Frecuencia</label>
                <select id="frequency" class="form-select" required>
                  <option value="quincenal">Quincenal</option>
                  <option value="mensual">Mensual</option>
                </select>
              </div>
              <div class="mb-3">
                <label>Inicio</label>
                <input id="start_date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Fin</label>
                <input id="end_date" type="date" class="form-control" required>
              </div>
              <div class="mb-3">
                <label>Título (opcional)</label>
                <input id="title" class="form-control" placeholder="Ej. Quincena 1 Feb 2026">
              </div>

              <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn-pastel btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn-pastel btn-p2">
                  <i class="bi bi-check2"></i> Guardar
                </button>
              </div>
              <div class="tiny muted mt-2" id="periodMsg"></div>
            </form>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
  const periodsUrl = "{{ url('/payroll/periods') }}";

  let PERIODS = [];

  function fmtRange(a,b){
    const A = new Date(a+'T00:00:00');
    const B = new Date(b+'T00:00:00');
    const f = (d)=> d.toLocaleDateString('es-MX',{year:'numeric',month:'short',day:'2-digit'});
    return `${f(A)} · ${f(B)}`;
  }

  async function refreshPeriods(){
    const status = document.getElementById('statusFilter').value;
    const params = new URLSearchParams();
    if(status) params.set('status', status);

    const tbody = document.getElementById('periodRows');
    tbody.innerHTML = `<tr><td colspan="5" class="tiny muted">Cargando...</td></tr>`;

    const res = await fetch(`${periodsUrl}?${params.toString()}`, { headers:{'Accept':'application/json'}});
    const data = await res.json().catch(()=>({}));
    PERIODS = Array.isArray(data) ? data : (data.data || []);
    renderPeriods();
  }

  function renderPeriods(){
    const freq = document.getElementById('frequencyFilter').value;
    const rows = PERIODS.filter(p => !freq || p.frequency === freq);

    document.getElementById('countPeriods').textContent = rows.length;

    const tbody = document.getElementById('periodRows');
    if(!rows.length){
      tbody.innerHTML = `<tr><td colspan="5" class="tiny muted">No hay periodos.</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(p => `
      <tr>
        <td>
          <div class="fw-semibold">${(p.title ? escapeHtml(p.title) : fmtRange(p.start_date, p.end_date))}</div>
          <div class="tiny muted">${fmtRange(p.start_date, p.end_date)}</div>
        </td>
        <td><span class="badge-soft">${escapeHtml(p.frequency)}</span></td>
        <td><span class="badge-soft">${escapeHtml(p.status)}</span></td>
        <td class="tiny muted">${p.entries_count ?? '—'}</td>
        <td class="text-end">
          <a class="btn-pastel btn-p1 text-decoration-none" style="height:38px; padding:0 12px;"
             href="{{ url('/payroll/periods') }}/${p.id}">
            <i class="bi bi-eye"></i> Ver
          </a>
        </td>
      </tr>
    `).join('');
  }

  function openPeriodCreate(){
    document.getElementById('periodMsg').textContent = '';
    const today = new Date().toISOString().slice(0,10);
    document.getElementById('start_date').value = today;
    document.getElementById('end_date').value = today;
    document.getElementById('title').value = '';
    document.getElementById('frequency').value = 'quincenal';
  }

  async function createPeriod(e){
    e.preventDefault();

    const payload = {
      frequency: document.getElementById('frequency').value,
      start_date: document.getElementById('start_date').value,
      end_date: document.getElementById('end_date').value,
      title: document.getElementById('title').value.trim() || null
    };

    const res = await fetch(periodsUrl, {
      method:'POST',
      headers:{
        'Accept':'application/json',
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': CSRF
      },
      body: JSON.stringify(payload)
    });

    const out = await res.json().catch(()=>({}));
    if(!res.ok){
      document.getElementById('periodMsg').textContent = out.message || 'No se pudo guardar.';
      return;
    }

    document.getElementById('periodMsg').textContent = 'Periodo creado.';
    await refreshPeriods();
    setTimeout(()=> bootstrap.Modal.getInstance(document.getElementById('periodModal'))?.hide(), 450);
  }

  function escapeHtml(str){
    return String(str ?? '').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
  }

  refreshPeriods();
</script>
@endsection
