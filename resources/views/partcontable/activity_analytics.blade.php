@extends('layouts.app')
@section('title', 'Analíticas de actividad')
@section('tema_oscuro', '1')

@push('styles')
@include('partials.ui-tokens')
<style>
  .aa-wrap{ max-width:1200px; margin-inline:auto; padding:0 16px 48px; color:var(--ui-ink); font-family:'Inter',system-ui,sans-serif; }
  .aa-head{ display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin:10px 0 18px; }
  .aa-title{ margin:0; font-size:22px; font-weight:700; letter-spacing:-.02em; }
  .aa-sub{ margin:6px 0 0; font-size:13.5px; color:var(--ui-muted); }

  .aa-range{ display:inline-flex; gap:2px; padding:3px; border:1px solid var(--ui-border); border-radius:999px; background:var(--ui-surface-2); }
  .aa-range a{ padding:7px 14px; border-radius:999px; font-size:13px; font-weight:600; color:var(--ui-muted); text-decoration:none; }
  .aa-range a.on{ background:var(--ui-surface); color:var(--ui-ink); box-shadow:var(--ui-shadow-xs); }
  .aa-btn{ display:inline-flex; align-items:center; gap:7px; height:36px; padding:0 14px; border:1px solid var(--ui-border-strong);
           border-radius:var(--ui-r); background:var(--ui-surface); color:var(--ui-ink-2); font-size:13px; font-weight:600; text-decoration:none; }
  .aa-btn:hover{ background:var(--ui-surface-3); color:var(--ui-ink); }

  .aa-kpis{ display:grid; grid-template-columns:repeat(4, minmax(0,1fr)); gap:12px; margin-bottom:16px; }
  .aa-kpi{ padding:16px 18px; background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:18px; }
  .aa-kpi .l{ font-size:12.5px; color:var(--ui-muted); font-weight:500; }
  .aa-kpi .v{ margin-top:6px; font-size:26px; font-weight:600; letter-spacing:-.02em; color:var(--ui-ink); font-variant-numeric:tabular-nums; }
  .aa-kpi .v.ok{ color:var(--ui-ok-ink); }

  .aa-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .aa-card{ padding:16px 18px; background:var(--ui-surface); border:1px solid var(--ui-border); border-radius:18px; min-width:0; }
  .aa-card.wide{ grid-column:1 / -1; }
  .aa-card h3{ margin:0 0 12px; font-size:14px; font-weight:600; letter-spacing:-.01em; color:var(--ui-ink); }
  .aa-canvas{ position:relative; height:300px; }
  .aa-canvas.tall{ height:340px; }
  .aa-empty{ padding:40px 10px; text-align:center; color:var(--ui-muted); font-size:13.5px; }

  @media (max-width:900px){ .aa-kpis{ grid-template-columns:repeat(2,minmax(0,1fr)); } .aa-grid{ grid-template-columns:1fr; } }
  @media (max-width:560px){ .aa-kpis{ grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php
  $fmtMin = function ($m) {
      $m = (int) $m;
      if ($m < 60) return $m . ' min';
      $h = intdiv($m, 60); $r = $m % 60;
      return $r ? ($h . 'h ' . $r . 'm') : ($h . 'h');
  };
@endphp

<div class="aa-wrap">
  <div class="aa-head">
    <div>
      <h1 class="aa-title">Analíticas de actividad</h1>
      <p class="aa-sub">Qué se hace más, quién lo hace y cuánto tiempo activo llevan los usuarios.</p>
    </div>
    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
      <div class="aa-range">
        @foreach(['7' => '7 días', '30' => '30 días', '90' => '90 días', 'todo' => 'Todo'] as $k => $lbl)
          <a href="{{ route('partcontable.activity.analytics', ['rango' => $k]) }}" class="{{ $rango === $k ? 'on' : '' }}">{{ $lbl }}</a>
        @endforeach
      </div>
      <a href="{{ route('partcontable.activity.all') }}" class="aa-btn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Ver bitácora completa
      </a>
    </div>
  </div>

  <div class="aa-kpis">
    <div class="aa-kpi"><div class="l">Eventos registrados</div><div class="v">{{ number_format($totalEventos) }}</div></div>
    <div class="aa-kpi"><div class="l">Usuarios activos</div><div class="v">{{ number_format($usuariosActivos) }}</div></div>
    <div class="aa-kpi"><div class="l">Acciones (crear/editar/borrar)</div><div class="v">{{ number_format($escrituras) }}</div></div>
    <div class="aa-kpi"><div class="l">Tiempo activo total</div><div class="v ok">{{ $fmtMin($tiempoActivoTotal) }}</div></div>
  </div>

  <div class="aa-grid">
    <div class="aa-card wide">
      <h3>Actividad por día</h3>
      <div class="aa-canvas"><canvas id="chSerie"></canvas></div>
    </div>

    <div class="aa-card">
      <h3>Lo que más se hace</h3>
      @if($topPantallas->isEmpty())<div class="aa-empty">Sin datos en este rango.</div>@else
      <div class="aa-canvas tall"><canvas id="chPantallas"></canvas></div>
      @endif
    </div>

    <div class="aa-card">
      <h3>Quién lo hace</h3>
      @if($topUsuarios->isEmpty())<div class="aa-empty">Sin datos en este rango.</div>@else
      <div class="aa-canvas tall"><canvas id="chUsuarios"></canvas></div>
      @endif
    </div>

    <div class="aa-card">
      <h3>Tiempo activo por usuario</h3>
      @if($tiempoActivo->isEmpty())<div class="aa-empty">Sin datos en este rango.</div>@else
      <div class="aa-canvas tall"><canvas id="chTiempo"></canvas></div>
      @endif
    </div>

    <div class="aa-card">
      <h3>Tipo de acción (método)</h3>
      @if($porMetodo->isEmpty())<div class="aa-empty">Sin datos en este rango.</div>@else
      <div class="aa-canvas tall"><canvas id="chMetodo"></canvas></div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  if (typeof Chart === 'undefined') return;
  var css = getComputedStyle(document.documentElement);
  var v = function(n, f){ var x = css.getPropertyValue(n); return (x && x.trim()) || f; };
  var ink   = v('--ui-ink-2', '#334155');
  var grid  = v('--ui-border', '#e5e7eb');
  var accent= v('--ui-accent', '#2563eb');
  var ok    = v('--ui-ok', '#22c55e');
  var warn  = v('--ui-warn', '#f59e0b');
  var danger= v('--ui-danger', '#ef4444');
  var pal   = [accent, ok, warn, danger, '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16', '#eab308'];

  Chart.defaults.color = ink;
  Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
  Chart.defaults.font.size = 12;

  var serie     = @json($serie);
  var pantallas = @json($topPantallas);
  var usuarios  = @json($topUsuarios);
  var tiempo    = @json($tiempoActivo);
  var metodos   = @json($porMetodo);

  var gridCfg = { grid: { color: grid, drawBorder:false }, ticks: { color: ink } };
  var noGrid  = { grid: { display:false }, ticks: { color: ink } };

  // Actividad por día (línea)
  var sLabels = Object.keys(serie), sData = Object.values(serie).map(Number);
  new Chart(document.getElementById('chSerie'), {
    type:'line',
    data:{ labels:sLabels, datasets:[{ label:'Eventos', data:sData, borderColor:accent,
      backgroundColor:'rgba(37,99,235,.12)', fill:true, tension:.32, pointRadius:2, borderWidth:2 }] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } },
      scales:{ x:noGrid, y:{ ...gridCfg, beginAtZero:true } } }
  });

  // Lo que más se hace (barras horizontales)
  if (pantallas.length) new Chart(document.getElementById('chPantallas'), {
    type:'bar',
    data:{ labels:pantallas.map(function(p){ return p.etiqueta; }),
      datasets:[{ data:pantallas.map(function(p){ return Number(p.n); }), backgroundColor:accent, borderRadius:6, barThickness:'flex', maxBarThickness:22 }] },
    options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } },
      scales:{ x:{ ...gridCfg, beginAtZero:true }, y:noGrid } }
  });

  // Quién lo hace
  if (usuarios.length) new Chart(document.getElementById('chUsuarios'), {
    type:'bar',
    data:{ labels:usuarios.map(function(u){ return u.nombre; }),
      datasets:[{ data:usuarios.map(function(u){ return Number(u.n); }), backgroundColor:pal, borderRadius:6, maxBarThickness:22 }] },
    options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } },
      scales:{ x:{ ...gridCfg, beginAtZero:true }, y:noGrid } }
  });

  // Tiempo activo por usuario (minutos)
  if (tiempo.length) new Chart(document.getElementById('chTiempo'), {
    type:'bar',
    data:{ labels:tiempo.map(function(t){ return t.nombre; }),
      datasets:[{ data:tiempo.map(function(t){ return Number(t.min); }), backgroundColor:ok, borderRadius:6, maxBarThickness:22 }] },
    options:{ indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false },
      tooltip:{ callbacks:{ label:function(c){ var m=c.parsed.x; var h=Math.floor(m/60), r=m%60; return (h?h+'h ':'')+r+'m'; } } } },
      scales:{ x:{ ...gridCfg, beginAtZero:true }, y:noGrid } }
  });

  // Métodos (dona)
  var mLabels = Object.keys(metodos), mData = Object.values(metodos).map(Number);
  if (mLabels.length) new Chart(document.getElementById('chMetodo'), {
    type:'doughnut',
    data:{ labels:mLabels, datasets:[{ data:mData, backgroundColor:pal, borderWidth:0 }] },
    options:{ responsive:true, maintainAspectRatio:false, cutout:'62%',
      plugins:{ legend:{ position:'bottom', labels:{ color:ink, boxWidth:12, padding:12 } } } }
  });
})();
</script>
@endpush
