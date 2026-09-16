@php $url = \App\Support\DashboardWidgets::url('tickets.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 9a3 3 0 0 1 0 6v3a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-3a3 3 0 0 1 0-6V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver</a>@endif
    </div>

    <div class="dw-num">{{ number_format($w['abiertos'] ?? 0) }}</div>
    <div class="dw-sub">pendientes · {{ $w['mios'] ?? 0 }} {{ ($w['mios'] ?? 0) === 1 ? 'tuyo' : 'tuyos' }}</div>

    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b class="{{ ($w['vencidos'] ?? 0) > 0 ? 'es-baja' : '' }}">{{ $w['vencidos'] ?? 0 }}</b><span>Vencidos</span></div>
            <div><b>{{ $w['creados_mes'] ?? 0 }}</b><span>Nuevos este mes</span></div>
            <div><b class="es-sube">{{ $w['completados_mes'] ?? 0 }}</b><span>Cerrados este mes</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['vencidos'] ?? 0 }} vencidos</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Por estado</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
