{{-- resources/views/checkout/success.blade.php --}}
@extends('layouts.web')
@section('title','Pago recibido')

@section('content')
@php
  $sessionId = $sessionId ?? request('session_id');
  $cart      = (array) session('cart', []);
  $subtotal  = array_reduce($cart, fn($c,$r)=> $c + (float)($r['price']??0) * (int)($r['qty']??1), 0);
  $shipping  = (array) session('checkout.shipping', ['price'=>0]);
  $total     = $subtotal + (float)($shipping['price'] ?? 0);
@endphp

<style>
  .sx-wrap{max-width:980px;margin:0 auto}
  .sx-card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;box-shadow:0 12px 30px rgba(2,8,23,.06);overflow:hidden}
  .sx-hero{padding:28px 24px;background: radial-gradient(1200px 400px at -10% -50%, #e6eeff 0%, transparent 60%),
                            radial-gradient(900px 300px at 120% 10%, #e0f7ff 0%, transparent 60%);}
  .sx-title{margin:0;font-size:2.1rem;font-weight:900;letter-spacing:.2px;color:#0f172a;display:flex;gap:8px;align-items:center}
  .sx-sub{color:#475569;margin-top:6px}
  .sx-body{padding:18px 24px;display:grid;grid-template-columns:2fr 1fr;gap:18px}
  @media(max-width: 980px){ .sx-body{grid-template-columns:1fr}}
  .btn{display:inline-flex;align-items:center;gap:10px;justify-content:center;border-radius:12px;padding:10px 14px;
       font-weight:800;text-decoration:none;border:1px solid #dbe2ea;background:#fff;color:#0f172a;cursor:pointer}
  .btn-primary{background:#0f3bd6;border-color:#0f3bd6;color:#fff}
  .btn-ghost{background:#f8fafc}
  .muted{color:#64748b}
  .sum{display:flex;justify-content:space-between;margin:8px 0;font-weight:800}
  .line{border:0;border-top:1px solid #eef2f7;margin:12px 0}
  .sx-id{display:flex;gap:10px;align-items:center;background:#f1f5ff;border:1px dashed #c4d1ff;color:#1e293b;
         padding:10px 12px;border-radius:10px;word-break:break-all}
  .sx-badge{display:inline-flex;align-items:center;gap:8px;background:#e8fff3;border:1px solid #baf7d4;color:#065f46;
            font-weight:800;border-radius:999px;padding:6px 10px}
  .sx-grid-items{display:grid;gap:10px}
  .sx-item{display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9}
  .sx-receipt{background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px}
  canvas#confetti{position:fixed;inset:0;pointer-events:none;z-index:40}
</style>

<canvas id="confetti"></canvas>

<div class="sx-wrap">
  <div class="sx-card">
    <div class="sx-hero">
      <div class="sx-badge">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12l4 4L19 6" stroke="#065f46" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Pago recibido
      </div>
      <h1 class="sx-title">
        ¡Gracias por tu compra!
        <span aria-hidden="true">🎉</span>
      </h1>
      <p class="sx-sub">Hemos registrado tu pago correctamente. Pronto recibirás un correo de confirmación.</p>
    </div>

    <div class="sx-body">
      {{-- Columna principal --}}
      <div>
        {{-- ID de sesión Stripe --}}
        @if($sessionId)
        <div>
          <label class="muted" style="font-weight:700">ID de sesión de Stripe</label>
          <div class="sx-id" id="idBox">
            <code id="sid">{{ $sessionId }}</code>
            <button class="btn btn-ghost" id="copyBtn" type="button" title="Copiar ID">Copiar</button>
          </div>
          <div class="muted" id="copyMsg" style="display:none;margin-top:6px">¡Copiado al portapapeles!</div>
        </div>
        @endif

        {{-- Resumen del pedido --}}
        <div class="line"></div>
        <h3 style="margin:0 0 6px;font-weight:900">Tu pedido</h3>
        @if(count($cart))
          <div class="sx-grid-items">
            @foreach($cart as $row)
              <div class="sx-item">
                <img src="{{ $row['image'] ?? asset('images/placeholder.png') }}" alt="" style="width:56px;height:56px;border-radius:10px;border:1px solid #e5e7eb;object-fit:cover">
                <div>
                  <div style="font-weight:800">{{ $row['name'] ?? 'Producto' }}</div>
                  <div class="muted">x{{ $row['qty'] ?? 1 }}</div>
                </div>
                <div style="font-weight:900">${{ number_format(($row['price'] ?? 0)*($row['qty'] ?? 1),2) }}</div>
              </div>
            @endforeach
          </div>
        @else
          <p class="muted">No pudimos leer los artículos del carrito (posiblemente ya fue vaciado).</p>
        @endif

        {{-- Totales --}}
        <div class="line"></div>
        <div class="sum"><span>Subtotal</span><span>${{ number_format($subtotal,2) }}</span></div>
        <div class="sum"><span>Envío</span><span>{{ ($shipping['price'] ?? 0) > 0 ? '$'.number_format($shipping['price'],2) : '—' }}</span></div>
        <div class="line"></div>
        <div class="sum" style="font-size:1.1rem"><span>Total pagado</span><span>${{ number_format($total,2) }}</span></div>

        {{-- CTA --}}
        <div class="line"></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px">
          <a href="{{ route('web.home') }}" class="btn btn-primary">Seguir comprando</a>
          <a href="{{ route('web.cart.index') }}" class="btn">Ver carrito</a>
          {{-- Si tienes una ruta de pedidos del usuario, cámbiala aquí --}}
          {{-- <a href="{{ route('orders.index') }}" class="btn">Mis pedidos</a> --}}
        </div>
      </div>

      {{-- Sidebar / Sugerencias --}}
      <aside>
        <div class="sx-receipt">
          <div style="font-weight:900;margin-bottom:6px">Comprobante</div>
          <p class="muted" style="margin:0">
            Te enviaremos el comprobante al correo asociado a tu cuenta.
            Si no lo ves en unos minutos, revisa tu carpeta de spam.
          </p>
        </div>

        <div class="sx-receipt" style="margin-top:12px">
          <div style="font-weight:900;margin-bottom:6px">¿Factura?</div>
          <p class="muted" style="margin:0">
            Si necesitas CFDI, podrás capturar tus datos en el paso de Facturación de tu siguiente compra.
          </p>
        </div>
      </aside>
    </div>
  </div>
</div>

@push('scripts')
<script>
(() => {
  // Copiar ID
  const btn = document.getElementById('copyBtn');
  if (btn) {
    btn.addEventListener('click', async () => {
      const t = document.getElementById('sid')?.textContent || '';
      try { await navigator.clipboard.writeText(t); } catch {}
      const m = document.getElementById('copyMsg');
      if (m){ m.style.display='block'; setTimeout(()=>m.style.display='none', 2200); }
    });
  }

  // Confetti liviano (canvas)
  const cvs = document.getElementById('confetti');
  const ctx = cvs.getContext('2d');
  const pieces = [];
  function resize(){ cvs.width = innerWidth; cvs.height = innerHeight; }
  addEventListener('resize', resize); resize();

  for(let i=0;i<120;i++){
    pieces.push({
      x: Math.random()*cvs.width,
      y: -20 - Math.random()*cvs.height,
      s: 4+Math.random()*6,
      v: 1+Math.random()*3,
      r: Math.random()*Math.PI,
      dr: (Math.random()-.5)*0.2
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
      ctx.fillStyle = ['#0f3bd6','#22c55e','#f59e0b','#ef4444','#06b6d4'][p.s|0 % 5];
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
