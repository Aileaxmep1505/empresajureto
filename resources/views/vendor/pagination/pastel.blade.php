@if ($paginator->hasPages())
<nav class="pg" role="navigation" aria-label="Paginación">
  {{-- Prev --}}
  @if ($paginator->onFirstPage())
    <span class="pg-btn is-disabled" aria-disabled="true">Anterior</span>
  @else
    <a class="pg-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
  @endif

  {{-- Pages --}}
  <div class="pg-pages">
    @foreach ($elements as $element)

      {{-- "..." --}}
      @if (is_string($element))
        <span class="pg-ellipsis">{{ $element }}</span>
      @endif

      {{-- Array of links --}}
      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <span class="pg-num is-active" aria-current="page">{{ $page }}</span>
          @else
            <a class="pg-num" href="{{ $url }}">{{ $page }}</a>
          @endif
        @endforeach
      @endif

    @endforeach
  </div>

  {{-- Next --}}
  @if ($paginator->hasMorePages())
    <a class="pg-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
  @else
    <span class="pg-btn is-disabled" aria-disabled="true">Siguiente</span>
  @endif
</nav>
@endif
