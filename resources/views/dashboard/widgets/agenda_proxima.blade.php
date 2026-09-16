@php $url = \App\Support\DashboardWidgets::url('agenda.calendar'); @endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico verde">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Abrir agenda</a>@endif
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">No tienes eventos próximos.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $ev)
                @php
                    $inicio = $ev->start_at;
                    $esHoy = $inicio && $inicio->isToday();
                @endphp
                <div class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $ev->title ?: 'Evento' }}</span>
                        <span class="dw-fila-s">
                            @if ($inicio)
                                {{ $esHoy ? 'Hoy' : ucfirst($inicio->locale('es')->isoFormat('ddd D MMM')) }}
                                @unless ($ev->all_day) · {{ $inicio->format('H:i') }} @endunless
                            @endif
                            @if ($ev->location) · {{ $ev->location }} @endif
                        </span>
                    </span>
                    @if ($ev->category)
                        <span class="dw-badge {{ $esHoy ? 'verde' : '' }}">{{ ucfirst($ev->category) }}</span>
                    @elseif ($esHoy)
                        <span class="dw-badge verde">Hoy</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
