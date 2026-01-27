@extends('layouts.app')

@section('title','Documentación confidencial para altas')

@section('content')
<div class="alta-wrap">
  {{-- Header --}}
  <div class="alta-header">
    <div>
      <h1 class="alta-title">Documentación para altas</h1>
      <p class="alta-sub">
        Gestión de contratos, formatos y políticas protegidas por NIP.
      </p>
    </div>

    <div class="alta-actions">
      <form action="{{ route('secure.alta-docs.logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-ghost">
          <span class="ico" aria-hidden="true">
            <svg viewBox="0 0 24 24">
              <path d="M7 10V7a5 5 0 0 1 10 0v3"/>
              <rect x="5" y="10" width="14" height="11" rx="2"/>
            </svg>
          </span>
          Cerrar sesión
        </button>
      </form>
    </div>
  </div>

  {{-- Mensajes --}}
  @if(session('ok'))
    <div class="alert alert-ok">
      {{ session('ok') }}
    </div>
  @endif
  @if(session('error'))
    <div class="alert alert-error">
      {{ session('error') }}
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-error">
      @foreach($errors->all() as $e)
        <div>{{ $e }}</div>
      @endforeach
    </div>
  @endif

  {{-- Barra de acciones --}}
  <div class="alta-card alta-card-upload">
    <div class="alta-card-head">
      <div>
        <h2 class="alta-card-title">Documentos de altas</h2>
        <p class="alta-card-sub-muted">
          Archivos: PDF, Word, Excel, CSV, XML, TXT · Máx. 20 MB.
        </p>
      </div>
      <div class="alta-card-cta">
        <button type="button" class="btn btn-soft-primary" data-modal-target="upload-modal">
          <span class="ico" aria-hidden="true">
            <svg viewBox="0 0 24 24">
              <path d="M4 4h16v16H4z"/>
              <path d="M8 12h8"/>
              <path d="M12 8v8"/>
            </svg>
          </span>
          Subir documento
        </button>
      </div>
    </div>
  </div>

  {{-- Card listado --}}
  <div class="alta-card alta-card-list">
    <div class="alta-card-head">
      <div>
        <h2 class="alta-card-title">Historial</h2>
        <p class="alta-card-sub">
          Solo usuarios con NIP correcto pueden ver y descargar estos archivos.
        </p>
      </div>
    </div>

    @if($docs->isEmpty())
      <div class="alta-empty">
        <div class="alta-empty-icon">
          <svg viewBox="0 0 24 24">
            <rect x="4" y="4" width="16" height="16" rx="2"/>
            <path d="M9 9h6M9 13h4"/>
          </svg>
        </div>
        <div class="alta-empty-text">
          Aún no hay documentos. Sube el primero con el botón “Subir documento”.
        </div>
      </div>
    @else
      <div class="alta-table-wrap">
        <table class="alta-table">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Nombre</th>
              <th>Tamaño</th>
              <th>Notas</th>
              <th>Subido por</th>
              <th>Fecha</th>
              <th class="right">Acciones</th>
            </tr>
          </thead>
          <tbody>
          @foreach($docs as $doc)
            <tr>
              <td>
                <span class="tag">{{ $doc->friendly_type }}</span>
              </td>
              <td class="doc-name">
                {{ $doc->original_name }}
              </td>
              <td>{{ $doc->human_size }}</td>
              <td class="doc-notes">
                {{ $doc->notes ?: '—' }}
              </td>
              <td>
                {{ optional($doc->uploadedBy)->name ?? '—' }}
              </td>
              <td>
                {{ optional($doc->created_at)->format('d/m/Y H:i') }}
              </td>
              <td class="right">
                <div class="alta-row-actions">
                  {{-- Previsualizar --}}
                  <button
                    type="button"
                    class="btn btn-ghost btn-xs js-preview-doc"
                    data-name="{{ $doc->original_name }}"
                    data-url="{{ route('alta.docs.preview', $doc) }}"
                    data-download="{{ route('alta.docs.download', $doc) }}"
                  >
                    <span class="ico" aria-hidden="true">
                      <svg viewBox="0 0 24 24">
                        <path d="M2 12s3-6 10-6 10 6 10 6-3 6-10 6S2 12 2 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                      </svg>
                    </span>
                    Ver
                  </button>

                  {{-- Descargar --}}
                  <a href="{{ route('alta.docs.download', $doc) }}" class="btn btn-ghost btn-xs">
                    <span class="ico" aria-hidden="true">
                      <svg viewBox="0 0 24 24">
                        <path d="M12 4v12"/>
                        <path d="M8 12l4 4 4-4"/>
                        <rect x="4" y="18" width="16" height="2" rx="1"/>
                      </svg>
                    </span>
                    Descargar
                  </a>

                  {{-- Eliminar --}}
                  <form action="{{ route('alta.docs.destroy', $doc) }}" method="POST" onsubmit="return confirm('¿Eliminar este documento?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-xs">
                      <span class="ico" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                          <path d="M4 7h16"/>
                          <path d="M10 11v6"/>
                          <path d="M14 11v6"/>
                          <path d="M6 7l1 12a1 1 0 0 0 1 .9h8a1 1 0 0 0 1-.9L18 7"/>
                          <path d="M9 7V4h6v3"/>
                        </svg>
                      </span>
                      Eliminar
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

      <div class="alta-pager">
        {{ $docs->links() }}
      </div>
    @endif
  </div>
</div>

{{-- FAB en móvil para subir (abre el mismo modal) --}}
<button type="button" class="alta-fab" data-modal-target="upload-modal">
  <span class="alta-fab-ico" aria-hidden="true">
    <svg viewBox="0 0 24 24">
      <path d="M4 4h16v16H4z"/>
      <path d="M8 12h8"/>
      <path d="M12 8v8"/>
    </svg>
  </span>
  <span class="alta-fab-label">Subir</span>
</button>

{{-- MODAL: Subir documentación --}}
<div id="upload-modal" class="modal" aria-hidden="true">
  <div class="modal__overlay" data-modal-close></div>
  <div class="modal__panel">
    <div class="modal__header">
      <h2 class="modal__title">Subir documento</h2>
      <button type="button" class="modal__close" data-modal-close>
        <svg viewBox="0 0 24 24">
          <path d="M6 6l12 12M18 6L6 18"/>
        </svg>
      </button>
    </div>

    <div class="modal__body">
      <form action="{{ route('alta.docs.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div id="dropzone" class="alta-dropzone">
          <div class="alta-drop-icon">
            <svg viewBox="0 0 24 24">
              <path d="M12 16V4"/>
              <path d="M8 8l4-4 4 4"/>
              <rect x="4" y="14" width="16" height="6" rx="2"/>
            </svg>
          </div>
          <div class="alta-drop-body">
            <div class="alta-drop-title">Arrastra o haz clic para escoger</div>
            <div class="alta-drop-hint">
              Puedes subir uno o varios archivos.
            </div>
          </div>
          <input
            id="files_input"
            name="files[]"
            type="file"
            multiple
            class="alta-drop-input"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.xml,.txt"
          >
        </div>

        <div id="files_chips" class="alta-files-chips"></div>

        <div class="alta-notes-row">
          <label class="lbl">Notas (opcional)</label>
          <input
            type="text"
            name="notes"
            class="inp"
            maxlength="500"
            placeholder="Ej: Alta de nuevos proveedores"
            value="{{ old('notes') }}"
          >
        </div>

        <div class="modal__footer">
          <button type="button" class="btn btn-soft-muted" data-modal-close>Cancelar</button>
          <button type="submit" class="btn btn-soft-primary">
            Subir
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- MODAL: Previsualizar documento --}}
<div id="preview-modal" class="modal" aria-hidden="true">
  <div class="modal__overlay" data-modal-close></div>
  <div class="modal__panel modal__panel--wide">
    <div class="modal__header">
      <h2 class="modal__title" id="preview-title">Documento</h2>
      <button type="button" class="modal__close" data-modal-close>
        <svg viewBox="0 0 24 24">
          <path d="M6 6l12 12M18 6L6 18"/>
        </svg>
      </button>
    </div>

    <div class="modal__body modal-preview-body">
      <iframe
        id="preview-frame"
        class="modal-preview-frame"
        src=""
        title="Previsualización de documento"
      ></iframe>

      <div id="preview-fallback" class="modal-preview-fallback">
        Este tipo de archivo no se puede previsualizar aquí. Ábrelo o descárgalo directamente.
      </div>
    </div>

    <div class="modal__footer modal-preview-footer">
      <a id="preview-open-tab" href="#" target="_blank" class="btn btn-soft-muted">
        Abrir en pestaña nueva
      </a>
      <a id="preview-download" href="#" class="btn btn-soft-primary">
        Descargar
      </a>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  :root{
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e2e8f0;
    --bg-soft:#f8fafc;
    --brand:#2563eb;
    --brand-soft:#eff6ff;
    --danger:#dc2626;
    --radius-lg:18px;
    --radius-md:12px;
    --shadow-soft:0 18px 40px rgba(15,23,42,.06);
  }

  .alta-wrap{
    max-width:1100px;
    margin:18px auto;
    padding:0 14px 24px;
  }

  .alta-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:12px;
  }
  .alta-title{
    margin:0;
    font-size:1.45rem;
    font-weight:800;
    letter-spacing:-.02em;
    color:var(--ink);
  }
  .alta-sub{
    margin:4px 0 0;
    font-size:.9rem;
    color:var(--muted);
  }
  .alta-actions{
    display:flex;
    gap:8px;
    align-items:center;
  }

  .btn{
    border:0;
    border-radius:999px;
    padding:8px 14px;
    font-weight:600;
    cursor:pointer;
    font-size:.85rem;
    display:inline-flex;
    align-items:center;
    gap:6px;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease;
    white-space:nowrap;
    text-decoration:none;
  }
  .btn-primary{
    background:var(--brand);
    color:#eff6ff;
    box-shadow:0 12px 28px rgba(37,99,235,.35);
  }
  .btn-primary:hover{
    transform:translateY(-1px);
    box-shadow:0 16px 36px rgba(37,99,235,.4);
  }
  .btn-ghost{
    background:#ffffff;
    border:1px solid var(--line);
    color:var(--ink);
  }
  .btn-ghost:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 24px rgba(15,23,42,.06);
  }
  .btn-danger{
    background:#fee2e2;
    border:1px solid #fecaca;
    color:#b91c1c;
  }
  .btn-danger:hover{
    background:#fecaca;
    box-shadow:0 10px 24px rgba(248,113,113,.35);
    transform:translateY(-1px);
  }
  .btn-xs{
    padding:5px 10px;
    font-size:.76rem;
  }

  /* Botones pastel */
  .btn-soft-primary{
    background:#e0f2fe;
    color:#1d4ed8;
    border:1px solid #bfdbfe;
    box-shadow:none;
  }
  .btn-soft-primary:hover{
    background:#dbeafe;
    transform:translateY(-1px);
  }

  .btn-soft-muted{
    background:#f9fafb;
    color:#4b5563;
    border:1px solid #e5e7eb;
  }
  .btn-soft-muted:hover{
    background:#f3f4f6;
    transform:translateY(-1px);
  }

  .ico svg{
    width:16px;
    height:16px;
    stroke:currentColor;
    stroke-width:1.7;
    fill:none;
    stroke-linecap:round;
    stroke-linejoin:round;
  }

  .alert{
    border-radius:var(--radius-md);
    padding:8px 10px;
    font-size:.82rem;
    margin-bottom:10px;
  }
  .alert-ok{
    background:#dcfce7;
    color:#166534;
    border:1px solid #bbf7d0;
  }
  .alert-error{
    background:#fef2f2;
    color:#991b1b;
    border:1px solid #fecaca;
  }

  .alta-card{
    border-radius:var(--radius-lg);
    border:1px solid var(--line);
    background:#ffffff;
    box-shadow:var(--shadow-soft);
    padding:14px 14px 16px;
    margin-bottom:14px;
  }
  .alta-card-upload{
    background:#ffffff;
  }

  .alta-card-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:8px;
    margin-bottom:4px;
  }
  .alta-card-title{
    margin:0;
    font-size:1rem;
    font-weight:800;
    color:#0f172a;
  }
  .alta-card-sub{
    margin:2px 0 0;
    font-size:.82rem;
    color:#475569;
  }
  .alta-card-sub-muted{
    margin:2px 0 0;
    font-size:.8rem;
    color:#6b7280;
  }
  .alta-card-cta{
    flex-shrink:0;
  }

  .lbl{
    display:block;
    font-weight:700;
    color:var(--ink);
    margin:10px 0 4px;
    font-size:.85rem;
  }
  .inp{
    width:100%;
    border-radius:var(--radius-md);
    border:1px solid var(--line);
    background:#f8fafc;
    padding:8px 10px;
    font-size:.85rem;
    color:var(--ink);
    min-height:38px;
    transition:border-color .15s ease, box-shadow .15s ease, background .15s ease, transform .08s ease;
  }
  .inp:focus{
    outline:none;
    border-color:#93c5fd;
    box-shadow:0 0 0 1px #bfdbfe;
    background:#ffffff;
    transform:translateY(-1px);
  }

  /* Dropzone */
  .alta-dropzone{
    position:relative;
    border-radius:18px;
    border:1.5px dashed rgba(148,163,184,.9);
    background:linear-gradient(135deg, rgba(249,250,251,1), #ffffff);
    padding:12px;
    display:flex;
    align-items:center;
    gap:12px;
    cursor:pointer;
    transition:border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .1s ease;
  }
  .alta-dropzone:hover{
    border-color:#60a5fa;
    box-shadow:0 10px 24px rgba(37,99,235,.12);
    transform:translateY(-1px);
  }
  .alta-drop-icon{
    width:40px;
    height:40px;
    border-radius:999px;
    background:#1d4ed8;
    display:flex;
    align-items:center;
    justify-content:center;
    box-shadow:0 10px 22px rgba(30,64,175,.5);
    flex:0 0 auto;
  }
  .alta-drop-icon svg{
    width:20px;
    height:20px;
    stroke:#e0f2fe;
    stroke-width:1.7;
    fill:none;
    stroke-linecap:round;
    stroke-linejoin:round;
  }
  .alta-drop-body{
    display:flex;
    flex-direction:column;
    gap:2px;
  }
  .alta-drop-title{
    font-size:.9rem;
    font-weight:700;
    color:#0f172a;
  }
  .alta-drop-hint{
    font-size:.8rem;
    color:#6b7280;
  }

  /* input invisible ocupando toda la zona -> clic abre explorador, sin JS extra */
  .alta-drop-input{
    position:absolute;
    inset:0;
    opacity:0;
    cursor:pointer;
  }

  .alta-files-chips{
    margin-top:6px;
    display:flex;
    flex-wrap:wrap;
    gap:6px;
  }
  .alta-file-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:3px 8px;
    border-radius:999px;
    background:#eff6ff;
    border:1px solid #dbeafe;
    font-size:.75rem;
    color:#1e293b;
    max-width:100%;
  }
  .alta-file-chip span{
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:180px;
  }

  .alta-notes-row{
    margin-top:10px;
  }

  /* Tabla documentos */
  .alta-table-wrap{
    width:100%;
    overflow:auto;
    border-radius:16px;
    border:1px solid var(--line);
    background:#ffffff;
  }
  .alta-table{
    width:100%;
    border-collapse:collapse;
    font-size:.82rem;
  }
  .alta-table thead{
    background:#f1f5f9;
  }
  .alta-table th,
  .alta-table td{
    padding:8px 10px;
    border-bottom:1px solid #e5e7eb;
    text-align:left;
    vertical-align:middle;
  }
  .alta-table th{
    font-weight:700;
    color:#0f172a;
    white-space:nowrap;
  }
  .alta-table td{
    color:#4b5563;
  }
  .alta-table tr:hover{
    background:#f9fafb;
  }
  .doc-name{
    max-width:260px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .doc-notes{
    max-width:240px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }
  .tag{
    display:inline-flex;
    align-items:center;
    padding:3px 8px;
    border-radius:999px;
    border:1px solid #dbeafe;
    background:#eff6ff;
    font-size:.72rem;
    font-weight:600;
    color:#1d4ed8;
  }
  .right{
    text-align:right;
  }

  .alta-row-actions{
    display:flex;
    justify-content:flex-end;
    gap:6px;
    flex-wrap:wrap;
  }

  .alta-pager{
    margin-top:10px;
    display:flex;
    justify-content:flex-end;
  }

  .alta-empty{
    padding:14px 10px;
    display:flex;
    align-items:center;
    gap:10px;
    color:#6b7280;
    font-size:.85rem;
  }
  .alta-empty-icon{
    width:36px;
    height:36px;
    border-radius:999px;
    background:#f1f5f9;
    display:flex;
    align-items:center;
    justify-content:center;
    flex:0 0 auto;
  }
  .alta-empty-icon svg{
    width:20px;
    height:20px;
    stroke:#9ca3af;
    stroke-width:1.7;
    fill:none;
    stroke-linecap:round;
    stroke-linejoin:round;
  }

  /* MODALES */
  .modal{
    position:fixed;
    inset:0;
    display:none;
    align-items:center;
    justify-content:center;
    z-index:60;
  }
  .modal.is-open{
    display:flex;
  }
  .modal__overlay{
    position:absolute;
    inset:0;
    background:rgba(15,23,42,.45);
    backdrop-filter:blur(6px);
  }
  .modal__panel{
    position:relative;
    z-index:1;
    width:100%;
    max-width:520px;
    max-height:90vh;
    border-radius:20px;
    background:#ffffff;
    box-shadow:0 20px 60px rgba(15,23,42,.35);
    padding:12px 14px 12px;
    display:flex;
    flex-direction:column;
    transform:translateY(8px);
    animation:modal-in .18s ease-out;
  }
  .modal__panel--wide{
    max-width:960px;
  }

  .modal__header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:8px;
    padding:2px 2px 10px;
    border-bottom:1px solid #e5e7eb;
  }
  .modal__title{
    margin:0;
    font-size:1rem;
    font-weight:800;
    color:#0f172a;
  }
  .modal__close{
    border:0;
    border-radius:999px;
    width:30px;
    height:30px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:#f3f4f6;
    cursor:pointer;
  }
  .modal__close svg{
    width:16px;
    height:16px;
    stroke:#374151;
    stroke-width:1.7;
    fill:none;
    stroke-linecap:round;
    stroke-linejoin:round;
  }

  .modal__body{
    padding:10px 2px 10px;
    overflow:auto;
  }

  .modal__footer{
    padding:8px 2px 2px;
    display:flex;
    justify-content:flex-end;
    gap:8px;
    border-top:1px solid #e5e7eb;
    margin-top:6px;
  }

  @keyframes modal-in{
    from{opacity:0; transform:translateY(16px);}
    to{opacity:1; transform:translateY(8px);}
  }

  /* PREVIEW */
  .modal-preview-body{
    padding:10px 0 6px;
  }
  .modal-preview-frame{
    width:100%;
    height:60vh;
    border:1px solid #e5e7eb;
    border-radius:14px;
    background:#f9fafb;
    display:none;
  }
  .modal-preview-fallback{
    display:none;
    font-size:.85rem;
    color:#6b7280;
    padding:10px 8px;
    border-radius:14px;
    background:#f9fafb;
    border:1px dashed #d1d5db;
  }
  .modal-preview-footer{
    justify-content:space-between;
  }

  /* FAB */
  .alta-fab{
    position:fixed;
    right:16px;
    bottom:16px;
    border-radius:999px;
    padding:8px 14px;
    background:#0f172a;
    color:#f9fafb;
    display:none;
    align-items:center;
    gap:8px;
    box-shadow:0 18px 40px rgba(15,23,42,.45);
    border:0;
    cursor:pointer;
    z-index:55;
  }
  .alta-fab-ico{
    width:32px;
    height:32px;
    border-radius:999px;
    background:#111827;
    display:flex;
    align-items:center;
    justify-content:center;
  }
  .alta-fab-ico svg{
    width:18px;
    height:18px;
    stroke:#e5e7eb;
    stroke-width:1.7;
    fill:none;
    stroke-linecap:round;
    stroke-linejoin:round;
  }
  .alta-fab-label{
    font-size:.85rem;
    font-weight:600;
  }

  /* Responsivo */
  @media (max-width: 900px){
    .alta-header{
      align-items:flex-start;
    }
    .alta-row-actions{
      justify-content:flex-start;
    }
    .alta-table th:nth-child(4),
    .alta-table td:nth-child(4){
      display:none;
    }
  }

  @media (max-width: 640px){
    .alta-table th:nth-child(5),
    .alta-table td:nth-child(5){
      display:none;
    }

    .modal{
      align-items:flex-end;
    }
    .modal__panel{
      max-width:100%;
      border-radius:18px 18px 0 0;
      max-height:82vh;
      margin:0 0 env(safe-area-inset-bottom,0);
    }

    .alta-fab{
      display:inline-flex;
    }
  }
</style>
@endpush

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    /*** MODALES ***/
    const body = document.body;

    function openModal(id){
      const modal = document.getElementById(id);
      if(!modal) return;
      modal.classList.add('is-open');
      body.style.overflow = 'hidden';
    }
    function closeModal(modal){
      if(!modal) return;
      modal.classList.remove('is-open');
      body.style.overflow = '';
      if(modal.id === 'preview-modal'){
        const frame = document.getElementById('preview-frame');
        const fallback = document.getElementById('preview-fallback');
        if(frame){ frame.src = ''; frame.style.display = 'none'; }
        if(fallback){ fallback.style.display = 'none'; }
      }
    }

    // Abrir modal
    document.querySelectorAll('[data-modal-target]').forEach(btn => {
      btn.addEventListener('click', () => {
        const target = btn.getAttribute('data-modal-target');
        if(target) openModal(target);
      });
    });

    // Cerrar modal
    document.addEventListener('click', (e) => {
      if(e.target.closest('[data-modal-close]')){
        const modal = e.target.closest('.modal') || document.querySelector('.modal.is-open');
        if(modal) closeModal(modal);
      }
    });

    // ESC para cerrar
    document.addEventListener('keydown', (e) => {
      if(e.key === 'Escape'){
        const modal = document.querySelector('.modal.is-open');
        if(modal) closeModal(modal);
      }
    });

    /*** DROPZONE SUBIDA ***/
    const input    = document.getElementById('files_input');
    const chipsBox = document.getElementById('files_chips');

    function refreshChips(files) {
      if (!chipsBox) return;
      chipsBox.innerHTML = '';
      if (!files || !files.length) return;

      Array.from(files).forEach(file => {
        const chip = document.createElement('div');
        chip.className = 'alta-file-chip';
        const sizeKb = Math.round(file.size / 1024);
        chip.innerHTML = `<span>${file.name}</span><small>${sizeKb} KB</small>`;
        chipsBox.appendChild(chip);
      });
    }

    if (input) {
      input.addEventListener('change', function () {
        refreshChips(input.files);
      });
    }

    /*** PREVIEW DOCUMENTOS ***/
    const previewButtons = document.querySelectorAll('.js-preview-doc');
    const previewTitle   = document.getElementById('preview-title');
    const previewFrame   = document.getElementById('preview-frame');
    const previewFallback= document.getElementById('preview-fallback');
    const openTabLink    = document.getElementById('preview-open-tab');
    const downloadLink   = document.getElementById('preview-download');

    if (previewFrame) {
      previewFrame.addEventListener('load', () => {
        previewFrame.style.display = 'block';
        if(previewFallback) previewFallback.style.display = 'none';
      });
    }

    previewButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const name        = btn.dataset.name || 'Documento';
        const url         = btn.dataset.url || '#';
        const downloadUrl = btn.dataset.download || url;

        if(previewTitle) previewTitle.textContent = name;
        if(openTabLink) openTabLink.href = url;
        if(downloadLink) downloadLink.href = downloadUrl;

        const ext = (name.split('.').pop() || '').toLowerCase();
        const canEmbed = ['pdf','png','jpg','jpeg','gif','webp'].includes(ext);

        if(canEmbed && previewFrame){
          previewFrame.style.display = 'none';
          previewFrame.src = url;
        } else {
          if(previewFrame) {
            previewFrame.style.display = 'none';
            previewFrame.src = '';
          }
          if(previewFallback) previewFallback.style.display = 'block';
        }

        openModal('preview-modal');
      });
    });
  });
</script>
@endpush
