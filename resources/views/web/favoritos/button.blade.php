@php
  /** @var \App\Models\CatalogItem $item */
  $isActive = $isActive ?? (auth()->check() && auth()->user()->favorites->contains($item->id ?? 0));
@endphp

<style>
  /* ====== estilos aislados del botón de favorito ====== */
  .fav-btn{
    --ink:#1b2550; --muted:#8a93a6; --brand:#a3d5ff; --on:#ff7aa2;
    all:unset; display:inline-flex; align-items:center; gap:8px;
    padding:8px 12px; border:1px solid #e7ecf5; border-radius:999px;
    background:#fff; cursor:pointer; user-select:none; transition:.2s ease;
    box-shadow:0 6px 20px rgba(2,8,23,.06);
  }
  .fav-btn:hover{ background:#fff; color:#000; box-shadow:0 10px 28px rgba(2,8,23,.10); }
  .fav-btn .heart{ width:18px; height:18px; display:inline-block; position:relative; transform:translateY(1px); }
  .fav-btn .heart::before{
    content:""; position:absolute; inset:0; 
    mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path d="M12.1 8.64l-.1.1-.1-.1C10.14 6.94 7.1 7.24 5.73 9.17c-1.07 1.52-.86 3.64.51 4.9l4.98 4.54c.46.42 1.2.42 1.66 0l4.98-4.54c1.37-1.26 1.58-3.38.51-4.9-1.37-1.93-4.41-2.23-6.17-.53z" stroke="black" stroke-width="1.5"/></svg>') no-repeat center / contain;
    background: currentColor;
  }
  .fav-btn:not(.active){ color: var(--muted); }
  .fav-btn.active{ color: var(--on); border-color: #ffd3e1; background:#fff; }
  .fav-btn .txt{ font:600 12px/1.1 system-ui, -apple-system, "Inter", "Segoe UI", Roboto, sans-serif; letter-spacing:.2px; }
</style>

@guest
  <a href="{{ route('login') }}" class="fav-btn" aria-label="Inicia sesión para agregar a favoritos">
    <span class="heart" aria-hidden="true"></span>
    <span class="txt">Favorito</span>
  </a>
@else
  <button class="fav-btn {{ $isActive ? 'active' : '' }}"
          data-fav
          data-id="{{ $item->id }}"
          data-url="{{ route('favoritos.toggle', $item) }}"
          aria-pressed="{{ $isActive ? 'true':'false' }}"
          aria-label="Agregar a favoritos">
    <span class="heart" aria-hidden="true"></span>
    <span class="txt">{{ $isActive ? 'En favoritos' : 'Favorito' }}</span>
  </button>

  {{-- Script se inyecta una sola vez --}}
  @once
    <script>
      (function(){
        if (window.__FavInit) return; window.__FavInit = true;

        const TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        async function toggleFav(btn){
          const url = btn.dataset.url;
          btn.disabled = true;
          try{
            const r = await fetch(url, {
              method:'POST',
              headers:{
                'X-CSRF-TOKEN': TOKEN,
                'X-Requested-With':'XMLHttpRequest',
                'Accept':'application/json'
              }
            });
            if(r.status === 401){
              window.location.href = "{{ route('login') }}";
              return;
            }
            const data = await r.json();
            const active = data.status === 'added';
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            const txt = btn.querySelector('.txt');
            if(txt) txt.textContent = active ? 'En favoritos' : 'Favorito';
            btn.disabled = false;
          }catch(e){
            console.error('Fav error', e);
            btn.disabled = false;
          }
        }

        document.addEventListener('click', (ev)=>{
          const btn = ev.target.closest('[data-fav]');
          if(!btn) return;
          ev.preventDefault();
          toggleFav(btn);
        }, {passive:false});
      })();
    </script>
  @endonce
@endguest
