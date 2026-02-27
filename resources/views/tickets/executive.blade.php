@extends('layouts.app')
@section('title','Tickets | Centro de Control Ejecutivo')

@section('content')
@php
  /**
   * ✅ Dashboard Ejecutivo (SaaS Premium & Modern UI)
   * ✅ Diseño intacto (mismos estilos/estructura)
   * ✅ Cambios: NO gráficas repetidas + score por usuario + tiempo UI + activos ahora
   */

  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES ?? [
      'alta' => 'Alta', 'media' => 'Media', 'baja' => 'Baja'
  ];

  $stats = $stats ?? [
    'open' => 0, 'closed' => 0, 'overdue' => 0, 'due_soon' => 0, 'avg_hours' => null,
  ];

  $byUserPending = $byUserPending ?? collect();
  $byPriority    = $byPriority    ?? collect();
  $workload      = $workload      ?? collect();

  $assignedByUser   = $assignedByUser   ?? collect();
  $resolvedByUser   = $resolvedByUser   ?? collect();
  $avgResolveByUser = $avgResolveByUser ?? collect();
  $qualityByUser    = $qualityByUser    ?? collect();
  $trend            = $trend            ?? collect();

  // ✅ NUEVOS (no rompen si vienen vacíos)
  $userScoreByUser  = $userScoreByUser  ?? collect();  // avg tickets.score por usuario (ventana KPI)
  $userUiTimeByUser = $userUiTimeByUser ?? collect();  // horas UI por usuario (audits elapsed_seconds)
  $activeNowByUser  = $activeNowByUser  ?? collect();  // tickets abiertos con updated_at reciente por usuario
  $activeTickets    = $activeTickets    ?? collect();  // lista rápida (opcional)

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
    if($overRate >= 20) return ['label'=>'Crítico', 'class'=>'danger', 'desc'=>'Alta presión por vencimientos. Reasigna urgentes.'];
    if($overRate >= 10 || $soonRate >= 25) return ['label'=>'En alerta', 'class'=>'warning', 'desc'=>'Riesgo de incumplimiento. Enfoca hoy en por vencer.'];
    return ['label'=>'Estable', 'class'=>'success', 'desc'=>'Buen control. Mantén el ritmo de operación.'];
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

  // ========= SERIES PARA GRÁFICAS =========
  $chartAssigned = ($assignedByUser->count() ? $assignedByUser : $byUserPending)
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'count'=>(int)($r['count'] ?? 0)])->values();

  $chartResolved = ($resolvedByUser->count() ? $resolvedByUser : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'count'=>(int)($r['count'] ?? 0)])->values();

  $chartAvgRes = ($avgResolveByUser->count() ? $avgResolveByUser : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'avg_hours'=>(float)($r['avg_hours'] ?? 0)])->values();

  $chartQuality = ($qualityNorm->count() ? $qualityNorm : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'score'=>(int)($r['score'] ?? 0)])->values();

  $chartTrend = ($trend->count() ? $trend : collect())->values();

  // ✅ NUEVAS SERIES
  $chartPriority = ($byPriority->count() ? $byPriority : collect())
      ->map(fn($r)=>['priority'=>$r['priority'] ?? '', 'count'=>(int)($r['count'] ?? 0)])->values();

  $chartUserScore = ($userScoreByUser->count() ? $userScoreByUser : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'avg_score'=>$r['avg_score'], 'n'=>(int)($r['n'] ?? 0)])->values();

  $chartUiTime = ($userUiTimeByUser->count() ? $userUiTimeByUser : collect())
      ->map(fn($r)=>['name'=>$r['name'] ?? '—', 'ui_hours'=>(float)($r['ui_hours'] ?? 0)])->values();

  // ✅ Índice rápidos para mostrar "Activos ahora" en minitabla
  $activeNowIdx = [];
  foreach(($activeNowByUser ?? []) as $row){
    $activeNowIdx[(string)($row['name'] ?? '')] = (int)($row['count'] ?? 0);
  }

@endphp

<div class="container py-4" id="tkPremium">
  <style>
    /* =========================================
       Premium SaaS Center — Modern & Clean UI
       ✅ 100% encapsulado dentro de #tkPremium
       ========================================= */
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    #tkPremium{
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;

      /* ✅ Espacio con header (sin tocar estilos globales) */
      padding-top: 18px;
      position: relative;
      z-index: 0;

      /* Modern Core Colors */
      --brand: #4F46E5;
      --brand-hover: #4338CA;
      --brand-bg: #EEF2FF;

      --text-main: #0F172A;
      --text-muted: #64748B;
      --text-light: #94A3B8;

      --bg-body: #F8FAFC;
      --card-bg: #FFFFFF;
      --border-light: #E2E8F0;

      /* Functional Colors */
      --success: #10B981; --success-bg: #D1FAE5; --success-text: #065F46;
      --warning: #F59E0B; --warning-bg: #FEF3C7; --warning-text: #92400E;
      --danger:  #EF4444; --danger-bg:  #FEE2E2; --danger-text:  #991B1B;
      --info:    #3B82F6; --info-bg:    #DBEAFE; --info-text:    #1E40AF;

      /* Radii & Shadows */
      --radius-xl: 20px;
      --radius-lg: 16px;
      --radius-md: 12px;
      --radius-sm: 8px;
      --radius-pill: 9999px;

      --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
      --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
      --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
      --shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);

      /* Animations */
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #tkPremium *{ box-sizing: border-box; }

    /* ✅ Animación con nombre único para no chocar */
    @keyframes tkpFadeUp{
      from{ opacity: 0; transform: translateY(15px); }
      to{ opacity: 1; transform: translateY(0); }
    }
    #tkPremium .animate-in{ animation: tkpFadeUp .6s ease-out forwards; opacity: 0; }
    #tkPremium .delay-1{ animation-delay: .1s; }
    #tkPremium .delay-2{ animation-delay: .2s; }
    #tkPremium .delay-3{ animation-delay: .3s; }

    #tkPremium .wrapBg{
      margin-top: 10px; /* ✅ aire extra del header */
      background: var(--bg-body);
      border-radius: var(--radius-xl);
      overflow: hidden;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border-light);
    }

    /* Topbar */
    #tkPremium .topbar{
      padding: 28px 32px;
      background: var(--card-bg);
      border-bottom: 1px solid var(--border-light);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
    }
    #tkPremium .hTitle{ margin:0; font-weight:800; color:var(--text-main); font-size:24px; letter-spacing:-.02em; }
    #tkPremium .hSub{ margin-top:12px; display:flex; flex-wrap:wrap; gap:10px; }

    /* Buttons */
    #tkPremium .btn-premium{
      padding:10px 24px;
      border-radius: var(--radius-pill);
      font-weight:600;
      font-size:14px;
      color:#fff;
      background: linear-gradient(135deg, var(--brand), #6366F1);
      border:none;
      display:inline-flex;
      align-items:center;
      gap:8px;
      cursor:pointer;
      box-shadow: 0 4px 14px 0 rgba(79,70,229,.39);
      transition: var(--transition);
      text-decoration:none;
      white-space: nowrap;
    }
    #tkPremium .btn-premium:hover{
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(79,70,229,.40);
    }

    /* Icons */
    #tkPremium .ico{ width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; }
    #tkPremium .ico svg{ width:100%; height:100%; display:block; }

    #tkPremium .content{ padding:32px; display:flex; flex-direction:column; gap:28px; }

    /* Sections */
    #tkPremium .section{
      background: var(--card-bg);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
    }
    #tkPremium .section:hover{ box-shadow: var(--shadow-lg); }

    #tkPremium .sectionTop{
      padding:20px 24px;
      border-bottom: 1px solid var(--border-light);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap: 14px;
    }
    #tkPremium .sectionT{ font-weight:700; font-size:16px; color:var(--text-main); display:flex; gap:8px; align-items:center; }
    #tkPremium .sectionHint{ font-size:13px; font-weight:500; color:var(--text-muted); margin-top:4px; }
    #tkPremium .sectionBody{ padding:24px; }

    /* Badges */
    #tkPremium .badge{
      display:inline-flex; align-items:center; gap:6px;
      padding:6px 12px; border-radius: var(--radius-pill);
      font-weight:600; font-size:12px; letter-spacing:.3px;
    }
    #tkPremium .badge-subtle{ background: var(--bg-body); color: var(--text-muted); border:1px solid var(--border-light); }

    #tkPremium .badge.success{ background: var(--success-bg); color: var(--success-text); }
    #tkPremium .badge.warning{ background: var(--warning-bg); color: var(--warning-text); }
    #tkPremium .badge.danger{  background: var(--danger-bg);  color: var(--danger-text); }
    #tkPremium .badge.info{    background: var(--info-bg);    color: var(--info-text); }

    /* KPIs Grid */
    #tkPremium .kpis{ display:grid; grid-template-columns: repeat(4, 1fr); gap:24px; }
    #tkPremium .kpi{
      background: var(--card-bg);
      border:1px solid var(--border-light);
      border-radius: var(--radius-lg);
      padding:24px;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      position:relative;
      overflow:hidden;
    }
    #tkPremium .kpi:hover{ transform: translateY(-4px); box-shadow: var(--shadow-hover); border-color:#CBD5E1; }

    #tkPremium .kTop{ display:flex; justify-content:space-between; align-items:center; gap: 10px; }
    #tkPremium .kLabel{ font-weight:600; color:var(--text-muted); font-size:14px; }

    #tkPremium .kIconWrap{ width:40px; height:40px; border-radius: var(--radius-md); display:flex; align-items:center; justify-content:center; }
    #tkPremium .kIconWrap.blue{  background: var(--info-bg);    color: var(--info); }
    #tkPremium .kIconWrap.green{ background: var(--success-bg); color: var(--success); }
    #tkPremium .kIconWrap.red{   background: var(--danger-bg);  color: var(--danger); }
    #tkPremium .kIconWrap.amber{ background: var(--warning-bg); color: var(--warning); }

    #tkPremium .kValue{ font-weight:800; font-size:36px; color:var(--text-main); margin-top:16px; font-variant-numeric: tabular-nums; line-height:1; }
    #tkPremium .kMeta{ margin-top:16px; color:var(--text-muted); font-size:13px; font-weight:500; display:flex; justify-content:space-between; gap: 10px; }

    #tkPremium .gauge{ margin-top:12px; height:6px; border-radius: var(--radius-pill); background: var(--border-light); overflow:hidden; }
    #tkPremium .gauge > span{ display:block; height:100%; border-radius: var(--radius-pill); width:0; transition: width 1.5s cubic-bezier(0.22,1,0.36,1); }

    /* Grids */
    #tkPremium .grid2{ display:grid; grid-template-columns: 1fr 1fr; gap:24px; }
    #tkPremium .grid3{ display:grid; grid-template-columns: repeat(3, 1fr); gap:24px; }

    /* Charts */
    #tkPremium .chartWrap{ padding:24px; border:1px solid var(--border-light); border-radius: var(--radius-lg); background: var(--card-bg); transition: var(--transition); }
    #tkPremium .chartWrap:hover{ box-shadow: var(--shadow-md); }
    #tkPremium .chartHead{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px; gap: 10px; }
    #tkPremium .chartT{ font-weight:700; color:var(--text-main); display:flex; gap:8px; align-items:center; font-size:15px; }
    #tkPremium .chartBox{ height:280px; position:relative; width:100%; }
    #tkPremium .chartBox.sm{ height:220px; }
    #tkPremium canvas{ width:100% !important; height:100% !important; }

    /* Tables */
    #tkPremium table{ width:100%; border-collapse: separate; border-spacing:0; }
    #tkPremium th, #tkPremium td{ text-align:left; padding:16px 20px; border-bottom:1px solid var(--border-light); vertical-align:middle; }
    #tkPremium th{
      font-size:12px; color: var(--text-light); font-weight:700;
      text-transform: uppercase; letter-spacing:.8px;
      background:#F8FAFC;
      border-top:1px solid var(--border-light);
      border-bottom:2px solid var(--border-light);
    }
    #tkPremium td{ font-weight:600; color: var(--text-main); font-size:14px; transition: var(--transition); }
    #tkPremium tbody tr{ transition: var(--transition); }
    #tkPremium tbody tr:hover td{ background: var(--bg-body); }
    #tkPremium tbody tr:last-child td{ border-bottom:none; }

    #tkPremium .table-bar{ height:6px; border-radius: var(--radius-pill); background: var(--border-light); overflow:hidden; min-width:100px; }
    #tkPremium .table-bar > span{ display:block; height:100%; transition: width 1s ease-out; }

    /* Callout */
    #tkPremium .callout{
      border:1px solid var(--info-bg);
      background: linear-gradient(to right, #F0F9FF, #FFFFFF);
      border-left:4px solid var(--info);
      border-radius: var(--radius-md);
      padding:20px 24px;
      display:flex; gap:16px; align-items:flex-start;
      color: var(--text-main); font-size:14.5px; line-height:1.6;
      box-shadow: var(--shadow-sm);
    }
    #tkPremium .callout b{ font-weight:700; color: var(--brand); }

    /* Responsive */
    @media(max-width: 1200px){
      #tkPremium .kpis{ grid-template-columns: repeat(2, 1fr); }
      #tkPremium .grid3{ grid-template-columns: 1fr; }
    }
    @media(max-width: 992px){
      #tkPremium .topbar{ flex-direction: column; align-items:flex-start; }
      #tkPremium .grid2{ grid-template-columns: 1fr; }
    }
    @media(max-width: 640px){
      #tkPremium .kpis{ grid-template-columns: 1fr; }
      #tkPremium .topbar{ padding: 22px 18px; }
      #tkPremium .content{ padding: 20px; }
    }
  </style>

  @php
    $I = function($name){
      $icons = [
        'dashboard' => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10z"/><path d="M13 21h8V11h-8v10z"/><path d="M13 3h8v6h-8V3z"/><path d="M3 17h8v4H3v-4z"/></svg>',
        'ticket'    => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4V8z"/><path d="M9 12h6"/></svg>',
        'check'     => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
        'alert'     => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
        'clock'     => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>',
        'users'     => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'trend'     => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M3 17l6-6 4 4 7-7"/><path d="M14 8h6v6"/></svg>',
        'star'      => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.77 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg>',
        'bulb'      => '<svg fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 21h6"/><path d="M12 22v-1"/><path d="M15 11.5c0 2.5-3 4.5-3 4.5s-3-2-3-4.5a3 3 0 0 1 6 0z"/><circle cx="12" cy="7" r="4"/></svg>',
      ];
      return $icons[$name] ?? $icons['dashboard'];
    };
    $avgText = is_null($avgH) ? '—' : number_format((float)$avgH, 1).' h';
  @endphp

    {{-- Header --}}
    <div class="topbar">
      <div>
        <h3 class="hTitle">Centro de Control de Tickets</h3>
        <div class="hSub">
          <span class="badge badge-subtle"><span class="ico">{!! $I('trend') !!}</span> Monitoreo en vivo</span>
          <span class="badge badge-subtle"><span class="ico">{!! $I('clock') !!}</span> SLA Tracked</span>
          <span class="badge badge-subtle"><span class="ico">{!! $I('users') !!}</span> Team Performance</span>
        </div>
      </div>
      <a class="btn-premium" href="{{ route('tickets.index') }}">
        <span class="ico">{!! $I('ticket') !!}</span> Administrar Tickets
      </a>
    </div>

    <div class="content">
      {{-- Executive Summary --}}
      <div class="section animate-in delay-1">
        <div class="sectionTop">
          <div>
            <div class="sectionT"><span class="ico">{!! $I('bulb') !!}</span> Inteligencia Operativa</div>
            <div class="sectionHint">Resumen analítico para la toma de decisiones inmediatas.</div>
          </div>
          <span class="badge {{ $h['class'] }}">
            <span class="ico">{!! $h['class']==='danger' ? $I('alert') : ($h['class']==='warning' ? $I('alert') : $I('check')) !!}</span>
            Estado: {{ $h['label'] }}
          </span>
        </div>
        <div class="sectionBody">
          <div class="callout">
            <span class="ico" style="color: var(--info); margin-top: 2px;">{!! $I('alert') !!}</span>
            <div class="text">
              <b>Lectura Ejecutiva:</b> {{ $h['desc'] }}<br><br>
              Actualmente operamos con <b>{{ $open }}</b> tickets abiertos y <b>{{ $closed }}</b> cerrados, logrando una tasa de cierre global del <b>{{ $closeRate }}%</b>.
              En cuanto a riesgos de SLA: tenemos <b>{{ $over }}</b> casos vencidos y <b>{{ $soon }}</b> próximos a vencer.
              El tiempo promedio de resolución global se mantiene en <b>{{ $avgText }}</b>.
            </div>
          </div>
        </div>
      </div>

      {{-- KPIs Interactivos --}}
      <div class="kpis animate-in delay-2">
        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Abiertos Activos</div>
            <div class="kIconWrap blue"><span class="ico">{!! $I('ticket') !!}</span></div>
          </div>
          <div class="kValue">{{ $open }}</div>
          <div class="kMeta">
            <span>Proporción total</span>
            <span>{{ $total ? round(($open/$total)*100) : 0 }}%</span>
          </div>
          <div class="gauge"><span style="width:{{ $total ? round(($open/$total)*100) : 0 }}%; background: var(--info);"></span></div>
        </div>

        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Tickets Cerrados</div>
            <div class="kIconWrap green"><span class="ico">{!! $I('check') !!}</span></div>
          </div>
          <div class="kValue">{{ $closed }}</div>
          <div class="kMeta">
            <span>Tasa de éxito</span>
            <span>{{ $closeRate }}%</span>
          </div>
          <div class="gauge"><span style="width:{{ $closeRate }}%; background: var(--success);"></span></div>
        </div>

        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Vencidos (Riesgo)</div>
            <div class="kIconWrap red"><span class="ico">{!! $I('alert') !!}</span></div>
          </div>
          <div class="kValue">{{ $over }}</div>
          <div class="kMeta">
            <span>Incidencia actual</span>
            <span>{{ $overRate }}%</span>
          </div>
          <div class="gauge"><span style="width:{{ min(100,$overRate) }}%; background: var(--danger);"></span></div>
        </div>

        <div class="kpi">
          <div class="kTop">
            <div class="kLabel">Por Vencer (≤ 24h)</div>
            <div class="kIconWrap amber"><span class="ico">{!! $I('clock') !!}</span></div>
          </div>
          <div class="kValue">{{ $soon }}</div>
          <div class="kMeta">
            <span>Alerta temprana</span>
            <span>{{ $soonRate }}%</span>
          </div>
          <div class="gauge"><span style="width:{{ min(100,$soonRate) }}%; background: var(--warning);"></span></div>
        </div>
      </div>

      {{-- CHARTS GRID --}}
      <div class="grid2 animate-in delay-3">
        {{-- Izquierda --}}
        <div style="display: flex; flex-direction: column; gap: 24px;">
          <div class="chartWrap">
            <div class="chartHead">
              <div><div class="chartT"><span class="ico">{!! $I('trend') !!}</span> Evolución Temporal</div></div>
              <span class="badge badge-subtle">Histórico</span>
            </div>
            <div class="chartBox"><canvas id="chTrend"></canvas></div>
          </div>

          <div class="chartWrap">
            <div class="chartHead">
              <div><div class="chartT"><span class="ico">{!! $I('users') !!}</span> Carga de Trabajo por Agente</div></div>
            </div>
            <div class="chartBox"><canvas id="chAssigned"></canvas></div>
          </div>
        </div>

        {{-- Derecha --}}
        <div class="grid2" style="gap: 24px;">
          <div class="chartWrap" style="grid-column: span 2;">
            <div class="chartHead">
              <div><div class="chartT"><span class="ico">{!! $I('clock') !!}</span> Velocidad de Resolución (Horas)</div></div>
            </div>
            <div class="chartBox sm"><canvas id="chAvgResolve"></canvas></div>
          </div>

          <div class="chartWrap">
            <div class="chartHead">
              <div><div class="chartT" style="font-size: 14px;"><span class="ico">{!! $I('ticket') !!}</span> Score por Usuario</div></div>
            </div>
            <div class="chartBox sm"><canvas id="chOpenClosed"></canvas></div>
          </div>

          <div class="chartWrap">
            <div class="chartHead">
              <div><div class="chartT" style="font-size: 14px;"><span class="ico">{!! $I('alert') !!}</span> Salud SLA</div></div>
            </div>
            <div class="chartBox sm"><canvas id="chSla"></canvas></div>
          </div>
        </div>
      </div>

      {{-- TABLAS DE DESEMPEÑO --}}
      <div class="section animate-in delay-3">
        <div class="sectionTop" style="border-bottom: none; padding-bottom: 0;">
          <div><div class="sectionT"><span class="ico">{!! $I('star') !!}</span> Leaderboard de Desempeño</div></div>
        </div>
        <div class="sectionBody" style="padding: 24px 0 0 0; overflow-x: auto;">
          <table>
            <thead>
              <tr>
                <th style="padding-left: 32px;">Agente</th>
                <th>Asignados</th>
                <th>Resueltos</th>
                <th>Tasa Cumplimiento</th>
                <th>Velocidad Prom.</th>
                <th>Calidad Q/A</th>
                <th style="padding-right: 32px;">Score Global</th>
              </tr>
            </thead>
            <tbody>
              @forelse($ranking as $r)
                @php
                  $dr = (int)$r['done_rate'];
                  $qual = (int)$r['quality'];
                  $score = (int)$r['exec_score'];
                  $tagC = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
                  $tagQ = $qual >= 80 ? 'success' : ($qual >= 60 ? 'warning' : 'danger');
                @endphp
                <tr>
                  <td style="padding-left: 32px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                      <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--bg-body); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--brand);">
                        {{ strtoupper(substr($r['name'], 0, 1)) }}
                      </div>
                      {{ $r['name'] }}
                    </div>
                  </td>
                  <td>{{ (int)$r['assigned'] }}</td>
                  <td>{{ (int)$r['resolved'] }}</td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span>{{ $dr }}%</span>
                      <div class="table-bar" style="min-width: 60px;">
                        <span style="width:{{ $dr }}%; background: {{ $dr >= 70 ? 'var(--success)' : ($dr >= 45 ? 'var(--warning)' : 'var(--danger)') }}"></span>
                      </div>
                    </div>
                  </td>
                  <td>{{ is_null($r['avg_hours']) ? '—' : number_format((float)$r['avg_hours'], 1).' h' }}</td>
                  <td><span class="badge {{ $tagQ }}">{{ $qual ?: '—' }}</span></td>
                  <td style="padding-right: 32px;"><span class="badge {{ $tagC }}">{{ $score }} / 100</span></td>
                </tr>
              @empty
                <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">No hay datos registrados aún.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      {{-- MINITABLAS --}}
      <div class="grid3 animate-in delay-3">
        <div class="section">
          <div class="sectionTop"><div class="sectionT">Carga de Pendientes</div></div>
          <div class="sectionBody" style="padding: 0;">
            <table>
              <tbody>
                @forelse($byUserPending->take(5) as $row)
                  @php $c = (int)($row['count']??0); $pct = $maxPending>0 ? round(($c/$maxPending)*100) : 0; @endphp
                  <tr>
                    <td style="padding-left: 24px; font-size: 13px;">{{ $row['name'] ?? '—' }}</td>
                    <td style="font-size: 13px;">{{ $c }}</td>
                    <td style="padding-right: 24px;">
                      <div class="table-bar" style="height: 4px; min-width: 50px;">
                        <span style="width:{{ $pct }}%; background: var(--brand)"></span>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 16px;">Sin datos</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="section">
          <div class="sectionTop"><div class="sectionT">Distribución por Prioridad</div></div>
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
                    <td style="padding-left: 24px; font-size: 13px;">{{ $label }}</td>
                    <td style="font-size: 13px;">{{ $c }}</td>
                    <td style="padding-right: 24px;">
                      <div class="table-bar" style="height: 4px; min-width: 50px;">
                        <span style="width:{{ $pct }}%; background: var(--warning)"></span>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 16px;">Sin datos</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="section">
          <div class="sectionTop"><div class="sectionT">Atención Crítica (Riesgo)</div></div>
          <div class="sectionBody" style="padding: 0;">
            <table>
              <thead>
                <tr>
                  <th style="padding-left:24px;">Agente</th>
                  <th>Abiertos</th>
                  <th>Vencidos</th>
                  <th style="padding-right:24px;">Activos ahora</th>
                </tr>
              </thead>
              <tbody>
                @forelse($workload->take(4) as $row)
                  @php
                    $an = (int)($activeNowIdx[$row['name'] ?? ''] ?? 0);
                  @endphp
                  <tr>
                    <td style="padding-left: 24px; font-size: 13px;">{{ $row['name'] ?? '—' }}</td>
                    <td style="font-size: 13px;">{{ (int)($row['open'] ?? 0) }}</td>
                    <td style="font-size: 13px;">
                      @if(((int)($row['overdue'] ?? 0)) > 0)
                        <span class="badge danger" style="padding: 2px 8px;">{{ (int)($row['overdue'] ?? 0) }}</span>
                      @else
                        <span class="badge success" style="padding: 2px 8px;">0</span>
                      @endif
                    </td>
                    <td style="padding-right: 24px;">
                      @if($an > 0)
                        <span class="badge info" style="padding: 2px 8px;">{{ $an }}</span>
                      @else
                        <span class="badge badge-subtle" style="padding: 2px 8px;">0</span>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 16px;">Sin datos</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  document.addEventListener("DOMContentLoaded", function() {
    const root = document.getElementById('tkPremium');
    if(!root || typeof Chart === 'undefined') return;

    const cBrand = '#4F46E5';
    const cSuccess = '#10B981';
    const cWarning = '#F59E0B';
    const cDanger = '#EF4444';
    const cInfo = '#3B82F6';

    const OPEN   = @json($open);
    const CLOSED = @json($closed);
    const OVER   = @json($over);
    const SOON   = @json($soon);

    const assigned = @json($chartAssigned);     // {name,count}
    const avgRes   = @json($chartAvgRes);       // {name,avg_hours}
    const trend    = @json($chartTrend);        // {label,open,closed}

    const workload = @json($workload);          // {name,open,overdue}
    const prio     = @json($chartPriority);     // {priority,count}
    const userScore = @json($chartUserScore);   // {name,avg_score,n}
    const uiTime = @json($chartUiTime);         // {name,ui_hours}

    const prioritiesMap = @json($priorities ?? []);

    const by = (arr, key) => (arr || []).map(x => x?.[key]);
    const safeNum = (n)=> Number.isFinite(+n) ? +n : 0;

    const baseOpts = (extra={}) => ({
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 1200, easing: 'easeOutQuart' },
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            boxWidth: 10,
            usePointStyle: true,
            padding: 20,
            font: { weight: '600', family: "'Plus Jakarta Sans', system-ui, sans-serif" }
          }
        },
        tooltip: {
          backgroundColor: '#0F172A',
          titleColor: '#FFFFFF',
          bodyColor: '#F8FAFC',
          borderColor: 'rgba(255,255,255,0.1)',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          displayColors: true,
          usePointStyle: true,
          titleFont: { family: "'Plus Jakarta Sans', system-ui, sans-serif", weight: '700' },
          bodyFont: { family: "'Plus Jakarta Sans', system-ui, sans-serif", weight: '600' }
        }
      },
      ...extra
    });

    const createGradient = (ctx, colorStart, colorEnd) => {
      const g = ctx.createLinearGradient(0, 0, 0, 400);
      g.addColorStop(0, colorStart);
      g.addColorStop(1, colorEnd);
      return g;
    };

    // 1) Carga de Trabajo por Agente (STACKED: Abiertos + Vencidos)
    const ctxA = document.getElementById('chAssigned');
    if(ctxA) {
      const labels = (workload && workload.length ? workload : (assigned||[]).map(x=>({name:x.name, open:safeNum(x.count), overdue:0}))).map(x=>x.name);
      const openData = (workload && workload.length ? workload : []).map(x=> safeNum(x.open));
      const overdueData = (workload && workload.length ? workload : []).map(x=> safeNum(x.overdue));

      const useOpen = openData.length ? openData : (assigned||[]).map(x=> safeNum(x.count));
      const useOver = overdueData.length ? overdueData : (assigned||[]).map(()=> 0);

      new Chart(ctxA.getContext('2d'), {
        type:'bar',
        data:{
          labels,
          datasets:[
            { label:'Abiertos', data: useOpen, backgroundColor: cBrand, borderRadius: 6, maxBarThickness: 38, stack:'w' },
            { label:'Vencidos', data: useOver, backgroundColor: cDanger, borderRadius: 6, maxBarThickness: 38, stack:'w' },
          ]
        },
        options: baseOpts({
          scales:{
            y:{ beginAtZero:true, stacked:true, border:{display:false}, grid:{ color:'#F1F5F9', borderDash:[4,4] }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } },
            x:{ stacked:true, border:{display:false}, grid:{ display:false }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } }
          }
        })
      });
    }

    // 2) Salud SLA (Doughnut)
    const ctxS = document.getElementById('chSla');
    if(ctxS) {
      new Chart(ctxS, {
        type:'doughnut',
        data:{
          labels:['Vencidos','Por vencer','Resto Seguro'],
          datasets:[{
            data:[OVER, SOON, Math.max(0, OPEN - OVER - SOON)],
            backgroundColor:[cDanger, cWarning, '#E2E8F0'],
            borderWidth:0,
            hoverOffset:6
          }]
        },
        options: baseOpts({ cutout:'78%', plugins:{ legend:{ position:'right' } } })
      });
    }

    // 3) Velocidad Promedio (Horas)
    const ctxAvg = document.getElementById('chAvgResolve');
    if(ctxAvg) {
      new Chart(ctxAvg, {
        type:'bar',
        data:{
          labels: by(avgRes,'name'),
          datasets:[{
            label:'Horas Promedio',
            data:(avgRes||[]).map(x=> safeNum(x.avg_hours)),
            backgroundColor:cWarning,
            borderRadius:6,
            maxBarThickness:30
          }]
        },
        options: baseOpts({
          indexAxis:'y',
          scales:{
            x:{ beginAtZero:true, border:{display:false}, grid:{ color:'#F1F5F9', borderDash:[4,4] }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } },
            y:{ border:{display:false}, grid:{ display:false }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } }
          }
        })
      });
    }

    // 4) Score por Usuario (tickets.score) — fallback: Horas UI (timer)
    const ctxOC = document.getElementById('chOpenClosed');
    if(ctxOC) {
      const labels = (userScore||[]).map(x => x.name);
      const data = (userScore||[]).map(x => (x.avg_score === null ? 0 : safeNum(x.avg_score)));

      const fallback = (!labels.length && uiTime && uiTime.length);
      const fLabels = fallback ? uiTime.map(x=>x.name) : labels;
      const fData   = fallback ? uiTime.map(x=>safeNum(x.ui_hours)) : data;
      const fLabel  = fallback ? 'Horas UI (Timer)' : 'Score Promedio (Tickets)';

      new Chart(ctxOC, {
        type:'bar',
        data:{
          labels: fLabels,
          datasets:[{
            label: fLabel,
            data: fData,
            backgroundColor: fallback ? cInfo : cSuccess,
            borderRadius: 6,
            maxBarThickness: 26
          }]
        },
        options: baseOpts({
          indexAxis:'y',
          plugins:{ legend:{ position:'bottom' } },
          scales:{
            x:{ beginAtZero:true, border:{display:false}, grid:{ color:'#F1F5F9', borderDash:[4,4] }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } },
            y:{ border:{display:false}, grid:{ display:false }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } }
          }
        })
      });
    }

    // 5) Tendencia (Area Spline)
    const ctxT = document.getElementById('chTrend');
    if(ctxT) {
      const c2d = ctxT.getContext('2d');
      new Chart(c2d, {
        type:'line',
        data:{
          labels:(trend||[]).map(x=> x.label ?? ''),
          datasets:[
            { label:'Cerrados', data:(trend||[]).map(x=> safeNum(x.closed)), borderColor:cSuccess, backgroundColor:createGradient(c2d,'rgba(16,185,129,0.3)','rgba(16,185,129,0)'), fill:true, tension:.4, borderWidth:3, pointRadius:4, pointBackgroundColor:'#fff' },
            { label:'Abiertos', data:(trend||[]).map(x=> safeNum(x.open)), borderColor:cBrand, backgroundColor:createGradient(c2d,'rgba(79,70,229,0.2)','rgba(79,70,229,0)'), fill:true, tension:.4, borderWidth:3, pointRadius:4, pointBackgroundColor:'#fff' }
          ]
        },
        options: baseOpts({
          interaction:{ mode:'index', intersect:false },
          scales:{
            y:{ beginAtZero:true, border:{display:false}, grid:{ color:'#F1F5F9', borderDash:[4,4] }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } },
            x:{ border:{display:false}, grid:{ display:false }, ticks:{ color:'#64748B', font:{ family:"'Plus Jakarta Sans', system-ui, sans-serif", weight:'600' } } }
          }
        })
      });
    }
  });
  </script>
</div>
@endsection