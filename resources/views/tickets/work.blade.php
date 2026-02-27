{{-- resources/views/tickets/work.blade.php --}}
@extends('layouts.app')
@section('title', 'Trabajo | '.$ticket->folio)

@section('content')
@php
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Schema;

  $statuses   = $statuses   ?? \App\Http\Controllers\Tickets\TicketController::STATUSES;
  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = $areas      ?? \App\Http\Controllers\Tickets\TicketController::AREAS;

  // ✅ Workflow base (pasos reales del proceso)
  $workflow = [
    'pendiente'  => ['label'=>'Pendiente', 'key'=>'pendiente'],
    'revision'   => ['label'=>'En revisión', 'key'=>'revision'],
    'progreso'   => ['label'=>'En progreso', 'key'=>'progreso'],
    'bloqueado'  => ['label'=>'En espera', 'key'=>'bloqueado'],
    'pruebas'    => ['label'=>'En pruebas', 'key'=>'pruebas'],
    'completado' => ['label'=>'Completado', 'key'=>'completado'],
    'cancelado'  => ['label'=>'Cancelado', 'key'=>'cancelado'],

    // ✅ Estado informativo (cuando regresa a empezar)
    'reabierto'  => ['label'=>'Reabierto', 'key'=>'reabierto'],
  ];

  // ✅ Secuencia estricta (no incluye bloqueado)
  $strict = ['pendiente','revision','progreso','pruebas','completado'];
  $strictIndex = array_flip($strict);

  // Estado actual real
  $current = $ticket->status ?: 'pendiente';

  // ✅ REGLA: si está "reabierto" -> debe volver a recorrer el proceso desde el punto de reapertura.
  // En tu sistema, cuando se reabre con back_to="reabierto", significa "volver a empezar".
  // Entonces lo tratamos como "pendiente" para el flujo, pero mostramos el pill como "reabierto".
  $isReopened = ($current === 'reabierto');

  // "currentFlow" es el estado que manda el flujo (para reglas de avance)
  $currentFlow = $isReopened ? 'pendiente' : $current;

  // Para el usuario: etiqueta visible
  $currentLabel = $statuses[$current] ?? ($workflow[$current]['label'] ?? $current);

  $isFinal = in_array($current, ['completado','cancelado'], true);
  $canComplete = ($currentFlow === 'pruebas'); // ✅ finalizar solo desde pruebas
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

  // ✅ calcular siguiente paso estricto a partir del flujo (reabierto => empieza como pendiente)
  $nextStrict = null;
  if (isset($strictIndex[$currentFlow]) && $strictIndex[$currentFlow] < count($strict)-1) {
    $nextStrict = $strict[$strictIndex[$currentFlow] + 1];
  } elseif ($currentFlow === 'bloqueado') {
    $nextStrict = 'progreso';
  }

  // ✅ Regla de movimientos:
  // - No saltos (secuencia estricta)
  // - bloqueado se permite siempre
  // - cancelado se permite siempre
  // - completado solo desde botón Finalizar (no desde mover aquí)
  // - Si el ticket está "reabierto", el flujo volvió a arrancar (desde pendiente), por eso NO te deja brincar.
  $canMoveTo = function(string $target) use ($current, $currentFlow, $nextStrict){
    if (in_array($current, ['completado','cancelado'], true)) return false;

    // En reabierto, solo cuenta el flujo
    if ($target === $currentFlow) return false;

    if ($target === 'cancelado') return true;
    if ($target === 'completado') return false;

    // bloqueado siempre permitido
    if ($target === 'bloqueado') return true;

    // si estaba bloqueado, se permite reanudar a progreso o pruebas
    if ($currentFlow === 'bloqueado') return in_array($target, ['progreso','pruebas'], true);

    // estrictamente el siguiente
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

  // ✅ Pasos visibles del proceso (la línea de progreso NO incluye reabierto)
  $steps = ['pendiente','revision','progreso','bloqueado','pruebas'];
  $order = ['pendiente','revision','progreso','bloqueado','pruebas'];

  // posición según flujo
  $pos = array_search($currentFlow, $order, true);
  if ($pos === false) {
    $pos = in_array($current, ['completado','cancelado'], true) ? count($order) : 0;
  }

  // ✅ En reabierto: “reinicia” completados (empieza desde pendiente)
  $isStepDone = function(string $key) use ($order, $pos, $current, $currentFlow){
    $i = array_search($key, $order, true);
    if ($i === false) return false;

    if (in_array($current, ['completado','cancelado'], true)) return true;

    // bloqueado se considera hasta progreso como hecho
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
    if ($isFinal) {
      $fillIndex = $n - 1;
    } elseif ($currentFlow === 'bloqueado') {
      $fillIndex = (int) array_search('progreso', $order, true);
      if ($fillIndex < 0) $fillIndex = 0;
    } else {
      $fillIndex = (int) $pos;
      if ($fillIndex < 0) $fillIndex = 0;
      if ($fillIndex > $n - 1) $fillIndex = $n - 1;
    }
  }
  $lineFill = ($n > 1) ? round(($fillIndex / ($n - 1)) * 100) : 0;

  // ✅ Íconos
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

  // ✅ versión de reapertura para resetear timer en front (si existe)
  $reopenVer = null;
  if (Schema::hasColumn('tickets','reopened_at') && !empty($ticket->reopened_at)) {
    $reopenVer = optional($ticket->reopened_at)->timestamp;
  } elseif (!empty($ticket->updated_at)) {
    $reopenVer = optional($ticket->updated_at)->timestamp;
  } else {
    $reopenVer = time();
  }
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<div id="jtTicketWork">
  <style>
    #jtTicketWork, #jtTicketWork *{ box-sizing:border-box; }

    #jtTicketWork{
      --jt-primary: #3b82f6;
      --jt-primary-hover: #2563eb;
      --jt-primary-soft: rgba(59, 130, 246, 0.08);
      --jt-primary-ring: rgba(59, 130, 246, 0.3);

      --jt-success: #10b981;
      --jt-success-hover: #059669;
      --jt-danger: #ef4444;
      --jt-warning: #f59e0b;

      --jt-bg: #f8fafc;
      --jt-surface: #ffffff;

      --jt-ink: #0f172a;
      --jt-muted: #64748b;
      --jt-line: #e2e8f0;

      --jt-shadow-sm: 0 1px 2px 0 rgba(15, 23, 42, 0.04);
      --jt-shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.05), 0 2px 4px -2px rgba(15, 23, 42, 0.03);
      --jt-shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
      --jt-shadow-modal: 0 25px 50px -12px rgba(15, 23, 42, 0.25);

      --jt-r-sm: 0.5rem; --jt-r-md: 0.75rem; --jt-r-lg: 1rem; --jt-r-xl: 1.25rem;
      --jt-tr: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);

      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      color: var(--jt-ink);
      font-weight: 400;

      padding: 1.5rem;
      position: relative;
      z-index: 0;
      isolation: isolate;
      animation: jtFadeIn .4s ease-out;
      background-color: transparent;
    }
    @keyframes jtFadeIn{ from{opacity:0; transform:translateY(10px)} to{opacity:1; transform:translateY(0)} }

    #jtTicketWork .jt-ico{ width:1.2rem; height:1.2rem; display:inline-flex; align-items:center; justify-content:center; }
    #jtTicketWork .jt-ico svg{ width:100%; height:100%; }

    #jtTicketWork .jt-muted{ color: var(--jt-muted); font-size:.875rem; font-weight:500; }

    #jtTicketWork .jt-topbar{
      padding: 1.25rem 1.75rem;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(226, 232, 240, 0.8);
      border-radius: var(--jt-r-xl);
      box-shadow: var(--jt-shadow-sm);
      position: sticky; top: 16px; z-index: 10;
      transition: var(--jt-tr);
    }
    #jtTicketWork .jt-topbar:hover { box-shadow: var(--jt-shadow-md); }
    #jtTicketWork .jt-topbarGrid{
      display:grid;
      grid-template-columns: minmax(0,1fr) auto;
      gap: 1.5rem;
      align-items:center;
    }
    @media (max-width: 992px){ #jtTicketWork .jt-topbarGrid{ grid-template-columns: 1fr; } }

    #jtTicketWork .jt-title{
      font-size: 1.5rem;
      font-weight: 700;
      letter-spacing: -0.025em;
      margin: 0 0 .5rem 0;
      line-height: 1.2;
      color: var(--jt-ink);
    }

    #jtTicketWork .jt-metaRow{ display:flex; flex-wrap:wrap; gap:.75rem 1.5rem; align-items:center; }
    #jtTicketWork .jt-pills{ margin-top: 1rem; display:flex; flex-wrap:wrap; gap:.5rem; }

    #jtTicketWork .jt-pill{
      padding: .3rem .8rem;
      border-radius: 9999px;
      font-size: .75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .05em;
      display:inline-flex; align-items:center; gap:.4rem;
      white-space: nowrap;
      border: 1px solid transparent;
      transition: var(--jt-tr);
    }
    #jtTicketWork .jt-pill.blue{ background: var(--jt-primary-soft); color: var(--jt-primary-hover); border-color: rgba(59, 130, 246, 0.1); }
    #jtTicketWork .jt-pill.green{ background: rgba(16, 185, 129, 0.1); color: #047857; border-color: rgba(16, 185, 129, 0.2); }
    #jtTicketWork .jt-pill.amber{ background: rgba(245, 158, 11, 0.1); color: #b45309; border-color: rgba(245, 158, 11, 0.2); }
    #jtTicketWork .jt-pill.red{ background: rgba(239, 68, 68, 0.1); color: #b91c1c; border-color: rgba(239, 68, 68, 0.2); }
    #jtTicketWork .jt-pill.slate{ background: #f8fafc; color: #475569; border: 1px solid var(--jt-line); }

    #jtTicketWork .jt-pillHigh{
      background: rgba(239, 68, 68, 0.08);
      color: #b91c1c;
      border-color: rgba(239, 68, 68, 0.3);
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.05);
      font-weight: 700;
    }
    #jtTicketWork .jt-prioTag{
      padding: 2px 6px;
      border-radius: 999px;
      font-size: 10px;
      font-weight: 800;
      letter-spacing: .05em;
      background: rgba(239, 68, 68, 0.15);
      color: #b91c1c;
    }

    #jtTicketWork .jt-actionsCol{ display:flex; flex-direction:column; gap:.75rem; align-items:flex-end; }
    @media (max-width: 992px){ #jtTicketWork .jt-actionsCol{ align-items:flex-start; } }
    #jtTicketWork .jt-actionsRow{ display:flex; gap:.75rem; flex-wrap:wrap; }

    #jtTicketWork .jt-btn{
      display:inline-flex; align-items:center; gap:.5rem;
      padding: .6rem 1.15rem;
      border-radius: var(--jt-r-sm);
      font-size: .875rem;
      font-weight: 600;
      text-decoration:none;
      transition: var(--jt-tr);
      cursor:pointer;
      border: 1px solid var(--jt-line);
      background: var(--jt-surface);
      color: var(--jt-ink);
      box-shadow: var(--jt-shadow-sm);
      white-space: nowrap;
    }
    #jtTicketWork .jt-btn:hover:not(:disabled){
      background: #f8fafc;
      transform: translateY(-1px);
      box-shadow: var(--jt-shadow-md);
      border-color: #cbd5e1;
    }
    #jtTicketWork .jt-btn:disabled{ opacity:.5; cursor:not-allowed; transform:none; box-shadow:none; }
    #jtTicketWork .jt-btn:focus-visible{ outline: none; box-shadow: 0 0 0 3px var(--jt-primary-ring); }

    #jtTicketWork .jt-btnPrimary{ background: var(--jt-primary); color:#fff; border-color: transparent; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.25); }
    #jtTicketWork .jt-btnPrimary:hover:not(:disabled){ background: var(--jt-primary-hover); color:#fff; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3); }

    #jtTicketWork .jt-btnSuccess{ background: var(--jt-success); color:#fff; border-color: transparent; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); }
    #jtTicketWork .jt-btnSuccess:hover:not(:disabled){ background: var(--jt-success-hover); color:#fff; }

    #jtTicketWork .jt-btnDanger{ background: #fff; color: var(--jt-danger); border-color: rgba(239, 68, 68, 0.3); }
    #jtTicketWork .jt-btnDanger:hover:not(:disabled){ background: #fef2f2; border-color: var(--jt-danger); }

    #jtTicketWork .jt-grid{
      margin-top: 1.5rem;
      display:grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
      gap: 1.5rem;
    }
    @media (max-width: 992px){ #jtTicketWork .jt-grid{ grid-template-columns: 1fr; } }

    #jtTicketWork .jt-card{
      background: var(--jt-surface);
      border: 1px solid var(--jt-line);
      border-radius: var(--jt-r-xl);
      box-shadow: var(--jt-shadow-sm);
      overflow:hidden;
      margin-bottom: 1.5rem;
      transition: var(--jt-tr);
    }
    #jtTicketWork .jt-card:hover{ box-shadow: var(--jt-shadow-md); }
    #jtTicketWork .jt-cardHd{
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--jt-line);
      background: #fff;
      display:flex; align-items:center; justify-content:space-between; gap:1rem;
    }
    #jtTicketWork .jt-cardT{
      font-weight: 600;
      font-size: 1.05rem;
      margin:0;
      display:flex; align-items:center; gap:.6rem;
      color: var(--jt-ink);
    }
    #jtTicketWork .jt-cardBd{ padding: 1.5rem; }

    #jtTicketWork .jt-tl{
      position:relative;
      padding-left:.5rem;
      display:flex;
      flex-direction:column;
      gap: 1.25rem;
    }
    #jtTicketWork .jt-tl::before{
      content:'';
      position:absolute;
      left: 19px;
      top: 12px;
      bottom: 24px;
      width: 2px;
      background: linear-gradient(
        to bottom,
        var(--jt-primary) 0%,
        var(--jt-primary) var(--jt-line-fill, 0%),
        var(--jt-line) var(--jt-line-fill, 0%),
        var(--jt-line) 100%
      );
      z-index:0;
      border-radius: 999px;
      transition: background 0.5s ease;
    }

    #jtTicketWork .jt-tlStep{
      position:relative; z-index:1;
      display:grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 1rem;
      align-items:center;
      padding: 0.5rem 0;
    }
    @media (max-width: 576px){ #jtTicketWork .jt-tlStep{ grid-template-columns: 1fr; } }

    #jtTicketWork .jt-stepInfo{ display:flex; align-items:center; gap: 1.25rem; min-width:0; }

    #jtTicketWork .jt-dot{
      width: 28px; height:28px;
      border-radius: 50%;
      background:#fff;
      border: 2px solid var(--jt-line);
      flex: 0 0 auto;
      box-shadow: 0 0 0 4px var(--jt-surface);
      display:grid;
      place-items:center;
      transition: var(--jt-tr);
    }

    #jtTicketWork .jt-tlStep.is-done .jt-dot{
      border-color: var(--jt-success);
      background: var(--jt-success);
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }
    #jtTicketWork .jt-tlStep.is-done .jt-dot::after{
      content:'';
      width: 10px;
      height: 6px;
      border-left: 2px solid #fff;
      border-bottom: 2px solid #fff;
      transform: rotate(-45deg);
      margin-top:-2px;
    }

    #jtTicketWork .jt-tlStep.is-active .jt-dot{
      border-color: var(--jt-primary);
      background: var(--jt-primary);
      box-shadow: 0 0 0 6px var(--jt-primary-soft);
    }
    #jtTicketWork .jt-tlStep.is-active .jt-dot::after{
      content:''; width: 8px; height: 8px; background: #fff; border-radius: 50%;
    }

    #jtTicketWork .jt-stepName{
      font-weight: 500;
      font-size: .95rem;
      color: var(--jt-muted);
      transition: var(--jt-tr);
    }
    #jtTicketWork .jt-tlStep.is-active .jt-stepName{
      font-weight: 700;
      color: var(--jt-ink);
      font-size: 1rem;
    }
    #jtTicketWork .jt-tlStep.is-done .jt-stepName{
      color: var(--jt-ink);
      font-weight: 500;
    }

    #jtTicketWork .jt-stepSub{
      font-size:.7rem;
      color: var(--jt-primary);
      font-weight: 700;
      margin-top:.3rem;
      text-transform:uppercase;
      letter-spacing:.08em;
    }

    #jtTicketWork .jt-timer{
      text-align:center;
      padding: 2rem 1.5rem;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      border-radius: var(--jt-r-xl);
      color:#fff;
      border: 1px solid #334155;
      box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.15), inset 0 2px 4px rgba(255,255,255,0.05);
      position: relative;
      overflow: hidden;
    }
    #jtTicketWork .jt-timer::before {
      content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    }
    #jtTicketWork .jt-timerLbl{
      font-size:.75rem;
      font-weight: 600;
      text-transform:uppercase;
      letter-spacing:.15em;
      color:#94a3b8;
    }
    #jtTicketWork .jt-timerVal{
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 3.5rem;
      font-weight: 300;
      letter-spacing: -0.03em;
      margin: 1rem 0 1.5rem;
      color: #38bdf8;
      text-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
      transition: text-shadow 0.3s ease, color 0.3s ease;
    }
    #jtTicketWork .jt-timerBtns{ display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; }
    #jtTicketWork .jt-btnTimer{
      background: rgba(255,255,255,0.08);
      color:#f8fafc;
      border: 1px solid rgba(255,255,255,0.1);
      font-weight: 500;
      backdrop-filter: blur(4px);
    }
    #jtTicketWork .jt-btnTimer:hover:not(:disabled){ background: rgba(255,255,255,0.15); transform: translateY(-1px); }
    #jtTicketWork .jt-btnIcon{ padding: .65rem; border-radius: var(--jt-r-md); }

    #jtTicketWork textarea.jt-input{
      width:100%;
      border: 1px solid var(--jt-line);
      border-radius: var(--jt-r-md);
      padding: 1rem 1.25rem;
      font-family: inherit;
      font-size: .95rem;
      resize: vertical;
      min-height: 110px;
      background: var(--jt-surface);
      transition: var(--jt-tr);
      font-weight: 400;
      color: var(--jt-ink);
      line-height: 1.5;
    }
    #jtTicketWork textarea.jt-input::placeholder{ color: #94a3b8; }
    #jtTicketWork textarea.jt-input:focus{
      outline:none;
      border-color: var(--jt-primary);
      box-shadow: 0 0 0 4px var(--jt-primary-soft);
    }

    #jtTicketWork .jt-cmt{
      background: var(--jt-surface);
      border-left: 3px solid var(--jt-line);
      padding: 0 0 0 1.25rem;
      margin-bottom: 1.5rem;
      position: relative;
    }
    #jtTicketWork .jt-cmtHd{
      display:flex;
      justify-content:space-between;
      gap: 1rem;
      align-items:center;
      margin-bottom: .5rem;
    }
    #jtTicketWork .jt-cmtUser{ font-weight: 600; font-size:.95rem; display:flex; align-items:center; gap:.6rem; color: var(--jt-ink); }
    #jtTicketWork .jt-cmtUser::before{ content:''; width:26px; height:26px; background: linear-gradient(135deg, var(--jt-primary-soft), rgba(59,130,246,0.15)); border-radius:50%; display:block; border: 1px solid rgba(59,130,246,0.1); }
    #jtTicketWork .jt-cmtTime{ color: var(--jt-muted); font-size:.8rem; font-weight: 500; }
    #jtTicketWork .jt-cmtBody{ font-size:.95rem; white-space:pre-wrap; color: #334155; line-height: 1.6; background: #f8fafc; padding: 1rem; border-radius: var(--jt-r-md); border: 1px solid var(--jt-line); margin-top: 0.5rem; }

    #jtTicketWork .jt-dl{ display:flex; flex-direction:column; }
    #jtTicketWork .jt-dlItem{
      display:grid;
      grid-template-columns: 140px minmax(0,1fr);
      gap: 1.5rem;
      padding: 1.25rem 0;
      border-bottom: 1px solid var(--jt-line);
      align-items: start;
    }
    #jtTicketWork .jt-dlItem:last-child{ border-bottom:none; padding-bottom:0; }
    @media (max-width: 576px){ #jtTicketWork .jt-dlItem{ grid-template-columns: 1fr; gap:.4rem; padding: 1rem 0; } }
    #jtTicketWork .jt-lbl{
      color: var(--jt-muted);
      font-size:.8rem;
      font-weight: 600;
      text-transform:uppercase;
      letter-spacing:.08em;
      margin-top: 0.1rem;
    }
    #jtTicketWork .jt-val{ font-weight: 400; font-size:.95rem; min-width:0; color: var(--jt-ink); line-height: 1.6; }

    #jtTicketWork .jt-drop{
      border: 2px dashed #cbd5e1;
      border-radius: var(--jt-r-xl);
      padding: 2.5rem 1.5rem;
      text-align:center;
      background: #f8fafc;
      transition: var(--jt-tr);
    }
    #jtTicketWork .jt-drop:hover{ border-color: var(--jt-primary); background: var(--jt-primary-soft); }
    #jtTicketWork .jt-fileRow{ display:flex; gap:1rem; margin-top: 1.5rem; align-items:center; justify-content:center; flex-wrap:wrap; }
    #jtTicketWork .jt-fileName{
      max-width: 260px;
      padding: .65rem 1.25rem;
      background:#fff;
      border: 1px solid var(--jt-line);
      border-radius: var(--jt-r-md);
      font-size:.85rem;
      color: var(--jt-muted);
      overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
      font-weight: 500;
      box-shadow: var(--jt-shadow-sm);
    }

    #jtTicketWork .jt-doc{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap: 1rem;
      padding: 1.15rem;
      border: 1px solid var(--jt-line);
      border-radius: var(--jt-r-lg);
      margin-bottom: 1rem;
      background:#fff;
      transition: var(--jt-tr);
      box-shadow: var(--jt-shadow-sm);
    }
    #jtTicketWork .jt-doc:hover{ border-color: #cbd5e1; box-shadow: var(--jt-shadow-md); transform: translateY(-2px); }
    #jtTicketWork .jt-docL{ display:flex; align-items:center; gap: 1.25rem; min-width:0; }
    #jtTicketWork .jt-docIcon{
      width:48px; height:48px;
      border-radius: var(--jt-r-md);
      background: var(--jt-primary-soft);
      color: var(--jt-primary);
      display:grid; place-items:center;
      flex: 0 0 auto;
    }
    #jtTicketWork .jt-docMeta{ min-width:0; }
    #jtTicketWork .jt-docName{ font-weight: 600; font-size:.95rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width: 280px; color: var(--jt-ink); }
    #jtTicketWork .jt-docDate{ font-size:.8rem; color: var(--jt-muted); margin-top:.25rem; font-weight: 400; }
    #jtTicketWork .jt-docBtns{ display:flex; gap:.5rem; flex-wrap:wrap; }

    #jtTicketWork .jt-modalBack{
      position: fixed; inset:0;
      background: rgba(15, 23, 42, 0.5);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      display:none;
      place-items:center;
      z-index: 9999;
      padding: 1.5rem;
    }
    #jtTicketWork .jt-modalBack.is-open{ display:grid; }
    #jtTicketWork .jt-modal{
      background:#fff;
      width:100%;
      max-width: 860px;
      border-radius: 1.5rem;
      box-shadow: var(--jt-shadow-modal);
      overflow:hidden;
      animation: jtZoom .3s cubic-bezier(0.16,1,0.3,1) forwards;
      border: 1px solid rgba(255,255,255,0.2);
    }
    @keyframes jtZoom{ from{ opacity:0; transform: scale(.97) translateY(10px);} to{ opacity:1; transform: scale(1) translateY(0);} }

    #jtTicketWork .jt-modalHd{
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--jt-line);
      display:flex;
      justify-content:space-between;
      align-items:center;
      background: #fff;
    }
    #jtTicketWork .jt-modalBd{ padding: 1.5rem; }

    #jtTicketWork .jt-preview{
      width:100%;
      height: 65vh;
      max-height: 700px;
      background: #0f172a;
      border-radius: var(--jt-r-lg);
      overflow:hidden;
      display:grid;
      place-items:center;
      box-shadow: inset 0 2px 10px rgba(0,0,0,.5);
    }
    #jtTicketWork .jt-preview img,
    #jtTicketWork .jt-preview video,
    #jtTicketWork .jt-preview iframe{
      width:100%;
      height:100%;
      object-fit: contain;
      border: none;
    }

    /* ✅ Banner reabierto */
    #jtTicketWork .jt-reopenBanner{
      margin: 12px 0 0;
      padding: 10px 12px;
      border-radius: 14px;
      border: 1px solid rgba(239,68,68,.22);
      background: rgba(239,68,68,.06);
      color: #991b1b;
      font-weight: 700;
      display:flex;
      gap:10px;
      align-items:flex-start;
    }
    #jtTicketWork .jt-reopenBanner small{
      display:block;
      font-weight: 600;
      color: rgba(153,27,27,.85);
      margin-top: 2px;
    }
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

          {{-- ✅ estado visible real (puede ser reabierto) --}}
          <span class="jt-pill {{ $pillStatusClass($ticket->status) }}">Estado: {{ $currentLabel }}</span>
        </div>

        {{-- ✅ aviso reabierto (y motivo si existe) --}}
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
                <button class="jt-btn jt-btnSuccess" type="submit" {{ (!$isAssignee || !$canComplete) ? 'disabled' : '' }}>
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

          @if(!$isAssignee && !$isFinal)
            <div class="jt-muted" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--jt-line); text-align: center;">
              Solo el asignado puede mover el progreso del ticket.
            </div>
          @endif

          @if($isAssignee && !$isFinal)
            <div class="jt-muted" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--jt-line); text-align: center;">
              Regla: no se puede completar si no pasa por todos los estados (Pendiente → Revisión → Progreso → Pruebas). Bloqueado es opcional.
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

          <div class="d-flex flex-column">
            @forelse($ticket->comments ?? [] as $c)
              <div class="jt-cmt">
                <div class="jt-cmtHd">
                  <div class="jt-cmtUser">{{ optional($c->user)->name ?: 'Usuario' }}</div>
                  <div class="jt-cmtTime">{{ optional($c->created_at)->diffForHumans() }}</div>
                </div>
                <div class="jt-cmtBody">{{ $c->body }}</div>
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
                    <div class="jt-docDate">{{ optional($d->created_at)->format('d M Y') }}</div>
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

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
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

  // ===== TIMER =====
  const isAssignee = @json($isAssignee);
  const isFinal    = @json($isFinal);

  // 👇 estado real + estado de flujo (reabierto => pendiente)
  const currentStatusReal = @json((string)$current);
  const currentStatusFlow = @json((string)$currentFlow);

  const reopenVer = @json((int)$reopenVer);
  const key = 'jt_timer_' + @json((string)$ticket->id);
  const verKey = 'jt_timer_ver_' + @json((string)$ticket->id);

  // ✅ Reset automático del timer cuando cambia la “versión” (reapertura / update)
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

    if(btnStart) btnStart.disabled = !isAssignee || isFinal || state.running;
    if(btnStop)  btnStop.disabled  = !isAssignee || isFinal || !state.running;

    if(state.running && el) {
      el.style.textShadow = '0 0 20px rgba(56, 189, 248, 0.4)';
      el.style.color = '#7dd3fc';
    } else if(el) {
      el.style.textShadow = '0 0 15px rgba(56, 189, 248, 0.2)';
      el.style.color = '#38bdf8';
    }
  }

  function startTimer(){
    if(!isAssignee || isFinal || state.running) return;
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

  btnStart?.addEventListener('click', startTimer);
  btnStop?.addEventListener('click', stopTimer);

  btnReset?.addEventListener('click', ()=>{
    if(!isAssignee || isFinal) return;
    if(confirm('¿Seguro que deseas reiniciar el tiempo?')){
      state = { running:false, startAt:null, elapsed:0, startedOnce:false };
      saveState();
      clearInterval(tInterval); tInterval = null;
      syncUI();
    }
  });

  // ✅ Auto-start solo cuando entra a PROGRESO (del flujo)
  if(isAssignee && !isFinal && currentStatusFlow === 'progreso' && !state.startedOnce){
    startTimer();
  }

  syncUI();
  if(state.running) tInterval = setInterval(syncUI, 1000);

  // Enviar tiempo al finalizar/cancelar
  const completeForm = document.getElementById('jtCompleteForm');
  const cancelForm   = document.getElementById('jtCancelForm');
  const elComplete   = document.getElementById('jtElapsedComplete');
  const elCancel     = document.getElementById('jtElapsedCancel');

  completeForm?.addEventListener('submit', function(){
    if(elComplete) elComplete.value = String(getElapsed());
    stopTimer();
  });

  cancelForm?.addEventListener('submit', function(){
    if(elCancel) elCancel.value = String(getElapsed());
    stopTimer();
  });

  // En cada cambio de estatus
  document.querySelectorAll('form.jtMoveForm').forEach(function(f){
    f.addEventListener('submit', function(){
      const hid = f.querySelector('.jtElapsedOnMove');
      if(hid) hid.value = String(getElapsed());

      const target = f.querySelector('input[name="status"]')?.value || '';
      if(target === 'progreso' && !state.startedOnce){
        startTimer();
      }
    });
  });

  // ===== MODALS =====
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

  const cancelModalObj = modalApi('jtCancelModal', ['jtCancelBack','jtCancelClose']);
  document.getElementById('jtOpenCancel')?.addEventListener('click', () => {
    cancelModalObj.open();
    setTimeout(() => document.querySelector('#jtCancelForm textarea')?.focus(), 100);
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
        frame.innerHTML = '<div style="color:#94a3b8; font-weight:500; text-align:center; padding:2rem;">Sin previsualización en línea.<br>Descarga el archivo.</div>';
      } else if(kind === 'image') {
        frame.innerHTML = `<img src="${url}" alt="${name || ''}">`;
      } else if(kind === 'video') {
        frame.innerHTML = `<video src="${url}" controls playsinline></video>`;
      } else if(kind === 'audio') {
        frame.innerHTML = `<audio src="${url}" controls style="width:88%;"></audio>`;
      } else if(kind === 'pdf') {
        frame.innerHTML = `<iframe src="${url}#toolbar=0&navpanes=0&scrollbar=0"></iframe>`;
      } else {
        frame.innerHTML = '<div style="color:#94a3b8; font-weight:500; text-align:center; padding:2rem;">Formato sin previsualización.<br>Por favor descarga el archivo para verlo.</div>';
      }

      pvModalObj.open();
    });
  });
});
</script>
@endsection