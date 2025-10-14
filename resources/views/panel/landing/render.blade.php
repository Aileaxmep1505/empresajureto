@props(['section'])

@php $layout = $section->layout; @endphp

<style>
  .lg-wrap{ margin-bottom:24px; }
  .lg-grid{ display:grid; gap:16px; }
  .lg-grid.grid-1{ grid-template-columns:1fr; }
  .lg-grid.grid-2{ grid-template-columns:repeat(2,1fr); }
  .lg-grid.grid-3{ grid-template-columns:repeat(3,1fr); }
  .lg-card{ background:#fff; border-radius:16px; padding:12px; box-shadow:0 10px 28px rgba(0,0,0,.06); }
  .lg-img{ width:100%; height:200px; object-fit:cover; border-radius:12px; }
  .lg-title{ font-weight:700; margin:8px 0 2px; color:#14206a; }
  .lg-sub{ color:#6b7280; font-size:.95rem; }
  .lg-btn{ display:inline-block; margin-top:8px; padding:8px 12px; border-radius:12px; background:#14206a; color:#fff; text-decoration:none; }
  @media (max-width: 900px){
    .lg-grid.grid-2,.lg-grid.grid-3{ grid-template-columns:1fr; }
  }
</style>

<div class="lg-wrap">
  <div class="lg-grid {{ $layout === 'banner-wide' ? 'grid-1' : $layout }}">
    @foreach($section->items as $it)
      <div class="lg-card">
        <img class="lg-img" src="{{ $it->image_url }}" alt="{{ $it->title ?? 'banner' }}">
        @if($it->title)<div class="lg-title">{{ $it->title }}</div>@endif
        @if($it->subtitle)<div class="lg-sub">{{ $it->subtitle }}</div>@endif
        @if($it->cta_text && $it->cta_url)
          <a class="lg-btn" href="{{ $it->cta_url }}">{{ $it->cta_text }}</a>
        @endif
      </div>
    @endforeach
  </div>
</div>
