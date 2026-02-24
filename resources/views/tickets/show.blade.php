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
  $slaClass = $sla==='overdue' ? 'red' : ($sla==='due_soon' ? 'amber' : ($sla==='ok' ? 'green' : 'slate'));

  $statusColor = function($st){
    return match($st){
      'completado' => 'green',
      'cancelado'  => 'red',
      'bloqueado'  => 'amber',
      'revision'   => 'amber',
      'pruebas'    => 'amber',
      default      => 'slate',
    };
  };

  $canWork = auth()->id() && ((int)auth()->id() === (int)($ticket->assignee_id ?? 0));

  $folio = $ticket->folio ?? ('TKT-'.str_pad((string)$ticket->id, 4, '0', STR_PAD_LEFT));
  $stLabel = $statuses[$ticket->status] ?? ($ticket->status ?: '—');
  $prLabel = $priorities[$ticket->priority] ?? ($ticket->priority ?: '—');
  $arLabel = $areas[$ticket->area] ?? ($ticket->area ?: 'Sin área');

  $slaLabel = $sla==='overdue' ? 'Vencido' : ($sla==='due_soon' ? 'Por vencer' : ($sla==='ok' ? 'En tiempo' : 'Sin fecha'));

  // Prioridad -> tono
  $prio = (string)($ticket->priority ?? '');
  $prioTone = match($prio){
    'alta','high','urgente','critica','crítico','critico' => 'red',
    'media','medium' => 'amber',
    'baja','low' => 'green',
    default => 'slate'
  };
@endphp

<div id="tkPremium">
  <style>
    /* =========================
      TICKET VIEW — FULL WIDTH
      Fondo degradado FULL VIEWPORT (tipo ejemplo)
      + Pasteles + Priority color + Activity scroll
    ========================== */

    /* ✅ importante: evita choques con estilos globales */
    #tkPremium{
      position: relative;
      isolation: isolate; /* el fondo se queda detrás sin afectar header */
      margin: 0 !important;
      padding: 0 !important;

      --bg-body:#f7f9ff;
      --card-bg:rgba(255,255,255,.92);

      --text-main:#0f172a;
      --text-muted:#64748b;
      --text-light:#94a3b8;

      --border-light:rgba(15,23,42,.10);
      --border-dark:rgba(15,23,42,.18);

      --radius-sm:10px;
      --radius-md:14px;
      --radius-lg:18px;

      --shadow-sm:0 1px 2px rgba(2,6,23,.06);
      --shadow-md:0 12px 34px rgba(2,6,23,.10);
      --shadow-hover:0 18px 50px rgba(2,6,23,.14);

      /* Pasteles PRO */
      --p-sky-bg:#eaf4ff;   --p-sky-br:#cfe7ff;   --p-sky-tx:#1f4e8c;
      --p-indigo-bg:#eef2ff;--p-indigo-br:#dbe4ff;--p-indigo-tx:#3730a3;
      --p-mint-bg:#eafff1;  --p-mint-br:#c9f6dd;  --p-mint-tx:#146b3a;
      --p-amber-bg:#fff5d6; --p-amber-br:#ffe6a6; --p-amber-tx:#8a4b10;
      --p-rose-bg:#ffe9ee;  --p-rose-br:#ffd0da;  --p-rose-tx:#8f1732;
      --p-slate-bg:#f1f5f9; --p-slate-br:#e2e8f0; --p-slate-tx:#334155;

      /* Status base */
      --green-bg:var(--p-mint-bg);  --green-br:var(--p-mint-br);  --green-tx:var(--p-mint-tx);
      --amber-bg:var(--p-amber-bg); --amber-br:var(--p-amber-br); --amber-tx:var(--p-amber-tx);
      --red-bg:var(--p-rose-bg);    --red-br:var(--p-rose-br);    --red-tx:var(--p-rose-tx);
      --slate-bg:var(--p-slate-bg); --slate-br:var(--p-slate-br); --slate-tx:var(--p-slate-tx);

      /* Botones pastel (fondo pastel + texto mismo tono) */
      --btn-sky-bg:var(--p-sky-bg);        --btn-sky-br:var(--p-sky-br);        --btn-sky-tx:var(--p-sky-tx);
      --btn-indigo-bg:var(--p-indigo-bg);  --btn-indigo-br:var(--p-indigo-br);  --btn-indigo-tx:var(--p-indigo-tx);
      --btn-mint-bg:var(--p-mint-bg);      --btn-mint-br:var(--p-mint-br);      --btn-mint-tx:var(--p-mint-tx);
      --btn-rose-bg:var(--p-rose-bg);      --btn-rose-br:var(--p-rose-br);      --btn-rose-tx:var(--p-rose-tx);
    }

    #tkPremium *{ box-sizing:border-box; }

    /* ✅ FULL VIEWPORT BACKGROUND (NO depende del body ni de wrappers globales) */
    #tkPremium .bleed{
      position: relative;
      min-height: 100vh;
      padding: 22px 0 46px;
      background: transparent !important;
      overflow: visible !important; /* por si algún global mete overflow */
    }

    /* Fondo fijo: siempre ocupa TODA la pantalla */
    #tkPremium .bleed::before{
      content:"";
      position: fixed;
      inset: 0;
      z-index: -1;
      pointer-events: none;

      /* parecido al ejemplo: suave, limpio, verde/menta */
      background:
        radial-gradient(1200px 520px at 12% 18%, rgba(34,197,94,.10), transparent 55%),
        radial-gradient(1100px 520px at 60% 18%, rgba(56,189,248,.12), transparent 56%),
        radial-gradient(900px 520px at 92% 22%, rgba(79,70,229,.10), transparent 58%),
        linear-gradient(180deg, #f2fff7 0%, #f7fbff 42%, #f8fafc 100%);
    }

    /* ✅ ancho grande en desktop + responsive */
    #tkPremium .wrap{
      width: min(1400px, 100%);
      margin: 0 auto;
      padding: 0 clamp(14px, 2vw, 26px);
    }

    /* Grid */
    #tkPremium .grid{
      display:grid;
      grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr);
      gap: 18px;
      align-items:start;
    }

    /* Cards */
    #tkPremium .card{
      background: var(--card-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border-light);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      overflow:hidden;
      transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }
    #tkPremium .card:hover{
      transform: translateY(-1px);
      box-shadow: var(--shadow-hover);
      border-color: var(--border-dark);
    }

    /* HERO */
    #tkPremium .hero{
      padding: 18px 18px 16px;
      margin-bottom: 18px;
    }
    #tkPremium .heroTop{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap: 14px;
      flex-wrap:wrap;
    }
    #tkPremium .topline{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      align-items:center;
      margin-bottom: 10px;
    }
    #tkPremium .folio-badge{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 12.5px;
      font-weight: 900;
      color: var(--slate-tx);
      background: var(--slate-bg);
      border: 1px solid var(--slate-br);
      padding: 5px 10px;
      border-radius: 999px;
      letter-spacing: .02em;
    }
    #tkPremium .h1{
      margin:0;
      font-size: clamp(18px, 1.2vw + 14px, 26px);
      font-weight: 950;
      color: var(--text-main);
      letter-spacing: -0.02em;
      line-height: 1.15;
    }
    #tkPremium .metaLine{
      margin-top: 10px;
      display:flex;
      gap:14px;
      flex-wrap:wrap;
      color: var(--text-muted);
      font-weight: 750;
      font-size: 12.5px;
    }
    #tkPremium .metaLine span{ display:inline-flex; align-items:center; gap:6px; }
    #tkPremium .ico16{ width:16px; height:16px; stroke-width:1.6; opacity:.92; }

    /* Pills */
    #tkPremium .pill{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding: 5px 10px;
      border-radius: 999px;
      border: 1px solid var(--slate-br);
      background: var(--slate-bg);
      color: var(--slate-tx);
      font-size: 12px;
      font-weight: 900;
      white-space: nowrap;
    }
    #tkPremium .pill svg{ width: 14px; height: 14px; opacity:.85; }
    #tkPremium .pill.green{ background: var(--green-bg); border-color: var(--green-br); color: var(--green-tx); }
    #tkPremium .pill.amber{ background: var(--amber-bg); border-color: var(--amber-br); color: var(--amber-tx); }
    #tkPremium .pill.red{ background: var(--red-bg); border-color: var(--red-br); color: var(--red-tx); }
    #tkPremium .pill.brand{ background: var(--p-indigo-bg); border-color: var(--p-indigo-br); color: var(--p-indigo-tx); }
    #tkPremium .pill.sky{ background: var(--p-sky-bg); border-color: var(--p-sky-br); color: var(--p-sky-tx); }

    /* Buttons */
    #tkPremium .heroR{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:flex-end; }
    #tkPremium .btn{
      border: 1px solid var(--border-dark);
      border-radius: 999px;
      padding: 9px 14px;
      background: #fff;
      color: var(--text-main);
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:8px;
      font-size: 13px;
      font-weight: 950;
      box-shadow: var(--shadow-sm);
      transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
      cursor:pointer;
      line-height:1;
      user-select:none;
      outline:none;
    }
    #tkPremium .btn:hover{ transform: translateY(-1px); box-shadow: var(--shadow-md); }
    #tkPremium .btn:active{ transform: translateY(0px); box-shadow: var(--shadow-sm); }
    #tkPremium .btn svg{ width:16px; height:16px; stroke-width:1.8; opacity:.95; }

    #tkPremium .btn.pastel-sky{ background: var(--btn-sky-bg); border-color: var(--btn-sky-br); color: var(--btn-sky-tx); }
    #tkPremium .btn.pastel-indigo{ background: var(--btn-indigo-bg); border-color: var(--btn-indigo-br); color: var(--btn-indigo-tx); }
    #tkPremium .btn.pastel-mint{ background: var(--btn-mint-bg); border-color: var(--btn-mint-br); color: var(--btn-mint-tx); }
    #tkPremium .btn.pastel-rose{ background: var(--btn-rose-bg); border-color: var(--btn-rose-br); color: var(--btn-rose-tx); }

    /* Section */
    #tkPremium .sectionHead{
      padding: 12px 14px;
      border-bottom: 1px solid var(--border-light);
      background: linear-gradient(to bottom, rgba(255,255,255,.70), rgba(255,255,255,.40));
    }
    #tkPremium .sectionHead h3{
      margin:0;
      font-size: 13px;
      font-weight: 950;
      color: var(--text-main);
      display:flex;
      gap:8px;
      align-items:center;
      letter-spacing:.01em;
    }
    #tkPremium .sectionBody{ padding: 14px; }

    /* Key/Value */
    #tkPremium .infoGrid{
      display:flex;
      flex-direction:column;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      overflow:hidden;
      background: rgba(255,255,255,.60);
    }
    #tkPremium .row{
      display:grid;
      grid-template-columns: 180px 1fr;
      gap: 10px;
      padding: 11px 12px;
      border-bottom: 1px solid var(--border-light);
      font-size: 13px;
    }
    #tkPremium .row:last-child{ border-bottom:none; }
    #tkPremium .row:nth-child(even){ background: rgba(248,250,252,.55); }
    #tkPremium .k{ font-weight: 950; color: var(--text-muted); }
    #tkPremium .v{ font-weight: 750; color: var(--text-main); white-space: pre-wrap; line-height: 1.45; }
    #tkPremium .v strong{ font-weight: 950; color: var(--p-indigo-tx); }

    /* Inputs */
    #tkPremium .input, #tkPremium select, #tkPremium textarea{
      width:100%;
      border: 1px solid var(--border-dark);
      border-radius: 12px;
      padding: 10px 12px;
      background: rgba(255,255,255,.90);
      font-size: 13px;
      font-family: inherit;
      color: var(--text-main);
      transition: box-shadow .15s ease, border-color .15s ease, background .15s ease;
    }
    #tkPremium textarea{ min-height: 84px; resize: vertical; }
    #tkPremium .input:focus, #tkPremium select:focus, #tkPremium textarea:focus{
      outline:none;
      border-color: rgba(79,70,229,.45);
      box-shadow: 0 0 0 4px rgba(79,70,229,.12);
      background: #fff;
    }
    #tkPremium .help{ margin-top:6px; font-size: 12px; color: var(--text-light); font-weight: 750; }
    #tkPremium .rightActions{ display:flex; justify-content:flex-end; margin-top: 10px; gap:10px; }

    /* Upload */
    #tkPremium .uploadBox{
      border: 1px dashed rgba(79,70,229,.30);
      background: rgba(238,242,255,.55);
      border-radius: var(--radius-lg);
      padding: 12px;
      display:flex;
      flex-direction:column;
      gap: 10px;
    }
    #tkPremium .uploadRow{ display:grid; grid-template-columns: 1fr; gap: 10px; }
    #tkPremium .uploadSplit{
      display:grid;
      grid-template-columns: 180px 1fr;
      gap: 10px;
    }
    #tkPremium .filePick{
      position:relative;
      border: 1px solid rgba(2,6,23,.10);
      background: rgba(255,255,255,.85);
      border-radius: 14px;
      padding: 10px 12px;
      display:flex;
      align-items:center;
      gap: 10px;
      min-height: 44px;
      overflow:hidden;
    }
    #tkPremium .filePick svg{ width:18px; height:18px; opacity:.85; }
    #tkPremium .filePick .hint{
      font-size: 12.5px;
      font-weight: 850;
      color: var(--text-muted);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      flex:1;
    }
    #tkPremium .filePick input[type="file"]{
      position:absolute;
      inset:0;
      opacity:0;
      cursor:pointer;
      width:100%;
      height:100%;
    }

    /* Documents */
    #tkPremium .doc{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 10px;
      padding: 11px 12px;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      background: rgba(255,255,255,.88);
      margin-bottom: 8px;
      transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    }
    #tkPremium .doc:hover{ transform: translateY(-1px); box-shadow: var(--shadow-sm); border-color: var(--border-dark); }
    #tkPremium .doc .name{
      font-weight: 950;
      font-size: 13px;
      color: var(--text-main);
      display:flex;
      align-items:center;
      gap: 8px;
      min-width:0;
    }
    #tkPremium .doc .meta{ font-size: 12px; color: var(--text-muted); margin-top: 3px; font-weight: 750; }

    /* Comments */
    #tkPremium .comment{ border-bottom: 1px solid var(--border-light); padding: 12px 0; }
    #tkPremium .comment:last-child{ border-bottom:none; padding-bottom:0; }
    #tkPremium .comment:first-child{ padding-top:0; }
    #tkPremium .cTop{ display:flex; justify-content:space-between; gap:10px; margin-bottom: 6px; }
    #tkPremium .cName{ font-weight: 950; font-size: 13px; color: var(--text-main); }
    #tkPremium .cTime{ font-size: 12px; color: var(--text-light); font-weight: 850; }
    #tkPremium .cBody{ font-size: 13px; color: var(--text-muted); line-height: 1.5; white-space: pre-wrap; font-weight: 750; }

    /* Activity scroll */
    #tkPremium .activityScroll{
      max-height: 520px;
      overflow:auto;
      padding-right: 6px;
      overscroll-behavior: contain;
    }
    #tkPremium .activityScroll::-webkit-scrollbar{ width: 10px; }
    #tkPremium .activityScroll::-webkit-scrollbar-track{
      background: rgba(15,23,42,.05);
      border-radius: 999px;
    }
    #tkPremium .activityScroll::-webkit-scrollbar-thumb{
      background: rgba(79,70,229,.22);
      border-radius: 999px;
      border: 2px solid rgba(255,255,255,.60);
    }
    #tkPremium .activityScroll::-webkit-scrollbar-thumb:hover{
      background: rgba(79,70,229,.32);
    }

    /* Audit */
    #tkPremium .audit{
      position:relative;
      padding-left: 18px;
      padding-bottom: 14px;
      border-left: 2px solid rgba(15,23,42,.10);
    }
    #tkPremium .audit::before{
      content:'';
      position:absolute;
      left:-6px;
      top: 2px;
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background: rgba(79,70,229,.35);
      border: 2px solid rgba(255,255,255,.95);
      box-shadow: 0 6px 18px rgba(2,6,23,.10);
    }
    #tkPremium .audit:last-child{ border-left-color: transparent; padding-bottom:0; }
    #tkPremium .a1{ font-weight: 950; font-size: 13px; color: var(--text-main); }
    #tkPremium .a2{ font-size: 12px; color: var(--text-light); margin-top: 2px; font-weight: 850; }

    #tkPremium .audit-box{
      margin-top: 8px;
      border: 1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      overflow:hidden;
      font-size: 12px;
      background: rgba(255,255,255,.86);
    }
    #tkPremium .audit-box-header{
      background: rgba(241,245,249,.75);
      padding: 7px 10px;
      font-weight: 950;
      border-bottom: 1px solid rgba(15,23,42,.08);
      color: var(--text-muted);
    }
    #tkPremium .audit-box-body{ padding: 10px; }

    /* Alerts */
    #tkPremium .alert{
      padding: 12px 14px;
      border-radius: var(--radius-lg);
      margin-bottom: 14px;
      font-size: 13px;
      font-weight: 950;
      display:flex;
      gap: 10px;
      align-items:center;
      border: 1px solid var(--border-light);
      background: rgba(255,255,255,.75);
      box-shadow: var(--shadow-sm);
    }
    #tkPremium .alert-success{ background: var(--green-bg); color: var(--green-tx); border-color: var(--green-br); }
    #tkPremium .alert-danger{ background: var(--red-bg); color: var(--red-tx); border-color: var(--red-br); }
    #tkPremium .alert ul{ margin:0; padding-left: 18px; font-weight: 850; }

    @media(max-width: 992px){
      #tkPremium .grid{ grid-template-columns: 1fr; }
      #tkPremium .heroR{ justify-content:flex-start; width:100%; }
      #tkPremium .row{ grid-template-columns: 1fr; gap: 4px; }
      #tkPremium .uploadSplit{ grid-template-columns: 1fr; }
      #tkPremium .activityScroll{ max-height: 420px; }
    }
  </style>

  <div class="bleed">
    <div class="wrap">

      @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
      @if(session('err')) <div class="alert alert-danger">{{ session('err') }}</div> @endif
      @if($errors->any())
        <div class="alert alert-danger">
          <strong>Revisa los siguientes errores:</strong>
          <ul>
            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif

      {{-- HERO (sin card contenedora grande) --}}
      <div class="card hero">
        <div class="heroTop">
          <div class="heroL" style="min-width:0;">
            <div class="topline">
              <span class="folio-badge">{{ $folio }}</span>

              <span class="pill {{ $statusColor($ticket->status) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>
                {{ $stLabel }}
              </span>

              <span class="pill sky">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20.59 13.41 11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82Z"/><path d="M7 7h.01"/></svg>
                {{ $arLabel }}
              </span>

              {{-- ✅ Prioridad marcada por color --}}
              <span class="pill {{ $prioTone }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l7 4v6c0 5-3 9-7 10C8 21 5 17 5 12V6l7-4z"/></svg>
                {{ $prLabel }}
              </span>

              <span class="pill {{ $slaClass ?: 'slate' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>
                {{ $slaLabel }}
              </span>

              <span class="pill brand">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 3v18h18"/><path d="M7 14l3-3 3 3 5-6"/></svg>
                Score: {{ $ticket->score ?? '—' }}
              </span>
            </div>

            <h1 class="h1">{{ $ticket->title }}</h1>

            <div class="metaLine">
              <span>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>
                De: {{ optional($ticket->creator)->name ?: '—' }}
              </span>
              <span>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                Para: {{ optional($ticket->assignee)->name ?: '—' }}
              </span>
              <span>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Vence: {{ $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : '—' }}
              </span>
            </div>
          </div>

          <div class="heroR">
            <a class="btn pastel-sky" href="{{ route('tickets.index') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg>
              Volver
            </a>

            @if(\Illuminate\Support\Facades\Route::has('tickets.work') && $canWork)
              <a class="btn pastel-indigo" href="{{ route('tickets.work',$ticket) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20V10"/><path d="m18 14-6-6-6 6"/></svg>
                Trabajar
              </a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('tickets.complete'))
              <form method="POST" action="{{ route('tickets.complete',$ticket) }}" style="margin:0;">
                @csrf
                <button class="btn pastel-mint" type="submit" onclick="return confirm('¿Marcar como completado?');">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
                  Completar
                </button>
              </form>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('tickets.cancel'))
              <form method="POST" action="{{ route('tickets.cancel',$ticket) }}" style="margin:0;">
                @csrf
                <button class="btn pastel-rose" type="submit" onclick="return confirm('¿Cancelar este ticket?');">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  Cancelar
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>

      <div class="grid">
        {{-- IZQUIERDA --}}
        <div>
          <div class="card" style="margin-bottom:18px;">
            <div class="sectionHead">
              <h3>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Detalles del requerimiento
              </h3>
            </div>
            <div class="sectionBody">
              <div class="infoGrid">
                <div class="row">
                  <div class="k">Descripción</div>
                  <div class="v">{{ $ticket->description ?: '—' }}</div>
                </div>
                <div class="row">
                  <div class="k">Impacto / Urgencia</div>
                  <div class="v">
                    Impacto: <strong>{{ $ticket->impact ?? '—' }}</strong> &nbsp;|&nbsp;
                    Urgencia: <strong>{{ $ticket->urgency ?? '—' }}</strong> &nbsp;|&nbsp;
                    Esfuerzo: <strong>{{ $ticket->effort ?? '—' }}</strong>
                  </div>
                </div>
                @if($ticket->completed_at)
                <div class="row">
                  <div class="k">Fecha Completado</div>
                  <div class="v">{{ $ticket->completed_at->format('Y-m-d H:i') }}</div>
                </div>
                @endif
                @if($ticket->cancelled_at)
                <div class="row">
                  <div class="k">Fecha Cancelado</div>
                  <div class="v">{{ $ticket->cancelled_at->format('Y-m-d H:i') }}</div>
                </div>
                @endif
              </div>
            </div>
          </div>

          <div class="card">
            <div class="sectionHead">
              <h3>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Conversación
              </h3>
            </div>
            <div class="sectionBody">
              <form method="POST" action="{{ route('tickets.comments.store',$ticket) }}">
                @csrf
                <textarea class="input" name="body" placeholder="Añadir una actualización o nota interna...">{{ old('body') }}</textarea>
                <div class="rightActions">
                  <button class="btn pastel-indigo" type="submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Comentar
                  </button>
                </div>
              </form>

              @if($ticket->comments->count() > 0)
                <div style="margin-top: 14px; border-top: 1px solid var(--border-light); padding-top: 12px;">
                  @foreach($ticket->comments as $c)
                    <div class="comment">
                      <div class="cTop">
                        <div class="cName">{{ optional($c->user)->name ?: '—' }}</div>
                        <div class="cTime">{{ optional($c->created_at)->format('Y-m-d H:i') }}</div>
                      </div>
                      <div class="cBody">{{ $c->body }}</div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>
        </div>

        {{-- DERECHA --}}
        <div>
          <div class="card" style="margin-bottom:18px;">
            <div class="sectionHead">
              <h3>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                Archivos
              </h3>
            </div>
            <div class="sectionBody">
              <form method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data">
                @csrf

                <div class="uploadBox">
                  <div class="uploadRow">
                    <input class="input" name="name" placeholder="Nombre (Ej. Captura de error)" value="{{ old('name') }}">
                  </div>

                  <div class="uploadSplit">
                    <select class="input" name="category">
                      <option value="adjunto">Adjunto</option>
                      <option value="evidencia">Evidencia</option>
                      <option value="link">Link</option>
                    </select>

                    <div class="filePick" title="Selecciona un archivo">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                      </svg>
                      <div class="hint">Haz clic para seleccionar archivo (imagen, pdf, video, etc.)</div>
                      <input type="file" name="file" accept="*/*">
                    </div>
                  </div>

                  <div class="uploadRow">
                    <input class="input" name="external_url" placeholder="O pega un enlace externo (https://...)" value="{{ old('external_url') }}">
                    <div class="help">Tip: si agregas link, el archivo es opcional.</div>
                  </div>

                  <div class="rightActions" style="margin-top: 0;">
                    <button class="btn pastel-sky" type="submit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20V10"/><path d="m18 14-6-6-6 6"/></svg>
                      Subir archivo
                    </button>
                  </div>
                </div>
              </form>

              @if($ticket->documents->count() > 0)
                <div style="margin-top: 14px;">
                  @foreach($ticket->documents as $d)
                    <div class="doc">
                      <div style="min-width:0; flex:1;">
                        <div class="name">
                          <span style="min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $d->name }}</span>
                          <span class="pill" style="font-size: 10px; padding: 3px 8px; background: rgba(241,245,249,.75); border-color: rgba(15,23,42,.10);">
                            v{{ $d->version ?? 1 }}
                          </span>
                        </div>
                        <div class="meta">
                          {{ ucfirst($d->category ?? 'adjunto') }} · {{ optional($d->created_at)->format('M d, H:i') }}
                        </div>
                        @if(!empty($d->external_url))
                          <div class="meta" style="margin-top: 4px;">
                            <a href="{{ $d->external_url }}" target="_blank" rel="noopener noreferrer" style="color: var(--p-indigo-tx); text-decoration: none; font-weight: 950;">
                              Abrir enlace ↗
                            </a>
                          </div>
                        @endif
                      </div>

                      <div style="display:flex; gap:8px; align-items:center;">
                        @if($d->path)
                          <a class="btn pastel-sky" style="padding: 8px 10px;" href="{{ route('tickets.documents.download',[$ticket,$d]) }}" title="Descargar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                          </a>
                        @endif
                        <form method="POST" action="{{ route('tickets.documents.destroy',[$ticket,$d]) }}" onsubmit="return confirm('¿Eliminar adjunto?');" style="margin:0;">
                          @csrf @method('DELETE')
                          <button class="btn pastel-rose" style="padding: 8px 10px;" type="submit" title="Eliminar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                          </button>
                        </form>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>

          <div class="card">
            <div class="sectionHead">
              <h3>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="12 8 12 12 14 14"/><circle cx="12" cy="12" r="10"/></svg>
                Actividad
              </h3>
            </div>
            <div class="sectionBody">
              <div class="activityScroll">
                @forelse($ticket->audits as $a)
                  @php
                    $label = $actionLabels[$a->action] ?? $a->action;
                    $diff  = (array) ($a->diff ?? []);
                  @endphp

                  <div class="audit">
                    <div class="a1">{{ $label }}</div>
                    <div class="a2">{{ $userName($a->user) }} · {{ optional($a->created_at)->format('M d, H:i') }}</div>

                    @if($a->action === 'ticket_updated' && !empty($diff['before']) && !empty($diff['after']))
                      @php
                        $before = (array) $diff['before'];
                        $after  = (array) $diff['after'];
                        $keys = ['status','priority','area','assignee_id','due_at'];
                        $changes = [];
                        foreach ($keys as $k){
                          if (($before[$k] ?? null) != ($after[$k] ?? null)) {
                            $changes[$k] = ['from'=>$before[$k]??'—', 'to'=>$after[$k]??'—'];
                          }
                        }
                      @endphp
                      @if(!empty($changes))
                        <div class="audit-box">
                          <div class="audit-box-header">Cambios clave</div>
                          <div class="audit-box-body">
                            @foreach($changes as $k => $c)
                              @php
                                $f = $fmt($c['from']); $t = $fmt($c['to']);
                                if ($k==='assignee_id'){ $f = $fmtAssignee($c['from']); $t = $fmtAssignee($c['to']); }
                              @endphp
                              <div style="display:flex; justify-content:space-between; gap:10px; margin-bottom:6px;">
                                <span style="color:var(--text-muted); font-weight:950;">{{ $prettyKey($k) }}</span>
                                <span style="text-align:right;">
                                  <span style="text-decoration:line-through; color:var(--text-light); margin-right:6px; font-weight:850;">{{ $f }}</span>
                                  <span style="color:var(--text-main); font-weight:950;">{{ $t }}</span>
                                </span>
                              </div>
                            @endforeach
                          </div>
                        </div>
                      @endif
                    @endif
                  </div>
                @empty
                  <div class="help">No hay registros de actividad.</div>
                @endforelse
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection