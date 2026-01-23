{{-- resources/views/manual_invoices/index.blade.php --}}
@extends('layouts.app')
@section('title','Facturas')
@section('header','Facturas')

@push('styles')
<style>
  
:root{
  --btn-blue:#2563eb; --btn-blue-h:#1d4ed8; --btn-blue-soft:#e6efff;
  --btn-green:#059669; --btn-green-h:#047857; --btn-green-soft:#e6fff4;
  --btn-gray:#64748b; --btn-gray-h:#475569; --btn-gray-soft:#eef2f7;
  --btn-red:#ef4444; --btn-red-h:#dc2626; --btn-red-soft:#ffe9eb;

  --surface:#ffffff; --border:#e5e7eb; --muted:#6b7280;
}
.page{ max-width:1200px; margin:12px auto 24px; padding:0 14px }

/* Hero */
.hero{
  display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
  background: radial-gradient(800px 120px at 10% 0%, rgba(59,130,246,.10), transparent 60%),
              radial-gradient(800px 120px at 100% 0%, rgba(14,165,233,.09), transparent 60%),
              var(--surface);
  border:1px solid var(--border); border-radius:18px; padding:12px 14px;
}
.hero h1{ margin:0; font-weight:800; letter-spacing:-.02em }
.subtle{ color:var(--muted); font-size:.86rem }

/* Icon button (no text) */
.ibtn{
  width:40px; height:40px;
  display:inline-grid;
  place-items:center;
  border-radius:999px;
  border:1px solid var(--border);
  background:#fff;
  color:#111827;
  text-decoration:none;
  cursor:pointer;
  transition:transform .12s ease, box-shadow .12s ease, background-color .12s ease, border-color .12s ease;
}
.ibtn:hover{
  transform:translateY(-1px);
  box-shadow:0 10px 22px rgba(29,78,216,.10);
  background:#f8fafc;
}
.ibtn:active{ transform:translateY(0) }

.ibtn.blue{ background:var(--btn-blue-soft); color:var(--btn-blue); border-color:#cfe0ff }
.ibtn.blue:hover{ background:#dbeafe; color:var(--btn-blue-h) }

.ibtn.green{ background:var(--btn-green-soft); color:var(--btn-green); border-color:#b7f7db }
.ibtn.green:hover{ background:#d1fae5; color:var(--btn-green-h) }

.ibtn.gray{ background:var(--btn-gray-soft); color:var(--btn-gray); border-color:#e5e7eb }
.ibtn.gray:hover{ background:#e2e8f0; color:var(--btn-gray-h) }

.ibtn.red{ background:var(--btn-red-soft); color:var(--btn-red); border-color:#fecaca }
.ibtn.red:hover{ background:#fee2e2; color:var(--btn-red-h) }

/* Tooltip (como catalogitems) */
.tipwrap{ position:relative; display:inline-block; }
.tipwrap .tip{
  position:absolute;
  left:50%;
  bottom:calc(100% + 10px);
  transform:translateX(-50%);
  background:#0f172a;
  color:#fff;
  padding:7px 10px;
  border-radius:10px;
  font-size:.75rem;
  font-weight:800;
  letter-spacing:.01em;
  white-space:nowrap;
  opacity:0;
  visibility:hidden;
  pointer-events:none;
  transition:opacity .12s ease, transform .12s ease, visibility .12s ease;
  box-shadow:0 18px 40px rgba(2,6,23,.25);
  z-index:60;
}
.tipwrap .tip::after{
  content:"";
  position:absolute;
  top:100%;
  left:50%;
  transform:translateX(-50%);
  width:0;height:0;
  border-left:7px solid transparent;
  border-right:7px solid transparent;
  border-top:7px solid #0f172a;
}
.tipwrap:hover .tip,
.tipwrap:focus-within .tip{
  opacity:1;
  visibility:visible;
  transform:translateX(-50%) translateY(-2px);
}

/* Searchbar */
.searchbar{
  display:flex; align-items:center; gap:8px; background:#fff; height:42px; border-radius:999px; padding:0 10px 0 12px;
  border:1px solid #cfe0ff; box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 6px 14px rgba(29,78,216,.10);
  min-width:260px; max-width:min(70vw, 520px)
}
.sb-icon{ width:24px; display:grid; place-items:center; color:#94a3b8 }
.sb-input{ flex:1; border:0; outline:none; background:transparent; font-size:.86rem }
.sb-clear{
  border:0; background:transparent; color:#94a3b8;
  width:28px; height:28px; border-radius:50%;
  display:grid; place-items:center; cursor:pointer; visibility:hidden
}
.sb-clear:hover{ background:#f1f5f9; color:#64748b }

/* Tabs */
.tabs{ margin-top:14px; display:flex; flex-wrap:wrap; gap:8px; }
.tab-pill{
  border-radius:999px; padding:6px 12px; font-size:.8rem;
  border:1px solid var(--border); background:#f8fafc; color:#64748b;
  text-decoration:none; font-weight:700;
}
.tab-pill.active{
  background:#e6efff; border-color:#bfdbfe; color:#1d4ed8;
}

/* Tabla */
.table-wrap{
  margin-top:14px;
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:16px;
  overflow:visible;
}

table{
  width:100%;
  min-width: 100%;
  border-collapse:separate;
  border-spacing:0;
  table-layout: fixed;
}

thead th{
  background:#f7faff; color:#334155; text-align:left; font-weight:900;
  border-bottom:1px solid var(--border); padding:12px 12px; white-space:nowrap;
  font-size:.75rem; text-transform:uppercase; letter-spacing:.04em;
}
tbody td{ padding:11px 12px; border-bottom:1px solid var(--border); vertical-align:middle; font-size:.84rem }
tbody tr:hover{ background:#f8fbff }

/* Acciones no sticky */
th.th-actions, td.t-actions{
  position:static;
  right:auto;
  background:transparent;
  z-index:auto;
  border-left:0;
}

/* Badges */
.badge-type{
  padding:.18rem .55rem; border-radius:999px; font-weight:900; font-size:.72rem;
  background:#eef2f7; color:#334155; border:1px solid #e5e7eb;
}
.badge-type.i{ background:#e6efff; color:#1d4ed8; border-color:#bfdbfe }
.badge-type.e{ background:#fef3c7; color:#92400e; border-color:#fde68a }
.badge-type.p{ background:#e0f2fe; color:#0369a1; border-color:#bae6fd }

.chip-status{
  display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:4px 10px;
  font-size:.76rem; font-weight:900;
}
.chip-status .dot{ width:8px; height:8px; border-radius:999px; background:currentColor }
.chip-status.valid{ background:#ecfdf3; color:#15803d; border:1px solid #bbf7d0 }
.chip-status.draft{ background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb }
.chip-status.cancelled{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca }
.chip-status.pending_cancel{ background:#fffbeb; color:#b45309; border:1px solid #fed7aa }

/* Cliente */
.cell-client{ max-width:260px; overflow:hidden; text-overflow:ellipsis; }
.cell-client .name{ font-weight:900; }
.cell-client .rfc{ font-size:.78rem; color:#6b7280; }

/* Importe link */
.cell-amount a{
  color:#2563eb; text-decoration:none; font-weight:900;
  font-feature-settings:"tnum" 1,"lnum" 1;
}
.cell-amount a:hover{ text-decoration:underline; }

/* Flash */
.status-flash{
  margin-top:10px; font-size:12px; padding:8px 12px; border-radius:999px;
  background:#ecfdf3; color:#047857; border:1px solid #bbf7d0;
}

/* ===== SweetAlert2: estilo minimal/vidrio ===== */
.swal2-popup.custom-swal{
  border-radius:22px !important;
  padding:18px 18px 14px !important;
  background:
    radial-gradient(circle at 0 0, rgba(59,130,246,.12), transparent 55%),
    radial-gradient(circle at 100% 0, rgba(16,185,129,.09), transparent 60%),
    #f9fafb !important;
  box-shadow:
    0 30px 70px rgba(15,23,42,.28),
    0 0 0 1px rgba(148,163,184,.55);
  backdrop-filter: blur(22px);
}
.swal2-title{
  font-size:1.05rem !important;
  font-weight:800 !important;
  letter-spacing:-.01em;
  color:#0f172a !important;
}
.swal2-html-container{
  margin-top:6px !important;
  font-size:.86rem !important;
  color:#4b5563 !important;
}
.swal2-actions{
  margin-top:16px !important;
  gap:8px !important;
}
.swal2-styled.swal2-confirm{
  border-radius:999px !important;
  padding:8px 18px !important;
  font-weight:800 !important;
  background:#2563eb !important;
  border:0 !important;
  box-shadow:0 12px 26px rgba(37,99,235,.35);
}
.swal2-styled.swal2-cancel{
  border-radius:999px !important;
  padding:8px 16px !important;
  font-weight:700 !important;
  background:#e5e7eb !important;
  color:#111827 !important;
  border:0 !important;
}
.swal2-icon{
  margin:0 0 10px !important;
  transform:scale(.8);
}

/* Responsive: apilado */
@media (max-width: 960px){
  .page{ padding:0 8px }
  .table-wrap{ border:0; background:transparent; overflow:visible }
  table, thead, tbody, th, td, tr { display:block }
  table{ min-width:0 }
  thead{ display:none }

  tbody tr{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:16px;
    padding:12px;
    margin-bottom:12px;
  }
  tbody td{ border:0; padding:6px 0; }

  td[data-label]::before{
    content: attr(data-label) ": ";
    font-size:.75rem;
    font-weight:900;
    color:#6b7280;
    text-transform:uppercase;
    letter-spacing:.04em;
    display:block;
    margin-bottom:3px;
  }

  td.t-actions{
    margin-top:8px;
    padding-top:10px !important;
    border-top:1px dashed #e5e7eb;
  }
  td.t-actions .actions-row{
    justify-content:flex-start !important;
  }
}
</style>
@endpush

@section('content')
<div class="page">

  {{-- Hero --}}
  <div class="hero">
    <div>
      <h1 class="h4">Facturas</h1>
      <div class="subtle">
        Borradores y CFDI timbrados con Facturapi, vinculados a tus clientes y productos.
      </div>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center">

      {{-- Crear (icono + tooltip) --}}
      <span class="tipwrap">
        <a href="{{ route('manual_invoices.create') }}" class="ibtn blue" aria-label="Crear factura">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"/>
          </svg>
        </a>
        <span class="tip">Crear factura</span>
      </span>

      {{-- buscador --}}
      <form method="GET" class="searchbar">
        <input type="hidden" name="status" value="{{ $status }}">
        <span class="sb-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
          </svg>
        </span>
        <input class="sb-input" id="liveSearchInput" type="text"
               name="q"
               value="{{ $q }}"
               placeholder="Buscar por cliente, RFC, folio o importe…"
               autocomplete="off">
        <button type="button" class="sb-clear" id="sbClear" aria-label="Limpiar">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 6L6 18M6 6l12 12"/>
          </svg>
        </button>
      </form>
    </div>
  </div>

  @if(session('status'))
    <div class="status-flash">
      {{ session('status') }}
    </div>
  @endif
  @if(session('error'))
    <div class="status-flash" style="background:#fef2f2;border-color:#fecaca;color:#b91c1c;">
      {{ session('error') }}
    </div>
  @endif

  {{-- Tabs estatus --}}
  @php
    $tabs = [
        'all'            => 'Todas',
        'valid'          => 'Válidas',
        'draft'          => 'Borradores',
        'cancelled'      => 'Canceladas',
        'pending_cancel' => 'Pendientes de cancelación',
    ];
  @endphp
  <div class="tabs">
    @foreach($tabs as $key => $label)
      <a href="{{ route('manual_invoices.index', ['status' => $key, 'q' => $q]) }}"
         class="tab-pill {{ $status === $key ? 'active' : '' }}">
        {{ $label }}
      </a>
    @endforeach
  </div>

  {{-- Tabla --}}
  <div class="table-wrap">
    <table id="invTable">
      <thead>
      <tr>
        <th style="width:80px;">Tipo</th>
        <th>Cliente</th>
        <th style="width:130px;">Serie/Folio</th>
        <th style="width:170px;">Fecha</th>
        <th style="width:120px;">Importe</th>
        <th style="width:150px;">Estatus</th>
        <th class="th-actions" style="width:180px;">Acciones</th>
      </tr>
      </thead>
      <tbody>
      @forelse($invoices as $inv)
        @php
          $clientName = $inv->client->nombre ?? $inv->receiver_name ?? '—';
          $clientRfc  = $inv->client->rfc ?? $inv->receiver_rfc ?? '';
          $date = $inv->stamped_at?->format('d M Y H:i') ?? $inv->created_at->format('d M Y H:i');

          $serieFolio = trim(
              ($inv->serie ? $inv->serie : '') .
              ($inv->serie && $inv->folio ? '-' : '') .
              ($inv->folio ?? '')
          );
          if ($serieFolio === '') $serieFolio = '—';

          $typeClass   = strtolower($inv->type ?? 'i');
          $statusLabel = $inv->status_label ?? ucfirst($inv->status ?? '—');
        @endphp

        <tr>
          <td data-label="Tipo">
            <span class="badge-type {{ $typeClass }}">
              @if($inv->type === 'I') I
              @elseif($inv->type === 'E') E
              @elseif($inv->type === 'P') P
              @else —
              @endif
            </span>
          </td>

          <td class="cell-client" data-label="Cliente">
            <div class="name">{{ $clientName }}</div>
            @if($clientRfc)
              <div class="rfc">{{ $clientRfc }}</div>
            @endif
          </td>

          <td data-label="Serie/Folio">{{ $serieFolio }}</td>
          <td data-label="Fecha">{{ $date }}</td>

          <td class="cell-amount" data-label="Importe">
            <a href="{{ route('manual_invoices.show', $inv) }}">
              ${{ number_format($inv->total ?? 0, 2) }}
            </a>
          </td>

          <td data-label="Estatus">
            <span class="chip-status {{ $inv->status }}">
              <span class="dot"></span>
              {{ $statusLabel }}
            </span>
          </td>

          <td class="t-actions" data-label="Acciones">
            <div class="actions-row" style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">

              {{-- Ver --}}
              <span class="tipwrap">
                <a href="{{ route('manual_invoices.show', $inv) }}" class="ibtn gray" aria-label="Ver detalle">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </a>
                <span class="tip">Ver detalle</span>
              </span>

              @if($inv->status === 'draft')
                {{-- Editar --}}
                <span class="tipwrap">
                  <a href="{{ route('manual_invoices.edit', $inv) }}" class="ibtn blue" aria-label="Editar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                  </a>
                  <span class="tip">Editar</span>
                </span>

                {{-- Timbrar con SweetAlert --}}
                <span class="tipwrap">
                  <form action="{{ route('manual_invoices.stamp', $inv) }}" method="POST" style="display:inline">
                    @csrf
                    <button type="button"
                            class="ibtn green js-timbrar"
                            data-sw-title="¿Timbrar esta factura?"
                            data-sw-text="Se timbrará el CFDI en Facturapi para {{ $clientName }}. ¿Deseas continuar?"
                            aria-label="Timbrar">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 7h16M4 12h16M4 17h16"/>
                      </svg>
                    </button>
                  </form>
                  <span class="tip">Timbrar</span>
                </span>

              @else
                @if($inv->facturapi_id)
                  {{-- PDF --}}
                  <span class="tipwrap">
                    <a href="{{ route('manual_invoices.downloadPdf', $inv) }}" class="ibtn blue" aria-label="Descargar PDF">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12"/><path d="M8 11l4 4 4-4"/><path d="M5 21h14"/>
                      </svg>
                    </a>
                    <span class="tip">Descargar PDF</span>
                  </span>

                  {{-- XML --}}
                  <span class="tipwrap">
                    <a href="{{ route('manual_invoices.downloadXml', $inv) }}" class="ibtn blue" aria-label="Descargar XML">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h6"/>
                      </svg>
                    </a>
                    <span class="tip">Descargar XML</span>
                  </span>
                @endif
              @endif

            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center; padding:18px; color:#6b7280;">
            No hay facturas para los filtros seleccionados.
          </td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>

  <div style="margin-top:10px; font-size:.8rem; color:#6b7280; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
    <span>
      Mostrando {{ $invoices->count() }} de {{ $invoices->total() }} facturas
    </span>
    <span>{{ $invoices->links() }}</span>
  </div>

</div>
@endsection

@push('scripts')
{{-- SweetAlert2 CDN (una sola vez; si ya está en tu layout, puedes quitar esta línea) --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  // Search clear
  const input    = document.getElementById('liveSearchInput');
  const clearBtn = document.getElementById('sbClear');

  if (input && clearBtn) {
    function updateClear(){
      clearBtn.style.visibility = (input.value.trim() !== '') ? 'visible' : 'hidden';
    }
    updateClear();

    clearBtn.addEventListener('click', function(){
      input.value = '';
      updateClear();
      const form = input.closest('form');
      if (form) {
        form.querySelectorAll('input[name="q"]').forEach(i => i.value = '');
        form.submit();
      }
    });

    input.addEventListener('input', updateClear);
  }

  // SweetAlert timbrar
  const timbrarBtns = document.querySelectorAll('.js-timbrar');
  if (!timbrarBtns.length) return;

  timbrarBtns.forEach(btn => {
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const form  = btn.closest('form');
      if (!form) return;

      const title = btn.dataset.swTitle || '¿Timbrar esta factura?';
      const text  = btn.dataset.swText  || 'Se timbrará el CFDI en Facturapi. ¿Deseas continuar?';

      if (typeof Swal === 'undefined') {
        if (confirm(text)) form.submit();
        return;
      }

      Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, timbrar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        background: 'transparent',
        backdrop: 'rgba(15,23,42,.45)',
        allowOutsideClick: false,
        allowEscapeKey: true,
        customClass: {
          popup: 'custom-swal'
        }
      }).then(result => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
})();
</script>
@endpush
