@extends('layouts.app')
@section('title','Mantenimiento')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
  :root {
    /* Paleta Base Minimalista */
    --bg: #f4f5f7; 
    --card: #ffffff; 
    --input-bg: #f9fafb; 
    --ink-dark: #0f172a; 
    --ink: #334155; 
    --muted: #64748b; 
    --muted-light: #94a3b8;
    --line: #e2e8f0; 
    
    /* Colores de Acción y Estados */
    --blue: #007aff; 
    --blue-soft: #eff6ff; 
    --success: #15803d; 
    --success-soft: #f0fdf4; 
    --danger: #ef4444; 
    --danger-soft: #fef2f2;
    --warning: #b45309;
    --warning-soft: #fffbeb;
    
    /* Variables de Diseño Actualizadas */
    --font-family: 'Quicksand', sans-serif;
    --radius-card: 12px;
    --radius-modal: 12px; 
    --radius-input: 8px;
    --radius-btn: 8px;
  }

  body { 
    background: var(--bg); 
    font-family: var(--font-family);
    color: var(--ink);
    font-weight: 500;
    -webkit-font-smoothing: antialiased;
  }

  /* --- Contenedor Principal --- */
  .page { width: 100%; padding: 40px 24px; max-width: 1200px; margin: 0 auto; }
  .head { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 32px; }
  .title { margin: 0; font-size: 26px; font-weight: 700; color: var(--ink-dark); letter-spacing: -0.02em; }
  .sub { margin-top: 4px; color: var(--muted); font-size: 14px; font-weight: 600; }

  /* --- Botones --- */
  .btn-primary {
    background: var(--blue); color: #fff; border: none; border-radius: var(--radius-btn);
    height: 42px; padding: 0 20px; font-size: 14px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease;
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 122, 255, 0.25); color: #fff;}
  .btn-primary:active { transform: scale(0.98); }

  .btn-ghost {
    background: transparent; color: var(--muted); border: none; border-radius: var(--radius-btn);
    height: 42px; padding: 0 16px; font-size: 14px; font-weight: 700; transition: all 0.2s ease;
  }
  .btn-ghost:hover { background: var(--input-bg); color: var(--ink-dark); }

  /* --- Tarjetas de Estadísticas --- */
  .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
  .stat-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card); padding: 24px; transition: 0.3s ease; box-shadow: 0 2px 8px rgba(0,0,0,0.01); }
  .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.04); }
  .stat-card .num { font-size: 32px; font-weight: 700; line-height: 1; margin-bottom: 8px; }
  .stat-card .lbl { font-size: 14px; font-weight: 600; }
  .stat-blue .num { color: var(--blue); } .stat-blue .lbl { color: var(--muted); }
  .stat-amber .num { color: #d97706; } .stat-amber .lbl { color: var(--muted); }
  .stat-green .num { color: var(--success); } .stat-green .lbl { color: var(--muted); }
  .stat-gray .num { color: var(--ink-dark); } .stat-gray .lbl { color: var(--muted); }

  /* --- Búsqueda y Filtros --- */
  .filters-wrap { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; align-items: center; }
  .search-box { position: relative; flex: 1; min-width: 260px; }
  .search-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 15px; }
  .search-box input { 
    height: 42px; padding-left: 42px; background: var(--card); border-radius: var(--radius-input); 
    border: 1px solid var(--line); font-family: var(--font-family); font-weight: 600; font-size: 14px; 
    width: 100%; transition: 0.2s; 
  }
  .search-box input:focus { border-color: var(--blue); outline: none; box-shadow: 0 0 0 3px var(--blue-soft); }

  /* --- Tablas --- */
  .table-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; }
  .table-corporate { margin-bottom: 0; width: 100%; }
  .table-corporate thead th { background: var(--card); color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--line); border-top: none; }
  .table-corporate tbody td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid var(--line); font-size: 14px; font-weight: 600; }
  .table-corporate tbody tr:last-child td { border-bottom: none; }
  .table-corporate tbody tr:hover { background-color: var(--input-bg); }
  .asset-name { font-weight: 700; color: var(--ink-dark); }
  .asset-desc { font-size: 13px; color: var(--muted); margin-top: 4px; font-weight: 500; }
  
  .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
  .b-programado { background: var(--blue-soft); color: var(--blue); }
  .b-en_proceso { background: var(--warning-soft); color: var(--warning); }
  .b-completado { background: var(--success-soft); color: var(--success); }
  .b-cancelado { background: var(--danger-soft); color: var(--danger); }

  .action-btn { width: 34px; height: 34px; border-radius: 8px; border: none; background: transparent; color: var(--muted); display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; font-size: 15px; text-decoration: none; }
  .action-btn:hover { background: var(--input-bg); color: var(--ink-dark); transform: translateY(-1px); }
  .btn-complete-subtle { background: transparent; border: 1px solid #bbf7d0; color: var(--success); font-weight: 700; border-radius: 8px; padding: 6px 14px; font-size: 13px; transition: 0.2s; }
  .btn-complete-subtle:hover { background: var(--success-soft); border-color: var(--success); transform: translateY(-1px); }
  .empty-state { padding: 48px; text-align: center; color: var(--muted); font-weight: 600; }

  /* ========================================================
     MODAL REDISEÑADO PREMIUM (Animaciones, Blur, Scrollbar)
     ======================================================== */
  
  /* Efecto Blur para el fondo oscuro del modal */
  .modal-backdrop.show {
    opacity: 0.45;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    background-color: #0f172a;
  }

  /* Animación de entrada premium tipo Apple/Nelo */
  .modal.fade .modal-dialog {
    transform: scale(0.95) translateY(15px);
    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease;
  }
  .modal.show .modal-dialog {
    transform: scale(1) translateY(0);
  }

  .modal-corp .modal-content { 
    border: 1px solid rgba(255,255,255,0.1); 
    border-radius: var(--radius-modal); 
    box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); 
    background: var(--card); 
  }
  
  .modal-corp .modal-header { 
    border-bottom: 1px solid var(--line); 
    padding: 20px 28px; 
    background: var(--card);
    border-radius: var(--radius-modal) var(--radius-modal) 0 0;
  }
  
  .modal-corp .modal-title-text { font-size: 18px; font-weight: 700; color: var(--ink-dark); margin: 0; }
  .modal-corp .btn-close { background-size: 12px; opacity: 0.4; transition: 0.2s; }
  .modal-corp .btn-close:hover { opacity: 1; transform: rotate(90deg); }
  
  /* Modal Body con Custom Scrollbar y Desplazamiento forzado */
  .modal-corp .modal-body { 
    padding: 28px; 
    overflow-y: auto !important; /* Fuerza el scroll vertical si el contenido excede el alto */
  }
  
  .modal-corp .modal-body::-webkit-scrollbar { width: 6px; }
  .modal-corp .modal-body::-webkit-scrollbar-track { background: transparent; }
  .modal-corp .modal-body::-webkit-scrollbar-thumb { 
    background: #cbd5e1; 
    border-radius: 10px; 
  }
  .modal-corp .modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

  .modal-corp .modal-footer { 
    border-top: 1px solid var(--line); 
    padding: 16px 28px; 
    background: var(--input-bg); 
    border-radius: 0 0 var(--radius-modal) var(--radius-modal);
  }

  /* Elementos de Formulario (Inputs regulares) */
  .form-label { font-weight: 700; color: var(--ink-dark); font-size: 13px; margin-bottom: 6px; }
  .form-control {
    border-radius: var(--radius-input); border: 1px solid var(--line); background-color: var(--input-bg);
    padding: 10px 14px; font-size: 14px; font-weight: 600; color: var(--ink-dark); font-family: var(--font-family);
    height: 42px; transition: all 0.2s ease; box-shadow: inset 0 1px 2px rgba(0,0,0,0.01);
  }
  .form-control:focus { border-color: var(--blue); background-color: var(--card); box-shadow: 0 0 0 3px var(--blue-soft); outline: none; }
  .form-control::placeholder { color: var(--muted-light); font-weight: 500; }
  textarea.form-control { height: auto; min-height: 80px; resize: vertical; }

  /* ========================================================
     DISEÑO Y ANIMACIÓN DEL SELECT CUSTOM
     ======================================================== */
  .custom-select-wrapper {
    position: relative;
    user-select: none;
    width: 100%;
  }
  .custom-select-trigger {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: var(--radius-input);
    border: 1px solid var(--line);
    background-color: var(--input-bg);
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ink-dark);
    cursor: pointer;
    height: 42px;
    transition: all 0.2s ease;
  }
  .custom-select-trigger:hover { background-color: var(--card); border-color: #cbd5e1; }
  .custom-select-wrapper.open .custom-select-trigger {
    border-color: var(--blue);
    background-color: var(--card);
    box-shadow: 0 0 0 3px var(--blue-soft);
  }
  .custom-select-trigger i {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    color: var(--muted-light);
    font-size: 12px;
  }
  .custom-select-wrapper.open .custom-select-trigger i {
    transform: rotate(180deg);
    color: var(--blue);
  }
  .custom-options-container {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    right: 0;
    background: var(--card);
    border-radius: 12px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    border: 1px solid var(--line);
    z-index: 9999;
    overflow: hidden;
    transform-origin: top;
    transform: scaleY(0.9) translateY(-5px);
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .custom-select-wrapper.open .custom-options-container {
    transform: scaleY(1) translateY(0);
    opacity: 1;
    visibility: visible;
  }
  .custom-options-list {
    max-height: 220px;
    overflow-y: auto;
    padding: 6px;
  }
  .custom-options-list::-webkit-scrollbar { width: 6px; }
  .custom-options-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
  .custom-options-list::-webkit-scrollbar-track { background: transparent; }

  .custom-option {
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ink);
    cursor: pointer;
    border-radius: 8px;
    transition: background 0.2s, color 0.2s;
    margin-bottom: 2px;
  }
  .custom-option:last-child { margin-bottom: 0; }
  .custom-option:hover { background: var(--input-bg); color: var(--ink-dark); }
  .custom-option.selected { background: var(--blue-soft); color: var(--blue); }

  /* Alertas */
  .alert-corp { border-radius: var(--radius-input); padding: 16px 20px; margin-bottom: 24px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 12px; border: none; }
  .alert-corp.ok { background: var(--success-soft); color: var(--success); }
  .alert-corp.bad { background: var(--danger-soft); color: var(--danger); }
  .text-danger { color: var(--danger) !important; }

  @media (max-width: 991.98px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 767.98px) {
    .stats-grid { grid-template-columns: 1fr; }
    .table-corporate thead { display: none; }
    .table-corporate tbody td { display: block; padding: 12px 16px; border-bottom: none; text-align: right; }
    .table-corporate tbody td::before { content: attr(data-label); float: left; font-weight: 700; color: var(--muted); text-transform: uppercase; font-size: 12px; }
    .table-corporate tbody tr { border-bottom: 1px solid var(--line); display: block; padding: 8px 0; }
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
      'en_proceso' => 'bi-wrench',
      'completado' => 'bi-check2-circle',
      'cancelado'  => 'bi-x-circle',
      default => 'bi-dot',
    };
  }
@endphp

<div class="page">
  <div class="head">
    <div>
      <h1 class="title">Mantenimiento</h1>
      <p class="sub">{{ $maintenances->count() }} registros activos</p>
    </div>
    <button type="button" class="btn-primary" id="mmNewBtn">
      <i class="bi bi-plus-lg"></i>
      <span>Registrar Mantenimiento</span>
    </button>
  </div>

  @if(session('ok'))
    <div class="alert-corp ok"><i class="bi bi-check-circle-fill fs-5"></i>{{ session('ok') }}</div>
  @endif
  @if(session('bad'))
    <div class="alert-corp bad"><i class="bi bi-exclamation-triangle-fill fs-5"></i>{{ session('bad') }}</div>
  @endif

  <div class="stats-grid">
    <div class="stat-card stat-blue"><div class="num">{{ $counts['programado'] }}</div><div class="lbl">Programados</div></div>
    <div class="stat-card stat-amber"><div class="num">{{ $counts['en_proceso'] }}</div><div class="lbl">En proceso</div></div>
    <div class="stat-card stat-green"><div class="num">{{ $counts['completado'] }}</div><div class="lbl">Completados</div></div>
    <div class="stat-card stat-gray"><div class="num">{{ $counts['cancelado'] }}</div><div class="lbl">Cancelados</div></div>
  </div>

  <div class="filters-wrap">
    <div class="search-box">
      <i class="bi bi-search"></i>
      <input type="text" id="mtSearch" placeholder="Buscar por activo o técnico...">
    </div>
    <div style="min-width: 200px; z-index: 10;">
      <select id="mtStatusFilter" class="form-select animated-select">
        <option value="" selected>Todos los estados</option>
        <option value="programado">Programado</option>
        <option value="en_proceso">En proceso</option>
        <option value="completado">Completado</option>
        <option value="cancelado">Cancelado</option>
      </select>
    </div>
  </div>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table-corporate">
        <thead>
          <tr>
            <th>Activo</th>
            <th>Tipo</th>
            <th>Técnico</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody id="mtTableBody">
          @forelse($maintenances as $m)
            <tr class="mt-row" data-search="{{ strtolower(($m->item->name ?? '').' '.($m->technician ?? '')) }}" data-status="{{ $m->status }}">
              <td data-label="Activo">
                <div class="asset-name">{{ $m->item->name ?? 'Activo eliminado' }}</div>
                <div class="asset-desc">{{ \Illuminate\Support\Str::limit($m->description, 45) }}</div>
              </td>
              <td data-label="Tipo">{{ ucfirst($m->type) }}</td>
              <td data-label="Técnico">{{ $m->technician ?: '—' }}</td>
              <td data-label="Fecha">{{ optional($m->maintenance_date)->format('Y-m-d') }}</td>
              <td data-label="Estado">
                <span class="status-badge b-{{ $m->status }}">
                  <i class="bi {{ mtStatusIcon($m->status) }}"></i> {{ mtStatusLabel($m->status) }}
                </span>
              </td>
              <td data-label="Acciones" class="text-end">
                <div class="d-inline-flex align-items-center gap-2">
                  @if(!in_array($m->status, ['completado', 'cancelado']))
                    <form method="POST" action="{{ route('maintenance.complete', $m->id) }}" class="m-0">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn-complete-subtle" title="Marcar Completado">Completar</button>
                    </form>
                  @endif
                  <button type="button" class="action-btn js-edit-mt" title="Editar"
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
                    <i class="bi bi-pencil-square"></i>
                  </button>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="empty-state">No hay mantenimientos registrados en el sistema.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ===== MODAL REGISTRAR / EDITAR PREMIUM ===== --}}
<div class="modal fade modal-corp" id="mmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <form class="modal-content" method="POST" action="{{ route('maintenance.store') }}" id="mmForm">
      @csrf
      <input type="hidden" name="_method" id="mmMethod" value="POST">

      <div class="modal-header">
        <h4 class="modal-title-text" id="mmTitle">Registrar Mantenimiento</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body pb-5">
        <div class="mb-4">
          <label class="form-label">Activo <span class="text-danger">*</span></label>
          <select name="inventory_item_id" id="mmItem" class="form-select animated-select" required>
            <option value="" disabled selected>Selecciona un activo del inventario</option>
            @foreach($items as $it)
              <option value="{{ $it->id }}">{{ $it->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <label class="form-label">Tipo de mantenimiento</label>
            <select name="type" id="mmType" class="form-select animated-select">
              <option value="preventivo" selected>Preventivo</option>
              <option value="correctivo">Correctivo</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Estado</label>
            <select name="status" id="mmStatus" class="form-select animated-select">
              <option value="programado" selected>Programado</option>
              <option value="en_proceso">En proceso</option>
              <option value="completado">Completado</option>
              <option value="cancelado">Cancelado</option>
            </select>
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <label class="form-label">Técnico / Proveedor</label>
            <input type="text" name="technician" id="mmTechnician" class="form-control" placeholder="Nombre de la persona o empresa">
          </div>
          <div class="col-md-6">
            <label class="form-label">Costo Estimado ($)</label>
            <input type="number" step="0.01" min="0" name="cost" id="mmCost" class="form-control" placeholder="0.00">
          </div>
        </div>

        <div class="row g-4 mb-4">
          <div class="col-md-6">
            <label class="form-label">Fecha de mantenimiento</label>
            <input type="date" name="maintenance_date" id="mmDate" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Próximo mantenimiento</label>
            <input type="date" name="next_maintenance_date" id="mmNext" class="form-control">
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Descripción del trabajo</label>
          <textarea name="description" id="mmDescription" class="form-control" placeholder="Ej. Cambio de componentes, limpieza..."></textarea>
        </div>

        <div class="mb-2">
          <label class="form-label">Notas adicionales</label>
          <textarea name="notes" id="mmNotes" class="form-control" placeholder="Comentarios internos..."></textarea>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-primary" id="mmSubmit">Guardar Registro</button>
      </div>
    </form>
  </div>
</div>

<script>
  /* =======================================================
     SCRIPT: Selects Animados (Custom Dropdown)
     ======================================================= */
  document.addEventListener('DOMContentLoaded', () => {
    const selects = document.querySelectorAll('.animated-select');

    selects.forEach(nativeSelect => {
      nativeSelect.style.display = 'none';

      const wrapper = document.createElement('div');
      wrapper.className = 'custom-select-wrapper';
      nativeSelect.parentNode.insertBefore(wrapper, nativeSelect);
      wrapper.appendChild(nativeSelect);

      const trigger = document.createElement('div');
      trigger.className = 'custom-select-trigger';
      trigger.innerHTML = `<span>${nativeSelect.options[nativeSelect.selectedIndex]?.text || 'Seleccionar'}</span> <i class="bi bi-chevron-down"></i>`;
      wrapper.appendChild(trigger);

      const optionsContainer = document.createElement('div');
      optionsContainer.className = 'custom-options-container';
      const optionsList = document.createElement('div');
      optionsList.className = 'custom-options-list';
      optionsContainer.appendChild(optionsList);
      wrapper.appendChild(optionsContainer);

      Array.from(nativeSelect.options).forEach((option, index) => {
        if (option.disabled) return; 

        const customOption = document.createElement('div');
        customOption.className = 'custom-option';
        customOption.textContent = option.text;
        customOption.dataset.value = option.value;
        if (index === nativeSelect.selectedIndex) customOption.classList.add('selected');

        customOption.addEventListener('click', (e) => {
          e.stopPropagation();
          optionsList.querySelectorAll('.custom-option').forEach(el => el.classList.remove('selected'));
          customOption.classList.add('selected');
          trigger.querySelector('span').textContent = customOption.textContent;
          
          nativeSelect.value = customOption.dataset.value;
          nativeSelect.dispatchEvent(new Event('change'));
          wrapper.classList.remove('open');
        });
        optionsList.appendChild(customOption);
      });

      trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.custom-select-wrapper').forEach(w => {
          if (w !== wrapper) w.classList.remove('open');
        });
        wrapper.classList.toggle('open');
      });
    });

    document.addEventListener('click', () => {
      document.querySelectorAll('.custom-select-wrapper').forEach(w => w.classList.remove('open'));
    });
  });

  function syncAnimatedSelect(selectId) {
    const nativeSelect = document.getElementById(selectId);
    if (!nativeSelect) return;
    const wrapper = nativeSelect.closest('.custom-select-wrapper');
    if (!wrapper) return;

    const triggerSpan = wrapper.querySelector('.custom-select-trigger span');
    const customOptions = wrapper.querySelectorAll('.custom-option');
    const selectedIndex = nativeSelect.selectedIndex;
    
    triggerSpan.textContent = nativeSelect.options[selectedIndex]?.text || '';
    customOptions.forEach(opt => {
      if (opt.dataset.value === nativeSelect.value) {
        opt.classList.add('selected');
      } else {
        opt.classList.remove('selected');
      }
    });
  }

  /* =======================================================
     SCRIPT: Filtros y Modal 
     ======================================================= */
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

  const mmModalEl = document.getElementById('mmModal');
  const mmModal = () => bootstrap.Modal.getOrCreateInstance(mmModalEl);
  const mmForm = document.getElementById('mmForm');
  const STORE_URL = '{{ route('maintenance.store') }}';

  function resetMmForm(){
    mmForm.reset();
    mmForm.action = STORE_URL;
    document.getElementById('mmMethod').value = 'POST';
    document.getElementById('mmTitle').textContent = 'Registrar Mantenimiento';
    document.getElementById('mmSubmit').textContent = 'Guardar Registro';
    document.getElementById('mmDate').value = '{{ now()->format('Y-m-d') }}';

    document.getElementById('mmItem').value = '';
    document.getElementById('mmType').value = 'preventivo';
    document.getElementById('mmStatus').value = 'programado';
    syncAnimatedSelect('mmItem');
    syncAnimatedSelect('mmType');
    syncAnimatedSelect('mmStatus');
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
      
      syncAnimatedSelect('mmItem');
      syncAnimatedSelect('mmType');
      syncAnimatedSelect('mmStatus');

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