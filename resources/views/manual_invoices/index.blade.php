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

.pbtn{
  font-weight:800; border-radius:14px; padding:10px 14px;
  display:inline-flex; align-items:center; gap:8px;
  text-decoration:none; border:2px solid transparent; font-size:.86rem
}
.pbtn-blue{ color:var(--btn-blue); background:var(--btn-blue-soft); border-color:#cfe0ff }
.pbtn-blue:hover{ background:#dbeafe; color:var(--btn-blue-h) }

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

/* Tabs de estatus */
.tabs{
  margin-top:14px; display:flex; flex-wrap:wrap; gap:8px;
}
.tab-pill{
  border-radius:999px;
  padding:6px 12px;
  font-size:.8rem;
  border:1px solid var(--border);
  background:#f8fafc;
  color:#64748b;
  text-decoration:none;
  font-weight:600;
}
.tab-pill.active{
  background:#e6efff;
  border-color:#bfdbfe;
  color:#1d4ed8;
}

/* Tabla */
.table-wrap{
  margin-top:14px; background:var(--surface); border:1px solid var(--border); border-radius:16px;
  overflow:auto; contain: paint; -webkit-overflow-scrolling:touch;
}
table{
  width:100%; min-width: 880px;
  border-collapse:separate; border-spacing:0; table-layout: fixed;
}
thead th{
  background:#f7faff; color:#334155; text-align:left; font-weight:800;
  border-bottom:1px solid var(--border); padding:12px 12px; white-space:nowrap; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em;
}
tbody td{ padding:11px 12px; border-bottom:1px solid var(--border); vertical-align:middle; font-size:.84rem }
tbody tr:hover{ background:#f8fbff }
tbody tr{ transform: translateZ(0); backface-visibility:hidden; }

th.th-actions, td.t-actions{
  position:sticky; right:0; background:var(--surface); z-index:2; border-left:1px solid var(--border)
}

/* Badges */
.badge-type{
  padding:.18rem .55rem; border-radius:999px; font-weight:700; font-size:.72rem;
  background:#eef2f7; color:#334155; border:1px solid #e5e7eb;
}
.badge-type.i{ background:#e6efff; color:#1d4ed8; border-color:#bfdbfe }
.badge-type.e{ background:#fef3c7; color:#92400e; border-color:#fde68a }
.badge-type.p{ background:#e0f2fe; color:#0369a1; border-color:#bae6fd }

.chip-status{
  display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:4px 10px;
  font-size:.76rem; font-weight:700;
}
.chip-status .dot{ width:8px; height:8px; border-radius:999px; background:currentColor }
.chip-status.valid{ background:#ecfdf3; color:#15803d; border:1px solid #bbf7d0 }
.chip-status.draft{ background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb }
.chip-status.cancelled{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca }
.chip-status.pending_cancel{ background:#fffbeb; color:#b45309; border:1px solid #fed7aa }

/* Cliente + importe */
.cell-client{ max-width:260px; overflow:hidden; text-overflow:ellipsis; }
.cell-client .name{ font-weight:700; }
.cell-client .rfc{ font-size:.78rem; color:#6b7280; }

.cell-amount a{
  color:#2563eb; text-decoration:none; font-weight:700; font-feature-settings:"tnum" 1,"lnum" 1;
}
.cell-amount a:hover{ text-decoration:underline; }

/* Botones acciones */
.btn-mini{
  border-radius:999px;
  padding:6px 10px;
  font-size:.76rem;
  border:1px solid #e5e7eb;
  background:#f9fafb;
  color:#111827;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:6px;
}
.btn-mini.primary{
  background:#2563eb;
  color:#fff;
  border-color:#1d4ed8;
}
.btn-mini + .btn-mini{ margin-left:4px; }

/* Flash */
.status-flash{
  margin-top:10px;
  font-size:12px;
  padding:8px 12px;
  border-radius:999px;
  background:#ecfdf3;
  color:#047857;
  border:1px solid #bbf7d0;
}

/* Responsive */
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
  tbody td{ border:0; padding:4px 0; }
  td[data-label]::before{
    content: attr(data-label) ": ";
    font-size:.75rem;
    font-weight:600;
    color:#6b7280;
    text-transform:uppercase;
    letter-spacing:.04em;
    display:block;
    margin-bottom:2px;
  }
  th.th-actions, td.t-actions{ position:static; border-left:0; background:transparent; }
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
      <a href="{{ route('manual_invoices.create') }}" class="pbtn pbtn-blue">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        Crear factura
      </a>

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
        <th class="th-actions" style="width:150px;">Acciones</th>
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
          if ($serieFolio === '') {
              $serieFolio = '—';
          }

          $typeClass = strtolower($inv->type ?? 'i');

          // Si tienes accessor getStatusLabelAttribute en el modelo:
          $statusLabel = $inv->status_label ?? ucfirst($inv->status ?? '—');
        @endphp
        <tr
          data-name="{{ \Illuminate\Support\Str::lower($clientName) }}"
          data-rfc="{{ \Illuminate\Support\Str::lower($clientRfc) }}"
          data-folio="{{ \Illuminate\Support\Str::lower($serieFolio) }}"
        >
          <td data-label="Tipo">
            <span class="badge-type {{ $typeClass }}">
              @if($inv->type === 'I') Ingreso
              @elseif($inv->type === 'E') Egreso
              @elseif($inv->type === 'P') Pago
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

          <td data-label="Serie/Folio">
            {{ $serieFolio }}
          </td>

          <td data-label="Fecha">
            {{ $date }}
          </td>

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
            @if($inv->status === 'draft')
              <a href="{{ route('manual_invoices.edit', $inv) }}" class="btn-mini">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
                </svg>
                Editar
              </a>
              <form action="{{ route('manual_invoices.stamp', $inv) }}"
                    method="POST"
                    style="display:inline">
                @csrf
                <button type="submit" class="btn-mini primary"
                        onclick="return confirm('¿Timbrar esta factura?');">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 7h16M4 12h16M4 17h16"/>
                  </svg>
                  Timbrar
                </button>
              </form>
            @else
              <a href="{{ route('manual_invoices.show', $inv) }}" class="btn-mini">
                Ver detalle
              </a>
            @endif
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

  <div style="margin-top:10px; font-size:.8rem; color:#6b7280; display:flex; justify-content:space-between; align-items:center;">
    <span>
      Mostrando {{ $invoices->count() }} de {{ $invoices->total() }} facturas
    </span>
    <span>{{ $invoices->links() }}</span>
  </div>

</div>
@endsection

@push('scripts')
<script>
(function(){
  const input    = document.getElementById('liveSearchInput');
  const clearBtn = document.getElementById('sbClear');

  if (!input || !clearBtn) return;

  function updateClear(){
    if (input.value.trim() !== '') {
      clearBtn.style.visibility = 'visible';
    } else {
      clearBtn.style.visibility = 'hidden';
    }
  }

  updateClear();

  clearBtn.addEventListener('click', function(){
    input.value = '';
    updateClear();
    // reenviar el form sin q
    const form = input.closest('form');
    if (form) {
      form.querySelectorAll('input[name="q"]').forEach(i => i.value = '');
      form.submit();
    }
  });

  input.addEventListener('input', updateClear);
})();
</script>
@endpush
