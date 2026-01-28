@extends('layouts.web')
@section('title','Mis Favoritos')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
  /* ========= Tokens mint ========= */
  #favwrap{
    --mint:#48cfad; --mint-dark:#34c29e;
    --ink:#0b1220; --muted:#64748b; --line:#e8eef6; --card:#ffffff;
    position:relative; min-height:100vh;
    margin:0;
    font-family:"Quicksand", system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, "Helvetica Neue", Arial;
    color:var(--ink);
  }
  #favwrap a{ text-decoration:none; color:inherit; }

  /* ========= Fondo degradado ========= */
  #favwrap .bg-grad{
    position:fixed; inset:0; z-index:-1; pointer-events:none;
    background:
      radial-gradient(900px 560px at 50% -210px, rgba(255,255,255,.72), transparent 60%),
      linear-gradient(180deg,#f3faea 0%, #eef5ff 48%, #ecebff 100%);
    filter:saturate(1.02);
  }

  /* ========= Contenedor y encabezado ========= */
  #favwrap .container{max-width:1320px;margin:clamp(44px,6vw,76px) auto 60px;padding:0 16px}
  #favwrap .head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
  #favwrap .title{margin:0;font-weight:800;color:var(--ink);font-size:clamp(22px,3.2vw,36px);letter-spacing:-.2px;line-height:1.1}
  #favwrap .chips{display:flex;gap:8px;flex-wrap:wrap}
  #favwrap .chip{
    display:inline-flex;align-items:center;gap:8px;padding:9px 12px;border-radius:999px;
    background:rgba(255,255,255,.60); border:1px solid rgba(232,238,246,.95);
    backdrop-filter: blur(10px);
    font-weight:800;color:var(--ink)
  }
  #favwrap .chip .dot{width:8px;height:8px;border-radius:50%;background:var(--mint);box-shadow:0 0 0 3px rgba(72,207,173,.16)}

  /* ========= Grid de cards ========= */
  #favwrap .grid{display:grid;grid-template-columns:repeat( auto-fill, minmax(260px,1fr)); gap:18px}

  /* Card base + hover */
  #favwrap .card{
    position:relative; background:var(--card); border:1px solid rgba(232,238,246,.95); border-radius:18px; overflow:hidden;
    box-shadow:0 14px 34px rgba(2,8,23,.10); display:flex; flex-direction:column;
    transition: border-color .18s ease, box-shadow .22s ease, transform .18s ease;
    will-change: transform, box-shadow;
  }

  /* Imagen: usa photo_1/2/3 */
  #favwrap .img{
    aspect-ratio: 4 / 5;
    background:#fff;
    display:grid; place-items:center;
    overflow:hidden; padding:10px;
  }
  #favwrap .img img{
    width:100%; height:100%;
    object-fit:contain;
    display:block; transition: transform .35s ease;
  }

  #favwrap .card:hover,
  #favwrap .card:focus-within{
    transform: translateY(-4px);
    border-color:#dfe6f0;
    box-shadow: 0 0 0 3px rgba(72,207,173,.08), 0 22px 54px rgba(2,8,23,.16);
  }
  #favwrap .card:hover .img img,
  #favwrap .card:focus-within .img img{
    transform: scale(1.02);
  }
  #favwrap .card:hover .name{ color:var(--mint-dark); }

  @media (prefers-reduced-motion: reduce){
    #favwrap .card, #favwrap .img img{ transition:none }
  }

  #favwrap .body{padding:12px 12px 14px}
  #favwrap .name{
    font-weight:800;font-size:15px;line-height:1.25;margin:4px 0 10px;color:var(--ink);
    transition:color .18s ease;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
  }
  #favwrap .row{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
  #favwrap .price{font-weight:900;font-size:15px;color:#0f172a}

  /* ========= Badge "Quitar" flotante ========= */
  #favwrap .rmv-badge{
    position:absolute; top:10px; right:10px; z-index:2;
    display:inline-flex; align-items:center; gap:6px;
    padding:6px 10px; border-radius:999px; border:1px solid #ffe1ea;
    background:rgba(255,255,255,.92);
    backdrop-filter: blur(10px);
    color:#cc4b4b; font-weight:800; font-size:12px;
    cursor:pointer; transition: box-shadow .18s ease, transform .05s ease, border-color .18s ease;
  }
  #favwrap .rmv-badge:hover{ box-shadow:0 10px 22px rgba(2,8,23,.12); border-color:#ffc9d6 }
  #favwrap .rmv-badge:active{ transform: translateY(1px) }
  #favwrap .rmv-badge svg{width:12px;height:12px}

  #favwrap .empty{
    padding:40px;text-align:center;color:var(--muted);
    background:rgba(255,255,255,.55);
    border:1px dashed #dbe5f0;border-radius:18px
  }

  /* ========= SweetAlert2 tema mint minimal ========= */
  .swal2-popup.swal2-mint{border-radius:16px; border:1px solid rgba(232,238,246,.95); box-shadow:0 18px 48px rgba(2,8,23,.14)}
  .swal2-title{font-weight:900; color:var(--ink)}
  .swal2-html-container{color:var(--muted)}
  .swal2-actions{gap:10px}

  .swal2-actions .btn-mint{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:140px; padding:10px 16px; border-radius:999px;
    background:var(--mint) !important; color:#fff !important; border:0;
    font-weight:900; box-shadow:0 12px 22px rgba(72,207,173,.26);
  }
  .swal2-actions .btn-mint:hover{ background:var(--mint-dark) !important }
  .swal2-actions .btn-ghost{
    display:inline-flex; align-items:center; justify-content:center;
    min-width:120px; padding:10px 16px; border-radius:999px;
    background:#fff; color:var(--ink); border:1px solid rgba(232,238,246,.95);
    font-weight:900;
  }
  .swal2-actions .btn-ghost:hover{ border-color:#dfe6f0 }
</style>

@php
  // === helper: convierte photo_1/2/3 a URL usable ===
  $imgUrl = function($raw){
    if(!$raw || !is_string($raw) || trim($raw)==='') return null;
    $raw = trim($raw);
    if (\Illuminate\Support\Str::startsWith($raw, ['http://','https://'])) return $raw;
    if (\Illuminate\Support\Str::startsWith($raw, ['storage/'])) return asset($raw);
    return \Illuminate\Support\Facades\Storage::url($raw);
  };

  // === toma la primera foto disponible del item ===
  $pickPhotoUrl = function($p) use ($imgUrl){
    foreach([$p->photo_1 ?? null, $p->photo_2 ?? null, $p->photo_3 ?? null] as $c){
      $u = $imgUrl($c);
      if($u) return $u;
    }
    // fallback por si aún existe image_url en algunos registros
    if(!empty($p->image_url)) return $p->image_url;
    return asset('images/placeholder.png');
  };
@endphp

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

            $thumb = $pickPhotoUrl($item);
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
              <img src="{{ $thumb }}" alt="{{ $item->name }}"
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
          iconColor: '#34c29e',
          showCancelButton: true,
          buttonsStyling: false,
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
