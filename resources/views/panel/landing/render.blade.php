{{-- resources/views/landing/render.blade.php --}}
@php
  /** @var \App\Models\LandingSection $section */
  $layout = $section->layout; // 'banner-wide', 'grid-1', 'grid-2', 'grid-3'
  $gridClass = match($layout) {
    'banner-wide' => 'lp-grid-banner',
    'grid-1'      => 'lp-grid-1',
    'grid-2'      => 'lp-grid-2',
    default       => 'lp-grid-3',
  };
@endphp

<section class="lp-wrap" aria-label="{{ $section->name }}">
  {{-- Si quieres mostrar el nombre de la sección, descomenta: --}}
  {{-- <header class="lp-head"><h2>{{ $section->name }}</h2></header> --}}

  <div class="lp-stage">
    <div class="lp-grid {{ $gridClass }}">
      @forelse($section->items as $it)
        @php
          // Usa el accessor image_url de tu modelo LandingItem
          $img = $it->image_url ?? asset('images/placeholder.png');
          $title = $it->title ?? '';
          $subtitle = $it->subtitle ?? '';
          $ctaText = $it->cta_text ?? '';
          $ctaUrl  = $it->cta_url ?? '';
        @endphp

        <article class="lp-card ao">
          <img class="img" src="{{ $img }}" alt="{{ $title ?: 'Imagen de bloque' }}" loading="lazy">
          @if($title || $subtitle || $ctaText)
          <div class="txt">
            @if($title)   <div class="t1">{{ $title }}</div> @endif
            @if($subtitle)<div class="t2">{{ $subtitle }}</div> @endif
            @if($ctaText && $ctaUrl)
              <a class="cta" href="{{ $ctaUrl }}">
                <span class="mi">bolt</span>{{ $ctaText }}
              </a>
            @endif
          </div>
          @endif
        </article>
      @empty
        <div class="lp-empty">No hay bloques en esta sección.</div>
      @endforelse
    </div>
  </div>
</section>
