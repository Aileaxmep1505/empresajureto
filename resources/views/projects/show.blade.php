@extends('layouts.app')
@section('title', $project->name)

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg:           #f9fafb;
    --card:         #ffffff;
    --ink:          #111;
    --ink2:         #333;
    --muted:        #888;
    --line:         #ebebeb;
    --blue:         #007aff;
    --blue-soft:    #e6f0ff;
    --success:      #15803d;
    --success-soft: #e6ffe6;
    --danger:       #ef4444;
    --danger-soft:  #ffebeb;
    --warning:      #b45309;
    --warning-soft: #fef9c3;
  }

  body { font-family: 'Quicksand', sans-serif; background: var(--bg); color: var(--ink2); }

  .pjd-wrap { width: 100%; max-width: 100%; margin: 0; padding: 0; min-height: calc(100vh - 60px); display: flex; flex-direction: column; }

  /* ── Topbar del proyecto ── */
  .pjd-topbar {
    display: flex; align-items: center; gap: 18px; flex-wrap: wrap;
    padding: 12px 24px; background: #fff; border-bottom: 1px solid var(--line);
    position: sticky; top: 0; z-index: 10;
  }
  .pjd-back { color: var(--muted); text-decoration: none; font-size: 1.1rem; padding: 4px 8px; border-radius: 8px; transition: all .15s; }
  .pjd-back:hover { background: var(--bg); color: var(--blue); }
  .pjd-title { font-weight: 700; color: var(--ink); font-size: .98rem; display: flex; align-items: center; gap: 6px; }
  .pjd-status-pill { padding: 2px 8px; border-radius: 999px; font-size: .68rem; font-weight: 700; background: var(--blue-soft); color: var(--blue); margin-left: 6px; }
  .pjd-status-pill.is-ready { background: var(--success-soft); color: var(--success); }
  .pjd-status-pill.is-processing { background: var(--warning-soft); color: var(--warning); }
  .pjd-status-pill.is-error { background: var(--danger-soft); color: var(--danger); }

  /* ── Tabs ── */
  .pjd-tabs { display: flex; gap: 4px; flex: 1; flex-wrap: wrap; }
  .pjd-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 999px; border: none; background: transparent;
    font-family: inherit; font-size: .88rem; font-weight: 600; color: var(--ink2);
    cursor: pointer; transition: all .18s;
  }
  .pjd-tab:hover { background: var(--bg); color: var(--blue); }
  .pjd-tab.is-active { background: var(--blue); color: #fff; }
  .pjd-tab svg { width: 16px; height: 16px; }

  .pjd-view-doc { margin-left: auto; display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: .85rem; font-weight: 600; text-decoration: none; padding: 6px 10px; border-radius: 8px; }
  .pjd-view-doc:hover { background: var(--bg); color: var(--blue); }

  /* ── Layout 2 columnas ── */
  .pjd-body { flex: 1; display: grid; grid-template-columns: 1fr 1fr; gap: 0; min-height: 0; }
  @media (max-width: 1100px) { .pjd-body { grid-template-columns: 1fr; } }

  .pjd-left { display: flex; flex-direction: column; border-right: 1px solid var(--line); background: #fff; min-height: 0; }
  .pjd-right { display: flex; flex-direction: column; background: var(--bg); min-height: 0; overflow: auto; }

  /* ── CHAT (left) ── */
  .pjd-chat-head { padding: 10px 18px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: flex-end; }
  .pjd-chat-reset { background: var(--bg); border: 1px solid var(--line); padding: 5px 12px; border-radius: 999px; font-size: .8rem; font-weight: 600; color: var(--ink2); cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all .18s; }
  .pjd-chat-reset:hover { background: var(--blue-soft); color: var(--blue); border-color: var(--blue); }
  .pjd-chat-list { flex: 1; padding: 18px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; }
  .pjd-msg { max-width: 80%; }
  .pjd-msg.is-user { align-self: flex-end; }
  .pjd-msg.is-assistant { align-self: flex-start; display: flex; gap: 10px; }
  .pjd-msg-avatar { width: 28px; height: 28px; border-radius: 50%; background: var(--ink); color: #fff; display: grid; place-items: center; font-weight: 700; font-size: .8rem; flex-shrink: 0; }
  .pjd-msg-body { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 10px 14px; font-size: .92rem; line-height: 1.5; }
  .pjd-msg.is-user .pjd-msg-body { background: var(--bg); border-color: var(--line); }
  .pjd-msg-meta { font-size: .72rem; color: var(--muted); margin-bottom: 3px; font-weight: 700; }
  .pjd-msg.is-assistant .pjd-msg-meta { display: flex; align-items: center; gap: 6px; }

  .pjd-chat-input { padding: 14px 18px; border-top: 1px solid var(--line); background: #fff; display: flex; align-items: center; gap: 10px; }
  .pjd-chat-input input { flex: 1; border: 1px solid var(--line); border-radius: 999px; padding: 10px 16px; font-family: inherit; font-size: .92rem; outline: none; transition: border-color .2s; }
  .pjd-chat-input input:focus { border-color: var(--blue); }
  .pjd-chat-send { width: 38px; height: 38px; border-radius: 50%; background: var(--ink); color: #fff; border: none; cursor: pointer; display: grid; place-items: center; transition: transform .12s; }
  .pjd-chat-send:hover { transform: scale(1.05); }
  .pjd-chat-send:disabled { opacity: .5; cursor: not-allowed; }

  /* ── Panel derecho ── */
  .pjd-pane { padding: 18px 22px; display: none; }
  .pjd-pane.is-active { display: block; }
  .pjd-pane-title { font-size: 1.05rem; font-weight: 700; color: var(--ink); margin: 0 0 4px; padding-right: 36px; }
  .pjd-pane-actions { display: flex; gap: 8px; align-items: center; margin-bottom: 18px; }
  .pjd-pane-actions .pj-btn-mini { background: var(--card); border: 1px solid var(--line); border-radius: 8px; padding: 6px 10px; font-size: .78rem; cursor: pointer; font-weight: 600; color: var(--muted); }
  .pjd-pane-actions .pj-btn-mini:hover { color: var(--blue); border-color: var(--blue); background: var(--blue-soft); }

  /* Cards de Ficha / Resumen */
  .pjd-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; margin-bottom: 14px; overflow: hidden; }
  .pjd-card-head { padding: 12px 16px; border-bottom: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 10px; cursor: pointer; user-select: none; background: #fafbff; }
  .pjd-card-head h3 { margin: 0; font-size: .98rem; font-weight: 700; color: var(--ink); display: inline-flex; align-items: center; gap: 6px; }
  .pjd-card-head h3 .sparkle { color: var(--blue); }
  .pjd-card-chev { width: 22px; height: 22px; display: grid; place-items: center; color: var(--muted); transition: transform .2s; }
  .pjd-card.is-open .pjd-card-chev { transform: rotate(180deg); }
  .pjd-card-body { padding: 6px 16px 14px; display: none; }
  .pjd-card.is-open .pjd-card-body { display: block; }

  .pjd-field { padding: 10px 0; border-bottom: 1px solid var(--line); }
  .pjd-field:last-child { border-bottom: none; }
  .pjd-field-label { font-size: .78rem; font-weight: 700; color: var(--muted); background: var(--bg); padding: 4px 10px; border-radius: 6px; display: inline-block; margin-bottom: 6px; }
  .pjd-field-value { font-size: .92rem; color: var(--ink); font-weight: 600; line-height: 1.5; padding: 2px 4px; }

  .pjd-qa { padding: 10px 0; border-bottom: 1px solid var(--line); }
  .pjd-qa:last-child { border-bottom: none; }
  .pjd-qa-q { font-size: .85rem; font-weight: 700; color: var(--muted); background: var(--bg); padding: 6px 12px; border-radius: 8px; margin-bottom: 8px; display: inline-block; }
  .pjd-qa-a { font-size: .92rem; color: var(--ink); font-weight: 600; line-height: 1.5; padding: 0 6px; }

  /* Checklist */
  .pjd-check-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--line); border-radius: 12px; margin-bottom: 6px; background: var(--card); cursor: pointer; }
  .pjd-check-item input { width: 18px; height: 18px; cursor: pointer; }
  .pjd-check-item.is-done .pjd-check-label { text-decoration: line-through; color: var(--muted); }

  /* Borrador (textarea simple) */
  .pjd-draft-toolbar { background: #f5f7fb; border: 1px solid var(--line); border-radius: 12px 12px 0 0; padding: 8px; display: flex; gap: 4px; flex-wrap: wrap; }
  .pjd-draft-btn { background: transparent; border: none; padding: 6px 8px; border-radius: 6px; cursor: pointer; font-size: .85rem; font-weight: 700; color: var(--ink2); }
  .pjd-draft-btn:hover { background: var(--card); }
  .pjd-draft-editor { width: 100%; min-height: 500px; padding: 16px; border: 1px solid var(--line); border-top: none; border-radius: 0 0 12px 12px; background: #fff; font-family: inherit; font-size: .95rem; outline: none; resize: vertical; }

  /* Documentos */
  .pjd-doc { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--line); border-radius: 12px; background: var(--card); margin-bottom: 8px; }
  .pjd-doc-icon { width: 34px; height: 40px; border-radius: 6px; background: var(--danger-soft); border: 1px solid #fecaca; display: grid; place-items: center; color: var(--danger); font-size: .65rem; font-weight: 700; flex-shrink: 0; }
  .pjd-doc-meta { flex: 1; min-width: 0; }
  .pjd-doc-name { font-size: .92rem; font-weight: 700; color: var(--ink); }
  .pjd-doc-sub { font-size: .78rem; color: var(--muted); margin-top: 2px; }
  .pjd-doc-actions { display: flex; gap: 6px; }
  .pjd-doc-link { padding: 6px 10px; border-radius: 8px; background: var(--bg); color: var(--ink2); font-weight: 600; font-size: .8rem; text-decoration: none; border: 1px solid var(--line); }
  .pjd-doc-link:hover { color: var(--blue); border-color: var(--blue); }

  /* Empty state Inicio */
  .pjd-inicio-card { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 18px; margin-bottom: 14px; }
  .pjd-inicio-card h4 { margin: 0 0 8px; font-size: .9rem; font-weight: 700; color: var(--ink); }
  .pjd-inicio-card p { margin: 0; font-size: .88rem; color: var(--muted); }

  /* Loading bubbles */
  .pjd-loading-dots { display: inline-flex; gap: 4px; }
  .pjd-loading-dots span { width: 6px; height: 6px; border-radius: 50%; background: var(--muted); animation: pjdBounce 1.2s infinite ease-in-out; }
  .pjd-loading-dots span:nth-child(2) { animation-delay: .15s; }
  .pjd-loading-dots span:nth-child(3) { animation-delay: .3s; }
  @keyframes pjdBounce { 0%,80%,100% { transform: scale(.6); opacity: .4; } 40% { transform: scale(1); opacity: 1; } }
</style>
@endpush

@section('content')
@php
  $sd = $project->structured_data ?? [];
  $ficha = $sd['ficha'] ?? [];
  $fechas = $sd['fechas_clave'] ?? [];
  $resumenEjec = $sd['resumen_ejecutivo'] ?? [];
  $partidas = $sd['partidas'] ?? [];
  $checklist = $project->checklist ?? [];
  $statusClass = match($project->status) {
      'ready' => 'is-ready',
      'processing' => 'is-processing',
      'error', 'partial' => 'is-error',
      default => '',
  };
  $statusLabel = match($project->status) {
      'ready' => 'Listo',
      'processing' => 'Procesando…',
      'error' => 'Error',
      'partial' => 'Parcial',
      default => $project->status,
  };
@endphp

<div class="pjd-wrap">

  <div class="pjd-topbar">
    <a href="{{ route('projects.index') }}" class="pjd-back" title="Volver">←</a>
    <div class="pjd-title">
      {{ $project->name }}
      <span class="pjd-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
    </div>

    <div class="pjd-tabs" id="pjdTabs">
      <button class="pjd-tab" data-tab="analisis">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h6M9 13h6M9 17h3"/></svg>
        Análisis de Bases
      </button>
      <button class="pjd-tab" data-tab="inicio">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 11l9-8 9 8M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/></svg>
        Inicio
      </button>
      <button class="pjd-tab is-active" data-tab="ficha">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        Ficha
      </button>
      <button class="pjd-tab" data-tab="resumen">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6"/></svg>
        Resumen Ejecutivo
      </button>
      <button class="pjd-tab" data-tab="checklist">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Checklist
      </button>
      <button class="pjd-tab" data-tab="borrador">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
        Borrador
      </button>
      <button class="pjd-tab" data-tab="documentos">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        Documentos ({{ $project->documents->count() }})
      </button>

      @if($project->documents->isNotEmpty())
        <a href="{{ Storage::disk('public')->url($project->documents->first()->file_path) }}"
           target="_blank"
           class="pjd-view-doc">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Ver documento
        </a>
      @endif
    </div>
  </div>

  <div class="pjd-body">

    {{-- ============ COLUMNA IZQUIERDA: CHAT ============ --}}
    <div class="pjd-left">
      <div class="pjd-chat-head">
        <button type="button" class="pjd-chat-reset" id="pjdChatReset" title="Reiniciar chat">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v6h6"/></svg>
          Reiniciar
        </button>
      </div>

      <div class="pjd-chat-list" id="pjdChatList">
        @forelse($project->chatMessages as $m)
          <div class="pjd-msg {{ $m->role === 'user' ? 'is-user' : 'is-assistant' }}">
            @if($m->role === 'assistant')
              <div class="pjd-msg-avatar">m</div>
              <div>
                <div class="pjd-msg-meta">monico · {{ $m->created_at->format('H:i') }}</div>
                <div class="pjd-msg-body">{!! nl2br(e($m->content)) !!}</div>
              </div>
            @else
              <div class="pjd-msg-body">{{ $m->content }}</div>
            @endif
          </div>
        @empty
          <div class="pjd-msg is-assistant">
            <div class="pjd-msg-avatar">m</div>
            <div>
              <div class="pjd-msg-meta">monico</div>
              <div class="pjd-msg-body">Hola, soy tu asistente para esta licitación. Pregúntame lo que quieras saber sobre los documentos que subiste.</div>
            </div>
          </div>
        @endforelse
      </div>

      <form class="pjd-chat-input" id="pjdChatForm" autocomplete="off">
        @csrf
        <input type="text" name="message" id="pjdChatInput" placeholder="Escribe tu pregunta...">
        <button type="submit" class="pjd-chat-send" id="pjdChatSend" aria-label="Enviar">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
        </button>
      </form>
    </div>

    {{-- ============ COLUMNA DERECHA: PANEL DINÁMICO ============ --}}
    <div class="pjd-right">

      {{-- INICIO --}}
      <div class="pjd-pane" data-pane="inicio">
        <div class="pjd-inicio-card">
          <h4>Estado del proyecto</h4>
          <p>Status: <strong>{{ $statusLabel }}</strong></p>
          <p>Documentos: <strong>{{ $project->documents->count() }}</strong></p>
          <p>Creado: <strong>{{ $project->created_at->format('d M Y H:i') }}</strong></p>
        </div>
        @if($project->status === 'error' && $project->error_message)
          <div class="pjd-inicio-card" style="border-color:#fecaca;background:#fff5f5;">
            <h4 style="color:var(--danger)">Error de procesamiento</h4>
            <p>{{ $project->error_message }}</p>
          </div>
        @endif
      </div>

      {{-- FICHA --}}
      <div class="pjd-pane is-active" data-pane="ficha">
        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Ficha de Resumen <span class="sparkle">✨</span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @php
              $fichaRows = [
                'Número de licitación'             => $ficha['numero_licitacion'] ?? null,
                'Tipo de evento'                   => $ficha['tipo_evento'] ?? null,
                'Organismo'                        => $ficha['organismo'] ?? null,
                '¿Cuál es el objeto de la licitación?' => $ficha['objeto_licitacion'] ?? null,
                '¿Cuál es el medio de participación?'  => $ficha['medio_participacion'] ?? null,
              ];
            @endphp
            @foreach($fichaRows as $label => $value)
              <div class="pjd-field">
                <div class="pjd-field-label">{{ $label }}</div>
                <div class="pjd-field-value">{{ $value ?: 'No se encontró información' }}</div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Fechas Clave <span class="sparkle">✨</span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @php
              $fechasRows = [
                'Fecha de publicación'              => $fechas['fecha_publicacion'] ?? null,
                'Junta de aclaraciones'             => $fechas['junta_aclaraciones'] ?? null,
                'Presentación y apertura de proposiciones' => $fechas['presentacion_apertura'] ?? null,
                'Fallo'                              => $fechas['fallo'] ?? null,
                'Vigencia del contrato'              => $fechas['vigencia_contrato'] ?? null,
              ];
            @endphp
            @foreach($fechasRows as $label => $value)
              <div class="pjd-field">
                <div class="pjd-field-label">{{ $label }}</div>
                <div class="pjd-field-value">{{ $value ?: 'No se encontró información' }}</div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- RESUMEN EJECUTIVO --}}
      <div class="pjd-pane" data-pane="resumen">
        <div class="pjd-card is-open">
          <div class="pjd-card-head js-card-toggle">
            <h3>Resumen Ejecutivo <span class="sparkle">✨</span></h3>
            <div class="pjd-card-chev">▾</div>
          </div>
          <div class="pjd-card-body">
            @forelse($resumenEjec as $qa)
              <div class="pjd-qa">
                <div class="pjd-qa-q">{{ $qa['pregunta'] ?? '' }}</div>
                <div class="pjd-qa-a">{{ $qa['respuesta'] ?? 'No se encontró información' }}</div>
              </div>
            @empty
              <p style="color:var(--muted);font-size:.9rem;padding:8px;">Sin información disponible.</p>
            @endforelse
          </div>
        </div>
      </div>

      {{-- CHECKLIST --}}
      <div class="pjd-pane" data-pane="checklist">
        <h3 class="pjd-pane-title">Checklist sugerido</h3>
        <div id="pjdChecklistContainer">
          @forelse($checklist as $i => $item)
            <label class="pjd-check-item {{ !empty($item['checked']) ? 'is-done' : '' }}">
              <input type="checkbox" data-idx="{{ $i }}" {{ !empty($item['checked']) ? 'checked' : '' }}>
              <span class="pjd-check-label">{{ $item['item'] ?? $item['text'] ?? '' }}</span>
            </label>
          @empty
            <p style="color:var(--muted);font-size:.9rem;">Sin items en el checklist.</p>
          @endforelse
        </div>
      </div>

      {{-- BORRADOR --}}
      <div class="pjd-pane" data-pane="borrador">
        <h3 class="pjd-pane-title">Borrador</h3>
        <div class="pjd-pane-actions">
          <button type="button" class="pj-btn-mini" id="pjdSaveDraft">Guardar</button>
          <span style="color:var(--muted);font-size:.78rem;" id="pjdDraftStatus"></span>
        </div>
        <div class="pjd-draft-toolbar">
          <button type="button" class="pjd-draft-btn" onclick="document.execCommand('bold')"><b>B</b></button>
          <button type="button" class="pjd-draft-btn" onclick="document.execCommand('italic')"><i>I</i></button>
          <button type="button" class="pjd-draft-btn" onclick="document.execCommand('underline')"><u>U</u></button>
          <button type="button" class="pjd-draft-btn" onclick="document.execCommand('insertUnorderedList')">• Lista</button>
          <button type="button" class="pjd-draft-btn" onclick="document.execCommand('insertOrderedList')">1. Lista</button>
        </div>
        <div id="pjdDraftEditor" class="pjd-draft-editor" contenteditable="true">{!! $project->draft_content ?? '' !!}</div>
      </div>

      {{-- DOCUMENTOS --}}
      <div class="pjd-pane" data-pane="documentos">
        <h3 class="pjd-pane-title">Documentos del proyecto</h3>
        @forelse($project->documents as $doc)
          <div class="pjd-doc">
            <div class="pjd-doc-icon">{{ strtoupper(pathinfo($doc->filename, PATHINFO_EXTENSION) ?: 'FILE') }}</div>
            <div class="pjd-doc-meta">
              <div class="pjd-doc-name">{{ $doc->filename }}</div>
              <div class="pjd-doc-sub">
                {{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB ·
                Status: <strong>{{ $doc->status }}</strong>
              </div>
            </div>
            <div class="pjd-doc-actions">
              <a href="{{ Storage::disk('public')->url($doc->file_path) }}" target="_blank" class="pjd-doc-link">Ver</a>
            </div>
          </div>
        @empty
          <p style="color:var(--muted);font-size:.9rem;">Este proyecto no tiene documentos.</p>
        @endforelse
      </div>

    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  'use strict';

  const PROJECT_SLUG    = @json($project->slug);
  const CHAT_URL        = @json(route('projects.chat', $project));
  const CHAT_RESET_URL  = @json(route('projects.chat.reset', $project));
  const DRAFT_URL       = @json(route('projects.draft', $project));
  const CSRF            = '{{ csrf_token() }}';

  // ============ TABS ============
  const tabs = document.querySelectorAll('.pjd-tab');
  const panes = document.querySelectorAll('.pjd-pane');

  function activateTab(name) {
    tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
    panes.forEach(p => p.classList.toggle('is-active', p.dataset.pane === name));
  }

  tabs.forEach(t => t.addEventListener('click', () => activateTab(t.dataset.tab)));

  // Activar pestaña inicial = "ficha"
  activateTab('ficha');

  // ============ CARDS COLAPSABLES ============
  document.querySelectorAll('.js-card-toggle').forEach(head => {
    head.addEventListener('click', () => {
      head.closest('.pjd-card').classList.toggle('is-open');
    });
  });

  // ============ CHAT ============
  const chatForm  = document.getElementById('pjdChatForm');
  const chatInput = document.getElementById('pjdChatInput');
  const chatSend  = document.getElementById('pjdChatSend');
  const chatList  = document.getElementById('pjdChatList');
  const chatReset = document.getElementById('pjdChatReset');

  function scrollChatToBottom() {
    chatList.scrollTop = chatList.scrollHeight;
  }
  scrollChatToBottom();

  function appendMsg(role, content, time = '') {
    const wrap = document.createElement('div');
    wrap.className = `pjd-msg ${role === 'user' ? 'is-user' : 'is-assistant'}`;
    if (role === 'user') {
      wrap.innerHTML = `<div class="pjd-msg-body">${escapeHtml(content)}</div>`;
    } else {
      wrap.innerHTML = `
        <div class="pjd-msg-avatar">m</div>
        <div>
          <div class="pjd-msg-meta">monico${time ? ' · ' + time : ''}</div>
          <div class="pjd-msg-body">${escapeHtml(content).replace(/\n/g, '<br>')}</div>
        </div>
      `;
    }
    chatList.appendChild(wrap);
    scrollChatToBottom();
    return wrap;
  }

  function appendLoading() {
    const wrap = document.createElement('div');
    wrap.className = 'pjd-msg is-assistant';
    wrap.id = 'pjdLoadingMsg';
    wrap.innerHTML = `
      <div class="pjd-msg-avatar">m</div>
      <div>
        <div class="pjd-msg-meta">monico</div>
        <div class="pjd-msg-body"><span class="pjd-loading-dots"><span></span><span></span><span></span></span></div>
      </div>
    `;
    chatList.appendChild(wrap);
    scrollChatToBottom();
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = chatInput.value.trim();
    if (!msg) return;

    chatInput.value = '';
    chatSend.disabled = true;
    appendMsg('user', msg);
    appendLoading();

    try {
      const fd = new FormData();
      fd.append('_token', CSRF);
      fd.append('message', msg);

      const res = await fetch(CHAT_URL, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd, credentials: 'same-origin' });
      const json = await res.json();

      document.getElementById('pjdLoadingMsg')?.remove();

      if (json.ok && json.assistant_message) {
        appendMsg('assistant', json.assistant_message.content, json.assistant_message.time);
      } else {
        appendMsg('assistant', json.message || 'Hubo un error.');
      }
    } catch (err) {
      document.getElementById('pjdLoadingMsg')?.remove();
      appendMsg('assistant', 'Error de red. Intenta de nuevo.');
    } finally {
      chatSend.disabled = false;
      chatInput.focus();
    }
  });

  chatReset.addEventListener('click', async () => {
    if (!confirm('¿Borrar todo el historial del chat?')) return;
    try {
      await fetch(CHAT_RESET_URL, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
      chatList.innerHTML = '';
      appendMsg('assistant', 'Hola, soy tu asistente para esta licitación. Pregúntame lo que quieras saber sobre los documentos que subiste.');
    } catch (_) {}
  });

  // ============ CHECKLIST ============
  // (Solo UI por ahora — guardar requiere endpoint adicional)
  document.querySelectorAll('#pjdChecklistContainer input[type=checkbox]').forEach(cb => {
    cb.addEventListener('change', () => {
      cb.closest('.pjd-check-item').classList.toggle('is-done', cb.checked);
    });
  });

  // ============ BORRADOR ============
  const draftEditor = document.getElementById('pjdDraftEditor');
  const saveBtn = document.getElementById('pjdSaveDraft');
  const draftStatus = document.getElementById('pjdDraftStatus');

  saveBtn.addEventListener('click', async () => {
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('draft_content', draftEditor.innerHTML);
    saveBtn.disabled = true;
    saveBtn.textContent = 'Guardando…';
    try {
      const res = await fetch(DRAFT_URL, { method: 'POST', headers: { 'Accept': 'application/json' }, body: fd, credentials: 'same-origin' });
      if (res.ok) {
        draftStatus.textContent = 'Guardado ' + new Date().toLocaleTimeString();
      } else {
        draftStatus.textContent = 'Error al guardar';
      }
    } catch (e) {
      draftStatus.textContent = 'Error de red';
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'Guardar';
    }
  });

})();
</script>
@endpush