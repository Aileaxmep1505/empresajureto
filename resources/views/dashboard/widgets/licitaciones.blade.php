@php $url = \App\Support\DashboardWidgets::url('projects.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14 13-7.5 7.5a2.1 2.1 0 0 1-3-3L11 10"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver</a>@endif
    </div>

    <div class="dw-num">{{ number_format($w['en_juego'] ?? 0) }}</div>
    <div class="dw-sub">en juego · {{ $w['ganadas'] ?? 0 }} {{ ($w['ganadas'] ?? 0) === 1 ? 'ganada' : 'ganadas' }}</div>

    {{-- Al crecer, la tarjeta gana desglose en vez de quedarse vacía. --}}
    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b>{{ $w['analisis'] ?? 0 }}</b><span>En análisis</span></div>
            <div><b>{{ $w['participa'] ?? 0 }}</b><span>Participando</span></div>
            <div><b class="{{ ($w['perdidas'] ?? 0) > 0 ? 'es-baja' : '' }}">{{ $w['perdidas'] ?? 0 }}</b><span>Perdidas</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['total'] ?? 0 }} en total</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Por etapa</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
