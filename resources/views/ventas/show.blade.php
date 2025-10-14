@extends('layouts.app')
@section('title','Venta VTA-'.($venta->id ?: '—'))

@section('content')
<style>
  :root{
    --bg:#f6f7fb; --card:#fff; --ink:#1f2937; --muted:#6b7280; --line:#e5e7eb;
    --ok:#16a34a; --warn:#d97706; --bad:#b91c1c;
    --radius:16px; --shadow:0 16px 40px rgba(18,38,63,.08);
    /* Pasteles */
    --p1:#c7d2fe; /* indigo-200 */
    --p2:#bfdbfe; /* sky-200 */
    --p3:#bbf7d0; /* green-200 */
    --p4:#fde68a; /* amber-300 */
    --p5:#fecaca; /* rose-200 */
    --p6:#e9d5ff; /* violet-200 */
  }
  body{background:var(--bg)}
  .wrap{max-width:1100px;margin:24px auto;padding:0 14px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;margin-bottom:16px}
  .head{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;gap:12px;justify-content:space-between;align-items:center;flex-wrap:wrap}
  .body{padding:18px}
  .badge{padding:4px 10px;border-radius:999px;border:1px solid var(--line);font-size:12px;display:inline-flex;gap:8px;align-items:center}
  .badge .dot{width:8px;height:8px;border-radius:999px;background:#9ca3af}
  .badge.ok .dot{background:var(--ok)} .badge.warn .dot{background:var(--warn)} .badge.bad .dot{background:var(--bad)}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .actions{display:flex;gap:8px;flex-wrap:wrap}

  /* ===== BOTONES PASTELES ===== */
  .btn{
    --c:#111827;
    display:inline-flex;align-items:center;justify-content:center;gap:8px;
    padding:10px 14px;border-radius:14px;background:var(--p1); color:var(--c);
    font-weight:700;text-decoration:none;box-shadow:0 6px 16px rgba(17,24,39,.10);
    border:none; outline:none; cursor:pointer; transition:transform .15s, background .15s, color .15s, box-shadow .15s;
  }
  .btn:hover{transform:translateY(-1px); background:#fff; color:#111; box-shadow:0 10px 28px rgba(17,24,39,.14)}
  .btn:active{transform:translateY(0)}
  .btn.p1{background:var(--p1)} .btn.p2{background:var(--p2)} .btn.p3{background:var(--p3)}
  .btn.p4{background:var(--p4)} .btn.p5{background:var(--p5)} .btn.p6{background:var(--p6)}
  .btn.white{background:#fff;color:#111}
  .btn:disabled{opacity:.65;pointer-events:none}

  .small{font-size:12px;color:var(--muted)}
  .muted{color:var(--muted)}
  .kv{display:flex;justify-content:space-between;gap:8px;margin:6px 0}
  .table{width:100%;border-collapse:collapse}
  .table th,.table td{border-bottom:1px solid var(--line);padding:10px;text-align:left;vertical-align:top}
  .table th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#334155}
  .right{text-align:right}
  .pre{white-space:pre-wrap;word-break:break-word}

  /* Totales (derecha) */
  .totals-wrap{display:flex;justify-content:flex-end}
  .totals-box{width:100%;max-width:420px;margin-top:12px;border:1px dashed var(--line);border-radius:14px;padding:14px;background:#fff}
  .trow{display:flex;justify-content:space-between;gap:12px;margin:6px 0}
  .trow .label{color:#334155}
  .trow .value{font-variant-numeric:tabular-nums;text-align:right}
  .trow.sum .label,.trow.sum .value{font-weight:700}
  .trow.grand{margin-top:10px;padding-top:10px;border-top:2px solid var(--line)}
  .trow.pill .value{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:#f8fafc;border:1px solid var(--line);font-size:12px}

  /* Responsive tabla → cards */
  @media (max-width: 768px){
    .grid{grid-template-columns:1fr}
    .table thead{display:none}
    .table, .table tbody, .table tr, .table td{display:block;width:100%}
    .table tr{background:#fff;border:1px solid var(--line);border-radius:12px;margin-bottom:12px;padding:12px}
    .table td{border-bottom:none;padding:6px 0}
    .table td[data-label]::before{
      content:attr(data-label);display:block;font-size:12px;color:var(--muted);
      margin-bottom:2px;text-transform:uppercase;letter-spacing:.04em
    }
    .btn{flex:1}
    .totals-box{max-width:none}
  }
  @media print{ .actions{display:none!important} .card{box-shadow:none;border-color:#cbd5e1} body{background:#fff} }

  /* ===== MODAL (dialog) ===== */
  dialog#sendEmailModal{
    border:none; border-radius:18px; padding:0; width:96%; max-width:640px;
    box-shadow:0 28px 70px rgba(18,38,63,.22);
    background:#fff; color:#0f172a;
  }
  dialog#sendEmailModal::backdrop{
    background:rgba(15,23,42,.35);
    -webkit-backdrop-filter: blur(2px);
    backdrop-filter: blur(2px);
  }
  .modal-head{
    padding:16px 18px; border-bottom:1px solid var(--line);
    display:flex; align-items:center; justify-content:space-between; gap:12px;
  }
  .modal-foot{
    padding:16px 18px; border-top:1px solid var(--line);
    display:flex; align-items:center; justify-content:flex-end; gap:8px;
  }
  .input{
    width:100%; padding:10px 12px;
    border:1px solid var(--line); border-radius:10px;
    background:#fff; color:#0f172a; outline:none;
    transition:border-color .15s, box-shadow .15s;
  }
  .input:focus{ border-color:#94a3b8; box-shadow:0 0 0 3px rgba(148,163,184,.25) }
  label{ display:block; font-size:12px; color:#64748b; margin-bottom:6px; }

  /* ===== SWEETALERT2: Redondeado + sin scroll ===== */
  .swal2-popup.swal-rounded{
    border-radius:18px !important;
    border:1px solid var(--line);
    box-shadow:0 24px 64px rgba(18,38,63,.18);
    overflow:visible !important;      /* quita scroll interno */
  }
  .swal2-html-container{
    color:#334155; max-height:none !important; /* sin scroll */
  }
  .swal2-title{ font-weight:800; letter-spacing:.2px }

  /* ===== Loader Gooey (SCOPED) ===== */
  .goo-stage{
    width:100%; display:flex; align-items:center; justify-content:center; flex-direction:column;
    padding:6px 0;
  }
  .goo-container{
    width:200px; height:200px; position:relative; margin:auto;
    filter:url('#goo');
    animation: goo-rotate-move 2s ease-in-out infinite;
  }
  .goo-dot{
    width:70px; height:70px; border-radius:50%; background-color:#000;
    position:absolute; inset:0; margin:auto;
  }
  .goo-dot-3{ background-color:#f74d75; animation: goo-dot-3-move 2s ease infinite, goo-index 6s ease infinite; }
  .goo-dot-2{ background-color:#10beae; animation: goo-dot-2-move 2s ease infinite, goo-index 6s -4s ease infinite; }
  .goo-dot-1{ background-color:#ffe386; animation: goo-dot-1-move 2s ease infinite, goo-index 6s -2s ease infinite; }
  @keyframes goo-dot-3-move{
    20% {transform: scale(1)}
    45% {transform: translateY(-18px) scale(.45)}
    60% {transform: translateY(-90px) scale(.45)}
    80% {transform: translateY(-90px) scale(.45)}
    100%{transform: translateY(0) scale(1)}
  }
  @keyframes goo-dot-2-move{
    20% {transform: scale(1)}
    45% {transform: translate(-16px, 12px) scale(.45)}
    60% {transform: translate(-80px, 60px) scale(.45)}
    80% {transform: translate(-80px, 60px) scale(.45)}
    100%{transform: translate(0,0) scale(1)}
  }
  @keyframes goo-dot-1-move{
    20% {transform: scale(1)}
    45% {transform: translate(16px, 12px) scale(.45)}
    60% {transform: translate(80px, 60px) scale(.45)}
    80% {transform: translate(80px, 60px) scale(.45)}
    100%{transform: translate(0,0) scale(1)}
  }
  @keyframes goo-rotate-move{
    55% {transform: rotate(0deg)}
    80% {transform: rotate(360deg)}
    100%{transform: rotate(360deg)}
  }
  @keyframes goo-index{
    0%,100% {z-index:3}
    33.3%   {z-index:2}
    66.6%   {z-index:1}
  }
  .goo-caption{ margin-top:6px; font-size:13px; color:#64748b; text-align:center }
</style>

@php
  use Illuminate\Support\Arr;

  $items = $venta->relationLoaded('items') ? $venta->items : $venta->items()->get();

  // Inversión en COSTO: Σ (cost * cantidad)
  $inversion_costo = 0.0;
  // Subtotal como base de precios (referencia)
  $subtotal_precios = 0.0;

  foreach ($items as $it) {
      $cost = (float)($it->cost ?? 0);
      $pu   = (float)($it->precio_unitario ?? $it->precio ?? 0);
      $qty  = (float)($it->cantidad ?? 0);
      $inversion_costo   += ($cost * $qty);
      $subtotal_precios  += ($pu   * $qty);
  }

  // Totales persistidos
  $subtotal        = (float)($venta->subtotal ?? 0);
  $iva             = (float)($venta->iva ?? 0);
  $descuentoGlobal = (float)($venta->descuento ?? 0);
  $envio           = (float)($venta->envio ?? 0);
  $total           = (float)($venta->total ?? max(0, round($subtotal - $descuentoGlobal + $envio + $iva, 2)));

  // Ganancia estimada
  $ganancia_estimada = (float)($venta->ganancia_estimada ?? 0);
  if (!$ganancia_estimada && $inversion_costo > 0) {
      $ganancia_estimada = max(0, round($subtotal - $inversion_costo, 2));
  }
  $utilidad_global = (float)($venta->utilidad_global ?? 0);
  $utilidad_pct    = $inversion_costo > 0 ? ($ganancia_estimada / $inversion_costo) * 100 : 0;

  $estado = strtolower($venta->estado ?? 'pendiente');
  $estadoClass = [
    'pagada'=>'ok','pagado'=>'ok','completada'=>'ok',
    'pendiente'=>'warn','en_proceso'=>'warn',
    'cancelada'=>'bad',
  ][$estado] ?? '';
@endphp

<div class="wrap">
  {{-- HEADER --}}
  <div class="card">
    <div class="head">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <h2 style="margin:0">VTA-{{ $venta->id ?: '—' }}</h2>
        <span class="badge {{ $estadoClass }}"><span class="dot"></span>{{ ucfirst($venta->estado ?? 'Pendiente') }}</span>
      </div>
      <div class="actions">
        <a href="{{ route('ventas.pdf', $venta) }}" class="btn p2" target="_blank" rel="noopener">PDF de venta</a>
        @if(!empty($venta->factura_pdf_url))
          <a href="{{ $venta->factura_pdf_url }}" class="btn p3" target="_blank" rel="noopener">Factura timbrada (PDF)</a>
        @endif
        @if(!empty($venta->factura_xml_url))
          <a href="{{ $venta->factura_xml_url }}" class="btn p6" target="_blank" rel="noopener">Factura (XML)</a>
        @endif

        <button class="btn p1" type="button" onclick="document.getElementById('sendEmailModal').showModal()">
          Enviar por email
        </button>
      </div>
    </div>

    <div class="body">
      <div class="grid">
        <div>
          @php $cli = $venta->cliente; @endphp
          <div style="font-weight:700;margin-bottom:4px">Cliente</div>
          <div>{{ $cli->name ?? $cli->nombre ?? $cli->razon_social ?? '—' }}</div>
          <div class="small muted">
            @if(!empty($cli?->rfc)) RFC: {{ $cli->rfc }} @endif
            @if(!empty($cli?->email)) · {{ $cli->email }} @endif
            @if(!empty($cli?->telefono)) · {{ $cli->telefono }} @endif
          </div>
          @if($venta->cotizacion)
            <div class="small muted" style="margin-top:6px">Origen: COT-{{ $venta->cotizacion->id }}</div>
          @endif
        </div>
        <div>
          <div class="kv"><div class="muted">Moneda</div><div>{{ $venta->moneda ?? 'MXN' }}</div></div>
          @if($utilidad_global>0)
            <div class="kv"><div class="muted">Utilidad global (%)</div><div>{{ number_format($utilidad_global,2) }}%</div></div>
          @endif
          @if(!empty($venta->factura_uuid))
            <div class="kv"><div class="muted">UUID</div><div>{{ $venta->factura_uuid }}</div></div>
          @endif
          <div class="kv"><div class="muted">Fecha</div><div>{{ optional($venta->created_at)->format('d/m/Y H:i') }}</div></div>
          @if(!empty($venta->notas))
            <div class="kv" style="align-items:flex-start"><div class="muted">Notas</div><div class="pre">{{ $venta->notas }}</div></div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL: Enviar por correo --}}
  <dialog id="sendEmailModal">
    <form id="emailForm" method="POST" action="{{ route('ventas.email', $venta) }}">
      @csrf
      <div class="modal-head">
        <h3 style="margin:0">Enviar por email</h3>
        <button type="button" onclick="document.getElementById('sendEmailModal').close()" class="btn white">Cerrar</button>
      </div>

      <div style="padding:18px;display:grid;gap:10px">
        @php $cli = $venta->cliente; @endphp

        <div>
          <label>Para</label>
          <input name="to" type="email" class="input"
                 value="{{ $cli->email ?? '' }}" required
                 placeholder="cliente@correo.com">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
          <div>
            <label>CC (opcional)</label>
            <input name="cc" type="email" class="input" placeholder="cc@correo.com">
          </div>
          <div>
            <label>BCC (opcional)</label>
            <input name="bcc" type="email" class="input" placeholder="bcc@correo.com">
          </div>
        </div>

        <div>
          <label>Asunto</label>
          <input name="subject" type="text" class="input"
                 value="Documentos de su compra VTA-{{ $venta->folio }}">
        </div>

        <div>
          <label>Mensaje</label>
          <textarea name="message" rows="5" class="input" placeholder="Escribe un mensaje personalizado…"></textarea>
          <div class="small" style="color:#6b7280;margin-top:6px">
            Se adjuntarán la <strong>factura (PDF/XML)</strong> y el <strong>PDF de la venta</strong>.
          </div>
        </div>
      </div>

      <div class="modal-foot">
        <button type="button" class="btn white" onclick="document.getElementById('sendEmailModal').close()">Cancelar</button>
        <button id="sendBtn" type="submit" class="btn p3">Enviar</button>
      </div>
    </form>
  </dialog>

  {{-- PRODUCTOS --}}
  <div class="card">
    <div class="head"><h3 style="margin:0">Productos</h3></div>
    <div class="body">
      <table class="table">
        <thead>
          <tr>
            <th>Producto / Descripción</th>
            <th class="right">Cant.</th>
            <th class="right">P. Unit.</th>
            <th class="right">Desc.</th>
            <th class="right">IVA%</th>
            <th class="right">Importe</th>
          </tr>
        </thead>
        <tbody>
          @forelse($items as $it)
            @php $prod = $it->producto; @endphp
            <tr>
              <td data-label="Producto / Descripción">
                <div style="font-weight:600">{{ $it->descripcion ?? ($prod->nombre ?? $prod->name ?? ('#'.$it->producto_id)) }}</div>
                @if(!empty($prod?->sku) || !empty($prod?->marca))
                  <div class="small muted">
                    @if(!empty($prod?->sku)) SKU: {{ $prod->sku }} @endif
                    @if(!empty($prod?->marca)) · {{ $prod->marca }} @endif
                  </div>
                @endif
              </td>
              <td class="right" data-label="Cant.">{{ number_format($it->cantidad,2) }}</td>
              <td class="right" data-label="P. Unit.">${{ number_format($it->precio_unitario ?? ($it->precio ?? 0),2) }}</td>
              <td class="right" data-label="Desc.">${{ number_format($it->descuento ?? 0,2) }}</td>
              <td class="right" data-label="IVA %">{{ number_format($it->iva_porcentaje ?? 0,2) }}%</td>
              <td class="right" data-label="Importe">${{ number_format($it->importe ?? ($it->importe_total ?? 0),2) }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="small muted">Sin productos.</td></tr>
          @endforelse
        </tbody>
      </table>

      {{-- Totales derecha --}}
      <div class="totals-wrap">
        <div class="totals-box">
          <div class="trow">
            <div class="label">Inversión (costo)</div>
            <div class="value">${{ number_format($inversion_costo,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">Ganancia estimada</div>
            <div class="value">${{ number_format($ganancia_estimada,2) }}</div>
          </div>
          <div class="trow pill">
            <div class="label">Utilidad</div>
            <div class="value">{{ number_format($utilidad_pct,2) }}%</div>
          </div>

          <div class="trow sum">
            <div class="label">Subtotal</div>
            <div class="value">${{ number_format($subtotal,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">IVA</div>
            <div class="value">${{ number_format($iva,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">Descuento</div>
            <div class="value">- ${{ number_format($descuentoGlobal,2) }}</div>
          </div>
          <div class="trow">
            <div class="label">Envío</div>
            <div class="value">${{ number_format($envio,2) }}</div>
          </div>
          <div class="trow grand">
            <div class="label">TOTAL</div>
            <div class="value"><strong>${{ number_format($total,2) }} {{ $venta->moneda ?? 'MXN' }}</strong></div>
          </div>

          <div class="trow small">
            <div class="label">Precio productos (referencia)</div>
            <div class="value">${{ number_format($subtotal_precios,2) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- FINANCIAMIENTO --}}
  @if($venta->plazos && $venta->plazos->count())
    <div class="card">
      <div class="head"><h3 style="margin:0">Plan de financiamiento</h3></div>
      <div class="body">
        <table class="table">
          <thead><tr><th>#</th><th>Vence</th><th class="right">Monto</th><th>Estado</th></tr></thead>
          <tbody>
            @foreach($venta->plazos as $pz)
              <tr>
                <td data-label="#">{{ $pz->numero }}</td>
                <td data-label="Vence">{{ optional($pz->vence_el)->format('d/m/Y') }}</td>
                <td data-label="Monto" class="right">${{ number_format($pz->monto,2) }}</td>
                <td data-label="Estado">{{ $pz->pagado ? 'Pagado' : 'Pendiente' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        @if($venta->financiamiento_config)
          <div class="small muted" style="margin-top:8px">
            Tasa anual: {{ $venta->financiamiento_config['tasa_anual'] ?? 0 }}% —
            Enganche: ${{ number_format($venta->financiamiento_config['enganche'] ?? 0,2) }}
            @if(isset($venta->financiamiento_config['plazos'])) — Plazos: {{ $venta->financiamiento_config['plazos'] }} @endif
            @if(isset($venta->financiamiento_config['primer_vencimiento'])) — Primer vencimiento: {{ Arr::get($venta->financiamiento_config,'primer_vencimiento') }} @endif
          </div>
        @endif
      </div>
    </div>
  @endif
</div>

{{-- SweetAlert2 + lógica de envío --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const form    = document.getElementById('emailForm');
    const modal   = document.getElementById('sendEmailModal');
    const sendBtn = document.getElementById('sendBtn');

    if (form) {
      form.addEventListener('submit', () => {
        // Cierra el modal inmediatamente
        if (modal && typeof modal.close === 'function' && modal.open) {
          modal.close();
        }
        if (sendBtn) sendBtn.disabled = true;

        // Loader Gooey (SweetAlert sin scroll interno)
        requestAnimationFrame(() => {
          Swal.fire({
            title: 'Enviando…',
            html: `
              <div class="goo-stage" aria-live="polite">
                <div class="goo-container">
                  <div class="goo-dot goo-dot-1"></div>
                  <div class="goo-dot goo-dot-2"></div>
                  <div class="goo-dot goo-dot-3"></div>
                </div>
                <div class="goo-caption">Preparando adjuntos</div>
                <svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute">
                  <defs>
                    <filter id="goo">
                      <feGaussianBlur in="SourceGraphic" stdDeviation="10" result="blur" />
                      <feColorMatrix in="blur" mode="matrix"
                        values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 21 -7"/>
                    </filter>
                  </defs>
                </svg>
              </div>
            `,
            customClass: { popup: 'swal-rounded' },
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            backdrop: true,
            heightAuto: false   // evita reajuste y scroll
          });
        });
      }, { passive: true });
    }

    // Mensajes post-redirect
    @if (session('ok'))
      Swal.fire({
        icon:'success',
        title:'Enviado',
        text:"{{ session('ok') }}",
        timer:1800,
        showConfirmButton:false,
        customClass:{ popup:'swal-rounded' },
        heightAuto:false
      });
    @endif

    @if (session('warn'))
      Swal.fire({
        icon:'warning',
        title:'Aviso',
        text:"{{ session('warn') }}",
        timer:2200,
        showConfirmButton:false,
        customClass:{ popup:'swal-rounded' },
        heightAuto:false
      });
    @endif

    @if ($errors->any())
      Swal.fire({
        icon:'error',
        title:'No se pudo enviar',
        html:`{!! implode('<br>', $errors->all()) !!}`,
        confirmButtonText:'Entendido',
        customClass:{ popup:'swal-rounded' },
        heightAuto:false
      });
    @endif
  });
</script>
@endsection
