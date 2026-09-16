@php $url = \App\Support\DashboardWidgets::url('propuestas-comerciales.index') ?? \App\Support\DashboardWidgets::url('admin.licitacion-propuestas.index'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="m7 15 4-5 4 3 5-7"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver todas</a>@endif
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Aún no hay propuestas.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $p)
                @php $enlace = \App\Support\DashboardWidgets::url('propuestas-comerciales.show', $p); @endphp
                <a href="{{ $enlace ?? '#' }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $p->folio ? $p->folio . ' · ' : '' }}{{ $p->titulo ?: 'Propuesta #' . $p->id }}</span>
                        <span class="dw-fila-s">{{ $p->cliente ?: 'Sin cliente' }} · <span class="dw-badge azul">{{ ucfirst($p->status ?: '—') }}</span></span>
                    </span>
                    <span class="dw-fila-v">${{ number_format((float) $p->total, 2) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
