@extends('layouts.app')
@section('title','Tickets')

@section('content')
@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Schema;

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
      if(in_array($st, ['abierto','pendiente','nuevo','open','revision'], true)) $tmpOpen++;
      elseif(in_array($st, ['en_progreso','progreso','in_progress','working','bloqueado','pruebas'], true)) $tmpProg++;
      elseif(in_array($st, ['resuelto','cerrado','done','closed','resolved','completado','cancelado'], true)) $tmpDone++;
    }
    $countOpen = $countOpen ?? $tmpOpen;
    $countProgress = $countProgress ?? $tmpProg;
    $countDone = $countDone ?? $tmpDone;
  }

  // columnas (1 sola vez)
  $hasReviewStatus      = Schema::hasColumn('tickets','review_status');
  $hasReviewApprovedAt  = Schema::hasColumn('tickets','review_approved_at');
  $hasReviewDecision    = Schema::hasColumn('tickets','review_decision');

  $isApprovedTicket = function($t) use ($hasReviewStatus, $hasReviewApprovedAt, $hasReviewDecision){
    $ok = false;
    if($hasReviewStatus)     $ok = $ok || in_array((string)($t->review_status ?? ''), ['approved','aprobado'], true);
    if($hasReviewApprovedAt) $ok = $ok || !empty($t->review_approved_at);
    if($hasReviewDecision)   $ok = $ok || in_array((string)($t->review_decision ?? ''), ['approved','aprobado'], true);
    // fallback por auditoría si viene cargada
    if(!$ok && !empty($t->audits) && method_exists($t->audits,'contains')){
      $ok = $t->audits->contains(fn($a) => ($a->action ?? '') === 'ticket_review_approved');
    }
    return (bool)$ok;
  };

  $statusMeta = function($status) use ($statuses){
    $k = (string)$status;
    $label = $statuses[$k] ?? Str::headline(str_replace('_',' ',$k));
    $map = [
      'abierto'     => ['cls'=>'tkr-b-amber','label'=>$label ?: 'Abierto'],
      'pendiente'   => ['cls'=>'tkr-b-purple','label'=>$label ?: 'Pendiente'],
      'revision'    => ['cls'=>'tkr-b-amber','label'=>$label ?: 'En revisión'],
      'nuevo'       => ['cls'=>'tkr-b-amber','label'=>$label ?: 'Nuevo'],
      'open'        => ['cls'=>'tkr-b-amber','label'=>$label ?: 'Open'],
      'en_progreso' => ['cls'=>'tkr-b-blue','label'=>$label ?: 'En Progreso'],
      'progreso'    => ['cls'=>'tkr-b-blue','label'=>$label ?: 'En Progreso'],
      'in_progress' => ['cls'=>'tkr-b-blue','label'=>$label ?: 'In Progress'],
      'working'     => ['cls'=>'tkr-b-blue','label'=>$label ?: 'Working'],
      'bloqueado'   => ['cls'=>'tkr-b-yellow','label'=>$label ?: 'En espera'],
      'pruebas'     => ['cls'=>'tkr-b-amber','label'=>$label ?: 'En pruebas'],
      'resuelto'    => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Resuelto'],
      'cerrado'     => ['cls'=>'tkr-b-slate','label'=>$label ?: 'Cerrado'],
      'done'        => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Done'],
      'closed'      => ['cls'=>'tkr-b-slate','label'=>$label ?: 'Closed'],
      'resolved'    => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Resolved'],
      'completado'  => ['cls'=>'tkr-b-emerald','label'=>$label ?: 'Completado'],
      'cancelado'   => ['cls'=>'tkr-b-red','label'=>$label ?: 'Cancelado'],
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

  // =========================
  // Eisenhower + Score (solo para ordenar)
  // =========================
  $isDone = function($t){
    return in_array((string)($t->status ?? ''), ['completado','cancelado','cerrado','done','closed','resolved','resuelto'], true);
  };

  $getIU = function($t){
    $impact  = (int)($t->impact ?? 0);
    $urgency = (int)($t->urgency ?? 0);
    $effort  = (int)($t->effort ?? 0);

    if($impact <= 0 || $urgency <= 0){
      $pk = mb_strtolower((string)($t->priority ?? ''));

      $impactInf = 2; $urgInf = 2;

      if(in_array($pk, ['critica','crítica','urgente'], true)){
        $impactInf = 5; $urgInf = 5;
      } elseif(in_array($pk, ['alta'], true)){
        $impactInf = 4; $urgInf = 4;
      } elseif(in_array($pk, ['media'], true)){
        $impactInf = 4; $urgInf = 3;
      } elseif(in_array($pk, ['baja','mejora'], true)){
        $impactInf = 2; $urgInf = 2;
      }

      try{
        if(!empty($t->due_at)){
          $diffH = now()->diffInHours($t->due_at, false);
          if($diffH <= 0) $urgInf = max($urgInf, 5);
          elseif($diffH <= 24) $urgInf = max($urgInf, 4);
          elseif($diffH <= 72) $urgInf = max($urgInf, 3);
        }
      }catch(\Throwable $e){}

      if($impact <= 0)  $impact  = $impactInf;
      if($urgency <= 0) $urgency = $urgInf;
    }

    if($effort < 0) $effort = 0;
    if($effort > 5) $effort = 5;

    return [$impact,$urgency,$effort];
  };

  $getScore = function($t) use ($getIU){
    [$i,$u,$e] = $getIU($t);
    return (($i + $u) - $e);
  };

  $eisenKey = function($t) use ($getIU){
    [$i,$u] = $getIU($t);
    $important = ($i >= 4);
    $urgent    = ($u >= 4);

    if($important && $urgent) return 'do';
    if($important && !$urgent) return 'plan';
    if(!$important && $urgent) return 'delegate';
    return 'eliminate';
  };

  // =========================
  // Reordenar paginator (sin cambiar tu flujo)
  // =========================
  if($tickets instanceof \Illuminate\Pagination\AbstractPaginator){
    $col = $tickets->getCollection();

    $col = $col->sortBy(function($t) use ($isDone, $eisenKey, $getScore){
      $doneRank = $isDone($t) ? 1 : 0;

      $key = $eisenKey($t);
      $quadRank = match($key){
        'do' => 0,
        'plan' => 1,
        'delegate' => 2,
        default => 3,
      };

      $score = (int)$getScore($t);
      $scoreRank = 999 - $score;

      $dueRank = 999999999;
      try{
        if(!empty($t->due_at)){
          $dueRank = (int) now()->diffInMinutes($t->due_at, false);
        }
      }catch(\Throwable $e){}

      return [$doneRank, $quadRank, $scoreRank, $dueRank];
    });

    $tickets->setCollection($col->values());
  }
@endphp

<div id="tkrTickets">
  <style>
    /* =========================
      AISLADO: SOLO #tkrTickets
    ========================== */
    #tkrTickets{
      position: relative;
      isolation: isolate;
      margin: 0 !important;
      padding: 0 !important;

      --ink: #0f172a;
      --muted:#64748b;
      --line: rgba(15,23,42,.12);
      --brand:#4f52e8;

      --shadow-soft: 0 10px 30px rgba(2,6,23,.06);
      --shadow-hover: 0 22px 60px rgba(2,6,23,.14);
    }

    #tkrTickets .tkr-bleed{
      position: relative;
      min-height: 100vh;
      padding: 18px 0 44px;
      background: transparent !important;
      overflow-x:hidden;
      overflow: visible !important;
    }
    #tkrTickets .tkr-bleed::before{
      content:"";
      position: fixed;
      inset: 0;
      z-index: -1;
      pointer-events: none;
      background:
        radial-gradient(1200px 520px at 12% 18%, rgba(34,197,94,.08), transparent 55%),
        radial-gradient(1100px 520px at 60% 18%, rgba(56,189,248,.10), transparent 56%),
        radial-gradient(900px 520px at 92% 22%, rgba(79,70,229,.08), transparent 58%),
        linear-gradient(180deg, #f8fafc 0%, #f7fbff 52%, #f8fafc 100%);
    }

    #tkrTickets .tkr-wrap{ max-width: 1160px; margin: 0 auto; padding: 0 16px; }

    /* Header */
    #tkrTickets .tkr-head{ display:flex; justify-content:space-between; align-items:flex-start; gap: 16px; margin: 0 0 14px; }
    #tkrTickets .tkr-titleBox{ display:flex; gap: 12px; align-items:flex-start; min-width: 0; }
    #tkrTickets .tkr-icon{
      width:40px;height:40px;border-radius:12px;
      background: rgba(79,82,232,.10);
      border: 1px solid rgba(79,82,232,.20);
      color: var(--brand);
      display:grid;place-items:center; flex: 0 0 auto;
    }
    #tkrTickets .tkr-h1{ margin:0; font-size: 28px; font-weight: 650; letter-spacing:-.02em; color: var(--ink); line-height: 1.1; }
    #tkrTickets .tkr-sub{ margin:6px 0 0 0; color: var(--muted); font-weight: 450; font-size: 13px; }

    #tkrTickets .tkr-headActions{
      display:flex;
      gap: 10px;
      align-items:center;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    #tkrTickets .tkr-new{
      display:inline-flex; align-items:center; gap: 10px;
      background: var(--brand); color: #fff; text-decoration:none;
      padding: 12px 16px; border-radius: 14px; font-weight: 650;
      box-shadow: 0 14px 30px rgba(79,82,232,.18);
      transition: transform .06s ease, box-shadow .2s ease, filter .15s ease;
      white-space: nowrap; border: 0;
    }
    #tkrTickets .tkr-new:hover{ filter: brightness(.98); box-shadow: 0 18px 34px rgba(79,82,232,.20); }
    #tkrTickets .tkr-new:active{ transform: translateY(1px); }

    #tkrTickets .tkr-btnSoft{
      display:inline-flex; align-items:center; justify-content:center; gap: 10px;
      height: 46px;
      padding: 0 14px;
      border-radius: 14px;
      text-decoration:none;
      font-weight: 650;
      font-size: 13px;
      color: #1e293b;
      background: rgba(15,23,42,.06);
      border: 1px solid rgba(15,23,42,.12);
      box-shadow: 0 10px 26px rgba(2,6,23,.06);
      transition: transform .06s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
      white-space: nowrap;
    }
    #tkrTickets .tkr-btnSoft svg{ width: 18px; height: 18px; stroke-width: 1.8; opacity: .95; }
    #tkrTickets .tkr-btnSoft:hover{
      background: rgba(15,23,42,.08);
      border-color: rgba(15,23,42,.18);
      box-shadow: 0 16px 36px rgba(2,6,23,.10);
      transform: translateY(-1px);
    }
    #tkrTickets .tkr-btnSoft:active{ transform: translateY(0px); }

    /* Stats */
    #tkrTickets .tkr-stats{ display:grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; margin: 14px 0 16px; }
    #tkrTickets .tkr-stat{
      position:relative; overflow:hidden; background: #fff;
      border: 1px solid var(--line); border-radius: 18px;
      padding: 18px; box-shadow: var(--shadow-soft);
      transition: box-shadow .2s ease, transform .12s ease;
      min-height: 96px; isolation:isolate;
    }
    #tkrTickets .tkr-stat:hover{ box-shadow: 0 16px 40px rgba(2,6,23,.10); transform: translateY(-1px); }
    #tkrTickets .tkr-bubble{ position:absolute; right:-30px; top:-30px; width:110px; height:110px; border-radius:999px; opacity:.75; z-index:0; }
    #tkrTickets .tkr-stat .tkr-sIcon{
      width:42px;height:42px;border-radius:14px; display:grid;place-items:center;
      border: 1px solid rgba(15,23,42,.10); position:relative; z-index:1;
      margin-bottom: 12px; background: #fff;
    }
    #tkrTickets .tkr-stat .tkr-num{ font-size: 28px; font-weight: 650; color: var(--ink); line-height: 1; position:relative; z-index:1; }
    #tkrTickets .tkr-stat .tkr-lbl{ margin-top:6px; font-size: 12px; font-weight: 450; color: var(--muted); position:relative; z-index:1; }

    #tkrTickets .tkr-stat.s1 .tkr-bubble{ background: rgba(79,82,232,.12); }
    #tkrTickets .tkr-stat.s1 .tkr-sIcon{ background: rgba(79,82,232,.12); color: var(--brand); border-color: rgba(79,82,232,.24); }
    #tkrTickets .tkr-stat.s2 .tkr-bubble{ background: rgba(245,158,11,.12); }
    #tkrTickets .tkr-stat.s2 .tkr-sIcon{ background: rgba(245,158,11,.12); color:#b45309; border-color: rgba(245,158,11,.24); }
    #tkrTickets .tkr-stat.s3 .tkr-bubble{ background: rgba(59,130,246,.12); }
    #tkrTickets .tkr-stat.s3 .tkr-sIcon{ background: rgba(59,130,246,.12); color:#1d4ed8; border-color: rgba(59,130,246,.24); }
    #tkrTickets .tkr-stat.s4 .tkr-bubble{ background: rgba(16,185,129,.12); }
    #tkrTickets .tkr-stat.s4 .tkr-sIcon{ background: rgba(16,185,129,.12); color:#047857; border-color: rgba(16,185,129,.24); }

    /* Filters */
    #tkrTickets .tkr-filters{
      background: #fff;
      border: 1px solid rgba(15,23,42,.12);
      border-radius: 18px;
      box-shadow: var(--shadow-soft);
      padding: 12px;
      display:grid;
      grid-template-columns: 1fr 220px 170px;
      gap: 12px;
      align-items:center;
      margin: 0 0 16px;
    }
    #tkrTickets .tkr-field{
      display:flex; align-items:center; gap:10px;
      border: 1px solid rgba(15,23,42,.12);
      background: #fff;
      border-radius: 14px;
      padding: 10px 12px;
      height: 46px;
      min-width:0;
    }
    #tkrTickets .tkr-input, #tkrTickets .tkr-select{
      border:0; outline:0; background:transparent;
      width:100%; color: var(--ink);
      font-weight: 500;
      font-size: 14px;
      min-width:0;
    }
    #tkrTickets .tkr-select{ cursor:pointer; appearance: none; -webkit-appearance: none; -moz-appearance: none; }
    #tkrTickets .tkr-caret{ width:18px; height:18px; color: rgba(100,116,139,.85); flex: 0 0 auto; }
    #tkrTickets .tkr-ico{ width:18px; height:18px; color: rgba(100,116,139,.85); flex: 0 0 auto; }

    /* Pills */
    #tkrTickets .tkr-badge{
      display:inline-flex; align-items:center; padding: 4px 10px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 11px;

      white-space:nowrap;
      color:#fff;
      letter-spacing: 0.02em;
    }
    #tkrTickets .tkr-b-amber{ background:#f59e0b; border-color:#b45309; }
    #tkrTickets .tkr-b-blue{ background:#2563eb; border-color:#1d4ed8; }
    #tkrTickets .tkr-b-purple{ background:#7c3aed; border-color:#5b21b6; }
    #tkrTickets .tkr-b-emerald{ background:#10b981; border-color:#047857; }
    #tkrTickets .tkr-b-slate{ background:#334155; border-color:#0f172a; }
    #tkrTickets .tkr-b-yellow{ background:#ca8a04; border-color:#854d0e; }
    #tkrTickets .tkr-b-orange{ background:#ea580c; border-color:#9a3412; }
    #tkrTickets .tkr-b-red{ background:#ef4444; border-color:#991b1b; }

    /* Urge Badge Fuerte */
    #tkrTickets .tkr-b-urgent{
      background:#dc2626;
      border-color:#7f1d1d;
      color:#fff;
      text-transform: uppercase;
      font-weight: 800;
      padding: 4px 12px;
    }

    /* =========================
       NUEVO DISEÑO DE CARDS
    ========================== */
    #tkrTickets .tkr-list{ display:flex; flex-direction:column; gap: 16px; }

    #tkrTickets .tkr-card{
      background: #fff;
      border: 1px solid rgba(15,23,42,.08);
      border-radius: 16px;
      padding: 20px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,.05), 0 2px 4px -2px rgba(0,0,0,.04);
      transition: transform .14s ease, box-shadow .20s ease, border-color .20s ease;
      position: relative;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .tkr-card:hover{
      transform: scale(1.01);
      box-shadow: var(--shadow-hover);
      border-color: rgba(79,82,232,.18);
    }

    /* Estado Completado/Cancelado */
    #tkrTickets .tkr-card.is-done{
      background: #f8fafc;
      border-color: rgba(15,23,42,.06);
      transition: transform .14s ease, box-shadow .20s ease, border-color .20s ease;
      box-shadow: none;
    }
    #tkrTickets .tkr-card.is-done .tkr-ttl { color: var(--muted); }
    #tkrTickets .tkr-card.is-done:hover{
      transform: scale(1.01);
      box-shadow: var(--shadow-hover);
      border-color: rgba(79,82,232,.18);
    }

    /* Indicador Urgente */
    #tkrTickets .tkr-card.is-urgent{
      border-left: 4px solid #ef4444;
    }

    /* Layout Interno de la Card */
    #tkrTickets .tkr-card-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      flex-wrap: wrap;
    }
    #tkrTickets .tkr-ch-left {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }
    #tkrTickets .tkr-folio{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 12px;
      font-weight: 700;
      color: #475569;
      background: #f1f5f9;
      padding: 4px 8px;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
    }
    #tkrTickets .tkr-card-body {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    #tkrTickets .tkr-ttl{
      margin: 0;
      font-size: 17px;
      font-weight: 700;
      color: var(--ink);
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    #tkrTickets .tkr-desc{
      margin: 0;
      color: #64748b;
      font-weight: 400;
      font-size: 14px;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    /* Footer de la Card */
    #tkrTickets .tkr-card-foot {
      margin-top: 4px;
      padding-top: 14px;
      border-top: 1px solid #f1f5f9;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 16px;
    }
    #tkrTickets .tkr-meta{
      display:flex;
      gap:16px;
      flex-wrap:wrap;
      color: #64748b;
      font-weight: 500;
      font-size: 13px;
      align-items: center;
    }
    #tkrTickets .tkr-meta-item{
      display:inline-flex;
      gap: 6px;
      align-items:center;
    }
    #tkrTickets .tkr-avatar {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      background: rgba(79,82,232,.15);
      color: var(--brand);
      font-size: 10px;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
      text-transform: uppercase;
      border: 1px solid rgba(79,82,232,.25);
    }

    #tkrTickets .tkr-chev{
      flex: 0 0 auto;
      width: 42px; height: 42px;
      border-radius: 10px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      display:grid; place-items:center;
      color: #94a3b8;
      transition: all .2s ease;
      cursor: pointer;
      opacity: 0;
      pointer-events: none;
      transform: translateX(-2px);
    }
    #tkrTickets .tkr-card:hover .tkr-chev{
      opacity: 1;
      pointer-events: auto;
      transform: translateX(0);
    }
    #tkrTickets .tkr-chev:hover{
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
      width: min(420px, 26vw);
      background: #fff;
      box-shadow: -20px 0 70px rgba(2,6,23,.22);
      z-index: 9999;
      overflow: auto;
      border-left: 1px solid rgba(15,23,42,.10);

      transform: translateX(100%);
      opacity: .99;
      transition: transform .22s ease, opacity .22s ease;
      will-change: transform;
    }
    #tkrTickets .tkr-drawer.is-open{ transform: translateX(0); opacity: 1; }

    #tkrTickets .tkr-dh{
      padding: 16px 18px;
      border-bottom: 1px solid rgba(15,23,42,.10);
      display:flex; justify-content:space-between; align-items:center; gap: 10px;
      background:#fff; position: sticky; top: 0; z-index: 1;
    }
    #tkrTickets .tkr-chip{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 12px;
      font-weight: 750;
      color: rgba(71,85,105,.85);
      background:#f8fafc;
      border: 1px solid rgba(15,23,42,.10);
      padding: 6px 10px;
      border-radius: 999px;
    }
    #tkrTickets .tkr-close{
      width: 40px; height: 40px;
      border-radius: 999px;
      border: 1px solid rgba(15,23,42,.12);
      background: #fff;
      display:grid; place-items:center;
      cursor:pointer;
      transition: background .15s ease, box-shadow .15s ease;
    }
    #tkrTickets .tkr-close:hover{ background:#f8fafc; box-shadow: 0 10px 24px rgba(2,6,23,.10); }

    #tkrTickets .tkr-db{ padding: 18px; }
    #tkrTickets .tkr-dtitle{ margin: 12px 0 0; font-size: 22px; font-weight: 850; letter-spacing:-.01em; color: var(--ink); }
    #tkrTickets .tkr-dtext{
      margin-top: 12px;
      background:#f8fafc;
      border: 1px solid rgba(15,23,42,.10);
      border-radius: 16px;
      padding: 14px;
      color:#334155;
      font-weight: 600;
      line-height: 1.6;
      font-size: 13px;
      white-space: pre-wrap;
    }

    #tkrTickets .tkr-grid{ margin-top: 14px; display:grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    #tkrTickets .tkr-gi{
      background:#fff;
      border: 1px solid rgba(15,23,42,.10);
      border-radius: 16px;
      padding: 14px;
      display:flex; gap: 12px; align-items:flex-start;
    }
    #tkrTickets .tkr-ic{
      width: 40px; height: 40px;
      border-radius: 14px;
      display:grid; place-items:center;
      background: rgba(15,23,42,.06);
      border: 1px solid rgba(15,23,42,.12);
      color: #0f172a;
      flex: 0 0 auto;
    }
    #tkrTickets .tkr-k{
      font-size: 11px;
      font-weight: 800;
      color: rgba(71,85,105,.82);
      text-transform: uppercase;
      letter-spacing: .04em;
      margin-bottom: 2px;
    }
    #tkrTickets .tkr-v{
      font-size: 13px;
      font-weight: 750;
      color: var(--ink);
      word-break: break-word;
    }

    #tkrTickets .tkr-actions{
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid rgba(15,23,42,.10);
      display:flex; gap: 10px; flex-wrap:wrap;
    }
    #tkrTickets .tkr-linkBtn{
      height: 46px;
      border-radius: 14px;
      padding: 0 16px;
      border: 1px solid rgba(15,23,42,.12);
      background: #fff;
      color: var(--ink);
      font-weight: 800;
      text-decoration:none;
      display:inline-flex; align-items:center; justify-content:center; gap: 10px;
      transition: box-shadow .2s ease, transform .06s ease, border-color .15s ease, background .15s ease;
    }
    #tkrTickets .tkr-linkBtn:hover{ box-shadow: 0 14px 30px rgba(2,6,23,.10); border-color: rgba(15,23,42,.22); }
    #tkrTickets .tkr-linkBtn:active{ transform: translateY(1px); }

    #tkrTickets .tkr-linkBtn.work{
      background: #10b981;
      border-color: #047857;
      color:#fff;
    }
    #tkrTickets .tkr-linkBtn.work:hover{ background:#0ea371; border-color:#065f46; }

    #tkrTickets .tkr-linkBtn.report{
      background: #2563eb;
      border-color: #1d4ed8;
      color: #fff;
    }
    #tkrTickets .tkr-linkBtn.report:hover{ background:#1f5bdc; border-color:#1e40af; }

    @media (max-width: 1024px){
      #tkrTickets .tkr-stats{ grid-template-columns: repeat(2, minmax(0,1fr)); }
      #tkrTickets .tkr-drawer{ width: min(520px, 92vw); }
      #tkrTickets .tkr-filters{ grid-template-columns: 1fr; }
    }
    @media (max-width: 560px){
      #tkrTickets .tkr-stats{ grid-template-columns: 1fr; }
      #tkrTickets .tkr-head{ align-items:flex-start; }
      #tkrTickets .tkr-headActions{ width: 100%; justify-content: flex-start; }
      #tkrTickets .tkr-new{ width: 100%; justify-content:center; }
      #tkrTickets .tkr-btnSoft{ width: 100%; }
      #tkrTickets .tkr-card-head { flex-direction: column; align-items: stretch; }
      #tkrTickets .tkr-card-foot { flex-direction: column; align-items: flex-start; gap: 12px; }
      #tkrTickets .tkr-chev { align-self: flex-end; }
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

        <div class="tkr-headActions">
          <a class="tkr-btnSoft" href="{{ route('tickets.executive') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 3v18h18"/>
              <path d="M7 14l3-3 3 3 5-6"/>
            </svg>
            Panel Ejecutivo
          </a>

          <a class="tkr-new" href="{{ route('tickets.create') }}">
            <span style="display:grid;place-items:center">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14"/><path d="M5 12h14"/>
              </svg>
            </span>
            Nuevo Ticket
          </a>
        </div>
      </div>

      {{-- STATS --}}
      <div class="tkr-stats">
        <div class="tkr-stat s1">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>
            </svg>
          </div>
          <div class="tkr-num">{{ $countTotal }}</div>
          <div class="tkr-lbl">Total Tickets</div>
        </div>

        <div class="tkr-stat s2">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>
            </svg>
          </div>
          <div class="tkr-num">{{ $countOpen }}</div>
          <div class="tkr-lbl">Abiertos</div>
        </div>

        <div class="tkr-stat s3">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 14l4-4 4 4 4-8 4 2"/>
            </svg>
          </div>
          <div class="tkr-num">{{ $countProgress }}</div>
          <div class="tkr-lbl">En Progreso</div>
        </div>

        <div class="tkr-stat s4">
          <div class="tkr-bubble"></div>
          <div class="tkr-sIcon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 6 9 17l-5-5"/>
            </svg>
          </div>
          <div class="tkr-num">{{ $countDone }}</div>
          <div class="tkr-lbl">Resueltos</div>
        </div>
      </div>

      {{-- FILTERS (AUTO SUBMIT SIN BOTÓN OK) --}}
      <form id="tkrFilters" class="tkr-filters" method="GET" action="{{ route('tickets.index') }}">
        <div class="tkr-field">
          <svg class="tkr-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
          </svg>
          <input id="tkrQ" class="tkr-input" type="search" name="q" placeholder="Buscar tickets..." value="{{ request('q') }}" autocomplete="off">
        </div>

        <div class="tkr-field">
          <select id="tkrStatusSel" class="tkr-select" name="status">
            <option value="">Todos los estados</option>
            @foreach($statuses as $k => $label)
              <option value="{{ $k }}" @selected(request('status')===$k)>{{ $label }}</option>
            @endforeach
          </select>
          <svg class="tkr-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </div>

        <div class="tkr-field">
          <select id="tkrPrioritySel" class="tkr-select" name="priority">
            <option value="">Todas</option>
            @foreach($priorities as $k => $label)
              <option value="{{ $k }}" @selected(request('priority')===$k)>{{ $label }}</option>
            @endforeach
          </select>
          <svg class="tkr-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 9l6 6 6-6"/>
          </svg>
        </div>
      </form>

      {{-- LIST --}}
      <div class="tkr-list">
        @forelse($tickets as $t)
          @php
            $folio = $t->folio ?? ('TKT-'.str_pad((string)$t->id, 4, '0', STR_PAD_LEFT));
            $st = $statusMeta($t->status);
            $pr = $priorityMeta($t->priority);
            $assigneeName = optional($t->assignee)->name ?: 'Sin asignar';
            $assigneeInitial = mb_substr($assigneeName, 0, 1);
            $area = $areaLabel($t->area);
            $created = $t->created_at ? $t->created_at->format('d M Y, H:i') : '—';

            // NUEVO: creador + a quién se le encomendó
            $creatorName = optional($t->creator)->name
              ?: (property_exists($t,'createdBy') ? optional($t->createdBy)->name : null)
              ?: (property_exists($t,'user') ? optional($t->user)->name : null)
              ?: '—';

            $uid = auth()->check() ? (int)auth()->id() : 0;
            $createdBy = (int)($t->created_by ?? 0);
            $isAssignedToMe = (bool)($t->assignee_id && $uid && (int)$t->assignee_id === $uid);

            $statusKey = (string)($t->status ?? '');
            $isCompleted = ($statusKey === 'completado');
            $isCancelled = ($statusKey === 'cancelado');

            $isApproved = (bool)$isApprovedTicket($t);
            $isFinal = (bool)($isCompleted || $isCancelled || $isApproved);

            [$i,$u,$e] = $getIU($t);
            $prioKey = mb_strtolower((string)($t->priority ?? ''));
            $dueUrgent = false;
            try{
              if(!empty($t->due_at)){
                $diffH = now()->diffInHours($t->due_at, false);
                if($diffH <= 24) $dueUrgent = true;
              }
            }catch(\Throwable $e){}
            $isUrgentNow = (bool)($dueUrgent || $u >= 4 || in_array($prioKey, ['urgente','critica','crítica'], true));

            $hasReportRoute = \Illuminate\Support\Facades\Route::has('tickets.reportPdf');
            $canSeeReport = $uid && ($isAssignedToMe || ($createdBy && $createdBy === $uid));
            $canReport = (bool)($hasReportRoute && $canSeeReport && ($isCompleted || $isCancelled || $isApproved));

            $canWork = (bool)($isAssignedToMe && \Illuminate\Support\Facades\Route::has('tickets.work') && !$isFinal);

            $payload = [
              'id' => $t->id,
              'folio' => (string)$folio,
              'title' => (string)($t->title ?? '—'),
              'description' => (string)($t->description ?? ''),
              'status_label' => (string)$st['label'],
              'status_cls' => (string)$st['cls'],
              'priority_label' => (string)$pr['label'],
              'priority_cls' => (string)$pr['cls'],

              // A quién se le encomendó
              'assignee' => (string)$assigneeName,
              // Quién asigna / quién lo creó
              'assigned_by' => (string)$creatorName,

              'area' => (string)$area,
              'created' => (string)$created,
              'show_url' => route('tickets.show',$t),

              'is_done' => (bool)($isCompleted || $isCancelled),
              'is_approved' => (bool)$isApproved,

              'can_work' => (bool)$canWork,
              'work_url' => $canWork ? route('tickets.work',$t) : '',

              'can_report' => (bool)$canReport,
              'report_url' => $canReport ? route('tickets.reportPdf',$t) : '',
            ];
          @endphp

          <div class="tkr-card {{ ($isCompleted || $isCancelled) ? 'is-done' : '' }} {{ $isUrgentNow && !$isFinal ? 'is-urgent' : '' }}"
               data-open-ticket='@json($payload)'>

            {{-- Header de la Card --}}
            <div class="tkr-card-head">
              <div class="tkr-ch-left">
                <span class="tkr-folio">{{ $folio }}</span>
                <span class="tkr-badge {{ $st['cls'] }}">{{ $st['label'] }}</span>
                @if($isApproved)
                  <span class="tkr-badge tkr-b-emerald">Aprobado</span>
                @endif
                <span class="tkr-badge {{ $pr['cls'] }}">{{ $pr['label'] }}</span>
                @if($isUrgentNow && !$isFinal)
                  <span class="tkr-badge tkr-b-urgent">Urge</span>
                @endif
              </div>
              <div class="tkr-meta" style="color:#94a3b8; font-size: 12px;">
                 {{ $created }}
              </div>
            </div>

            {{-- Cuerpo de la Card --}}
            <div class="tkr-card-body">
              <h3 class="tkr-ttl">{{ $t->title }}</h3>
              <div class="tkr-desc">{{ $t->description ?: 'Sin descripción provista en este ticket.' }}</div>
            </div>

            {{-- Footer de la Card --}}
            <div class="tkr-card-foot">
              <div class="tkr-meta">
                <div class="tkr-meta-item" title="Encomendado a: {{ $assigneeName }}">
                  <div class="tkr-avatar">{{ $assigneeInitial }}</div>
                  <span>{{ $assigneeName }}</span>
                </div>

                <div class="tkr-meta-item" title="Área: {{ $area }}">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20.59 13.41 11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82Z"/><path d="M7 7h.01"/>
                  </svg>
                  <span>{{ $area }}</span>
                </div>
              </div>

              <button type="button" class="tkr-chev" title="Abrir vista previa">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M9 18l6-6-6-6"/>
                </svg>
              </button>
            </div>

          </div>

        @empty
          <div class="tkr-card" style="cursor:default; text-align: center; padding: 40px 20px;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 12px;">
               <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <h3 class="tkr-ttl">No se encontraron tickets</h3>
            <div class="tkr-desc">Ajusta tus filtros o crea un ticket nuevo para empezar.</div>
          </div>
        @endforelse
      </div>

      @if($tickets instanceof \Illuminate\Pagination\AbstractPaginator && $tickets->hasPages())
        <div style="margin-top:24px;">
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
            <div class="tkr-k">Encomendado a</div>
            <div id="tkrAssignee" class="tkr-v">—</div>
          </div>
        </div>

        {{-- CAMBIO: Email -> Quién asigna --}}
        <div class="tkr-gi">
          <div class="tkr-ic">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <div>
            <div class="tkr-k">Quién asigna</div>
            <div id="tkrAssignedBy" class="tkr-v">—</div>
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

        <a id="tkrReportBtn" class="tkr-linkBtn report" href="#" style="display:none;" target="_blank" rel="noopener">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <path d="M14 2v6h6"/>
            <path d="M8 13h8"/><path d="M8 17h8"/>
          </svg>
          Descargar reporte PDF
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

    // Debounce simple
    function debounce(fn, wait){
      let t = null;
      return function(){
        const ctx = this;
        const args = arguments;
        clearTimeout(t);
        t = setTimeout(function(){ fn.apply(ctx, args); }, wait);
      };
    }

    // Serializa y limpia params vacíos
    function buildQuery(form){
      const fd = new FormData(form);
      const params = new URLSearchParams();
      fd.forEach(function(v,k){
        const val = (v===null || v===undefined) ? '' : String(v).trim();
        if(val !== '') params.set(k, val);
      });
      return params.toString();
    }

    function submitFilters(form){
      if(!form) return;
      const base = form.getAttribute('action') || window.location.pathname;
      const qs = buildQuery(form);
      const url = qs ? (base + '?' + qs) : base;
      window.location.assign(url);
    }

    ready(function(){
      const root = document.getElementById('tkrTickets');
      if(!root) return;

      // ====== AUTO-FILTERS ======
      const form = document.getElementById('tkrFilters');
      const q = document.getElementById('tkrQ');
      const st = document.getElementById('tkrStatusSel');
      const pr = document.getElementById('tkrPrioritySel');

      const autoSubmit = debounce(function(){
        submitFilters(form);
      }, 350);

      if(q){
        q.addEventListener('input', autoSubmit);
        q.addEventListener('search', autoSubmit); // cuando borras con la X
      }
      if(st) st.addEventListener('change', function(){ submitFilters(form); });
      if(pr) pr.addEventListener('change', function(){ submitFilters(form); });

      // Evita enter si quieres (opcional). Igual submittea:
      if(form){
        form.addEventListener('submit', function(e){
          e.preventDefault();
          submitFilters(form);
        });
      }

      // ====== DRAWER ======
      const overlay = document.getElementById('tkrOverlay');
      const drawer  = document.getElementById('tkrDrawer');
      const btnClose = document.getElementById('tkrClose');

      const chip = document.getElementById('tkrChip');
      const elStatus = document.getElementById('tkrStatus');
      const elPriority = document.getElementById('tkrPriority');
      const elTitle = document.getElementById('tkrTitle');
      const elDesc = document.getElementById('tkrDesc');

      const elAssignee = document.getElementById('tkrAssignee');
      const elAssignedBy = document.getElementById('tkrAssignedBy'); // NUEVO
      const elArea = document.getElementById('tkrArea');
      const elCreated = document.getElementById('tkrCreated');

      const btnShow = document.getElementById('tkrShowBtn');
      const btnWork = document.getElementById('tkrWorkBtn');
      const btnReport = document.getElementById('tkrReportBtn');

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
        if(elAssignedBy) elAssignedBy.textContent = safeText(data.assigned_by); // NUEVO
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

        if(btnReport){
          if(data.can_report && data.report_url){
            btnReport.style.display = '';
            btnReport.href = data.report_url;
          } else {
            btnReport.style.display = 'none';
            btnReport.href = '#';
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

      root.addEventListener('click', function(e){
        const card = e.target.closest('[data-open-ticket]');
        if(!card) return;
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
      if(btnClose) btnClose.addEventListener('click', closeDrawer);

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