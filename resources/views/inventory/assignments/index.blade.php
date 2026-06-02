@extends('layouts.app')
@section('title','Asignaciones')

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
    --bg: #f4f5f7; /* Fondo página */
    --card: #ffffff; 
    --input-bg: #f9fafb; /* Fondo de inputs tipo Apple/Nelo */
    --ink-dark: #0f172a; 
    --ink: #334155; 
    --muted: #64748b; 
    --muted-light: #94a3b8;
    --line: #e2e8f0; 
    --blue: #007aff; 
    --blue-soft: #eff6ff; 
    --success: #15803d; 
    --success-soft: #f0fdf4; 
    --danger: #ef4444; 
    --danger-soft: #fef2f2;
    
    /* Variables de Diseño */
    --font-family: 'Quicksand', sans-serif;
    --radius-card: 16px;
    --radius-modal: 20px;
    --radius-input: 10px;
    --radius-btn: 10px;
  }

  body { 
    background: var(--bg); 
    font-family: var(--font-family);
    color: var(--ink);
    font-weight: 500;
    -webkit-font-smoothing: antialiased;
  }

  /* --- Contenedor y Tablas (Se mantiene la base anterior limpia) --- */
  .page {
    width: 100%;
    padding: 40px 24px;
    max-width: 1200px; 
    margin: 0 auto;
  }

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

  /* Búsqueda y Tablas */
  .search-wrap { margin-bottom: 24px; max-width: 360px; }
  .search-box { position: relative; }
  .search-box i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 15px; }
  .search-box input { height: 44px; padding-left: 42px; background: var(--card); border-radius: var(--radius-input); border: 1px solid var(--line); font-family: var(--font-family); font-weight: 600; font-size: 14px; width: 100%; transition: 0.2s; }
  .search-box input:focus { border-color: var(--blue); outline: none; box-shadow: 0 0 0 3px var(--blue-soft); }

  .table-card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-card); box-shadow: 0 2px 8px rgba(0,0,0,0.02); overflow: hidden; }
  .table-corporate { margin-bottom: 0; width: 100%; }
  .table-corporate thead th { background: var(--card); color: var(--muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 16px 24px; border-bottom: 1px solid var(--line); border-top: none; }
  .table-corporate tbody td { padding: 16px 24px; vertical-align: middle; border-bottom: 1px solid var(--line); font-size: 14px; font-weight: 600; }
  .table-corporate tbody tr:last-child td { border-bottom: none; }
  .table-corporate tbody tr:hover { background-color: var(--input-bg); }
  
  .asset-name, .user-name { font-weight: 700; color: var(--ink-dark); }
  .text-xs { font-size: 12px; color: var(--muted); margin-top: 2px; font-weight: 600; }
  .status-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
  .badge-active { background: var(--success-soft); color: var(--success); }
  .badge-return { background: var(--input-bg); color: var(--muted); }

  .action-btn { width: 34px; height: 34px; border-radius: 8px; border: none; background: transparent; color: var(--muted); display: inline-flex; align-items: center; justify-content: center; transition: 0.2s; font-size: 15px; }
  .action-btn:hover { background: var(--blue-soft); color: var(--blue); transform: translateY(-1px); }

  /* --- MODAL REDISEÑADO COMPACTO --- */
  .modal-corp .modal-content {
    border: none;
    border-radius: var(--radius-modal);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    background: var(--card);
  }

  .modal-corp .modal-header {
    border-bottom: none; /* Sin línea separadora */
    padding: 24px 28px 16px; /* Padding ajustado */
  }

  .modal-corp .modal-title-text {
    font-size: 18px;
    font-weight: 700;
    color: var(--ink-dark);
    margin: 0;
  }

  .modal-corp .btn-close {
    background-size: 12px;
    opacity: 0.5;
    transition: 0.2s;
  }
  .modal-corp .btn-close:hover { opacity: 1; }

  .modal-corp .modal-body {
    padding: 0 28px 24px;
  }

  .modal-corp .modal-footer {
    border-top: none; /* Sin línea separadora */
    padding: 0 28px 24px;
    background: transparent;
  }

  /* --- FORMULARIOS COMPACTOS --- */
  .form-label {
    font-weight: 700;
    color: var(--ink-dark);
    font-size: 13px;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .form-control, .form-select {
    border-radius: var(--radius-input);
    border: 1px solid var(--line);
    background-color: var(--card);
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 600;
    color: var(--ink-dark);
    font-family: var(--font-family);
    height: 42px; /* Altura compacta */
    transition: all 0.2s ease;
    box-shadow: 0 1px 2px rgba(0,0,0,0.01);
  }

  /* Efecto Focus Elegante */
  .form-control:focus, .form-select:focus {
    border-color: var(--blue);
    background-color: var(--card);
    box-shadow: 0 0 0 3px var(--blue-soft);
    outline: none;
  }

  .form-control::placeholder {
    color: var(--muted-light);
    font-weight: 500;
  }

  textarea.form-control {
    height: auto;
    min-height: 80px;
    resize: vertical;
  }

  /* Diseño Personalizado Selects Nativos */
  .form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 14px;
    padding-right: 36px;
    cursor: pointer;
  }
  .form-select:focus {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23007aff' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='18 15 12 9 6 15'%3E%3C/polyline%3E%3C/svg%3E");
  }

  /* --- ÁREA DE FIRMA ESTILO IMAGEN --- */
  .sig-wrapper {
    position: relative;
    width: 100%;
  }

  .sig-container {
    border: 1.5px dashed #cbd5e1;
    border-radius: 12px;
    background: transparent;
    overflow: hidden;
    position: relative;
    transition: 0.2s ease;
  }
  
  .sig-container:hover {
    border-color: var(--blue);
    background: var(--blue-soft);
  }

  #sigCanvas {
    width: 100%;
    height: 160px; /* Más compacto */
    display: block;
    cursor: crosshair;
    position: relative;
    z-index: 2;
  }

  /* Placeholder de la firma (el icono en medio) */
  .sig-placeholder {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: var(--muted-light);
    text-align: center;
    z-index: 1;
    pointer-events: none; /* Los clics pasan al canvas */
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
  }
  .sig-placeholder i { font-size: 20px; }
  .sig-placeholder span { font-size: 13px; font-weight: 600; }

  .clear-btn {
    font-size: 12px;
    font-weight: 700;
    color: var(--muted);
    background: none;
    border: none;
    padding: 4px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: 0.2s;
  }
  .clear-btn:hover { background: var(--danger-soft); color: var(--danger); }
  
  .text-danger { color: var(--danger) !important; }
</style>

<div class="page">
  <div class="head">
    <div>
      <h1 class="title">Gestión de Asignaciones</h1>
      <div class="sub">Tienes {{ $activeCount }} asignaciones activas.</div>
    </div>
    <button class="btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
      <i class="bi bi-plus-lg"></i><span>Nueva Asignación</span>
    </button>
  </div>

  <div class="search-wrap">
    <div class="search-box">
      <i class="bi bi-search"></i>
      <input type="text" id="assignmentSearch" placeholder="Buscar activo o usuario...">
    </div>
  </div>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-corporate align-middle" id="assignmentsTable">
        <thead>
          <tr>
            <th>Activo</th>
            <th>Usuario</th>
            <th>Cant.</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($assignments as $assignment)
            <tr class="assignment-row" data-search="{{ strtolower(($assignment->item->name ?? '').' '.($assignment->user->name ?? '')) }}">
              <td><div class="asset-name">{{ $assignment->item->name ?? 'Activo eliminado' }}</div></td>
              <td>
                <div class="user-name">{{ $assignment->user->name ?? 'Usuario' }}</div>
                <div class="text-xs">{{ $assignment->user->email ?? 'Sin correo' }}</div>
              </td>
              <td>{{ $assignment->quantity }}</td>
              <td>{{ optional($assignment->assigned_at)->format('d/m/Y') }}</td>
              <td>
                @if($assignment->status === 'activa')
                  <span class="status-badge badge-active">Activa</span>
                @else
                  <span class="status-badge badge-return">Devuelta</span>
                @endif
              </td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <a href="{{ route('assets.assignments.pdf', $assignment->id) }}" target="_blank" class="action-btn" title="Ver PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>
                  @if($assignment->status === 'activa')
                    <button type="button" class="action-btn" data-bs-toggle="modal" data-bs-target="#returnModal" data-assignment-id="{{ $assignment->id }}" data-item-name="{{ $assignment->item->name ?? 'Activo' }}">
                      <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5 text-muted fw-bold">No hay asignaciones registradas.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- MODAL NUEVA ASIGNACIÓN (Diseño Compacto e Integrado) --}}
<div class="modal fade modal-corp" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content" method="POST" action="{{ route('assets.assignments.store') }}" id="assignForm">
      @csrf
      <div class="modal-header">
        <h4 class="modal-title-text">Asignar Activo</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Activo <span class="text-danger">*</span></label>
          <select class="form-select" name="inventory_item_id" required>
            <option value="" disabled selected>Selecciona un activo</option>
            @foreach($items as $item)
              <option value="{{ $item->id }}">{{ $item->name }} (#ACT-000{{ $item->id }})</option>
            @endforeach
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Usuario <span class="text-danger">*</span></label>
          <select class="form-select" name="user_id" required>
            <option value="" disabled selected>Selecciona un usuario</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="quantity" min="1" value="1" required>
          </div>
          <div class="col-6">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control" value="{{ date('Y-m-d') }}" readonly>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Notas</label>
          <textarea class="form-control" name="notes" placeholder="Agrega notas o detalles del equipo..."></textarea>
        </div>

        <div class="mb-1">
          <label class="form-label">
            <span>Firma digital del responsable <span class="text-danger">*</span></span>
            <button type="button" class="clear-btn" id="clearSignatureBtn">
              <i class="bi bi-pencil"></i> Borrar
            </button>
          </label>
          <div class="text-xs text-muted mb-2 fw-medium" style="margin-top: -6px;">Dibuja tu firma con el mouse o dedo</div>
          
          <div class="sig-wrapper">
            <div class="sig-placeholder" id="sigPlaceholder">
              <i class="bi bi-pen"></i>
              <span>Firma aquí</span>
            </div>
            <div class="sig-container">
              <canvas id="sigCanvas"></canvas>
            </div>
          </div>
          <input type="hidden" name="signature" id="signatureInput">
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

{{-- MODAL DEVOLUCIÓN --}}
<div class="modal fade modal-corp" id="returnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="returnForm">
      @csrf
      <div class="modal-header">
        <div>
          <h4 class="modal-title-text">Procesar Devolución</h4>
          <div class="text-muted small mt-1 fw-bold" id="returnItemLabel">Activo</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Condición <span class="text-danger">*</span></label>
          <select class="form-select" name="return_condition" required>
            <option value="" disabled selected>Selecciona el estado</option>
            <option value="excelente">Excelente</option>
            <option value="bueno">Bueno</option>
            <option value="regular">Regular</option>
            <option value="malo">Malo</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label">Detalles <span class="text-danger">*</span></label>
          <textarea class="form-control" name="return_details" required placeholder="Describe motivo y estado..."></textarea>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-primary">Confirmar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Búsqueda rápida
  const searchInput = document.getElementById('assignmentSearch');
  searchInput?.addEventListener('input', e => {
    const q = e.target.value.toLowerCase().trim();
    document.querySelectorAll('.assignment-row').forEach(row => {
      row.style.display = (!q || row.dataset.search.includes(q)) ? '' : 'none';
    });
  });

  // Lógica de Firma Rediseñada
  const canvas = document.getElementById('sigCanvas');
  const signatureInput = document.getElementById('signatureInput');
  const clearBtn = document.getElementById('clearSignatureBtn');
  const sigPlaceholder = document.getElementById('sigPlaceholder');
  const assignModal = document.getElementById('assignModal');
  const ctx = canvas.getContext('2d');
  let drawing = false;
  let hasDrawn = false;

  function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(ratio, ratio);
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a';
  }

  function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    const evt = e.touches ? e.touches[0] : e;
    return { x: evt.clientX - rect.left, y: evt.clientY - rect.top };
  }

  function startDraw(e) {
    drawing = true;
    hasDrawn = true;
    sigPlaceholder.style.opacity = '0'; // Oculta el placeholder suavemente
    const p = getPos(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
  }

  function draw(e) {
    if (!drawing) return;
    e.preventDefault();
    const p = getPos(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
  }

  function endDraw() { drawing = false; }

  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', draw);
  window.addEventListener('mouseup', endDraw);
  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  window.addEventListener('touchend', endDraw);

  clearBtn.addEventListener('click', () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    signatureInput.value = '';
    hasDrawn = false;
    sigPlaceholder.style.opacity = '1'; // Muestra el placeholder de nuevo
  });

  assignModal.addEventListener('shown.bs.modal', () => {
    resizeCanvas();
    clearBtn.click();
  });
  window.addEventListener('resize', () => { if (assignModal.classList.contains('show')) resizeCanvas(); });

  document.getElementById('assignForm').addEventListener('submit', e => {
    if (!hasDrawn) {
      e.preventDefault();
      alert('Por favor, capture la firma.');
      return;
    }
    signatureInput.value = canvas.toDataURL('image/png');
  });

  // Modal de Devolución
  document.getElementById('returnModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('returnForm').action = `/internal-assets/assignments/${btn.dataset.assignmentId}/return`;
    document.getElementById('returnItemLabel').textContent = btn.dataset.itemName;
  });
</script>
@endsection