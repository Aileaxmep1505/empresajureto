@extends('layouts.app')
@section('title', 'WMS · Productividad')

@push('styles')
  @include('admin.wms.ops._estilos')
  <style>
    .lab-barras{ display:flex; align-items:flex-end; gap:6px; height:132px; padding-top:18px; }
    .lab-barra{ flex:1; min-width:0; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; gap:6px; height:100%; }
    .lab-barra i{ display:block; width:100%; max-width:42px; border-radius:4px 4px 0 0; background:var(--ui-accent); min-height:3px; }
    .lab-barra i.cero{ background:var(--ui-surface-3); }
    .lab-barra b{ font-size:11.5px; font-weight:600; color:var(--ui-ink-2); font-variant-numeric:tabular-nums; }
    .lab-barra span{ font-size:11.5px; color:var(--ui-muted); white-space:nowrap; }
    .lab-ef{ display:inline-flex; align-items:center; gap:7px; }
    .lab-ef .ops-progress{ width:56px; flex:0 0 auto; }
    .lab-ef .ops-progress > i.bajo{ background:var(--ui-danger); }
    .lab-ef .ops-progress > i.medio{ background:var(--ui-warn); }
    .lab-ef .ops-progress > i.alto{ background:var(--ui-ok); }
    .lab-ef b{ font-variant-numeric:tabular-nums; }
    .lab-area{ white-space:nowrap; }
    .lab-area small{ color:var(--ui-muted); }
    .lab-nada{ color:var(--ui-faint); }
  </style>
@endpush

@section('content')
@php
  $maxDia = max(1, collect($serie)->max('tareas'));
  $areas = ['picking' => 'Picking', 'recepcion' => 'Recepción', 'movimientos' => 'Movimientos', 'embarque' => 'Embarque', 'reabasto' => 'Reabasto', 'conteos' => 'Conteos'];
  $nivel = fn ($ef) => $ef >= 100 ? 'alto' : ($ef >= 70 ? 'medio' : 'bajo');
@endphp

<div class="ops-wrap">
  @include('admin.wms.ops._nav', [
      'opsTitulo' => 'Productividad del personal',
      'opsSub' => 'Lo que hizo cada persona en el almacén: olas surtidas, recepciones, movimientos, embarques, reabastos y conteos. En picking y embarque se compara su ritmo contra la meta por hora.',
      'opsTour' => 'wms-productividad',
      'opsPasos' => [
          ['Elige el periodo', 'Hoy, la semana o los últimos 30 días.'],
          ['Mira quién hizo qué', 'Tareas y piezas por área: picking, recepción, embarque, reabasto y conteos.'],
          ['Compara con la meta', 'La barra de eficiencia es el ritmo real contra lo que esperas por hora.'],
          ['Ajusta las metas', 'Si la meta no cuadra con tu operación, cámbiala abajo y todo se recalcula.'],
      ],
  ])

  <form method="GET" action="{{ route('admin.wms.labor.index') }}" class="ops-card" data-tour="periodo">
    <div class="ops-card-body">
      <div class="ops-actions">
        <div class="ops-field"><label for="lDesde">Desde</label><input id="lDesde" type="date" name="desde" value="{{ $desde->format('Y-m-d') }}"></div>
        <div class="ops-field"><label for="lHasta">Hasta</label><input id="lHasta" type="date" name="hasta" value="{{ $hasta->format('Y-m-d') }}"></div>
        <button type="submit" class="ops-btn is-primary" style="align-self:flex-end;">Ver periodo</button>
        <a class="ops-btn" style="align-self:flex-end;" href="{{ route('admin.wms.labor.index', ['desde' => now()->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}">Hoy</a>
        <a class="ops-btn" style="align-self:flex-end;" href="{{ route('admin.wms.labor.index', ['desde' => now()->subDays(29)->format('Y-m-d'), 'hasta' => now()->format('Y-m-d')]) }}">30 días</a>
      </div>
    </div>
  </form>

  <div class="ops-kpis">
    <div class="ops-kpi azul"><b>{{ $totales['personas'] }}</b><span>Personas con actividad</span></div>
    <div class="ops-kpi"><b>{{ number_format($totales['tareas']) }}</b><span>Tareas realizadas</span></div>
    <div class="ops-kpi"><b>{{ number_format($totales['unidades']) }}</b><span>Unidades manejadas</span></div>
    <div class="ops-kpi"><b>{{ $totales['personas'] ? number_format($totales['tareas'] / $totales['personas'], 1) : '—' }}</b><span>Tareas por persona</span></div>
  </div>

  <div class="ops-card">
    <div class="ops-card-head"><div><h2>Actividad por día</h2><p>Tareas terminadas entre {{ $desde->format('d/m/Y') }} y {{ $hasta->format('d/m/Y') }}.</p></div></div>
    <div class="ops-card-body">
      @if(count($serie) > 45)
        <p class="ops-sub">El periodo es muy largo para graficarlo por día. Elige 45 días o menos.</p>
      @else
        <div class="lab-barras">
          @foreach($serie as $d)
            <div class="lab-barra" title="{{ $d['dia'] }}: {{ $d['tareas'] }} tareas">
              <b>{{ $d['tareas'] ?: '' }}</b>
              <i class="{{ $d['tareas'] ? '' : 'cero' }}" style="height:{{ max(3, round($d['tareas'] / $maxDia * 100)) }}%"></i>
              <span>{{ $d['dia'] }}</span>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <div class="ops-card">
    <div class="ops-card-head"><div data-tour="tabla"><h2>Por persona</h2><p>Tareas y unidades por área. La eficiencia es el ritmo real contra la meta; solo se calcula donde hay tiempo medido.</p></div></div>

    @if($tabla->isEmpty())
      <div class="ops-empty"><h3>Sin actividad en el periodo</h3><p>Prueba con un rango de fechas más amplio.</p></div>
    @else
      <div class="ops-table-wrap">
        <table class="ops-table">
          <thead>
            <tr>
              <th>Persona</th>
              @foreach($areas as $a)<th class="num">{{ $a }}</th>@endforeach
              <th class="num">Total</th>
              <th>Eficiencia picking</th>
              <th>Eficiencia embarque</th>
            </tr>
          </thead>
          <tbody>
            @foreach($tabla as $f)
              <tr>
                <td><div class="ops-prod">{{ $f['user']->name ?? 'Usuario eliminado' }}</div></td>
                @foreach($areas as $k => $a)
                  <td class="num lab-area">
                    @if($f[$k]['tareas'])<b>{{ $f[$k]['tareas'] }}</b> <small>· {{ number_format($f[$k]['unidades']) }} u</small>@else<span class="lab-nada">—</span>@endif
                  </td>
                @endforeach
                <td class="num"><b>{{ $f['tareas'] }}</b> <small style="color:var(--ui-muted);">· {{ number_format($f['unidades']) }} u</small></td>
                @foreach(['picking', 'embarque'] as $k)
                  <td>
                    @if($f[$k . '_ef'] !== null)
                      <span class="lab-ef" title="{{ $f[$k . '_uph'] }} u/h contra meta de {{ $metas[$k . '_uph'] }}">
                        <span class="ops-progress"><i class="{{ $nivel($f[$k . '_ef']) }}" style="width:{{ min(100, $f[$k . '_ef']) }}%"></i></span>
                        <b>{{ $f[$k . '_ef'] }}%</b> <small style="color:var(--ui-muted);">{{ $f[$k . '_uph'] }} u/h</small>
                      </span>
                    @else
                      <span class="lab-nada">—</span>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <form method="POST" action="{{ route('admin.wms.labor.targets') }}" class="ops-card" data-tour="metas">
    @csrf
    <div class="ops-card-head">
      <div><h2>Metas por hora</h2><p>Contra esto se mide la eficiencia. Ajústalas a la realidad de tu operación.</p></div>
      <button type="submit" class="ops-btn">Guardar metas</button>
    </div>
    <div class="ops-card-body">
      <div class="ops-grid">
        <div class="ops-field"><label for="mPick">Picking (unidades / hora)</label><input id="mPick" type="number" name="picking_uph" min="1" value="{{ $metas['picking_uph'] }}" required></div>
        <div class="ops-field"><label for="mEmb">Embarque (unidades / hora)</label><input id="mEmb" type="number" name="embarque_uph" min="1" value="{{ $metas['embarque_uph'] }}" required></div>
      </div>
    </div>
  </form>
</div>
@endsection

@push('scripts')
  @include('partials.ui-tour')
  <script>
    UITour.registrar('wms-productividad', {
      version: 1,
      auto: true,
      pasos: [
    {
        "el": "[data-tour=\"periodo\"]",
        "titulo": "Primero, el periodo",
        "texto": "Todo lo de abajo se calcula con estas fechas. Tienes atajos de Hoy y 30 días."
    },
    {
        "el": "[data-tour=\"tabla\"]",
        "titulo": "Quién hizo qué",
        "texto": "Cada columna es un área del almacén. El número grande son tareas; abajo, las piezas."
    },
    {
        "el": "[data-tour=\"metas\"]",
        "titulo": "Pon tus metas",
        "texto": "La eficiencia compara el ritmo real contra estas cifras. Ajústalas a lo que tu equipo sí puede hacer."
    }
],
    });
  </script>
@endpush
