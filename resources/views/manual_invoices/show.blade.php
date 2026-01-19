{{-- resources/views/manual_invoices/show.blade.php --}}
@extends('layouts.app')
@section('title','Detalle de factura')
@section('header','Detalle de factura')

@push('styles')
<style>
:root{
  --btn-blue:#2563eb; --btn-blue-h:#1d4ed8; --btn-blue-soft:#e6efff;
  --btn-green:#059669; --btn-green-h:#047857; --btn-green-soft:#e6fff4;
  --btn-gray:#64748b; --btn-gray-h:#475569; --btn-gray-soft:#eef2f7;
  --btn-red:#ef4444; --btn-red-h:#dc2626; --btn-red-soft:#ffe9eb;

  --surface:#ffffff; --border:#e5e7eb; --muted:#6b7280;
  --ink:#0f172a;
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

/* Buttons */
.pbtn{
  font-weight:800; border-radius:14px; padding:10px 14px;
  display:inline-flex; align-items:center; gap:8px;
  text-decoration:none; border:2px solid transparent; font-size:.86rem;
  cursor:pointer;
}
.pbtn svg{ flex:0 0 auto; }

.pbtn-blue{ color:var(--btn-blue); background:var(--btn-blue-soft); border-color:#cfe0ff }
.pbtn-blue:hover{ background:#dbeafe; color:var(--btn-blue-h) }

.pbtn-green{ color:var(--btn-green); background:var(--btn-green-soft); border-color:#b7f7db }
.pbtn-green:hover{ background:#d1fae5; color:var(--btn-green-h) }

.pbtn-gray{ color:var(--btn-gray); background:var(--btn-gray-soft); border-color:#e5e7eb }
.pbtn-gray:hover{ background:#e2e8f0; color:var(--btn-gray-h) }

.pbtn-red{ color:var(--btn-red); background:var(--btn-red-soft); border-color:#fecaca }
.pbtn-red:hover{ background:#fee2e2; color:var(--btn-red-h) }

/* Flash */
.flash{
  margin-top:10px;
  font-size:12px;
  padding:8px 12px;
  border-radius:999px;
  display:inline-block;
}
.flash.ok{ background:#ecfdf3; color:#047857; border:1px solid #bbf7d0; }
.flash.err{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }

/* Layout */
.grid{
  margin-top:14px;
  display:grid;
  grid-template-columns: 360px 1fr;
  gap:14px;
  align-items:start;
}
@media (max-width: 980px){
  .grid{ grid-template-columns:1fr; }
}

/* Cards */
.cardx{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:18px;
  overflow:hidden;
}
.cardx .head{
  padding:12px 14px;
  border-bottom:1px solid var(--border);
  background:#f7faff;
  font-weight:900;
  color:#334155;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
}
.cardx .body{ padding:14px; }

/* Status chip */
.chip-status{
  display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:4px 10px;
  font-size:.76rem; font-weight:800;
}
.chip-status .dot{ width:8px; height:8px; border-radius:999px; background:currentColor }
.chip-status.valid{ background:#ecfdf3; color:#15803d; border:1px solid #bbf7d0 }
.chip-status.draft{ background:#f3f4f6; color:#4b5563; border:1px solid #e5e7eb }
.chip-status.cancelled{ background:#fef2f2; color:#b91c1c; border:1px solid #fecaca }
.chip-status.pending_cancel{ background:#fffbeb; color:#b45309; border:1px solid #fed7aa }

/* Key/Value list */
.kv{ display:grid; grid-template-columns: 1fr; gap:10px; }
.kv .row{ display:flex; justify-content:space-between; gap:12px; }
.kv .k{ color:var(--muted); font-size:.82rem; font-weight:700; }
.kv .v{ color:var(--ink); font-weight:800; text-align:right; max-width:65%; overflow:hidden; text-overflow:ellipsis; }

/* Items table */
.table-wrap{
  background:var(--surface);
  border:1px solid var(--border);
  border-radius:16px;
  overflow:auto;
}
table{
  width:100%;
  min-width: 980px;
  border-collapse:separate;
  border-spacing:0;
  table-layout: fixed;
}
thead th{
  background:#f7faff; color:#334155; text-align:left; font-weight:900;
  border-bottom:1px solid var(--border); padding:12px 12px; white-space:nowrap;
  font-size:.75rem; text-transform:uppercase; letter-spacing:.04em;
}
tbody td{
  padding:11px 12px;
  border-bottom:1px solid var(--border);
  vertical-align:top;
  font-size:.84rem;
  color:#0f172a;
}
tbody tr:hover{ background:#f8fbff }

.mono{ font-feature-settings:"tnum" 1,"lnum" 1; }
.muted{ color:var(--muted); font-weight:700; font-size:.82rem; }
.badge{
  display:inline-flex; align-items:center;
  padding:.15rem .55rem; border-radius:999px;
  font-weight:800; font-size:.72rem;
  border:1px solid #e5e7eb; background:#f3f4f6; color:#334155;
}

/* Totals box */
.totals{
  margin-top:12px;
  border-top:1px dashed #e5e7eb;
  padding-top:12px;
  display:grid;
  gap:8px;
}
.totals .trow{ display:flex; justify-content:space-between; gap:12px; }
.totals .trow .k{ color:var(--muted); font-weight:800; }
.totals .trow .v{ font-weight:900; }
.totals .grand{ font-size:1.05rem; }

/* SweetAlert2 estilo vidrio minimal */
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

@media (max-width: 960px){
  .table-wrap{ border:0; background:transparent; overflow:visible }
  table, thead, tbody, th, td, tr{ display:block }
  thead{ display:none }
  table{ min-width:0 }
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
    font-weight:800;
    color:#6b7280;
    text-transform:uppercase;
    letter-spacing:.04em;
    display:block;
    margin-bottom:3px;
  }
}
</style>
@endpush

@section('content')
@php
  /** @var \App\Models\ManualInvoice $invoice */
  $clientName = $invoice->client->nombre ?? $invoice->receiver_name ?? '—';
  $clientRfc  = $invoice->client->rfc ?? $invoice->receiver_rfc ?? '';
  $date       = $invoice->stamped_at?->format('d M Y H:i') ?? $invoice->created_at->format('d M Y H:i');

  $serieFolio = trim(
      ($invoice->serie ? $invoice->serie : '') .
      ($invoice->serie && $invoice->folio ? '-' : '') .
      ($invoice->folio ?? '')
  );
  if ($serieFolio === '') $serieFolio = '—';

  $statusLabel = $invoice->status_label ?? ucfirst($invoice->status ?? '—');

  $money = fn($n) => '$' . number_format((float)($n ?? 0), 2);
@endphp

<div class="page">

  {{-- Hero --}}
  <div class="hero">
    <div>
      <h1 class="h4">Factura {{ $serieFolio }}</h1>
      <div class="subtle">
        {{ $clientName }} @if($clientRfc) · RFC {{ $clientRfc }} @endif · {{ $date }}
      </div>
    </div>

    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center">
      <a href="{{ route('manual_invoices.index') }}" class="pbtn pbtn-gray">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
        Volver
      </a>

      @if($invoice->status === 'draft')
        <a href="{{ route('manual_invoices.edit', $invoice) }}" class="pbtn pbtn-blue">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/>
          </svg>
          Editar
        </a>

        <form action="{{ route('manual_invoices.stamp', $invoice) }}" method="POST" style="display:inline">
          @csrf
          <button type="button"
                  class="pbtn pbtn-green js-timbrar-show"
                  data-sw-title="¿Timbrar factura {{ $serieFolio }}?"
                  data-sw-text="Se timbrará el CFDI en Facturapi para {{ $clientName }}. ¿Deseas continuar?">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
            Timbrar
          </button>
        </form>
      @else
        @if($invoice->facturapi_id)
          <a href="{{ route('manual_invoices.downloadPdf', $invoice) }}" class="pbtn pbtn-blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 3v12"/><path d="M8 11l4 4 4-4"/><path d="M5 21h14"/>
            </svg>
            PDF
          </a>

          <a href="{{ route('manual_invoices.downloadXml', $invoice) }}" class="pbtn pbtn-blue">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16v16H4z"/><path d="M8 8h8M8 12h8M8 16h6"/>
            </svg>
            XML
          </a>
        @endif
      @endif
    </div>
  </div>

  @if(session('status'))
    <div class="flash ok">{{ session('status') }}</div>
  @endif
  @if(session('error'))
    <div class="flash err">{{ session('error') }}</div>
  @endif

  <div class="grid">

    {{-- Aside --}}
    <div class="cardx">
      <div class="head">
        <span>Resumen</span>
        <span class="chip-status {{ $invoice->status }}">
          <span class="dot"></span> {{ $statusLabel }}
        </span>
      </div>
      <div class="body">
        <div class="kv">
          <div class="row">
            <div class="k">Tipo</div>
            <div class="v">
              @if($invoice->type === 'I') Ingreso
              @elseif($invoice->type === 'E') Egreso
              @elseif($invoice->type === 'P') Pago
              @else —
              @endif
            </div>
          </div>

          <div class="row">
            <div class="k">Serie/Folio</div>
            <div class="v">{{ $serieFolio }}</div>
          </div>

          <div class="row">
            <div class="k">Moneda</div>
            <div class="v">{{ $invoice->currency ?? 'MXN' }}</div>
          </div>

          @if(!empty($invoice->exchange_rate))
            <div class="row">
              <div class="k">Tipo de cambio</div>
              <div class="v mono">{{ $invoice->exchange_rate }}</div>
            </div>
          @endif

          <div class="row">
            <div class="k">Método</div>
            <div class="v">{{ $invoice->payment_method ?? '—' }}</div>
          </div>

          <div class="row">
            <div class="k">Forma</div>
            <div class="v">{{ $invoice->payment_form ?? '—' }}</div>
          </div>

          <div class="row">
            <div class="k">Uso CFDI</div>
            <div class="v">{{ $invoice->cfdi_use ?? '—' }}</div>
          </div>

          <div class="row">
            <div class="k">Exportación</div>
            <div class="v">{{ $invoice->exportation ?? '—' }}</div>
          </div>

          @if($invoice->facturapi_uuid)
            <div class="row">
              <div class="k">UUID</div>
              <div class="v mono" title="{{ $invoice->facturapi_uuid }}">{{ $invoice->facturapi_uuid }}</div>
            </div>
          @endif

          @if($invoice->verification_url)
            <div class="row">
              <div class="k">Verificación</div>
              <div class="v">
                <a href="{{ $invoice->verification_url }}" target="_blank" rel="noopener" style="color:#2563eb; font-weight:900; text-decoration:none;">
                  Abrir
                </a>
              </div>
            </div>
          @endif
        </div>

        <div class="totals">
          <div class="trow">
            <div class="k">Subtotal</div>
            <div class="v mono">{{ $money($invoice->subtotal) }}</div>
          </div>
          <div class="trow">
            <div class="k">Descuento</div>
            <div class="v mono">{{ $money($invoice->discount_total) }}</div>
          </div>
          <div class="trow">
            <div class="k">IVA</div>
            <div class="v mono">{{ $money($invoice->tax_total) }}</div>
          </div>
          <div class="trow grand">
            <div class="k">Total</div>
            <div class="v mono">{{ $money($invoice->total) }}</div>
          </div>
        </div>

        @if(!empty($invoice->notes))
          <div style="margin-top:12px; border-top:1px dashed #e5e7eb; padding-top:12px;">
            <div style="font-weight:900; margin-bottom:6px;">Notas internas</div>
            <div class="muted" style="white-space:pre-wrap">{{ $invoice->notes }}</div>
          </div>
        @endif
      </div>
    </div>

    {{-- Main --}}
    <div class="cardx">
      <div class="head">
        <span>Conceptos</span>
        <span class="badge">{{ $invoice->items->count() }} items</span>
      </div>
      <div class="body">

        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th style="width:240px;">Producto</th>
                <th>Descripción</th>
                <th style="width:90px;">Cant.</th>
                <th style="width:130px;">P. unit.</th>
                <th style="width:120px;">Desc.</th>
                <th style="width:90px;">IVA</th>
                <th style="width:130px;">Total</th>
              </tr>
            </thead>
            <tbody>
              @foreach($invoice->items as $it)
                @php
                  $pSku  = $it->sku ?? ($it->product->sku ?? null);
                  $pName = $it->product->name ?? null;
                  $taxRate = (float)($it->tax_rate ?? 0);
                @endphp
                <tr>
                  <td data-label="Producto">
                    <div style="font-weight:900;">
                      {{ $pSku ? strtoupper($pSku) : '—' }}
                    </div>
                    @if($pName)
                      <div class="muted">{{ $pName }}</div>
                    @endif
                    <div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">
                      <span class="badge">SAT {{ $it->product_key ?: '01010101' }}</span>
                      <span class="badge">{{ $it->unit_code ?: 'H87' }}</span>
                    </div>
                  </td>

                  <td data-label="Descripción">
                    <div style="font-weight:800;">{{ $it->description }}</div>
                  </td>

                  <td data-label="Cant." class="mono">
                    {{ rtrim(rtrim(number_format((float)$it->quantity, 3, '.', ''), '0'), '.') }}
                  </td>

                  <td data-label="P. unit." class="mono">
                    {{ $money($it->unit_price) }}
                  </td>

                  <td data-label="Desc." class="mono">
                    {{ $money($it->discount) }}
                  </td>

                  <td data-label="IVA">
                    <span class="badge mono">{{ rtrim(rtrim(number_format($taxRate,2,'.',''), '0'), '.') }}%</span>
                  </td>

                  <td data-label="Total" class="mono" style="font-weight:900;">
                    {{ $money($it->total) }}
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const btn = document.querySelector('.js-timbrar-show');
  if (!btn) return;

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
})();
</script>
@endpush
