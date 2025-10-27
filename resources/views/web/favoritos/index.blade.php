@extends('layouts.web')
@section('title','Mis Favoritos')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  /* ========= Tokens mint ========= */
  #favwrap{
    --mint:#48cfad; --mint-dark:#34c29e;
    --ink:#2a2e35; --muted:#7a7f87; --line:#e9ecef; --card:#ffffff;
    position:relative; min-height:100vh; font-family:"Open Sans",sans-serif; color:var(--ink);
  }

  /* ========= Fondo degradado ========= */
  #favwrap .bg-grad{
    position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHZpZXdCb3g9JzAgMCAxMDAgMTAwJz4KICA8ZGVmcz4KICAgIDxsaW5lYXJHcmFkaWVudCBpZD0iZyIgeDE9IjAiIHkxPSIwIiB4Mj0iMCIgeTI9IjEiPgogICAgICA8c3RvcCBvZmZzZXQ9IjAiIHN0b3AtY29sb3I9IiM5MGVlZTYiLz4KICAgICAgPHN0b3Agb2Zmc2V0PSI1NSUiIHN0b3AtY29sb3I9IiNmZmZmZmYiLz4KICAgICAgPHN0b3Agb2Zmc2V0PSIxMDAiIHN0b3AtY29sb3I9IiNmZmQxYjUiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgPC9kZWZzPgogIDxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSJ1cmwoI2cpIi8+Cjwvc3ZnPg==")
        center / cover no-repeat fixed,
      radial-gradient(1200px 700px at 50% -220px, rgba(255,255,255,.55), transparent 60%),
      radial-gradient(1200px 700px at 50% calc(100% + 220px), rgba(255,255,255,.50), transparent 60%);
    filter:saturate(1.02);
  }

  /* ========= Contenedor y encabezado ========= */
  #favwrap .container{max-width:1320px;margin:clamp(48px,6vw,80px) auto 60px;padding:0 16px}
  #favwrap .head{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}
  #favwrap .title{margin:0;font-weight:700;color:var(--ink);font-size:clamp(22px,3.2vw,36px);letter-spacing:-.2px}
  #favwrap .chips{display:flex;gap:8px;flex-wrap:wrap}
  #favwrap .chip{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#f7fbff;border:1px solid var(--line);font-weight:700;color:var(--ink)}
  #favwrap .chip .dot{width:8px;height:8px;border-radius:50%;background:var(--mint);box-shadow:0 0 0 3px rgba(72,207,173,.16)}

  /* ========= Grid de cards ========= */
  #favwrap .grid{display:grid;grid-template-columns:repeat( auto-fill, minmax(260px,1fr)); gap:18px}

  /* Card base + hover */
  #favwrap .card{
    position:relative; background:var(--card); border:1px solid var(--line); border-radius:16px; overflow:hidden;
    box-shadow:0 12px 30px rgba(18,38,63,.10); display:flex; flex-direction:column;
    transition: border-color .18s ease, box-shadow .22s ease, transform .18s ease;
    will-change: transform, box-shadow;
  }

  /* Imagen: más alta y sin recorte */
  #favwrap .img{
    aspect-ratio: 4 / 5;           /* más alta que 4/3 */
    background:#fff;
    display:grid; place-items:center;
    overflow:hidden; padding:8px;  /* margen interno para contain */
  }
  #favwrap .img img{
    width:100%; height:100%;
    object-fit:contain;            /* sin recortes */
    display:block; transition: transform .35s ease;
  }

  #favwrap .card:hover,
  #favwrap .card:focus-within{
    transform: translateY(-4px);
    border-color:#dfe3e8;
    box-shadow: 0 0 0 3px rgba(72,207,173,.08), 0 18px 44px rgba(18,38,63,.16);
  }
  #favwrap .card:hover .img img,
  #favwrap .card:focus-within .img img{
    transform: scale(1.02);        /* leve zoom sin cortar */
  }
  #favwrap .card:hover .name{ color:var(--mint-dark); }

  @media (prefers-reduced-motion: reduce){
    #favwrap .card, #favwrap .img img{ transition:none }
  }

  #favwrap .body{padding:12px 12px 14px}
  #favwrap .name{font-weight:700;font-size:15px;line-height:1.25;margin:4px 0 8px;color:var(--ink);text-decoration:none;transition:color .18s ease}
  #favwrap .row{display:flex;align-items:center;justify-content:space-between;gap:8px}
  #favwrap .price{font-weight:700;font-size:15px;color:#0f172a}

  /* ========= Badge "Quitar" flotante ========= */
  #favwrap .rmv-badge{
    position:absolute; top:10px; right:10px; z-index:2;
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 10px; border-radius:999px; border:1px solid #ffe1ea;
    background:#fff; color:#cc4b4b; font-weight:700; font-size:12px;
    cursor:pointer; transition: box-shadow .18s ease, transform .05s ease, border-color .18s ease;
  }
  #favwrap .rmv-badge:hover{ box-shadow:0 10px 22px rgba(18,38,63,.12); border-color:#ffc9d6 }
  #favwrap .rmv-badge:active{ transform: translateY(1px) }
  #favwrap .rmv-badge svg{width:12px;height:12px}

  #favwrap .empty{padding:40px;text-align:center;color:var(--muted);background:rgba(255,255,255,.55);border:1px dashed #e5e9ee;border-radius:16px}

  /* ========= SweetAlert2 tema mint minimal (botones visibles) ========= */
  .swal2-popup.swal2-mint{border-radius:16px; border:1px solid var(--line); box-shadow:0 18px 48px rgba(18,38,63,.14)}
  .swal2-title{font-weight:800; color:var(--ink)}
  .swal2-html-container{color:var(--muted)}
  .swal2-actions{gap:10px}
  /* estilos de botones usando clases personalizadas, sin depender de .swal2-confirm */
  .swal2-actions .btn-mint{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:140px; padding:10px 16px; border-radius:999px;
    background:var(--mint) !important; color:#fff !important; border:0;
    font-weight:700; box-shadow:0 12px 22px rgba(72,207,173,.26);
  }
  .swal2-actions .btn-mint:hover{ background:var(--mint-dark) !important }
  .swal2-actions .btn-ghost{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:120px; padding:10px 16px; border-radius:999px;
    background:#fff; color:var(--ink); border:1px solid var(--line);
    font-weight:700;
  }
  .swal2-actions .btn-ghost:hover{ border-color:#dfe3e8 }
</style>

<div id="favwrap">
  <div class="bg-grad" aria-hidden="true"></div>

  <div class="container">
    <div class="head">
      <h1 class="title">Mis Favoritos</h1>
      <div class="chips" aria-label="Fortalezas">
        <span class="chip"><i class="dot"></i> Factura inmediata</span>
        <span class="chip"><i class="dot"></i> Envío rápido</span>
      </div>
    </div>

    @if($items->count() === 0)
      <div class="empty">Aún no tienes productos en favoritos.</div>
    @else
      <div class="grid" id="fav-grid">
        @foreach($items as $item)
          @php
            $productUrl = \Illuminate\Support\Facades\Route::has('web.catalog.show')
              ? route('web.catalog.show', $item)
              : (\Illuminate\Support\Facades\Route::has('web.producto.show')
                  ? route('web.producto.show', $item->slug ?? $item->id)
                  : url('/catalogo/'.($item->slug ?? $item->id)));
          @endphp

          <article class="card" id="fav-card-{{ $item->id }}">
            <!-- Badge quitar -->
            <button
              type="button"
              class="rmv-badge"
              data-url="{{ route('favoritos.destroy',$item) }}"
              data-id="{{ $item->id }}"
              data-name="{{ $item->name }}"
              aria-label="Quitar de favoritos">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12"/>
              </svg>
              Quitar
            </button>

            <a class="img" href="{{ $productUrl }}">
              <img src="{{ $item->image_url ?? asset('images/placeholder.png') }}" alt="{{ $item->name }}"
                   onerror="this.onerror=null;this.src='{{ asset('images/placeholder.png') }}'">
            </a>

            <div class="body">
              <a href="{{ $productUrl }}" class="name">{{ $item->name }}</a>

              <div class="row">
                <div class="price">${{ number_format($item->sale_price ?? $item->price ?? 0, 2) }} MXN</div>
                @include('web.favoritos.button', ['item'=>$item, 'isActive'=>true])
              </div>
            </div>
          </article>
        @endforeach
      </div>

      <div style="margin-top:16px;">
        {{ $items->links() }}
      </div>
    @endif
  </div>
</div>

<script>
  // SweetAlert2 confirmación y eliminación
  (function(){
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.querySelectorAll('.rmv-badge').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const url  = btn.dataset.url;
        const id   = btn.dataset.id;
        const name = btn.dataset.name || 'este producto';
        const card = document.getElementById('fav-card-'+id);

        const ans = await Swal.fire({
          title: 'Quitar de favoritos',
          html: `¿Seguro que deseas quitar<br><strong>${name}</strong>?`,
          icon: 'warning',
          iconColor: getComputedStyle(document.documentElement).getPropertyValue('--mint-dark') || '#34c29e',
          showCancelButton: true,
          buttonsStyling: false,     // usamos nuestras clases
          customClass: {
            popup: 'swal2-mint',
            confirmButton: 'btn-mint',
            cancelButton: 'btn-ghost'
          },
          confirmButtonText: 'Sí, quitar',
          cancelButtonText: 'Cancelar'
        });

        if(!ans.isConfirmed) return;

        try{
          const res = await fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': token,
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/x-www-form-urlencoded',
              'Accept': 'application/json'
            },
            body: new URLSearchParams({ _method: 'DELETE' })
          });
          if(!res.ok) throw new Error('No se pudo eliminar');

          // Quitar del DOM
          card?.parentNode?.removeChild(card);

          // Si no quedan, mensaje vacío
          const grid = document.getElementById('fav-grid');
          if(grid && grid.children.length === 0){
            grid.outerHTML = '<div class="empty">Aún no tienes productos en favoritos.</div>';
          }

          // Toast
          Swal.fire({
            toast:true, position:'top-end', showConfirmButton:false, timer:1600, icon:'success',
            title:'Eliminado de favoritos', buttonsStyling:false,
            customClass:{ popup:'swal2-mint' }
          });

        }catch(e){
          Swal.fire({
            icon:'error', title:'Ups', text:'No se pudo quitar. Inténtalo de nuevo.',
            buttonsStyling:false, customClass:{ popup:'swal2-mint', confirmButton:'btn-mint' }
          });
        }
      });
    });
  })();
</script>
@endsection
