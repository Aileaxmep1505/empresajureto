@php $url = \App\Support\DashboardWidgets::url('accounting.receivables.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico rojo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver</a>@endif
    </div>

    <div class="dw-num">${{ number_format((float) ($w['monto'] ?? 0), 2) }}</div>
    <div class="dw-sub">{{ $w['cuentas'] ?? 0 }} {{ ($w['cuentas'] ?? 0) === 1 ? 'cuenta pendiente' : 'cuentas pendientes' }}</div>

    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b class="{{ ($w['vencidas'] ?? 0) > 0 ? 'es-baja' : '' }}">{{ $w['vencidas'] ?? 0 }}</b><span>Vencidas</span></div>
            <div><b class="{{ ($w['monto_vencido'] ?? 0) > 0 ? 'es-baja' : '' }}">${{ number_format((float) ($w['monto_vencido'] ?? 0), 0) }}</b><span>Monto vencido</span></div>
            <div><b class="es-sube">${{ number_format((float) ($w['cobrado_mes'] ?? 0), 0) }}</b><span>Cobrado este mes</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['vencidas'] ?? 0 }} vencidas</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Próximas a vencer</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
