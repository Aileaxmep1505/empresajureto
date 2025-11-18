@extends('layouts.app')
@section('title','Publicaciones')
@section('content')

@php
use App\Models\Post;

// Tomamos directamente los posts de la base de datos (todos)
// Si quieres limitar por filtros ya aplicados en el controlador, dime y lo uso.
$photos     = Post::where('tipo', 'foto')->orderBy('fecha', 'desc')->get();
$videos     = Post::where('tipo', 'video')->orderBy('fecha', 'desc')->get();
$documents  = Post::where('tipo', 'documento')->orderBy('fecha', 'desc')->get();
@endphp

<h1 class="pj-title">Publicaciones</h1>

<!-- FILTROS (mantengo tu formulario para usar el controlador) -->
<form method="GET" class="pj-filters">
    <input type="number" name="year" placeholder="Año" value="{{ request('year') }}">
    <input type="number" name="month" placeholder="Mes" value="{{ request('month') }}">
    <input type="number" name="day" placeholder="Día" value="{{ request('day') }}">
    <input type="text" name="empresa" placeholder="Empresa" value="{{ request('empresa') }}">
    <button type="submit">Filtrar</button>
    <a href="{{ route('posts.create') }}" class="pj-create">Crear Publicación</a>
</form>

<!-- BENTO GRID enfocado en lo subido desde BD -->
<div class="pj-bento-grid">

  <!-- PHOTOS: marquee con mini-thumbs (desde BD) -->
  <article class="pj-bento pj-files">
    <div class="pj-bento-content">
      <div class="pj-bento-icon">
        <svg viewBox="0 0 24 24" width="34" height="34"><path fill="currentColor" d="M21 19V7a2 2 0 0 0-2-2h-3.17l-1.83-2H10L8.17 5H5a2 2 0 0 0-2 2v12"/></svg>
      </div>
      <div class="pj-bento-text">
        <h3>Fotos ({{ $photos->count() }})</h3>
        <p>Vistas previas de las fotos que subiste.</p>
      </div>
    </div>

    <div class="pj-marquee" aria-hidden="true" data-marquee="photos">
      <div class="pj-marquee-track">
        @if($photos->isEmpty())
          <div class="pj-empty">No hay fotos aún</div>
        @else
          @foreach($photos as $p)
            <figure class="pj-mini-file">
              <img src="{{ Storage::url($p->archivo) }}" alt="{{ $p->titulo }}" class="pj-mini-thumb" loading="lazy">
              <figcaption class="pj-mini-title">{{ $p->titulo }}</figcaption>
            </figure>
          @endforeach
          {{-- repetir para continuidad visual --}}
          @foreach($photos as $p)
            <figure class="pj-mini-file">
              <img src="{{ Storage::url($p->archivo) }}" alt="{{ $p->titulo }}" class="pj-mini-thumb" loading="lazy">
              <figcaption class="pj-mini-title">{{ $p->titulo }}</figcaption>
            </figure>
          @endforeach
        @endif
      </div>
    </div>
  </article>

  <!-- VIDEOS: marquee con mini-videos (desde BD) -->
  <article class="pj-bento pj-notifications">
    <div class="pj-bento-content">
      <div class="pj-bento-icon">
        <svg viewBox="0 0 24 24" width="34" height="34"><path fill="currentColor" d="M23 7l-7 5 7 5V7zM1 5h15v14H1z"/></svg>
      </div>
      <div class="pj-bento-text">
        <h3>Videos ({{ $videos->count() }})</h3>
        <p>Previews de videos — mute y autoplay en miniatura.</p>
      </div>
    </div>

    <div class="pj-marquee" aria-hidden="true" data-marquee="videos">
      <div class="pj-marquee-track">
        @if($videos->isEmpty())
          <div class="pj-empty">No hay videos</div>
        @else
          @foreach($videos as $p)
            <figure class="pj-mini-file pj-mini-video">
              <video playsinline muted loop preload="metadata" class="pj-mini-vid" poster="">
                <source src="{{ Storage::url($p->archivo) }}" type="video/mp4">
                Tu navegador no soporta video.
              </video>
              <figcaption class="pj-mini-title">{{ $p->titulo }}</figcaption>
            </figure>
          @endforeach
          @foreach($videos as $p)
            <figure class="pj-mini-file pj-mini-video">
              <video playsinline muted loop preload="metadata" class="pj-mini-vid" poster="">
                <source src="{{ Storage::url($p->archivo) }}" type="video/mp4">
                Tu navegador no soporta video.
              </video>
              <figcaption class="pj-mini-title">{{ $p->titulo }}</figcaption>
            </figure>
          @endforeach
        @endif
      </div>
    </div>
  </article>

  <!-- DOCUMENTS: lista compacta (desde BD) -->
  <article class="pj-bento pj-integrations">
    <div class="pj-bento-content">
      <div class="pj-bento-icon">
        <svg viewBox="0 0 24 24" width="34" height="34"><path fill="currentColor" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path fill="#fff" d="M14 2v6h6"/></svg>
      </div>
      <div class="pj-bento-text">
        <h3>Documentos ({{ $documents->count() }})</h3>
        <p>PDF, XLSX, TXT, GPG y otros archivos subidos.</p>
      </div>
    </div>

    <div class="pj-doc-list">
      @if($documents->isEmpty())
        <div class="pj-empty">No hay documentos</div>
      @else
        @foreach($documents->take(6) as $d)
          <div class="pj-doc-item">
            <svg class="pj-file-icon" viewBox="0 0 24 24" width="28" height="28"><path d="M4 4h16v16H4V4z"/></svg>
            <div class="pj-doc-meta">
              <div class="pj-doc-title">{{ $d->titulo }}</div>
              <div class="pj-doc-name">{{ pathinfo($d->archivo, PATHINFO_BASENAME) }}</div>
            </div>
            <a href="{{ Storage::url($d->archivo) }}" target="_blank" class="pj-doc-link">Abrir</a>
          </div>
        @endforeach
        @if($documents->count() > 6)
          <div class="pj-more">+{{ $documents->count()-6 }} more</div>
        @endif
      @endif
    </div>
  </article>

  <!-- calendar -->
  <article class="pj-bento pj-calendar">
    <div class="pj-bento-content">
      <div class="pj-bento-icon">
        <svg viewBox="0 0 24 24" width="34" height="34"><path fill="currentColor" d="M7 10h5v5H7z"/><path fill="currentColor" d="M7 3v2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2V3h-2v2H9V3H7z"/></svg>
      </div>
      <div class="pj-bento-text">
        <h3>Calendar</h3>
        <p>Usalo para filtrar por fecha.</p>
      </div>
    </div>

    <div class="pj-mini-calendar">
      <div class="pj-month">November 2025</div>
      <div class="pj-days">
        @php $days = range(1,30); @endphp
        @foreach($days as $d)
          <div class="pj-day">{{ $d }}</div>
        @endforeach
      </div>
    </div>
  </article>

</div>

<!-- GRID DE PUBLICACIONES (usando el $posts que te pasa el controlador, paginado) -->
<div class="pj-grid">
    @foreach($posts as $post)
        <div class="pj-card">
            <!-- PREVISUALIZACIÓN -->
            @if($post->tipo == 'foto')
                <img src="{{ Storage::url($post->archivo) }}" alt="{{ $post->titulo }}" class="pj-media">
            @elseif($post->tipo == 'video')
                <video class="pj-media" playsinline muted loop>
                    <source src="{{ Storage::url($post->archivo) }}" type="video/mp4">
                </video>
            @else
                <div class="pj-file">
                    <svg class="pj-file-icon" viewBox="0 0 24 24">
                        <path d="M4 4h16v16H4V4zm2 2v12h12V6H6zm4 2h4v2h-4V8zm0 4h4v2h-4v-2z"/>
                    </svg>
                    <span class="pj-file-name">{{ pathinfo($post->archivo, PATHINFO_BASENAME) }}</span>
                </div>
            @endif

            <!-- INFORMACIÓN -->
            <div class="pj-info">
                <h3 class="pj-title-card">{{ $post->titulo }}</h3>
                <p class="pj-meta">{{ $post->empresa }} • {{ \Carbon\Carbon::parse($post->fecha)->format('d/m/Y') }}</p>
                <p class="pj-desc">{{ $post->descripcion }}</p>
            </div>

            <!-- FOOTER -->
            <div class="pj-footer">
                <a href="{{ route('posts.show', $post) }}" class="pj-btn">Ver</a>
                <span>{{ $post->comentarios()->count() }} comentarios</span>
            </div>
        </div>
    @endforeach
</div>

<!-- PAGINACIÓN -->
<div class="pj-pagination">
    {{ $posts->withQueryString()->links() }}
</div>

@endsection


<style>
/* (Mismo CSS, enfocado y conciso para esta versión) */
.pj-title{font-size:32px;margin-bottom:18px;font-weight:800;color:#111}
.pj-filters{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:22px}
.pj-filters input,.pj-filters button,.pj-filters .pj-create{padding:8px 12px;border-radius:10px;border:1px solid rgba(16,24,40,.08);font-size:14px;background:#fff}
.pj-filters button{background:#4f46e5;color:#fff;border:none}
.pj-filters .pj-create{background:#06b6d4;color:#fff;text-decoration:none;border:none}

/* Bento grid */
.pj-bento-grid{display:grid;grid-template-columns: repeat(6,1fr);grid-auto-rows:220px;gap:18px;margin-bottom:26px}
.pj-bento{position:relative;padding:18px;border-radius:18px;background:linear-gradient(180deg,#fff,#fbfdff);box-shadow:0 8px 30px rgba(2,6,23,0.06);overflow:hidden;border:1px solid rgba(16,24,40,0.04)}
.pj-bento .pj-bento-content{display:flex;align-items:center;gap:12px;z-index:2}
.pj-bento-icon{width:54px;height:54px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:linear-gradient(180deg,#eef2ff,#f8fbff)}
.pj-bento-text h3{margin:0;font-size:18px;color:#0f172a}
.pj-bento-text p{margin:4px 0 0;color:#6b7280;font-size:13px}

/* grid areas */
.pj-files{grid-column:1 / span 2;grid-row:1}
.pj-notifications{grid-column:3 / span 4;grid-row:1}
.pj-integrations{grid-column:1 / span 4;grid-row:2}
.pj-calendar{grid-column:5 / span 2;grid-row:2}

/* marquee */
.pj-marquee{position:absolute;left:0;right:0;bottom:0;padding:14px 18px;z-index:1}
.pj-marquee-track{display:flex;gap:12px;transform:translateX(0);animation:pj-marquee 28s linear infinite}
@keyframes pj-marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
.pj-mini-file{width:220px;padding:8px;border-radius:14px;background:#fff;border:1px solid rgba(16,24,40,0.04);box-shadow:0 6px 18px rgba(2,6,23,0.04);display:flex;flex-direction:column;align-items:flex-start;overflow:hidden}
.pj-mini-thumb{width:100%;height:110px;object-fit:cover;border-radius:8px}
.pj-mini-title{font-weight:600;font-size:13px;margin-top:8px;color:#0f172a}
.pj-mini-video .pj-mini-vid{width:100%;height:110px;border-radius:8px;object-fit:cover;display:block}

/* docs */
.pj-doc-list{display:flex;flex-direction:column;gap:8px;margin-top:12px}
.pj-doc-item{display:flex;align-items:center;gap:12px;padding:8px;border-radius:10px;background:#fff;border:1px solid rgba(16,24,40,0.03)}
.pj-doc-meta{flex:1}
.pj-doc-title{font-weight:700;color:#0f172a}
.pj-doc-name{font-size:12px;color:#6b7280}
.pj-doc-link{text-decoration:none;color:#2563eb;font-weight:600}

/* publicaciones grid */
.pj-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-top:10px}
.pj-card{position:relative;border-radius:14px;overflow:hidden;background:#fff;box-shadow:0 10px 40px rgba(2,6,23,0.06);transition:transform .28s}
.pj-card:hover{transform:translateY(-6px)}
.pj-media{width:100%;height:200px;object-fit:cover;display:block}
.pj-file{display:flex;flex-direction:column;justify-content:center;align-items:center;height:200px;background:linear-gradient(135deg,#f3f4f6,#ffffff)}
.pj-file-icon{width:48px;height:48px;fill:#6b7280;margin-bottom:10px}
.pj-file-name{font-size:14px;color:#374151;text-align:center;padding:0 8px}
.pj-info{padding:14px}
.pj-title-card{font-size:18px;margin:0 0 4px;font-weight:700}
.pj-meta{font-size:13px;color:#6b7280;margin:0}
.pj-desc{color:#374151;margin-top:8px;font-size:14px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.pj-footer{position:absolute;bottom:12px;left:12px;right:12px;display:flex;justify-content:space-between;opacity:0;transition:opacity .2s}
.pj-card:hover .pj-footer{opacity:1}
.pj-btn{background:#4f46e5;color:#fff;padding:8px 12px;border-radius:8px;text-decoration:none;font-size:13px}

/* responsive */
@media(max-width:1000px){
  .pj-bento-grid{grid-template-columns:repeat(4,1fr);grid-auto-rows:200px}
  .pj-files{grid-column:1 / span 2}
  .pj-notifications{grid-column:3 / span 2}
  .pj-integrations{grid-column:1 / span 2}
  .pj-calendar{grid-column:3 / span 2}
}
@media(max-width:700px){
  .pj-bento-grid{grid-template-columns:repeat(1,1fr);grid-auto-rows:180px}
  .pj-marquee-track{animation-duration:18s}
  .pj-media{height:150px}
}
</style>

<script>
/* Marquee pause/resume per section + autoplay mini-videos control */
document.querySelectorAll('.pj-marquee').forEach(m => {
  const track = m.querySelector('.pj-marquee-track');
  if(!track) return;
  m.addEventListener('mouseenter', ()=> track.style.animationPlayState = 'paused');
  m.addEventListener('mouseleave', ()=> track.style.animationPlayState = 'running');

  const vids = m.querySelectorAll('video');
  if(vids.length){
    m.addEventListener('mouseenter', ()=> vids.forEach(v=> v.pause()));
    m.addEventListener('mouseleave', ()=> vids.forEach(v=>{
      const p = v.play();
      if(p && p.catch) p.catch(()=>{});
    }));
    vids.forEach(v => {
      v.muted = true;
      v.loop = true;
      const p = v.play();
      if(p && p.catch) p.catch(()=>{});
    });
  }
});

/* Pause marquee when out of view for perf */
const observer = new IntersectionObserver((entries)=>{
  entries.forEach(en=>{
    const track = en.target.querySelector('.pj-marquee-track');
    if(!track) return;
    track.style.animationPlayState = en.isIntersecting ? 'running' : 'paused';
    const vids = en.target.querySelectorAll('video');
    vids.forEach(v => en.isIntersecting ? v.play().catch(()=>{}) : v.pause());
  });
},{threshold: 0.2});
document.querySelectorAll('.pj-marquee').forEach(m => observer.observe(m));
</script>

