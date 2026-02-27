{{-- resources/views/tickets/work.blade.php --}}
@extends('layouts.app')
@section('title', 'Trabajo | '.$ticket->folio)

@section('content')
@php
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Schema;

  try { \Carbon\Carbon::setLocale('es'); } catch (\Throwable $e) {}

  $statuses   = $statuses   ?? \App\Http\Controllers\Tickets\TicketController::STATUSES;
  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = $areas      ?? \App\Http\Controllers\Tickets\TicketController::AREAS;

  $workflow = [
    'pendiente'  => ['label'=>'Pendiente', 'key'=>'pendiente'],
    'revision'   => ['label'=>'En revisión', 'key'=>'revision'],
    'progreso'   => ['label'=>'En progreso', 'key'=>'progreso'],
    'bloqueado'  => ['label'=>'En espera', 'key'=>'bloqueado'],
    'pruebas'    => ['label'=>'En pruebas', 'key'=>'pruebas'],
    'completado' => ['label'=>'Completado', 'key'=>'completado'],
    'cancelado'  => ['label'=>'Cancelado', 'key'=>'cancelado'],
    'reabierto'  => ['label'=>'Reabierto', 'key'=>'reabierto'],
  ];

  $strict = ['pendiente','revision','progreso','pruebas','completado'];
  $strictIndex = array_flip($strict);

  $current = $ticket->status ?: 'pendiente';
  $isReopened = ($current === 'reabierto');
  $currentFlow = $isReopened ? 'pendiente' : $current;
  $currentLabel = $statuses[$current] ?? ($workflow[$current]['label'] ?? $current);

  $isFinal = in_array($current, ['completado','cancelado'], true);
  $canComplete = ($currentFlow === 'pruebas');
  $canCancel = !$isFinal;

  $isAssignee = auth()->check() && (string)auth()->id() === (string)($ticket->assignee_id ?? '');

  $sla = $ticket->sla_signal ?? 'neutral';
  $slaClass = $sla==='overdue' ? 'red' : ($sla==='due_soon' ? 'amber' : ($sla==='ok' ? 'green' : 'slate'));

  $pillStatusClass = function($st){
    return match($st){
      'completado' => 'green',
      'cancelado'  => 'red',
      'reabierto'  => 'red',
      'bloqueado'  => 'amber',
      'revision'   => 'amber',
      'pruebas'    => 'amber',
      default      => 'blue',
    };
  };

  $nextStrict = null;
  if (isset($strictIndex[$currentFlow]) && $strictIndex[$currentFlow] < count($strict)-1) {
    $nextStrict = $strict[$strictIndex[$currentFlow] + 1];
  } elseif ($currentFlow === 'bloqueado') {
    $nextStrict = 'progreso';
  }

  $canMoveTo = function(string $target) use ($current, $currentFlow, $nextStrict){
    if (in_array($current, ['completado','cancelado'], true)) return false;
    if ($target === $currentFlow) return false;

    if ($target === 'cancelado') return true;
    if ($target === 'completado') return false;

    if ($target === 'bloqueado') return true;
    if ($currentFlow === 'bloqueado') return in_array($target, ['progreso','pruebas'], true);

    return $target === $nextStrict;
  };

  $docUrl = function($d){
    if (empty($d->path)) return null;
    try { return Storage::url($d->path); } catch (\Throwable $e) { return null; }
  };

  $docKind = function($d){
    $mime = data_get($d, 'meta.mime') ?: (string)($d->mime ?? '');
    $name = (string)($d->name ?? '');
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (Str::startsWith($mime, 'image/')) return 'image';
    if (Str::startsWith($mime, 'video/')) return 'video';
    if (Str::startsWith($mime, 'audio/')) return 'audio';
    if ($mime === 'application/pdf' || $ext === 'pdf') return 'pdf';
    return 'file';
  };

  $steps = ['pendiente','revision','progreso','bloqueado','pruebas'];
  $order = ['pendiente','revision','progreso','bloqueado','pruebas'];

  $pos = array_search($currentFlow, $order, true);
  if ($pos === false) $pos = in_array($current, ['completado','cancelado'], true) ? count($order) : 0;

  $isStepDone = function(string $key) use ($order, $pos, $current, $currentFlow){
    $i = array_search($key, $order, true);
    if ($i === false) return false;
    if (in_array($current, ['completado','cancelado'], true)) return true;
    if ($currentFlow === 'bloqueado') return $i <= array_search('progreso', $order, true);
    return $i < $pos;
  };

  $isStepActive = function(string $key) use ($current, $currentFlow){
    if (in_array($current, ['completado','cancelado'], true)) return false;
    return $currentFlow === $key;
  };

  $n = count($order);
  $fillIndex = 0;
  if ($n > 1) {
    if ($isFinal) $fillIndex = $n - 1;
    elseif ($currentFlow === 'bloqueado') {
      $fillIndex = (int) array_search('progreso', $order, true);
      if ($fillIndex < 0) $fillIndex = 0;
    } else {
      $fillIndex = (int) $pos;
      if ($fillIndex < 0) $fillIndex = 0;
      if ($fillIndex > $n - 1) $fillIndex = $n - 1;
    }
  }
  $lineFill = ($n > 1) ? round(($fillIndex / ($n - 1)) * 100) : 0;

  $I = function($name){
    $icons = [
      'arrowLeft' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
      'check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
      'x'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
      'flag'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>',
      'shield'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
      'clock'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>',
      'play'      => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
      'pause'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>',
      'trash'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>',
      'chat'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>',
      'paperclip' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>',
      'eye'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
      'download'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
      'info'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
      'file'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>',
      'image'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>',
      'pdf'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
      'warn'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
      'bolt'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>',
      'list'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>',
    ];
    return $icons[$name] ?? $icons['info'];
  };

  $slaText = match($sla){
    'overdue'  => 'Vencido',
    'due_soon' => 'Por vencer',
    'ok'       => 'En tiempo',
    default    => 'Sin fecha',
  };

  $prioKey = (string)($ticket->priority ?? '');
  $prioLabel = $priorities[$prioKey] ?? ($prioKey ?: '—');
  $prioIsHigh = in_array(mb_strtolower($prioKey), ['alta','high','urgente','critica','crítico','critico'], true);

  $reopenVer = null;
  if (Schema::hasColumn('tickets','reopened_at') && !empty($ticket->reopened_at)) {
    $reopenVer = optional($ticket->reopened_at)->timestamp;
  } elseif (!empty($ticket->updated_at)) {
    $reopenVer = optional($ticket->updated_at)->timestamp;
  } else {
    $reopenVer = time();
  }

  // ✅ CHECKLIST REAL
  $checklistModel = null;
  try {
    if (isset($ticket->checklist) && $ticket->checklist) {
      $checklistModel = $ticket->checklist;
      if (!$checklistModel->relationLoaded('items')) $checklistModel->load('items');
    } else {
      $checklistModel = \App\Models\TicketChecklist::with('items')
        ->where('ticket_id', $ticket->id)
        ->orderByDesc('id')
        ->first();
    }
  } catch (\Throwable $e) { $checklistModel = null; }

  $checklistItems = $checklistModel?->items ?? collect();
  $hasChecklist = $checklistItems->count() > 0;

  // ✅ IDs para separar comentarios (izq quien asignó/creó, der encargado)
  $creatorId = null;
  if (Schema::hasColumn('tickets','created_by')) $creatorId = (int)($ticket->created_by ?? 0);
  if (!$creatorId && Schema::hasColumn('tickets','user_id')) $creatorId = (int)($ticket->user_id ?? 0);

  $assigneeId = (int)($ticket->assignee_id ?? 0);
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="{{ asset('css/ticket-work.css') }}?v={{ time() }}">

<div id="jtTicketWork">

  {{-- Ajustes SOLO para este contenedor (no mover diseño global) --}}
  <style>
    /* Checklist tachado */
    #jtTicketWork .jt-checkRow.is-done .jt-checkTxt{ text-decoration: line-through; opacity:.78; }
    #jtTicketWork .jt-checkRow.is-done .jt-checkTxt .jt-muted{ text-decoration:none; opacity:1; }

    /* Comentarios: izquierda/derecha dentro del contenedor de discusión */
    #jtTicketWork .jt-chatWrap{ display:flex; flex-direction:column; gap:12px; }
    #jtTicketWork .jt-msg{
      display:flex;
      width:100%;
      margin: 2px 0;
    }
    #jtTicketWork .jt-msg.is-left{ justify-content:flex-start; }
    #jtTicketWork .jt-msg.is-right{ justify-content:flex-end; }

    #jtTicketWork .jt-bubble{
      width: min(520px, 92%);
      border: 1px solid var(--jt-line);
      background:#fff;
      border-radius: 16px;
      padding: 12px 12px 10px;
      box-shadow: var(--jt-shadow-sm);
    }
    #jtTicketWork .jt-msg.is-left .jt-bubble{ border-top-left-radius: 10px; }
    #jtTicketWork .jt-msg.is-right .jt-bubble{
      border-top-right-radius: 10px;
      background: #f8fafc;
    }

    #jtTicketWork .jt-bubbleHd{
      display:flex; justify-content:space-between; align-items:center; gap:10px;
      margin-bottom:6px;
    }
    #jtTicketWork .jt-bubbleUser{
      font-weight:700; font-size:.9rem; color: var(--jt-ink);
      display:flex; align-items:center; gap:.55rem;
      min-width:0;
    }
    #jtTicketWork .jt-bubbleUser::before{
      content:''; width:26px; height:26px;
      border-radius:999px;
      background: linear-gradient(135deg, var(--jt-primary-soft), rgba(59,130,246,0.12));
      border: 1px solid rgba(59,130,246,0.14);
      flex:0 0 auto;
    }
    #jtTicketWork .jt-msg.is-right .jt-bubbleUser::before{
      background: linear-gradient(135deg, rgba(15,23,42,0.06), rgba(15,23,42,0.12));
      border-color: rgba(15,23,42,0.10);
    }
    #jtTicketWork .jt-bubbleTime{ color: var(--jt-muted); font-size:.78rem; font-weight:600; white-space:nowrap; }
    #jtTicketWork .jt-bubbleBody{
      white-space:pre-wrap;
      line-height:1.6;
      color:#334155;
      font-size:.95rem;
      font-weight:500;
    }

    /* Modal Finalizar minimalista */
    #jtTicketWork .jt-modalMini{
      background:#fff;
      width:100%;
      max-width: 760px;
      border-radius: 18px;
      box-shadow: var(--jt-shadow-modal);
      overflow:hidden;
      border: 1px solid rgba(226,232,240,.9);
    }
    #jtTicketWork .jt-badgesRow{ display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
    #jtTicketWork .jt-badge{
      display:inline-flex; align-items:center; gap:8px;
      border:1px solid rgba(226,232,240,.9);
      background:#fff;
      border-radius:999px;
      padding:6px 10px;
      font-weight:800;
      color:#0f172a;
      font-size:.8rem;
    }
    #jtTicketWork .jt-hr{ height:1px; background: var(--jt-line); margin: 14px 0; }
    #jtTicketWork .jt-help{ color:#64748b; font-weight:600; font-size:.82rem; margin-top:6px; }
  </style>

  {{-- Alertas --}}
  @if(session('ok'))
    <div class="alert alert-success" style="border-radius: var(--jt-r-md); font-weight: 500; border:1px solid rgba(16,185,129,0.2); background:rgba(16,185,129,0.05); color:#047857;">{{ session('ok') }}</div>
  @endif
  @if(session('err'))
    <div class="alert alert-danger" style="border-radius: var(--jt-r-md); font-weight: 500; border:1px solid rgba(239,68,68,0.2); background:rgba(239,68,68,0.05); color:#b91c1c;">{{ session('err') }}</div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger" style="border-radius: var(--jt-r-md); border:1px solid rgba(239,68,68,0.2); background:rgba(239,68,68,0.05); color:#b91c1c;">
      <strong style="font-weight:600;">Revisa los siguientes errores:</strong>
      <ul class="mb-0 mt-2" style="font-weight:400;">@foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
    </div>
  @endif

  {{-- TOPBAR --}}
  <div class="jt-topbar">
    <div class="jt-topbarGrid">
      <div>
        <h3 class="jt-title">{{ $ticket->folio }} <span style="color:var(--jt-muted); font-weight:400; margin:0 .25rem;">/</span> {{ $ticket->title }}</h3>

        <div class="jt-metaRow jt-muted">
          <span class="d-flex align-items-center gap-2">
            <span class="jt-ico">{!! $I('shield') !!}</span>
            <span style="font-weight:500; color: var(--jt-ink);">{{ optional($ticket->assignee)->name ?: 'Sin asignar' }}</span>
          </span>

          <span class="d-flex align-items-center gap-2">
            <span class="jt-ico">{!! $I('clock') !!}</span>
            <span>Vence: <span style="font-weight:500; color: var(--jt-ink);">{{ $ticket->due_at ? $ticket->due_at->format('d/m/Y H:i') : 'N/A' }}</span></span>
          </span>
        </div>

        <div class="jt-pills">
          <span class="jt-pill {{ $prioIsHigh ? 'jt-pillHigh' : 'blue' }}">
            {!! $prioIsHigh ? $I('bolt') : '' !!}
            {{ $prioLabel }}
            @if($prioIsHigh)
              <span class="jt-prioTag">PRIORIDAD</span>
            @endif
          </span>

          <span class="jt-pill slate">{{ $areas[$ticket->area] ?? ($ticket->area ?: 'Sin área') }}</span>
          <span class="jt-pill {{ $slaClass }}">{{ $slaText }}</span>
          <span class="jt-pill {{ $pillStatusClass($ticket->status) }}">Estado: {{ $currentLabel }}</span>
        </div>

        @if($isReopened || !empty($ticket->reopen_reason))
          <div class="jt-reopenBanner">
            <span class="jt-ico" style="color:#ef4444;">{!! $I('warn') !!}</span>
            <div style="min-width:0;">
              <div>Ticket reabierto: el flujo vuelve a iniciar desde el punto de reapertura (sin saltos).</div>
              @if(!empty($ticket->reopen_reason))
                <small style="white-space:pre-wrap;">Motivo: {{ $ticket->reopen_reason }}</small>
              @endif
            </div>
          </div>
        @endif
      </div>

      <div class="jt-actionsCol">
        <div class="jt-actionsRow">
          <a class="jt-btn" href="{{ route('tickets.show',$ticket) }}">
            <span class="jt-ico">{!! $I('arrowLeft') !!}</span> Ver Detalle
          </a>
        </div>

        @if(!$isAssignee)
          <div class="jt-muted" style="text-align:right; font-size:.8rem;">
            Solo el asignado puede gestionar este ticket.
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- GRID CONTENT --}}
  <div class="jt-grid">

    {{-- LEFT COLUMN --}}
    <div class="d-flex flex-column">

      {{-- WORKFLOW --}}
      <div class="jt-card">
        <div class="jt-cardHd">
          <h4 class="jt-cardT"><span class="jt-ico" style="color:var(--jt-primary);">{!! $I('flag') !!}</span> Progreso del Ticket</h4>

          <div class="d-flex gap-2 flex-wrap">
            @if(!$isFinal)
              <form method="POST" action="{{ route('tickets.complete',$ticket) }}" id="jtCompleteForm" class="m-0">
                @csrf
                <input type="hidden" name="elapsed_seconds" id="jtElapsedComplete" value="0">
                <input type="hidden" name="completion_detail" id="jtCompletionDetail" value="">
                <input type="hidden" name="checklist_json" id="jtChecklistJson" value="[]">
                <button class="jt-btn jt-btnSuccess" type="button" id="jtAskComplete" {{ (!$isAssignee || !$canComplete) ? 'disabled' : '' }}>
                  <span class="jt-ico">{!! $I('check') !!}</span> Finalizar
                </button>
              </form>

              <button class="jt-btn jt-btnDanger" type="button" id="jtOpenCancel" {{ (!$isAssignee || !$canCancel) ? 'disabled' : '' }}>
                <span class="jt-ico">{!! $I('x') !!}</span> Cancelar
              </button>
            @endif
          </div>
        </div>

        <div class="jt-cardBd">
          <div class="jt-tl" style="--jt-line-fill: {{ $lineFill }}%;">
            @foreach($steps as $key)
              @php
                $w = $workflow[$key];
                $isDone = $isStepDone($key);
                $isActive = $isStepActive($key);
              @endphp

              <div class="jt-tlStep {{ $isActive ? 'is-active' : '' }} {{ $isDone ? 'is-done' : '' }}">
                <div class="jt-stepInfo">
                  <div class="jt-dot"></div>
                  <div>
                    <div class="jt-stepName">{{ $w['label'] }}</div>
                    @if($isActive)
                      <div class="jt-stepSub">Estado actual</div>
                    @endif
                  </div>
                </div>

                <form method="POST" action="{{ route('tickets.update',$ticket) }}" class="m-0 jtMoveForm">
                  @csrf @method('PUT')
                  <input type="hidden" name="status" value="{{ $key }}">
                  <input type="hidden" name="elapsed_seconds" class="jtElapsedOnMove" value="0">
                  <button class="jt-btn" style="padding:.45rem .9rem; font-size:.8rem; border-radius: .4rem;" type="submit"
                    {{ (!$isAssignee || !$canMoveTo($key)) ? 'disabled' : '' }}>
                    Mover aquí
                  </button>
                </form>
              </div>
            @endforeach
          </div>

          {{-- CHECKLIST --}}
          @if($hasChecklist)
            <div class="jt-check" id="jtChecklistBox">
              <div class="jt-checkHd">
                <h5 class="jt-checkT">
                  <span class="jt-ico" style="color:var(--jt-primary);">{!! $I('list') !!}</span>
                  {{ $checklistModel->title ?: 'Checklist para finalizar' }}
                </h5>
                <div class="jt-checkMeta" id="jtChecklistMeta">0 de 0</div>
              </div>

              <div class="jt-checkBd">
                <div class="jt-muted" style="margin-bottom:10px;">
                  Marca lo que se realizó. Se guarda al instante y al finalizar se adjunta el detalle.
                </div>

                @foreach($checklistItems as $it)
                  @php $done = (bool)($it->done ?? false); @endphp
                  <label class="jt-checkRow {{ $done ? 'is-done' : '' }}" data-row-for="{{ $it->id }}">
                    <input
                      class="jt-box jtChecklistItem"
                      type="checkbox"
                      data-id="{{ $it->id }}"
                      data-text="{{ e($it->title) }}"
                      {{ $done ? 'checked' : '' }}
                      {{ (!$isAssignee || $isFinal) ? 'disabled' : '' }}
                    >
                    <div class="jt-checkTxt">
                      {{ $it->title }}
                      @if(!empty($it->detail))
                        <div class="jt-muted" style="margin-top:6px; font-weight:700; font-size:.82rem; white-space:pre-wrap;">{{ $it->detail }}</div>
                      @endif
                      @if($it->recommended)
                        <div class="jt-muted" style="margin-top:6px; font-weight:900; font-size:.78rem; color:#2563eb;">Recomendado</div>
                      @endif
                    </div>
                  </label>
                @endforeach

                @if(!$isAssignee && !$isFinal)
                  <div class="jt-muted" style="margin-top:10px;">
                    Solo el asignado puede marcar el checklist.
                  </div>
                @endif
              </div>
            </div>
          @else
            <div class="jt-muted" style="margin-top: 14px; padding: 12px 14px; border:1px dashed var(--jt-line); border-radius: var(--jt-r-xl); background: var(--jt-bg);">
              No hay checklist configurado para este ticket.
            </div>
          @endif

          @if(!$isAssignee && !$isFinal)
            <div class="jt-muted" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--jt-line); text-align: center;">
              Solo el asignado puede mover el progreso del ticket.
            </div>
          @endif

          @if($isAssignee && !$isFinal)
            <div class="jt-muted" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--jt-line); text-align: center;">
              Regla: no se puede completar si no pasa por todos los estados (Pendiente → Revisión → Progreso → Pruebas). En espera es opcional.
            </div>
          @endif
        </div>
      </div>

      {{-- COMMENTS --}}
      <div class="jt-card">
        <div class="jt-cardHd">
          <h4 class="jt-cardT"><span class="jt-ico" style="color:var(--jt-primary);">{!! $I('chat') !!}</span> Discusión y Notas</h4>
        </div>
        <div class="jt-cardBd">
          <form method="POST" action="{{ route('tickets.comments.store',$ticket) }}" class="mb-4">
            @csrf
            <textarea name="body" class="jt-input" placeholder="Añade una actualización, observación o nota interna del progreso..." {{ $isFinal ? 'disabled' : '' }}></textarea>
            <div class="d-flex justify-content-end mt-3">
              <button class="jt-btn jt-btnPrimary" type="submit" {{ $isFinal ? 'disabled' : '' }}>Publicar Nota</button>
            </div>
          </form>

          <div class="jt-chatWrap">
            @forelse($ticket->comments ?? [] as $c)
              @php
                $uid = (int)($c->user_id ?? optional($c->user)->id ?? 0);

                // ✅ izquierda = quien asignó/creó (creatorId)
                // ✅ derecha = usuario encargado (assigneeId)
                $side = 'left';
                if ($assigneeId && $uid === $assigneeId) $side = 'right';
                elseif ($creatorId && $uid === $creatorId) $side = 'left';
                else $side = 'left';
              @endphp

              <div class="jt-msg is-{{ $side }}">
                <div class="jt-bubble">
                  <div class="jt-bubbleHd">
                    <div class="jt-bubbleUser" title="{{ optional($c->user)->name ?: 'Usuario' }}">
                      <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: 320px;">
                        {{ optional($c->user)->name ?: 'Usuario' }}
                      </span>
                    </div>
                    <div class="jt-bubbleTime">{{ optional($c->created_at)->locale('es')->diffForHumans() }}</div>
                  </div>
                  <div class="jt-bubbleBody">{{ $c->body }}</div>
                </div>
              </div>
            @empty
              <div class="text-center jt-muted py-5" style="background: var(--jt-bg); border-radius: var(--jt-r-lg); border: 1px dashed var(--jt-line);">
                <span class="jt-ico mb-2" style="width:2.5rem; height:2.5rem; color: #cbd5e1;">{!! $I('chat') !!}</span><br>
                <span style="font-weight: 400;">No hay notas registradas en este ticket aún.</span>
              </div>
            @endforelse
          </div>
        </div>
      </div>

    </div>

    {{-- RIGHT COLUMN --}}
    <div class="d-flex flex-column">

      {{-- TIMER --}}
      <div class="jt-card" style="border:none; background:transparent; box-shadow:none; margin-bottom:0;">
        <div class="jt-timer">
          <div class="jt-timerLbl">Tiempo Activo</div>
          <div class="jt-timerVal" id="jtTimer">00:00:00</div>
          <div class="jt-timerBtns">
            <button class="jt-btn jt-btnTimer" type="button" id="jtStart" {{ $isFinal ? 'disabled' : '' }}>
              <span class="jt-ico">{!! $I('play') !!}</span> Iniciar
            </button>
            <button class="jt-btn jt-btnTimer" type="button" id="jtStop" disabled {{ $isFinal ? 'disabled' : '' }}>
              <span class="jt-ico">{!! $I('pause') !!}</span> Pausar
            </button>
            <button class="jt-btn jt-btnIcon" style="background:transparent; color:#f87171; border: 1px solid rgba(248,113,113,0.3);" type="button" id="jtReset" title="Reiniciar" {{ $isFinal ? 'disabled' : '' }}>
              <span class="jt-ico">{!! $I('trash') !!}</span>
            </button>
          </div>

          <div class="jt-muted" style="margin-top:12px; text-align:center;">
            Al entrar a <span style="font-weight:800; color:#e2e8f0;">En progreso</span> el timer inicia automáticamente y no se pausa hasta terminar.
          </div>
        </div>
      </div>

      {{-- INFO --}}
      <div class="jt-card mt-4">
        <div class="jt-cardHd">
          <h4 class="jt-cardT"><span class="jt-ico" style="color:var(--jt-primary);">{!! $I('info') !!}</span> Detalles del Ticket</h4>
        </div>
        <div class="jt-cardBd" style="padding: 0.5rem 1.5rem;">
          <div class="jt-dl">
            <div class="jt-dlItem">
              <span class="jt-lbl">Prioridad</span>
              <span class="jt-val">
                <span class="jt-pill {{ $prioIsHigh ? 'jt-pillHigh' : 'slate' }}" style="padding:.25rem .75rem; font-size:.7rem;">
                  {!! $prioIsHigh ? $I('bolt') : '' !!}
                  {{ $prioLabel }}
                  @if($prioIsHigh)<span class="jt-prioTag">URGENTE</span>@endif
                </span>
              </span>
            </div>

            <div class="jt-dlItem">
              <span class="jt-lbl">Área</span>
              <span class="jt-val">{{ $areas[$ticket->area] ?? ($ticket->area ?: '—') }}</span>
            </div>

            <div class="jt-dlItem">
              <span class="jt-lbl">Descripción</span>
              <span class="jt-val">
                {{ Str::limit($ticket->description ?: 'Sin descripción detallada provista en la solicitud.', 160) }}
              </span>
            </div>

            @if(!empty($ticket->reopen_reason))
              <div class="jt-dlItem">
                <span class="jt-lbl">Motivo reapertura</span>
                <span class="jt-val" style="white-space:pre-wrap;">{{ $ticket->reopen_reason }}</span>
              </div>
            @endif

          </div>
        </div>
      </div>

      {{-- ATTACHMENTS --}}
      <div class="jt-card">
        <div class="jt-cardHd">
          <h4 class="jt-cardT"><span class="jt-ico" style="color:var(--jt-primary);">{!! $I('paperclip') !!}</span> Archivos Adjuntos</h4>
        </div>

        <div class="jt-cardBd">
          <form method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data" id="jtUploadForm">
            @csrf
            <div class="jt-drop">
              <div class="jt-muted" style="display:flex; gap:.6rem; justify-content:center; align-items:center;">
                <span class="jt-ico" style="color:var(--jt-primary);">{!! $I('download') !!}</span>
                <span style="font-weight: 500;">Arrastra un archivo o selecciónalo</span>
              </div>

              <div class="jt-fileRow">
                <label class="jt-btn m-0" for="jtFile"
                  style="background:white; cursor:pointer; justify-content:center; {{ (!$isAssignee || $isFinal) ? 'pointer-events:none;opacity:0.5;' : '' }}">
                  Explorar Archivos...
                </label>

                <div class="jt-fileName" id="jtFileName">Ningún archivo...</div>
                <input type="file" id="jtFile" name="file" class="d-none" {{ (!$isAssignee || $isFinal) ? 'disabled' : '' }}>
              </div>

              <button class="jt-btn jt-btnPrimary w-100 mt-4" type="submit" {{ (!$isAssignee || $isFinal) ? 'disabled' : '' }} style="justify-content:center; padding: 0.75rem;">
                Subir Archivo
              </button>
            </div>
          </form>

          <div class="mt-4">
            @forelse($ticket->documents ?? [] as $d)
              @php
                $url  = $docUrl($d);
                $kind = $docKind($d);
                $docIcon = match($kind){ 'image'=>'image', 'pdf'=>'pdf', default=>'file' };
              @endphp

              <div class="jt-doc">
                <div class="jt-docL">
                  <div class="jt-docIcon"><span class="jt-ico">{!! $I($docIcon) !!}</span></div>
                  <div class="jt-docMeta">
                    <div class="jt-docName" title="{{ $d->name }}">{{ $d->name }}</div>
                    <div class="jt-docDate">
                      {{ optional($d->created_at)->locale('es')->translatedFormat('d M Y') }}
                    </div>
                  </div>
                </div>

                <div class="jt-docBtns">
                  <button type="button"
                    class="jt-btn jt-btnIcon"
                    style="background: var(--jt-bg); border: 1px solid var(--jt-line);"
                    data-preview="1"
                    data-kind="{{ $kind }}"
                    data-name="{{ e($d->name) }}"
                    data-url="{{ $url ? e($url) : '' }}"
                    data-download="{{ e(route('tickets.documents.download',[$ticket,$d])) }}"
                    title="Vista Previa">
                    <span class="jt-ico">{!! $I('eye') !!}</span>
                  </button>

                  <a class="jt-btn jt-btnIcon"
                    style="background: var(--jt-primary-soft); color: var(--jt-primary-hover); border: transparent;"
                    href="{{ route('tickets.documents.download',[$ticket,$d]) }}"
                    title="Descargar">
                    <span class="jt-ico">{!! $I('download') !!}</span>
                  </a>
                </div>
              </div>
            @empty
              <div class="text-center jt-muted mt-2" style="font-size:.85rem; font-weight: 400;">Aún no hay documentos adjuntos.</div>
            @endforelse
          </div>

        </div>
      </div>

    </div>
  </div>

  {{-- MODAL PREVIEW --}}
  <div class="jt-modalBack" id="jtPvModal">
    <div class="jt-modal">
      <div class="jt-modalHd">
        <h4 class="jt-cardT m-0"><span class="jt-ico" style="color: var(--jt-primary);">{!! $I('eye') !!}</span> <span id="jtPvTitle">Vista previa</span></h4>
        <button class="jt-btn jt-btnIcon" type="button" id="jtPvClose" style="border:none; background:transparent;">
          <span class="jt-ico">{!! $I('x') !!}</span>
        </button>
      </div>
      <div class="jt-modalBd">
        <div class="jt-preview" id="jtPvFrame"><div style="color:#94a3b8; font-weight:500;">Cargando visualización...</div></div>
        <div class="d-flex justify-content-end mt-4">
          <a class="jt-btn jt-btnPrimary" id="jtPvDownload" href="#" target="_blank">
            <span class="jt-ico">{!! $I('download') !!}</span> Descargar Archivo
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL CANCELAR --}}
  <div class="jt-modalBack" id="jtCancelModal">
    <div class="jt-modal" style="max-width: 520px;">
      <div class="jt-modalHd">
        <h4 class="jt-cardT m-0" style="color: var(--jt-danger);"><span class="jt-ico">{!! $I('warn') !!}</span> Cancelar Ticket</h4>
        <button class="jt-btn jt-btnIcon" type="button" id="jtCancelClose" style="border:none; background:transparent;">
          <span class="jt-ico">{!! $I('x') !!}</span>
        </button>
      </div>
      <div class="jt-modalBd">
        <form method="POST" action="{{ route('tickets.cancel',$ticket) }}" id="jtCancelForm">
          @csrf
          <input type="hidden" name="elapsed_seconds" id="jtElapsedCancel" value="0">

          <label class="jt-lbl mb-2 d-block">Motivo de cancelación (Requerido)</label>
          <textarea name="reason" class="jt-input mb-4" required placeholder="Ej. El ticket fue duplicado, se resolvió por otra vía..."></textarea>

          <div class="d-flex gap-2 justify-content-end flex-wrap">
            <button class="jt-btn" type="button" id="jtCancelBack">Volver</button>
            <button class="jt-btn jt-btnDanger" type="submit" onclick="return confirm('¿Confirmar cancelación definitiva?');">
              Confirmar Cancelación
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ✅ MODAL FINALIZAR (reemplaza SweetAlert) --}}
  <div class="jt-modalBack" id="jtCompleteModal">
    <div class="jt-modalMini">
      <div class="jt-modalHd">
        <h4 class="jt-cardT m-0" style="color: var(--jt-success);">
          <span class="jt-ico">{!! $I('check') !!}</span> Finalizar Ticket
        </h4>
        <button class="jt-btn jt-btnIcon" type="button" id="jtCompleteClose" style="border:none; background:transparent;">
          <span class="jt-ico">{!! $I('x') !!}</span>
        </button>
      </div>

      <div class="jt-modalBd">
        <div class="jt-muted">Antes de finalizar, escribe un detalle claro de lo que se realizó.</div>

        <div class="jt-badgesRow">
          <span class="jt-badge">⏱️ <span id="jtCompleteTime">00:00:00</span></span>
          <span class="jt-badge">✅ <span id="jtCompleteChk">0/0</span></span>
        </div>

        <div class="jt-hr"></div>

        <label class="jt-lbl mb-2 d-block">Detalle de lo realizado (Requerido)</label>
        <textarea id="jtCompleteDetailInput" class="jt-input" rows="5" placeholder="Ej. Se revisó el requerimiento, se corrigió el problema, se validó en pruebas y se adjuntó evidencia..."></textarea>
        <div class="jt-help">Incluye qué se cambió, cómo se probó y si se adjuntó evidencia.</div>

        <div class="d-flex gap-2 justify-content-end flex-wrap mt-4">
          <button class="jt-btn" type="button" id="jtCompleteBack">Volver</button>
          <button class="jt-btn jt-btnSuccess" type="button" id="jtCompleteConfirm">
            Finalizar
          </button>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // ===== CSRF (fetch) =====
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // ===== UPLOAD UI =====
  const file = document.getElementById('jtFile');
  const nameEl = document.getElementById('jtFileName');
  const uploadZone = document.querySelector('#jtTicketWork .jt-drop');

  function updateFileName(){
    if(!file || !nameEl) return;
    nameEl.textContent = (file.files && file.files[0]) ? file.files[0].name : 'Ningún archivo...';
  }
  file?.addEventListener('change', updateFileName);

  if(uploadZone && file && !file.disabled){
    ['dragover', 'dragenter'].forEach(evt => {
      uploadZone.addEventListener(evt, e => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--jt-primary)';
        uploadZone.style.background = 'var(--jt-primary-soft)';
      });
    });
    ['dragleave', 'drop'].forEach(evt => {
      uploadZone.addEventListener(evt, e => {
        e.preventDefault();
        uploadZone.style.borderColor = '#cbd5e1';
        uploadZone.style.background = '#f8fafc';
      });
    });
    uploadZone.addEventListener('drop', e => {
      if(e.dataTransfer?.files?.length){
        file.files = e.dataTransfer.files;
        updateFileName();
      }
    });
  }

  // ===== Modal helper =====
  function modalApi(modalId, closeBtnIds){
    const modal = document.getElementById(modalId);
    const api = {
      el: modal,
      open: function(){
        if(!modal) return;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      },
      close: function(){
        if(!modal) return;
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
      }
    };

    if(!modal) return api;

    (closeBtnIds || []).forEach(id => document.getElementById(id)?.addEventListener('click', api.close));
    modal.addEventListener('mousedown', e => { if(e.target === modal) api.close(); });
    document.addEventListener('keydown', e => { if(e.key === 'Escape' && modal.classList.contains('is-open')) api.close(); });

    return api;
  }

  // ===== TIMER (auto-start en progreso y NO se pausa hasta terminar) =====
  const isAssignee = @json($isAssignee);
  const isFinal    = @json($isFinal);
  const canComplete = @json((bool)$canComplete);
  const currentStatusFlow = @json((string)$currentFlow);

  const reopenVer = @json((int)$reopenVer);
  const ticketId = @json((string)$ticket->id);

  const key = 'jt_timer_' + ticketId;
  const verKey = 'jt_timer_ver_' + ticketId;

  try{
    const prevVer = parseInt(localStorage.getItem(verKey) || '0', 10);
    if(!prevVer || prevVer !== reopenVer){
      localStorage.setItem(verKey, String(reopenVer));
      localStorage.removeItem(key);
    }
  }catch(e){}

  const el = document.getElementById('jtTimer');
  const btnStart = document.getElementById('jtStart');
  const btnStop  = document.getElementById('jtStop');
  const btnReset = document.getElementById('jtReset');

  let state = JSON.parse(localStorage.getItem(key)) || { running:false, startAt:null, elapsed:0, startedOnce:false };
  let tInterval = null;

  function pad(n){ return String(n).padStart(2,'0'); }
  function formatSecs(sec){
    sec = Math.max(0, Math.floor(sec));
    return `${pad(Math.floor(sec/3600))}:${pad(Math.floor((sec%3600)/60))}:${pad(sec%60)}`;
  }
  function nowSecs(){ return Math.floor(Date.now()/1000); }
  function getElapsed(){
    return state.running && state.startAt ? (state.elapsed + (nowSecs() - state.startAt)) : state.elapsed;
  }
  function saveState(){ localStorage.setItem(key, JSON.stringify(state)); }

  function syncUI(){
    if(el) el.textContent = formatSecs(getElapsed());

    // ✅ Ya no se usa pause. Mantén diseño, pero deshabilitado.
    if(btnStop) btnStop.disabled = true;

    // ✅ start manual deshabilitado (solo auto al entrar a progreso)
    if(btnStart) btnStart.disabled = true;

    // ✅ reset también deshabilitado (no pausar ni resetear hasta terminar)
    if(btnReset) btnReset.disabled = true;

    if(state.running && el) {
      el.style.textShadow = '0 0 20px rgba(56, 189, 248, 0.4)';
      el.style.color = '#7dd3fc';
    } else if(el) {
      el.style.textShadow = '0 0 15px rgba(56, 189, 248, 0.2)';
      el.style.color = '#38bdf8';
    }
  }

  function startTimerForce(){
    if(!isAssignee || isFinal) return;
    if(state.running) return;

    state.running = true;
    state.startAt = nowSecs();
    state.startedOnce = true;
    saveState();

    if(!tInterval) tInterval = setInterval(syncUI, 1000);
    syncUI();
  }

  function stopTimer(){
    if(!state.running) return;
    state.elapsed = getElapsed();
    state.running = false;
    state.startAt = null;
    saveState();
    clearInterval(tInterval); tInterval = null;
    syncUI();
  }

  // ✅ Si ya estás en progreso al cargar, iniciar sí o sí
  if(isAssignee && !isFinal && currentStatusFlow === 'progreso'){
    startTimerForce();
  }

  syncUI();
  if(state.running) tInterval = setInterval(syncUI, 1000);

  // Enviar tiempo al cancelar
  const cancelForm   = document.getElementById('jtCancelForm');
  const elCancel     = document.getElementById('jtElapsedCancel');
  cancelForm?.addEventListener('submit', function(){
    if(elCancel) elCancel.value = String(getElapsed());
    stopTimer();
  });

  // ✅ En cada cambio de estatus: si el target es progreso, iniciar ANTES de enviar
  document.querySelectorAll('form.jtMoveForm').forEach(function(f){
    f.addEventListener('submit', function(){
      const hid = f.querySelector('.jtElapsedOnMove');
      if(hid) hid.value = String(getElapsed());

      const target = f.querySelector('input[name="status"]')?.value || '';
      if(target === 'progreso'){
        startTimerForce(); // ✅ inicia sí o sí
      }
    });
  });

  // ===== CHECKLIST (tachado + contador + guarda en BD) =====
  const hasChecklist = @json((bool)$hasChecklist);

  function readChecklist(){
    const items = [];
    document.querySelectorAll('.jtChecklistItem').forEach(cb => {
      items.push({
        id: cb.dataset.id || '',
        text: cb.dataset.text || '',
        done: !!cb.checked
      });
    });
    return items;
  }

  function applyRowState(cb){
    const row = cb.closest('.jt-checkRow');
    if(!row) return;
    if(cb.checked) row.classList.add('is-done');
    else row.classList.remove('is-done');
  }

  function updateChecklistMeta(){
    if(!hasChecklist) return;
    const meta = document.getElementById('jtChecklistMeta');
    const items = readChecklist();
    const total = items.length;
    const done  = items.filter(x => x.done).length;
    if(meta) meta.textContent = `${done} de ${total}`;

    const btn = document.getElementById('jtAskComplete');
    if(btn){
      const baseDisabled = (!isAssignee || isFinal || !canComplete);
      const okAll = total > 0 ? (done === total) : true;
      btn.disabled = baseDisabled || !okAll;
    }
  }

  document.querySelectorAll('.jtChecklistItem').forEach(cb => applyRowState(cb));
  updateChecklistMeta();

  async function saveChecklistItemToDb(itemId, done){
    const url = `/tickets/${encodeURIComponent(ticketId)}/checklist-items/${encodeURIComponent(itemId)}`;
    const res = await fetch(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ done: !!done })
    });

    if(!res.ok){
      let msg = 'No se pudo guardar el checklist.';
      try{
        const j = await res.json();
        msg = j.message || msg;
      }catch(e){}
      throw new Error(msg);
    }
    return true;
  }

  document.querySelectorAll('.jtChecklistItem').forEach(cb => {
    cb.addEventListener('change', async () => {
      if(!hasChecklist) return;

      applyRowState(cb);
      updateChecklistMeta();

      const prev = !cb.checked;
      try{
        await saveChecklistItemToDb(cb.dataset.id, cb.checked);
      }catch(err){
        cb.checked = prev;
        applyRowState(cb);
        updateChecklistMeta();
        alert(err?.message || 'No se pudo guardar. Intenta de nuevo.');
      }
    });
  });

  // ===== MODALS (cancel, preview, finalizar) =====
  const cancelModalObj = modalApi('jtCancelModal', ['jtCancelBack','jtCancelClose']);
  document.getElementById('jtOpenCancel')?.addEventListener('click', () => {
    cancelModalObj.open();
    setTimeout(() => document.querySelector('#jtCancelForm textarea')?.focus(), 120);
  });

  const pvModalObj = modalApi('jtPvModal', ['jtPvClose']);
  document.querySelectorAll('[data-preview="1"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const { kind, name, url, download } = btn.dataset;

      const t = document.getElementById('jtPvTitle');
      const d = document.getElementById('jtPvDownload');
      const frame = document.getElementById('jtPvFrame');

      if(t) t.textContent = name || 'Vista previa';
      if(d) d.href = download || '#';
      if(frame) frame.innerHTML = '';

      if(!frame){
        pvModalObj.open();
        return;
      }

      if(!url){
        frame.innerHTML = '<div style="color:#94a3b8; font-weight:700; text-align:center; padding:2rem;">Sin previsualización en línea.<br>Descarga el archivo.</div>';
      } else if(kind === 'image') {
        frame.innerHTML = `<img src="${url}" alt="${name || ''}">`;
      } else if(kind === 'video') {
        frame.innerHTML = `<video src="${url}" controls playsinline></video>`;
      } else if(kind === 'audio') {
        frame.innerHTML = `<audio src="${url}" controls style="width:88%;"></audio>`;
      } else if(kind === 'pdf') {
        frame.innerHTML = `<iframe src="${url}#toolbar=0&navpanes=0&scrollbar=0"></iframe>`;
      } else {
        frame.innerHTML = '<div style="color:#94a3b8; font-weight:700; text-align:center; padding:2rem;">Formato sin previsualización.<br>Por favor descarga el archivo para verlo.</div>';
      }

      pvModalObj.open();
    });
  });

  // ✅ Modal finalizar (reemplaza SweetAlert)
  const completeModalObj = modalApi('jtCompleteModal', ['jtCompleteBack','jtCompleteClose']);

  const askCompleteBtn = document.getElementById('jtAskComplete');
  const completeForm   = document.getElementById('jtCompleteForm');
  const elComplete     = document.getElementById('jtElapsedComplete');
  const elDetail       = document.getElementById('jtCompletionDetail');
  const elChkJson      = document.getElementById('jtChecklistJson');

  const modalTime = document.getElementById('jtCompleteTime');
  const modalChk  = document.getElementById('jtCompleteChk');
  const modalDetail = document.getElementById('jtCompleteDetailInput');
  const modalConfirm = document.getElementById('jtCompleteConfirm');

  function refreshCompleteModal(){
    const elapsed = getElapsed();
    const items = hasChecklist ? readChecklist() : [];
    const total = items.length;
    const done  = items.filter(x => x.done).length;

    if(modalTime) modalTime.textContent = formatSecs(elapsed);
    if(modalChk) modalChk.textContent = `${done}/${total}`;
  }

  askCompleteBtn?.addEventListener('click', function(){
    if(!completeForm || askCompleteBtn.disabled) return;

    // Seguridad: checklist completo si existe
    const items = hasChecklist ? readChecklist() : [];
    if(hasChecklist && items.length > 0 && items.some(x => !x.done)){
      alert('Para finalizar, completa el checklist.');
      return;
    }

    refreshCompleteModal();
    if(modalDetail) modalDetail.value = '';
    completeModalObj.open();
    setTimeout(() => modalDetail?.focus(), 120);
  });

  modalConfirm?.addEventListener('click', function(){
    if(!completeForm) return;

    const detail = (modalDetail?.value || '').trim();
    if(detail.length < 10){
      alert('Escribe un detalle (mínimo 10 caracteres).');
      modalDetail?.focus();
      return;
    }

    const elapsed = getElapsed();
    const items = hasChecklist ? readChecklist() : [];

    if(elComplete) elComplete.value = String(elapsed);
    if(elDetail) elDetail.value = detail;
    if(elChkJson) elChkJson.value = JSON.stringify(items || []);

    stopTimer();
    completeForm.submit();
  });
});
</script>
@endsection