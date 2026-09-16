{{-- Tarjeta genérica de accesos directos: pinta un grupo de DashboardAccesos. --}}
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <span class="msi" aria-hidden="true">{{ $w['icono'] ?? 'apps' }}</span>
        </span>
        <h3>{{ $titulo }}</h3>
    </div>

    @if (empty($w['items']))
        <p class="dw-vacio">No hay módulos disponibles en este grupo.</p>
    @else
        <div class="dw-accesos">
            @foreach ($w['items'] as $item)
                <a href="{{ $item['url'] }}" class="dw-acceso" title="{{ $item['label'] }}">
                    @if (! empty($item['badge']))
                        <span class="dw-acceso-badge">{{ $item['badge'] }}</span>
                    @endif
                    <span class="dw-acceso-ico"><span class="msi" aria-hidden="true">{{ $item['icon'] }}</span></span>
                    <span class="dw-acceso-txt">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
