@extends('layouts.app')
@section('title','Mantenimiento')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
  :root{
    --bg:#f6f8fc;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e6edf5;
    --teal:#13998f;
  }
  html,body{ background:var(--bg); color:var(--ink); }
  .mt-page{ padding:24px 20px 40px; }
  .mt-head{
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:14px; flex-wrap:wrap; margin-bottom:22px;
  }
  .mt-title{ margin:0; font-size:26px; font-weight:800; color:#0b1f44; }
  .mt-sub{ margin:4px 0 0; color:var(--muted); font-size:14px; }
  .mt-btn-new{
    border:none; background:var(--teal); color:#fff; font-weight:700;
    border-radius:12px; padding:12px 20px; display:inline-flex; align-items:center;
    gap:8px; box-shadow:0 10px 20px rgba(19,153,143,.18); cursor:pointer; font-size:15px;
  }
  .mt-stats{
    display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:20px;
  }
  .mt-stat{
    border-radius:16px; padding:20px 22px; border:1px solid var(--line); background:#fff;
  }
  .mt-stat .num{ font-size:34px; font-weight:800; line-height:1; }
  .mt-stat .lbl{ margin-top:6px; font-size:14px; font-weight:600; }
  .mt-stat.blue{ background:#eff6ff; border-color:#dbeafe; }
  .mt-stat.blue .num,.mt-stat.blue .lbl{ color:#2563eb; }
  .mt-stat.amber{ background:#fffbeb; border-color:#fef3c7; }
  .mt-stat.amber .num,.mt-stat.amber .lbl{ color:#d97706; }
  .mt-stat.green{ background:#ecfdf5; border-color:#d1fae5; }
  .mt-stat.green .num,.mt-stat.green .lbl{ color:#16a34a; }
  .mt-stat.gray{ background:#fff; border-color:var(--line); }
  .mt-stat.gray .num{ color:#0f172a; } .mt-stat.gray .lbl{ color:var(--muted); }

  .mt-filters{
    background:#fff; border:1px solid var(--line); border-radius:16px;
    padding:14px; margin-bottom:18px; display:flex; gap:14px; flex-wrap:wrap; align-items:center;
  }
  .mt-search{ position:relative; flex:1; min-width:240px; }
  .mt-search .bi{ position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; }
  .mt-search input{
    width:100%; padding:12px 12px 12px 42px; border:1px solid #e2e8f0;
    border-radius:12px; font-size:14px;
  }
  .mt-filter-select{
    border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; font-size:14px; min-width:200px;
  }

  .mt-table-wrap{ background:#fff; border:1px solid var(--line); border-radius:16px; overflow:hidden; }
  .mt-table{ width:100%; border-collapse:collapse; }
  .mt-table th{
    text-align:left; font-size:14px; color:#475569; font-weight:700;
    padding:18px 20px; border-bottom:1px solid var(--line);
  }
  .mt-table td{ padding:18px 20px; border-bottom:1px solid #f1f5f9; vertical-align:middle; font-size:14px; }
  .mt-table tr:last-child td{ border-bottom:none; }
  .mt-asset-name{ font-weight:800; color:#0f172a; }
  .mt-asset-desc{ color:#94a3b8; font-size:13px; margin-top:2px; }
  .mt-badge{
    display:inline-flex; align-items:center; gap:6px; border-radius:999px;
    padding:6px 12px; font-size:13px; font-weight:700;
  }
  .b-programado{ background:#dbeafe; color:#2563eb; }
  .b-en_proceso{ background:#fef3c7; color:#d97706; }
  .b-completado{ background:#dcfce7; color:#16a34a; }
  .b-cancelado{ background:#fee2e2; color:#dc2626; }
  .mt-link-edit{
    background:none; border:none; color:#0f172a; font-weight:700; cursor:pointer; padding:0; font-size:14px;
  }
  .mt-btn-complete{
    border:1px solid #86efac; background:#fff; color:#16a34a; font-weight:700;
    border-radius:10px; padding:7px 16px; cursor:pointer; font-size:14px;
  }
  .mt-btn-complete:hover{ background:#f0fdf4; }
  .mt-empty{ padding:40px; text-align:center; color:#94a3b8; }

  /* Modal */
  .mm-modal{ border:none; border-radius:18px; }
  .mm-head{ display:flex; align-items:center; justify-content:space-between; padding:24px 26px 4px; }
  .mm-head h5{ margin:0; font-size:22px; font-weight:800; }
  .mm-close{ border:none; background:transparent; font-size:22px; color:#475569; }
  .mm-body{ padding:18px 26px 4px; }
  .mm-label{ display:block; font-weight:700; font-size:14px; margin-bottom:6px; }
  .mm-label .req{ color:#ef4444; }
  .mm-input,.mm-select,.mm-textarea{
    width:100%; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px;
    font-size:15px; margin-bottom:16px;
  }
  .mm-input:focus,.mm-select:focus,.mm-textarea:focus{
    outline:none; border-color:#b9d8ff; box-shadow:0 0 0 4px rgba(37,99,235,.08);
  }
  .mm-textarea{ min-height:90px; resize:vertical; }
  .mm-foot{ display:flex; justify-content:flex-end; gap:12px; padding:14px 26px 24px; }
  .mm-cancel{ border:1px solid #e2e8f0; background:#fff; font-weight:700; border-radius:12px; padding:12px 24px; }
  .mm-save{ border:none; background:var(--teal); color:#fff; font-weight:800; border-radius:12px; padding:12px 24px; }

  @media (max-width:767.98px){
    .mt-stats{ grid-template-columns:1fr 1fr; }
    .mt-table thead{ display:none; }
  }
</style>

@php
  function mtStatusLabel($s){
    return match($s){
      'programado' => 'Programado',
      'en_proceso' => 'En proceso',
      'completado' => 'Completado',
      'cancelado'  => 'Cancelado',
      default => ucfirst($s),
    };
  }
  function mtStatusIcon($s){
    return match($s){
      'programado' => 'bi-clock',
      'en_proceso' => 'bi-wrench-adjustable',
      'completado' => 'bi-check-circle',
      'cancelado'  => 'bi-x-circle',
      default => 'bi-dot',
    };
  }
@endphp

<div class="mt-page">
  <div class="mt-head">
    <div>
      <h1 class="mt-title">Mantenimiento</h1>
      <p class="mt-sub">{{ $maintenances->count() }} registros</p>
    </div>
    <button type="button" class="mt-btn-new" id="mmNewBtn">
      <i class="bi bi-plus-lg"></i>
      <span>Registrar mantenimiento</span>
    </button>
  </div>

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif
  @if(session('bad'))
    <div class="alert alert-danger">{{ session('bad') }}</div>
  @endif

  <div class="mt-stats">
    <div class="mt-stat blue">
      <div class="num">{{ $counts['programado'] }}</div>
      <div class="lbl">Programados</div>
    </div>
    <div class="mt-stat amber">
      <div class="num">{{ $counts['en_proceso'] }}</div>
      <div class="lbl">En proceso</div>
    </div>
    <div class="mt-stat green">
      <div class="num">{{ $counts['completado'] }}</div>
      <div class="lbl">Completados</div>
    </div>
    <div class="mt-stat gray">
      <div class="num">{{ $counts['cancelado'] }}</div>
      <div class="lbl">Cancelados</div>
    </div>
  </div>

  <div class="mt-filters">
    <div class="mt-search">
      <i class="bi bi-search"></i>
      <input type="text" id="mtSearch" placeholder="Buscar por activo o técnico...">
    </div>
    <select id="mtStatusFilter" class="mt-filter-select">
      <option value="">Todos los estados</option>
      <option value="programado">Programado</option>
      <option value="en_proceso">En proceso</option>
      <option value="completado">Completado</option>
      <option value="cancelado">Cancelado</option>
    </select>
  </div>

  <div class="mt-table-wrap">
    <table class="mt-table">
      <thead>
        <tr>
          <th>Activo</th>
          <th>Tipo</th>
          <th>Técnico</th>
          <th>Fecha</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="mtTableBody">
        @forelse($maintenances as $m)
          <tr class="mt-row"
              data-search="{{ strtolower(($m->item->name ?? '').' '.($m->technician ?? '')) }}"
              data-status="{{ $m->status }}">
            <td>
              <div class="mt-asset-name">{{ $m->item->name ?? 'Activo eliminado' }}</div>
              <div class="mt-asset-desc">{{ \Illuminate\Support\Str::limit($m->description, 40) }}</div>
            </td>
            <td>{{ ucfirst($m->type) }}</td>
            <td>{{ $m->technician ?: '—' }}</td>
            <td>{{ optional($m->maintenance_date)->format('Y-m-d') }}</td>
            <td>
              <span class="mt-badge b-{{ $m->status }}">
                <i class="bi {{ mtStatusIcon($m->status) }}"></i>
                {{ mtStatusLabel($m->status) }}
              </span>
            </td>
            <td>
              <div class="d-flex align-items-center gap-3">
                <button type="button" class="mt-link-edit js-edit-mt"
                  data-id="{{ $m->id }}"
                  data-item="{{ $m->inventory_item_id }}"
                  data-type="{{ $m->type }}"
                  data-status="{{ $m->status }}"
                  data-technician="{{ $m->technician }}"
                  data-cost="{{ $m->cost }}"
                  data-date="{{ optional($m->maintenance_date)->format('Y-m-d') }}"
                  data-next="{{ optional($m->next_maintenance_date)->format('Y-m-d') }}"
                  data-description="{{ $m->description }}"
                  data-notes="{{ $m->notes }}"
                  data-update-url="{{ route('maintenance.update', $m->id) }}">
                  Editar
                </button>

                @if(!in_array($m->status, ['completado', 'cancelado']))
                  <form method="POST" action="{{ route('maintenance.complete', $m->id) }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="mt-btn-complete">Completar</button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="mt-empty">No hay mantenimientos registrados.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ===== MODAL REGISTRAR / EDITAR ===== --}}
<div class="modal fade" id="mmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content mm-modal">
      <form method="POST" action="{{ route('maintenance.store') }}" id="mmForm">
        @csrf
        <input type="hidden" name="_method" id="mmMethod" value="POST">

        <div class="mm-head">
          <h5 id="mmTitle">Registrar Mantenimiento</h5>
          <button type="button" class="mm-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="mm-body">
          <label class="mm-label">Activo <span class="req">*</span></label>
          <select name="inventory_item_id" id="mmItem" class="mm-select" required>
            <option value="">Selecciona un activo</option>
            @foreach($items as $it)
              <option value="{{ $it->id }}">{{ $it->name }}</option>
            @endforeach
          </select>

          <div class="row">
            <div class="col-md-6">
              <label class="mm-label">Tipo de mantenimiento</label>
              <select name="type" id="mmType" class="mm-select">
                <option value="preventivo">Preventivo</option>
                <option value="correctivo">Correctivo</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="mm-label">Estado</label>
              <select name="status" id="mmStatus" class="mm-select">
                <option value="programado">Programado</option>
                <option value="en_proceso">En proceso</option>
                <option value="completado">Completado</option>
                <option value="cancelado">Cancelado</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label class="mm-label">Técnico / Proveedor</label>
              <input type="text" name="technician" id="mmTechnician" class="mm-input" placeholder="Nombre del técnico">
            </div>
            <div class="col-md-6">
              <label class="mm-label">Costo ($)</label>
              <input type="number" step="0.01" min="0" name="cost" id="mmCost" class="mm-input" placeholder="0.00">
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <label class="mm-label">Fecha de mantenimiento</label>
              <input type="date" name="maintenance_date" id="mmDate" class="mm-input" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-md-6">
              <label class="mm-label">Próximo mantenimiento</label>
              <input type="date" name="next_maintenance_date" id="mmNext" class="mm-input">
            </div>
          </div>

          <label class="mm-label">Descripción del trabajo</label>
          <textarea name="description" id="mmDescription" class="mm-textarea" placeholder="Ej. Cambio de teclado, limpieza interna..."></textarea>

          <label class="mm-label">Notas adicionales</label>
          <textarea name="notes" id="mmNotes" class="mm-textarea"></textarea>
        </div>

        <div class="mm-foot">
          <button type="button" class="mm-cancel" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="mm-save" id="mmSubmit">Registrar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  /* Filtros */
  const mtSearch = document.getElementById('mtSearch');
  const mtStatusFilter = document.getElementById('mtStatusFilter');
  const mtRows = document.querySelectorAll('.mt-row');

  function applyMtFilters(){
    const q = (mtSearch.value || '').trim().toLowerCase();
    const st = mtStatusFilter.value;
    mtRows.forEach(row => {
      const matchSearch = !q || (row.dataset.search || '').includes(q);
      const matchStatus = !st || row.dataset.status === st;
      row.style.display = (matchSearch && matchStatus) ? '' : 'none';
    });
  }
  mtSearch.addEventListener('input', applyMtFilters);
  mtStatusFilter.addEventListener('change', applyMtFilters);

  /* Modal */
  const mmModalEl = document.getElementById('mmModal');
  const mmModal = () => bootstrap.Modal.getOrCreateInstance(mmModalEl);
  const mmForm = document.getElementById('mmForm');
  const STORE_URL = '{{ route('maintenance.store') }}';

  function resetMmForm(){
    mmForm.reset();
    mmForm.action = STORE_URL;
    document.getElementById('mmMethod').value = 'POST';
    document.getElementById('mmTitle').textContent = 'Registrar Mantenimiento';
    document.getElementById('mmSubmit').textContent = 'Registrar';
    document.getElementById('mmDate').value = '{{ now()->format('Y-m-d') }}';
  }

  document.getElementById('mmNewBtn').addEventListener('click', () => {
    resetMmForm();
    mmModal().show();
  });

  document.querySelectorAll('.js-edit-mt').forEach(btn => {
    btn.addEventListener('click', () => {
      const d = btn.dataset;
      mmForm.action = d.updateUrl;
      document.getElementById('mmMethod').value = 'PUT';
      document.getElementById('mmTitle').textContent = 'Editar Mantenimiento';
      document.getElementById('mmSubmit').textContent = 'Guardar cambios';

      document.getElementById('mmItem').value = d.item || '';
      document.getElementById('mmType').value = d.type || 'preventivo';
      document.getElementById('mmStatus').value = d.status || 'programado';
      document.getElementById('mmTechnician').value = d.technician || '';
      document.getElementById('mmCost').value = d.cost || '';
      document.getElementById('mmDate').value = d.date || '';
      document.getElementById('mmNext').value = d.next || '';
      document.getElementById('mmDescription').value = d.description || '';
      document.getElementById('mmNotes').value = d.notes || '';

      mmModal().show();
    });
  });
</script>
@endsection