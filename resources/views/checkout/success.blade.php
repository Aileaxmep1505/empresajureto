{{-- resources/views/checkout/success.blade.php --}}
@extends('layouts.web')
@section('title','Pago recibido')

@section('content')
@php
  use App\Models\Venta;

  /** =========================
   * 1) RESOLVER ORIGEN DE DATOS
   * ==========================*/
  /** @var \App\Models\Order|null $order */
  $hasOrder   = isset($order) && $order;

  // ID de sesión (prioriza el de la orden)
  $sessionId  = $sessionId
                ?? request('session_id')
                ?? ($hasOrder ? ($order->stripe_session_id ?? null) : null);

  // --------- Cuando VIENE ORDEN (preferido) ----------
  if ($hasOrder) {
      $itemsCol   = $order->relationLoaded('items') ? $order->items : $order->items()->get();
      $items      = collect($itemsCol ?? []);
      $subtotal   = (float) ($order->subtotal ?? 0);
      $shipAmount = (float) ($order->shipping_amount ?? 0);
      $total      = (float) ($order->total ?? ($subtotal + $shipAmount));

      $shipName   = $order->shipping_name ?? null;
      $shipSrv    = $order->shipping_service ?? null;
      $shipEta    = $order->shipping_eta ?? null;

      $addrArr    = (array) ($order->address_json ?? []);
      $addrTx     = trim(
                      ($addrArr['street'] ?? '') . ' ' .
                      ($addrArr['ext_number'] ?? '') . ' ' .
                      ($addrArr['colony'] ?? '') . ', ' .
                      ($addrArr['municipality'] ?? '') . ', ' .
                      ($addrArr['state'] ?? '') . ' CP ' .
                      ($addrArr['postal_code'] ?? '')
                    );
  }
  // --------- Fallback: usar SESIÓN (si no hay orden) ----------
  else {
      $cart       = (array) session('cart', []);
      $subtotal   = array_reduce($cart, fn($c,$r)=> $c + (float)($r['price']??0) * max(1,(int)($r['qty']??1)), 0);
      $shipping   = (array) session('checkout.shipping', ['price'=>0]);
      $shipAmount = (float) ($shipping['price'] ?? 0);
      $total      = $subtotal + $shipAmount;

      $shipName   = $shipping['name']    ?? ($shipping['carrier'] ?? null);
      $shipSrv    = $shipping['service'] ?? null;
      $shipEta    = $shipping['eta']     ?? null;

      $addr       = (array) session('checkout.address', []);
      $addrTx     = trim(
                      ($addr['street'] ?? '') . ' ' .
                      ($addr['ext_number'] ?? '') . ' ' .
                      ($addr['colony'] ?? '') . ', ' .
                      ($addr['municipality'] ?? '') . ', ' .
                      ($addr['state'] ?? '') . ' CP ' .
                      ($addr['postal_code'] ?? '')
                    );

      // para pintar items con el mismo markup que la orden
      $items = collect(array_map(function($r){
          return (object)[
              'name'   => $r['name'] ?? 'Producto',
              'qty'    => (int) max(1, (int)($r['qty'] ?? 1)),
              'price'  => (float) ($r['price'] ?? 0),
              'amount' => (float) ($r['price'] ?? 0) * max(1, (int)($r['qty'] ?? 1)),
              'meta'   => ['image' => $r['image'] ?? null],
          ];
      }, $cart));
  }

  /** =========================
   * 2) FACTURA (si se timbró)
   * ==========================*/
  // Estructura esperada: ['id','uuid','series','folio_number', ...]
  $invoice    = (array) ($invoice ?? session('checkout.invoice', []));
  $invoiceId  = $invoice['id']           ?? null;
  $invoiceUUID= $invoice['uuid']         ?? null;
  $series     = $invoice['series']       ?? null;
  $folioNum   = $invoice['folio_number'] ?? null;
  $folioFull  = trim(($series ? $series : '').'-'.($folioNum ? $folioNum : ''), '-');

  // Fallback a tu modelo Venta (si lo usas)
  $ventaIdFromSession = session('venta_id');
  $venta = null;
  if ($ventaIdFromSession) {
      $venta = Venta::query()
        ->where('user_id', auth()->id())
        ->where('id', $ventaIdFromSession)
        ->first();
  }
  if (!$venta && auth()->check()) {
      $venta = Venta::query()
        ->where('user_id', auth()->id())
        ->orderByDesc('timbrada_en')
        ->orderByDesc('id')
        ->first();
  }
  $facturaPDF  = $venta->factura_pdf_url   ?? null;
  $facturaXML  = $venta->factura_xml_url   ?? null;
  $facturaId   = $venta->factura_id        ?? null;
  $facturaUUID = $venta->factura_uuid      ?? null;

  $tagFolio = $folioFull ?: ($venta->serie ?? '');
  $tagFolio = trim($tagFolio . (isset($venta->folio) ? '-'.$venta->folio : ''), '-');
  $tagUUID  = $invoiceUUID ?: $facturaUUID;
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet"/>

<style>
  /* ====================== VARIABLES ====================== */
  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #111111;
    --text: #333333;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
  }

  body {
    font-family: "Quicksand", system-ui, -apple-system, sans-serif;
    background: var(--bg);
    color: var(--text);
    margin: 0;
  }

  /* ====== Estilos aislados para esta vista ====== */
  .sx-wrap {
    max-width: 980px;
    margin: clamp(32px, 5vw, 64px) auto;
    padding: 0 20px;
    box-sizing: border-box;
  }
  
  .sx-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 20px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.04);
    overflow: hidden;
  }
  
  .sx-hero {
    padding: 40px 32px;
    background: radial-gradient(circle at top right, var(--success-soft) 0%, var(--card) 60%);
    text-align: center;
    border-bottom: 1px solid var(--line);
  }
  
  .sx-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--success-soft); color: var(--success);
    font-weight: 700; border-radius: 999px; padding: 6px 14px;
    font-size: 0.95rem; margin-bottom: 20px;
  }
  
  .sx-title {
    margin: 0; font-size: 2.2rem; font-weight: 700; color: var(--ink);
    display: flex; gap: 12px; align-items: center; justify-content: center;
  }
  
  .sx-sub {
    color: var(--muted); margin-top: 12px; font-size: 1.05rem; font-weight: 500;
  }

  .sx-body {
    padding: 32px; display: grid; grid-template-columns: 2fr 1fr; gap: 32px;
  }
  @media(max-width: 980px){ .sx-body{ grid-template-columns: 1fr; } }

  .btn {
    display: inline-flex; align-items: center; gap: 10px; justify-content: center;
    border-radius: 8px; padding: 10px 18px; font-weight: 600; font-size: 0.95rem;
    text-decoration: none; border: none; cursor: pointer; font-family: inherit;
    transition: transform 0.15s ease, box-shadow 0.2s ease, background 0.2s ease;
  }
  .btn:active { transform: scale(0.98); }
  
  .btn-primary { background: var(--blue); color: #ffffff; }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(0, 122, 255, 0.15); }
  
  .btn-outline { background: var(--card); border: 1px solid var(--blue); color: var(--blue); }
  .btn-outline:hover { background: var(--blue-soft); transform: translateY(-1px); }
  
  .btn-ghost { background: transparent; color: #555555; border: 1px solid var(--line); }
  .btn-ghost:hover { background: var(--bg); color: var(--ink); }

  .muted { color: var(--muted); }
  
  .sum { display: flex; justify-content: space-between; margin: 12px 0; font-weight: 600; color: var(--text); }
  .line { border: 0; border-top: 1px solid var(--line); margin: 24px 0; }
  
  .sx-id {
    display: flex; gap: 12px; align-items: center; justify-content: space-between;
    background: var(--bg); border: 1px dashed var(--line); color: var(--ink);
    padding: 12px 16px; border-radius: 12px; word-break: break-all; margin-top: 8px;
  }
  
  .sx-grid-items { display: grid; gap: 0; }
  .sx-item {
    display: grid; grid-template-columns: auto 1fr auto; gap: 16px; align-items: center;
    padding: 16px 0; border-bottom: 1px solid var(--line);
  }
  .sx-item:last-child { border-bottom: none; }
  
  .sx-receipt {
    background: var(--bg); border: 1px solid var(--line); border-radius: 16px; padding: 20px;
  }
  
  .sx-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
  
  .sx-pill {
    display: inline-flex; gap: 8px; align-items: center;
    background: var(--card); border: 1px solid var(--line); color: var(--ink);
    padding: 6px 12px; border-radius: 999px; font-weight: 600; font-size: 0.85rem;
  }

  canvas#confetti { position: fixed; inset: 0; pointer-events: none; z-index: 9999; }
</style>

<canvas id="confetti"></canvas>

<div class="sx-wrap">
  <div class="sx-card">
    <div class="sx-hero">
      <div class="sx-badge">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M5 12l4 4L19 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Pago Procesado con Éxito
      </div>
      <h1 class="sx-title">
        ¡Gracias por tu compra! <span aria-hidden="true">🎉</span>
      </h1>
      <p class="sx-sub">Hemos registrado tu orden correctamente. En breve recibirás un correo con el detalle completo.</p>
    </div>

    <div class="sx-body">
      {{-- Columna principal --}}
      <div>
        {{-- ID de sesión de Stripe --}}
        @if($sessionId)
        <div style="margin-bottom: 24px;">
          <label style="font-weight:700; color: var(--ink);">ID de Transacción (Stripe)</label>
          <div class="sx-id" id="idBox">
            <code id="sid" style="font-family: monospace; font-size: 0.95rem;">{{ $sessionId }}</code>
            <button class="btn btn-ghost" id="copyBtn" type="button" title="Copiar ID" style="padding: 6px 12px; font-size: 0.85rem;">Copiar</button>
          </div>
          <div class="muted" id="copyMsg" style="display:none; margin-top:8px; font-size: 0.85rem; color: var(--success); font-weight: 600;">¡Copiado al portapapeles!</div>
        </div>
        @endif

        {{-- Envío + dirección (si existen) --}}
        @if(($shipName ?? null) || ($addrTx ?? null))
          <div class="line"></div>
          <h3 style="font-weight:700; margin: 0 0 12px 0; font-size: 1.1rem; color: var(--ink);">Detalles de Envío</h3>
          <div style="margin-bottom:8px; font-weight: 600; color: var(--ink);">
            @if($shipName){{ $shipName }}@endif
            @if($shipSrv) <span class="muted" style="font-weight:500;">— {{ $shipSrv }}</span>@endif
            @if($shipEta) <span class="muted" style="font-weight:500;">({{ $shipEta }})</span>@endif
          </div>
          @if(!empty($addrTx))
            <div style="line-height: 1.6; color: var(--text);">{{ $addrTx }}</div>
          @endif
        @endif

        {{-- Resumen del pedido --}}
        <div class="line"></div>
        <h3 style="margin: 0 0 16px 0; font-weight:700; font-size: 1.1rem; color: var(--ink);">Resumen de tu pedido</h3>

        @if($items->count())
          <div class="sx-grid-items">
            @foreach($items as $it)
              @php
                $img = data_get($it, 'meta.image');
                if (is_string($it->meta ?? null)) {
                  $decoded = json_decode($it->meta, true);
                  $img = $img ?: data_get($decoded, 'image');
                }
              @endphp
              <div class="sx-item">
                <img src="{{ $img ?: asset('images/placeholder.png') }}" alt="" 
                     style="width:56px; height:56px; border-radius:8px; border:1px solid var(--line); object-fit:cover; background: var(--bg);">
                <div>
                  <div style="font-weight:600; color: var(--ink);">{{ $it->name ?? 'Producto' }}</div>
                  <div class="muted" style="font-size: 0.9rem; margin-top: 4px;">x{{ (int)($it->qty ?? 1) }}</div>
                </div>
                <div style="text-align:right">
                  <div class="muted" style="font-size:0.85rem; margin-bottom: 2px;">
                    ${{ number_format((float)($it->price ?? 0), 2) }} c/u
                  </div>
                  <div style="font-weight:700; color: var(--ink);">
                    ${{ number_format((float)($it->amount ?? ((float)($it->price ?? 0) * (int)($it->qty ?? 1))), 2) }}
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <p class="muted">No pudimos leer los artículos de esta orden (posiblemente la sesión ya expiró).</p>
        @endif

        {{-- Totales --}}
        <div class="line" style="margin-top: 16px;"></div>
        <div class="sum"><span>Subtotal</span><span style="color: var(--ink);">${{ number_format($subtotal,2) }} MXN</span></div>
        <div class="sum">
          <span>Envío</span>
          <span class="muted">{{ $shipAmount > 0 ? '$'.number_format($shipAmount,2).' MXN' : 'GRATIS' }}</span>
        </div>
        <div class="line" style="margin-bottom: 16px;"></div>
        <div class="sum" style="font-size:1.2rem; font-weight: 700; color: var(--ink);">
          <span>Total Pagado</span>
          <span>${{ number_format($total,2) }} MXN</span>
        </div>

        {{-- CTAs --}}
        <div class="sx-actions" style="margin-top: 40px;">
          <a href="{{ route('web.home') }}" class="btn btn-primary">Volver al Inicio</a>
          {{-- Si tienes ruta de órdenes, descomenta esto --}}
          {{-- <a href="{{ route('orders.index') }}" class="btn btn-outline">Ver Mis Pedidos</a> --}}
        </div>
      </div>

      {{-- Sidebar: comprobantes --}}
      <aside>
        <div class="sx-receipt">
          <h3 style="font-weight:700; font-size: 1.05rem; margin: 0 0 8px 0; color: var(--ink);">Comprobante de Orden</h3>
          <p class="muted" style="margin:0; line-height: 1.5; font-size: 0.95rem;">
            Se ha enviado una copia de tu recibo a tu correo electrónico asociado.
          </p>
        </div>

        {{-- FACTURA CFDI --}}
        <div class="sx-receipt" style="margin-top:16px">
          <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom: 12px;">
            <h3 style="font-weight:700; font-size: 1.05rem; margin: 0; color: var(--ink);">Facturación CFDI</h3>
          </div>
          
          @if($tagFolio || $tagUUID)
            <div style="margin-bottom: 16px;">
              <span class="sx-pill">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $tagFolio ?: 'Emitida' }}
              </span>
            </div>
          @endif

          {{-- Prioriza descarga vía rutas del controlador si tenemos $invoiceId --}}
          @if($invoiceId)
            <div style="display:flex; gap:8px; flex-direction: column;">
              <a class="btn btn-outline" href="{{ route('checkout.invoice.pdf', $invoiceId) }}" target="_blank" rel="noopener" style="width: 100%;">
                Descargar PDF
              </a>
              <a class="btn btn-outline" href="{{ route('checkout.invoice.xml', $invoiceId) }}" style="width: 100%;">
                Descargar XML
              </a>
            </div>
            <p class="muted" style="margin: 12px 0 0 0; font-size: 0.85rem; text-align: center;">Documentos con validez fiscal oficial.</p>

          {{-- Fallback: usa URLs guardadas en tu tabla ventas --}}
          @elseif($venta && ($facturaPDF || $facturaXML))
            <div style="display:flex; gap:8px; flex-direction: column;">
              @if($facturaPDF)
                <a class="btn btn-outline" href="{{ $facturaPDF }}" target="_blank" rel="noopener" style="width: 100%;">Descargar PDF</a>
              @endif
              @if($facturaXML)
                <a class="btn btn-outline" href="{{ $facturaXML }}" target="_blank" rel="noopener" style="width: 100%;">Descargar XML</a>
              @endif
            </div>
            <p class="muted" style="margin: 12px 0 0 0; font-size: 0.85rem; text-align: center;">Documentos con validez fiscal oficial.</p>

          {{-- Si ya hay folio/UUID pero aún no hay links, sugiere autorefresh --}}
          @elseif($venta && ($facturaId || $facturaUUID))
            <p class="muted" id="noFacturaYet" style="margin:0; font-size: 0.95rem; line-height: 1.5;">
              <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:var(--blue); margin-right:6px; animation: pulse 1.5s infinite;"></span>
              Procesando timbrado del SAT. En breves segundos podrás descargar tus archivos aquí.
            </p>

          {{-- Mensaje neutro si no solicitó factura --}}
          @else
            <p class="muted" style="margin:0; font-size: 0.95rem; line-height: 1.5;">
              Si solicitaste factura durante el checkout, tus enlaces de descarga aparecerán en esta sección en breve.
            </p>
          @endif
        </div>
      </aside>
    </div>
  </div>
</div>

<style>
  @keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
    100% { opacity: 1; transform: scale(1); }
  }
</style>

@push('scripts')
<script>
(() => {
  // Copiar ID de sesión
  const btn = document.getElementById('copyBtn');
  if (btn) {
    btn.addEventListener('click', async () => {
      const t = document.getElementById('sid')?.textContent || '';
      try { await navigator.clipboard.writeText(t); } catch {}
      const m = document.getElementById('copyMsg');
      if (m){ m.style.display='block'; setTimeout(()=>m.style.display='none', 2500); }
    });
  }

  // Autorefresh si estamos esperando timbrado (fallback Venta)
  if (document.getElementById('noFacturaYet')) {
    setTimeout(() => { window.location.reload(); }, 8000);
  }

  // Confetti liviano corporativo (Tonos azules y éxito)
  const cvs = document.getElementById('confetti');
  if (!cvs) return;
  const ctx = cvs.getContext('2d');
  const pieces = [];
  function resize(){ cvs.width = innerWidth; cvs.height = innerHeight; }
  addEventListener('resize', resize); resize();

  for (let i=0;i<100;i++){
    pieces.push({
      x: Math.random()*cvs.width,
      y: -20 - Math.random()*cvs.height,
      s: 4 + Math.random()*5,
      v: 1 + Math.random()*2.5,
      r: Math.random()*Math.PI,
      dr: (Math.random()-.5)*0.15
    });
  }
  let t0 = performance.now();
  function draw(t){
    const dt = (t - t0)/16; t0 = t;
    ctx.clearRect(0,0,cvs.width,cvs.height);
    pieces.forEach(p=>{
      p.y += p.v*dt; p.r += p.dr*dt; p.x += Math.sin(p.y*0.01)*0.5;
      if(p.y > cvs.height+20){ p.y = -20; p.x = Math.random()*cvs.width; }
      ctx.save(); ctx.translate(p.x,p.y); ctx.rotate(p.r);
      // Paleta corporativa: Azules, verdes, platas
      const palette = ['#007aff','#e6f0ff','#15803d','#e6ffe6','#ebebeb'];
      ctx.fillStyle = palette[(p.s|0) % palette.length];
      ctx.fillRect(-p.s/2,-p.s/2,p.s,p.s*0.6);
      ctx.restore();
    });
    requestAnimationFrame(draw);
  }
  requestAnimationFrame(draw);
})();
</script>
@endpush
@endsection