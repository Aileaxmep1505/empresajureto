<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2 3 14h8l-1 8 10-12h-8z"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    @if (empty($w['items']))
        <p class="dw-vacio">No hay acciones disponibles.</p>
    @else
        <div class="dw-accesos">
            @foreach ($w['items'] as $item)
                <a href="{{ $item['url'] }}" class="dw-acceso" title="{{ $item['label'] }}">
                    <span class="dw-acceso-ico"><span class="msi" aria-hidden="true">{{ $item['icon'] }}</span></span>
                    <span class="dw-acceso-txt">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
