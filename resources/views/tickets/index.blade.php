@extends('layouts.app')
@section('title','Tickets')

@section('content')
@php
  use Illuminate\Support\Str;

  $statuses   = $statuses   ?? \App\Http\Controllers\Tickets\TicketController::STATUSES;
  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = $areas      ?? \App\Http\Controllers\Tickets\TicketController::AREAS;

  // Conteos (fallback)
  $countTotal = $ticketsTotal ?? ($tickets instanceof \Illuminate\Pagination\AbstractPaginator ? $tickets->total() : (is_countable($tickets)? count($tickets): 0));

  $countOpen = $ticketsOpen ?? null;
  $countProgress = $ticketsProgress ?? null;
  $countDone = $ticketsDone ?? null;

  if($countOpen === null || $countProgress === null || $countDone === null){
    $tmpOpen=0; $tmpProg=0; $tmpDone=0;
    foreach(($tickets ?? []) as $t){
      $st = (string)($t->status ?? '');
      if(in_array($st, ['abierto','pendiente','nuevo','open'], true)) $tmpOpen++;
      elseif(in_array($st, ['en_progreso','progreso','in_progress','working'], true)) $tmpProg++;
      elseif(in_array($st, ['resuelto','cerrado','done','closed','resolved','completado'], true)) $tmpDone++;
    }
    $countOpen = $countOpen ?? $tmpOpen;
    $countProgress = $countProgress ?? $tmpProg;
    $countDone = $countDone ?? $tmpDone;
  }

  $statusMeta = function($status) use ($statuses){
    $k = (string)$status;
    $label = $statuses[$k] ?? Str::headline(str_replace('_',' ',$k));
    $map = [
      'abierto'     => ['cls'=>'tkr-b-amber','label'=>$label ?: 'Abierto'],
      'pendiente'   => ['cls'=>'tkr-b-purple','label'=>$label ?: 'Pendiente'],
      'nuevo'       => ['cls'=>'tkr-b-amber','label'=>$label ?: 'Nuevo'],
      'open'        => ['cls'=>'tkr-b-amber','label'=>$label ?: 'Open'],
      'en_progreso' => ['cls'=>'tkr-b-blue','label'=>$label ?: 'En Progreso'],
      'progreso'    => ['cls'=>'tkr-b-blue','label'=>$label ?: 'En Progreso'],
      'in_progress' => ['cls'=>'tkr-b-blue','label'=>$label ?: 'In Progress'],
      'working'     => ['cls'=>'tkr-b-blue','label'=>$label ?: 'Working'],
      'resuelto'    => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Resuelto'],
      'cerrado'     => ['cls'=>'tkr-b-slate','label'=>$label ?: 'Cerrado'],
      'done'        => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Done'],
      'closed'      => ['cls'=>'tkr-b-slate','label'=>$label ?: 'Closed'],
      'resolved'    => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Resolved'],
      'completado'  => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Completado'],
    ];
    return $map[$k] ?? ['cls'=>'tkr-b-slate','label'=>($label ?: '—')];
  };

  $priorityMeta = function($p) use ($priorities){
    $k = (string)$p;
    $label = $priorities[$k] ?? Str::headline(str_replace('_',' ',$k));
    $map = [
      'baja'    => ['cls'=>'tkr-b-slate','label'=>$label ?: 'Baja'],
      'media'   => ['cls'=>'tkr-b-yellow','label'=>$label ?: 'Media'],
      'alta'    => ['cls'=>'tkr-b-orange','label'=>$label ?: 'Alta'],
      'urgente' => ['cls'=>'tkr-b-red','label'=>$label ?: 'Urgente'],
      'critica' => ['cls'=>'tkr-b-red','label'=>$label ?: 'Crítica'],
      'mejora'  => ['cls'=>'tkr-b-purple','label'=>$label ?: 'Mejora'],
    ];
    return $map[$k] ?? ['cls'=>'tkr-b-slate','label'=>($label ?: '—')];
  };

  $areaLabel = function($a) use ($areas){
    $k = (string)$a;
    return $areas[$k] ?? ($k ?: 'Sin área');
  };
@endphp

<div id="tkrTickets">
  <style>
    /* =========================
      AISLADO: SOLO #tkrTickets
      (sin tocar body/header global)
    ========================== */
    #tkrTickets{
      --bg: #f6f8ff;
      --card: #ffffff;
      --ink: #0f172a;
      --muted:#64748b;
      --line: rgba(15,23,42,.10);
      --brand:#4f52e8;

      --p-indigo:#eef0ff;
      --p-amber:#fff5df;
      --p-sky:#eaf4ff;
      --p-mint:#e9fff1;

      --shadow-soft: 0 10px 30px rgba(2,6,23,.06);
      --shadow-hover: 0 22px 60px rgba(2,6,23,.14);
    }

    #tkrTickets .tkr-bleed{
      background: var(--bg);
      padding: 18px 0 44px;
      overflow-x:hidden;
    }
    #tkrTickets .tkr-wrap{ max-width: 1160px; margin: 0 auto; padding: 0 16px; }

    /* Header (no negritas extremas) */
    #tkrTickets .tkr-head{
      display:flex; justify-content:space-between; align-items:flex-start;
      gap: 16px; margin: 0 0 14px;
    }
    #tkrTickets .tkr-titleBox{ display:flex; gap: 12px; align-items:flex-start; min-width: 0; }
    #tkrTickets .tkr-icon{
      width:40px;height:40px;border-radius:12px;
      background: rgba(79,82,232,.10);
      border: 1px solid rgba(79,82,232,.18);
      color: var(--brand);
      display:grid;place-items:center;
      flex: 0 0 auto;
    }
    #tkrTickets .tkr-h1{
      margin:0;
      font-size: 28px;
      font-weight: 650; /* ✅ no bold */
      letter-spacing:-.02em;
      color: var(--ink);
      line-height: 1.1;
    }
    #tkrTickets .tkr-sub{
      margin:6px 0 0 0;
      color: var(--muted);
      font-weight: 450; /* ✅ no bold */
      font-size: 13px;
    }

    #tkrTickets .tkr-new{
      display:inline-flex; align-items:center; gap: 10px;
      background: var(--brand); color: #fff; text-decoration:none;
      padding: 12px 16px; border-radius: 14px;
      font-weight: 600; /* ✅ no bold */
      box-shadow: 0 14px 30px rgba(79,82,232,.18);
      transition: transform .06s ease, box-shadow .2s ease, filter .15s ease;
      white-space: nowrap; border: 0;
    }
    #tkrTickets .tkr-new:hover{ filter: brightness(.98); box-shadow: 0 18px 34px rgba(79,82,232,.20); }
    #tkrTickets .tkr-new:active{ transform: translateY(1px); }

    /* Stats */
    #tkrTickets .tkr-stats{ display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin: 14px 0 18px; }
    #tkrTickets .tkr-stat{
      position:relative; overflow:hidden; background: var(--card);
      border: 1px solid var(--line); border-radius: 18px;
      padding: 18px; box-shadow: var(--shadow-soft);
      transition: box-shadow .2s ease, transform .12s ease;
      min-height: 96px; isolation:isolate;
    }
    #tkrTickets .tkr-stat:hover{ box-shadow: 0 16px 40px rgba(2,6,23,.10); transform: translateY(-1px); }
    #tkrTickets .tkr-bubble{ position:absolute; right:-30px; top:-30px; width:110px; height:110px; border-radius:999px; opacity:.75; z-index:0; }
    #tkrTickets .tkr-stat .tkr-sIcon{
      width:42px;height:42px;border-radius:14px; display:grid;place-items:center;
      border: 1px solid rgba(15,23,42,.06); position:relative; z-index:1;
      margin-bottom: 12px; background: #fff;
      font-weight: 600;
    }
    #tkrTickets .tkr-stat .tkr-num{
      font-size: 28px;
      font-weight: 650; /* ✅ no bold */
      color: var(--ink); line-height: 1; position:relative; z-index:1;
    }
    #tkrTickets .tkr-stat .tkr-lbl{
      margin-top:6px;
      font-size: 12px;
      font-weight: 450; /* ✅ no bold */
      color: var(--muted); position:relative; z-index:1;
    }

    #tkrTickets .tkr-stat.s1 .tkr-bubble{ background: var(--p-indigo); }
    #tkrTickets .tkr-stat.s1 .tkr-sIcon{ background: rgba(79,82,232,.10); color: var(--brand); border-color: rgba(79,82,232,.18); }
    #tkrTickets .tkr-stat.s2 .tkr-bubble{ background: var(--p-amber); }
    #tkrTickets .tkr-stat.s2 .tkr-sIcon{ background: rgba(245,158,11,.10); color:#b45309; border-color: rgba(245,158,11,.20); }
    #tkrTickets .tkr-stat.s3 .tkr-bubble{ background: var(--p-sky); }
    #tkrTickets .tkr-stat.s3 .tkr-sIcon{ background: rgba(59,130,246,.10); color:#1d4ed8; border-color: rgba(59,130,246,.20); }
    #tkrTickets .tkr-stat.s4 .tkr-bubble{ background: var(--p-mint); }
    #tkrTickets .tkr-stat.s4 .tkr-sIcon{ background: rgba(16,185,129,.10); color:#047857; border-color: rgba(16,185,129,.20); }

    /* Pills */
    #tkrTickets .tkr-badge{
      display:inline-flex; align-items:center; padding: 6px 10px;
      border-radius: 999px;
      font-weight: 550; /* ✅ no bold */
      font-size: 12px;
      border: 1px solid transparent; white-space:nowrap;
    }
    #tkrTickets .tkr-b-amber{ background:#fdecc8; color:#92400e; border-color:#f8d99a; }
    #tkrTickets .tkr-b-blue{ background:#dbeafe; color:#1d4ed8; border-color:#bfdbfe; }
    #tkrTickets .tkr-b-purple{ background:#ede9fe; color:#6d28d9; border-color:#ddd6fe; }
    #tkrTickets .tkr-b-emerald{ background:#dcfce7; color:#047857; border-color:#bbf7d0; }
    #tkrTickets .tkr-b-slate{ background:#f1f5f9; color:#334155; border-color:#e2e8f0; }
    #tkrTickets .tkr-b-yellow{ background:#fef9c3; color:#a16207; border-color:#fde68a; }
    #tkrTickets .tkr-b-orange{ background:#ffedd5; color:#9a3412; border-color:#fed7aa; }
    #tkrTickets .tkr-b-red{ background:#ffe4e6; color:#b91c1c; border-color:#fecdd3; }

    /* List */
    #tkrTickets .tkr-list{ display:flex; flex-direction:column; gap: 14px; }

    #tkrTickets .tkr-card{
      background: var(--card);
      border: 1px solid rgba(15,23,42,.06);
      border-radius: 20px;
      padding: 18px 18px;
      box-shadow: var(--shadow-soft);
      transition: transform .14s ease, box-shadow .20s ease, border-color .20s ease;
      position: relative;
      cursor: pointer; /* ✅ toda la card clickeable */
    }
    #tkrTickets .tkr-card:hover{
      transform: scale(1.01);
      box-shadow: var(--shadow-hover);
      border-color: rgba(79,82,232,.18);
    }
    #tkrTickets .tkr-cardRow{ display:flex; gap: 14px; align-items:flex-start; justify-content:space-between; }
    #tkrTickets .tkr-left{ flex:1 1 auto; min-width: 0; }
    #tkrTickets .tkr-topline{ display:flex; gap: 10px; align-items:center; flex-wrap:wrap; margin-bottom: 10px; }

    #tkrTickets .tkr-folio{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
      font-size: 12px;
      font-weight: 500; /* ✅ no bold */
      color: rgba(100,116,139,.75);
      margin-right: 4px;
    }
    #tkrTickets .tkr-ttl{
      margin: 0;
      font-size: 18px;
      font-weight: 600; /* ✅ no bold */
      letter-spacing:-.01em;
      color: var(--ink);
      transition: color .2s ease;
      white-space: nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    #tkrTickets .tkr-card:hover .tkr-ttl{ color: var(--brand); }

    #tkrTickets .tkr-desc{
      margin: 8px 0 14px;
      color: rgba(100,116,139,.95);
      font-weight: 450; /* ✅ no bold */
      font-size: 13px;
      line-height: 1.55;
      display:-webkit-box;
      -webkit-line-clamp:2;
      -webkit-box-orient:vertical;
      overflow:hidden;
    }
    #tkrTickets .tkr-meta{ display:flex; gap:16px; flex-wrap:wrap; color: rgba(100,116,139,.85); font-weight: 450; font-size: 12px; }
    #tkrTickets .tkr-meta span{ display:inline-flex; gap: 8px; align-items:center; }

    /* ✅ Botón > aparece SOLO en hover */
    #tkrTickets .tkr-chev{
      flex: 0 0 auto;
      width: 44px; height: 44px;
      border-radius: 14px;
      border: 1px solid rgba(15,23,42,.08);
      background:#fff;
      display:grid; place-items:center;
      color: rgba(148,163,184,.95);
      transition: color .2s ease, border-color .2s ease, background .2s ease, transform .2s ease, opacity .2s ease, box-shadow .2s ease;
      margin-top: 2px;

      opacity: 0;
      pointer-events: none;
      transform: translateX(-2px);
      cursor: pointer;
    }
    #tkrTickets .tkr-card:hover .tkr-chev{
      opacity: 1;
      pointer-events: auto;
      transform: translateX(0);
    }
    #tkrTickets .tkr-chev:hover{
      color: var(--brand);
      border-color: rgba(79,82,232,.20);
      background: rgba(79,82,232,.06);
      transform: translateX(2px);
      box-shadow: 0 14px 30px rgba(2,6,23,.10);
    }

    /* Drawer */
    #tkrTickets .tkr-overlay{
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.35);
      backdrop-filter: blur(8px);
      z-index: 9998;
      opacity: 0;
      pointer-events: none;
      transition: opacity .18s ease;
    }
    #tkrTickets .tkr-overlay.is-open{ opacity: 1; pointer-events: auto; }

    #tkrTickets .tkr-drawer{
      position: fixed;
      top: 0; right: 0;
      height: 100vh;
      width: min(420px, 26vw); /* ~1/4 */
      background: #fff;
      box-shadow: -20px 0 70px rgba(2,6,23,.22);
      z-index: 9999;
      overflow: auto;
      border-left: 1px solid rgba(15,23,42,.06);

      transform: translateX(100%);
      opacity: .99;
      transition: transform .22s ease, opacity .22s ease;
      will-change: transform;
    }
    #tkrTickets .tkr-drawer.is-open{ transform: translateX(0); opacity: 1; }

    #tkrTickets .tkr-dh{
      padding: 16px 18px;
      border-bottom: 1px solid rgba(15,23,42,.06);
      display:flex; justify-content:space-between; align-items:center; gap: 10px;
      background:#fff; position: sticky; top: 0; z-index: 1;
    }
    #tkrTickets .tkr-chip{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
      font-size: 12px;
      font-weight: 500; /* ✅ no bold */
      color: rgba(100,116,139,.75);
      background:#f8fafc;
      border: 1px solid rgba(15,23,42,.06);
      padding: 6px 10px;
      border-radius: 999px;
    }
    #tkrTickets .tkr-close{
      width: 40px; height: 40px;
      border-radius: 999px;
      border: 1px solid rgba(15,23,42,.10);
      background: #fff;
      display:grid; place-items:center;
      cursor:pointer;
      transition: background .15s ease, box-shadow .15s ease;
    }
    #tkrTickets .tkr-close:hover{ background:#f8fafc; box-shadow: 0 10px 24px rgba(2,6,23,.10); }

    #tkrTickets .tkr-db{ padding: 18px; }

    #tkrTickets .tkr-dtitle{
      margin: 12px 0 0;
      font-size: 22px;
      font-weight: 650; /* ✅ no bold */
      letter-spacing:-.01em;
      color: var(--ink);
    }
    #tkrTickets .tkr-dtext{
      margin-top: 12px;
      background:#f8fafc;
      border: 1px solid rgba(15,23,42,.06);
      border-radius: 16px;
      padding: 14px;
      color:#334155;
      font-weight: 450; /* ✅ no bold */
      line-height: 1.6;
      font-size: 13px;
      white-space: pre-wrap;
    }

    #tkrTickets .tkr-grid{ margin-top: 14px; display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    #tkrTickets .tkr-gi{
      background:#fff;
      border: 1px solid rgba(15,23,42,.06);
      border-radius: 16px;
      padding: 14px;
      display:flex; gap: 12px; align-items:flex-start;
    }
    #tkrTickets .tkr-ic{
      width: 40px; height: 40px;
      border-radius: 14px;
      display:grid; place-items:center;
      background: rgba(79,82,232,.10);
      border: 1px solid rgba(79,82,232,.18);
      color: var(--brand);
      flex: 0 0 auto;
    }
    #tkrTickets .tkr-k{
      font-size: 11px;
      font-weight: 500; /* ✅ no bold */
      color: rgba(100,116,139,.85);
      text-transform: uppercase;
      letter-spacing: .04em;
      margin-bottom: 2px;
    }
    #tkrTickets .tkr-v{
      font-size: 13px;
      font-weight: 550; /* ✅ moderado */
      color: var(--ink);
      word-break: break-word;
    }

    #tkrTickets .tkr-actions{
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid rgba(15,23,42,.06);
      display:flex; gap: 10px; flex-wrap:wrap;
    }
    #tkrTickets .tkr-linkBtn{
      height: 46px;
      border-radius: 14px;
      padding: 0 16px;
      border: 1px solid rgba(15,23,42,.10);
      background: #fff;
      color: var(--ink);
      font-weight: 550; /* ✅ no bold */
      text-decoration:none;
      display:inline-flex; align-items:center; justify-content:center; gap: 10px;
      transition: box-shadow .2s ease, transform .06s ease, border-color .15s ease, background .15s ease;
    }
    #tkrTickets .tkr-linkBtn:hover{ box-shadow: 0 14px 30px rgba(2,6,23,.10); border-color: rgba(79,82,232,.20); }
    #tkrTickets .tkr-linkBtn:active{ transform: translateY(1px); }

    #tkrTickets .tkr-linkBtn.work{
      background: #e9fff1;
      border-color: rgba(16,185,129,.20);
      color:#14532d;
    }

    /* Responsive */
    @media (max-width: 1024px){
      #tkrTickets .tkr-stats{ grid-template-columns: repeat(2, minmax(0,1fr)); }
      #tkrTickets .tkr-drawer{ width: min(520px, 92vw); }
    }
    @media (max-width: 560px){
      #tkrTickets .tkr-stats{ grid-template-columns: 1fr; }
    }
  </style>

  <div class="tkr-bleed">
    <div class="tkr-wrap">

      {{-- HEADER --}}
      <div class="tkr-head">
        <div class="tkr-titleBox">
          <div class="tkr-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z"/>
              <path d="M9 9h6"/><path d="M9 12h6"/><path d="M9 15h6"/>
            </svg>
          </div>
          <div style="min-width:0">
            <h1 class="tkr-h1">Sistema de Tickets</h1>
            <p class="tkr-sub">Gestiona y da seguimiento a todas las solicitudes</p>
          </div>
        </div>

        <a class="tkr-new" href="{{ route('tickets.create') }}">
          <span style="display:grid;place-items:center">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 5v14"/><path d="M5 12h14"/>
            </svg>
          </span>
          Nuevo Ticket
        </a>
      </div>

      {{-- STATS --}}
      <div class="tkr-stats">
        <div class="tkr-stat s1">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z"/>
              <path d="M9 9h6"/><path d="M9 12h6"/><path d="M9 15h6"/>
            </svg>
          </div>
          <div class="tkr-num">{{ $countTotal }}</div>
          <div class="tkr-lbl">Total Tickets</div>
        </div>

        <div class="tkr-stat s2">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">🕒</div>
          <div class="tkr-num">{{ $countOpen }}</div>
          <div class="tkr-lbl">Abiertos</div>
        </div>

        <div class="tkr-stat s3">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">△</div>
          <div class="tkr-num">{{ $countProgress }}</div>
          <div class="tkr-lbl">En Progreso</div>
        </div>

        <div class="tkr-stat s4">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">✓</div>
          <div class="tkr-num">{{ $countDone }}</div>
          <div class="tkr-lbl">Resueltos</div>
        </div>
      </div>

      {{-- ✅ SIN FILTROS (quitado) --}}

      {{-- LIST --}}
      <div class="tkr-list">
        @forelse($tickets as $t)
          @php
            $folio = $t->folio ?? ('TKT-'.str_pad((string)$t->id, 4, '0', STR_PAD_LEFT));
            $st = $statusMeta($t->status);
            $pr = $priorityMeta($t->priority);
            $assigneeName = optional($t->assignee)->name ?: 'Sin asignar';
            $area = $areaLabel($t->area);
            $created = $t->created_at ? $t->created_at->format('d M Y, H:i') : '—';

            $payload = [
              'id' => $t->id,
              'folio' => (string)$folio,
              'title' => (string)($t->title ?? '—'),
              'description' => (string)($t->description ?? ''),
              'status_label' => (string)$st['label'],
              'status_cls' => (string)$st['cls'],
              'priority_label' => (string)$pr['label'],
              'priority_cls' => (string)$pr['cls'],
              'assignee' => (string)$assigneeName,
              'area' => (string)$area,
              'email' => (string)($t->requester_email ?? $t->email ?? ''),
              'created' => (string)$created,
              'show_url' => route('tickets.show',$t),
              'can_work' => (bool)($t->assignee_id && auth()->check() && (string)auth()->id()===(string)$t->assignee_id && \Illuminate\Support\Facades\Route::has('tickets.work')),
              'work_url' => \Illuminate\Support\Facades\Route::has('tickets.work') ? route('tickets.work',$t) : '',
            ];
          @endphp

          <div class="tkr-card" data-open-ticket='@json($payload)'>
            <div class="tkr-cardRow">
              <div class="tkr-left">
                <div class="tkr-topline">
                  <span class="tkr-folio">{{ $folio }}</span>
                  <span class="tkr-badge {{ $st['cls'] }}">{{ $st['label'] }}</span>
                  <span class="tkr-badge {{ $pr['cls'] }}">{{ $pr['label'] }}</span>
                </div>

                <h3 class="tkr-ttl">{{ $t->title }}</h3>
                <div class="tkr-desc">{{ $t->description }}</div>

                <div class="tkr-meta">
                  <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/>
                    </svg>
                    {{ $assigneeName }}
                  </span>

                  <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M20.59 13.41 11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82Z"/><path d="M7 7h.01"/>
                    </svg>
                    {{ $area }}
                  </span>

                  <span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
                    </svg>
                    {{ $created }}
                  </span>
                </div>
              </div>

              {{-- Botón > (visual), pero la card también abre --}}
              <button type="button" class="tkr-chev" title="Abrir vista previa">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 18l6-6-6-6"/>
                </svg>
              </button>
            </div>
          </div>

        @empty
          <div class="tkr-card" style="cursor:default;">
            <h3 class="tkr-ttl">No se encontraron tickets</h3>
            <div class="tkr-desc">No hay resultados disponibles.</div>
          </div>
        @endforelse
      </div>

      @if($tickets instanceof \Illuminate\Pagination\AbstractPaginator && $tickets->hasPages())
        <div style="margin-top:16px;">
          {{ $tickets->links() }}
        </div>
      @endif

    </div>
  </div>

  {{-- OVERLAY + DRAWER --}}
  <div id="tkrOverlay" class="tkr-overlay" aria-hidden="true"></div>

  <aside id="tkrDrawer" class="tkr-drawer" aria-hidden="true" aria-label="Vista previa ticket">
    <div class="tkr-dh">
      <span id="tkrChip" class="tkr-chip">—</span>
      <button id="tkrClose" class="tkr-close" type="button" aria-label="Cerrar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="tkr-db">
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <span id="tkrStatus" class="tkr-badge tkr-b-slate">—</span>
        <span id="tkrPriority" class="tkr-badge tkr-b-slate">—</span>
      </div>

      <div id="tkrTitle" class="tkr-dtitle">—</div>
      <div id="tkrDesc" class="tkr-dtext">Sin descripción</div>

      <div class="tkr-grid">
        <div class="tkr-gi">
          <div class="tkr-ic">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/>
            </svg>
          </div>
          <div>
            <div class="tkr-k">Asignado</div>
            <div id="tkrAssignee" class="tkr-v">—</div>
          </div>
        </div>

        <div class="tkr-gi">
          <div class="tkr-ic">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 4h16v16H4z"/><path d="m22 6-10 7L2 6"/>
            </svg>
          </div>
          <div>
            <div class="tkr-k">Email</div>
            <div id="tkrEmail" class="tkr-v">—</div>
          </div>
        </div>

        <div class="tkr-gi">
          <div class="tkr-ic">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20.59 13.41 11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82Z"/><path d="M7 7h.01"/>
            </svg>
          </div>
          <div>
            <div class="tkr-k">Área</div>
            <div id="tkrArea" class="tkr-v">—</div>
          </div>
        </div>

        <div class="tkr-gi">
          <div class="tkr-ic">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
          </div>
          <div>
            <div class="tkr-k">Creado</div>
            <div id="tkrCreated" class="tkr-v">—</div>
          </div>
        </div>
      </div>

      {{-- ✅ Sin "Cambiar estado" (quitado) --}}

      <div class="tkr-actions">
        <a id="tkrShowBtn" class="tkr-linkBtn" href="#">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          Ver detalle
        </a>

        <a id="tkrWorkBtn" class="tkr-linkBtn work" href="#" style="display:none;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M3 6h18v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6z"/><path d="M3 12h18"/>
          </svg>
          Trabajar
        </a>
      </div>
    </div>
  </aside>

  <script>
  (function(){
    function ready(fn){
      if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
      else fn();
    }

    ready(function(){
      const root = document.getElementById('tkrTickets');
      if(!root) return;

      const overlay = document.getElementById('tkrOverlay');
      const drawer  = document.getElementById('tkrDrawer');
      const btnClose = document.getElementById('tkrClose');

      const chip = document.getElementById('tkrChip');
      const elStatus = document.getElementById('tkrStatus');
      const elPriority = document.getElementById('tkrPriority');
      const elTitle = document.getElementById('tkrTitle');
      const elDesc = document.getElementById('tkrDesc');
      const elAssignee = document.getElementById('tkrAssignee');
      const elEmail = document.getElementById('tkrEmail');
      const elArea = document.getElementById('tkrArea');
      const elCreated = document.getElementById('tkrCreated');
      const btnShow = document.getElementById('tkrShowBtn');
      const btnWork = document.getElementById('tkrWorkBtn');

      let prevOverflow = '';

      function safeText(v){ return (v === null || v === undefined || v === '') ? '—' : String(v); }

      function setBadge(el, cls, text){
        if(!el) return;
        el.className = el.className.replace(/\btkr-b-[a-z]+\b/g, '').trim();
        el.classList.add('tkr-badge');
        if(cls) el.classList.add(cls);
        el.textContent = safeText(text);
      }

      function openDrawer(data){
        chip.textContent = safeText(data.folio);

        setBadge(elStatus, data.status_cls || 'tkr-b-slate', data.status_label || '—');
        setBadge(elPriority, data.priority_cls || 'tkr-b-slate', data.priority_label || '—');

        elTitle.textContent = safeText(data.title);
        elDesc.textContent  = data.description ? String(data.description) : 'Sin descripción';

        elAssignee.textContent = safeText(data.assignee);
        elEmail.textContent    = safeText(data.email);
        elArea.textContent     = safeText(data.area);
        elCreated.textContent  = safeText(data.created);

        if(btnShow) btnShow.href = data.show_url || '#';

        if(btnWork){
          if(data.can_work && data.work_url){
            btnWork.style.display = '';
            btnWork.href = data.work_url;
          } else {
            btnWork.style.display = 'none';
            btnWork.href = '#';
          }
        }

        overlay.classList.add('is-open');
        drawer.classList.add('is-open');
        overlay.setAttribute('aria-hidden','false');
        drawer.setAttribute('aria-hidden','false');

        prevOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
      }

      function closeDrawer(){
        overlay.classList.remove('is-open');
        drawer.classList.remove('is-open');
        overlay.setAttribute('aria-hidden','true');
        drawer.setAttribute('aria-hidden','true');
        document.body.style.overflow = prevOverflow || '';
      }

      // ✅ Abre al click en la card O en el botón >
      root.addEventListener('click', function(e){
        const card = e.target.closest('[data-open-ticket]');
        if(!card) return;

        // Si le dieron click a un link dentro (por si agregas uno luego), respeta el link
        if(e.target.closest('a')) return;

        e.preventDefault();
        e.stopPropagation();

        try{
          const raw = card.getAttribute('data-open-ticket') || '{}';
          const data = JSON.parse(raw);
          openDrawer(data);
        }catch(err){
          console.error('No se pudo leer payload del ticket:', err);
        }
      });

      overlay.addEventListener('click', closeDrawer);
      btnClose.addEventListener('click', closeDrawer);

      document.addEventListener('keydown', function(e){
        if(e.key === 'Escape') closeDrawer();
      });

      drawer.addEventListener('click', function(e){
        e.stopPropagation();
      });
    });
  })();
  </script>
</div>
@endsection