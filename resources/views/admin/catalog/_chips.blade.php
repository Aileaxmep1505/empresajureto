{{-- Chips de filtros activos; cada uno se quita por separado. Se refresca por AJAX. --}}
@if($hasFilters)
  <div class="active-filters">
    <span class="lbl">Filtros:</span>
    @foreach($activos as [$etq, $val, $quitar])
      <a class="afchip" href="{{ $sinParams($quitar) }}" data-ajax title="Quitar filtro {{ $etq }}">
        {{ $etq }}: <b>{{ \Illuminate\Support\Str::limit($val, 32) }}</b>
        <span class="x"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg></span>
      </a>
    @endforeach
    <a class="afclear" href="{{ route('admin.catalog.index') }}" data-ajax>Limpiar todo</a>
  </div>
@endif
