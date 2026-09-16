@php $url = \App\Support\DashboardWidgets::url('admin.orders.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver</a>@endif
    </div>

    <div class="dw-num">{{ number_format($w['mes'] ?? 0) }}</div>
    <div class="dw-sub">este mes · ${{ number_format((float) ($w['monto_mes'] ?? 0), 2) }}</div>

    @if (($w['nivel'] ?? 1) >= 2)
        <div class="dw-mini">
            <div><b>{{ $w['total'] ?? 0 }}</b><span>Históricos</span></div>
            <div><b>{{ $w['pagados'] ?? 0 }}</b><span>Pagados</span></div>
            <div><b class="{{ ($w['sin_envio'] ?? 0) > 0 ? 'es-baja' : '' }}">{{ $w['sin_envio'] ?? 0 }}</b><span>Sin envío</span></div>
            <div><b>${{ number_format((float) ($w['monto_total'] ?? 0), 0) }}</b><span>Histórico</span></div>
        </div>
    @elseif ($alto >= 3)
        <div class="dw-pie">{{ $w['total'] ?? 0 }} pedidos en total</div>
    @endif

    @if (($w['nivel'] ?? 1) >= 3)
        <div class="dw-sep">Últimos pedidos</div>
        @include('dashboard.widgets._tabla', ['filas' => $w['tabla'] ?? []])
    @endif
</div>
