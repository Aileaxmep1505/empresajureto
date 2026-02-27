{{-- resources/views/tickets/show.blade.php --}}
@extends('layouts.app')
@section('title', ($ticket->folio ?? 'TKT').' | '.($ticket->title ?? 'Ticket'))

@section('content')
@php
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Facades\Schema;

  $statuses   = $statuses   ?? \App\Http\Controllers\Tickets\TicketController::STATUSES;
  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = $areas      ?? \App\Http\Controllers\Tickets\TicketController::AREAS;

  /* =========================
     ✅ ACTIVIDAD: TODO EN ESPAÑOL
     ========================= */
  $actionLabels = [
    'ticket_created'              => 'Se creó el ticket',
    'ticket_updated'              => 'Se actualizaron los datos del ticket',
    'comment_added'               => 'Se agregó un comentario',
    'doc_uploaded'                => 'Se adjuntó un archivo',
    'evidence_uploaded'           => 'Se subió una evidencia',
    'ticket_completed'            => 'Se marcó como completado',
    'ticket_cancelled'            => 'Se canceló el ticket',
    'ticket_submitted_for_review' => 'Se envió a revisión',
    'ticket_review_approved'      => 'Se aprobó la revisión y se cerró el ticket',
    'ticket_review_rejected'      => 'Se rechazó la revisión y se solicitó corrección',
    'ticket_force_reopened'       => 'Se reabrió el ticket para corrección',
    'report_generated'            => 'Se generó un reporte en PDF',
  ];

  $labelAction = function($action) use ($actionLabels){
    $k = (string)($action ?? '');
    if(isset($actionLabels[$k])) return $actionLabels[$k];

    $s = str_replace(['_','-'], ' ', $k);
    $s = trim($s);

    $rep = [
      'ticket' => 'ticket',
      'created' => 'creado',
      'updated' => 'actualizado',
      'deleted' => 'eliminado',
      'approved' => 'aprobado',
      'rejected' => 'rechazado',
      'review' => 'revisión',
      'submitted' => 'enviado',
      'for' => 'para',
      'reopen' => 'reapertura',
      'reopened' => 'reabierto',
      'force' => 'forzado',
      'generated' => 'generado',
      'comment' => 'comentario',
      'file' => 'archivo',
      'files' => 'archivos',
      'upload' => 'subida',
      'uploaded' => 'subido',
      'evidence' => 'evidencia',
      'completed' => 'completado',
      'cancelled' => 'cancelado',
      'cancel' => 'cancelación',
      'report' => 'reporte',
      'pdf' => 'PDF',
    ];

    $parts = preg_split('/\s+/', $s) ?: [];
    $parts = array_map(function($p) use ($rep){
      $p2 = strtolower($p);
      return $rep[$p2] ?? $p;
    }, $parts);

    $out = trim(implode(' ', $parts));
    if($out === '') return 'Actividad registrada';
    return mb_strtoupper(mb_substr($out, 0, 1)).mb_substr($out, 1);
  };

  $prettyKey = function(string $k){
    $map = [
      'title'=>'Título','description'=>'Descripción','priority'=>'Prioridad','area'=>'Área','status'=>'Estatus',
      'assignee'=>'Asignado a','assignee_id'=>'Asignado a','due_at'=>'Vencimiento',
      'impact'=>'Impacto','urgency'=>'Urgencia','effort'=>'Esfuerzo','score'=>'Score',
      'files'=>'Archivos','files_uploaded'=>'Archivos','cancel_reason'=>'Motivo de cancelación',
      'review_rating'=>'Calificación','review_comment'=>'Comentario','reason'=>'Motivo','reopen_reason'=>'Motivo de reapertura',
      'checklist_payload'=>'Checklist',
      'completion_detail'=>'Justificación de cierre','completed_note'=>'Justificación de cierre','completed_reason'=>'Justificación de cierre',
      'completion_summary'=>'Justificación de cierre','completion_justification'=>'Justificación de cierre',
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

  $fmtDateEs = function($dt){
    if(empty($dt)) return '—';
    try{
      return $dt->locale('es')->translatedFormat('d M Y, H:i');
    } catch(\Throwable $e){
      try{ return $dt->format('Y-m-d H:i'); } catch(\Throwable $e2){ return '—'; }
    }
  };

  $userName = fn($u) => $u ? ($u->name ?? '—') : '—';

  $userById = [];
  if (!empty($users ?? [])) foreach ($users as $u) $userById[(string)$u->id] = $u->name;
  $fmtAssignee = function($id) use ($userById){
    if (!$id) return '—';
    $k = (string)$id;
    return $userById[$k] ?? "Usuario #{$k}";
  };

  $statusColor = function($st){
    return match($st){
      'completado'  => 'green',
      'cancelado'   => 'red',
      'bloqueado'   => 'amber',
      'revision'    => 'amber',
      'pruebas'     => 'amber',
      'por_revisar' => 'amber',
      'reabierto'   => 'red',
      default       => 'slate',
    };
  };

  $sla = $ticket->sla_signal ?? 'neutral';
  $slaClass = $sla==='overdue' ? 'red' : ($sla==='due_soon' ? 'amber' : ($sla==='ok' ? 'green' : 'slate'));
  $slaLabel = $sla==='overdue' ? 'Vencido' : ($sla==='due_soon' ? 'Por vencer' : ($sla==='ok' ? 'En tiempo' : 'Sin fecha'));

  $uid = auth()->id();
  $isAssignee = $uid && ((int)$uid === (int)($ticket->assignee_id ?? 0));
  $isCreator  = $uid && ((int)$uid === (int)($ticket->created_by ?? 0));
  $canWork    = (bool)$isAssignee;

  $isCancelled = ((string)($ticket->status ?? '') === 'cancelado') || !empty($ticket->cancelled_at);
  $isCompleted = ((string)($ticket->status ?? '') === 'completado') || !empty($ticket->completed_at);
  $isFinal     = (bool)($isCancelled || $isCompleted);

  // ✅ Detectar APROBADO (robusto)
  $isApproved = false;
  if (Schema::hasColumn('tickets','review_status')) {
    $isApproved = $isApproved || in_array((string)($ticket->review_status ?? ''), ['approved','aprobado'], true);
  }
  if (Schema::hasColumn('tickets','review_approved_at')) {
    $isApproved = $isApproved || !empty($ticket->review_approved_at);
  }
  if (Schema::hasColumn('tickets','review_decision')) {
    $isApproved = $isApproved || in_array((string)($ticket->review_decision ?? ''), ['approved','aprobado'], true);
  }
  if (!empty($ticket->audits) && method_exists($ticket->audits, 'contains')) {
    $isApproved = $isApproved || $ticket->audits->contains(fn($a) => ($a->action ?? '') === 'ticket_review_approved');
  }

  // ✅ Solo-lectura SOLO cuando ya está APROBADO
  $readOnly = (bool)$isApproved;

  // ✅ Revisión (solo si NO está aprobado)
  $canReview = false;
  if(!$readOnly && $uid){
    if (Schema::hasColumn('tickets','assigned_by') && (int)($ticket->assigned_by ?? 0) === (int)$uid) $canReview = true;
    if (Schema::hasColumn('tickets','created_by')  && (int)($ticket->created_by  ?? 0) === (int)$uid) $canReview = true;
  }

  $folio   = $ticket->folio ?? ('TKT-'.str_pad((string)$ticket->id, 4, '0', STR_PAD_LEFT));
  $stLabel = $statuses[$ticket->status] ?? ($ticket->status ?: '—');
  $prLabel = $priorities[$ticket->priority] ?? ($ticket->priority ?: '—');
  $arLabel = $areas[$ticket->area] ?? ($ticket->area ?: 'Sin área');

  $prio = (string)($ticket->priority ?? '');
  $prioTone = match($prio){
    'alta','high','urgente','critica','crítico','critico' => 'red',
    'media','medium' => 'amber',
    'baja','low' => 'green',
    default => 'slate'
  };

  // ✅ WATERMARK: debe verse en APROBADO, COMPLETADO o CANCELADO
  $wmOn = (bool)($isApproved || $isCompleted || $isCancelled);

  $wmText = 'FINALIZADO';
  $wmSub  = 'Ticket finalizado';
  $wmTone = 'slate';

  if($isCancelled){
    $wmText = 'CANCELADO';
    $wmSub  = 'Ticket finalizado · Cancelado';
    $wmTone = 'red';
  } elseif($isApproved){
    $wmText = 'APROBADO';
    $wmSub  = 'Completado · Solo lectura';
    $wmTone = 'green';
  } elseif($isCompleted){
    $wmText = 'COMPLETADO';
    $wmSub  = 'Ticket finalizado · Completado';
    $wmTone = 'green';
  }

  /* =========================
     ✅ CHECKLIST (RELACIONAL - TU ESTRUCTURA REAL)
     Usa:
       - $ticket->checklists() -> hasMany TicketChecklist
       - $ticket->checklists->first()->items -> TicketChecklistItem
     ========================= */
  $ckTitle = 'Checklist del ticket';
  $ckItems = [];

  $ckModel = null;

  // 1) Si viene eager loaded
  try{
    if (isset($ticket->checklists) && $ticket->checklists instanceof \Illuminate\Support\Collection) {
      $ckModel = $ticket->checklists->sortByDesc('id')->first();
    }
  } catch(\Throwable $e){}

  // 2) Fallback: consultar el último checklist con items
  try{
    if(!$ckModel && method_exists($ticket,'checklists')){
      $ckModel = $ticket->checklists()->with('items')->orderByDesc('id')->first();
    }
  } catch(\Throwable $e){}

  // 3) Construir items
  try{
    if($ckModel){
      if(!empty($ckModel->title)) $ckTitle = (string)$ckModel->title;

      $items = null;
      if (isset($ckModel->items) && $ckModel->items instanceof \Illuminate\Support\Collection) {
        $items = $ckModel->items;
      } else {
        $items = $ckModel->items()->orderBy('sort_order')->orderBy('id')->get();
      }

      foreach($items as $it){
        $t = trim((string)($it->title ?? ''));
        if($t === '') continue;

        $ckItems[] = [
          'title' => $t,
          'detail' => !empty($it->detail) ? (string)$it->detail : null,
          'recommended' => (bool)($it->recommended ?? true),
          'done' => (bool)($it->done ?? false),
          'done_at' => $it->done_at ?? null,
          'done_by' => $it->done_by ?? null,
          'evidence_note' => $it->evidence_note ?? null,
        ];
      }
    }
  } catch(\Throwable $e){}

  $ckTotal = count($ckItems);
  $ckDone  = count(array_filter($ckItems, fn($x)=> !empty($x['done'])));
  $ckRec   = count(array_filter($ckItems, fn($x)=> !empty($x['recommended'])));

  /* =========================
     ✅ JUSTIFICACIÓN DE COMPLETADO (mostrar en SHOW)
     Busca:
     - columnas comunes
     - o diff en auditoría ticket_completed / ticket_review_approved
     ========================= */
  $completionJustification = null;

  $completionCols = [
    'completion_detail','completed_note','completed_reason','completion_summary','completion_justification',
    'done_note','done_reason','done_summary',
  ];

  foreach ($completionCols as $col) {
    try{
      if (Schema::hasColumn('tickets', $col) && !empty($ticket->{$col})) {
        $completionJustification = (string)$ticket->{$col};
        break;
      }
    } catch(\Throwable $e){}
  }

  if(empty($completionJustification) && !empty($ticket->audits)){
    foreach($ticket->audits as $a){
      $act = (string)($a->action ?? '');
      if(!in_array($act, ['ticket_completed','ticket_review_approved'], true)) continue;
      $diff = (array)($a->diff ?? []);

      foreach (['justification','completion_detail','completed_note','completed_reason','completion_summary','note','summary','detalle'] as $k) {
        if(!empty($diff[$k]) && is_string($diff[$k])) { $completionJustification = $diff[$k]; break; }
      }
      if(!empty($completionJustification)) break;

      if(!empty($diff['after']) && is_array($diff['after'])){
        foreach (['completion_detail','completed_note','completed_reason','completion_summary','completion_justification'] as $k) {
          if(!empty($diff['after'][$k])) { $completionJustification = (string)$diff['after'][$k]; break; }
        }
      }
      if(!empty($completionJustification)) break;
    }
  }

  $completionJustification = is_string($completionJustification) ? trim($completionJustification) : null;
  if($completionJustification === '') $completionJustification = null;

  /* =========================
     ✅ CHAT: ALINEACIÓN
     - Derecha: quien asignó (assigned_by) (fallback: created_by)
     - Izquierda: quien está haciendo (assignee_id)
     ========================= */
  $assignerId = null;
  if (Schema::hasColumn('tickets','assigned_by') && !empty($ticket->assigned_by)) $assignerId = (int)$ticket->assigned_by;
  if (!$assignerId && !empty($ticket->created_by)) $assignerId = (int)$ticket->created_by;

  $doerId = !empty($ticket->assignee_id) ? (int)$ticket->assignee_id : null;

  $commentSide = function($comment) use ($assignerId, $doerId){
    $cid = (int)($comment->user_id ?? (optional($comment->user)->id ?? 0));
    if($assignerId && $cid === (int)$assignerId) return 'right';
    if($doerId && $cid === (int)$doerId) return 'left';
    return 'neutral';
  };
@endphp

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div id="tkPremium">
  {{-- Si tienes CSS externo, se respeta. Este inline asegura que checklist/chat se vean bien aunque falle el CSS --}}
  <link rel="stylesheet" href="{{ asset('css/ticket-show.css') }}?v={{ time() }}">

  <style>
    /* =========================
       ✅ BASE (premium, minimal)
       ========================= */
    #tkPremium{
      --text-main:#0b1220;
      --text-muted:#64748b;
      --text-light:rgba(15,23,42,.45);
      --border-light:rgba(15,23,42,.10);
      --card-bg:rgba(255,255,255,.92);
      --bg1:#f7f9ff;
      --bg2:#fbfcff;

      --p-indigo:#4f46e5;
      --p-indigo-bg:rgba(238,242,255,.85);
      --p-indigo-tx:#3730a3;

      --p-sky:#0284c7;
      --p-sky-bg:rgba(224,242,254,.85);
      --p-sky-tx:#075985;

      --p-rose:#e11d48;
      --p-rose-bg:rgba(255,228,230,.92);
      --p-rose-tx:#9f1239;

      --p-amber:#f59e0b;
      --p-amber-bg:rgba(254,243,199,.92);
      --p-amber-tx:#92400e;

      --p-green:#10b981;
      --p-green-bg:rgba(209,250,229,.92);
      --p-green-tx:#065f46;

      --radius:18px;
      --shadow:0 16px 54px rgba(2,6,23,.10);
    }
    #tkPremium .bleed{
      background: radial-gradient(900px 420px at 20% -10%, rgba(99,102,241,.12), transparent 55%),
                  radial-gradient(760px 380px at 90% 5%, rgba(2,132,199,.10), transparent 55%),
                  linear-gradient(180deg, var(--bg1), var(--bg2));
      padding: 22px 0 38px;
    }
    #tkPremium .wrap{
      width: min(1180px, 92vw);
      margin: 0 auto;
    }
    #tkPremium .card{
      border:1px solid var(--border-light);
      background: var(--card-bg);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    #tkPremium .hero{ padding: 18px 18px 14px; }
    #tkPremium .heroTop{
      display:flex; gap:14px; justify-content:space-between; align-items:flex-start;
      flex-wrap:wrap;
    }
    #tkPremium .heroL{ flex:1 1 560px; min-width: 280px; }
    #tkPremium .heroR{ display:flex; gap:10px; flex:0 0 auto; align-items:center; flex-wrap:wrap; justify-content:flex-end; }

    #tkPremium .topline{ display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    #tkPremium .folio-badge{
      font-weight:950;
      color: var(--text-main);
      background: rgba(15,23,42,.06);
      border:1px solid rgba(15,23,42,.10);
      padding: 6px 10px;
      border-radius: 999px;
      letter-spacing:.02em;
    }
    #tkPremium .h1{
      margin: 12px 0 8px;
      font-size: 18px;
      font-weight: 950;
      color: var(--text-main);
      line-height: 1.2;
    }
    #tkPremium .metaLine{
      display:flex; gap:12px; flex-wrap:wrap;
      color: var(--text-muted);
      font-weight: 750;
      font-size: 12.8px;
    }
    #tkPremium .metaLine span{ display:inline-flex; gap:8px; align-items:center; }

    #tkPremium .ico16{ width:16px; height:16px; color: rgba(15,23,42,.65); }

    /* Buttons */
    #tkPremium .btn{
      display:inline-flex; gap:8px; align-items:center; justify-content:center;
      border-radius: 14px;
      padding: 10px 12px;
      border:1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.92);
      color: var(--text-main);
      font-weight: 950;
      text-decoration: none;
      cursor:pointer;
      transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
      user-select:none;
    }
    #tkPremium .btn:hover{ transform: translateY(-1px); box-shadow: 0 14px 34px rgba(2,6,23,.12); }
    #tkPremium .btn svg{ width:18px; height:18px; }

    #tkPremium .btn.pastel-indigo{ background: var(--p-indigo-bg); color: var(--p-indigo-tx); border-color: rgba(79,70,229,.20); }
    #tkPremium .btn.pastel-sky{ background: var(--p-sky-bg); color: var(--p-sky-tx); border-color: rgba(2,132,199,.18); }
    #tkPremium .btn.pastel-rose{ background: var(--p-rose-bg); color: var(--p-rose-tx); border-color: rgba(225,29,72,.18); }

    /* Pills */
    #tkPremium .pill{
      display:inline-flex; gap:8px; align-items:center;
      padding: 6px 10px;
      border-radius: 999px;
      border:1px solid rgba(15,23,42,.10);
      font-weight: 950;
      font-size: 12px;
      color: var(--text-main);
      background: rgba(255,255,255,.88);
      max-width:100%;
    }
    #tkPremium .pill svg{ width:16px; height:16px; }

    #tkPremium .pill.slate{ background: rgba(241,245,249,.85); }
    #tkPremium .pill.brand{ background: rgba(15,23,42,.06); }
    #tkPremium .pill.sky{ background: var(--p-sky-bg); color: var(--p-sky-tx); border-color: rgba(2,132,199,.18); }
    #tkPremium .pill.amber{ background: var(--p-amber-bg); color: var(--p-amber-tx); border-color: rgba(245,158,11,.20); }
    #tkPremium .pill.green{ background: var(--p-green-bg); color: var(--p-green-tx); border-color: rgba(16,185,129,.22); }
    #tkPremium .pill.red{ background: var(--p-rose-bg); color: var(--p-rose-tx); border-color: rgba(225,29,72,.20); }
    #tkPremium .pill.indigo{ background: var(--p-indigo-bg); color: var(--p-indigo-tx); border-color: rgba(79,70,229,.20); }

    /* Grid layout */
    #tkPremium .grid{ display:grid; grid-template-columns: 1.2fr .9fr; gap:18px; margin-top: 16px; }
    @media (max-width: 980px){ #tkPremium .grid{ grid-template-columns: 1fr; } }

    #tkPremium .sectionHead{
      padding: 12px 14px;
      border-bottom:1px solid var(--border-light);
      background: rgba(248,250,252,.78);
      display:flex; align-items:center; justify-content:space-between;
    }
    #tkPremium .sectionHead h3{
      margin:0; display:flex; gap:10px; align-items:center;
      font-size: 13.2px; font-weight: 950; color: var(--text-main);
    }
    #tkPremium .sectionBody{ padding: 14px; }

    #tkPremium .help{ color: var(--text-muted); font-weight: 750; font-size: 12.6px; line-height: 1.35; }

    #tkPremium .infoGrid{ display:flex; flex-direction:column; gap:10px; }
    #tkPremium .infoGrid .row{ display:grid; grid-template-columns: 180px 1fr; gap:12px; }
    @media (max-width: 640px){ #tkPremium .infoGrid .row{ grid-template-columns: 1fr; } }
    #tkPremium .infoGrid .k{ color: var(--text-muted); font-weight: 950; font-size: 12.5px; }
    #tkPremium .infoGrid .v{ color: var(--text-main); font-weight: 750; font-size: 12.8px; line-height:1.4; }

    /* Inputs */
    #tkPremium .input{
      width:100%;
      border-radius: 14px;
      border:1px solid rgba(15,23,42,.12);
      background: rgba(255,255,255,.92);
      padding: 10px 12px;
      font-weight: 750;
      color: var(--text-main);
      outline: none;
    }
    #tkPremium textarea.input{ min-height: 92px; resize: vertical; }

    #tkPremium .rightActions{ display:flex; justify-content:flex-end; margin-top:10px; }

    /* Checklist visual (premium) */
    #tkPremium .ckWrap{ display:flex; flex-direction:column; gap:10px; }
    #tkPremium .ckItem{
      border:1px solid var(--border-light);
      background: rgba(255,255,255,.92);
      border-radius: 16px;
      padding: 12px;
    }
    #tkPremium .ckTop{ display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
    #tkPremium .ckTitle{ font-weight: 950; color: var(--text-main); line-height:1.25; }
    #tkPremium .ckDetail{ margin-top:6px; color: var(--text-muted); font-weight: 750; white-space:pre-wrap; line-height:1.35; }

    /* Chat bubbles */
    #tkPremium .chat{ display:flex; flex-direction:column; gap:10px; margin-top: 12px; }
    #tkPremium .msgRow{ display:flex; }
    #tkPremium .msgRow.left{ justify-content:flex-start; }
    #tkPremium .msgRow.right{ justify-content:flex-end; }
    #tkPremium .msgRow.neutral{ justify-content:flex-start; }

    #tkPremium .bubble{
      width: min(520px, 100%);
      border-radius: 16px;
      border:1px solid rgba(15,23,42,.10);
      background: rgba(248,250,252,.86);
      padding: 10px 12px;
    }
    #tkPremium .msgRow.left .bubble{ background: rgba(224,242,254,.60); border-color: rgba(2,132,199,.16); }
    #tkPremium .msgRow.right .bubble{ background: rgba(238,242,255,.65); border-color: rgba(79,70,229,.18); }

    #tkPremium .bTop{ display:flex; justify-content:space-between; gap:10px; align-items:baseline; }
    #tkPremium .bName{ font-weight: 950; color: var(--text-main); font-size: 12.5px; }
    #tkPremium .bTime{ font-weight: 750; color: var(--text-muted); font-size: 12px; white-space:nowrap; }
    #tkPremium .bBody{ margin-top:6px; font-weight: 750; color: var(--text-main); font-size: 12.8px; line-height:1.45; white-space:pre-wrap; }

    /* Docs */
    #tkPremium .uploadBox{ display:flex; flex-direction:column; gap:10px; }
    #tkPremium .uploadSplit{ display:grid; grid-template-columns: 170px 1fr; gap:10px; }
    @media (max-width: 640px){ #tkPremium .uploadSplit{ grid-template-columns: 1fr; } }

    #tkPremium .filePick{
      border:1.5px dashed rgba(15,23,42,.18);
      background: rgba(248,250,252,.86);
      border-radius: 16px;
      padding: 12px;
      display:flex; gap:10px; align-items:flex-start;
      position:relative;
    }
    #tkPremium .filePick svg{ width:18px; height:18px; margin-top:2px; }
    #tkPremium .filePick .hint{ color: var(--text-muted); font-weight: 750; font-size: 12.6px; line-height:1.35; }
    #tkPremium .filePick input[type="file"]{
      position:absolute; inset:0; opacity:0; cursor:pointer;
    }

    #tkPremium .doc{
      border:1px solid var(--border-light);
      background: rgba(255,255,255,.92);
      border-radius: 16px;
      padding: 12px;
      display:flex; gap:10px; align-items:center; justify-content:space-between;
      margin-top:10px;
    }
    #tkPremium .doc .name{ display:flex; gap:8px; align-items:center; font-weight: 950; color: var(--text-main); }
    #tkPremium .doc .meta{ margin-top:4px; color: var(--text-muted); font-weight: 750; font-size: 12.4px; }

    /* Activity */
    #tkPremium .activityScroll{ display:flex; flex-direction:column; gap:10px; max-height: 520px; overflow:auto; padding-right:6px; }
    #tkPremium .audit{
      border:1px solid var(--border-light);
      background: rgba(255,255,255,.92);
      border-radius: 16px;
      padding: 12px;
    }
    #tkPremium .audit .a1{ font-weight: 950; color: var(--text-main); }
    #tkPremium .audit .a2{ margin-top:4px; font-weight: 750; color: var(--text-muted); font-size: 12.4px; }
    #tkPremium .audit-box{
      margin-top:10px;
      border:1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      background: rgba(248,250,252,.78);
      overflow:hidden;
    }
    #tkPremium .audit-box-header{
      padding: 9px 10px;
      border-bottom:1px solid rgba(15,23,42,.10);
      font-weight: 950;
      color: var(--text-main);
      font-size: 12.6px;
    }
    #tkPremium .audit-box-body{ padding: 10px; }

    /* SweetAlert minimal */
    #tkPremium .tk-toast{ border:1px solid rgba(15,23,42,.10); border-radius: 14px; }
  </style>

  {{-- ✅ WATERMARK GLOBAL --}}
  @if($wmOn)
    <style>
      #tkPremium .tk-watermark{
        position:fixed; inset:0;
        pointer-events:none;
        display:flex; align-items:center; justify-content:center;
        z-index:999; padding:18px;
      }
      #tkPremium .tk-watermark .wmBox{
        width:min(920px, 92vw);
        border:2px dashed rgba(15,23,42,.18);
        background:rgba(255,255,255,.58);
        border-radius:20px;
        padding:22px 18px;
        transform: rotate(-12deg);
        box-shadow: 0 22px 80px rgba(2,6,23,.10);
        text-align:center;
        backdrop-filter: blur(4px);
      }
      #tkPremium .tk-watermark .wmText{
        font-weight:950;
        letter-spacing:.18em;
        text-transform:uppercase;
        font-size: clamp(34px, 6vw, 74px);
        line-height:1.05;
        opacity:.22;
      }
      #tkPremium .tk-watermark .wmSub{
        margin-top:10px;
        font-weight:800;
        font-size: clamp(12px, 2.2vw, 18px);
        letter-spacing:.08em;
        opacity:.75;
      }
      #tkPremium .tk-watermark.is-red   .wmText{ color: rgba(239,68,68,1); }
      #tkPremium .tk-watermark.is-red   .wmSub { color: rgba(239,68,68,.95); }
      #tkPremium .tk-watermark.is-green .wmText{ color: rgba(16,185,129,1); }
      #tkPremium .tk-watermark.is-green .wmSub { color: rgba(16,185,129,.95); }
      #tkPremium .tk-watermark.is-slate .wmText{ color: rgba(100,116,139,1); }
      #tkPremium .tk-watermark.is-slate .wmSub { color: rgba(100,116,139,.95); }
    </style>

    <div class="tk-watermark is-{{ $wmTone }}" aria-hidden="true">
      <div class="wmBox">
        <div class="wmText">{{ $wmText }}</div>
        <div class="wmSub">{{ $wmSub }}</div>
      </div>
    </div>
  @endif

  {{-- ✅ Drawer Revisión (solo cuando NO está aprobado) --}}
  @if(!$readOnly)
  <style>
    #tkPremium .tkd-backdrop{
      position:fixed; inset:0;
      background:rgba(2,6,23,.52);
      backdrop-filter:blur(6px);
      opacity:0; pointer-events:none;
      transition:opacity .18s ease;
      z-index:9998;
    }
    #tkPremium .tkd{
      position:fixed; top:0; right:0;
      height:100vh; width:min(520px, 92vw);
      background:rgba(255,255,255,.98);
      border-left:1px solid rgba(15,23,42,.10);
      box-shadow:-18px 0 60px rgba(2,6,23,.22);
      transform:translateX(105%);
      transition:transform .22s ease;
      z-index:9999;
      display:flex; flex-direction:column;
      overflow:hidden;
      min-height:0;
    }
    #tkPremium .tkd.on{ transform:translateX(0); }
    #tkPremium .tkd-backdrop.on{ opacity:1; pointer-events:auto; }

    #tkPremium .tkd-head{
      padding:16px 18px;
      border-bottom:1px solid rgba(15,23,42,.10);
      display:flex; gap:12px; align-items:flex-start; justify-content:space-between;
      flex:0 0 auto;
      background:rgba(255,255,255,.98);
    }
    #tkPremium .tkd-title{ font-weight:950; color:#0f172a; font-size:14.5px; line-height:1.2; }
    #tkPremium .tkd-sub{
      margin-top:6px; font-weight:750; color:#64748b; font-size:12.5px;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:360px;
    }
    #tkPremium .tkd-close{
      width:36px; height:36px; border-radius:12px;
      border:1px solid rgba(15,23,42,.10);
      background:rgba(248,250,252,.92);
      display:flex; align-items:center; justify-content:center;
      cursor:pointer;
    }
    #tkPremium .tkd-close svg{ width:18px; height:18px; }

    #tkPremium .tkd-body{
      padding:14px 18px 18px;
      overflow:auto;
      display:flex; flex-direction:column; gap:12px;
      flex:1 1 auto;
      min-height:0;
      -webkit-overflow-scrolling: touch;
      overscroll-behavior: contain;
    }

    #tkPremium .tkd-choice{ display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    @media (max-width: 520px){ #tkPremium .tkd-choice{ grid-template-columns: 1fr; } }

    #tkPremium .tkd-opt{
      width:100%;
      border:1px solid rgba(15,23,42,.10);
      background:rgba(255,255,255,.92);
      border-radius:16px;
      padding:10px 12px;
      display:flex; gap:12px; align-items:flex-start;
      cursor:pointer;
      transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease;
      text-align:left;
      min-height:64px;
    }
    #tkPremium .tkd-opt:hover{
      transform: translateY(-1px);
      box-shadow: 0 14px 34px rgba(2,6,23,.10);
      border-color: rgba(79,70,229,.22);
    }
    #tkPremium .tkd-opt.isOn{
      border-color: rgba(79,70,229,.35);
      box-shadow: 0 16px 38px rgba(79,70,229,.16);
      background: rgba(238,242,255,.65);
    }
    #tkPremium .tkd-ico{
      width:36px; height:36px; border-radius:14px;
      border:1px solid rgba(15,23,42,.10);
      background:rgba(248,250,252,.95);
      display:flex; align-items:center; justify-content:center;
      flex:0 0 auto;
    }
    #tkPremium .tkd-ico svg{ width:18px; height:18px; }
    #tkPremium .tkd-opt h4{ margin:0; font-size:13px; font-weight:950; color:#0f172a; line-height:1.25; }

    #tkPremium .tkd-panel{
      border:1px solid rgba(15,23,42,.10);
      border-radius:16px;
      background:rgba(255,255,255,.92);
      overflow:hidden;
      display:none;
    }
    #tkPremium .tkd-panel.on{ display:block; }
    #tkPremium .tkd-panel .h{
      padding:10px 12px;
      border-bottom:1px solid rgba(15,23,42,.10);
      background:rgba(248,250,252,.85);
      display:flex; align-items:center; gap:8px;
      font-weight:950; color:#0f172a; font-size:12.8px;
    }
    #tkPremium .tkd-panel .h svg{ width:16px; height:16px; }
    #tkPremium .tkd-panel .b{ padding:12px; }

    #tkPremium .tkd-help{ font-weight:700; color:#64748b; font-size:12.5px; line-height:1.35; }

    #tkPremium .tkd-row{ display:flex; flex-direction:column; gap:8px; margin-top:10px; }
    #tkPremium .tkd-label{ font-weight:950; color:#0f172a; font-size:12.5px; }

    #tkPremium .tkd-actions{
      position: sticky;
      bottom: 0;
      display:flex;
      gap:10px;
      justify-content:flex-end;
      padding-top:12px;
      margin-top:12px;
      background: linear-gradient(to top, rgba(255,255,255,.98), rgba(255,255,255,.72));
      backdrop-filter: blur(3px);
      z-index: 2;
    }
    #tkPremium .tkd-actions .btn{ width:100%; justify-content:center; }

    #tkPremium .tkd-stars{ display:flex; gap:8px; align-items:center; padding-top:6px; }
    #tkPremium .tkd-stars input{ display:none; }
    #tkPremium .tkd-star{
      width:34px; height:34px; border-radius:13px;
      border:1px solid rgba(15,23,42,.12);
      background:rgba(248,250,252,.92);
      display:flex; align-items:center; justify-content:center;
      cursor:pointer;
      transition: transform .12s ease, box-shadow .12s ease;
      user-select:none;
    }
    #tkPremium .tkd-star svg{ width:16px; height:16px; }
    #tkPremium .tkd-star:hover{ transform: translateY(-1px); box-shadow:0 10px 22px rgba(2,6,23,.10); }
    #tkPremium .tkd-star.on{
      border-color: rgba(79,70,229,.35);
      box-shadow: 0 10px 24px rgba(79,70,229,.18);
      background: rgba(238,242,255,.95);
    }

    #tkPremium .tkd-drop{
      border:1.5px dashed rgba(15,23,42,.18);
      background:rgba(248,250,252,.86);
      border-radius:16px;
      padding:12px;
      display:flex; gap:10px; align-items:flex-start;
      cursor:pointer;
    }
    #tkPremium .tkd-drop svg{ width:18px; height:18px; margin-top:2px; }
    #tkPremium .tkd-chipwrap{ display:flex; flex-wrap:wrap; gap:8px; margin-top:10px; }
    #tkPremium .tkd-chip{
      display:inline-flex; gap:8px; align-items:center;
      border:1px solid rgba(15,23,42,.10);
      background:rgba(255,255,255,.92);
      border-radius:999px;
      padding:6px 10px;
      font-weight:850; color:#0f172a; font-size:12px;
      max-width:100%;
    }
    #tkPremium .tkd-chip span{ max-width:260px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    #tkPremium .tkd-chip button{
      border:0; width:20px; height:20px; border-radius:999px;
      background:rgba(239,68,68,.12); color:#ef4444;
      cursor:pointer; display:flex; align-items:center; justify-content:center;
    }

    #tkPremium .tkd-split{ display:grid; grid-template-columns:1fr; gap:10px; }

    #tkPremium .tkd-note{
      margin-top:10px;
      padding:10px 12px;
      border-radius:14px;
      border:1px solid rgba(15,23,42,.10);
      background:rgba(248,250,252,.75);
      font-weight:750;
      color:#475569;
      font-size:12.5px;
    }
  </style>

  <div class="tkd-backdrop" id="tkReviewBackdrop" aria-hidden="true"></div>

  <aside class="tkd" id="tkReviewDrawer" aria-hidden="true">
    <div class="tkd-head">
      <div style="min-width:0;">
        <div class="tkd-title">Revisión</div>
        <div class="tkd-sub">{{ $folio }} · {{ $ticket->title }}</div>
      </div>
      <button type="button" class="tkd-close" id="tkReviewClose" title="Cerrar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path d="M18 6 6 18"/><path d="M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="tkd-body">

      <div class="tkd-choice">
        <button type="button" class="tkd-opt" id="tkOptApprove">
          <div class="tkd-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M20 6 9 17l-5-5"/>
            </svg>
          </div>
          <div style="min-width:0;">
            <h4>Aprobar y cerrar</h4>
          </div>
        </button>

        <button type="button" class="tkd-opt" id="tkOptReopen">
          <div class="tkd-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M3 7h14"/><path d="M3 12h10"/><path d="M3 17h14"/>
              <path d="M19 7l2 2-2 2"/><path d="M19 17l2-2-2-2"/>
            </svg>
          </div>
          <div style="min-width:0;">
            <h4>Reabrir para corrección</h4>
          </div>
        </button>
      </div>

      {{-- ✅ APROBAR --}}
      <div class="tkd-panel" id="tkPanelApprove">
        <div class="h">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
          Aprobar (calificación)
        </div>
        <div class="b">
          @if(\Illuminate\Support\Facades\Route::has('tickets.reviewApprove'))
            <div class="tkd-help">Se guardará la calificación y el ticket quedará completado.</div>

            <form id="tkApproveForm" method="POST" action="{{ route('tickets.reviewApprove',$ticket) }}">
              @csrf

              <div class="tkd-row">
                <div class="tkd-label">Calificación (1 a 5)</div>
                <input type="hidden" name="review_rating" id="tkRating" value="5">
                <div class="tkd-stars" id="tkStars">
                  @for($i=1;$i<=5;$i++)
                    <label class="tkd-star {{ $i===5 ? 'on' : '' }}" data-v="{{ $i }}" title="{{ $i }}">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.77 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/>
                      </svg>
                    </label>
                  @endfor
                </div>
              </div>

              <div class="tkd-row">
                <div class="tkd-label">Comentario (opcional)</div>
                <textarea class="input" name="review_comment" rows="3" placeholder="Observaciones finales..."></textarea>
              </div>

              <div class="tkd-actions">
                <button type="button" class="btn pastel-sky" id="tkApproveAsk">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
                  Aprobar
                </button>
              </div>
            </form>
          @else
            <div class="tkd-note">Falta la ruta <code>tickets.reviewApprove</code>.</div>
          @endif
        </div>
      </div>

      {{-- ✅ REABRIR --}}
      <div class="tkd-panel" id="tkPanelReopen">
        <div class="h">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M3 7h14"/><path d="M3 12h10"/><path d="M3 17h14"/><path d="M19 7l2 2-2 2"/><path d="M19 17l2-2-2-2"/>
          </svg>
          Reabrir (observaciones + evidencias)
        </div>
        <div class="b">

          <form id="tkReopenForm"
                method="POST"
                action="{{ \Illuminate\Support\Facades\Route::has('tickets.forceReopen') ? route('tickets.forceReopen',$ticket) : 'javascript:void(0)' }}"
                enctype="multipart/form-data">
            @csrf

            <div class="tkd-row">
              <div class="tkd-label">Motivo (obligatorio)</div>
              <textarea class="input" name="reason" id="tkReopenReason" rows="4" placeholder="Explica qué falta o qué se debe corregir..."></textarea>
            </div>

            <div class="tkd-split">
              <div class="tkd-row">
                <div class="tkd-label">Reasignar a (opcional)</div>
                <select class="input" name="assignee_id">
                  <option value="">Mantener asignado actual</option>
                  @foreach(($users ?? []) as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="tkd-row">
                <div class="tkd-label">Regresar a estatus</div>
                <select class="input" name="back_to">
                  <option value="reabierto">Reabierto</option>
                  <option value="progreso">En progreso</option>
                  <option value="revision">En revisión</option>
                  <option value="pruebas">En pruebas</option>
                  <option value="pendiente">Pendiente</option>
                  <option value="bloqueado">En espera</option>
                </select>
              </div>
            </div>

            <div class="tkd-row">
              <div class="tkd-label">Evidencias (opcional)</div>

              <label class="tkd-drop" for="tkReopenFiles">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                  <polyline points="7 10 12 15 17 10"/>
                  <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <div>
                  <div style="font-weight:950; color:#0f172a;">Adjuntar fotos / PDF / video</div>
                  <div class="tkd-help">Se guardarán como evidencia de la reapertura.</div>
                </div>
              </label>

              <input id="tkReopenFiles" type="file" name="files[]"
                     accept="image/*,application/pdf,video/*,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
                     multiple style="display:none;">

              <div class="tkd-chipwrap" id="tkReopenChips" style="display:none;"></div>
            </div>

            <div class="tkd-actions">
              <button type="button" class="btn pastel-rose" id="tkReopenAsk">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 12H7"/><path d="M11 8l-4 4 4 4"/><path d="M3 21V3"/></svg>
                Reabrir
              </button>
            </div>
          </form>

          @if(!\Illuminate\Support\Facades\Route::has('tickets.forceReopen'))
            <div class="tkd-note">Falta la ruta <code>tickets.forceReopen</code> en <code>web.php</code>.</div>
          @endif
        </div>
      </div>

    </div>
  </aside>
  @endif

  <div class="bleed">
    <div class="wrap">

      {{-- HERO --}}
      <div class="card hero">
        <div class="heroTop">
          <div class="heroL" style="min-width:0;">
            <div class="topline">
              <span class="folio-badge">{{ $folio }}</span>

              <span class="pill {{ $statusColor($ticket->status) }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>
                {{ $stLabel }}
              </span>

              @if($isApproved)
                <span class="pill green">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
                  Aprobado
                </span>
              @endif

              <span class="pill sky">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20.59 13.41 11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82Z"/><path d="M7 7h.01"/></svg>
                {{ $arLabel }}
              </span>

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

              {{-- ✅ Badge checklist --}}
              <span class="pill slate">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/>
                  <path d="M4 6l1 1 2-2"/><path d="M4 12l1 1 2-2"/><path d="M4 18l1 1 2-2"/>
                </svg>
                Checklist: {{ $ckDone }}/{{ $ckTotal }}
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
                Vence: {{ $ticket->due_at ? $fmtDateEs($ticket->due_at) : '—' }}
              </span>
            </div>
          </div>

          <div class="heroR">
            <a class="btn pastel-sky" href="{{ route('tickets.index') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 18l-6-6 6-6"/></svg>
              Volver
            </a>

            {{-- ✅ Si ya está aprobado: NO mostrar revisar ni trabajar --}}
            @if(!$readOnly)

              @if($canReview)
                <button type="button" class="btn pastel-indigo" id="tkOpenReview" title="Revisar / reabrir">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.77 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/>
                  </svg>
                  Revisar
                </button>
              @endif

              @if(!$isFinal && \Illuminate\Support\Facades\Route::has('tickets.work') && $canWork)
                <a class="btn pastel-indigo" href="{{ route('tickets.work',$ticket) }}">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20V10"/><path d="m18 14-6-6-6 6"/></svg>
                  Trabajar
                </a>
              @endif

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
                    Impacto: {{ $ticket->impact ?? '—' }} &nbsp;|&nbsp;
                    Urgencia: {{ $ticket->urgency ?? '—' }} &nbsp;|&nbsp;
                    Esfuerzo: {{ $ticket->effort ?? '—' }}
                  </div>
                </div>

                @if(!empty($ticket->reopen_reason))
                <div class="row">
                  <div class="k">Motivo de reapertura</div>
                  <div class="v" style="white-space:pre-wrap;">{{ $ticket->reopen_reason }}</div>
                </div>
                @endif
              </div>
            </div>
          </div>

          {{-- ✅ JUSTIFICACIÓN DE COMPLETADO (cuando esté completado) --}}
          @if($isCompleted)
            <div class="card" style="margin-bottom:18px;">
              <div class="sectionHead">
                <h3>
                  <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M20 6 9 17l-5-5"/>
                  </svg>
                  Justificación de cierre
                </h3>
              </div>
              <div class="sectionBody">
                @if($completionJustification)
                  <div style="white-space:pre-wrap; font-weight:750; color:var(--text-main); line-height:1.45;">
                    {{ $completionJustification }}
                  </div>
                @else
                  <div class="help">No se encontró una justificación guardada para este cierre.</div>
                @endif
              </div>
            </div>
          @endif

          {{-- ✅ CHECKLIST (RELACIONAL) --}}
          <div class="card" style="margin-bottom:18px;">
            <div class="sectionHead">
              <h3>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path d="M9 6h11"/><path d="M9 12h11"/><path d="M9 18h11"/>
                  <path d="M4 6l1 1 2-2"/><path d="M4 12l1 1 2-2"/><path d="M4 18l1 1 2-2"/>
                </svg>
                {{ $ckTitle }}
              </h3>
            </div>
            <div class="sectionBody">
              @if($ckTotal === 0)
                <div class="help">Este ticket no tiene checklist guardado.</div>
              @else
                <div class="help" style="margin-bottom:10px;">
                  Progreso: {{ $ckDone }} de {{ $ckTotal }} · Recomendados: {{ $ckRec }}
                </div>

                <div class="ckWrap">
                  @foreach($ckItems as $it)
                    @php
                      $done = !empty($it['done']);
                      $rec  = !empty($it['recommended']);
                    @endphp

                    <div class="ckItem">
                      <div class="ckTop">
                        <div style="min-width:0;">
                          <div class="ckTitle">{{ $it['title'] }}</div>

                          @if(!empty($it['detail']))
                            <div class="ckDetail">{{ $it['detail'] }}</div>
                          @endif

                          @if(!empty($it['evidence_note']))
                            <div class="ckDetail" style="margin-top:8px;">
                              <span style="font-weight:950; color:var(--text-main);">Nota:</span>
                              {{ $it['evidence_note'] }}
                            </div>
                          @endif
                        </div>

                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                          @if($rec)
                            <span class="pill amber" style="font-size:11px;">Recomendado</span>
                          @endif
                          @if($done)
                            <span class="pill green" style="font-size:11px;">Completado</span>
                          @else
                            <span class="pill slate" style="font-size:11px;">Pendiente</span>
                          @endif
                        </div>
                      </div>

                      @if(!empty($it['done_at']))
                        <div class="help" style="margin-top:8px;">
                          Marcado: {{ $fmtDateEs($it['done_at']) }}
                        </div>
                      @endif
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          </div>

          {{-- ✅ CONVERSACIÓN (alineación izquierda/derecha) --}}
          <div class="card">
            <div class="sectionHead">
              <h3>
                <svg class="ico16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Conversación
              </h3>
            </div>
            <div class="sectionBody">

              {{-- ✅ Solo lectura: ocultar form de comentar --}}
              @if(!$readOnly)
                <form id="tkCommentForm" method="POST" action="{{ route('tickets.comments.store',$ticket) }}">
                  @csrf
                  <textarea class="input" name="body" placeholder="Añadir una actualización o nota interna...">{{ old('body') }}</textarea>
                  <div class="rightActions">
                    <button class="btn pastel-indigo" type="submit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                      Comentar
                    </button>
                  </div>
                </form>
              @else
                <div class="help" style="margin-bottom:10px;">
                  Este ticket está aprobado. La conversación es solo de consulta.
                </div>
              @endif

              <div class="chat">
                @forelse($ticket->comments as $c)
                  @php $side = $commentSide($c); @endphp
                  <div class="msgRow {{ $side }}">
                    <div class="bubble">
                      <div class="bTop">
                        <div class="bName">{{ optional($c->user)->name ?: '—' }}</div>
                        <div class="bTime">{{ $c->created_at ? $fmtDateEs($c->created_at) : '—' }}</div>
                      </div>
                      <div class="bBody">{{ $c->body }}</div>
                    </div>
                  </div>
                @empty
                  <div class="help">No hay comentarios todavía.</div>
                @endforelse
              </div>

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

              {{-- ✅ Solo lectura: ocultar subir archivos --}}
              @if(!$readOnly)
                <form id="tkUploadForm" method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data">
                  @csrf
                  <div class="uploadBox">
                    <div class="uploadRow">
                      <input class="input" name="name" placeholder="Nombre (Ej. Captura del proceso)" value="{{ old('name') }}">
                    </div>

                    <div class="uploadSplit">
                      <select class="input" name="category">
                        <option value="adjunto">Adjunto</option>
                        <option value="evidencia">Evidencia</option>
                        <option value="link">Enlace</option>
                      </select>

                      <div class="filePick" title="Selecciona un archivo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                          <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                          <polyline points="7 10 12 15 17 10"/>
                          <line x1="12" y1="15" x2="12" y2="3"/>
                        </svg>
                        <div class="hint">Haz clic para seleccionar archivo (imagen, PDF, video, etc.)</div>
                        <input type="file" name="file" accept="*/*">
                      </div>
                    </div>

                    <div class="uploadRow">
                      <input class="input" name="external_url" placeholder="O pega un enlace externo (https://...)" value="{{ old('external_url') }}">
                      <div class="help">Tip: si agregas enlace, el archivo es opcional.</div>
                    </div>

                    <div class="rightActions" style="margin-top: 0;">
                      <button class="btn pastel-sky" type="submit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20V10"/><path d="m18 14-6-6-6 6"/></svg>
                        Subir archivo
                      </button>
                    </div>
                  </div>
                </form>
              @else
                <div class="help" style="margin-bottom:10px;">
                  Este ticket está aprobado. Los archivos son solo de consulta.
                </div>
              @endif

              @if($ticket->documents->count() > 0)
                <div style="margin-top: 14px;">
                  @foreach($ticket->documents as $d)
                    @php
                      $previewUrl = '';
                      if (!empty($d->external_url)) $previewUrl = $d->external_url;
                      elseif (!empty($d->path)) $previewUrl = Storage::url($d->path);

                      $ext = '';
                      if (!empty($d->path)) $ext = strtolower(pathinfo($d->path, PATHINFO_EXTENSION) ?: '');
                      elseif (!empty($d->external_url)) {
                        $u = parse_url($d->external_url); $p = $u['path'] ?? '';
                        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION) ?: '');
                      }

                      $cat = (string)($d->category ?? 'adjunto');
                      $catLabel = match($cat){
                        'evidencia' => 'Evidencia',
                        'link' => 'Enlace',
                        default => 'Adjunto',
                      };
                    @endphp

                    <div class="doc">
                      <div style="min-width:0; flex:1;">
                        <div class="name">
                          <span style="min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $d->name }}</span>
                          <span class="pill slate" style="font-size: 10px; padding: 3px 8px;">
                            v{{ $d->version ?? 1 }}
                          </span>
                        </div>
                        <div class="meta">
                          {{ $catLabel }} · {{ $d->created_at ? $fmtDateEs($d->created_at) : '—' }}
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
                        @if(!empty($previewUrl))
                          <button type="button"
                                  class="btn pastel-indigo tkPreview"
                                  style="padding: 8px 10px;"
                                  title="Vista previa"
                                  data-url="{{ $previewUrl }}"
                                  data-name="{{ e($d->name) }}"
                                  data-ext="{{ $ext }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                              <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                              <circle cx="12" cy="12" r="3"/>
                            </svg>
                          </button>
                        @endif

                        @if($d->path)
                          <a class="btn pastel-sky" style="padding: 8px 10px;" href="{{ route('tickets.documents.download',[$ticket,$d]) }}" title="Descargar">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                          </a>
                        @endif

                        {{-- ✅ Solo lectura: ocultar eliminar --}}
                        @if(!$readOnly)
                          <form class="tkDocDel" method="POST" action="{{ route('tickets.documents.destroy',[$ticket,$d]) }}" style="margin:0;">
                            @csrf @method('DELETE')
                            <button class="btn pastel-rose" style="padding: 8px 10px;" type="submit" title="Eliminar">
                              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </button>
                          </form>
                        @endif
                      </div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="help">No hay archivos adjuntos.</div>
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
                    $label = $labelAction($a->action ?? '');
                    $diff  = (array) ($a->diff ?? []);
                  @endphp
                  <div class="audit">
                    <div class="a1">{{ $label }}</div>
                    <div class="a2">{{ $userName($a->user) }} · {{ $a->created_at ? $fmtDateEs($a->created_at) : '—' }}</div>

                    @if(in_array($a->action, ['ticket_review_rejected','ticket_force_reopened'], true) && !empty($diff['reason']))
                      <div class="audit-box">
                        <div class="audit-box-header">Motivo de reapertura</div>
                        <div class="audit-box-body" style="white-space:pre-wrap; font-weight:750; color:var(--text-main);">
                          {{ $diff['reason'] }}
                        </div>
                      </div>
                    @endif

                    {{-- ✅ Mostrar justificación si se completó --}}
                    @if($a->action === 'ticket_completed')
                      @php
                        $just = null;
                        foreach (['justification','completion_detail','completed_note','completed_reason','completion_summary','note','summary','detalle'] as $k) {
                          if(!empty($diff[$k]) && is_string($diff[$k])) { $just = $diff[$k]; break; }
                        }
                        if(!$just && !empty($diff['after']) && is_array($diff['after'])){
                          foreach (['completion_detail','completed_note','completed_reason','completion_summary','completion_justification'] as $k) {
                            if(!empty($diff['after'][$k])) { $just = (string)$diff['after'][$k]; break; }
                          }
                        }
                        $just = is_string($just) ? trim($just) : null;
                        if($just === '') $just = null;
                      @endphp
                      @if($just)
                        <div class="audit-box">
                          <div class="audit-box-header">Justificación de completado</div>
                          <div class="audit-box-body" style="white-space:pre-wrap; font-weight:750; color:var(--text-main);">
                            {{ $just }}
                          </div>
                        </div>
                      @endif
                    @endif

                    @if($a->action === 'ticket_review_approved')
                      <div class="audit-box">
                        <div class="audit-box-header">Cierre por revisión</div>
                        <div class="audit-box-body" style="display:flex; gap:10px; flex-wrap:wrap;">
                          <span class="pill brand">Calificación: {{ (int)($diff['review_rating'] ?? 0) ?: '—' }} / 5</span>
                          @if(!empty($diff['review_comment']))
                            <span class="pill sky" style="white-space:nowrap; max-width:100%; overflow:hidden; text-overflow:ellipsis;">
                              {{ $diff['review_comment'] }}
                            </span>
                          @endif
                        </div>
                      </div>
                    @endif

                    @if($a->action === 'ticket_updated' && !empty($diff['before']) && !empty($diff['after']))
                      @php
                        $before = (array) $diff['before'];
                        $after  = (array) $diff['after'];
                        $keys = ['status','priority','area','assignee_id','due_at','impact','urgency','effort','score'];
                        $changes = [];
                        foreach ($keys as $k){
                          if (($before[$k] ?? null) != ($after[$k] ?? null)) $changes[$k] = ['from'=>$before[$k]??'—', 'to'=>$after[$k]??'—'];
                        }
                      @endphp
                      @if(!empty($changes))
                        <div class="audit-box">
                          <div class="audit-box-header">Cambios registrados</div>
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

  <script>
    (function(){
      const TK_SWAL = {
        popup: 'tk-swal-popup',
        title: 'tk-swal-title',
        htmlContainer: 'tk-swal-html',
        confirmButton: 'tk-swal-btn tk-swal-confirm',
        cancelButton: 'tk-swal-btn tk-swal-cancel',
        denyButton: 'tk-swal-btn tk-swal-deny',
        actions: 'tk-swal-actions',
        input: 'tk-swal-input',
        validationMessage: 'tk-swal-validation',
        closeButton: 'tk-swal-close',
      };

      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2600,
        timerProgressBar: true,
        customClass: { popup: 'tk-toast' },
        didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer);
          toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
      });

      function toast(type, title){ Toast.fire({ icon: type, title: title }); }
      function swalBase(extra){
        return Swal.fire(Object.assign({
          customClass: TK_SWAL,
          buttonsStyling: false,
          showCloseButton: true,
          focusConfirm: false,
        }, extra || {}));
      }

      function showErrorsModal(html){
        swalBase({ icon:'error', title:'Revisa los errores', html: html, confirmButtonText:'Entendido' });
      }

      function safeExt(ext){ return (ext || '').toLowerCase().trim(); }
      function buildPreviewHtml(url, ext){
        const e = safeExt(ext);
        const u = url || '';
        const wrapStyle = 'width:100%; height:min(70vh, 560px); border-radius:14px; overflow:hidden; border:1px solid rgba(15,23,42,.12); background:rgba(255,255,255,.92);';
        if(['png','jpg','jpeg','webp','gif','bmp','svg'].includes(e)){
          return `<div style="${wrapStyle}; display:flex; align-items:center; justify-content:center; padding:10px;">
            <img src="${u}" alt="Vista previa" style="max-width:100%; max-height:100%; border-radius:12px;"/>
          </div>`;
        }
        if(['mp4','webm','ogg','mov','m4v'].includes(e)){
          return `<div style="${wrapStyle}; display:flex; align-items:center; justify-content:center; padding:10px;">
            <video src="${u}" controls style="width:100%; height:100%; border-radius:12px;"></video>
          </div>`;
        }
        if(e === 'pdf'){
          const pdfUrl = u.includes('#') ? u : (u + '#toolbar=0&navpanes=0&scrollbar=0');
          return `<div style="${wrapStyle}"><iframe src="${pdfUrl}" style="width:100%; height:100%; border:0;"></iframe></div>`;
        }
        return `<div style="${wrapStyle}"><iframe src="${u}" style="width:100%; height:100%; border:0;"></iframe></div>`;
      }

      document.addEventListener('DOMContentLoaded', function(){
        @if(session('ok')) toast('success', @json(session('ok'))); @endif
        @if(session('err')) toast('error', @json(session('err'))); @endif

        @if($errors->any())
          (function(){
            let html = '<ul style="text-align:left; margin:0; padding-left:18px;">';
            @foreach($errors->all() as $e) html += '<li>{{ addslashes($e) }}</li>'; @endforeach
            html += '</ul>';
            showErrorsModal(html);
          })();
        @endif

        const readOnly = @json((bool)$readOnly);

        // Drawer open/close (solo si NO es readOnly)
        if(!readOnly){
          const canReview = @json((bool)$canReview);
          const drawer   = document.getElementById('tkReviewDrawer');
          const backdrop = document.getElementById('tkReviewBackdrop');
          const openBtn  = document.getElementById('tkOpenReview');
          const closeBtn = document.getElementById('tkReviewClose');

          function lockBody(lock){
            document.documentElement.style.overflow = lock ? 'hidden' : '';
            document.body.style.overflow = lock ? 'hidden' : '';
          }

          function openDrawer(){
            if(!drawer || !backdrop) return;
            backdrop.classList.add('on');
            drawer.classList.add('on');
            drawer.setAttribute('aria-hidden','false');
            backdrop.setAttribute('aria-hidden','false');
            lockBody(true);
          }
          function closeDrawer(){
            if(!drawer || !backdrop) return;
            backdrop.classList.remove('on');
            drawer.classList.remove('on');
            drawer.setAttribute('aria-hidden','true');
            backdrop.setAttribute('aria-hidden','true');
            lockBody(false);
          }

          if(canReview && openBtn) openBtn.addEventListener('click', openDrawer);
          if(closeBtn) closeBtn.addEventListener('click', closeDrawer);
          if(backdrop) backdrop.addEventListener('click', closeDrawer);
          document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape') closeDrawer(); });

          // Selector cards -> show panel
          const optApprove = document.getElementById('tkOptApprove');
          const optReopen  = document.getElementById('tkOptReopen');
          const panelApprove = document.getElementById('tkPanelApprove');
          const panelReopen  = document.getElementById('tkPanelReopen');

          function setMode(mode){
            const isApprove = mode === 'approve';
            if(optApprove) optApprove.classList.toggle('isOn', isApprove);
            if(optReopen)  optReopen.classList.toggle('isOn', !isApprove);
            if(panelApprove) panelApprove.classList.toggle('on', isApprove);
            if(panelReopen)  panelReopen.classList.toggle('on', !isApprove);
          }

          // default: Reabrir
          setMode('reopen');

          if(optApprove) optApprove.addEventListener('click', ()=> setMode('approve'));
          if(optReopen)  optReopen.addEventListener('click',  ()=> setMode('reopen'));

          // Stars rating
          const stars  = document.getElementById('tkStars');
          const rating = document.getElementById('tkRating');
          function setStars(v){
            v = Math.max(1, Math.min(5, parseInt(v||5,10)));
            if(rating) rating.value = String(v);
            if(stars){
              stars.querySelectorAll('.tkd-star').forEach(el=>{
                const vv = parseInt(el.getAttribute('data-v')||'0',10);
                el.classList.toggle('on', vv <= v);
              });
            }
          }
          if(stars){
            stars.addEventListener('click', (e)=>{
              const el = e.target.closest('.tkd-star');
              if(!el) return;
              setStars(el.getAttribute('data-v'));
            });
          }

          // Approve confirm -> submit
          const approveAsk  = document.getElementById('tkApproveAsk');
          const approveForm = document.getElementById('tkApproveForm');
          if(approveAsk && approveForm){
            approveAsk.addEventListener('click', function(){
              const v = (document.getElementById('tkRating')?.value || '5');
              swalBase({
                icon: 'question',
                title: 'Aprobar y cerrar',
                html: `<div style="text-align:left">
                        Se aprobará el trabajo y el ticket se marcará como <b>completado</b>.<br>
                        Calificación: <b>${v}/5</b>.
                      </div>`,
                showCancelButton: true,
                confirmButtonText: 'Aprobar',
                cancelButtonText: 'Cancelar'
              }).then((res)=>{
                if(res.isConfirmed){
                  toast('info','Guardando...');
                  approveForm.submit();
                }
              });
            });
          }

          // Reopen files chips
          const fileInput = document.getElementById('tkReopenFiles');
          const chipWrap  = document.getElementById('tkReopenChips');
          let fileList = [];

          function rebuildInputFiles(){
            if(!fileInput) return;
            const dt = new DataTransfer();
            fileList.forEach(f=> dt.items.add(f));
            fileInput.files = dt.files;
          }
          function syncChips(){
            if(!chipWrap) return;
            chipWrap.innerHTML = '';
            if(!fileList.length){
              chipWrap.style.display = 'none';
              return;
            }
            chipWrap.style.display = 'flex';
            fileList.forEach((f, idx)=>{
              const chip = document.createElement('div');
              chip.className = 'tkd-chip';
              chip.innerHTML = `<span title="${(f?.name||'')}">${(f?.name||'archivo')}</span>
                                <button type="button" title="Quitar">×</button>`;
              chip.querySelector('button').addEventListener('click', ()=>{
                fileList.splice(idx,1);
                rebuildInputFiles();
                syncChips();
              });
              chipWrap.appendChild(chip);
            });
          }
          if(fileInput){
            fileInput.addEventListener('change', ()=>{
              const arr = Array.from(fileInput.files || []);
              arr.forEach(f=>{
                const key = `${f.name}__${f.size}`;
                const exists = fileList.some(x => `${x.name}__${x.size}` === key);
                if(!exists) fileList.push(f);
              });
              rebuildInputFiles();
              syncChips();
            });
          }

          // Reopen confirm -> submit
          const reopenAsk   = document.getElementById('tkReopenAsk');
          const reopenForm  = document.getElementById('tkReopenForm');
          const reopenReason = document.getElementById('tkReopenReason');
          if(reopenAsk && reopenForm && reopenReason){
            reopenAsk.addEventListener('click', function(){
              const action = (reopenForm.getAttribute('action') || '').trim();
              if(action === '' || action === 'javascript:void(0)'){
                swalBase({
                  icon:'warning',
                  title:'Falta la ruta',
                  html:'<div style="text-align:left">No existe la ruta <code>tickets.forceReopen</code> en <code>web.php</code>.</div>',
                  confirmButtonText:'Entendido'
                });
                return;
              }

              const v = (reopenReason.value || '').trim();
              if(v.length < 5){
                swalBase({
                  icon: 'warning',
                  title: 'Falta el motivo',
                  html: '<div style="text-align:left">Escribe una justificación (mínimo 5 caracteres).</div>',
                  confirmButtonText: 'Entendido'
                });
                return;
              }
              swalBase({
                icon: 'warning',
                title: 'Reabrir ticket',
                html: `<div style="text-align:left">
                        Se reabrirá el ticket para corrección y se notificará al asignado.<br>
                        Motivo: <b>${v.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</b>
                      </div>`,
                showCancelButton: true,
                confirmButtonText: 'Reabrir',
                cancelButtonText: 'Cancelar'
              }).then((res)=>{
                if(res.isConfirmed){
                  toast('info','Reabriendo...');
                  reopenForm.submit();
                }
              });
            });
          }

          // delete confirm
          document.querySelectorAll('form.tkDocDel').forEach(function(f){
            f.addEventListener('submit', function(e){
              e.preventDefault();
              swalBase({
                icon: 'warning',
                title: 'Eliminar adjunto',
                html: '<div style="text-align:left">Esta acción no se puede deshacer.</div>',
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar'
              }).then((res)=>{
                if(res.isConfirmed){
                  toast('info','Eliminando...');
                  f.submit();
                }
              });
            });
          });
        }

        // preview modal (siempre disponible)
        document.querySelectorAll('.tkPreview').forEach(function(btn){
          btn.addEventListener('click', function(){
            const url = btn.getAttribute('data-url') || '';
            const name = btn.getAttribute('data-name') || 'Vista previa';
            const ext  = btn.getAttribute('data-ext') || '';
            if(!url){ toast('error','No se encontró el archivo para previsualizar.'); return; }
            swalBase({
              title: name,
              html: buildPreviewHtml(url, ext),
              width: 980,
              showConfirmButton: false,
              showCancelButton: false,
            });
          });
        });

      });
    })();
  </script>
</div>
@endsection