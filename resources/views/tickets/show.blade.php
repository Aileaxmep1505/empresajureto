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

  $actionLabels = [
    'ticket_created'              => 'Ticket creado',
    'ticket_updated'              => 'Ticket actualizado',
    'comment_added'               => 'Comentario agregado',
    'doc_uploaded'                => 'Archivo adjunto',
    'evidence_uploaded'           => 'Evidencia subida',
    'ticket_completed'            => 'Ticket completado',
    'ticket_cancelled'            => 'Ticket cancelado',
    'ticket_submitted_for_review' => 'Enviado a revisión',
    'ticket_review_approved'      => 'Revisión aprobada',
    'ticket_review_rejected'      => 'Revisión rechazada (reabierto)',
    'ticket_force_reopened'       => 'Reabierto por revisión',
    'report_generated'            => 'Reporte PDF generado',
  ];

  $prettyKey = function(string $k){
    $map = [
      'title'=>'Título','description'=>'Descripción','priority'=>'Prioridad','area'=>'Área','status'=>'Estatus',
      'assignee'=>'Asignado a','assignee_id'=>'Asignado a','due_at'=>'Vencimiento',
      'impact'=>'Impacto','urgency'=>'Urgencia','effort'=>'Esfuerzo','score'=>'Score',
      'files'=>'Archivos','files_uploaded'=>'Archivos','cancel_reason'=>'Motivo de cancelación',
      'review_rating'=>'Calificación','review_comment'=>'Comentario','reason'=>'Motivo','reopen_reason'=>'Motivo de reapertura',
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
  $canClose   = (bool)($isAssignee || $isCreator);

  $isCancelled = ((string)($ticket->status ?? '') === 'cancelado') || !empty($ticket->cancelled_at);
  $isCompleted = ((string)($ticket->status ?? '') === 'completado') || !empty($ticket->completed_at);
  $isFinal     = (bool)($isCancelled || $isCompleted);

  // ✅ Detectar APROBADO (robusto: por columnas o por auditoría)
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
@endphp

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div id="tkPremium">
  <link rel="stylesheet" href="{{ asset('css/ticket-show.css') }}?v={{ time() }}">

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

    /* ✅ CAMBIO: botones juntos (lado a lado) para subir el bloque de evidencias */
    #tkPremium .tkd-choice{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap:10px;
      align-items:stretch;
    }
    @media (max-width: 520px){
      #tkPremium .tkd-choice{ grid-template-columns: 1fr; }
    }

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
    #tkPremium .tkd-opt p{ margin:6px 0 0; font-size:12.5px; font-weight:700; color:#64748b; line-height:1.35; }

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
    #tkPremium .tkd-actions .btn{
      width:100%;
      justify-content:center;
    }

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

            {{-- ✅ FIX: tu ruta soporta POST (por eso quitamos PUT) --}}
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
          

          {{-- ✅ TU RUTA SOPORTA POST --}}
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

            {{-- ✅ Si ya está aprobado: NO mostrar revisar, ni trabajar, ni completar, ni cancelar --}}
            @if(!$readOnly)

              @if($canReview)
                <button type="button" class="btn pastel-indigo" id="tkOpenReview" title="Revisar / reabrir">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.77 5.82 22 7 14.14l-5-4.87 6.91-1.01L12 2z"/>
                  </svg>
                  Revisar
                </button>
              @endif

              @if(!$isFinal)

                @if(\Illuminate\Support\Facades\Route::has('tickets.work') && $canWork)
                  <a class="btn pastel-indigo" href="{{ route('tickets.work',$ticket) }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 20V10"/><path d="m18 14-6-6-6 6"/></svg>
                    Trabajar
                  </a>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('tickets.complete') && $canClose)
                  <form id="tkCompleteForm" method="POST" action="{{ route('tickets.complete',$ticket) }}" style="margin:0;">
                    @csrf
                    <button class="btn pastel-mint" type="submit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 6 9 17l-5-5"/></svg>
                      Completar
                    </button>
                  </form>
                @endif

                @if(\Illuminate\Support\Facades\Route::has('tickets.cancel') && $canClose)
                  <form id="tkCancelForm" method="POST" action="{{ route('tickets.cancel',$ticket) }}" style="margin:0;">
                    @csrf
                    <input type="hidden" name="cancel_reason" id="tkCancelReason" value="">
                    <button class="btn pastel-rose" type="submit">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                      Cancelar
                    </button>
                  </form>
                @endif

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
                    Impacto: <strong>{{ $ticket->impact ?? '—' }}</strong> &nbsp;|&nbsp;
                    Urgencia: <strong>{{ $ticket->urgency ?? '—' }}</strong> &nbsp;|&nbsp;
                    Esfuerzo: <strong>{{ $ticket->effort ?? '—' }}</strong>
                  </div>
                </div>

                @if(!empty($ticket->reopen_reason))
                <div class="row">
                  <div class="k">Motivo reapertura</div>
                  <div class="v" style="white-space:pre-wrap;">{{ $ticket->reopen_reason }}</div>
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

              {{-- ✅ Solo lectura: ocultar subir archivos --}}
              @if(!$readOnly)
                <form id="tkUploadForm" method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data">
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
                    @endphp

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

                    @if(in_array($a->action, ['ticket_review_rejected','ticket_force_reopened'], true) && !empty($diff['reason']))
                      <div class="audit-box">
                        <div class="audit-box-header">Motivo de reapertura</div>
                        <div class="audit-box-body" style="white-space:pre-wrap; font-weight:750; color:var(--text-main);">
                          {{ $diff['reason'] }}
                        </div>
                      </div>
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
                        $keys = ['status','priority','area','assignee_id','due_at'];
                        $changes = [];
                        foreach ($keys as $k){
                          if (($before[$k] ?? null) != ($after[$k] ?? null)) $changes[$k] = ['from'=>$before[$k]??'—', 'to'=>$after[$k]??'—'];
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