@extends('layouts.app')
@section('title','Asignaciones')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
  :root {
    /* Paleta Corporativa */
    --bg-body: #f8fafc;
    --bg-surface: #ffffff;
    --text-main: #0f172a;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    
    /* Colores de Marca (Azul Corporativo) */
    --primary: #1e40af; 
    --primary-hover: #1e3a8a;
    --primary-ring: rgba(30, 64, 175, 0.15);
    
    /* Estados */
    --success-bg: #f0fdf4;
    --success-text: #166534;
    --neutral-bg: #f1f5f9;
    --neutral-text: #475569;
    
    /* Sombras y Radios */
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
    --radius-modal: 12px;
    --radius-card: 10px;
    --radius-btn: 6px;
    --radius-input: 6px;
    
    --font-family: 'Inter', system-ui, sans-serif;
  }

  body { 
    background: var(--bg-body); 
    font-family: var(--font-family);
    color: var(--text-main);
    -webkit-font-smoothing: antialiased;
  }

  .page {
    width: 100%;
    padding: 32px 24px;
    max-width: 1400px;
    margin: 0 auto;
  }

  /* Header Section */
  .head {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
  }

  .title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: var(--text-main);
    letter-spacing: -0.02em;
  }

  .sub {
    margin-top: 4px;
    color: var(--text-muted);
    font-size: 14px;
    font-weight: 500;
  }

  /* Buttons */
  .btn-corp-primary {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: var(--radius-btn);
    height: 40px;
    padding: 0 20px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
  }

  .btn-corp-primary:hover { 
    color: #fff; 
    background: var(--primary-hover); 
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
  }

  .btn-corp-secondary {
    border: 1px solid var(--border-color);
    background: var(--bg-surface);
    color: var(--text-main);
    border-radius: var(--radius-btn);
    height: 40px;
    padding: 0 16px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
  }

  .btn-corp-secondary:hover {
    background: var(--neutral-bg);
    border-color: #cbd5e1;
  }

  /* Search Box */
  .search-wrap {
    margin-bottom: 20px;
    max-width: 400px;
  }

  .search-box {
    position: relative;
  }

  .search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 14px;
  }

  .search-box input {
    height: 40px;
    padding-left: 40px;
    border-radius: var(--radius-input);
    border: 1px solid var(--border-color);
    background: var(--bg-surface);
    font-size: 14px;
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease;
  }

  .search-box input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-ring);
    outline: none;
  }

  /* Table Card */
  .table-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-sm);
    overflow: hidden;
  }

  .table-corporate {
    margin-bottom: 0;
  }

  .table-corporate thead th {
    background: #f8fafc;
    color: var(--text-muted);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 20px;
    border-bottom: 1px solid var(--border-color);
  }

  .table-corporate tbody td {
    padding: 16px 20px;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    font-size: 14px;
  }

  .table-corporate tbody tr:last-child td {
    border-bottom: none;
  }
  
  .table-corporate tbody tr:hover {
    background-color: #fcfcfd;
  }

  .asset-name {
    font-weight: 600;
    color: var(--text-main);
  }

  .user-name {
    font-weight: 500;
    color: var(--text-main);
  }

  .text-xs {
    font-size: 12px;
    color: var(--text-muted);
    margin-top: 2px;
  }

  /* Badges */
  .status-badge {
    display: inline-flex;
    align-items: center;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.02em;
  }

  .badge-active {
    background: var(--success-bg);
    color: var(--success-text);
    border: 1px solid #bbf7d0;
  }

  .badge-return {
    background: var(--neutral-bg);
    color: var(--neutral-text);
    border: 1px solid #e2e8f0;
  }

  /* Icon Buttons */
  .action-btn {
    width: 32px;
    height: 32px;
    border-radius: var(--radius-btn);
    border: 1px solid transparent;
    background: transparent;
    color: var(--text-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
  }

  .action-btn:hover {
    background: var(--neutral-bg);
    border-color: var(--border-color);
    color: var(--text-main);
  }

  /* Modals */
  .modal-corp .modal-content {
    border: none;
    border-radius: var(--radius-modal);
    box-shadow: var(--shadow-lg);
  }

  .modal-corp .modal-header {
    border-bottom: 1px solid var(--border-color);
    padding: 20px 24px;
    background: var(--bg-surface);
    border-radius: var(--radius-modal) var(--radius-modal) 0 0;
  }

  .modal-corp .modal-title-text {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-main);
    margin: 0;
  }

  .modal-corp .modal-body {
    padding: 24px;
  }

  .modal-corp .modal-footer {
    border-top: 1px solid var(--border-color);
    padding: 16px 24px;
    background: #f8fafc;
    border-radius: 0 0 var(--radius-modal) var(--radius-modal);
  }

  /* Form Elements */
  .form-label {
    font-weight: 500;
    color: var(--text-main);
    font-size: 14px;
    margin-bottom: 6px;
  }

  .form-control, .form-select {
    border-radius: var(--radius-input);
    border: 1px solid var(--border-color);
    padding: 10px 14px;
    font-size: 14px;
    color: var(--text-main);
    box-shadow: var(--shadow-sm);
  }

  .form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--primary-ring);
  }

  /* Signature Area */
  .sig-container {
    border: 1px solid var(--border-color);
    border-radius: var(--radius-input);
    background: var(--bg-surface);
    overflow: hidden;
    box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02);
  }

  #sigCanvas {
    width: 100%;
    height: 180px;
    display: block;
    cursor: crosshair;
  }

  .toolbar-sig {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .clear-btn {
    font-size: 12px;
    color: var(--text-muted);
    background: none;
    border: none;
    padding: 4px 8px;
    border-radius: 4px;
  }
  
  .clear-btn:hover {
    background: var(--neutral-bg);
    color: var(--text-main);
  }

  /* Alerts */
  .alert-corp {
    border-radius: var(--radius-input);
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .alert-corp.ok {
    background: var(--success-bg);
    color: var(--success-text);
    border: 1px solid #bbf7d0;
  }

  .alert-corp.bad {
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
  }
</style>

<div class="page">
  <div class="head">
    <div>
      <h1 class="title">Gestión de Asignaciones</h1>
      <div class="sub">Tienes {{ $activeCount }} asignaciones activas en el sistema.</div>
    </div>

    <button class="btn-corp-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
      <i class="bi bi-plus-lg"></i>
      <span>Nueva Asignación</span>
    </button>
  </div>

  @if(session('ok'))
    <div class="alert-corp ok">
      <i class="bi bi-check-circle-fill"></i>
      {{ session('ok') }}
    </div>
  @endif

  @if(session('bad'))
    <div class="alert-corp bad">
      <i class="bi bi-exclamation-triangle-fill"></i>
      {{ session('bad') }}
    </div>
  @endif

  <div class="search-wrap">
    <div class="search-box">
      <i class="bi bi-search"></i>
      <input type="text" id="assignmentSearch" class="form-control" placeholder="Buscar por activo, usuario o email...">
    </div>
  </div>

  <div class="table-card">
    <div class="table-responsive">
      <table class="table table-corporate align-middle" id="assignmentsTable">
        <thead>
          <tr>
            <th>Activo</th>
            <th>Asignado a</th>
            <th>Cant.</th>
            <th>Fecha de Asignación</th>
            <th>Estado</th>
            <th class="text-end">Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse($assignments as $assignment)
            <tr class="assignment-row"
                data-search="{{ strtolower(($assignment->item->name ?? '').' '.($assignment->user->name ?? '').' '.($assignment->user->email ?? '')) }}">
              <td>
                <div class="asset-name">{{ $assignment->item->name ?? 'Activo eliminado' }}</div>
              </td>
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
                  <a href="{{ route('assets.assignments.pdf', $assignment->id) }}" target="_blank" class="action-btn" title="Ver Documento PDF">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </a>

                  @if($assignment->status === 'activa')
                    <button
                      type="button"
                      class="action-btn"
                      title="Procesar Devolución"
                      data-bs-toggle="modal"
                      data-bs-target="#returnModal"
                      data-assignment-id="{{ $assignment->id }}"
                      data-item-name="{{ $assignment->item->name ?? 'Activo' }}"
                    >
                      <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-5">
                <div class="text-muted mb-2"><i class="bi bi-inbox fs-2"></i></div>
                <span class="text-muted fw-medium">No hay asignaciones registradas en el sistema.</span>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- MODAL NUEVA ASIGNACIÓN --}}
<div class="modal fade modal-corp" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" method="POST" action="{{ route('assets.assignments.store') }}" id="assignForm">
      @csrf

      <div class="modal-header d-flex justify-content-between align-items-center">
        <h4 class="modal-title-text">Registrar Nueva Asignación</h4>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="row g-3 mb-3">
          <div class="col-md-8">
            <label class="form-label">Activo a asignar <span class="text-danger">*</span></label>
            <select class="form-select" name="inventory_item_id" required>
              <option value="" disabled selected>Seleccione un activo del inventario</option>
              @foreach($items as $item)
                <option value="{{ $item->id }}">
                  {{ $item->name }} — Stock disponible: {{ $item->stock }} {{ $item->unit ?? 'pzas' }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Cantidad <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="quantity" min="1" value="1" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Colaborador <span class="text-danger">*</span></label>
          <select class="form-select" name="user_id" required>
            <option value="" disabled selected>Seleccione el usuario responsable</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
            @endforeach
          </select>
        </div>

        <div class="mb-4">
          <label class="form-label">Observaciones Adicionales</label>
          <textarea class="form-control" name="notes" rows="2" placeholder="Ej. Incluye cargador original y funda..."></textarea>
        </div>

        <div>
          <div class="toolbar-sig">
            <label class="form-label mb-0">Firma de conformidad <span class="text-danger">*</span></label>
            <button type="button" class="clear-btn" id="clearSignatureBtn">
              <i class="bi bi-eraser me-1"></i> Borrar firma
            </button>
          </div>

          <div class="sig-container">
            <canvas id="sigCanvas"></canvas>
          </div>
          <div class="text-xs mt-1 text-end">El colaborador debe firmar en el recuadro superior</div>
          <input type="hidden" name="signature" id="signatureInput">
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-corp-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-corp-primary">Guardar Asignación</button>
      </div>
    </form>
  </div>
</div>

{{-- MODAL DEVOLUCIÓN --}}
<div class="modal fade modal-corp" id="returnModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <form class="modal-content" method="POST" id="returnForm">
      @csrf

      <div class="modal-header d-flex justify-content-between align-items-center">
        <div>
          <h4 class="modal-title-text">Procesar Devolución</h4>
          <div class="text-muted small mt-1" id="returnItemLabel">Activo seleccionado</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Motivo de la devolución <span class="text-danger">*</span></label>
          <textarea class="form-control" name="return_reason" rows="2" required placeholder="Ej. Fin de contrato, reemplazo de equipo..."></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label">Estado físico y accesorios <span class="text-danger">*</span></label>
          <textarea class="form-control" name="return_details" rows="2" required placeholder="Describa si el equipo presenta desgaste, fallas o si faltan accesorios..."></textarea>
        </div>

        <div>
          <label class="form-label">Condición final <span class="text-danger">*</span></label>
          <select class="form-select" name="return_condition" required>
            <option value="" disabled selected>Seleccione la condición general</option>
            <option value="excelente">Excelente (Como nuevo)</option>
            <option value="bueno">Bueno (Desgaste normal)</option>
            <option value="regular">Regular (Desgaste notorio)</option>
            <option value="malo">Malo (Requiere mantenimiento)</option>
            <option value="dañado">Dañado (Inoperativo)</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-corp-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn-corp-primary">Confirmar Devolución</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Buscador
  const assignmentSearch = document.getElementById('assignmentSearch');
  const assignmentRows = document.querySelectorAll('.assignment-row');

  assignmentSearch?.addEventListener('input', function () {
    const q = (this.value || '').toLowerCase().trim();

    assignmentRows.forEach(row => {
      const text = (row.dataset.search || '').toLowerCase();
      row.style.display = !q || text.includes(q) ? '' : 'none';
    });
  });

  // Firma
  const canvas = document.getElementById('sigCanvas');
  const signatureInput = document.getElementById('signatureInput');
  const clearSignatureBtn = document.getElementById('clearSignatureBtn');
  const assignModal = document.getElementById('assignModal');
  const assignForm = document.getElementById('assignForm');

  const ctx = canvas.getContext('2d');
  let drawing = false;

  function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();

    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.scale(ratio, ratio);

    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = '#0f172a'; /* Azul muy oscuro para la tinta */
  }

  function getPoint(e) {
    const rect = canvas.getBoundingClientRect();

    if (e.touches && e.touches[0]) {
      return {
        x: e.touches[0].clientX - rect.left,
        y: e.touches[0].clientY - rect.top
      };
    }

    return {
      x: e.clientX - rect.left,
      y: e.clientY - rect.top
    };
  }

  function startDraw(e) {
    drawing = true;
    const p = getPoint(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
  }

  function draw(e) {
    if (!drawing) return;
    e.preventDefault();
    const p = getPoint(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
  }

  function endDraw() {
    drawing = false;
  }

  canvas.addEventListener('mousedown', startDraw);
  canvas.addEventListener('mousemove', draw);
  window.addEventListener('mouseup', endDraw);

  canvas.addEventListener('touchstart', startDraw, { passive: false });
  canvas.addEventListener('touchmove', draw, { passive: false });
  window.addEventListener('touchend', endDraw);

  clearSignatureBtn.addEventListener('click', function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    signatureInput.value = '';
  });

  assignModal.addEventListener('shown.bs.modal', function () {
    resizeCanvas();
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    signatureInput.value = '';
  });

  window.addEventListener('resize', function () {
    if (assignModal.classList.contains('show')) {
      resizeCanvas();
    }
  });

  assignForm.addEventListener('submit', function (e) {
    const img = canvas.toDataURL('image/png');

    // Validación básica para evitar firmas vacías
    if (!img || img.length < 2000) {
      e.preventDefault();
      alert('Por favor, capture la firma del colaborador antes de guardar la asignación.');
      return;
    }

    signatureInput.value = img;
  });

  // Modal devolver
  const returnModal = document.getElementById('returnModal');
  const returnForm = document.getElementById('returnForm');
  const returnItemLabel = document.getElementById('returnItemLabel');

  returnModal.addEventListener('show.bs.modal', function (e) {
    const btn = e.relatedTarget;
    const assignmentId = btn.getAttribute('data-assignment-id');
    const itemName = btn.getAttribute('data-item-name');

    returnForm.action = `/internal-assets/assignments/${assignmentId}/return`;
    returnItemLabel.textContent = itemName;
  });
</script>
@endsection