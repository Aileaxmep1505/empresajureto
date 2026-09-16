@php $url = \App\Support\DashboardWidgets::url('cotizaciones.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver todas</a>@endif
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Aún no hay cotizaciones.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $cot)
                @php
                    $enlace = \App\Support\DashboardWidgets::url('cotizaciones.show', $cot);
                    $color = match ($cot->estado) { 'converted' => 'verde', 'cancelled' => 'rojo', default => 'azul' };
                @endphp
                <a href="{{ $enlace ?? '#' }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">Cotización #{{ $cot->id }} · {{ $cot->cliente->nombre ?? 'Sin cliente' }}</span>
                        <span class="dw-fila-s"><span class="dw-badge {{ $color }}">{{ $cot->estado_label }}</span> · {{ $cot->created_at?->format('d/m/Y') }}</span>
                    </span>
                    <span class="dw-fila-v">${{ number_format((float) $cot->total, 2) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
