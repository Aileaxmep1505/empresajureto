@php
    $url = \App\Support\DashboardWidgets::url('projects.index');
    $colores = ['ganado' => 'verde', 'participa' => 'verde', 'junta_aclaraciones' => 'verde', 'armado_propuesta' => 'verde',
                'entrega' => 'verde', 'revision' => 'ambar', 'no_participa' => 'rojo', 'perdido' => 'rojo', 'desierta' => ''];
@endphp
<div class="dw">
    <div class="dw-head">
        <span class="dw-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14 13-7.5 7.5a2.1 2.1 0 0 1-3-3L11 10"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/></svg>
        </span>
        <h3>{{ $titulo }}</h3>
        @if ($url)<a href="{{ $url }}" class="dw-link">Ver todas</a>@endif
    </div>

    @if ($w['filas']->isEmpty())
        <p class="dw-vacio">Aún no hay licitaciones.</p>
    @else
        <div class="dw-filas">
            @foreach ($w['filas'] as $p)
                @php
                    $etapa = $p->workflow_status ?: 'analisis_bases';
                    $enlace = \App\Support\DashboardWidgets::url('projects.show', $p);
                @endphp
                <a href="{{ $enlace ?? '#' }}" class="dw-fila">
                    <span class="dw-fila-txt">
                        <span class="dw-fila-t">{{ $p->name ?: 'Licitación #' . $p->id }}</span>
                        <span class="dw-fila-s">{{ $p->assigned_name ?: ($p->user->name ?? 'Sin responsable') }} · {{ $p->created_at?->format('d/m/Y') }}</span>
                    </span>
                    <span class="dw-badge {{ $colores[$etapa] ?? 'azul' }}">{{ \App\Support\DashboardWidgets::ETAPAS[$etapa] ?? ucfirst($etapa) }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
