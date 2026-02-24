@extends('layouts.app')
@section('title','Tickets | Centro de Control')

@section('content')
@php
  /**
   * ✅ Dashboard Ejecutivo (Diseño Minimalista & Pastel)
   */

  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;

  $stats = $stats ?? [
    'open' => 0,
    'closed' => 0,
    'overdue' => 0,
    'due_soon' => 0,
    'avg_hours' => null,
  ];

  $byUserPending = $byUserPending ?? collect();
  $byPriority    = $byPriority    ?? collect();
  $workload      = $workload      ?? collect();

  $assignedByUser   = $assignedByUser   ?? collect();
  $resolvedByUser   = $resolvedByUser   ?? collect();
  $avgResolveByUser = $avgResolveByUser ?? collect();
  $qualityByUser    = $qualityByUser    ?? collect();
  $trend            = $trend            ?? collect();

  $open   = (int)($stats['open'] ?? 0);
  $closed = (int)($stats['closed'] ?? 0);
  $over   = (int)($stats['overdue'] ?? 0);
  $soon   = (int)($stats['due_soon'] ?? 0);
  $avgH   = $stats['avg_hours'] ?? null;

  $total = $open + $closed;
  $closeRate = $total > 0 ? round(($closed / $total) * 100) : 0;
  $overRate  = $open > 0 ? round(($over / $open) * 100) : 0;
  $soonRate  = $open > 0 ? round(($soon / $open) * 100) : 0;

  $health = function() use ($overRate, $soonRate){
    if($overRate >= 20) return ['label'=>'Crítico', 'class'=>'red', 'desc'=>'Alta presión por vencimientos. Reasigna urgentes.'];
    if($overRate >= 10 || $soonRate >= 25) return ['label'=>'En alerta', 'class'=>'amber', 'desc'=>'Riesgo de incumplimiento. Enfoca hoy en por vencer.'];
    return ['label'=>'Estable', 'class'=>'green', 'desc'=>'Buen control. Mantén el ritmo de operación.'];
  };
  $h = $health();

  $fmtH = function($h){
    if(is_null($h)) return '—';
    return number_format((float)$h, 1).' h';
  };

  $maxPending = (int) ($byUserPending->max('count') ?? 0);
  $maxPrio    = (int) ($byPriority->max('count') ?? 0);
  $maxOpen    = (int) ($workload->max('open') ?? 0);

  $qualityNorm = $qualityByUser->map(function($r){
    $s = (float)($r['score'] ?? 0);
    if($s > 0 && $s <= 5.01) $s = ($s / 5) * 100;
    return ['name'=>$r['name'] ?? '—', 'score'=> round($s)];
  });

  $indexByName = function($coll, $key='name'){
    $m = [];
    foreach($coll as $r){
      $n = (string)($r[$key] ?? '');
      if($n !== '') $m[$n] = $r;
    }
    return $m;
  };
  $A = $indexByName($assignedByUser->toArray());
  $R = $indexByName($resolvedByUser->toArray());
  $T = $indexByName($avgResolveByUser->toArray());
  $Q = $indexByName($qualityNorm->toArray());

  $namesUnion = collect(array_unique(array_merge(
    array_keys($A), array_keys($R), array_keys($T), array_keys($Q)
  )))->filter()->values();

  $ranking = $namesUnion->map(function($name) use ($A,$R,$T,$Q){
    $assigned = (int)($A[$name]['count'] ?? 0);
    $resolved = (int)($R[$name]['count'] ?? 0);
    $avgHours = $T[$name]['avg_hours'] ?? null;
    $quality  = (int)($Q[$name]['score'] ?? 0);

    $speedScore = is_null($avgHours) ? 0 : max(0, min(100, round(100 / max(1, (float)$avgHours) * 8)));
    $doneRate   = $assigned > 0 ? round(($resolved / $assigned) * 100) : 0;
    $execScore  = round(($quality*0.45) + ($speedScore*0.30) + ($doneRate*0.25));

    return [
      'name'=>$name, 'assigned'=>$assigned, 'resolved'=>$resolved,
      'done_rate'=>$doneRate, 'avg_hours'=>$avgHours, 'quality'=>$quality,
      'exec_score'=>$execScore,
    ];
  })->sortByDesc('exec_score')->values();

  $chartAssigned = ($assignedByUser->count() ? $assignedByUser : $byUserPending)
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'count'=>(int)($r['count'] ?? 0)])->values();
  $chartResolved = ($resolvedByUser->count() ? $resolvedByUser : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'count'=>(int)($r['count'] ?? 0)])->values();
  $chartAvgRes = ($avgResolveByUser->count() ? $avgResolveByUser : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'avg_hours'=>(float)($r['avg_hours'] ?? 0)])->values();
  $chartQuality = ($qualityNorm->count() ? $qualityNorm : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'score'=>(int)($r['score'] ?? 0)])->values();
  $chartTrend = ($trend->count() ? $trend : collect())->values();

@endphp

<div class="container py-4" id="tkExec">
  <style>
    /* =========================================
       Executive Center — Pastel Minimalist
       ========================================= */
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

    #tkExec {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      
      /* Colors - Text & Layout */
      --ink: #2D3748;
      --ink-light: #4A5568;
      --muted: #718096;
      --muted-light: #A0AEC0;
      --line: #EDF2F7;
      
      /* Base cards */
      --bg-main: #F7FAFC;
      --card: #FFFFFF;
      
      /* Radii & Shadows */
      --radius-lg: 24px;
      --radius-md: 16px;
      --radius-sm: 8px;
      --radius-pill: 999px;
      --shadow-soft: 0 10px 40px -10px rgba(45, 55, 72, 0.06);
      --shadow-hover: 0 15px 50px -15px rgba(45, 55, 72, 0.12);

      /* Pastel Palette */
      --p-blue: #AECBFA;
      --p-blue-bg: rgba(174, 203, 250, 0.25);
      --p-blue-border: rgba(174, 203, 250, 0.6);

      --p-green: #BCE4D3;
      --p-green-bg: rgba(188, 228, 211, 0.35);
      --p-green-border: rgba(188, 228, 211, 0.8);

      --p-amber: #FDE29F;
      --p-amber-bg: rgba(253, 226, 159, 0.4);
      --p-amber-border: rgba(253, 226, 159, 0.8);

      --p-red: #FFB4AB;
      --p-red-bg: rgba(255, 180, 171, 0.3);
      --p-red-border: rgba(255, 180, 171, 0.8);

      --p-slate: #E2E8F0;
      --p-slate-bg: rgba(226, 232, 240, 0.5);
      --p-slate-border: rgba(226, 232, 240, 0.9);
    }

    #tkExec .wrapBg {
      background: var(--bg-main);
      border: 1px solid var(--line);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-soft);
      overflow: hidden;
    }

    /* Header */
    #tkExec .topbar {
      padding: 24px 32px;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
      background: var(--card);
    }
    #tkExec .hTitle {
      margin: 0;
      font-weight: 700;
      color: var(--ink);
      letter-spacing: -0.5px;
      font-size: 22px;
    }
    #tkExec .hSub {
      margin-top: 10px;
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }

    /* Primary Action Button */
    #tkExec .btnx {
      padding: 10px 20px;
      border-radius: var(--radius-pill);
      font-weight: 600;
      font-size: 14px;
      color: #2b6cb0;
      background: var(--p-blue-bg);
      border: 1px solid var(--p-blue-border);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
      cursor: pointer;
    }
    #tkExec .btnx:hover {
      background: var(--p-blue);
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(174, 203, 250, 0.4);
    }

    #tkExec .ico { width: 18px; height: 18px; display: inline-block; }
    #tkExec .ico svg { width: 100%; height: 100%; display: block; }

    #tkExec .content { padding: 32px; display: flex; flex-direction: column; gap: 24px; }

    /* Sections */
    #tkExec .section {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--radius-md);
      box-shadow: 0 4px 20px -10px rgba(0,0,0,0.02);
      overflow: hidden;
      transition: box-shadow 0.3s ease;
    }
    #tkExec .section:hover {
      box-shadow: var(--shadow-hover);
    }
    #tkExec .sectionTop {
      padding: 20px 24px;
      border-bottom: 1px solid var(--line);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    #tkExec .sectionT {
      font-weight: 600;
      font-size: 16px;
      color: var(--ink);
      display: flex;
      gap: 8px;
      align-items: center;
    }
    #tkExec .sectionHint {
      font-size: 13px;
      font-weight: 400;
      color: var(--muted);
      margin-top: 4px;
    }
    #tkExec .sectionBody { padding: 24px; }

    /* Tags / Badges (Pastel Style) */
    #tkExec .tag, #tkExec .kBadge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 6px 14px; border-radius: var(--radius-pill);
      font-weight: 500; font-size: 13px;
      color: var(--ink-light);
    }
    
    #tkExec .tag.blue, #tkExec .kBadge.blue { background: var(--p-blue-bg); border: 1px solid var(--p-blue-border); }
    #tkExec .tag.green, #tkExec .kBadge.green { background: var(--p-green-bg); border: 1px solid var(--p-green-border); }
    #tkExec .tag.amber, #tkExec .kBadge.amber { background: var(--p-amber-bg); border: 1px solid var(--p-amber-border); }
    #tkExec .tag.red, #tkExec .kBadge.red { background: var(--p-red-bg); border: 1px solid var(--p-red-border); }
    #tkExec .tag.slate, #tkExec .kBadge.slate { background: var(--p-slate-bg); border: 1px solid var(--p-slate-border); }
    
    /* Default Tag */
    #tkExec .tag:not(.blue):not(.green):not(.amber):not(.red):not(.slate) {
      background: var(--bg-main); border: 1px solid var(--line);
    }

    /* KPI grid */
    #tkExec .kpis { display: grid; grid-template-columns: repeat(12, 1fr); gap: 20px; }
    #tkExec .kpi {
      grid-column: span 4;
      border: 1px solid var(--line);
      border-radius: var(--radius-md);
      padding: 24px;
      background: var(--card);
      position: relative;
    }
    #tkExec .kTop { display: flex; justify-content: space-between; align-items: flex-start; }
    #tkExec .kLabel { font-weight: 500; color: var(--muted); font-size: 13px; }
    #tkExec .kValue { font-weight: 700; font-size: 36px; color: var(--ink); margin-top: 12px; font-variant-numeric: tabular-nums; }
    #tkExec .kMeta { margin-top: 12px; color: var(--muted-light); font-size: 12px; line-height: 1.5; }

    /* Progress/Gauge */
    #tkExec .gauge {
      margin-top: 16px; height: 6px; border-radius: var(--radius-pill);
      background: var(--line); overflow: hidden;
    }
    #tkExec .gauge > span {
      display: block; height: 100%; width: 0%;
      background: var(--p-blue); border-radius: var(--radius-pill);
    }

    /* Layout grids */
    #tkExec .grid2 { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; }
    #tkExec .grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; }

    /* Chart wrappers */
    #tkExec .chartWrap {
      padding: 24px; border: 1px solid var(--line);
      border-radius: var(--radius-md); background: var(--card);
    }
    #tkExec .chartHead { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; }
    #tkExec .chartT { font-weight: 600; color: var(--ink); display: flex; gap: 8px; align-items: center; font-size: 15px; }
    #tkExec .chartHint { font-size: 13px; font-weight: 400; color: var(--muted); margin-top: 4px; }
    #tkExec .chartBox { height: 280px; position: relative; }
    #tkExec .chartBox.sm { height: 220px; }
    #tkExec canvas { width: 100% !important; height: 100% !important; }

    /* Minimalist Tables */
    #tkExec table { width: 100%; border-collapse: collapse; }
    #tkExec th, #tkExec td { text-align: left; padding: 14px 12px; border-bottom: 1px solid var(--line); vertical-align: middle; }
    #tkExec th { font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    #tkExec td { font-weight: 500; color: var(--ink); font-size: 14px; }
    #tkExec tbody tr:last-child td { border-bottom: none; }
    #tkExec .bar {
      height: 6px; border-radius: var(--radius-pill); background: var(--line);
      overflow: hidden; min-width: 120px;
    }
    #tkExec .bar > span { display: block; height: 100%; background: var(--p-blue); }

    /* Callout */
    #tkExec .callout {
      border: 1px solid var(--p-amber-border);
      background: var(--p-amber-bg);
      border-radius: var(--radius-sm);
      padding: 16px 20px;
      display: flex; gap: 16px; align-items: flex-start;
      color: var(--ink); font-size: 14px; line-height: 1.6;
    }
    #tkExec .callout b { font-weight: 600; }
    #tkExec .text { color: var(--ink-light); }

    /* Responsive */
    @media(max-width: 1200px){ #tkExec .kpi { grid-column: span 6; } #tkExec .grid3 { grid-template-columns: 1fr; } }
    @media(max-width: 992px){
      #tkExec .topbar { flex-direction: column; align-items: flex-start; }
      #tkExec .grid2 { grid-template-columns: 1fr; }
      #tkExec .kpi { grid-column: span 12; }
    }
  </style>

  @php
    $I = function($name){
      $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13h8V3H3v10z"/><path d="M13 21h8V11h-8v10z"/><path d="M13 3h8v6h-8V3z"/><path d="M3 17h8v4H3v-4z"/></svg>',
        'ticket'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8z"/><path d="M9 12h6"/></svg>',
        'check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
        'alert'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
        'clock'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>',
        'users'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'trend'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 7-7"/><path d="M14 8h6v6"/></svg>',
        'priority'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4"/><path d="M4 4h14l-2 5 2 5H4"/></svg>',
        'star'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.77 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>',
      ];
      return $icons[$name] ?? $icons['dashboard'];
    };
    $avgText = is_null($avgH) ? '—' : number_format((float)$avgH, 1).' h';
  @endphp

  <div class="wrapBg">
    {{-- Header --}}
    <div class="topbar">
      <div>
        <h3 class="hTitle">Centro de Control — Tickets</h3>
        <div class="hSub">
          <span class="tag blue"><span class="ico">{!! $I('trend') !!}</span> Monitoreo</span>
          <span class="tag"><span class="ico">{!! $I('clock') !!}</span> SLA</span>
          <span class="tag"><span class="ico">{!! $I('users') !!}</span> Desempeño</span>
        </div>
      </div>
      <a class="btnx primary" href="{{ route('tickets.index') }}">
        <span class="ico">{!! $I('ticket') !!}</span> Ver tickets
      </a>
    </div>

    <div class="content">
      {{-- Executive narrative --}}
      <div class="section">
        <div class="sectionTop">
          <div>
            <div class="sectionT"><span class="ico">{!! $I('dashboard') !!}</span> Resumen ejecutivo</div>
            <div class="sectionHint">Panorama rápido para tomar decisiones: presión, ritmo de cierre y salud del proceso.</div>
          </div>
          <span class="kBadge {{ $h['class'] }}">
            <span class="ico">{!! $h['class']==='red' ? $I('alert') : ($h['class']==='amber' ? $I('alert') : $I('check')) !!}</span>
            {{ $h['label'] }}
          </span>
        </div>
        <div class="sectionBody">
          <div class="callout">
            <span class="ico">{!! $I('alert') !!}</span>
            <div class="text">
              <b>Lectura:</b> {{ $h['desc'] }}<br><br>
              Actualmente hay <b>{{ $open }}</b> tickets abiertos y <b>{{ $closed }}</b> cerrados. 
              La tasa de cierre global es <b>{{ $closeRate }}%</b>.
              En riesgos de SLA: <b>{{ $over }}</b> vencidos y <b>{{ $soon }}</b> por vencer.
              Tiempo promedio global: <b>{{ $avgText }}</b>.
            </div>
          </div>
        </div>
      </div>

      {{-- KPIs --}}
      <div class="kpis">
        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Abiertos</div>
            <span class="kBadge blue"><span class="ico">{!! $I('ticket') !!}</span></span>
          </div>
          <div class="kValue">{{ $open }}</div>
          <div class="gauge"><span style="width:{{ $total ? round(($open/$total)*100) : 0 }}%; background: var(--p-blue);"></span></div>
          <div class="kMeta">Proporción del total: {{ $total ? round(($open/$total)*100) : 0 }}%</div>
        </div>

        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Cerrados</div>
            <span class="kBadge green"><span class="ico">{!! $I('check') !!}</span></span>
          </div>
          <div class="kValue">{{ $closed }}</div>
          <div class="gauge"><span style="width:{{ $closeRate }}%; background: var(--p-green);"></span></div>
          <div class="kMeta">Tasa de cierre: {{ $closeRate }}%</div>
        </div>

        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Vencidos</div>
            <span class="kBadge red"><span class="ico">{!! $I('alert') !!}</span></span>
          </div>
          <div class="kValue">{{ $over }}</div>
          <div class="gauge"><span style="width:{{ min(100,$overRate) }}%; background: var(--p-red);"></span></div>
          <div class="kMeta">Incidencia en abiertos: {{ $overRate }}%</div>
        </div>

        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Por vencer (≤ 24h)</div>
            <span class="kBadge amber"><span class="ico">{!! $I('clock') !!}</span></span>
          </div>
          <div class="kValue">{{ $soon }}</div>
          <div class="gauge"><span style="width:{{ min(100,$soonRate) }}%; background: var(--p-amber);"></span></div>
          <div class="kMeta">Incidencia en abiertos: {{ $soonRate }}%</div>
        </div>
      </div>

      {{-- CHARTS --}}
      <div class="section">
        <div class="sectionTop">
          <div>
            <div class="sectionT"><span class="ico">{!! $I('dashboard') !!}</span> Gráficas operativas</div>
            <div class="sectionHint">Asignaciones, eficiencia y tendencia actualizados en tiempo real.</div>
          </div>
        </div>
        <div class="sectionBody" style="background: var(--bg-main);">
          <div class="grid2">
            <div>
              <div class="chartWrap mb-3">
                <div class="chartHead">
                  <div>
                    <div class="chartT"><span class="ico">{!! $I('users') !!}</span> Asignación por Usuario</div>
                  </div>
                  <span class="tag blue">Top</span>
                </div>
                <div class="chartBox"><canvas id="chAssigned"></canvas></div>
              </div>

              <div class="chartWrap">
                <div class="chartHead">
                  <div>
                    <div class="chartT"><span class="ico">{!! $I('clock') !!}</span> Tiempo promedio de resolución</div>
                  </div>
                </div>
                <div class="chartBox"><canvas id="chAvgResolve"></canvas></div>
              </div>
            </div>

            <div>
              <div class="chartWrap mb-3">
                <div class="chartHead">
                  <div><div class="chartT"><span class="ico">{!! $I('ticket') !!}</span> Abiertos vs Cerrados</div></div>
                </div>
                <div class="chartBox sm"><canvas id="chOpenClosed"></canvas></div>
              </div>

              <div class="chartWrap mb-3">
                <div class="chartHead">
                  <div><div class="chartT"><span class="ico">{!! $I('alert') !!}</span> Riesgo SLA</div></div>
                </div>
                <div class="chartBox sm"><canvas id="chSla"></canvas></div>
              </div>

              <div class="chartWrap">
                <div class="chartHead">
                  <div><div class="chartT"><span class="ico">{!! $I('star') !!}</span> Calidad (0-100)</div></div>
                </div>
                <div class="chartBox sm"><canvas id="chQuality"></canvas></div>
              </div>
            </div>
          </div>

          <div style="height:24px"></div>
          <div class="chartWrap">
            <div class="chartHead">
              <div><div class="chartT"><span class="ico">{!! $I('trend') !!}</span> Tendencia Temporal</div></div>
              <span class="tag blue">Línea</span>
            </div>
            <div class="chartBox"><canvas id="chTrend"></canvas></div>
          </div>
        </div>
      </div>

      {{-- TABLES / CONTROL --}}
      <div class="section">
        <div class="sectionTop">
          <div>
            <div class="sectionT"><span class="ico">{!! $I('users') !!}</span> Desempeño por Persona</div>
          </div>
        </div>
        <div class="sectionBody" style="padding: 0; overflow-x: auto;">
          <table>
            <thead style="background: var(--bg-main);">
              <tr>
                <th style="padding-left: 24px;">Usuario</th>
                <th>Asignados</th>
                <th>Resueltos</th>
                <th>Cumplimiento</th>
                <th>Tiempo prom.</th>
                <th>Calidad</th>
                <th style="padding-right: 24px;">Score</th>
              </tr>
            </thead>
            <tbody>
              @forelse($ranking as $r)
                @php
                  $dr = (int)$r['done_rate'];
                  $qual = (int)$r['quality'];
                  $score = (int)$r['exec_score'];
                  $tagC = $score >= 80 ? 'green' : ($score >= 60 ? 'amber' : 'red');
                  $tagQ = $qual >= 80 ? 'green' : ($qual >= 60 ? 'amber' : 'red');
                @endphp
                <tr>
                  <td style="padding-left: 24px;">{{ $r['name'] }}</td>
                  <td>{{ (int)$r['assigned'] }}</td>
                  <td>{{ (int)$r['resolved'] }}</td>
                  <td><span class="tag {{ $dr >= 70 ? 'green' : ($dr >= 45 ? 'amber' : 'red') }}">{{ $dr }}%</span></td>
                  <td>{{ is_null($r['avg_hours']) ? '—' : number_format((float)$r['avg_hours'], 1).' h' }}</td>
                  <td><span class="tag {{ $tagQ }}">{{ $qual ?: '—' }}</span></td>
                  <td style="padding-right: 24px;"><span class="tag {{ $tagC }}">{{ $score }}</span></td>
                </tr>
              @empty
                <tr><td colspan="7" style="text-align: center; color: var(--muted); padding: 24px;">Sin datos suficientes</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    <div class="grid3">
        <div class="section">
          <div class="sectionTop"><div class="sectionT">Pendientes por persona</div></div>
          <div class="sectionBody" style="padding: 0;">
            <table>
              <tbody>
                @forelse($byUserPending as $row)
                  @php $c = (int)($row['count']??0); $pct = $maxPending>0 ? round(($c/$maxPending)*100) : 0; @endphp
                  <tr>
                    <td style="padding-left: 24px;">{{ $row['name'] ?? '—' }}</td>
                    <td>{{ $c }}</td>
                    <td style="padding-right: 24px;"><div class="bar"><span style="width:{{ $pct }}%"></span></div></td>
                  </tr>
                @empty
                  <tr><td colspan="3" style="text-align: center; color: var(--muted); padding: 16px;">Sin datos</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="section">
          <div class="sectionTop"><div class="sectionT">Por prioridad</div></div>
          <div class="sectionBody" style="padding: 0;">
            <table>
              <tbody>
                @forelse($byPriority as $row)
                  @php 
                    $k = (string)($row['priority']??''); $c = (int)($row['count']??0); 
                    $pct = $maxPrio>0 ? round(($c/$maxPrio)*100) : 0; 
                    $label = $priorities[$k] ?? ($k ?: '—');
                  @endphp
                  <tr>
                    <td style="padding-left: 24px;">{{ $label }}</td>
                    <td>{{ $c }}</td>
                    <td style="padding-right: 24px;"><div class="bar"><span style="width:{{ $pct }}%; background: var(--p-amber)"></span></div></td>
                  </tr>
                @empty
                  <tr><td colspan="3" style="text-align: center; color: var(--muted); padding: 16px;">Sin datos</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="section">
          <div class="sectionTop"><div class="sectionT">Riesgo (Abiertos / Vencidos)</div></div>
          <div class="sectionBody" style="padding: 0;">
            <table>
              <thead style="background: var(--bg-main);">
                <tr><th style="padding-left:24px;">Usuario</th><th>Abiertos</th><th style="padding-right:24px;">Vencidos</th></tr>
              </thead>
              <tbody>
                @forelse($workload as $row)
                  <tr>
                    <td style="padding-left: 24px;">{{ $row['name'] ?? '—' }}</td>
                    <td>{{ (int)($row['open'] ?? 0) }}</td>
                    <td style="padding-right: 24px;"><span class="tag {{ ((int)($row['overdue'] ?? 0))>0 ? 'red' : 'green' }}">{{ (int)($row['overdue'] ?? 0) }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="3" style="text-align: center; color: var(--muted); padding: 16px;">Sin datos</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  (function(){
    // Inyectar los colores pastel a Chart.js
    Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
    Chart.defaults.color = '#718096';
    Chart.defaults.scale.grid.color = '#EDF2F7';

    const pBlue = '#AECBFA';
    const pGreen = '#BCE4D3';
    const pAmber = '#FDE29F';
    const pRed = '#FFB4AB';

    const OPEN   = @json($open);
    const CLOSED = @json($closed);
    const OVER   = @json($over);
    const SOON   = @json($soon);

    const assigned = @json($chartAssigned);
    const avgRes   = @json($chartAvgRes);
    const quality  = @json($chartQuality);
    const trend    = @json($chartTrend);

    const by = (arr, key) => (arr || []).map(x => x?.[key]);
    const safeNum = (n)=> Number.isFinite(+n) ? +n : 0;

    const baseOpts = (extra={}) => ({
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { labels: { boxWidth: 12, usePointStyle: true } },
        tooltip: { backgroundColor: '#FFFFFF', titleColor: '#2D3748', bodyColor: '#4A5568', borderColor: '#EDF2F7', borderWidth: 1, padding: 12 }
      },
      ...extra
    });

    const elA = document.getElementById('chAssigned');
    if(elA) new Chart(elA, { type:'bar', data:{ labels: by(assigned,'name'), datasets:[{ label:'Asignados', data: (assigned||[]).map(x=> safeNum(x.count)), backgroundColor: pBlue, borderRadius: 6 }] }, options: baseOpts({ scales:{ y:{ beginAtZero:true, border:{display:false} }, x:{ border:{display:false} } } }) });

    const elOC = document.getElementById('chOpenClosed');
    if(elOC) new Chart(elOC, { type:'doughnut', data:{ labels:['Abiertos','Cerrados'], datasets:[{ data:[OPEN, CLOSED], backgroundColor: [pBlue, pGreen], borderWidth: 0, hoverOffset: 4 }] }, options: baseOpts({ cutout: '75%' }) });

    const elS = document.getElementById('chSla');
    if(elS) new Chart(elS, { type:'doughnut', data:{ labels:['Vencidos','Por vencer','Resto'], datasets:[{ data:[OVER, SOON, Math.max(0, OPEN - OVER - SOON)], backgroundColor: [pRed, pAmber, '#E2E8F0'], borderWidth: 0, hoverOffset: 4 }] }, options: baseOpts({ cutout: '75%' }) });

    const elAvg = document.getElementById('chAvgResolve');
    if(elAvg) new Chart(elAvg, { type:'bar', data:{ labels: by(avgRes,'name'), datasets:[{ label:'Horas prom.', data: (avgRes||[]).map(x=> safeNum(x.avg_hours)), backgroundColor: pAmber, borderRadius: 6 }] }, options: baseOpts({ scales:{ y:{ beginAtZero:true, border:{display:false} }, x:{ border:{display:false} } } }) });

    const elQ = document.getElementById('chQuality');
    if(elQ) new Chart(elQ, { type:'bar', data:{ labels: by(quality,'name'), datasets:[{ label:'Calidad', data: (quality||[]).map(x=> safeNum(x.score)), backgroundColor: pGreen, borderRadius: 6 }] }, options: baseOpts({ scales:{ y:{ beginAtZero:true, max:100, border:{display:false} }, x:{ border:{display:false} } } }) });

    const elT = document.getElementById('chTrend');
    if(elT) new Chart(elT, { type:'line', data:{ labels: (trend||[]).map(x=> x.label ?? ''), datasets:[ { label:'Abiertos', data: (trend||[]).map(x=> safeNum(x.open)), borderColor: pAmber, backgroundColor: 'rgba(253, 226, 159, 0.2)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 0 }, { label:'Cerrados', data: (trend||[]).map(x=> safeNum(x.closed)), borderColor: pGreen, backgroundColor: 'rgba(188, 228, 211, 0.2)', fill: true, tension: 0.4, borderWidth: 3, pointRadius: 0 } ] }, options: baseOpts({ interaction: { mode: 'index', intersect: false }, scales:{ y:{ beginAtZero:true, border:{display:false} }, x:{ border:{display:false} } } }) });
  })();
  </script>
</div>
@endsection