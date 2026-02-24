{{-- resources/views/tickets/work.blade.php --}}
@extends('layouts.app')
@section('title', 'Trabajo | '.$ticket->folio)

@section('content')
@php
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Str;

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
  ];

  $strict = ['pendiente','revision','progreso','pruebas','completado'];
  $strictIndex = array_flip($strict);

  $current = $ticket->status ?: 'pendiente';

  $canComplete = ($current === 'pruebas');
  $canCancel = !in_array($current, ['completado','cancelado'], true);
  $isAssignee = auth()->check() && (string)auth()->id() === (string)($ticket->assignee_id ?? '');

  $sla = $ticket->sla_signal ?? 'neutral';
  $slaClass = $sla==='overdue' ? 'red' : ($sla==='due_soon' ? 'amber' : ($sla==='ok' ? 'green' : 'slate'));

  $pillStatusClass = function($st){
    return match($st){
      'completado' => 'green',
      'cancelado'  => 'red',
      'bloqueado'  => 'amber',
      'revision'   => 'amber',
      'pruebas'    => 'amber',
      default      => 'blue',
    };
  };

  $nextStrict = null;
  if (isset($strictIndex[$current]) && $strictIndex[$current] < count($strict)-1) {
    $nextStrict = $strict[$strictIndex[$current] + 1];
  } elseif ($current === 'bloqueado') {
    $nextStrict = 'progreso';
  }

  $canMoveTo = function(string $target) use ($current, $nextStrict){
    if (in_array($current, ['completado','cancelado'], true)) return false;
    if ($target === $current) return false;
    if ($target === 'cancelado') return true;
    if ($target === 'completado') return false;

    if ($target === 'bloqueado') return true;
    if ($current === 'bloqueado') return in_array($target, ['progreso','pruebas'], true);

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

  // Icons
  $I = function($name){
    $icons = [
      'arrowLeft' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>',
      'list'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01"/><path d="M3 12h.01"/><path d="M3 18h.01"/></svg>',
      'check'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
      'x'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>',
      'flag'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22V4"/><path d="M4 4h14l-2 5 2 5H4"/></svg>',
      'shield'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>',
      'clock'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>',
      'play'      => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>',
      'pause'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>',
      'trash'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>',
      'chat'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
      'paperclip' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-8.49 8.49a5 5 0 0 1-7.07-7.07l8.49-8.49a3 3 0 0 1 4.24 4.24l-8.49 8.49a1 1 0 0 1-1.41-1.41l8.49-8.49"/></svg>',
      'eye'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>',
      'download'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>',
      'info'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>',
      'file'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>',
      'image'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 16l5-5 4 4 5-6 4 5"/></svg>',
      'pdf'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M7 15h3"/><path d="M7 18h4"/><path d="M14 18h3"/></svg>',
      'warn'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
    ];
    return $icons[$name] ?? $icons['info'];
  };

  $slaText = match($sla){
    'overdue'  => 'Vencido',
    'due_soon' => 'Por vencer',
    'ok'       => 'En tiempo',
    default    => 'Sin fecha',
  };
@endphp

<div class="container py-4" id="tkWork">
  <style>
    /* ==============================================================
       Premium Minimalist Design System
       ============================================================== */
    :root {
      --color-primary: #4f46e5;
      --color-primary-hover: #4338ca;
      --color-primary-light: #e0e7ff;
      --color-success: #10b981;
      --color-danger: #ef4444;
      --color-warning: #f59e0b;
      --color-bg: #f8fafc;
      --color-surface: #ffffff;
      --color-text-main: #0f172a;
      --color-text-muted: #64748b;
      --color-border: #e2e8f0;
      
      --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
      --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
      --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.05), 0 4px 6px -4px rgb(0 0 0 / 0.05);
      --shadow-modal: 0 25px 50px -12px rgb(0 0 0 / 0.25);
      
      --radius-sm: 0.5rem;
      --radius-md: 0.75rem;
      --radius-lg: 1rem;
      --radius-xl: 1.5rem;
      
      --transition-base: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    #tkWork {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      color: var(--color-text-main);
      animation: fadeInUp 0.5s ease-out forwards;
    }

    /* Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulseSoft {
      0% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.4); }
      70% { box-shadow: 0 0 0 8px rgba(79, 70, 229, 0); }
      100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
    }

    /* Utilities */
    .ico { width: 1.1rem; height: 1.1rem; display: inline-flex; align-items: center; justify-content: center; }
    .ico svg { width: 100%; height: 100%; }
    .text-muted-pro { color: var(--color-text-muted); font-size: 0.85rem; font-weight: 500; }
    
    /* Layout */
    .wrapBg {
      background: var(--color-bg);
      border-radius: var(--radius-xl);
      border: 1px solid var(--color-border);
      overflow: hidden;
    }
    .grid-layout {
      display: grid;
      grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
      gap: 1.5rem;
      padding: 1.5rem;
    }
    @media (max-width: 992px) { .grid-layout { grid-template-columns: 1fr; } }

    /* Topbar (Glassmorphism) */
    .topbar {
      padding: 1.5rem;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--color-border);
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1.5rem;
      flex-wrap: wrap;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    .hTitle { font-size: 1.35rem; font-weight: 700; color: var(--color-text-main); margin: 0 0 0.5rem 0; letter-spacing: -0.02em; }
    
    /* Pills */
    .pills-group { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; }
    .pill {
      padding: 0.25rem 0.75rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.025em;
      border: 1px solid transparent;
      white-space: nowrap;
    }
    .pill.blue { background: var(--color-primary-light); color: var(--color-primary-hover); }
    .pill.green { background: #d1fae5; color: #047857; }
    .pill.amber { background: #fef3c7; color: #b45309; }
    .pill.red { background: #fee2e2; color: #b91c1c; }
    .pill.slate { background: #f1f5f9; color: #475569; border-color: var(--color-border); }

    /* Buttons */
    .btn-pro {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: var(--radius-sm);
      font-size: 0.875rem;
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition-base);
      cursor: pointer;
      border: 1px solid var(--color-border);
      background: var(--color-surface);
      color: var(--color-text-main);
      box-shadow: var(--shadow-sm);
    }
    .btn-pro:hover:not(:disabled) { transform: translateY(-1px); box-shadow: var(--shadow-md); }
    .btn-pro:active:not(:disabled) { transform: translateY(0); box-shadow: none; }
    .btn-pro:disabled { opacity: 0.5; cursor: not-allowed; }
    
    .btn-primary { background: var(--color-primary); color: white; border-color: var(--color-primary-hover); box-shadow: 0 1px 2px rgba(79, 70, 229, 0.3); }
    .btn-primary:hover:not(:disabled) { background: var(--color-primary-hover); }
    .btn-success { background: var(--color-success); color: white; border-color: #059669; }
    .btn-danger { background: var(--color-danger); color: white; border-color: #dc2626; }

    /* Cards */
    .card-pro {
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-sm);
      overflow: hidden;
      margin-bottom: 1.5rem;
      transition: var(--transition-base);
    }
    .card-pro:hover { box-shadow: var(--shadow-md); }
    .card-header {
      padding: 1rem 1.25rem;
      border-bottom: 1px solid var(--color-border);
      background: rgba(248, 250, 252, 0.5);
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .card-title { font-weight: 600; font-size: 0.95rem; margin: 0; display: flex; align-items: center; gap: 0.5rem; }
    .card-body { padding: 1.25rem; }

    /* Timeline Horizontal/Responsive */
    .timeline-container { display: flex; flex-direction: column; gap: 0.75rem; }
    .timeline-step {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 1rem;
      border-radius: var(--radius-md);
      background: var(--color-bg);
      border: 1px solid transparent;
      transition: var(--transition-base);
    }
    .timeline-step.is-active {
      background: var(--color-surface);
      border-color: var(--color-primary);
      box-shadow: var(--shadow-md);
    }
    .step-info { display: flex; align-items: center; gap: 0.75rem; }
    .step-dot {
      width: 12px; height: 12px;
      border-radius: 50%;
      background: var(--color-border);
      transition: var(--transition-base);
    }
    .timeline-step.is-active .step-dot {
      background: var(--color-primary);
      animation: pulseSoft 2s infinite;
    }
    .step-name { font-weight: 600; font-size: 0.9rem; }

    /* Timer Widget */
    .timer-widget {
      text-align: center;
      padding: 1.5rem;
      background: #0f172a; /* Slate 900 */
      border-radius: var(--radius-md);
      color: white;
    }
    .timer-val {
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 2.5rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      margin-bottom: 1rem;
      color: #38bdf8; /* Light blue */
      text-shadow: 0 0 15px rgba(56, 189, 248, 0.2);
    }
    .timer-actions { display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap; }

    /* Upload Area */
    .upload-zone {
      border: 2px dashed var(--color-border);
      border-radius: var(--radius-md);
      padding: 1.5rem;
      text-align: center;
      transition: var(--transition-base);
      background: var(--color-bg);
    }
    .upload-zone:hover { border-color: var(--color-primary-light); background: rgba(79, 70, 229, 0.02); }
    .file-input-wrapper { display: flex; align-items: center; gap: 1rem; margin-top: 1rem; }
    .file-name-display {
      flex: 1;
      padding: 0.5rem 0.75rem;
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      color: var(--color-text-muted);
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    /* Document Rows */
    .doc-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem;
      border: 1px solid var(--color-border);
      border-radius: var(--radius-md);
      margin-bottom: 0.5rem;
      transition: var(--transition-base);
    }
    .doc-row:hover { background: var(--color-bg); }
    .doc-ico-box {
      width: 40px; height: 40px;
      border-radius: var(--radius-sm);
      background: var(--color-primary-light);
      color: var(--color-primary);
      display: grid; place-items: center;
    }
    
    /* Comments */
    textarea.pro-input {
      width: 100%;
      border: 1px solid var(--color-border);
      border-radius: var(--radius-md);
      padding: 0.75rem 1rem;
      font-family: inherit;
      font-size: 0.9rem;
      transition: var(--transition-base);
      resize: vertical;
      min-height: 80px;
    }
    textarea.pro-input:focus {
      outline: none;
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
    }
    .comment-bubble {
      background: var(--color-bg);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-md);
      padding: 1rem;
      margin-bottom: 0.75rem;
    }

    /* Data List */
    .data-list { display: flex; flex-direction: column; gap: 0.75rem; }
    .data-item {
      display: grid;
      grid-template-columns: 120px 1fr;
      gap: 1rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--color-border);
    }
    .data-item:last-child { border-bottom: none; padding-bottom: 0; }
    .data-label { color: var(--color-text-muted); font-size: 0.85rem; font-weight: 600; }
    .data-value { font-weight: 500; font-size: 0.9rem; }

    /* Modals (Glassmorphism + SlideUp) */
    .modal-backdrop {
      position: fixed; inset: 0;
      background: rgba(15, 23, 42, 0.4);
      backdrop-filter: blur(4px);
      display: none; place-items: center;
      z-index: 9999; padding: 1rem;
    }
    .modal-backdrop.is-open { display: grid; }
    .modal-content {
      background: var(--color-surface);
      width: 100%; max-width: 800px;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-modal);
      overflow: hidden;
      animation: fadeInUp 0.3s ease-out forwards;
    }
    .modal-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--color-border);
      display: flex; justify-content: space-between; align-items: center;
    }
    .modal-body { padding: 1.5rem; }
    .preview-frame {
      width: 100%; height: 60vh; max-height: 600px;
      background: #0f172a; border-radius: var(--radius-md);
      overflow: hidden; display: grid; place-items: center;
    }
    .preview-frame img, .preview-frame video, .preview-frame iframe {
      width: 100%; height: 100%; object-fit: contain; border: none;
    }
  </style>

  {{-- Alerts --}}
  @if(session('ok')) <div class="alert alert-success" style="border-radius: var(--radius-md);">{{ session('ok') }}</div> @endif
  @if(session('err')) <div class="alert alert-danger" style="border-radius: var(--radius-md);">{{ session('err') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger" style="border-radius: var(--radius-md);">
      <strong>Revisa los siguientes errores:</strong>
      <ul class="mb-0 mt-1">@foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
    </div>
  @endif

  <div class="wrapBg">
    
    {{-- TOPBAR --}}
    <div class="topbar">
      <div>
        <h3 class="hTitle">{{ $ticket->folio }} <span style="opacity:0.4;font-weight:400;">/</span> {{ $ticket->title }}</h3>
        <div class="text-muted-pro d-flex align-items-center gap-3 flex-wrap">
          <span class="d-flex align-items-center gap-1"><span class="ico">{!! $I('shield') !!}</span> {{ optional($ticket->assignee)->name ?: 'Sin asignar' }}</span>
          <span class="d-flex align-items-center gap-1"><span class="ico">{!! $I('clock') !!}</span> Vence: {{ $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : 'N/A' }}</span>
        </div>
        
        <div class="pills-group">
          <span class="pill blue">{{ $priorities[$ticket->priority] ?? $ticket->priority }}</span>
          <span class="pill slate">{{ $areas[$ticket->area] ?? ($ticket->area ?: 'Sin área') }}</span>
          <span class="pill {{ $slaClass }}">{{ $slaText }}</span>
          <span class="pill {{ $pillStatusClass($ticket->status) }}">Estado: {{ $statuses[$ticket->status] ?? $ticket->status }}</span>
        </div>
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <a class="btn-pro" href="{{ route('tickets.show',$ticket) }}"><span class="ico">{!! $I('arrowLeft') !!}</span> Detalle</a>
        
        <form method="POST" action="{{ route('tickets.complete',$ticket) }}" class="m-0">
          @csrf
          <button class="btn-pro btn-success" type="submit" {{ (!$isAssignee || !$canComplete) ? 'disabled' : '' }} onclick="return confirm('¿Marcar como completado?');">
            <span class="ico">{!! $I('check') !!}</span> Finalizar
          </button>
        </form>
        
        <button class="btn-pro btn-danger" type="button" id="btnOpenCancel" {{ (!$isAssignee || !$canCancel) ? 'disabled' : '' }}>
          <span class="ico">{!! $I('x') !!}</span> Cancelar
        </button>
      </div>
    </div>

    {{-- GRID CONTENT --}}
    <div class="grid-layout">
      
      {{-- COLUMNA IZQUIERDA --}}
      <div class="d-flex flex-column">
        
        {{-- WORKFLOW --}}
        <div class="card-pro">
          <div class="card-header">
            <h4 class="card-title"><span class="ico" style="color:var(--color-primary);">{!! $I('flag') !!}</span> Progreso del Ticket</h4>
          </div>
          <div class="card-body">
            <div class="timeline-container">
              @foreach($steps as $key)
                @php $w = $workflow[$key]; @endphp
                <div class="timeline-step {{ $ticket->status === $key ? 'is-active' : '' }}">
                  <div class="step-info">
                    <div class="step-dot"></div>
                    <div>
                      <div class="step-name">{{ $w['label'] }}</div>
                      @if($ticket->status === $key) <span style="font-size:0.75rem; color:var(--color-primary); font-weight:600;">Estado Actual</span> @endif
                    </div>
                  </div>
                  
                  <form method="POST" action="{{ route('tickets.update',$ticket) }}" class="m-0">
                    @csrf @method('PUT')
                    <input type="hidden" name="status" value="{{ $key }}">
                    <button class="btn-pro" style="padding: 0.25rem 0.75rem; font-size: 0.75rem;" type="submit" {{ (!$isAssignee || !$canMoveTo($key)) ? 'disabled' : '' }}>
                      Mover
                    </button>
                  </form>
                </div>
              @endforeach
            </div>
            @if(!$isAssignee)
              <div class="mt-3 text-muted-pro text-center">Solo el asignado puede mover los estados.</div>
            @endif
          </div>
        </div>

        {{-- COMMENTS --}}
        <div class="card-pro">
          <div class="card-header">
            <h4 class="card-title"><span class="ico" style="color:var(--color-primary);">{!! $I('chat') !!}</span> Discusión y Notas</h4>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('tickets.comments.store',$ticket) }}" class="mb-4">
              @csrf
              <textarea name="body" class="pro-input" placeholder="Escribe una actualización o nota interna..."></textarea>
              <div class="d-flex justify-content-end mt-2">
                <button class="btn-pro btn-primary" type="submit">Publicar Nota</button>
              </div>
            </form>

            <div class="d-flex flex-column">
              @forelse($ticket->comments ?? [] as $c)
                <div class="comment-bubble">
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span style="font-weight:600; font-size:0.9rem;">{{ optional($c->user)->name ?: 'Usuario' }}</span>
                    <span class="text-muted-pro">{{ optional($c->created_at)->diffForHumans() }}</span>
                  </div>
                  <div style="font-size:0.9rem; white-space:pre-wrap; color:var(--color-text-main);">{{ $c->body }}</div>
                </div>
              @empty
                <div class="text-center text-muted-pro py-3">No hay comentarios aún.</div>
              @endforelse
            </div>
          </div>
        </div>

      </div>

      {{-- COLUMNA DERECHA --}}
      <div class="d-flex flex-column">
        
        {{-- TIMER --}}
        <div class="card-pro">
          <div class="timer-widget">
            <div style="font-size:0.8rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:0.5rem; opacity:0.8;">Tiempo Activo</div>
            <div class="timer-val" id="tkTimer">00:00:00</div>
            <div class="timer-actions">
              <button class="btn-pro" style="background:#1e293b; color:white; border:none;" type="button" id="btnStart"><span class="ico">{!! $I('play') !!}</span> Iniciar</button>
              <button class="btn-pro" style="background:#1e293b; color:white; border:none;" type="button" id="btnStop" disabled><span class="ico">{!! $I('pause') !!}</span> Pausar</button>
              <button class="btn-pro" style="background:transparent; color:#ef4444; border:1px solid rgba(239,68,68,0.3);" type="button" id="btnReset" title="Reiniciar"><span class="ico">{!! $I('trash') !!}</span></button>
            </div>
          </div>
        </div>

        {{-- INFO RESUMEN --}}
        <div class="card-pro">
          <div class="card-header">
            <h4 class="card-title"><span class="ico" style="color:var(--color-primary);">{!! $I('info') !!}</span> Detalles</h4>
          </div>
          <div class="card-body">
            <div class="data-list">
              <div class="data-item">
                <span class="data-label">Prioridad</span>
                <span class="data-value">{{ $priorities[$ticket->priority] ?? $ticket->priority }}</span>
              </div>
              <div class="data-item">
                <span class="data-label">Área</span>
                <span class="data-value">{{ $areas[$ticket->area] ?? ($ticket->area ?: '—') }}</span>
              </div>
              <div class="data-item">
                <span class="data-label">Descripción</span>
                <span class="data-value" style="font-size:0.85rem;">{{ Str::limit($ticket->description ?: 'Sin descripción', 100) }}</span>
              </div>
            </div>
          </div>
        </div>

        {{-- ATTACHMENTS --}}
        <div class="card-pro">
          <div class="card-header">
            <h4 class="card-title"><span class="ico" style="color:var(--color-primary);">{!! $I('paperclip') !!}</span> Archivos Adjuntos</h4>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data" id="tkUploadForm">
              @csrf
              <div class="upload-zone">
                <div class="text-muted-pro"><span class="ico">{!! $I('download') !!}</span> Arrastra un archivo o selecciónalo</div>
                <div class="file-input-wrapper">
                  <label class="btn-pro m-0" for="tkFile" style="background:white; cursor:pointer;" {{ !$isAssignee ? 'style=pointer-events:none;opacity:0.5;' : '' }}>Explorar</label>
                  <div class="file-name-display" id="tkFileName">Ningún archivo...</div>
                  <input type="file" id="tkFile" name="file" class="d-none" {{ !$isAssignee ? 'disabled' : '' }} style="display:none;">
                </div>
                <button class="btn-pro btn-primary w-100 mt-3" type="submit" {{ !$isAssignee ? 'disabled' : '' }}>Subir Archivo</button>
              </div>
            </form>

            <div class="mt-4">
              @forelse($ticket->documents ?? [] as $d)
                @php
                  $url  = $docUrl($d);
                  $kind = $docKind($d);
                  $docIcon = match($kind){ 'image'=>'image', 'pdf'=>'pdf', default=>'file' };
                @endphp
                <div class="doc-row">
                  <div class="d-flex align-items-center gap-3" style="min-width:0;">
                    <div class="doc-ico-box"><span class="ico">{!! $I($docIcon) !!}</span></div>
                    <div style="min-width:0;">
                      <div style="font-weight:600; font-size:0.85rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:150px;">{{ $d->name }}</div>
                      <div style="font-size:0.7rem; color:var(--color-text-muted);">{{ optional($d->created_at)->format('d/m/Y') }}</div>
                    </div>
                  </div>
                  <div class="d-flex gap-1">
                    <button type="button" class="btn-pro" style="padding:0.35rem 0.5rem;" data-preview="1" data-kind="{{ $kind }}" data-name="{{ e($d->name) }}" data-url="{{ $url ? e($url) : '' }}" data-download="{{ e(route('tickets.documents.download',[$ticket,$d])) }}" title="Ver"><span class="ico">{!! $I('eye') !!}</span></button>
                    <a class="btn-pro" style="padding:0.35rem 0.5rem;" href="{{ route('tickets.documents.download',[$ticket,$d]) }}" title="Descargar"><span class="ico">{!! $I('download') !!}</span></a>
                  </div>
                </div>
              @empty
                <div class="text-center text-muted-pro">Aún no hay adjuntos.</div>
              @endforelse
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- MODAL PREVIEW --}}
  <div class="modal-backdrop" id="pvModal">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="card-title m-0"><span class="ico">{!! $I('eye') !!}</span> <span id="pvTitle">Vista previa</span></h4>
        <button class="btn-pro" type="button" id="pvClose" style="border:none; background:transparent;"><span class="ico">{!! $I('x') !!}</span></button>
      </div>
      <div class="modal-body">
        <div class="preview-frame" id="pvFrame"><div style="color:#64748b; font-weight:600;">Cargando...</div></div>
        <div class="d-flex justify-content-end mt-3">
          <a class="btn-pro btn-primary" id="pvDownload" href="#" target="_blank"><span class="ico">{!! $I('download') !!}</span> Descargar Archivo</a>
        </div>
      </div>
    </div>
  </div>

  {{-- MODAL CANCELAR --}}
  <div class="modal-backdrop" id="cancelModal">
    <div class="modal-content" style="max-width:500px;">
      <div class="modal-header">
        <h4 class="card-title m-0 text-danger"><span class="ico">{!! $I('warn') !!}</span> Cancelar Ticket</h4>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('tickets.cancel',$ticket) }}" id="cancelForm">
          @csrf
          <label class="data-label mb-2 d-block">Motivo de cancelación (Requerido)</label>
          <textarea name="reason" class="pro-input mb-3" required placeholder="Ej. Duplicado, error de solicitud...">{{ old('reason') }}</textarea>
          <div class="d-flex gap-2 justify-content-end">
            <button class="btn-pro" type="button" id="cancelBack">Volver</button>
            <button class="btn-pro btn-danger" type="submit" onclick="return confirm('¿Confirmar cancelación definitiva?');">Confirmar Cancelación</button>
          </div>
        </form>
      </div>
    </div>
  </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // ===== UPLOAD UI =====
  const file = document.getElementById('tkFile');
  const nameEl = document.getElementById('tkFileName');
  const uploadZone = document.querySelector('.upload-zone');

  function updateFileName(){
    if(!file || !nameEl) return;
    nameEl.textContent = (file.files && file.files[0]) ? file.files[0].name : 'Ningún archivo...';
  }
  file?.addEventListener('change', updateFileName);

  if(uploadZone && file && !file.disabled){
    ['dragover', 'dragenter'].forEach(evt => {
      uploadZone.addEventListener(evt, e => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--color-primary)';
        uploadZone.style.background = 'var(--color-primary-light)';
      });
    });
    ['dragleave', 'drop'].forEach(evt => {
      uploadZone.addEventListener(evt, e => {
        e.preventDefault();
        uploadZone.style.borderColor = 'var(--color-border)';
        uploadZone.style.background = 'var(--color-bg)';
      });
    });
    uploadZone.addEventListener('drop', e => {
      if(e.dataTransfer?.files?.length){
        file.files = e.dataTransfer.files;
        updateFileName();
      }
    });
  }

  // ===== TIMER (UI local) =====
  const isAssignee = @json($isAssignee);
  const key = 'tk_timer_' + @json((string)$ticket->id);
  const el = document.getElementById('tkTimer');
  const btnStart = document.getElementById('btnStart');
  const btnStop  = document.getElementById('btnStop');
  const btnReset = document.getElementById('btnReset');

  let state = JSON.parse(localStorage.getItem(key)) || { running:false, startAt:null, elapsed:0 };
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
    if(btnStart) btnStart.disabled = !isAssignee || state.running;
    if(btnStop)  btnStop.disabled  = !isAssignee || !state.running;
    
    // Add glowing effect when running
    if(state.running && el) el.style.textShadow = '0 0 20px rgba(56, 189, 248, 0.6)';
    else if(el) el.style.textShadow = '0 0 15px rgba(56, 189, 248, 0.2)';
  }

  btnStart?.addEventListener('click', ()=>{
    if(!isAssignee || state.running) return;
    state.running = true; state.startAt = nowSecs(); saveState();
    if(!tInterval) tInterval = setInterval(syncUI, 1000);
    syncUI();
  });

  btnStop?.addEventListener('click', ()=>{
    if(!isAssignee || !state.running) return;
    state.elapsed = getElapsed(); state.running = false; state.startAt = null; saveState();
    clearInterval(tInterval); tInterval = null;
    syncUI();
  });

  btnReset?.addEventListener('click', ()=>{
    if(!isAssignee) return;
    if(confirm('¿Seguro que deseas reiniciar el tiempo?')){
      state = { running:false, startAt:null, elapsed:0 }; saveState();
      clearInterval(tInterval); tInterval = null; syncUI();
    }
  });

  syncUI();
  if(state.running) tInterval = setInterval(syncUI, 1000);

  // ===== MODALS =====
  function setupModal(modalId, openBtnsSelector, closeBtnsIds){
    const modal = document.getElementById(modalId);
    if(!modal) return;
    
    const openModal = () => { modal.classList.add('is-open'); document.body.style.overflow = 'hidden'; };
    const closeModal = () => { modal.classList.remove('is-open'); document.body.style.overflow = ''; };

    if(openBtnsSelector){
      document.querySelectorAll(openBtnsSelector).forEach(btn => {
        btn.addEventListener('click', openModal);
      });
    }
    
    closeBtnsIds.forEach(id => {
      document.getElementById(id)?.addEventListener('click', closeModal);
    });
    
    modal.addEventListener('mousedown', e => { if(e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if(e.key === 'Escape' && modal.classList.contains('is-open')) closeModal(); });
    
    return { open: openModal, close: closeModal, el: modal };
  }

  const cancelModalObj = setupModal('cancelModal', null, ['cancelClose', 'cancelBack']);
  document.getElementById('btnOpenCancel')?.addEventListener('click', () => {
    cancelModalObj.open();
    setTimeout(() => document.querySelector('#cancelForm textarea')?.focus(), 100);
  });

  const pvModalObj = setupModal('pvModal', null, ['pvClose']);
  document.querySelectorAll('[data-preview="1"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const { kind, name, url, download } = btn.dataset;
      document.getElementById('pvTitle').textContent = name || 'Vista previa';
      document.getElementById('pvDownload').href = download || '#';
      
      const frame = document.getElementById('pvFrame');
      frame.innerHTML = '';
      
      if(!url){
        frame.innerHTML = '<div style="color:#64748b; font-weight:600; text-align:center; padding:2rem;">Sin previsualización en línea.<br>Descarga el archivo.</div>';
      } else if(kind === 'image') {
        frame.innerHTML = `<img src="${url}" alt="${name}">`;
      } else if(kind === 'video') {
        frame.innerHTML = `<video src="${url}" controls playsinline></video>`;
      } else if(kind === 'audio') {
        frame.innerHTML = `<audio src="${url}" controls style="width:80%;"></audio>`;
      } else if(kind === 'pdf') {
        frame.innerHTML = `<iframe src="${url}"></iframe>`;
      } else {
        frame.innerHTML = '<div style="color:#64748b; font-weight:600; text-align:center; padding:2rem;">Formato sin previsualización.<br>Descarga el archivo.</div>';
      }
      pvModalObj.open();
    });
  });
});
</script>
@endsection