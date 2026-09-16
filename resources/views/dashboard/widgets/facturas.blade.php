@php $url = \App\Support\DashboardWidgets::url('manual_invoices.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 2v20l3-2 3 2 3-2 3 2 3-2V2l-3 2-3-2-3 2-3-2z"/><path d="M9 9h6M9 13h4"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver</a>@endif
    </div>

    <div class="dw-num">${{ number_format((float) ($w['monto_mes'] ?? 0), 2) }}</div>
    <div class="dw-sub">facturado este mes · {{ $w['validas'] ?? 0 }} válidas</div>

    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b>{{ $w['mes'] ?? 0 }}</b><span>Este mes</span></div>
            <div><b>{{ $w['borradores'] ?? 0 }}</b><span>Borradores</span></div>
            <div><b class="{{ ($w['canceladas'] ?? 0) > 0 ? 'es-baja' : '' }}">{{ $w['canceladas'] ?? 0 }}</b><span>Canceladas</span></div>
            <div><b>{{ $w['total'] ?? 0 }}</b><span>Históricas</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['borradores'] ?? 0 }} borradores</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Últimas facturas</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
