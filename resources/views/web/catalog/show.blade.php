@extends('layouts.web')
@section('title', $item->name)

@section('content')
<style>
  :root{
    --ink:#0e1726; --muted:#6b7280; --line:#e8eef6; --surface:#fff;
    --brand:#6ea8fe; --success:#16a34a; --warn:#eab308; --danger:#ef4444;
    --shadow:0 12px 30px rgba(13,23,38,.06);
  }

  .wrap{max-width:1100px;margin-inline:auto;padding-inline:16px}
  .grid{display:grid;gap:18px;grid-template-columns:repeat(12,1fr)}
  .card{background:var(--surface);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}
  .p16{padding:16px}

  /* ----- Galería ----- */
  .gal-main{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f6f8fc}
  .thumbs{display:flex;gap:10px;flex-wrap:wrap;padding:12px}
  .thumb{width:80px;height:80px;border-radius:12px;border:1px solid var(--line);object-fit:cover;cursor:pointer;opacity:.9;transition:transform .15s,opacity .15s}
  .thumb:hover{transform:translateY(-2px);opacity:1}
  .thumb.is-active{outline:2px solid var(--brand); opacity:1}

  /* ----- Texto / precios ----- */
  h1{margin:0 0 6px;font-weight:800;color:var(--ink);font-size:clamp(20px,3.2vw,28px)}
  .muted{color:var(--muted)}
  .price{font-weight:900;color:var(--ink);font-size:1.6rem}
  .sale{color:var(--success);font-weight:900;font-size:1.6rem}
  .old{color:var(--muted);text-decoration:line-through;margin-left:6px}
  .save{color:#166534;background:#ecfdf5;border:1px solid #bbf7d0;font-weight:800;border-radius:999px;padding:4px 10px;font-size:.85rem}

  /* ----- Controles ----- */
  .btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:12px;padding:12px 16px;font-weight:700;background:var(--brand);color:#0b1220;text-decoration:none;cursor:pointer;min-height:44px}
  .btn-ghost{background:#fff;border:1px solid var(--line);color:var(--ink)}
  .qty{width:100px;border:1px solid var(--line);border-radius:12px;padding:10px 12px;min-height:44px}

  /* ----- Badges & trust ----- */
  .badges{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0}
  .badge{font-weight:800;font-size:.78rem;padding:6px 10px;border-radius:999px;border:1px solid var(--line);background:#f8fafc}
  .badge--stock{background:#ecfdf5;border-color:#bbf7d0;color:#166534}
  .trust{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:12px}
  .trust .t{display:flex;align-items:center;gap:8px;border:1px dashed var(--line);border-radius:12px;padding:10px}
  .trust svg{width:18px;height:18px;color:#0ea5e9}

  /* ----- Tabla de especificaciones ----- */
  .specs{width:100%;border-collapse:collapse}
  .specs th,.specs td{padding:10px 12px;border-bottom:1px solid var(--line);text-align:left}
  .specs th{width:38%;color:#334155;font-weight:700}

  /* ----- Layout ----- */
  .colL{grid-column:span 6}
  .colR{grid-column:span 6}
  .colFull{grid-column:span 12}
  @media (max-width:900px){ .colL,.colR{grid-column:span 12} }

  /* ----- Sticky CTA móvil ----- */
  @media (max-width:780px){
    .sticky-buy{
      position:sticky; bottom:0; left:0; right:0; background:#fff; border-top:1px solid var(--line);
      padding:10px; display:flex; gap:10px; align-items:center; justify-content:space-between; z-index:10;
    }
    .sticky-buy .btn{flex:1}
  }

  /* ====== SIMILARES: estilo tipo ecommerce ====== */
  .sim-head{display:flex;align-items:flex-end;justify-content:space-between;gap:10px;margin:22px 0 12px}
  .sim-title{margin:0;font-weight:800;color:var(--ink);font-size:clamp(18px,3vw,22px)}
  .sim-grid{display:grid;gap:12px;grid-template-columns:repeat(12,1fr)}
  .sim-col{grid-column:span 3}
  @media (max-width:1100px){ .sim-col{grid-column:span 4} }
  @media (max-width:780px){
    .sim-grid{grid-auto-flow:column;grid-auto-columns:minmax(220px, 60%);overflow-x:auto;scroll-snap-type:x mandatory;padding-bottom:6px}
    .sim-col{grid-column:auto;scroll-snap-align:start}
  }
  .sim-card{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;box-shadow:var(--shadow);display:flex;flex-direction:column}
  .sim-img{width:100%;aspect-ratio:4/3;object-fit:cover;background:#f6f8fc}
  .sim-body{padding:12px;display:flex;flex-direction:column;gap:8px}
  .sim-name{font-weight:800;color:#0f172a;line-height:1.2;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0}
  .sim-price{font-weight:900}
  .sim-old{color:var(--muted);text-decoration:line-through;margin-left:6px}
  .sim-actions{display:flex;gap:8px;align-items:center;margin-top:auto}
  .sim-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);background:#fff;border-radius:10px;padding:8px 10px;font-weight:700;cursor:pointer}
  .sim-btn.primary{background:var(--brand);color:#0b1220;border-color:transparent}
</style>

@php
  $price = (float)($item->price ?? 0);
  $sale  = !is_null($item->sale_price) ? (float)$item->sale_price : null;
  $final = $sale ?? $price;
  $savePct = ($sale && $price>0) ? max(1, round(100 - (($sale/$price)*100))) : null;
  $monthly = $final > 0 ? round($final/12, 2) : 0;

  // Galería segura
  $images = [];
  if(is_array($item->images) && count($item->images)) $images = $item->images;
  if($item->image_url) array_unshift($images, $item->image_url);
  $images = array_values(array_unique(array_filter($images)));

  // ===== Productos similares =====
  $similars = \App\Models\CatalogItem::published()
      ->where('id','!=',$item->id)
      ->when(($item->category_id ?? null), function($q) use ($item){
          $q->where('category_id', $item->category_id);
      }, function($q) use ($item){
          if(!empty($item->brand)) $q->where('brand', $item->brand);
      })
      ->ordered()->take(12)->get();

  if($similars->isEmpty()){
      $similars = \App\Models\CatalogItem::published()->ordered()->take(12)->get();
  }

  $discountPct = function($p){
      if(is_null($p->sale_price) || !$p->price || $p->sale_price >= $p->price) return null;
      return max(1, round(100 - (($p->sale_price / $p->price) * 100)));
  };
@endphp

<div class="wrap">

  {{-- Migas mínimas --}}
  <div style="display:flex;align-items:center;gap:10px;margin:10px 0 14px;">
    <a class="btn-ghost btn" href="{{ route('web.catalog.index') }}">← Volver al catálogo</a>
  </div>

  <div class="grid">
    {{-- Galería --}}
    <div class="card colL">
      <img id="galMain" class="gal-main"
           src="{{ $images[0] ?? asset('images/placeholder.png') }}"
           alt="{{ $item->name }}"
           onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">

      @if(count($images) > 1)
        <div class="thumbs">
          @foreach($images as $i => $u)
            <img class="thumb {{ $i===0 ? 'is-active' : '' }}"
                 src="{{ $u }}" alt="Vista {{ $i+1 }}"
                 onerror="this.style.display='none'">
          @endforeach
        </div>
      @endif
    </div>

    {{-- Info / compra --}}
    <div class="card colR">
      <div class="p16">
        <h1>{{ $item->name }}</h1>
        <div class="muted">SKU: {{ $item->sku ?: '—' }} &middot; Marca: {{ $item->brand ?? '—' }}</div>

        <div class="badges">
          @if(($item->stock ?? 0) > 0)
            <span class="badge badge--stock">En stock ({{ $item->stock }})</span>
          @else
            <span class="badge" style="background:#fff7ed;border-color:#fed7aa;color:#9a3412">Sobre pedido</span>
          @endif
          <span class="badge">Entrega hoy en Toluca*</span>
          <span class="badge">Soporte técnico gratis</span>
          <span class="badge">Facturación inmediata</span>
        </div>

        <div style="margin:12px 0 14px; display:flex; align-items:baseline; gap:8px; flex-wrap:wrap;">
          @if($sale)
            <div class="sale">${{ number_format($sale,2) }}</div>
            <div class="old">${{ number_format($price,2) }}</div>
            @if($savePct)<span class="save">Ahorra {{ $savePct }}%</span>@endif
          @else
            <div class="price">${{ number_format($price,2) }}</div>
          @endif
        </div>

        <div class="muted" style="margin-bottom:10px;">
          Paga a 12 meses desde <strong>${{ number_format($monthly,2) }}</strong>*
        </div>

        @if($item->excerpt)
          <p class="muted" style="margin:0 0 8px">{{ $item->excerpt }}</p>
        @endif

        @if($item->description)
          <div style="margin-top:6px;">{!! nl2br(e($item->description)) !!}</div>
        @endif

        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <form action="{{ route('web.cart.add') }}" method="POST" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            @csrf
            <input type="hidden" name="catalog_item_id" value="{{ $item->id }}">
            <label class="muted" for="qty">Cantidad</label>
            <input id="qty" class="qty" type="number" name="qty" min="1" value="1">
            <button class="btn" type="submit">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 7H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
              Agregar al carrito
            </button>
          </form>

          {{-- COMPRAR AHORA (Stripe Checkout) --}}
          <a class="btn" href="#" onclick="buyNowStripe({{ $item->id }}); return false;">Comprar ahora</a>

          <a class="btn-ghost btn" href="{{ route('web.contacto') }}">Cotizar / Asesoría</a>
        </div>

        <div class="trust" style="margin-top:18px;">
          <div class="t">
            <svg viewBox="0 0 24 24" fill="none"><path d="M20 12H4m0 0 6 6M4 12l6-6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div><strong>Envío nacional</strong><div class="muted">2–5 días. Mismo día en Toluca*</div></div>
          </div>
          <div class="t">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 6v12m6-6H6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <div><strong>Garantía</strong><div class="muted">12 meses / DOA 7 días</div></div>
          </div>
          <div class="t">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 3l8 4v6c0 5-3.6 7.7-8 8-4.4-.3-8-3-8-8V7l8-4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
            <div><strong>Pagos seguros</strong><div class="muted">Tarjeta, SPEI y meses</div></div>
          </div>
        </div>

        {{-- Sticky CTA móvil --}}
        <div class="sticky-buy" aria-hidden="true">
          <div style="font-weight:900">{{ $sale ? '$'.number_format($sale,2) : '$'.number_format($price,2) }}</div>
          <a class="btn" href="#" onclick="buyNowStripe({{ $item->id }}); return false;">Comprar ahora</a>
        </div>
      </div>
    </div>

    {{-- Especificaciones (si existen) --}}
    @php
      $specs = is_array($item->specs ?? null) ? $item->specs : [];
    @endphp
    @if(count($specs))
      <div class="card colFull">
        <div class="p16">
          <h2 style="margin:0 0 10px;font-weight:800;color:var(--ink)">Especificaciones</h2>
          <table class="specs">
            <tbody>
              @foreach($specs as $k => $v)
                <tr>
                  <th>{{ $k }}</th>
                  <td>{{ is_array($v) ? implode(', ', $v) : $v }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
          <p class="muted" style="margin-top:10px;font-size:.9rem">*Envío mismo día en Toluca sujeto a cobertura y horario de corte.</p>
        </div>
      </div>
    @endif
  </div>

  {{-- ================== PRODUCTOS SIMILARES ================== --}}
  @if($similars->count())
    <div style="margin-top:24px">
      <div class="sim-head">
        <h2 class="sim-title">Productos similares</h2>
        <a class="btn-ghost btn" href="{{ route('web.catalog.index') }}">Ver más</a>
      </div>

      <div class="sim-grid">
        @foreach($similars as $p)
          @php $off = $discountPct($p); @endphp
          <div class="sim-col">
            <article class="sim-card">
              <a href="{{ route('web.catalog.show', $p) }}" aria-label="Ver {{ $p->name }}">
                <img class="sim-img"
                     src="{{ $p->image_url ?: asset('images/placeholder.png') }}"
                     alt="{{ $p->name }}"
                     loading="lazy"
                     onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
              </a>
              <div class="sim-body">
                <h3 class="sim-name">
                  <a href="{{ route('web.catalog.show', $p) }}" style="color:inherit;text-decoration:none">{{ $p->name }}</a>
                </h3>

                <div>
                  @if(!is_null($p->sale_price))
                    <span class="sim-price" style="color:var(--success)">${{ number_format($p->sale_price,2) }}</span>
                    <span class="sim-old">${{ number_format($p->price,2) }}</span>
                    @if($off)<span class="badge" style="margin-left:6px;background:#fff7ed;border-color:#fed7aa;color:#9a3412">-{{ $off }}%</span>@endif
                  @else
                    <span class="sim-price">${{ number_format($p->price,2) }}</span>
                  @endif
                </div>

                <div class="sim-actions">
                  <form action="{{ route('web.cart.add') }}" method="POST" style="display:inline-flex;gap:6px;align-items:center">
                    @csrf
                    <input type="hidden" name="catalog_item_id" value="{{ $p->id }}">
                    <button class="sim-btn primary" type="submit">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 7H6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      Agregar
                    </button>
                  </form>
                  <a class="sim-btn" href="{{ route('web.catalog.show', $p) }}">Ver</a>
                </div>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    </div>
  @endif
  {{-- =============== /PRODUCTOS SIMILARES ================== --}}

</div>

{{-- JS: mini-galería + feedback --}}
<script>
  // Cambiar imagen principal al hacer click en miniatura
  document.querySelectorAll('.thumb').forEach((t)=>{
    t.addEventListener('click', ()=>{
      const main = document.getElementById('galMain');
      document.querySelectorAll('.thumb').forEach(x=>x.classList.remove('is-active'));
      t.classList.add('is-active');
      main.src = t.src;
    });
  });

  // Evitar valores inválidos en qty
  const qty = document.getElementById('qty');
  if(qty){
    qty.addEventListener('input', () => {
      if(!qty.value || qty.value < 1) qty.value = 1;
    });
  }
</script>

{{-- Stripe: Comprar ahora (Checkout) con cookies (CSRF 419 fix) --}}
<script>
  async function buyNowStripe(itemId){
    try{
      const qty = document.getElementById('qty')?.value || 1;
      const res = await fetch("{{ url('/checkout/item') }}/" + itemId, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        credentials: "same-origin", // importante: envía cookie de sesión para CSRF
        body: JSON.stringify({ qty })
      });

      const data = await res.json().catch(()=> ({}));
      if(!res.ok){
        alert(data?.error || 'Error iniciando pago.');
        return;
      }
      if(data.url){
        window.location = data.url;
      }else{
        alert('Respuesta inesperada del servidor.');
      }
    }catch(e){
      alert('No se pudo iniciar el pago.');
    }
  }
</script>
@endsection
