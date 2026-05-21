@extends('layouts.app')

@push('styles')
<style>
/* ============= LAYOUT GENERAL ============= */
.pjd-wrap{font-family:Quicksand,Arial,sans-serif;color:#0f172a;max-width:1700px;margin:0 auto;padding:14px 18px 60px}
.pjd-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap}
.pjd-title{font-size:22px;font-weight:700;margin:0;color:#0f172a}
.pjd-sub{color:#64748b;font-size:13px;margin-top:2px}
.pjd-back{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #e5e7eb;padding:8px 12px;border-radius:8px;color:#1e3a5f;text-decoration:none;font-weight:600;font-size:13px}
.pjd-back:hover{background:#f1f5f9}

.pjd-tabs{display:flex;gap:4px;border-bottom:1px solid #e5e7eb;margin-bottom:18px;overflow-x:auto}
.pjd-tab{background:transparent;border:none;padding:10px 16px;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;border-bottom:2px solid transparent;font-family:inherit;white-space:nowrap}
.pjd-tab:hover{color:#1e3a5f}
.pjd-tab.is-active{color:#1e3a5f;border-bottom-color:#1e3a5f}

.pjd-grid{display:grid;grid-template-columns:380px 1fr;gap:18px;align-items:flex-start}
@media (max-width: 1100px){.pjd-grid{grid-template-columns:1fr}}

.pjd-pane{display:none}
.pjd-pane.is-active{display:block}

/* ============= CHAT ============= */
.pjd-chat{background:#fff;border:1px solid #e5e7eb;border-radius:12px;display:flex;flex-direction:column;height:calc(100vh - 200px);min-height:520px;position:sticky;top:14px}
.pjd-chat-head{padding:12px 14px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
.pjd-chat-title{font-weight:700;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px}
.pjd-chat-reset{background:transparent;border:none;color:#64748b;cursor:pointer;font-size:12px;padding:4px 8px;border-radius:6px}
.pjd-chat-reset:hover{background:#f1f5f9;color:#dc2626}
.pjd-chat-body{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px}
.pjd-msg{padding:10px 12px;border-radius:10px;font-size:13px;max-width:100%;line-height:1.5}
.pjd-msg.user{background:#1e3a5f;color:#fff;align-self:flex-end;max-width:85%}
.pjd-msg.assistant{background:#f8fafc;color:#0f172a;align-self:flex-start;width:100%;border:1px solid #e5e7eb}
.pjd-msg p{margin:0 0 8px 0}.pjd-msg p:last-child{margin:0}
.pjd-msg ul,.pjd-msg ol{margin:6px 0 6px 18px}
.pjd-msg code{background:#e2e8f0;padding:1px 6px;border-radius:4px;font-size:12px}
.pjd-msg-actions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.pjd-msg-actions button{background:#fff;border:1px solid #cbd5e1;padding:5px 10px;border-radius:6px;font-size:11px;font-weight:600;color:#1e3a5f;cursor:pointer;display:inline-flex;align-items:center;gap:4px;font-family:inherit}
.pjd-msg-actions button:hover{background:#f0f9ff;border-color:#1e3a5f}
.pjd-chat-foot{padding:10px 12px;border-top:1px solid #f1f5f9;display:flex;gap:8px}
.pjd-chat-input{flex:1;border:1px solid #e5e7eb;border-radius:8px;padding:8px 10px;font-size:13px;resize:none;font-family:inherit;min-height:38px;max-height:120px}
.pjd-chat-input:focus{outline:none;border-color:#1e3a5f;box-shadow:0 0 0 3px rgba(30,58,95,.1)}
.pjd-chat-send{background:#1e3a5f;color:#fff;border:none;border-radius:8px;padding:0 16px;cursor:pointer;font-weight:600;font-size:13px}
.pjd-chat-send:disabled{opacity:.5;cursor:not-allowed}

/* ============= TARJETAS Y CAMPOS ============= */
.pjd-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin-bottom:14px}
.pjd-card-title{font-size:15px;font-weight:700;color:#0f172a;margin:0 0 14px 0;display:flex;align-items:center;gap:8px}
.pjd-fields{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media (max-width: 720px){.pjd-fields{grid-template-columns:1fr}}
.pjd-field{padding:10px 12px;background:#fafbfc;border:1px solid #f1f5f9;border-radius:8px;cursor:pointer;transition:.15s}
.pjd-field:hover{background:#f0f9ff;border-color:#bae6fd}
.pjd-field-lbl{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
.pjd-field-val{font-size:14px;color:#0f172a}
.pjd-field-empty{color:#94a3b8;font-style:italic;font-size:13px}
.pjd-field.has-citation::after{content:'';display:inline-block;width:6px;height:6px;background:#1e3a5f;border-radius:50%;margin-left:6px;vertical-align:middle}

/* ============= MODAL DE CITAS ============= */
.pjd-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:9999;padding:18px}
.pjd-modal.is-open{display:flex}
.pjd-modal-box{background:#fff;border-radius:14px;max-width:760px;width:100%;max-height:88vh;display:flex;flex-direction:column;overflow:hidden}
.pjd-modal-head{padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center}
.pjd-modal-title{font-size:15px;font-weight:700;color:#0f172a;margin:0}
.pjd-modal-close{background:transparent;border:none;font-size:20px;cursor:pointer;color:#64748b;line-height:1;padding:4px 8px}
.pjd-modal-body{padding:18px;overflow-y:auto;flex:1}
.pjd-cite-block{margin-bottom:14px;padding:12px 14px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px}
.pjd-cite-src{font-size:12px;font-weight:700;color:#1e3a5f;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center}
.pjd-cite-page{background:#1e3a5f;color:#fff;padding:2px 8px;border-radius:999px;font-size:11px}
.pjd-cite-text{font-size:13px;color:#0f172a;line-height:1.5;font-style:italic;border-left:3px solid #1e3a5f;padding-left:10px;background:#fff;padding:8px 10px;border-radius:4px}

/* ============= BORRADOR / REPORTE ============= */
.pjd-sub-tabs{display:flex;gap:6px;margin-bottom:12px}
.pjd-sub-tab{background:#fff;border:1px solid #e5e7eb;padding:8px 14px;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;border-radius:8px;font-family:inherit}
.pjd-sub-tab.is-active{background:#1e3a5f;color:#fff;border-color:#1e3a5f}
.pjd-sub-pane{display:none}.pjd-sub-pane.is-active{display:block}

.pjd-editor-tools{display:flex;gap:6px;background:#fff;border:1px solid #e5e7eb;border-radius:8px 8px 0 0;padding:6px;flex-wrap:wrap;border-bottom:none}
.pjd-editor-tools button{background:transparent;border:none;padding:6px 8px;cursor:pointer;border-radius:4px;color:#475569;font-weight:600;font-size:13px;min-width:30px}
.pjd-editor-tools button:hover{background:#f1f5f9;color:#1e3a5f}
.pjd-editor{background:#fff;border:1px solid #e5e7eb;border-radius:0 0 8px 8px;padding:18px;min-height:420px;font-size:14px;line-height:1.6;outline:none}
.pjd-editor table{border-collapse:collapse;margin:10px 0;width:100%}
.pjd-editor table th,.pjd-editor table td{border:1px solid #e5e7eb;padding:6px 10px;font-size:13px}
.pjd-editor table th{background:#f8fafc;font-weight:700}
.pjd-save-bar{display:flex;justify-content:flex-end;gap:8px;margin-top:10px;flex-wrap:wrap}
.pjd-btn{display:inline-flex;align-items:center;gap:6px;background:#1e3a5f;color:#fff;border:none;padding:9px 16px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;font-family:inherit}
.pjd-btn:hover{background:#162d49}
.pjd-btn.ghost{background:#fff;color:#1e3a5f;border:1px solid #cbd5e1}
.pjd-btn.ghost:hover{background:#f0f9ff}
.pjd-btn:disabled{opacity:.6;cursor:not-allowed}
.pjd-report-empty{text-align:center;padding:60px 20px;color:#64748b}
.pjd-report-empty svg{opacity:.4;margin-bottom:14px}

/* ============= DOCUMENTOS ============= */
.pjd-docs{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}
.pjd-doc{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:8px}
.pjd-doc-name{font-size:13px;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.pjd-doc-meta{font-size:11px;color:#64748b}
.pjd-doc-actions{display:flex;gap:6px;margin-top:auto}
.pjd-doc-actions a{flex:1;text-align:center;background:#1e3a5f;color:#fff;padding:6px 8px;border-radius:6px;font-size:12px;text-decoration:none;font-weight:600}

/* ====== CHECKLIST PRO ====== */
.chk-counters{display:grid;grid-template-columns:repeat(8,1fr);gap:10px;margin-bottom:14px}
.chk-counter{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px 8px;text-align:center;cursor:pointer;transition:.15s}
.chk-counter:hover{border-color:#cbd5e1;box-shadow:0 2px 8px rgba(15,23,42,.06)}
.chk-counter.is-active{border-color:#1e3a5f;box-shadow:0 0 0 2px rgba(30,58,95,.12)}
.chk-num{display:block;font-size:22px;font-weight:700;color:#0f172a;line-height:1}
.chk-lbl{display:block;font-size:11px;color:#64748b;margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.chk-c-red .chk-num{color:#dc2626}.chk-c-yellow .chk-num{color:#d97706}
.chk-c-green .chk-num,.chk-c-green2 .chk-num{color:#16a34a}
.chk-c-gray .chk-num{color:#64748b}.chk-c-blue .chk-num{color:#2563eb}
.chk-c-total{background:#1e3a5f;color:#fff}
.chk-c-total .chk-num,.chk-c-total .chk-lbl{color:#fff}

.chk-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;flex-wrap:wrap}
.chk-toolbar-left,.chk-toolbar-right{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.chk-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:8px;font-size:13px;font-weight:600;font-family:Quicksand,Arial,sans-serif;cursor:pointer;border:1px solid #e5e7eb;background:#fff;color:#334155;transition:.15s}
.chk-btn:hover{background:#f8fafc;border-color:#cbd5e1}
.chk-btn-primary{background:#1e3a5f;color:#fff;border-color:#1e3a5f}
.chk-btn-primary:hover{background:#162d49;color:#fff}
.chk-btn-ghost{background:#fff}
.chk-btn-add{background:#fff;border:1.5px dashed #cbd5e1;color:#1e3a5f;width:100%;justify-content:center;padding:12px}
.chk-btn-add:hover{background:#f0f9ff;border-color:#1e3a5f}
.chk-export-size{font-weight:400;color:#94a3b8;font-size:12px}

.chk-search{position:relative;display:flex;align-items:center}
.chk-search svg{position:absolute;left:10px;color:#94a3b8}
.chk-search input{padding:8px 12px 8px 32px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;width:220px;font-family:Quicksand,Arial,sans-serif;background:#fff}
.chk-search input:focus{outline:none;border-color:#1e3a5f;box-shadow:0 0 0 3px rgba(30,58,95,.1)}

.chk-dropdown{position:relative}
.chk-dropdown-panel{position:absolute;top:calc(100% + 6px);right:0;background:#fff;border:1px solid #e5e7eb;border-radius:10px;box-shadow:0 10px 30px rgba(15,23,42,.12);padding:14px;min-width:220px;z-index:50;display:none}
.chk-dropdown-panel.is-open{display:block}
.chk-filtros-panel{display:none;grid-template-columns:1fr 1fr 1fr;gap:18px;min-width:540px}
.chk-filtros-panel.is-open{display:grid}
.chk-filter-group-title{font-size:12px;font-weight:700;color:#0f172a;margin-bottom:8px;text-transform:uppercase;letter-spacing:.4px}
.chk-filter-opt{display:flex;align-items:center;gap:8px;padding:6px 0;font-size:13px;color:#334155;cursor:pointer}
.chk-filter-opt input{accent-color:#1e3a5f}
.chk-filter-badge{background:#1e3a5f;color:#fff;border-radius:999px;padding:1px 7px;font-size:11px;margin-left:4px}

.chk-table-wrap{background:#fff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:12px}
.chk-table{width:100%;border-collapse:collapse;font-size:13px;font-family:Quicksand,Arial,sans-serif}
.chk-table thead th{background:#f8fafc;text-align:left;padding:10px 12px;font-weight:700;color:#475569;border-bottom:1px solid #e5e7eb;font-size:12px;text-transform:uppercase;letter-spacing:.3px}
.chk-table tbody td{padding:12px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#0f172a}
.chk-table tbody tr.chk-row{cursor:pointer;transition:.1s}
.chk-table tbody tr.chk-row:hover{background:#fafbfc}
.chk-table tbody tr.chk-row.is-expanded{background:#f8fafc}
.chk-th-chev,.chk-td-chev{width:36px;text-align:center}
.chk-th-check,.chk-td-check{width:36px;text-align:center}
.chk-chev{display:inline-flex;width:22px;height:22px;align-items:center;justify-content:center;border-radius:6px;color:#64748b;transition:.15s}
.chk-row.is-expanded .chk-chev{transform:rotate(90deg);background:#e0e7ff;color:#1e3a5f}

.chk-pill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;border:1px solid transparent}
.chk-pill-sin{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
.chk-pill-cumple{background:#dcfce7;color:#166534;border-color:#86efac}
.chk-pill-parcial{background:#fef3c7;color:#92400e;border-color:#fcd34d}
.chk-pill-nocumple{background:#fee2e2;color:#991b1b;border-color:#fca5a5}
.chk-pill-pendiente{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
.chk-pill-revision{background:#dbeafe;color:#1e40af;border-color:#93c5fd}
.chk-pill-aprobado{background:#dcfce7;color:#166534;border-color:#86efac}
.chk-pill-obligatorio{background:#fee2e2;color:#991b1b;border-color:#fca5a5}
.chk-pill-opcional{background:#f1f5f9;color:#475569;border-color:#e2e8f0}

.chk-cum-select,.chk-st-select{border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;font-size:12px;background:#fff;font-family:Quicksand,Arial,sans-serif;cursor:pointer}

.chk-detail-row td{padding:0!important;background:#f8fafc;border-bottom:1px solid #e5e7eb}
.chk-detail{padding:20px 24px;display:grid;grid-template-columns:1fr 1fr;gap:24px}
.chk-detail-block{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:14px 16px}
.chk-detail-block.chk-full{grid-column:1/-1}
.chk-detail-label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:6px}
.chk-detail-text{font-size:14px;color:#0f172a;line-height:1.55;user-select:text}
.chk-detail-text mark{background:#fef08a;padding:1px 2px;border-radius:2px}
.chk-detail-meta{font-size:12px;color:#64748b;margin-top:6px}
.chk-detail-meta strong{color:#0f172a}

.chk-prio-group{display:inline-flex;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden}
.chk-prio-btn{padding:6px 14px;border:none;background:#fff;font-size:12px;font-weight:700;color:#64748b;cursor:pointer;font-family:Quicksand,Arial,sans-serif;border-right:1px solid #e5e7eb}
.chk-prio-btn:last-child{border-right:none}
.chk-prio-btn:hover{background:#f8fafc}
.chk-prio-btn.is-active[data-prio="alta"]{background:#fee2e2;color:#991b1b}
.chk-prio-btn.is-active[data-prio="media"]{background:#fef3c7;color:#92400e}
.chk-prio-btn.is-active[data-prio="baja"]{background:#dcfce7;color:#166534}

.chk-date-input,.chk-select{width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:Quicksand,Arial,sans-serif;background:#fff;color:#0f172a}
.chk-date-input:focus,.chk-select:focus{outline:none;border-color:#1e3a5f;box-shadow:0 0 0 3px rgba(30,58,95,.1)}

.chk-notes{display:flex;flex-direction:column;gap:6px}
.chk-notes-head{display:flex;justify-content:space-between;align-items:center}
.chk-notes-empty{color:#94a3b8;font-size:13px;font-style:italic}
.chk-notes-text{font-size:13px;color:#0f172a;background:#fafbfc;padding:8px 10px;border-radius:6px;border:1px solid #f1f5f9;white-space:pre-wrap}
.chk-notes-edit{width:100%;min-height:80px;padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;font-size:13px;font-family:Quicksand,Arial,sans-serif;resize:vertical}
.chk-icon-btn{background:transparent;border:none;color:#1e3a5f;cursor:pointer;padding:4px;border-radius:4px;display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;font-family:Quicksand,Arial,sans-serif}
.chk-icon-btn:hover{background:#e0e7ff}

.chk-attachments{display:flex;flex-direction:column;gap:8px}
.chk-att-list{display:flex;flex-direction:column;gap:6px}
.chk-att-item{display:flex;align-items:center;gap:8px;padding:8px 10px;background:#fafbfc;border:1px solid #f1f5f9;border-radius:6px;font-size:13px}
.chk-att-item a{color:#1e3a5f;text-decoration:none;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.chk-att-item a:hover{text-decoration:underline}
.chk-att-rm{background:transparent;border:none;color:#dc2626;cursor:pointer;padding:2px}
.chk-detail-actions{grid-column:1/-1;display:flex;justify-content:flex-end;gap:8px;margin-top:4px}
.chk-footer{margin-top:12px}
.chk-empty{padding:40px;text-align:center;color:#64748b;background:#fff;border-radius:12px}

@media (max-width: 980px){
  .chk-counters{grid-template-columns:repeat(4,1fr)}
  .chk-detail{grid-template-columns:1fr}
  .chk-filtros-panel{grid-template-columns:1fr;min-width:240px}
  .chk-toolbar-left,.chk-toolbar-right{width:100%}
  .chk-search input{width:100%}
}
</style>
@endpush

@section('content')
<div class="pjd-wrap">

  {{-- ===== HEADER ===== --}}
  <div class="pjd-head">
    <div>
      <h1 class="pjd-title">{{ $project->name }}</h1>
      <div class="pjd-sub">Creado {{ $project->created_at?->format('d/m/Y') }} · {{ count($documents ?? []) }} documento(s)</div>
    </div>
    <a href="{{ route('projects.index') }}" class="pjd-back">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      Volver a proyectos
    </a>
  </div>

  {{-- ===== TABS ===== --}}
  <div class="pjd-tabs">
    <button class="pjd-tab is-active" data-tab="analisis">Análisis de Bases</button>
    <button class="pjd-tab" data-tab="inicio">Inicio</button>
    <button class="pjd-tab" data-tab="ficha">Ficha</button>
    <button class="pjd-tab" data-tab="resumen">Resumen Ejecutivo</button>
    <button class="pjd-tab" data-tab="checklist">Checklist</button>
    <button class="pjd-tab" data-tab="borrador">Borrador</button>
    <button class="pjd-tab" data-tab="documentos">Documentos</button>
  </div>

  <div class="pjd-grid">
    {{-- ===== COLUMNA IZQUIERDA: CHAT ===== --}}
    <div class="pjd-chat">
      <div class="pjd-chat-head">
        <div class="pjd-chat-title">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
          Asistente del proyecto
        </div>
        <button type="button" class="pjd-chat-reset" id="pjdChatReset">Limpiar</button>
      </div>
      <div class="pjd-chat-body" id="pjdChatBody">
        @forelse(($messages ?? []) as $m)
          <div class="pjd-msg {{ $m->role === 'user' ? 'user' : 'assistant' }}">{!! nl2br(e($m->content)) !!}</div>
        @empty
          <div class="pjd-msg assistant">
            Hola 👋 Soy tu asistente para este proyecto. Pregúntame sobre las bases, requisitos, fechas, garantías, partidas, etc.
          </div>
        @endforelse
      </div>
      <div class="pjd-chat-foot">
        <textarea class="pjd-chat-input" id="pjdChatInput" placeholder="Escribe tu pregunta..." rows="1"></textarea>
        <button class="pjd-chat-send" id="pjdChatSend">Enviar</button>
      </div>
    </div>

    {{-- ===== COLUMNA DERECHA: PANES ===== --}}
    <div>

      {{-- ANÁLISIS DE BASES --}}
      <div class="pjd-pane is-active" data-pane="analisis">
        <div class="pjd-card">
          <h3 class="pjd-card-title">Análisis general</h3>
          @php $resumen = data_get($project->structured_data, 'resumen_ejecutivo'); @endphp
          @if($resumen && is_string($resumen))
            <div style="line-height:1.6;color:#334155">{!! nl2br(e($resumen)) !!}</div>
          @elseif(is_array($resumen))
            @foreach($resumen as $k => $v)
              <div style="margin-bottom:10px"><strong style="color:#1e3a5f">{{ ucfirst(str_replace('_',' ',$k)) }}:</strong> {{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v }}</div>
            @endforeach
          @else
            <div style="color:#94a3b8">No hay análisis disponible. Sube documentos para que se genere automáticamente.</div>
          @endif
        </div>
      </div>

      {{-- INICIO --}}
      <div class="pjd-pane" data-pane="inicio">
        <div class="pjd-card">
          <h3 class="pjd-card-title">Datos iniciales</h3>
          @php $inicio = data_get($project->structured_data, 'ficha', []); @endphp
          <div class="pjd-fields">
            @foreach(['numero_procedimiento'=>'Núm. Procedimiento','convocante'=>'Convocante','tipo_procedimiento'=>'Tipo','caracter'=>'Carácter','objeto'=>'Objeto','origen_recursos'=>'Origen de Recursos'] as $k => $lbl)
              <div class="pjd-field {{ data_get($project->structured_data, 'citas.'.$k) ? 'has-citation' : '' }}" data-cite-key="{{ $k }}">
                <div class="pjd-field-lbl">{{ $lbl }}</div>
                <div class="pjd-field-val">
                  @if(!empty($inicio[$k])) {{ $inicio[$k] }} @else <span class="pjd-field-empty">—</span> @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- FICHA --}}
      <div class="pjd-pane" data-pane="ficha">
        <div class="pjd-card">
          <h3 class="pjd-card-title">Ficha técnica</h3>
          @php $ficha = data_get($project->structured_data, 'ficha', []); @endphp
          <div class="pjd-fields">
            @foreach($ficha as $k => $v)
              <div class="pjd-field {{ data_get($project->structured_data, 'citas.'.$k) ? 'has-citation' : '' }}" data-cite-key="{{ $k }}">
                <div class="pjd-field-lbl">{{ ucfirst(str_replace('_',' ',$k)) }}</div>
                <div class="pjd-field-val">{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : ($v ?: '—') }}</div>
              </div>
            @endforeach
          </div>
        </div>

        @php $fechas = data_get($project->structured_data, 'fechas_clave', []); @endphp
        @if(!empty($fechas))
          <div class="pjd-card">
            <h3 class="pjd-card-title">Fechas clave</h3>
            <div class="pjd-fields">
              @foreach($fechas as $k => $v)
                <div class="pjd-field {{ data_get($project->structured_data, 'citas.fecha_'.$k) ? 'has-citation' : '' }}" data-cite-key="fecha_{{ $k }}">
                  <div class="pjd-field-lbl">{{ ucfirst(str_replace('_',' ',$k)) }}</div>
                  <div class="pjd-field-val">{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : ($v ?: '—') }}</div>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>

      {{-- RESUMEN EJECUTIVO --}}
      <div class="pjd-pane" data-pane="resumen">
        <div class="pjd-card">
          <h3 class="pjd-card-title">Resumen ejecutivo</h3>
          @php $re = data_get($project->structured_data, 'resumen_ejecutivo', []); @endphp
          @if(is_array($re))
            <div class="pjd-fields">
              @foreach($re as $k => $v)
                <div class="pjd-field {{ data_get($project->structured_data, 'citas.re_'.$k) ? 'has-citation' : '' }}" data-cite-key="re_{{ $k }}">
                  <div class="pjd-field-lbl">{{ ucfirst(str_replace('_',' ',$k)) }}</div>
                  <div class="pjd-field-val">{{ is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : ($v ?: '—') }}</div>
                </div>
              @endforeach
            </div>
          @else
            <div style="line-height:1.6">{{ $re }}</div>
          @endif
        </div>
      </div>

      {{-- CHECKLIST PRO --}}
      <div class="pjd-pane" data-pane="checklist">
        {{-- CONTADORES --}}
        <div class="chk-counters">
          <div class="chk-counter" data-counter="sin_revisar"><span class="chk-num" id="chkCntSinRevisar">0</span><span class="chk-lbl">Sin revisar</span></div>
          <div class="chk-counter chk-c-red" data-counter="no_cumple"><span class="chk-num" id="chkCntNoCumple">0</span><span class="chk-lbl">No Cumple</span></div>
          <div class="chk-counter chk-c-yellow" data-counter="parcial"><span class="chk-num" id="chkCntParcial">0</span><span class="chk-lbl">Parcial</span></div>
          <div class="chk-counter chk-c-green" data-counter="cumple"><span class="chk-num" id="chkCntCumple">0</span><span class="chk-lbl">Cumple</span></div>
          <div class="chk-counter chk-c-gray" data-counter="pendiente"><span class="chk-num" id="chkCntPendiente">0</span><span class="chk-lbl">Pendiente</span></div>
          <div class="chk-counter chk-c-blue" data-counter="en_revision"><span class="chk-num" id="chkCntEnRevision">0</span><span class="chk-lbl">En revisión</span></div>
          <div class="chk-counter chk-c-green2" data-counter="aprobado"><span class="chk-num" id="chkCntAprobado">0</span><span class="chk-lbl">Aprobado</span></div>
          <div class="chk-counter chk-c-total" data-counter="total"><span class="chk-num" id="chkCntTotal">0</span><span class="chk-lbl">Total</span></div>
        </div>

        {{-- TOOLBAR --}}
        <div class="chk-toolbar">
          <div class="chk-toolbar-left">
            <button type="button" class="chk-btn chk-btn-ghost" id="chkBtnDescargar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Descargar lista
            </button>
            <button type="button" class="chk-btn chk-btn-ghost" id="chkBtnExportar">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Exportar <span id="chkExportCount">0</span> <span class="chk-export-size" id="chkExportSize">archivos (0 B)</span>
            </button>
          </div>
          <div class="chk-toolbar-right">
            <div class="chk-search">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" id="chkSearch" placeholder="Buscar en checklist...">
            </div>
            <button type="button" class="chk-btn chk-btn-primary" id="chkBtnReanalisis" title="Volver a analizar el checklist">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"/><path d="M20.49 15a9 9 0 0 1-14.85 3.36L1 14"/></svg>
              Reanálisis
            </button>

            <div class="chk-dropdown">
              <button type="button" class="chk-btn chk-btn-ghost" id="chkBtnFiltros">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                Filtros <span class="chk-filter-badge" id="chkFiltrosBadge" style="display:none">0</span>
              </button>
              <div class="chk-dropdown-panel chk-filtros-panel" id="chkFiltrosPanel">
                <div>
                  <div class="chk-filter-group-title">Cumplimiento</div>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="cumplimiento" value="__all__" checked> <span>Todos</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="cumplimiento" value="sin_revisar"> <span>Sin revisar</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="cumplimiento" value="cumple"> <span>Cumple</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="cumplimiento" value="parcial"> <span>Parcial</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="cumplimiento" value="no_cumple"> <span>No Cumple</span></label>
                </div>
                <div>
                  <div class="chk-filter-group-title">Status</div>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="status" value="__all__" checked> <span>Todos</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="status" value="pendiente"> <span>Pendiente</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="status" value="en_revision"> <span>En revisión</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="status" value="aprobado"> <span>Aprobado</span></label>
                </div>
                <div>
                  <div class="chk-filter-group-title">Prioridad</div>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="prioridad" value="__all__" checked> <span>Todas</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="prioridad" value="alta"> <span>Alta</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="prioridad" value="media"> <span>Media</span></label>
                  <label class="chk-filter-opt"><input type="checkbox" class="chk-flt" data-group="prioridad" value="baja"> <span>Baja</span></label>
                </div>
              </div>
            </div>

            <div class="chk-dropdown">
              <button type="button" class="chk-btn chk-btn-ghost" id="chkBtnColumnas">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
                Columnas
              </button>
              <div class="chk-dropdown-panel chk-cols-panel" id="chkColsPanel">
                <label class="chk-filter-opt"><input type="checkbox" class="chk-col-tgl" data-col="requisito" checked> <span>Requisito</span></label>
                <label class="chk-filter-opt"><input type="checkbox" class="chk-col-tgl" data-col="formato" checked> <span>Formato</span></label>
                <label class="chk-filter-opt"><input type="checkbox" class="chk-col-tgl" data-col="categoria" checked> <span>Categoría</span></label>
                <label class="chk-filter-opt"><input type="checkbox" class="chk-col-tgl" data-col="aplicabilidad" checked> <span>Aplicabilidad</span></label>
                <label class="chk-filter-opt"><input type="checkbox" class="chk-col-tgl" data-col="obligatorio" checked> <span>Obligatorio</span></label>
                <label class="chk-filter-opt"><input type="checkbox" class="chk-col-tgl" data-col="cumplimiento" checked> <span>Cumplimiento</span></label>
                <label class="chk-filter-opt"><input type="checkbox" class="chk-col-tgl" data-col="status" checked> <span>Status</span></label>
              </div>
            </div>
          </div>
        </div>

        {{-- TABLA --}}
        <div class="chk-table-wrap">
          <table class="chk-table" id="chkTable">
            <thead>
              <tr>
                <th class="chk-th-chev"></th>
                <th class="chk-th-check"><input type="checkbox" id="chkSelectAll"></th>
                <th data-col="requisito">Requisito</th>
                <th data-col="formato">Formato</th>
                <th data-col="categoria">Categoría</th>
                <th data-col="aplicabilidad">Aplicabilidad</th>
                <th data-col="obligatorio">Obligatorio</th>
                <th data-col="cumplimiento">Cumplimiento</th>
                <th data-col="status">Status</th>
              </tr>
            </thead>
            <tbody id="chkTbody"></tbody>
          </table>
          <div class="chk-empty" id="chkEmpty" style="display:none">
            <p>No hay requisitos en el checklist todavía.</p>
          </div>
        </div>

        <div class="chk-footer">
          <button type="button" class="chk-btn chk-btn-add" id="chkBtnAdd">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Agregar nuevo requisito
          </button>
        </div>
      </div>

      {{-- BORRADOR / REPORTE --}}
      <div class="pjd-pane" data-pane="borrador">
        <div class="pjd-sub-tabs">
          <button class="pjd-sub-tab is-active" data-sub="draft">Borrador</button>
          <button class="pjd-sub-tab" data-sub="report">Reporte</button>
        </div>

        <div class="pjd-sub-pane is-active" data-sub-pane="draft">
          <div class="pjd-editor-tools">
            <button data-cmd="bold" title="Negrita"><b>B</b></button>
            <button data-cmd="italic" title="Itálica"><i>I</i></button>
            <button data-cmd="underline" title="Subrayado"><u>U</u></button>
            <button data-cmd="insertUnorderedList" title="Lista">•</button>
            <button data-cmd="insertOrderedList" title="Lista numerada">1.</button>
            <button data-cmd="formatBlock" data-val="H2" title="Título">H2</button>
            <button data-cmd="formatBlock" data-val="H3" title="Subtítulo">H3</button>
            <button data-cmd="formatBlock" data-val="P" title="Párrafo">¶</button>
          </div>
          <div class="pjd-editor" id="pjdEditor" contenteditable="true">{!! $project->draft_content ?: '<p>Comienza a escribir tu borrador aquí. Puedes pegar tablas desde el chat o desde Excel.</p>' !!}</div>
          <div class="pjd-save-bar">
            <button class="pjd-btn ghost" id="pjdDraftDownload">Descargar .docx</button>
            <button class="pjd-btn" id="pjdDraftSave">Guardar borrador</button>
          </div>
        </div>

        <div class="pjd-sub-pane" data-sub-pane="report">
          @if(!empty($project->report_content))
            <div class="pjd-card" id="pjdReportContent">{!! $project->report_content !!}</div>
            <div class="pjd-save-bar">
              <button class="pjd-btn ghost" id="pjdReportDownload">Descargar reporte</button>
              <button class="pjd-btn" id="pjdReportRegen">Regenerar reporte</button>
            </div>
          @else
            <div class="pjd-report-empty" id="pjdReportEmpty">
              <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <p>Aún no se ha generado un reporte para este proyecto.</p>
              <button class="pjd-btn" id="pjdReportGen">Generar reporte</button>
            </div>
          @endif
        </div>
      </div>

      {{-- DOCUMENTOS --}}
      <div class="pjd-pane" data-pane="documentos">
        <div class="pjd-docs">
          @forelse(($documents ?? []) as $d)
            <div class="pjd-doc">
              <div class="pjd-doc-name">📄 {{ $d->filename }}</div>
              <div class="pjd-doc-meta">{{ strtoupper(pathinfo($d->filename, PATHINFO_EXTENSION)) }} · {{ number_format(($d->file_size ?? 0)/1024,1) }} KB</div>
              <div class="pjd-doc-actions">
                <a href="{{ asset('storage/'.$d->file_path) }}" target="_blank">Abrir</a>
              </div>
            </div>
          @empty
            <div style="color:#94a3b8;padding:20px">No hay documentos adjuntos.</div>
          @endforelse
        </div>
      </div>

    </div>
  </div>
</div>

{{-- ===== MODAL DE CITAS ===== --}}
<div class="pjd-modal" id="pjdCiteModal">
  <div class="pjd-modal-box">
    <div class="pjd-modal-head">
      <h3 class="pjd-modal-title" id="pjdCiteTitle">Fuente</h3>
      <button class="pjd-modal-close" id="pjdCiteClose">×</button>
    </div>
    <div class="pjd-modal-body" id="pjdCiteBody"></div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
window.PROJECT_DATA = {
  id: @json($project->id),
  citas: @json(data_get($project->structured_data, 'citas', new \stdClass)),
  csrf: '{{ csrf_token() }}',
  routes: {
    chat:        '{{ route('projects.chat', $project) }}',
    chatReset:   '{{ route('projects.chat.reset', $project) }}',
    draft:       '{{ route('projects.draft', $project) }}',
    checklist:   '{{ route('projects.checklist', $project) }}',
    report:      '{{ route('projects.report', $project) }}',
    chkAttach:   '{{ url('projects/'.$project->id.'/checklist/attach') }}',
    chkReanalyze:'{{ url('projects/'.$project->id.'/checklist/reanalyze') }}'
  }
};
window.PROJECT_USERS = @json($users ?? []);
window.PROJECT_CHECKLIST = @json($project->checklist ?? []);
</script>

<script>
/* ============= TABS ============= */
document.querySelectorAll('.pjd-tab').forEach(tab=>{
  tab.addEventListener('click',()=>{
    document.querySelectorAll('.pjd-tab').forEach(t=>t.classList.remove('is-active'));
    document.querySelectorAll('.pjd-pane').forEach(p=>p.classList.remove('is-active'));
    tab.classList.add('is-active');
    document.querySelector(`.pjd-pane[data-pane="${tab.dataset.tab}"]`).classList.add('is-active');
  });
});
document.querySelectorAll('.pjd-sub-tab').forEach(t=>{
  t.addEventListener('click',()=>{
    document.querySelectorAll('.pjd-sub-tab').forEach(x=>x.classList.remove('is-active'));
    document.querySelectorAll('.pjd-sub-pane').forEach(x=>x.classList.remove('is-active'));
    t.classList.add('is-active');
    document.querySelector(`.pjd-sub-pane[data-sub-pane="${t.dataset.sub}"]`).classList.add('is-active');
  });
});

/* ============= EDITOR TOOLS ============= */
document.querySelectorAll('.pjd-editor-tools button').forEach(b=>{
  b.addEventListener('click',()=>{
    if(b.dataset.cmd==='formatBlock') document.execCommand('formatBlock',false,b.dataset.val);
    else document.execCommand(b.dataset.cmd,false,null);
  });
});

/* ============= MODAL DE CITAS ============= */
const citeModal = document.getElementById('pjdCiteModal');
const citeTitle = document.getElementById('pjdCiteTitle');
const citeBody  = document.getElementById('pjdCiteBody');
document.getElementById('pjdCiteClose').onclick = ()=> citeModal.classList.remove('is-open');
citeModal.addEventListener('click',e=>{ if(e.target===citeModal) citeModal.classList.remove('is-open'); });

document.querySelectorAll('.pjd-field[data-cite-key]').forEach(f=>{
  f.addEventListener('click',()=>{
    const key = f.dataset.citeKey;
    const cites = (window.PROJECT_DATA.citas || {})[key];
    const lbl = f.querySelector('.pjd-field-lbl').textContent.trim();
    citeTitle.textContent = 'Fuente: '+lbl;
    if(!cites || (Array.isArray(cites) && !cites.length)){
      citeBody.innerHTML = '<div style="color:#94a3b8;padding:14px">Este campo no tiene referencia registrada. La extracción automática no encontró el origen específico.</div>';
    } else {
      const arr = Array.isArray(cites) ? cites : [cites];
      citeBody.innerHTML = arr.map(c => `
        <div class="pjd-cite-block">
          <div class="pjd-cite-src">
            <span>📄 ${c.documento || c.source || 'Documento'}</span>
            ${c.pagina ? `<span class="pjd-cite-page">Pág. ${c.pagina}</span>` : ''}
          </div>
          <div class="pjd-cite-text">"${(c.texto || c.quote || c.cita || '').replace(/[<>]/g,'')}"</div>
        </div>`).join('');
    }
    citeModal.classList.add('is-open');
  });
});

/* ============= CHAT ============= */
const chatBody  = document.getElementById('pjdChatBody');
const chatInput = document.getElementById('pjdChatInput');
const chatSend  = document.getElementById('pjdChatSend');
const chatReset = document.getElementById('pjdChatReset');

function escHtml(s){return String(s||'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}

function parseMarkdownTables(text){
  // Devuelve un array de bloques: {type:'text'|'table', data:...}
  const lines = text.split('\n');
  const blocks = [];
  let buf = []; let i = 0;
  while(i < lines.length){
    // Detectar tabla: línea con | seguida de línea separadora con ---
    if(lines[i] && /\|/.test(lines[i]) && lines[i+1] && /^\s*\|?[\s\-\|:]+\|?\s*$/.test(lines[i+1])){
      if(buf.length){ blocks.push({type:'text', data:buf.join('\n')}); buf=[]; }
      const headers = lines[i].split('|').map(s=>s.trim()).filter(s=>s.length);
      i += 2;
      const rows = [];
      while(i < lines.length && /\|/.test(lines[i])){
        rows.push(lines[i].split('|').map(s=>s.trim()).filter((s,idx,arr)=>!(idx===0&&!s)&&!(idx===arr.length-1&&!s)));
        i++;
      }
      blocks.push({type:'table', data:{headers, rows}});
    } else {
      buf.push(lines[i]); i++;
    }
  }
  if(buf.length) blocks.push({type:'text', data:buf.join('\n')});
  return blocks;
}

function buildTableHtmlInline(t){
  let html = '<table style="width:100%;border-collapse:collapse;margin:14px 0;border:1px solid #e5e7eb;font-family:Quicksand,Arial,sans-serif">';
  html += '<thead><tr>';
  t.headers.forEach(h=> html += `<th style="background:#1e3a5f;color:#fff;padding:8px 10px;text-align:left;font-size:12px;border:1px solid #1e3a5f">${escHtml(h)}</th>`);
  html += '</tr></thead><tbody>';
  t.rows.forEach((r,ri)=>{
    const bg = ri%2 ? '#ffffff' : '#f8fafc';
    html += `<tr style="background:${bg}">`;
    r.forEach(c=> html += `<td style="padding:8px 10px;border:1px solid #e5e7eb;font-size:12px;color:#0f172a">${escHtml(c)}</td>`);
    html += '</tr>';
  });
  html += '</tbody></table>';
  return html;
}

function renderAssistantMessage(text){
  const wrap = document.createElement('div');
  wrap.className = 'pjd-msg assistant';
  const blocks = parseMarkdownTables(text);
  let html = '';
  blocks.forEach((b,idx)=>{
    if(b.type==='text'){
      html += `<div>${escHtml(b.data).replace(/\n/g,'<br>')}</div>`;
    } else {
      const tableHtml = buildTableHtmlInline(b.data);
      html += `<div data-table-block="${idx}">${tableHtml}
        <div class="pjd-msg-actions">
          <button data-act="copy-table" data-idx="${idx}">📋 Copiar tabla</button>
          <button data-act="to-draft"   data-idx="${idx}">📝 Pasar al borrador</button>
          <button data-act="xlsx"       data-idx="${idx}">📊 Descargar Excel</button>
        </div>
      </div>`;
    }
  });
  wrap.innerHTML = html;
  wrap._blocks = blocks;
  chatBody.appendChild(wrap);
  chatBody.scrollTop = chatBody.scrollHeight;
  return wrap;
}

chatBody.addEventListener('click', async (e)=>{
  const btn = e.target.closest('[data-act]');
  if(!btn) return;
  const idx = parseInt(btn.dataset.idx,10);
  const wrap = btn.closest('.pjd-msg');
  const block = wrap._blocks?.[idx];
  if(!block || block.type!=='table') return;
  const tableHtml = buildTableHtmlInline(block.data);

  if(btn.dataset.act==='copy-table'){
    try{
      const item = new ClipboardItem({
        'text/html': new Blob([tableHtml],{type:'text/html'}),
        'text/plain': new Blob([block.data.headers.join('\t')+'\n'+block.data.rows.map(r=>r.join('\t')).join('\n')],{type:'text/plain'})
      });
      await navigator.clipboard.write([item]);
      btn.textContent='✅ Copiada'; setTimeout(()=>btn.textContent='📋 Copiar tabla',1500);
    }catch(err){ alert('No se pudo copiar la tabla'); }
  }
  if(btn.dataset.act==='to-draft'){
    const ed = document.getElementById('pjdEditor');
    ed.innerHTML += tableHtml;
    document.querySelector('.pjd-tab[data-tab="borrador"]').click();
  }
  if(btn.dataset.act==='xlsx'){
    const ws = XLSX.utils.aoa_to_sheet([block.data.headers, ...block.data.rows]);
    ws['!cols'] = block.data.headers.map((h,ci)=>({wch:Math.max(h.length, ...block.data.rows.map(r=>(r[ci]||'').length))+2}));
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Tabla');
    XLSX.writeFile(wb, 'tabla.xlsx');
  }
});

// Re-renderizar mensajes existentes que contengan tablas
document.querySelectorAll('.pjd-msg.assistant').forEach(m=>{
  const txt = m.textContent;
  if(/\|/.test(txt) && /\|\s*\n\s*\|?[\s\-\|:]+/.test(txt)){
    const blocks = parseMarkdownTables(txt);
    if(blocks.some(b=>b.type==='table')){
      m.innerHTML = '';
      m._blocks = blocks;
      blocks.forEach((b,idx)=>{
        if(b.type==='text') m.insertAdjacentHTML('beforeend', `<div>${escHtml(b.data).replace(/\n/g,'<br>')}</div>`);
        else m.insertAdjacentHTML('beforeend', `<div data-table-block="${idx}">${buildTableHtmlInline(b.data)}
          <div class="pjd-msg-actions">
            <button data-act="copy-table" data-idx="${idx}">📋 Copiar tabla</button>
            <button data-act="to-draft"   data-idx="${idx}">📝 Pasar al borrador</button>
            <button data-act="xlsx"       data-idx="${idx}">📊 Descargar Excel</button>
          </div></div>`);
      });
    }
  }
});

chatSend.addEventListener('click', sendChat);
chatInput.addEventListener('keydown', e=>{
  if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); sendChat(); }
});

async function sendChat(){
  const text = chatInput.value.trim();
  if(!text) return;
  chatInput.value=''; chatSend.disabled=true;
  const userDiv = document.createElement('div');
  userDiv.className='pjd-msg user';
  userDiv.textContent = text;
  chatBody.appendChild(userDiv);
  chatBody.scrollTop = chatBody.scrollHeight;
  const typing = document.createElement('div');
  typing.className='pjd-msg assistant';
  typing.textContent='Pensando…';
  chatBody.appendChild(typing);
  try{
    const r = await fetch(PROJECT_DATA.routes.chat,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':PROJECT_DATA.csrf,'Accept':'application/json'},
      body: JSON.stringify({message:text})
    });
    const j = await r.json();
    typing.remove();
    renderAssistantMessage(j.reply || j.error || 'Sin respuesta.');
  }catch(e){
    typing.remove();
    renderAssistantMessage('Error al contactar al asistente.');
  }
  chatSend.disabled=false;
}

chatReset.addEventListener('click', async ()=>{
  if(!confirm('¿Borrar todo el historial del chat?')) return;
  await fetch(PROJECT_DATA.routes.chatReset,{method:'DELETE',headers:{'X-CSRF-TOKEN':PROJECT_DATA.csrf}});
  chatBody.innerHTML = '<div class="pjd-msg assistant">Hola 👋 ¿En qué te ayudo?</div>';
});

/* ============= BORRADOR ============= */
document.getElementById('pjdDraftSave').addEventListener('click', async ()=>{
  const btn = document.getElementById('pjdDraftSave');
  btn.disabled = true; btn.textContent='Guardando...';
  await fetch(PROJECT_DATA.routes.draft,{
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':PROJECT_DATA.csrf},
    body: JSON.stringify({draft: document.getElementById('pjdEditor').innerHTML})
  });
  btn.textContent='✅ Guardado'; setTimeout(()=>{btn.textContent='Guardar borrador';btn.disabled=false;},1200);
});
document.getElementById('pjdDraftDownload').addEventListener('click',()=>{
  const html = '<html><head><meta charset="utf-8"></head><body>'+document.getElementById('pjdEditor').innerHTML+'</body></html>';
  const blob = new Blob([html],{type:'application/msword'});
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download='borrador.doc'; a.click();
});

/* ============= REPORTE ============= */
async function generateReport(){
  const empty = document.getElementById('pjdReportEmpty');
  if(empty) empty.innerHTML = '<p style="color:#1e3a5f">Generando reporte… esto puede tomar 30–60 segundos.</p>';
  const r = await fetch(PROJECT_DATA.routes.report,{method:'POST',headers:{'X-CSRF-TOKEN':PROJECT_DATA.csrf,'Accept':'application/json'}});
  const j = await r.json();
  if(j?.html){
    document.querySelector('.pjd-sub-pane[data-sub-pane="report"]').innerHTML =
      `<div class="pjd-card" id="pjdReportContent">${j.html}</div>
       <div class="pjd-save-bar">
         <button class="pjd-btn ghost" id="pjdReportDownload">Descargar reporte</button>
         <button class="pjd-btn" id="pjdReportRegen">Regenerar reporte</button>
       </div>`;
    bindReportButtons();
  } else if(empty){
    empty.innerHTML = '<p style="color:#dc2626">No se pudo generar el reporte.</p>';
  }
}
function bindReportButtons(){
  const dl = document.getElementById('pjdReportDownload');
  const rg = document.getElementById('pjdReportRegen');
  if(dl) dl.addEventListener('click',()=>{
    const html='<html><head><meta charset="utf-8"></head><body>'+document.getElementById('pjdReportContent').innerHTML+'</body></html>';
    const blob=new Blob([html],{type:'application/msword'});
    const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='reporte.doc'; a.click();
  });
  if(rg) rg.addEventListener('click',generateReport);
}
const reportGenBtn = document.getElementById('pjdReportGen');
if(reportGenBtn) reportGenBtn.addEventListener('click', generateReport);
bindReportButtons();

/* ============= CHECKLIST PRO ============= */
(function(){
  const PROJECT_ID = PROJECT_DATA.id;
  const CSRF = PROJECT_DATA.csrf;
  const USERS = window.PROJECT_USERS || [];
  let CHECKLIST = window.PROJECT_CHECKLIST || [];
  let FILTERS = {cumplimiento:new Set(), status:new Set(), prioridad:new Set()};
  let SEARCH = '';
  let EXPANDED = new Set();
  let SELECTED = new Set();

  function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function uid(){return 'r'+Math.random().toString(36).slice(2,9);}
  function fmtBytes(b){if(!b)return '0 B';const k=1024,s=['B','KB','MB','GB'];const i=Math.floor(Math.log(b)/Math.log(k));return (b/Math.pow(k,i)).toFixed(1)+' '+s[i];}
  function ensureIds(){ CHECKLIST.forEach(it=>{ if(!it._id) it._id=uid(); if(!it.adjuntos) it.adjuntos=[]; }); }

  function updateCounters(){
    const c={sin_revisar:0,no_cumple:0,parcial:0,cumple:0,pendiente:0,en_revision:0,aprobado:0,total:CHECKLIST.length};
    CHECKLIST.forEach(it=>{
      const cu=(it.cumplimiento||'sin_revisar').toLowerCase();
      const st=(it.status||'pendiente').toLowerCase();
      if(c[cu]!==undefined) c[cu]++;
      if(c[st]!==undefined) c[st]++;
    });
    document.getElementById('chkCntSinRevisar').textContent=c.sin_revisar;
    document.getElementById('chkCntNoCumple').textContent=c.no_cumple;
    document.getElementById('chkCntParcial').textContent=c.parcial;
    document.getElementById('chkCntCumple').textContent=c.cumple;
    document.getElementById('chkCntPendiente').textContent=c.pendiente;
    document.getElementById('chkCntEnRevision').textContent=c.en_revision;
    document.getElementById('chkCntAprobado').textContent=c.aprobado;
    document.getElementById('chkCntTotal').textContent=c.total;
  }

  function passesFilters(it){
    if(SEARCH){
      const blob=[it.requisito,it.descripcion,it.formato,it.categoria,it.fuente].join(' ').toLowerCase();
      if(!blob.includes(SEARCH)) return false;
    }
    function gOk(g, val){
      if(!FILTERS[g].size || FILTERS[g].has('__all__')) return true;
      return FILTERS[g].has((val||'').toLowerCase());
    }
    return gOk('cumplimiento', it.cumplimiento||'sin_revisar')
        && gOk('status', it.status||'pendiente')
        && gOk('prioridad', it.prioridad||'');
  }

  function pillObl(v){
    return v?`<span class="chk-pill chk-pill-obligatorio">Obligatorio</span>`:`<span class="chk-pill chk-pill-opcional">Opcional</span>`;
  }

  function renderDetail(it){
    const userOpts = ['<option value="">Sin asignar</option>'].concat(
      USERS.map(u=>`<option value="${u.id}" ${String(it.responsable_id||'')==String(u.id)?'selected':''}>${esc(u.name)}</option>`)
    ).join('');
    const userOptsRev = ['<option value="">Sin asignar</option>'].concat(
      USERS.map(u=>`<option value="${u.id}" ${String(it.revisor_id||'')==String(u.id)?'selected':''}>${esc(u.name)}</option>`)
    ).join('');

    const notes = it.notas
      ? `<div class="chk-notes-text" data-role="notes-view">${esc(it.notas)}</div>`
      : `<div class="chk-notes-empty" data-role="notes-view">No hay notas agregadas.</div>`;

    const adj = (it.adjuntos||[]).map((a,i)=>`
      <div class="chk-att-item">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
        <a href="${esc(a.url||'#')}" target="_blank">${esc(a.name||'archivo')}</a>
        <span style="color:#94a3b8;font-size:11px">${fmtBytes(a.size||0)}</span>
        <button class="chk-att-rm" data-rm-att="${i}" title="Quitar">×</button>
      </div>`).join('');

    return `
    <tr class="chk-detail-row" data-detail-of="${it._id}">
      <td colspan="9">
        <div class="chk-detail">
          <div class="chk-detail-block chk-full">
            <div class="chk-detail-label">Descripción</div>
            <div class="chk-detail-text">${esc(it.descripcion||it.requisito||'')}</div>
            ${it.fuente?`<div class="chk-detail-meta"><strong>Fuente:</strong> ${esc(it.fuente)}${it.pagina?` · <strong>Página de extracción:</strong> ${esc(String(it.pagina))}`:''}</div>`:''}
          </div>
          <div class="chk-detail-block">
            <div class="chk-detail-label">Prioridad</div>
            <div class="chk-prio-group">
              <button type="button" class="chk-prio-btn ${it.prioridad==='alta'?'is-active':''}" data-prio="alta">Alta</button>
              <button type="button" class="chk-prio-btn ${it.prioridad==='media'?'is-active':''}" data-prio="media">Media</button>
              <button type="button" class="chk-prio-btn ${it.prioridad==='baja'?'is-active':''}" data-prio="baja">Baja</button>
            </div>
          </div>
          <div class="chk-detail-block">
            <div class="chk-detail-label">Fecha límite</div>
            <input type="date" class="chk-date-input" data-fld="fecha_limite" value="${esc(it.fecha_limite||'')}">
          </div>
          <div class="chk-detail-block">
            <div class="chk-detail-label">Responsable</div>
            <select class="chk-select" data-fld="responsable_id">${userOpts}</select>
          </div>
          <div class="chk-detail-block">
            <div class="chk-detail-label">Revisor</div>
            <select class="chk-select" data-fld="revisor_id">${userOptsRev}</select>
          </div>
          <div class="chk-detail-block chk-full">
            <div class="chk-notes-head">
              <div class="chk-detail-label" style="margin:0">Notas</div>
              <button type="button" class="chk-icon-btn" data-act="edit-notes">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                ${it.notas?'Editar':'Agregar'}
              </button>
            </div>
            ${notes}
          </div>
          <div class="chk-detail-block chk-full">
            <div class="chk-notes-head">
              <div class="chk-detail-label" style="margin:0">Documentos Adjuntos</div>
              <label class="chk-icon-btn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                Adjuntar
                <input type="file" data-act="attach" multiple style="display:none">
              </label>
            </div>
            <div class="chk-att-list">${adj||'<div class="chk-notes-empty">No hay documentos adjuntos.</div>'}</div>
          </div>
          <div class="chk-detail-actions">
            <button type="button" class="chk-btn chk-btn-ghost" data-act="delete-item" style="color:#dc2626;border-color:#fca5a5">Eliminar requisito</button>
            <button type="button" class="chk-btn chk-btn-primary" data-act="save-item">Guardar cambios</button>
          </div>
        </div>
      </td>
    </tr>`;
  }

  function render(){
    ensureIds();
    updateCounters();
    const tb=document.getElementById('chkTbody');
    const rows=CHECKLIST.filter(passesFilters);
    if(!rows.length){ tb.innerHTML=''; document.getElementById('chkEmpty').style.display='block'; return; }
    document.getElementById('chkEmpty').style.display='none';
    tb.innerHTML = rows.map(it=>{
      const expanded = EXPANDED.has(it._id);
      const sel = SELECTED.has(it._id);
      const main = `
        <tr class="chk-row ${expanded?'is-expanded':''}" data-id="${it._id}">
          <td class="chk-td-chev"><span class="chk-chev"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg></span></td>
          <td class="chk-td-check"><input type="checkbox" class="chk-row-sel" ${sel?'checked':''}></td>
          <td data-col="requisito"><strong>${esc(it.requisito||'')}</strong></td>
          <td data-col="formato">${esc(it.formato||'—')}</td>
          <td data-col="categoria">${esc(it.categoria||'—')}</td>
          <td data-col="aplicabilidad">${esc(it.aplicabilidad||'—')}</td>
          <td data-col="obligatorio">${pillObl(!!it.obligatorio)}</td>
          <td data-col="cumplimiento">
            <select class="chk-cum-select" data-fld="cumplimiento" data-id="${it._id}" onclick="event.stopPropagation()">
              <option value="sin_revisar" ${(!it.cumplimiento||it.cumplimiento==='sin_revisar')?'selected':''}>Sin revisar</option>
              <option value="cumple" ${it.cumplimiento==='cumple'?'selected':''}>Cumple</option>
              <option value="parcial" ${it.cumplimiento==='parcial'?'selected':''}>Parcial</option>
              <option value="no_cumple" ${it.cumplimiento==='no_cumple'?'selected':''}>No Cumple</option>
            </select>
          </td>
          <td data-col="status">
            <select class="chk-st-select" data-fld="status" data-id="${it._id}" onclick="event.stopPropagation()">
              <option value="pendiente" ${(!it.status||it.status==='pendiente')?'selected':''}>Pendiente</option>
              <option value="en_revision" ${it.status==='en_revision'?'selected':''}>En revisión</option>
              <option value="aprobado" ${it.status==='aprobado'?'selected':''}>Aprobado</option>
            </select>
          </td>
        </tr>`;
      return main + (expanded ? renderDetail(it) : '');
    }).join('');
    applyColumnsVisibility();
  }

  function applyColumnsVisibility(){
    document.querySelectorAll('.chk-col-tgl').forEach(cb=>{
      const col=cb.dataset.col;
      const visible=cb.checked;
      document.querySelectorAll(`#chkTable [data-col="${col}"]`).forEach(el=>{ el.style.display = visible?'':'none'; });
    });
  }

  async function saveChecklist(){
    try{
      const payload = CHECKLIST.map(it=>{ const {_id,...rest}=it; return {_id,...rest}; });
      await fetch(PROJECT_DATA.routes.checklist,{
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        body:JSON.stringify({checklist: payload})
      });
    }catch(e){console.warn('No se pudo guardar checklist',e);}
  }

  document.getElementById('chkSearch').addEventListener('input',e=>{SEARCH=e.target.value.trim().toLowerCase();render();});

  document.querySelectorAll('.chk-counter').forEach(c=>{
    c.addEventListener('click',()=>{
      const cnt=c.dataset.counter;
      ['cumplimiento','status','prioridad'].forEach(g=>FILTERS[g].clear());
      if(['sin_revisar','cumple','parcial','no_cumple'].includes(cnt)) FILTERS.cumplimiento.add(cnt);
      else if(['pendiente','en_revision','aprobado'].includes(cnt)) FILTERS.status.add(cnt);
      document.querySelectorAll('.chk-counter').forEach(x=>x.classList.remove('is-active'));
      if(cnt!=='total') c.classList.add('is-active');
      document.querySelectorAll('.chk-flt').forEach(cb=>{
        const g=cb.dataset.group;
        cb.checked = cb.value==='__all__' ? !FILTERS[g].size : FILTERS[g].has(cb.value);
      });
      updateFilterBadge();
      render();
    });
  });

  function updateFilterBadge(){
    const total=FILTERS.cumplimiento.size+FILTERS.status.size+FILTERS.prioridad.size;
    const b=document.getElementById('chkFiltrosBadge');
    if(total){b.style.display='inline-block';b.textContent=total;} else b.style.display='none';
  }

  document.getElementById('chkBtnFiltros').addEventListener('click',e=>{
    e.stopPropagation();
    document.getElementById('chkColsPanel').classList.remove('is-open');
    document.getElementById('chkFiltrosPanel').classList.toggle('is-open');
  });
  document.getElementById('chkBtnColumnas').addEventListener('click',e=>{
    e.stopPropagation();
    document.getElementById('chkFiltrosPanel').classList.remove('is-open');
    document.getElementById('chkColsPanel').classList.toggle('is-open');
  });
  document.addEventListener('click',e=>{
    if(!e.target.closest('.chk-dropdown')){
      document.getElementById('chkFiltrosPanel').classList.remove('is-open');
      document.getElementById('chkColsPanel').classList.remove('is-open');
    }
  });

  document.querySelectorAll('.chk-flt').forEach(cb=>{
    cb.addEventListener('change',()=>{
      const g=cb.dataset.group;
      if(cb.value==='__all__'){
        FILTERS[g].clear();
        document.querySelectorAll(`.chk-flt[data-group="${g}"]`).forEach(x=>{x.checked=x.value==='__all__';});
      } else {
        if(cb.checked) FILTERS[g].add(cb.value); else FILTERS[g].delete(cb.value);
        const allCb=document.querySelector(`.chk-flt[data-group="${g}"][value="__all__"]`);
        if(allCb) allCb.checked=!FILTERS[g].size;
      }
      updateFilterBadge();
      render();
    });
  });

  document.querySelectorAll('.chk-col-tgl').forEach(cb=> cb.addEventListener('change',applyColumnsVisibility));

  document.getElementById('chkSelectAll').addEventListener('change',e=>{
    const v=e.target.checked;
    document.querySelectorAll('.chk-row-sel').forEach(cb=>{
      cb.checked=v;
      const id=cb.closest('tr').dataset.id;
      if(v) SELECTED.add(id); else SELECTED.delete(id);
    });
    updateExportCount();
  });

  function updateExportCount(){
    let bytes=0;
    SELECTED.forEach(id=>{
      const it=CHECKLIST.find(x=>x._id===id);
      (it?.adjuntos||[]).forEach(a=>bytes+=(a.size||0));
    });
    document.getElementById('chkExportCount').textContent=SELECTED.size;
    document.getElementById('chkExportSize').textContent=`archivos (${fmtBytes(bytes)})`;
  }

  document.getElementById('chkTbody').addEventListener('click',async function(e){
    const row = e.target.closest('tr.chk-row');
    if(row && !e.target.closest('select,input,button,.chk-td-check')){
      const id=row.dataset.id;
      if(EXPANDED.has(id)) EXPANDED.delete(id); else EXPANDED.add(id);
      render();
      return;
    }
    const detail = e.target.closest('tr.chk-detail-row');
    if(!detail) return;
    const id = detail.dataset.detailOf;
    const it = CHECKLIST.find(x=>x._id===id);
    if(!it) return;

    const prio = e.target.closest('.chk-prio-btn');
    if(prio){
      const p = prio.dataset.prio;
      it.prioridad = (it.prioridad===p)?'':p;
      render();
      saveChecklist();
      return;
    }

    const act = e.target.closest('[data-act]')?.dataset.act;
    if(act==='edit-notes'){
      const view = detail.querySelector('[data-role="notes-view"]');
      view.outerHTML = `<textarea class="chk-notes-edit" data-role="notes-edit">${esc(it.notas||'')}</textarea>`;
    }
    if(act==='save-item'){
      const fl = detail.querySelector('[data-fld="fecha_limite"]'); if(fl) it.fecha_limite=fl.value;
      const rp = detail.querySelector('[data-fld="responsable_id"]'); if(rp) it.responsable_id=rp.value||null;
      const rv = detail.querySelector('[data-fld="revisor_id"]'); if(rv) it.revisor_id=rv.value||null;
      const nt = detail.querySelector('[data-role="notes-edit"]'); if(nt) it.notas=nt.value;
      await saveChecklist();
      EXPANDED.delete(id);
      render();
    }
    if(act==='delete-item'){
      if(!confirm('¿Eliminar este requisito?')) return;
      CHECKLIST = CHECKLIST.filter(x=>x._id!==id);
      EXPANDED.delete(id);
      await saveChecklist();
      render();
    }
    const rmi = e.target.closest('[data-rm-att]');
    if(rmi){
      const idx = parseInt(rmi.dataset.rmAtt,10);
      it.adjuntos.splice(idx,1);
      render();
      saveChecklist();
    }
  });

  document.getElementById('chkTbody').addEventListener('change',function(e){
    if(e.target.classList.contains('chk-row-sel')){
      const id=e.target.closest('tr').dataset.id;
      if(e.target.checked) SELECTED.add(id); else SELECTED.delete(id);
      updateExportCount();
      return;
    }
    const fld = e.target.dataset?.fld;
    if(fld==='cumplimiento'||fld==='status'){
      const id = e.target.dataset.id;
      const it = CHECKLIST.find(x=>x._id===id);
      if(it){ it[fld] = e.target.value; updateCounters(); saveChecklist(); }
    }
    if(e.target.dataset?.act==='attach'){
      const detail = e.target.closest('tr.chk-detail-row');
      const id = detail?.dataset.detailOf;
      const it = CHECKLIST.find(x=>x._id===id);
      if(!it || !e.target.files?.length) return;
      const fd = new FormData();
      Array.from(e.target.files).forEach(f=>fd.append('files[]',f));
      fd.append('item_id', id);
      fd.append('_token', CSRF);
      fetch(PROJECT_DATA.routes.chkAttach,{method:'POST',body:fd,headers:{'X-CSRF-TOKEN':CSRF}})
        .then(r=>r.json()).then(json=>{
          if(json?.adjuntos){ it.adjuntos = (it.adjuntos||[]).concat(json.adjuntos); render(); saveChecklist(); }
        }).catch(()=>{
          Array.from(e.target.files).forEach(f=>it.adjuntos.push({name:f.name,size:f.size}));
          render();
        });
    }
  });

  document.getElementById('chkBtnAdd').addEventListener('click',()=>{
    const req = prompt('Nombre del nuevo requisito:');
    if(!req) return;
    const it = {_id:uid(),requisito:req,descripcion:'',formato:'',categoria:'',aplicabilidad:'',obligatorio:false,cumplimiento:'sin_revisar',status:'pendiente',prioridad:'',adjuntos:[]};
    CHECKLIST.push(it);
    EXPANDED.add(it._id);
    saveChecklist();
    render();
  });

  document.getElementById('chkBtnDescargar').addEventListener('click',()=>{
    const headers=['Requisito','Formato','Categoría','Aplicabilidad','Obligatorio','Cumplimiento','Status','Prioridad','Fecha límite','Notas'];
    const data = CHECKLIST.map(it=>[it.requisito,it.formato,it.categoria,it.aplicabilidad,it.obligatorio?'Sí':'No',it.cumplimiento,it.status,it.prioridad,it.fecha_limite,it.notas]);
    const ws = XLSX.utils.aoa_to_sheet([headers, ...data]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Checklist');
    XLSX.writeFile(wb, 'checklist.xlsx');
  });

  document.getElementById('chkBtnReanalisis').addEventListener('click',async()=>{
    if(!confirm('¿Volver a generar el checklist con IA? Se reemplazará el actual.')) return;
    const btn = document.getElementById('chkBtnReanalisis');
    btn.disabled=true; const old = btn.innerHTML; btn.innerHTML = 'Reanalizando…';
    try{
      const r=await fetch(PROJECT_DATA.routes.chkReanalyze,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});
      const j=await r.json();
      if(j?.checklist){ CHECKLIST=j.checklist; ensureIds(); render(); }
      else alert(j?.error || 'No se pudo reanalizar.');
    }catch(e){alert('Error al reanalizar.');}
    btn.disabled=false; btn.innerHTML = old;
  });

  ensureIds();
  render();
})();
</script>
@endpush