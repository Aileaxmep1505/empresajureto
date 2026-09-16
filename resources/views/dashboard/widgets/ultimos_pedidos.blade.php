@php $url = \App\Support\DashboardWidgets::url('admin.orders.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver todos</a>@endif
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Aún no hay pedidos.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $o)
                @php
                    $enlace = \App\Support\DashboardWidgets::url('admin.orders.show', $o);
                    $color = match ($o->status) { 'pagado', 'paid' => 'verde', 'cancelado', 'cancelled', 'refunded' => 'rojo', default => 'ambar' };
                @endphp
                <a href="{{ $enlace ?? '#' }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $o->customer_name ?: 'Pedido #' . $o->id }}</span>
                        <span class="dw-fila-s"><span class="dw-badge {{ $color }}">{{ ucfirst($o->status ?: '—') }}</span> · #{{ $o->id }} · {{ $o->created_at?->format('d/m/Y') }}</span>
                    </span>
                    <span class="dw-fila-v">${{ number_format((float) $o->total, 2) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
