@extends('layouts.app')
@section('title', $ticket->folio.' | '.$ticket->title)

@section('content')
@php
  $statuses   = $statuses   ?? \App\Http\Controllers\Tickets\TicketController::STATUSES;
  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = $areas      ?? \App\Http\Controllers\Tickets\TicketController::AREAS;

  $actionLabels = [
    'ticket_created'    => 'Ticket creado',
    'ticket_updated'    => 'Ticket actualizado',
    'comment_added'     => 'Comentario agregado',
    'doc_uploaded'      => 'Archivo adjunto',
    'evidence_uploaded' => 'Evidencia subida',
    'ticket_completed'  => 'Ticket completado',
    'ticket_cancelled'  => 'Ticket cancelado',
  ];

  $prettyKey = function(string $k){
    $map = [
      'title'       => 'Título',
      'description' => 'Descripción',
      'priority'    => 'Prioridad',
      'area'        => 'Área',
      'status'      => 'Estatus',
      'assignee'    => 'Asignado a',
      'assignee_id' => 'Asignado a',
      'due_at'      => 'Vencimiento',
      'impact'      => 'Impacto',
      'urgency'     => 'Urgencia',
      'effort'      => 'Esfuerzo',
      'score'       => 'Score',
      'files'       => 'Archivos',
      'files_uploaded' => 'Archivos',
    ];
    return $map[$k] ?? ucfirst(str_replace('_',' ', $k));
  };

  $fmt = function($v){
    if (is_null($v) || $v === '') return '—';
    if (is_bool($v)) return $v ? 'Sí' : 'No';
    if (is_array($v)) return '—';
    if (is_string($v)) return str_replace('T', ' ', trim($v));
    return (string) $v;
  };

  $userName = fn($u) => $u ? ($u->name ?? '—') : '—';

  $userById = [];
  if (!empty($users ?? [])) {
    foreach ($users as $u) $userById[(string)$u->id] = $u->name;
  }
  $fmtAssignee = function($id) use ($userById){
    if (!$id) return '—';
    $k = (string)$id;
    return $userById[$k] ?? "Usuario #{$k}";
  };

  $sla = $ticket->sla_signal ?? 'neutral';
  $slaClass = $sla==='overdue' ? 'red' : ($sla==='due_soon' ? 'amber' : ($sla==='ok' ? 'green' : ''));

  $statusColor = function($st){
    return match($st){
      'completado' => 'green',
      'cancelado'  => 'red',
      'bloqueado'  => 'amber',
      'revision'   => 'amber',
      'pruebas'    => 'amber',
      default      => '',
    };
  };

  $canWork = auth()->id() && ((int)auth()->id() === (int)($ticket->assignee_id ?? 0));

  $folio = $ticket->folio ?? ('TKT-'.str_pad((string)$ticket->id, 4, '0', STR_PAD_LEFT));
  $stLabel = $statuses[$ticket->status] ?? ($ticket->status ?: '—');
  $prLabel = $priorities[$ticket->priority] ?? ($ticket->priority ?: '—');
  $arLabel = $areas[$ticket->area] ?? ($ticket->area ?: 'Sin área');

  $slaLabel = $sla==='overdue' ? 'Vencido' : ($sla==='due_soon' ? 'Por vencer' : ($sla==='ok' ? 'En tiempo' : 'Sin fecha'));
@endphp

<div id="tkPremium">
  <style>
    /* =========================
      AISLADO: SOLO #tkPremium
    ========================== */
    #tkPremium{
      --bg1:#f7f9ff;
      --bg2:#f3f6ff;

      --card:#ffffff;
      --ink:#0b1220;
      --muted:rgba(15,23,42,.62);
      --line:rgba(15,23,42,.10);

      --brand:#4f52e8;
      --brandSoft:rgba(79,82,232,.10);

      --shadow:0 18px 60px rgba(2,6,23,.10);
      --shadow2:0 10px 24px rgba(2,6,23,.08);

      --greenBg:rgba(16,185,129,.14); --greenBr:rgba(16,185,129,.24); --greenTx:#0f766e;
      --amberBg:rgba(245,158,11,.14); --amberBr:rgba(245,158,11,.24); --amberTx:#92400e;
      --redBg:rgba(239,68,68,.14);    --redBr:rgba(239,68,68,.24);    --redTx:#b91c1c;
      --slateBg:rgba(2,6,23,.03);     --slateBr:rgba(15,23,42,.10);   --slateTx:#0b1220;

      --r16:16px;
      --r18:18px;
      --r22:22px;
    }

    #tkPremium .bleed{
      background: radial-gradient(1200px 500px at 10% -10%, rgba(79,82,232,.12), transparent 55%),
                  radial-gradient(900px 520px at 90% 0%, rgba(59,130,246,.10), transparent 55%),
                  linear-gradient(180deg, var(--bg1), var(--bg2));
      padding: 18px 0 44px;
      overflow-x:hidden;
    }
    #tkPremium .wrap{ max-width: 1180px; margin: 0 auto; padding: 0 16px; }

    /* Layout: main + rail */
    #tkPremium .grid{
      display:grid;
      grid-template-columns: 1.1fr .9fr;
      gap: 14px;
      align-items:start;
    }

    /* Card */
    #tkPremium .cardx{
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--r22);
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    #tkPremium .cardy{
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: var(--r22);
      box-shadow: var(--shadow2);
      overflow:hidden;
    }

    /* Top header */
    #tkPremium .hero{
      display:flex;
      justify-content:space-between;
      gap: 12px;
      padding: 16px 18px;
      border-bottom: 1px solid var(--line);
      background: linear-gradient(180deg, #ffffff, #f7f9ff);
    }

    #tkPremium .heroL{ min-width:0; }
    #tkPremium .topline{
      display:flex;
      align-items:center;
      gap: 10px;
      flex-wrap:wrap;
      margin-bottom: 8px;
    }
    #tkPremium .chip{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
      font-size: 12px;
      font-weight: 600;
      color: rgba(100,116,139,.85);
      background:#fff;
      border: 1px solid rgba(15,23,42,.10);
      padding: 6px 10px;
      border-radius: 999px;
    }
    #tkPremium .h1{
      margin: 0;
      font-size: 22px;
      font-weight: 650;
      color: var(--ink);
      letter-spacing: -.01em;
      line-height: 1.15;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 820px;
    }
    #tkPremium .metaLine{
      display:flex; gap: 14px; flex-wrap:wrap;
      color: var(--muted);
      font-weight: 450;
      font-size: 13px;
      margin-top: 6px;
    }
    #tkPremium .metaLine span{
      display:inline-flex; align-items:center; gap: 8px;
    }
    #tkPremium .ico16{ width:16px; height:16px; color: rgba(100,116,139,.85); flex: 0 0 auto; }

    /* Pills */
    #tkPremium .pills{ display:flex; flex-wrap:wrap; gap: 8px; margin-top: 10px; }
    #tkPremium .pill{
      display:inline-flex; align-items:center; gap: 8px;
      padding: 7px 10px;
      border-radius: 999px;
      border: 1px solid var(--slateBr);
      background: var(--slateBg);
      color: var(--slateTx);
      font-size: 12px;
      font-weight: 550;
      white-space: nowrap;
    }
    #tkPremium .pill svg{ width:16px; height:16px; opacity:.85; }
    #tkPremium .pill.green{ background: var(--greenBg); border-color: var(--greenBr); color: var(--greenTx); }
    #tkPremium .pill.amber{ background: var(--amberBg); border-color: var(--amberBr); color: var(--amberTx); }
    #tkPremium .pill.red{   background: var(--redBg);   border-color: var(--redBr);   color: var(--redTx); }
    #tkPremium .pill.brand{ background: var(--brandSoft); border-color: rgba(79,82,232,.22); color: #1e1b4b; }

    /* Actions */
    #tkPremium .heroR{
      display:flex; gap: 10px; flex-wrap:wrap; justify-content:flex-end;
      align-content:flex-start;
    }
    #tkPremium .btnx{
      border: 1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      padding: 10px 12px;
      background:#fff;
      color: var(--ink);
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap: 10px;
      box-shadow: var(--shadow2);
      font-weight: 550;
      transition: transform .06s ease, box-shadow .2s ease, border-color .15s ease, background .15s ease;
      cursor:pointer;
    }
    #tkPremium .btnx:hover{
      box-shadow: 0 16px 40px rgba(2,6,23,.12);
      border-color: rgba(79,82,232,.22);
    }
    #tkPremium .btnx:active{ transform: translateY(1px); }
    #tkPremium .btnx.primary{
      background: var(--brand);
      border-color: rgba(79,82,232,.28);
      color:#fff;
      box-shadow: 0 16px 40px rgba(79,82,232,.20);
    }
    #tkPremium .btnx.primary:hover{ filter: brightness(.98); }
    #tkPremium .btnx.good{
      background: var(--greenBg);
      border-color: var(--greenBr);
      color: #064e3b;
    }
    #tkPremium .btnx.danger{
      background: var(--redBg);
      border-color: var(--redBr);
      color: #7f1d1d;
    }
    #tkPremium .ico18{ width:18px; height:18px; }

    /* Sections */
    #tkPremium .sectionHead{
      padding: 14px 18px;
      border-bottom: 1px solid var(--line);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 10px;
      background: linear-gradient(180deg, #fff, #fbfcff);
    }
    #tkPremium .sectionHead h3{
      margin:0;
      font-size: 14px;
      font-weight: 650;
      color: var(--ink);
      letter-spacing: -.01em;
      display:flex; align-items:center; gap: 10px;
    }
    #tkPremium .sectionBody{ padding: 14px 18px; }

    /* Ticket Info: tidy rows */
    #tkPremium .infoGrid{
      display:grid;
      grid-template-columns: 1fr;
      gap: 10px;
    }
    #tkPremium .row{
      display:grid;
      grid-template-columns: 160px 1fr;
      gap: 12px;
      padding: 10px 12px;
      border: 1px solid rgba(15,23,42,.08);
      border-radius: var(--r16);
      background: rgba(2,6,23,.015);
      align-items:start;
    }
    #tkPremium .k{
      font-size: 11px;
      font-weight: 650;
      color: rgba(100,116,139,.85);
      text-transform: uppercase;
      letter-spacing: .06em;
    }
    #tkPremium .v{
      font-size: 13px;
      font-weight: 500;
      color: var(--ink);
      white-space: pre-wrap;
    }
    #tkPremium .v strong{ font-weight: 650; }

    /* Rail blocks */
    #tkPremium .stack{ display:grid; gap: 14px; }

    /* Attachments */
    #tkPremium .formGrid{
      display:grid; grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    #tkPremium label{
      display:block;
      font-size: 12px;
      font-weight: 550;
      color: rgba(15,23,42,.70);
      margin-bottom: 6px;
    }
    #tkPremium .input, #tkPremium select, #tkPremium textarea{
      width:100%;
      border: 1px solid rgba(15,23,42,.12);
      border-radius: 14px;
      padding: 10px 12px;
      background:#fff;
      outline:none;
      font-weight: 500;
      color: var(--ink);
    }
    #tkPremium textarea{ min-height: 92px; resize: vertical; }
    #tkPremium .help{
      margin-top:6px;
      font-size: 12px;
      color: rgba(100,116,139,.85);
      font-weight: 450;
    }
    #tkPremium .rightActions{ display:flex; justify-content:flex-end; margin-top: 12px; }

    #tkPremium .doc{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap: 12px;
      padding: 12px;
      border: 1px solid rgba(15,23,42,.08);
      border-radius: var(--r18);
      background:#fff;
      margin-bottom: 10px;
      transition: border-color .15s ease, box-shadow .2s ease, transform .12s ease;
    }
    #tkPremium .doc:hover{
      border-color: rgba(79,82,232,.18);
      box-shadow: 0 16px 40px rgba(2,6,23,.10);
      transform: translateY(-1px);
    }
    #tkPremium .doc .name{
      font-weight: 600;
      color: var(--ink);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      max-width: 320px;
    }
    #tkPremium .doc .meta{
      font-weight: 450;
      color: rgba(100,116,139,.90);
      font-size: 12px;
      margin-top: 4px;
      line-height: 1.4;
    }
    #tkPremium .docBtns{ display:flex; gap: 8px; flex-wrap:wrap; justify-content:flex-end; }
    #tkPremium .btnMini{
      height: 40px;
      padding: 0 12px;
      border-radius: 12px;
      border: 1px solid rgba(15,23,42,.10);
      background:#fff;
      color: var(--ink);
      text-decoration:none;
      display:inline-flex; align-items:center; gap: 8px;
      font-weight: 550;
      transition: transform .06s ease, box-shadow .2s ease, border-color .15s ease, background .15s ease;
      cursor:pointer;
    }
    #tkPremium .btnMini:hover{ box-shadow: 0 14px 30px rgba(2,6,23,.10); border-color: rgba(79,82,232,.18); }
    #tkPremium .btnMini:active{ transform: translateY(1px); }
    #tkPremium .btnMini.danger{ background: var(--redBg); border-color: var(--redBr); color:#7f1d1d; }

    /* Comments */
    #tkPremium .comment{
      border: 1px solid rgba(15,23,42,.08);
      border-radius: var(--r18);
      padding: 12px;
      background: rgba(2,6,23,.015);
      margin-bottom: 10px;
    }
    #tkPremium .cTop{
      display:flex;
      justify-content:space-between;
      gap: 10px;
      align-items:flex-start;
    }
    #tkPremium .cName{ font-weight: 650; color: var(--ink); }
    #tkPremium .cTime{ font-weight: 450; color: rgba(100,116,139,.90); font-size: 12px; }
    #tkPremium .cBody{ margin-top: 8px; white-space: pre-wrap; color: var(--ink); font-weight: 450; line-height: 1.6; }

    /* History */
    #tkPremium .audit{
      border: 1px solid rgba(15,23,42,.08);
      border-radius: var(--r18);
      padding: 12px;
      background:#fff;
      margin-bottom: 10px;
    }
    #tkPremium .a1{ font-weight: 650; color: var(--ink); }
    #tkPremium .a2{ margin-top: 4px; font-weight: 450; color: rgba(100,116,139,.90); font-size: 12px; }
    #tkPremium details summary{
      user-select:none; cursor:pointer;
      font-weight: 550;
      color: rgba(100,116,139,.95);
      margin-top: 8px;
    }
    #tkPremium pre{
      margin: 8px 0 0 0;
      font-size: 12px;
      background: rgba(2,6,23,.03);
      padding: 10px;
      border-radius: 12px;
      overflow:auto;
      border: 1px solid rgba(15,23,42,.08);
    }

    /* Alerts (bootstrap friendly) */
    #tkPremium .alert{ border-radius: 16px; border: 1px solid rgba(15,23,42,.08); }

    @media(max-width: 992px){
      #tkPremium .grid{ grid-template-columns: 1fr; }
      #tkPremium .row{ grid-template-columns: 1fr; }
      #tkPremium .formGrid{ grid-template-columns: 1fr; }
      #tkPremium .h1{ max-width: 100%; }
      #tkPremium .doc .name{ max-width: 100%; }
      #tkPremium .hero{ flex-direction: column; }
      #tkPremium .heroR{ justify-content:flex-start; }
    }
  </style>

  <div class="bleed">
    <div class="wrap">

      @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
      @if(session('err')) <div class="alert alert-danger">{{ session('err') }}</div> @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <strong>Revisa:</strong>
          <ul class="mb-0">
            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif

      <div class="cardx">

        {{-- HERO --}}
        <div class="hero">
          <div class="heroL">
            <div class="topline">
              <span class="chip">{{ $folio }}</span>
              <span class="pill {{ $statusColor($ticket->status) ?: '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>
                </svg>
                {{ $stLabel }}
              </span>
              <span class="pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20.59 13.41 11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82Z"/><path d="M7 7h.01"/>
                </svg>
                {{ $arLabel }}
              </span>
              <span class="pill">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2l7 4v6c0 5-3 9-7 10C8 21 5 17 5 12V6l7-4z"/>
                </svg>
                {{ $prLabel }}
              </span>
              <span class="pill {{ $slaClass ?: '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>
                </svg>
                {{ $slaLabel }}
              </span>
              <span class="pill brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 3v18h18"/><path d="M7 14l3-3 3 3 5-6"/>
                </svg>
                Score: {{ $ticket->score ?? '—' }}
              </span>
            </div>

            <h1 class="h1">{{ $ticket->title }}</h1>

            <div class="metaLine">
              <span>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/>
                </svg>
                Creado por: {{ optional($ticket->creator)->name ?: '—' }}
              </span>
              <span>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/>
                </svg>
                Asignado: {{ optional($ticket->assignee)->name ?: '—' }}
              </span>
              <span>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>
                </svg>
                Vence: {{ $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : '—' }}
              </span>
            </div>
          </div>

          <div class="heroR">
            <a class="btnx" href="{{ route('tickets.index') }}">
              <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 18l-6-6 6-6"/>
              </svg>
              Volver
            </a>

            @if(\Illuminate\Support\Facades\Route::has('tickets.work') && $canWork)
              <a class="btnx primary" href="{{ route('tickets.work',$ticket) }}">
                <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 3v18h18"/><path d="M7 14l3-3 3 3 5-6"/>
                </svg>
                Trabajar
              </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('tickets.complete'))
              <form method="POST" action="{{ route('tickets.complete',$ticket) }}">
                @csrf
                <button class="btnx good" type="submit" onclick="return confirm('¿Marcar como completado?');">
                  <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5"/>
                  </svg>
                  Completar
                </button>
              </form>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('tickets.cancel'))
              <form method="POST" action="{{ route('tickets.cancel',$ticket) }}">
                @csrf
                <button class="btnx danger" type="submit" onclick="return confirm('¿Cancelar este ticket?');">
                  <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 6l12 12"/><path d="M18 6 6 18"/>
                  </svg>
                  Cancelar
                </button>
              </form>
            @endif
          </div>
        </div>

        {{-- BODY --}}
        <div class="sectionBody">
          <div class="grid">

            {{-- MAIN: Información --}}
            <div class="cardy">
              <div class="sectionHead">
                <h3>
                  <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12h6"/><path d="M9 16h6"/><path d="M10 2h4"/><path d="M12 14v.01"/>
                    <path d="M8 2h8v4H8z"/><path d="M6 6h12v16H6z"/>
                  </svg>
                  Información del ticket
                </h3>
              </div>

              <div class="sectionBody">
                <div class="infoGrid">
                  <div class="row">
                    <div class="k">Título</div>
                    <div class="v">{{ $ticket->title ?: '—' }}</div>
                  </div>

                  <div class="row">
                    <div class="k">Descripción</div>
                    <div class="v">{{ $ticket->description ?: '—' }}</div>
                  </div>

                  <div class="row">
                    <div class="k">Asignado a</div>
                    <div class="v">{{ optional($ticket->assignee)->name ?: '—' }}</div>
                  </div>

                  <div class="row">
                    <div class="k">Área</div>
                    <div class="v">{{ $arLabel }}</div>
                  </div>

                  <div class="row">
                    <div class="k">Prioridad</div>
                    <div class="v">{{ $prLabel }}</div>
                  </div>

                  <div class="row">
                    <div class="k">Estatus</div>
                    <div class="v">{{ $stLabel }}</div>
                  </div>

                  <div class="row">
                    <div class="k">Vencimiento</div>
                    <div class="v">{{ $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : '—' }}</div>
                  </div>

                  <div class="row">
                    <div class="k">Matriz (1–5)</div>
                    <div class="v">
                      Impacto: <strong>{{ $ticket->impact ?? '—' }}</strong> ·
                      Urgencia: <strong>{{ $ticket->urgency ?? '—' }}</strong> ·
                      Esfuerzo: <strong>{{ $ticket->effort ?? '—' }}</strong>
                    </div>
                  </div>

                  <div class="row">
                    <div class="k">Score</div>
                    <div class="v">{{ $ticket->score ?? '—' }}</div>
                  </div>

                  @if($ticket->completed_at)
                    <div class="row">
                      <div class="k">Completado</div>
                      <div class="v">{{ $ticket->completed_at->format('Y-m-d H:i') }}</div>
                    </div>
                  @endif

                  @if($ticket->cancelled_at)
                    <div class="row">
                      <div class="k">Cancelado</div>
                      <div class="v">{{ $ticket->cancelled_at->format('Y-m-d H:i') }}</div>
                    </div>
                  @endif
                </div>
              </div>
            </div>

            {{-- RAIL --}}
            <div class="stack">

              {{-- Adjuntos --}}
              <div class="cardy">
                <div class="sectionHead">
                  <h3>
                    <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21.44 11.05 12.25 20.24a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
                    </svg>
                    Adjuntos
                  </h3>
                </div>

                <div class="sectionBody">
                  <form method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="formGrid">
                      <div>
                        <label>Nombre</label>
                        <input class="input" name="name" placeholder="Ej. Evidencia, PDF, Captura…" value="{{ old('name') }}">
                      </div>
                      <div>
                        <label>Categoría</label>
                        <select name="category">
                          <option value="adjunto">Adjunto</option>
                          <option value="evidencia">Evidencia</option>
                          <option value="doc">Documento</option>
                          <option value="link">Link</option>
                        </select>
                      </div>
                    </div>

                    <div style="height:10px"></div>

                    <div class="formGrid">
                      <div>
                        <label>Archivo</label>
                        <input class="input" type="file" name="file" accept="*/*">
                        <div class="help">PDF, Word, Excel, imágenes, videos, etc.</div>
                      </div>
                      <div>
                        <label>o Link externo</label>
                        <input class="input" name="external_url" placeholder="https://..." value="{{ old('external_url') }}">
                        <div class="help">Si es link, no subas archivo.</div>
                      </div>
                    </div>

                    <div class="rightActions">
                      <button class="btnx primary" type="submit">
                        <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M12 5v14"/><path d="M5 12h14"/>
                        </svg>
                        Subir
                      </button>
                    </div>
                  </form>

                  <div style="height:12px"></div>

                  @forelse($ticket->documents as $d)
                    <div class="doc">
                      <div style="min-width:0">
                        <div class="name">
                          {{ $d->name }}
                          <span class="pill" style="padding:4px 8px; margin-left:8px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M8 7h8"/><path d="M8 12h8"/><path d="M8 17h8"/>
                            </svg>
                            v{{ $d->version ?? 1 }}
                          </span>
                        </div>

                        <div class="meta">
                          {{ $d->category ?? 'adjunto' }}
                          · {{ optional($d->uploader)->name ?: '—' }}
                          · {{ optional($d->created_at)->format('Y-m-d H:i') }}
                        </div>

                        @if(!empty($d->external_url))
                          <div class="meta">
                            Link:
                            <a href="{{ $d->external_url }}" target="_blank" rel="noopener noreferrer">{{ $d->external_url }}</a>
                          </div>
                        @endif
                      </div>

                      <div class="docBtns">
                        @if($d->path)
                          <a class="btnMini" href="{{ route('tickets.documents.download',[$ticket,$d]) }}">
                            <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                              <path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>
                            </svg>
                            Descargar
                          </a>
                        @endif

                        <form method="POST" action="{{ route('tickets.documents.destroy',[$ticket,$d]) }}" onsubmit="return confirm('¿Eliminar adjunto?');">
                          @csrf
                          @method('DELETE')
                          <button class="btnMini danger" type="submit">
                            <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                              <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/>
                              <path d="M10 11v6"/><path d="M14 11v6"/>
                            </svg>
                            Eliminar
                          </button>
                        </form>
                      </div>
                    </div>
                  @empty
                    <div class="help">Aún no hay adjuntos.</div>
                  @endforelse
                </div>
              </div>

              {{-- Comentarios --}}
              <div class="cardy">
                <div class="sectionHead">
                  <h3>
                    <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
                    </svg>
                    Comentarios
                  </h3>
                </div>

                <div class="sectionBody">
                  <form method="POST" action="{{ route('tickets.comments.store',$ticket) }}">
                    @csrf
                    <label>Nuevo comentario</label>
                    <textarea name="body" placeholder="Escribe aquí…">{{ old('body') }}</textarea>
                    <div class="rightActions">
                      <button class="btnx primary" type="submit">
                        <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M22 2 11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                        Publicar
                      </button>
                    </div>
                  </form>

                  <div style="height:12px"></div>

                  @forelse($ticket->comments as $c)
                    <div class="comment">
                      <div class="cTop">
                        <div class="cName">{{ optional($c->user)->name ?: '—' }}</div>
                        <div class="cTime">{{ optional($c->created_at)->format('Y-m-d H:i') }}</div>
                      </div>
                      <div class="cBody">{{ $c->body }}</div>
                    </div>
                  @empty
                    <div class="help">Aún no hay comentarios.</div>
                  @endforelse
                </div>
              </div>

              {{-- Historial --}}
              <div class="cardy">
                <div class="sectionHead">
                  <h3>
                    <svg class="ico18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>
                    </svg>
                    Historial
                  </h3>
                </div>

                <div class="sectionBody">
                  @forelse($ticket->audits as $a)
                    @php
                      $label = $actionLabels[$a->action] ?? $a->action;
                      $diff  = (array) ($a->diff ?? []);

                      $tinfo = (array) ($diff['ticket'] ?? []);
                      $filesUploaded = $diff['files_uploaded'] ?? null;
                      $filesList = (array) ($diff['files'] ?? []);

                      $payload = (array) ($diff['payload'] ?? []);
                      if (isset($payload['files']) && is_array($payload['files'])) unset($payload['files']);
                    @endphp

                    <div class="audit">
                      <div class="a1">{{ $label }} · {{ $userName($a->user) }}</div>
                      <div class="a2">{{ optional($a->created_at)->format('Y-m-d H:i') }}</div>

                      {{-- ticket_created (nuevo) --}}
                      @if($a->action === 'ticket_created' && !empty($tinfo))
                        <div style="margin-top:10px; display:grid; gap:8px;">
                          @foreach($tinfo as $k => $v)
                            @php
                              $val = $fmt($v);
                              if ($k === 'assignee') $val = $fmtAssignee($v);
                            @endphp
                            <div class="row" style="background:#fff;">
                              <div class="k">{{ $prettyKey($k) }}</div>
                              <div class="v">{{ $val }}</div>
                            </div>
                          @endforeach

                          @if(!is_null($filesUploaded))
                            <div class="row" style="background:#fff;">
                              <div class="k">Archivos subidos</div>
                              <div class="v">{{ (int)$filesUploaded }}</div>
                            </div>
                          @endif

                          @if(!empty($filesList))
                            <div class="row" style="background:#fff;">
                              <div class="k">Archivos</div>
                              <div class="v">
                                @foreach($filesList as $f)
                                  @php
                                    $n = $f['name'] ?? 'Archivo';
                                    $m = $f['mime'] ?? '';
                                    $s = isset($f['size']) ? number_format(((int)$f['size'])/1024/1024, 2).' MB' : '';
                                  @endphp
                                  <div style="font-weight:550;">• {{ $n }} <span style="color:rgba(15,23,42,.55); font-weight:450;">{{ $m ? "({$m})" : '' }} {{ $s ? "· {$s}" : '' }}</span></div>
                                @endforeach
                              </div>
                            </div>
                          @endif
                        </div>
                      @endif

                      {{-- ticket_created (viejo) --}}
                      @if($a->action === 'ticket_created' && empty($tinfo) && !empty($payload))
                        <div style="margin-top:10px; display:grid; gap:8px;">
                          @foreach($payload as $k => $v)
                            @php
                              $val = $fmt($v);
                              if ($k === 'assignee_id') $val = $fmtAssignee($v);
                            @endphp
                            <div class="row" style="background:#fff;">
                              <div class="k">{{ $prettyKey($k) }}</div>
                              <div class="v">{{ $val }}</div>
                            </div>
                          @endforeach
                        </div>
                      @endif

                      {{-- ticket_updated (nuevo) --}}
                      @if($a->action === 'ticket_updated' && !empty($diff['before']) && !empty($diff['after']))
                        @php
                          $before = (array) $diff['before'];
                          $after  = (array) $diff['after'];
                          $keys = ['title','description','priority','area','status','assignee_id','due_at','impact','urgency','effort','score'];
                          $changes = [];
                          foreach ($keys as $k){
                            $b = $before[$k] ?? null;
                            $n = $after[$k] ?? null;
                            if ($b != $n) $changes[$k] = ['from'=>$b,'to'=>$n];
                          }
                        @endphp

                        @if(!empty($changes))
                          <div style="margin-top:10px; border:1px solid rgba(15,23,42,.08); border-radius:18px; overflow:hidden; background:#fff;">
                            <div style="padding:10px 12px; font-weight:650; border-bottom:1px solid rgba(15,23,42,.08); background:linear-gradient(180deg,#fff,#f7f9ff);">
                              Cambios realizados
                            </div>
                            <div style="padding:10px 12px; display:grid; gap:10px;">
                              @foreach($changes as $k => $c)
                                @php
                                  $from = $fmt($c['from']);
                                  $to   = $fmt($c['to']);
                                  if ($k === 'assignee_id') { $from = $fmtAssignee($c['from']); $to = $fmtAssignee($c['to']); }
                                @endphp
                                <div style="display:grid; grid-template-columns:160px 1fr; gap:12px;">
                                  <div class="k">{{ $prettyKey($k) }}</div>
                                  <div class="v">
                                    <span style="color:rgba(15,23,42,.55); font-weight:450;">{{ $from }}</span>
                                    <span style="margin:0 8px; color:rgba(15,23,42,.35)">→</span>
                                    <span style="font-weight:550;">{{ $to }}</span>
                                  </div>
                                </div>
                              @endforeach
                            </div>
                          </div>
                        @else
                          <div class="help" style="margin-top:10px;">Se guardó sin cambios visibles.</div>
                        @endif
                      @endif

                      @if(!empty($a->diff))
                        <details>
                          <summary>Ver detalles (soporte)</summary>
                          <pre>{{ json_encode($a->diff, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        </details>
                      @endif
                    </div>
                  @empty
                    <div class="help">Sin historial todavía.</div>
                  @endforelse
                </div>
              </div>

            </div>{{-- /stack --}}
          </div>{{-- /grid --}}
        </div>{{-- /sectionBody --}}
      </div>{{-- /cardx --}}

    </div>
  </div>
</div>
@endsection