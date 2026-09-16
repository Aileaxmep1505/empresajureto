@php $url = \App\Support\DashboardWidgets::url('tickets.my'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico ambar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 11l3 3 8-8"/><path d="M20 12v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h9"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver todos</a>@endif
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">No tienes tickets pendientes.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $t)
                @php
                    $enlace = \App\Support\DashboardWidgets::url('tickets.show', $t);
                    $vencido = $t->due_at && $t->due_at->isPast();
                    $color = match ($t->priority) { 'alta', 'high', 'urgente', 'urgent' => 'rojo', 'media', 'medium' => 'ambar', default => '' };
                @endphp
                <a href="{{ $enlace ?? '#' }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $t->folio ? $t->folio . ' · ' : '' }}{{ $t->title ?: 'Ticket #' . $t->id }}</span>
                        <span class="dw-fila-s">
                            {{ ucfirst(str_replace('_', ' ', $t->status ?: '—')) }}
                            @if ($t->priority) · <span class="dw-badge {{ $color }}">{{ ucfirst($t->priority) }}</span> @endif
                        </span>
                    </span>
                    <span class="dw-fila-v {{ $vencido ? 'es-alerta' : '' }}">{{ $t->due_at ? $t->due_at->format('d/m') : '—' }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
