@extends('layouts.app')

@section('title', 'WMS · Picking Scanner')

@section('content')
@php
  $indexUrl = route('admin.wms.picking.v2');
  $updateUrlBase = url('/admin/wms/picking-v2');
  $todayLabel = now()->format('d/m/Y H:i:s');
@endphp

<div class="enterprise-wrap">
  <header class="e-header fade-in">
    <div class="e-header-brand">
      <div class="e-logo">WMS</div>
      <div class="e-divider"></div>
      <h1 class="e-title">Terminal de Picking y Área de Picking</h1>
      <span class="e-badge-live">
        <span class="live-dot"></span> En línea · {{ $todayLabel }}
      </span>
    </div>
    <div class="e-header-actions">
      <a href="{{ $indexUrl }}" class="btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Regresar al panel
      </a>
    </div>
  </header>

  <div class="e-layout-scanner fade-in" style="animation-delay: 0.1s;">
    <div class="e-col-sidebar">
      <div class="e-panel h-full flex-col">
        <div class="e-panel-header">
          <div>
            <h2 class="e-panel-title">Cola de operaciones</h2>
            <p class="e-panel-sub">Tareas activas asignadas al operador</p>
          </div>
          <div class="e-badge-count" id="activeCount">0</div>
        </div>
        <div id="taskList" class="e-task-list"></div>
      </div>
    </div>

    <div class="e-col-main">
      <div class="e-panel h-full" id="scannerPanel"></div>
    </div>
  </div>
</div>

<div id="scanToastStack" class="scan-toast-stack"></div>
@endsection

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap');

  :root {
    --bg-base: #FAFAFA;
    --bg-panel: #FFFFFF;
    --bg-terminal: #0A0A0A;

    --border-soft: #EAEAEA;
    --border-hard: #D1D1D1;
    --border-terminal: #333333;

    --text-primary: #111111;
    --text-secondary: #666666;
    --text-tertiary: #999999;
    --text-terminal: #00FF41;

    --c-blue: #0070F3;
    --c-green: #10B981;
    --c-red: #E00;
    --c-amber: #F5A623;

    --radius-sm: 6px;
    --radius-md: 10px;
    --radius-lg: 14px;

    --font-sans: 'Inter', -apple-system, sans-serif;
    --font-mono: 'JetBrains Mono', monospace;

    --shadow-elevation: 0 4px 24px rgba(0,0,0,0.04);
  }

  body {
    background-color: var(--bg-base);
    color: var(--text-primary);
    font-family: var(--font-sans);
    -webkit-font-smoothing: antialiased;
  }

  .t-mono { font-family: var(--font-mono); font-size: 0.8rem; letter-spacing: -0.02em; }
  .t-nums { font-feature-settings: "tnum"; font-variant-numeric: tabular-nums; font-family: var(--font-mono); }
  .t-micro { font-size: 0.75rem; }
  .t-strong { font-weight: 600; }
  .t-muted { color: var(--text-secondary); }

  .enterprise-wrap { max-width: 1600px; margin: 0 auto; padding: 2rem; display: flex; flex-direction: column; gap: 1.5rem; height: 100vh; }
  .e-layout-scanner { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 1.5rem; align-items: start; height: calc(100vh - 120px); }
  .e-col-sidebar { display: flex; flex-direction: column; height: 100%; }
  .e-col-main { display: flex; flex-direction: column; height: 100%; }
  .h-full { height: 100%; display: flex; flex-direction: column; }
  .flex-col { flex-direction: column; }

  @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
  .fade-in { animation: fadeIn 0.4s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; opacity: 0; }

  @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
  .live-dot { width: 6px; height: 6px; background-color: var(--c-green); border-radius: 50%; display: inline-block; margin-right: 4px; animation: pulse 2s infinite; }

  .e-header { display: flex; justify-content: space-between; align-items: center; }
  .e-header-brand { display: flex; align-items: center; gap: 1rem; }
  .e-logo { font-weight: 800; font-size: 1.2rem; letter-spacing: -0.05em; background: var(--text-primary); color: white; padding: 0.2rem 0.6rem; border-radius: 4px; }
  .e-divider { width: 1px; height: 20px; background: var(--border-hard); transform: rotate(15deg); }
  .e-title { font-size: 1.2rem; font-weight: 600; margin: 0; }
  .e-badge-live { display: inline-flex; align-items: center; font-size: 0.7rem; font-family: var(--font-mono); background: var(--bg-panel); border: 1px solid var(--border-soft); color: var(--text-secondary); padding: 0.3rem 0.6rem; border-radius: 4px; text-transform: uppercase; }

  .e-panel { background: var(--bg-panel); border: 1px solid var(--border-soft); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-elevation); }
  .e-panel-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center; background: #FFF; z-index: 10; }
  .e-panel-title { font-size: 0.95rem; font-weight: 600; margin: 0; letter-spacing: -0.01em; color: var(--text-primary); }
  .e-panel-sub { font-size: 0.8rem; color: var(--text-secondary); margin: 0.2rem 0 0 0; }
  .e-badge-count { font-family: var(--font-mono); font-size: 0.75rem; background: var(--text-primary); color: white; padding: 0.2rem 0.6rem; border-radius: 999px; font-weight: 700; }

  .btn-ghost, .btn-primary, .btn-terminal {
    display: inline-flex; align-items: center; justify-content: center; gap: 0.4rem;
    padding: 0.5rem 1rem; font-size: 0.85rem; font-weight: 500; border-radius: var(--radius-sm);
    cursor: pointer; transition: 0.15s ease; text-decoration: none; border: 1px solid transparent;
  }
  .btn-ghost { color: var(--text-secondary); }
  .btn-ghost:hover { background: var(--border-soft); color: var(--text-primary); }
  .btn-primary { background: var(--text-primary); color: white; width: 100%; font-weight: 600; }
  .btn-primary:hover { opacity: 0.85; }
  .btn-terminal { background: var(--bg-terminal); color: var(--text-terminal); font-family: var(--font-mono); border: 1px solid var(--border-terminal); }
  .btn-terminal:hover { background: #1a1a1a; border-color: var(--text-terminal); }

  .e-task-list { overflow-y: auto; flex: 1; padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; background: var(--bg-base); }
  .e-task-card {
    background: var(--bg-panel); border: 1px solid var(--border-soft); border-radius: var(--radius-md);
    padding: 1.25rem; transition: all 0.2s ease; cursor: pointer; position: relative;
  }
  .e-task-card:hover { border-color: var(--border-hard); box-shadow: var(--shadow-elevation); }
  .e-task-card.is-selected { border-color: var(--text-primary); box-shadow: 0 0 0 1px var(--text-primary); }

  .e-task-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
  .e-task-id { font-family: var(--font-mono); font-weight: 700; font-size: 0.9rem; color: var(--text-primary); }
  .e-task-ref { font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.2rem; }

  .e-status-tag { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; padding: 0.2rem 0.5rem; border-radius: 4px; letter-spacing: 0.05em; }
  .status-pending { background: #FFFBEB; color: var(--c-amber); border: 1px solid #FDE68A; }
  .status-progress { background: #EFF6FF; color: var(--c-blue); border: 1px solid #BFDBFE; }
  .status-completed { background: #ECFDF5; color: var(--c-green); border: 1px solid #A7F3D0; }

  .e-progress-wrap { margin-bottom: 0.75rem; }
  .e-progress-labels { display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.4rem; font-weight: 500; }
  .e-progress-track { height: 4px; background: var(--border-soft); border-radius: 999px; overflow: hidden; position: relative; }
  .e-progress-fill { height: 100%; border-radius: 999px; transition: width 0.3s ease; }
  .fill-blue { background: var(--text-primary); }
  .fill-green { background: var(--c-green); }

  .e-terminal-view { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
  .e-terminal-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border-soft); display: flex; gap: 2rem; background: var(--bg-base); }
  .e-kpi-block { display: flex; flex-direction: column; }
  .e-kpi-val { font-family: var(--font-mono); font-size: 1.5rem; font-weight: 700; color: var(--text-primary); line-height: 1.1; }
  .e-kpi-lbl { font-size: 0.7rem; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.05em; margin-top: 0.2rem; }

  .e-terminal-body { flex: 1; overflow-y: auto; padding: 1.5rem; display: flex; flex-direction: column; gap: 1.5rem; }

  .e-mode-tabs { display: flex; gap: 0.5rem; padding: 0.25rem; background: var(--bg-base); border-radius: var(--radius-sm); border: 1px solid var(--border-soft); width: max-content; }
  .e-mode-tab { padding: 0.4rem 1rem; font-size: 0.8rem; font-weight: 600; border-radius: 4px; border: none; background: transparent; color: var(--text-secondary); cursor: pointer; transition: 0.2s; }
  .e-mode-tab.is-active { background: var(--bg-panel); color: var(--text-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
  .e-mode-tab:disabled { opacity: 0.4; cursor: not-allowed; }

  .e-scanner-box { background: var(--bg-terminal); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: inset 0 2px 10px rgba(0,0,0,0.5); }
  .e-scanner-box.mode-stage { border: 1px solid var(--c-green); }
  .e-scanner-title { color: var(--text-secondary); font-family: var(--font-mono); font-size: 0.75rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem; }
  .e-scanner-title::before { content: ">"; color: var(--text-terminal); font-weight: bold; }

  .e-scanner-input-wrapper { position: relative; display: flex; align-items: center; }
  .e-scanner-input {
    width: 100%; background: transparent; border: none; border-bottom: 1px dashed var(--border-terminal);
    color: var(--text-terminal); font-family: var(--font-mono); font-size: 1.25rem; padding: 0.5rem 0; outline: none; transition: border 0.2s;
  }
  .e-scanner-input:focus { border-bottom-color: var(--text-terminal); }
  .e-scanner-input::placeholder { color: #333; }

  .e-stage-loc-group { display: flex; gap: 0.5rem; margin-top: 1rem; }
  .e-loc-input { flex: 1; background: #1a1a1a; border: 1px solid var(--border-terminal); color: white; font-family: var(--font-mono); padding: 0.5rem 1rem; border-radius: 4px; outline: none; }
  .e-loc-input:focus { border-color: var(--c-green); }

  .e-grid-title { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-primary); margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center; }
  .e-table { width: 100%; border-collapse: collapse; text-align: left; }
  .e-table th { font-size: 0.7rem; text-transform: uppercase; color: var(--text-tertiary); padding: 0.5rem; font-weight: 600; border-bottom: 1px solid var(--border-soft); }
  .e-table td { padding: 0.75rem 0.5rem; border-bottom: 1px solid var(--border-soft); vertical-align: middle; }
  .e-table tbody tr:hover { background: var(--bg-base); }
  .e-table tbody tr.row-done td { opacity: 0.6; }

  .qty-pill { display: inline-flex; font-family: var(--font-mono); font-size: 0.75rem; background: var(--bg-base); padding: 0.2rem 0.4rem; border-radius: 4px; border: 1px solid var(--border-soft); }
  .qty-pill.done { background: #ECFDF5; border-color: #A7F3D0; color: #047857; }

  .fast-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.15rem 0.45rem;
    border-radius: 999px;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
    color: #047857;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
  }

  .e-log-box { background: var(--bg-base); border: 1px solid var(--border-soft); border-radius: var(--radius-md); padding: 1rem; }
  .e-log-list { display: flex; flex-direction: column; gap: 0.5rem; max-height: 200px; overflow-y: auto; font-family: var(--font-mono); font-size: 0.75rem; }
  .e-log-row { display: grid; grid-template-columns: 70px auto 1fr; gap: 1rem; align-items: start; padding: 0.4rem; border-radius: 4px; border-left: 2px solid transparent; }
  .e-log-row:hover { background: var(--bg-panel); }
  .e-log-row.log-success { border-left-color: var(--c-green); color: var(--text-primary); }
  .e-log-row.log-error { border-left-color: var(--c-red); color: var(--c-red); }
  .e-log-row.log-warning { border-left-color: var(--c-amber); color: var(--c-amber); }
  .e-log-row.log-info { border-left-color: var(--c-blue); color: var(--text-secondary); }

  .log-time { color: var(--text-tertiary); }
  .log-badge { font-weight: 700; text-transform: uppercase; font-size: 0.65rem; padding: 0.1rem 0.3rem; border-radius: 2px; }
  .log-success .log-badge { background: #ECFDF5; color: var(--c-green); }
  .log-error .log-badge { background: #FEF2F2; color: var(--c-red); }
  .log-warning .log-badge { background: #FFFBEB; color: var(--c-amber); }
  .log-info .log-badge { background: #EFF6FF; color: var(--c-blue); }
  .log-msg { word-break: break-word; }

  .e-empty { padding: 4rem 2rem; text-align: center; color: var(--text-tertiary); display: flex; flex-direction: column; align-items: center; gap: 1rem; }

  .e-modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: none; align-items: center; justify-content: center; z-index: 1000; }
  .e-modal-backdrop.is-open { display: flex; }
  .e-modal { width: min(480px, 90%); background: var(--bg-panel); border-radius: var(--radius-lg); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); border: 1px solid var(--border-soft); overflow: hidden; }
  .e-modal-body { padding: 2rem; }
  .e-modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.5rem 0; }
  .e-modal-val { font-family: var(--font-mono); font-size: 1.1rem; font-weight: 700; }
  .e-modal-lbl { font-size: 0.75rem; color: var(--text-secondary); text-transform: uppercase; }

  .ff-batch-wrap { display: flex; flex-direction: column; gap: 1rem; }
  .ff-batch-card {
    border: 1px solid var(--border-soft);
    border-radius: var(--radius-md);
    background: var(--bg-panel);
    padding: 1rem;
  }
  .ff-batch-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
  }
  .ff-batch-title {
    font-family: var(--font-mono);
    font-weight: 700;
    font-size: 0.9rem;
    color: var(--text-primary);
  }
  .ff-batch-sub {
    font-size: 0.78rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
  }
  .ff-batch-kpis {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
  .ff-mini-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.55rem;
    border-radius: 999px;
    border: 1px solid var(--border-soft);
    background: var(--bg-base);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--text-primary);
  }

  .ff-box-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
    gap: 0.8rem;
  }
  .ff-box-card {
    border: 1px solid #D7DCE5;
    border-radius: 16px;
    padding: 0.95rem;
    background: #fff;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    box-shadow: 0 8px 20px rgba(15,23,42,0.04);
    transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
  }
  .ff-box-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(15,23,42,0.08);
  }
  .ff-box-card.is-free {
    background: linear-gradient(180deg, #FFFFFF 0%, #FAFBFC 100%);
    border-color: #D8DEE8;
  }
  .ff-box-card.is-picked {
    background: linear-gradient(180deg, #EFF6FF 0%, #DBEAFE 100%);
    border-color: #60A5FA;
    box-shadow: 0 10px 24px rgba(37,99,235,0.14);
  }
  .ff-box-card.is-staged {
    background: linear-gradient(180deg, #ECFDF5 0%, #D1FAE5 100%);
    border-color: #34D399;
    box-shadow: 0 10px 24px rgba(16,185,129,0.16);
  }
  .ff-box-code {
    font-family: var(--font-mono);
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--text-primary);
    word-break: break-word;
  }
  .ff-box-state {
    display: inline-flex;
    width: max-content;
    font-size: 0.66rem;
    font-weight: 800;
    text-transform: uppercase;
    padding: 0.24rem 0.5rem;
    border-radius: 999px;
    border: 1px solid transparent;
  }
  .ff-box-state.state-free {
    background: #F3F4F6;
    color: #6B7280;
    border-color: #E5E7EB;
  }
  .ff-box-state.state-picked {
    background: #DBEAFE;
    color: #1D4ED8;
    border-color: #60A5FA;
  }
  .ff-box-state.state-staged {
    background: #DCFCE7;
    color: #15803D;
    border-color: #4ADE80;
  }
  .ff-box-pieces-strong {
    font-family: var(--font-mono);
    font-size: 0.92rem;
    font-weight: 800;
    line-height: 1.1;
    letter-spacing: -0.02em;
  }
  .ff-box-pieces-strong.picked {
    color: #1D4ED8;
  }
  .ff-box-pieces-strong.staged {
    color: #15803D;
  }
  .ff-box-pieces-sub {
    font-family: var(--font-mono);
    font-size: 0.72rem;
    color: var(--text-secondary);
    line-height: 1.1;
  }

  .scan-toast-stack {
    position: fixed;
    top: 18px;
    right: 18px;
    z-index: 4000;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
    width: min(380px, calc(100vw - 24px));
  }
  .scan-toast {
    position: relative;
    overflow: hidden;
    border-radius: 14px;
    border: 1px solid #E5E7EB;
    background: #FFFFFF;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
    padding: 12px 14px 14px;
    transform: translateY(-8px);
    opacity: 0;
    animation: toastIn .18s ease forwards;
    pointer-events: auto;
  }
  .scan-toast.toast-out {
    animation: toastOut .18s ease forwards;
  }
  .scan-toast.success {
    border-color: #86EFAC;
    background: linear-gradient(180deg, #F0FDF4 0%, #ECFDF5 100%);
  }
  .scan-toast.error {
    border-color: #FCA5A5;
    background: linear-gradient(180deg, #FEF2F2 0%, #FFF1F2 100%);
  }
  .scan-toast.warning {
    border-color: #FCD34D;
    background: linear-gradient(180deg, #FFFBEB 0%, #FEF3C7 100%);
  }
  .scan-toast.info {
    border-color: #93C5FD;
    background: linear-gradient(180deg, #EFF6FF 0%, #DBEAFE 100%);
  }
  .scan-toast-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 4px;
  }
  .scan-toast-type {
    font-size: 0.68rem;
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .scan-toast.success .scan-toast-type { color: #15803D; }
  .scan-toast.error .scan-toast-type { color: #B91C1C; }
  .scan-toast.warning .scan-toast-type { color: #B45309; }
  .scan-toast.info .scan-toast-type { color: #1D4ED8; }
  .scan-toast-close {
    border: 0;
    background: transparent;
    color: #64748B;
    font-size: 1rem;
    line-height: 1;
    cursor: pointer;
    padding: 0;
  }
  .scan-toast-msg {
    font-size: 0.86rem;
    font-weight: 600;
    color: #111827;
    line-height: 1.35;
    padding-right: 18px;
  }
  .scan-toast-bar {
    position: absolute;
    left: 0;
    bottom: 0;
    height: 3px;
    width: 100%;
    transform-origin: left center;
    animation: toastBar 2.1s linear forwards;
  }
  .scan-toast.success .scan-toast-bar { background: #22C55E; }
  .scan-toast.error .scan-toast-bar { background: #EF4444; }
  .scan-toast.warning .scan-toast-bar { background: #F59E0B; }
  .scan-toast.info .scan-toast-bar { background: #3B82F6; }

  @keyframes toastIn {
    from { opacity: 0; transform: translateY(-8px) scale(.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
  }
  @keyframes toastOut {
    from { opacity: 1; transform: translateY(0) scale(1); }
    to { opacity: 0; transform: translateY(-8px) scale(.98); }
  }
  @keyframes toastBar {
    from { transform: scaleX(1); }
    to { transform: scaleX(0); }
  }

  @media (max-width: 1024px) {
    .e-layout-scanner { grid-template-columns: 1fr; height: auto; }
    .e-col-sidebar { height: 400px; }
    .ff-batch-head { flex-direction: column; }
    .ff-batch-kpis { justify-content: flex-start; }
  }
</style>
@endpush

@push('scripts')
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

  let tasks = @json($tasks ?? []);
  const products = @json($products ?? []);
  const operatorName = @json($operatorName ?? 'Operador');
  const updateUrlBase = @json($updateUrlBase);
  const selectedTaskFromServer = @json($selectedTask ?? null);
  const selectedTaskIdFromServer = @json($selectedTaskId ?? null);

  const taskList = document.getElementById('taskList');
  const scannerPanel = document.getElementById('scannerPanel');
  const activeCount = document.getElementById('activeCount');
  const toastStack = document.getElementById('scanToastStack');

  let selectedTask = null;
  let scanHistory = [];
  let currentMode = 'collect';
  let currentStagingLocation = '';
  let scanBusy = false;
  let scanAutoTimer = null;
  let lastToastKey = '';
  let lastToastAt = 0;

  function esc(str){
    return String(str ?? '').replace(/[&<>"']/g, m => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    }[m]));
  }

  function normalize(value){
    return String(value ?? '')
      .replace(/[´`‘’‚‛']/g, '-')
      .replace(/-+/g, '-')
      .trim()
      .toUpperCase();
  }

  function normalizeScanInputValue(value){
    return String(value ?? '')
      .replace(/[´`‘’‚‛']/g, '-')
      .replace(/-+/g, '-')
      .replace(/\s+/g, '')
      .toUpperCase();
  }

  function asNumber(value){
    const n = Number(value || 0);
    return Number.isFinite(n) ? n : 0;
  }

  function timeFmt(value){
    try{
      return new Date(value).toLocaleTimeString('es-MX', { hour12:false, hour:'2-digit', minute:'2-digit', second:'2-digit' });
    }catch(e){
      return '';
    }
  }

  function toastTypeLabel(type){
    switch(type){
      case 'success': return 'Correcto';
      case 'error': return 'Error';
      case 'warning': return 'Atención';
      default: return 'Info';
    }
  }

  function showToast(message, type = 'info'){
    if(!toastStack || !message) return;

    const now = Date.now();
    const key = `${type}|${String(message).trim()}`;
    if(key === lastToastKey && (now - lastToastAt) < 450){
      return;
    }
    lastToastKey = key;
    lastToastAt = now;

    const toast = document.createElement('div');
    toast.className = `scan-toast ${type}`;
    toast.innerHTML = `
      <div class="scan-toast-head">
        <div class="scan-toast-type">${esc(toastTypeLabel(type))}</div>
        <button type="button" class="scan-toast-close" aria-label="Cerrar">×</button>
      </div>
      <div class="scan-toast-msg">${esc(message)}</div>
      <div class="scan-toast-bar"></div>
    `;

    toastStack.prepend(toast);

    while(toastStack.children.length > 4){
      toastStack.removeChild(toastStack.lastChild);
    }

    const closeToast = () => {
      if(!toast.isConnected) return;
      toast.classList.add('toast-out');
      setTimeout(() => toast.remove(), 180);
    };

    toast.querySelector('.scan-toast-close')?.addEventListener('click', closeToast);
    setTimeout(closeToast, 2100);
  }

  function uniqueLabels(values){
    return [...new Set((Array.isArray(values) ? values : []).map(v => normalize(v)).filter(Boolean))];
  }

  function normalizeLabelList(value){
    return uniqueLabels(Array.isArray(value) ? value : []);
  }

  function normalizeAllocations(value){
    if(!Array.isArray(value)) return [];
    return value.map(entry => {
      if(typeof entry === 'string'){
        return { label: normalize(entry), pieces: 0 };
      }

      return {
        label: normalize(entry?.label || entry?.box_label || entry?.code || entry?.box || ''),
        pieces: asNumber(entry?.pieces ?? entry?.qty ?? entry?.quantity ?? 0)
      };
    }).filter(entry => entry.label);
  }

  function buildAllocationsFromLabels(labels, totalPieces, unitsPerBox){
    const normalized = uniqueLabels(labels);
    let remaining = asNumber(totalPieces);
    const perBox = asNumber(unitsPerBox);

    return normalized.map(label => {
      const pieces = perBox > 0 ? Math.min(perBox, Math.max(0, remaining)) : 0;
      remaining -= pieces;
      return { label, pieces };
    }).filter(entry => entry.label);
  }

  function cloneAllocations(list){
    return (Array.isArray(list) ? list : []).map(entry => ({
      label: normalize(entry.label),
      pieces: asNumber(entry.pieces)
    }));
  }

  function cloneTaskItems(items){
    return (Array.isArray(items) ? items : []).map(item => ({
      ...item,
      scanned_boxes: [...(Array.isArray(item.scanned_boxes) ? item.scanned_boxes : [])],
      staged_boxes: [...(Array.isArray(item.staged_boxes) ? item.staged_boxes : [])],
      box_allocations: cloneAllocations(item.box_allocations),
      stage_box_allocations: cloneAllocations(item.stage_box_allocations),
    }));
  }

  function allocationPiecesForLabel(allocations, label){
    const code = normalize(label);
    return (Array.isArray(allocations) ? allocations : []).reduce((sum, entry) => {
      return sum + (normalize(entry.label) === code ? asNumber(entry.pieces) : 0);
    }, 0);
  }

  function mergeAllocation(allocations, label, pieces){
    const code = normalize(label);
    const qty = asNumber(pieces);
    const next = cloneAllocations(allocations);

    if(!code || qty <= 0) return next;

    const idx = next.findIndex(entry => normalize(entry.label) === code);
    if(idx === -1){
      next.push({ label: code, pieces: qty });
      return next;
    }

    next[idx] = {
      ...next[idx],
      label: code,
      pieces: asNumber(next[idx].pieces) + qty
    };

    return next;
  }

  function uniqueLabelsFromAllocations(allocations){
    return uniqueLabels((Array.isArray(allocations) ? allocations : []).map(entry => entry.label));
  }

  function findProductMetaByAny(data){
    const sku = normalize(data?.product_sku || data?.sku);
    const barcode = normalize(data?.product_barcode || data?.barcode);
    const code = normalize(data?.product_code || data?.code);
    const name = normalize(data?.product_name || data?.name);

    return (Array.isArray(products) ? products : []).find(p =>
      (sku && normalize(p?.sku) === sku) ||
      (barcode && normalize(p?.barcode) === barcode) ||
      (code && normalize(p?.code) === code) ||
      (name && normalize(p?.name) === name)
    ) || null;
  }

  function resolveUnitsPerBox(item){
    const productMeta = findProductMetaByAny(item);

    const candidates = [
      item?.units_per_box,
      item?.fastflow_units_per_box,
      item?.pieces_per_box,
      item?.box_pieces,
      item?.qty_per_box,
      item?.pieces_box,
      item?.presentation_units,
      productMeta?.units_per_box,
      productMeta?.fastflow_units_per_box,
      productMeta?.pieces_per_box,
      productMeta?.box_pieces,
      productMeta?.qty_per_box,
      productMeta?.pieces_box,
      productMeta?.presentation_units,
    ];

    for(const candidate of candidates){
      const value = asNumber(candidate);
      if(value > 0) return value;
    }

    const totalBoxes = asNumber(
      item?.total_boxes ??
      item?.boxes_count ??
      item?.available_boxes_count ??
      productMeta?.total_boxes ??
      productMeta?.boxes_count
    );

    const totalPieces = asNumber(
      item?.available_stock ??
      item?.total_pieces ??
      item?.current_stock ??
      productMeta?.available_stock ??
      productMeta?.current_stock
    );

    if(totalBoxes > 0 && totalPieces > 0){
      const inferred = Math.floor(totalPieces / totalBoxes);
      if(inferred > 0) return inferred;
    }

    return 0;
  }

  function ensureTaskShape(task){
    if(!task) return null;

    const items = Array.isArray(task.items) ? task.items : [];
    const deliveries = Array.isArray(task.deliveries) ? task.deliveries : [];

    task.items = items.map(item => {
      const quantityRequired = asNumber(item.quantity_required);
      const quantityPicked = asNumber(item.quantity_picked);
      const quantityStaged = asNumber(item.quantity_staged);
      const unitsPerBox = resolveUnitsPerBox(item);

      let scannedBoxes = normalizeLabelList(item.scanned_boxes);
      let stagedBoxes = normalizeLabelList(item.staged_boxes);

      let boxAllocations = normalizeAllocations(item.box_allocations);
      let stageBoxAllocations = normalizeAllocations(item.stage_box_allocations);

      if(!boxAllocations.length && scannedBoxes.length){
        boxAllocations = buildAllocationsFromLabels(scannedBoxes, quantityPicked, unitsPerBox);
      }

      if(!stageBoxAllocations.length && stagedBoxes.length){
        stageBoxAllocations = buildAllocationsFromLabels(stagedBoxes, quantityStaged, unitsPerBox);
      }

      if(boxAllocations.length){
        scannedBoxes = uniqueLabelsFromAllocations(boxAllocations);
      }

      if(stageBoxAllocations.length){
        stagedBoxes = uniqueLabelsFromAllocations(stageBoxAllocations);
      }

      return {
        ...item,
        quantity_required: quantityRequired,
        quantity_picked: quantityPicked,
        quantity_staged: quantityStaged,
        picked: quantityPicked >= quantityRequired,
        staged: quantityStaged >= quantityRequired,
        staging_location_code: item.staging_location_code || '',
        product_sku: String(item.product_sku || '').toUpperCase(),
        product_barcode: String(item.product_barcode || '').toUpperCase(),
        product_code: String(item.product_code || '').toUpperCase(),
        batch_code: String(item.batch_code || '').toUpperCase(),
        location_code: String(item.location_code || ''),
        is_fastflow: !!item.is_fastflow || normalize(item.location_code) === 'FAST FLOW',
        units_per_box: unitsPerBox,
        scanned_boxes: scannedBoxes,
        staged_boxes: stagedBoxes,
        box_allocations: boxAllocations,
        stage_box_allocations: stageBoxAllocations,
      };
    });

    task.deliveries = deliveries;
    return task;
  }

  tasks = Array.isArray(tasks) ? tasks.map(ensureTaskShape) : [];

  function getActiveTasks(){
    return tasks.filter(t => t.status === 'pending' || t.status === 'in_progress');
  }

  function getRequiredQty(task){
    return (task?.items || []).reduce((sum, i) => sum + asNumber(i.quantity_required), 0);
  }

  function getCollectedQty(task){
    return (task?.items || []).reduce((sum, i) => sum + asNumber(i.quantity_picked), 0);
  }

  function getStagedQty(task){
    return (task?.items || []).reduce((sum, i) => sum + asNumber(i.quantity_staged), 0);
  }

  function getCollectProgress(task){
    const r = getRequiredQty(task);
    return r > 0 ? Math.round((getCollectedQty(task) / r) * 100) : 0;
  }

  function getStageProgress(task){
    const r = getRequiredQty(task);
    return r > 0 ? Math.round((getStagedQty(task) / r) * 100) : 0;
  }

  function isCollectDone(task){
    const items = task?.items || [];
    return items.length > 0 && items.every(item => asNumber(item.quantity_picked) >= asNumber(item.quantity_required));
  }

  function isStageDone(task){
    const items = task?.items || [];
    return items.length > 0 && items.every(item => asNumber(item.quantity_staged) >= asNumber(item.quantity_required));
  }

  function getTaskStatusClass(task){
    if(task.status === 'completed') return 'status-completed';
    if(task.status === 'in_progress' || isCollectDone(task)) return 'status-progress';
    return 'status-pending';
  }

  function getTaskStatusLabel(task){
    if(task.status === 'completed') return 'COMPLETADA';
    if(isStageDone(task)) return 'LISTA_PARA_CIERRE';
    if(isCollectDone(task)) return 'UBICANDO';
    if(task.status === 'in_progress') return 'ACTIVA';
    return 'EN_COLA';
  }

  function addHistory(log){
    scanHistory.unshift({
      timestamp: new Date().toISOString(),
      user: operatorName,
      ...log
    });

    showToast(log.message || 'Movimiento registrado.', log.type || 'info');
  }

  function extractFastFlowBatchFromLabel(scannedCode){
    const code = normalize(scannedCode);
    if(!code) return null;

    const match = code.match(/^(FF-\d{6}-\d{3})-C\d{3,}$/i);
    if(match && match[1]){
      return normalize(match[1]);
    }

    return null;
  }

  function extractFastFlowBoxLabel(scannedCode){
    const code = normalize(scannedCode);
    const match = code.match(/^(FF-\d{6}-\d{3}-C\d{3,})$/i);
    return match ? normalize(match[1]) : null;
  }

  function findProductByScan(barcode){
    const code = normalize(barcode);

    return products.find(p =>
      normalize(p.barcode) === code ||
      normalize(p.sku) === code ||
      normalize(p.code) === code ||
      normalize(p.name) === code
    );
  }

  function findTaskItemIndexByProduct(product){
    const s = normalize(product?.sku);
    const b = normalize(product?.barcode);
    const c = normalize(product?.code);

    return (selectedTask.items || []).findIndex(i =>
      normalize(i.product_sku) === s ||
      normalize(i.product_barcode) === b ||
      normalize(i.product_code) === c
    );
  }

  function getFastFlowPickedBoxes(item){
    return uniqueLabelsFromAllocations(item.box_allocations).length;
  }

  function getFastFlowStagedBoxes(item){
    return uniqueLabelsFromAllocations(item.stage_box_allocations).length;
  }

  function getBatchItemIndexes(task, batchCode){
    const batch = normalize(batchCode);
    return (task?.items || []).map((item, index) => {
      return item.is_fastflow && normalize(item.batch_code) === batch ? index : null;
    }).filter(index => index !== null);
  }

  function getBatchAllocationTotals(task, batchCode, type = 'collect'){
    const batch = normalize(batchCode);
    const out = {};

    (task?.items || []).forEach(item => {
      if(!item.is_fastflow || normalize(item.batch_code) !== batch) return;

      const allocations = type === 'stage'
        ? (Array.isArray(item.stage_box_allocations) ? item.stage_box_allocations : [])
        : (Array.isArray(item.box_allocations) ? item.box_allocations : []);

      allocations.forEach(entry => {
        const label = normalize(entry.label);
        if(!label) return;
        out[label] = asNumber(out[label]) + asNumber(entry.pieces);
      });
    });

    return out;
  }

  function getFastFlowGroups(task){
    const map = new Map();

    (task?.items || []).forEach((item, index) => {
      if(!item.is_fastflow || !normalize(item.batch_code)) return;

      const batch = normalize(item.batch_code);

      if(!map.has(batch)){
        map.set(batch, {
          batch_code: batch,
          product_sku: item.product_sku || '',
          product_name: item.product_name || '',
          itemIndexes: [],
          unitsPerBox: 0,
          availableStock: 0,
          totalBoxes: 0,
          requiredPieces: 0,
          pickedPieces: 0,
          stagedPieces: 0,
          explicitLabels: [],
        });
      }

      const group = map.get(batch);

      group.itemIndexes.push(index);
      group.unitsPerBox = Math.max(group.unitsPerBox, asNumber(item.units_per_box));
      group.availableStock = Math.max(group.availableStock, asNumber(item.available_stock || item.total_pieces || item.current_stock || 0));
      group.totalBoxes = Math.max(group.totalBoxes, asNumber(item.total_boxes || item.boxes_count || item.available_boxes_count || 0));
      group.requiredPieces += asNumber(item.quantity_required);
      group.pickedPieces += asNumber(item.quantity_picked);
      group.stagedPieces += asNumber(item.quantity_staged);

      const itemLabels = [
        ...(Array.isArray(item.box_labels) ? item.box_labels : []),
        ...(Array.isArray(item.available_boxes) ? item.available_boxes : []),
      ];
      group.explicitLabels.push(...itemLabels);

      if(!group.product_sku && item.product_sku) group.product_sku = item.product_sku;
      if(!group.product_name && item.product_name) group.product_name = item.product_name;
    });

    return Array.from(map.values()).map(group => {
      const collectedByLabel = getBatchAllocationTotals(task, group.batch_code, 'collect');
      const stagedByLabel = getBatchAllocationTotals(task, group.batch_code, 'stage');

      let totalBoxes = asNumber(group.totalBoxes);
      const unitsPerBox = asNumber(group.unitsPerBox);

      if(unitsPerBox > 0){
        totalBoxes = Math.max(
          totalBoxes,
          Math.ceil(asNumber(group.availableStock) / unitsPerBox),
          Math.ceil(asNumber(group.requiredPieces) / unitsPerBox),
          Object.keys(collectedByLabel).length,
          Object.keys(stagedByLabel).length
        );
      } else {
        totalBoxes = Math.max(
          totalBoxes,
          Object.keys(collectedByLabel).length,
          Object.keys(stagedByLabel).length
        );
      }

      const explicitLabels = uniqueLabels(group.explicitLabels);
      const generatedLabels = totalBoxes > 0
        ? Array.from({ length: totalBoxes }, (_, i) => `${group.batch_code}-C${String(i + 1).padStart(3, '0')}`)
        : [];

      const boxLabels = explicitLabels.length ? explicitLabels : generatedLabels;

      return {
        ...group,
        unitsPerBox,
        totalBoxes,
        boxLabels,
        collectedByLabel,
        stagedByLabel,
        pickedBoxes: Object.keys(collectedByLabel).length,
        stagedBoxes: Object.keys(stagedByLabel).length,
      };
    });
  }

  async function patchTask(id, payload, options = {}){
    const shouldRender = options.render !== false;

    const res = await fetch(`${updateUrlBase}/${id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    });

    const text = await res.text();
    let data = {};

    try {
      data = text ? JSON.parse(text) : {};
    } catch (e) {
      throw new Error('La respuesta del servidor no fue válida.');
    }

    if(!res.ok || !data.ok){
      throw new Error(data.message || 'No se pudo actualizar la tarea.');
    }

    const updatedTask = ensureTaskShape(data.task);
    tasks = tasks.map(t => Number(t.id) === Number(id) ? updatedTask : t);
    selectedTask = updatedTask;

    renderTaskList();
    if(shouldRender){
      renderPanel();
    }

    return updatedTask;
  }

  function renderTaskList(){
    const active = getActiveTasks();
    activeCount.textContent = active.length;

    if(!active.length){
      taskList.innerHTML = `<div class="e-empty"><div class="t-mono">NO_HAY_TAREAS_EN_COLA</div></div>`;
      return;
    }

    taskList.innerHTML = active.map(task => {
      const cProg = getCollectProgress(task);
      const sProg = getStageProgress(task);
      const selected = selectedTask && Number(selectedTask.id) === Number(task.id);

      return `
        <div class="e-task-card ${selected ? 'is-selected' : ''}" data-task-id="${task.id}">
          <div class="e-task-top">
            <div>
              <div class="e-task-id">${esc(task.task_number)}</div>
              <div class="e-task-ref">Referencia: ${esc(task.order_number || 'N/A')}</div>
            </div>
            <span class="e-status-tag ${getTaskStatusClass(task)}">${esc(getTaskStatusLabel(task))}</span>
          </div>

          <div class="e-progress-wrap">
            <div class="e-progress-labels"><span>Recolección</span><span class="t-nums">${cProg}%</span></div>
            <div class="e-progress-track"><div class="e-progress-fill fill-blue" style="width:${cProg}%"></div></div>
          </div>

          <div class="e-progress-wrap" style="margin-bottom:1rem;">
            <div class="e-progress-labels"><span>Área de picking</span><span class="t-nums">${sProg}%</span></div>
            <div class="e-progress-track"><div class="e-progress-fill fill-green" style="width:${sProg}%"></div></div>
          </div>

          ${!selected ? `<button type="button" class="btn-primary e-open-start" data-task-id="${task.id}">Montar en terminal</button>` : ''}
        </div>
      `;
    }).join('');
  }

  function renderEmptyPanel(){
    scannerPanel.innerHTML = `
      <div class="h-full flex-col" style="justify-content:center; align-items:center;">
        <div class="e-empty">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 7V4h3M20 7V4h-3M4 17v3h3M20 17v3h-3M7 12h10"/></svg>
          <div class="t-mono" style="font-size:1.1rem; color: var(--text-primary);">ESPERANDO_SELECCIÓN_DE_TAREA</div>
          <div class="t-micro t-muted">Selecciona una tarea de la cola para inicializar el escáner.</div>
        </div>
      </div>
    `;
  }

  function renderFastFlowGroups(task){
    const groups = getFastFlowGroups(task);
    if(!groups.length) return '';

    return `
      <div>
        <div class="e-grid-title">Cajas Fast Flow de la tarea</div>
        <div class="ff-batch-wrap">
          ${groups.map(group => `
            <div class="ff-batch-card">
              <div class="ff-batch-head">
                <div>
                  <div class="ff-batch-title">${esc(group.batch_code)}</div>
                  <div class="ff-batch-sub">
                    ${esc(group.product_name || 'Producto Fast Flow')}
                    ${group.product_sku ? ` · SKU ${esc(group.product_sku)}` : ''}
                    ${group.unitsPerBox > 0 ? ` · ${group.unitsPerBox} pzas/caja` : ''}
                  </div>
                </div>

                <div class="ff-batch-kpis">
                  <span class="ff-mini-pill">Cajas: ${group.totalBoxes}</span>
                  <span class="ff-mini-pill">Recolectadas: ${group.pickedBoxes}</span>
                  <span class="ff-mini-pill">Ubicadas: ${group.stagedBoxes}</span>
                  <span class="ff-mini-pill">Piezas: ${group.pickedPieces}/${group.requiredPieces}</span>
                </div>
              </div>

              <div class="ff-box-grid">
                ${group.boxLabels.map(label => {
                  const collected = asNumber(group.collectedByLabel[label]);
                  const staged = asNumber(group.stagedByLabel[label]);
                  const boxCapacity = asNumber(group.unitsPerBox);
                  const collectDenominator = boxCapacity > 0 ? boxCapacity : collected;
                  const stageDenominator = collected > 0 ? collected : collectDenominator;

                  let stateClass = 'is-free';
                  let badgeClass = 'state-free';
                  let stateLabel = 'Disponible';

                  if(staged > 0 && staged >= collected && collected > 0){
                    stateClass = 'is-staged';
                    badgeClass = 'state-staged';
                    stateLabel = 'Ubicada';
                  }else if(collected > 0){
                    stateClass = 'is-picked';
                    badgeClass = 'state-picked';
                    stateLabel = 'Recolectada';
                  }

                  return `
                    <div class="ff-box-card ${stateClass}">
                      <div class="ff-box-code">${esc(label)}</div>
                      <span class="ff-box-state ${badgeClass}">${esc(stateLabel)}</span>

                      <div class="ff-box-pieces-strong picked">
                        ${collected}/${collectDenominator || 0} pzas
                      </div>

                      <div class="ff-box-pieces-sub">
                        Ubicado: ${staged}/${stageDenominator || 0}
                      </div>
                    </div>
                  `;
                }).join('')}
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }

  function renderPanel(){
    if(!selectedTask){
      renderEmptyPanel();
      return;
    }

    selectedTask = ensureTaskShape(selectedTask);

    const items = Array.isArray(selectedTask.items) ? selectedTask.items : [];
    const stageDone = isStageDone(selectedTask);
    const collectDone = isCollectDone(selectedTask);

    scannerPanel.innerHTML = `
      <div class="e-terminal-view">
        <div class="e-terminal-header">
          <div class="e-kpi-block">
            <span class="e-kpi-lbl">Tarea</span>
            <span class="e-kpi-val">${esc(selectedTask.task_number)}</span>
          </div>
          <div class="e-kpi-block" style="border-left: 1px solid var(--border-soft); padding-left: 2rem;">
            <span class="e-kpi-lbl">Recolección</span>
            <span class="e-kpi-val">${getCollectedQty(selectedTask)}/${getRequiredQty(selectedTask)}</span>
          </div>
          <div class="e-kpi-block" style="border-left: 1px solid var(--border-soft); padding-left: 2rem;">
            <span class="e-kpi-lbl">Área de picking</span>
            <span class="e-kpi-val">${getStagedQty(selectedTask)}/${getRequiredQty(selectedTask)}</span>
          </div>
        </div>

        <div class="e-terminal-body">
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div class="e-mode-tabs">
              <button class="e-mode-tab ${currentMode === 'collect' ? 'is-active' : ''}" data-mode="collect">Modo recolección</button>
              <button class="e-mode-tab ${currentMode === 'stage' ? 'is-active' : ''}" data-mode="stage" ${collectDone ? '' : 'disabled'}>Modo área de picking</button>
            </div>
            ${currentMode === 'stage' && currentStagingLocation ? `<span class="e-badge-live">Ubicación destino: ${esc(currentStagingLocation)}</span>` : ''}
          </div>

          <div class="e-scanner-box ${currentMode === 'stage' ? 'mode-stage' : ''}">
            <div class="e-scanner-title">
              ${currentMode === 'collect'
                ? 'Escanea producto o etiqueta Fast Flow para registrar recolección'
                : 'Escanea producto o etiqueta Fast Flow para registrar ubicación'}
            </div>

            <form id="scanForm" class="e-scanner-input-wrapper">
              <input
                type="text"
                id="scanInput"
                class="e-scanner-input"
                placeholder="Escanea aquí..."
                autocomplete="off"
                autocapitalize="off"
                autocorrect="off"
                spellcheck="false"
              >
              <button type="submit" style="display:none" aria-hidden="true" tabindex="-1">submit</button>
            </form>

            <div class="t-micro" style="margin-top:10px; color:#7d7d7d;">
              También acepta etiquetas Fast Flow como: FF-250318-001-C001
            </div>

            ${currentMode === 'stage' ? `
              <div class="e-stage-loc-group">
                <input type="text" id="stagingLocationInput" class="e-loc-input" placeholder="Escanea o escribe ubicación destino">
                <button type="button" id="setStagingLocationBtn" class="btn-terminal">Registrar ubicación</button>
              </div>
            ` : ''}
          </div>

          <div>
            <div class="e-grid-title">Requerimientos de la tarea</div>
            <table class="e-table">
              <thead>
                <tr>
                  <th>SKU</th>
                  <th>Descripción</th>
                  <th>Origen</th>
                  <th>Destino</th>
                  <th>Recolectado</th>
                  <th>Ubicado</th>
                </tr>
              </thead>
              <tbody>
                ${items.map(item => {
                  const req = asNumber(item.quantity_required);
                  const pck = asNumber(item.quantity_picked);
                  const stg = asNumber(item.quantity_staged);
                  const isDone = stg >= req;

                  const isFast = !!item.is_fastflow;
                  const unitsPerBox = asNumber(item.units_per_box);
                  const pickedBoxes = isFast ? getFastFlowPickedBoxes(item) : 0;
                  const stagedBoxes = isFast ? getFastFlowStagedBoxes(item) : 0;

                  return `
                    <tr class="${isDone ? 'row-done' : ''}">
                      <td class="t-mono t-strong">
                        ${esc(item.product_sku || '-')}
                        ${isFast ? `<div style="margin-top:6px;"><span class="fast-chip">Fast Flow</span></div>` : ''}
                      </td>
                      <td class="t-micro">
                        ${esc(item.product_name)}
                        ${item.batch_code ? `<div class="t-mono t-micro t-muted" style="margin-top:4px;">Lote: ${esc(item.batch_code)}</div>` : ''}
                        ${isFast && unitsPerBox > 0 ? `<div class="t-mono t-micro t-muted" style="margin-top:4px;">${unitsPerBox} piezas por caja</div>` : ''}
                      </td>
                      <td class="t-mono t-muted">${esc(item.location_code || 'N/A')}</td>
                      <td class="t-mono t-muted">${esc(item.staging_location_code || 'PENDIENTE')}</td>
                      <td>
                        <span class="qty-pill ${pck >= req ? 'done' : ''}">${pck}/${req} piezas</span>
                        ${isFast ? `<div class="t-micro t-muted" style="margin-top:4px;">Etiquetas: ${pickedBoxes}</div>` : ''}
                      </td>
                      <td>
                        <span class="qty-pill ${stg >= req ? 'done' : ''}">${stg}/${req} piezas</span>
                        ${isFast ? `<div class="t-micro t-muted" style="margin-top:4px;">Etiquetas: ${stagedBoxes}</div>` : ''}
                      </td>
                    </tr>
                  `;
                }).join('')}
              </tbody>
            </table>
          </div>

          ${renderFastFlowGroups(selectedTask)}

          ${scanHistory.length ? `
            <div class="e-log-box">
              <div class="e-grid-title">Bitácora en vivo</div>
              <div class="e-log-list">
                ${scanHistory.slice(0, 15).map(log => `
                  <div class="e-log-row log-${log.type}">
                    <span class="log-time">${timeFmt(log.timestamp)}</span>
                    <span class="log-badge">${log.type}</span>
                    <span class="log-msg">
                      ${esc(log.message)}
                      ${log.sku ? ` [SKU: ${esc(log.sku)}]` : ''}
                      ${log.location ? ` [UBICACIÓN: ${esc(log.location)}]` : ''}
                      ${log.batch ? ` [LOTE: ${esc(log.batch)}]` : ''}
                      ${log.label ? ` [ETIQUETA: ${esc(log.label)}]` : ''}
                    </span>
                  </div>
                `).join('')}
              </div>
            </div>
          ` : ''}

          <div style="display:flex; gap:1rem; margin-top: auto;">
            ${stageDone ? `<button type="button" class="btn-primary" id="forceCompleteBtn" style="background:var(--c-green);">Cerrar tarea</button>` : ''}
            <button type="button" class="btn-ghost" id="resetFocusBtn" style="border: 1px solid var(--border-soft);">Reenfocar escáner</button>
          </div>
        </div>
      </div>
    `;

    bindPanelEvents();
  }

  async function submitCurrentScan(){
    const scanInput = document.getElementById('scanInput');
    if(!scanInput || !selectedTask || scanBusy) return;

    const fixedValue = normalizeScanInputValue(scanInput.value || '');
    if(scanInput.value !== fixedValue){
      scanInput.value = fixedValue;
    }

    const barcode = normalize(fixedValue);

    if(!barcode){
      scanInput.focus();
      return;
    }

    scanBusy = true;

    try{
      if(currentMode === 'collect'){
        await handleCollectScan(barcode);
      } else {
        await handleStageScan(barcode);
      }

      scanInput.value = '';
      renderPanel();
    }catch(error){
      addHistory({
        type: 'error',
        message: 'Error al procesar el escaneo: ' + (error?.message || 'Error desconocido'),
        label: barcode
      });
      renderPanel();
    }finally{
      scanBusy = false;
      setTimeout(() => {
        const input = document.getElementById('scanInput');
        if(document.activeElement?.id !== 'stagingLocationInput'){
          input?.focus();
        }
      }, 20);
    }
  }

  function bindPanelEvents(){
    const scanInput = document.getElementById('scanInput');
    const scanForm = document.getElementById('scanForm');

    if(scanInput){
      setTimeout(() => scanInput.focus(), 20);

      scanInput.addEventListener('keydown', async function(e){
        if(e.key === 'Enter' || e.key === 'NumpadEnter'){
          e.preventDefault();
          e.stopPropagation();
          clearTimeout(scanAutoTimer);
          await submitCurrentScan();
          return;
        }

        if(e.key === 'Tab'){
          const currentValue = normalize(scanInput.value || '');
          if(currentValue !== ''){
            e.preventDefault();
            e.stopPropagation();
            clearTimeout(scanAutoTimer);
            await submitCurrentScan();
          }
        }
      });

      scanInput.addEventListener('input', function(){
        clearTimeout(scanAutoTimer);

        const fixedValue = normalizeScanInputValue(scanInput.value || '');
        if(scanInput.value !== fixedValue){
          scanInput.value = fixedValue;
        }

        const currentValue = normalize(fixedValue);
        if(!currentValue) return;

        const looksLikeFastFlow = /^FF-\d{6}-\d{3}-C\d{3,}$/i.test(currentValue);
        const looksLikeShortCode = currentValue.length >= 3;

        if(looksLikeFastFlow){
          submitCurrentScan();
          return;
        }

        if(looksLikeShortCode){
          scanAutoTimer = setTimeout(async () => {
            const latestFixedValue = normalizeScanInputValue(scanInput.value || '');
            if(scanInput.value !== latestFixedValue){
              scanInput.value = latestFixedValue;
            }

            const freshValue = normalize(latestFixedValue);
            if(!freshValue) return;

            await submitCurrentScan();
          }, 45);
        }
      });

      scanInput.addEventListener('blur', function(){
        setTimeout(() => {
          const active = document.activeElement;
          if(active && (active.id === 'stagingLocationInput' || active.id === 'setStagingLocationBtn')){
            return;
          }
          document.getElementById('scanInput')?.focus();
        }, 20);
      });
    }

    if(scanForm){
      scanForm.addEventListener('submit', async function(e){
        e.preventDefault();
        e.stopPropagation();
        clearTimeout(scanAutoTimer);
        await submitCurrentScan();
      });
    }

    document.querySelectorAll('.e-mode-tab[data-mode]').forEach(btn => {
      btn.addEventListener('click', function(){
        const mode = this.dataset.mode;

        if(mode === 'stage' && !isCollectDone(selectedTask)){
          addHistory({ type: 'warning', message: 'Primero debes completar toda la recolección.' });
          renderPanel();
          return;
        }

        currentMode = mode;
        renderPanel();
      });
    });

    const setStagingLocationBtn = document.getElementById('setStagingLocationBtn');
    if(setStagingLocationBtn){
      setStagingLocationBtn.addEventListener('click', registerStagingLocation);
    }

    const locInput = document.getElementById('stagingLocationInput');
    if(locInput){
      locInput.addEventListener('keydown', e => {
        if(e.key === 'Enter' || e.key === 'NumpadEnter'){
          e.preventDefault();
          registerStagingLocation();
        }
      });

      locInput.addEventListener('input', function(){
        const fixedValue = normalizeScanInputValue(locInput.value || '');
        if(locInput.value !== fixedValue){
          locInput.value = fixedValue;
        }
      });
    }

    const forceCompleteBtn = document.getElementById('forceCompleteBtn');
    if(forceCompleteBtn){
      forceCompleteBtn.addEventListener('click', completeTask);
    }

    const resetFocusBtn = document.getElementById('resetFocusBtn');
    if(resetFocusBtn){
      resetFocusBtn.addEventListener('click', () => {
        const input = document.getElementById('scanInput');
        input?.focus();
      });
    }
  }

  function openStartModal(taskId){
    const task = tasks.find(t => Number(t.id) === Number(taskId));
    if(!task) return;

    const existing = document.getElementById('eStartModal');
    if(existing) existing.remove();

    const wrap = document.createElement('div');
    wrap.className = 'e-modal-backdrop is-open';
    wrap.id = 'eStartModal';
    wrap.innerHTML = `
      <div class="e-modal">
        <div class="e-modal-body">
          <div class="e-grid-title" style="font-size:1.1rem;">Inicializar tarea</div>
          <div class="t-muted t-micro">Tarea: ${esc(task.task_number)} | Operador: ${esc(operatorName)}</div>

          <div class="e-modal-grid">
            <div><div class="e-modal-lbl">Recolección</div><div class="e-modal-val">${getCollectProgress(task)}%</div></div>
            <div><div class="e-modal-lbl">Área de picking</div><div class="e-modal-val">${getStageProgress(task)}%</div></div>
          </div>

          <div style="display:flex; gap:1rem;">
            <button class="btn-primary" id="confirmStartBtn">Montar tarea</button>
            <button class="btn-ghost" style="border:1px solid var(--border-soft);" data-close-modal>Cancelar</button>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(wrap);

    wrap.addEventListener('click', e => {
      if(e.target === wrap || e.target.hasAttribute('data-close-modal')) wrap.remove();
    });

    document.getElementById('confirmStartBtn')?.addEventListener('click', () => {
      wrap.remove();
      selectTask(taskId);
    });
  }

  async function selectTask(taskId){
    const task = tasks.find(t => Number(t.id) === Number(taskId));
    if(!task) return;

    selectedTask = ensureTaskShape(task);
    currentMode = isCollectDone(selectedTask) ? 'stage' : 'collect';
    currentStagingLocation = '';

    if(selectedTask.status === 'pending'){
      try{
        await patchTask(selectedTask.id, {
          status: 'in_progress',
          started_at: selectedTask.started_at || new Date().toISOString()
        });
      }catch(e){
        addHistory({ type: 'error', message: 'No se pudo iniciar la tarea: ' + e.message });
      }
      return;
    }

    renderTaskList();
    renderPanel();
  }

  function registerStagingLocation(){
    const input = document.getElementById('stagingLocationInput');
    const value = normalize(input?.value || '');

    if(!value){
      addHistory({ type: 'warning', message: 'La ubicación destino no es válida.' });
      renderPanel();
      return;
    }

    currentStagingLocation = value;
    addHistory({ type: 'info', message: 'Ubicación destino registrada.', location: value });
    renderPanel();
  }

  async function handleCollectScan(barcode){
    const batch = extractFastFlowBatchFromLabel(barcode);
    const label = extractFastFlowBoxLabel(barcode);

    if(batch && label){
      const itemIndexes = getBatchItemIndexes(selectedTask, batch);

      if(!itemIndexes.length){
        addHistory({
          type: 'error',
          message: 'La etiqueta Fast Flow no pertenece a esta tarea.',
          label: barcode,
          batch
        });
        return;
      }

      const sourceItem = selectedTask.items[itemIndexes[0]];
      const unitsPerBox = asNumber(resolveUnitsPerBox(sourceItem));

      if(unitsPerBox <= 0){
        addHistory({
          type: 'error',
          message: 'El lote Fast Flow no tiene piezas por caja configuradas.',
          sku: sourceItem?.product_sku,
          batch,
          label
        });
        return;
      }

      const batchAllocations = getBatchAllocationTotals(selectedTask, batch, 'collect');
      const alreadyAllocated = asNumber(batchAllocations[label]);

      if(alreadyAllocated >= unitsPerBox){
        addHistory({
          type: 'warning',
          message: 'Esa caja Fast Flow ya fue escaneada completamente en recolección.',
          sku: sourceItem?.product_sku,
          batch,
          label
        });
        return;
      }

      const items = cloneTaskItems(selectedTask.items);
      let remainingInLabel = unitsPerBox - alreadyAllocated;
      let distributed = 0;
      let affectedSku = sourceItem?.product_sku || '';

      for(const idx of itemIndexes){
        const item = { ...items[idx] };
        const required = asNumber(item.quantity_required);
        const picked = asNumber(item.quantity_picked);
        const pending = Math.max(0, required - picked);

        if(pending <= 0) continue;
        if(remainingInLabel <= 0) break;

        const take = Math.min(pending, remainingInLabel);
        if(take <= 0) continue;

        item.quantity_picked = picked + take;
        item.picked = item.quantity_picked >= required;
        item.collected_at = new Date().toISOString();
        item.box_allocations = mergeAllocation(item.box_allocations, label, take);
        item.scanned_boxes = uniqueLabels([...item.scanned_boxes, label]);
        items[idx] = item;

        remainingInLabel -= take;
        distributed += take;
        if(!affectedSku) affectedSku = item.product_sku || '';
      }

      if(distributed <= 0){
        addHistory({
          type: 'warning',
          message: 'Ese lote Fast Flow ya alcanzó la cantidad requerida.',
          sku: affectedSku,
          batch,
          label
        });
        return;
      }

      const allPicked = items.every(i => asNumber(i.quantity_picked) >= asNumber(i.quantity_required));

      await patchTask(selectedTask.id, {
        items,
        status: 'in_progress',
        completed_at: null
      }, { render: false });

      addHistory({
        type: 'success',
        message: `Caja Fast Flow recolectada (+${distributed} piezas)`,
        sku: affectedSku,
        batch,
        label,
        location: sourceItem?.location_code || 'FAST FLOW'
      });

      if(allPicked){
        currentMode = 'stage';
        addHistory({ type: 'info', message: 'Toda la recolección quedó completa. Puedes pasar al área de picking.' });
      }

      return;
    }

    const product = findProductByScan(barcode);
    if(!product){
      addHistory({ type: 'error', message: 'Código no encontrado.', sku: barcode });
      return;
    }

    const items = cloneTaskItems(selectedTask.items);
    const idx = findTaskItemIndexByProduct(product);

    if(idx === -1){
      addHistory({ type: 'error', message: 'Ese producto no pertenece a esta tarea.', sku: product.sku || barcode });
      return;
    }

    const item = { ...items[idx] };
    const req = asNumber(item.quantity_required);
    const pck = asNumber(item.quantity_picked);

    if(pck >= req){
      addHistory({ type: 'warning', message: 'Ese producto ya alcanzó la cantidad requerida.', sku: product.sku });
      return;
    }

    item.quantity_picked = pck + 1;
    item.picked = item.quantity_picked >= req;
    item.collected_at = new Date().toISOString();
    items[idx] = item;

    const allPicked = items.every(i => asNumber(i.quantity_picked) >= asNumber(i.quantity_required));

    await patchTask(selectedTask.id, {
      items,
      status: 'in_progress',
      completed_at: null
    }, { render: false });

    addHistory({
      type: 'success',
      message: 'Recolección registrada +1',
      sku: item.product_sku,
      location: item.location_code
    });

    if(allPicked){
      currentMode = 'stage';
      addHistory({ type: 'info', message: 'Toda la recolección quedó completa. Puedes pasar al área de picking.' });
    }
  }

  async function handleStageScan(barcode){
    if(!isCollectDone(selectedTask)){
      addHistory({ type: 'warning', message: 'Primero debes terminar toda la recolección.' });
      return;
    }

    if(!currentStagingLocation){
      addHistory({ type: 'warning', message: 'Primero registra la ubicación destino del área de picking.' });
      return;
    }

    const batch = extractFastFlowBatchFromLabel(barcode);
    const label = extractFastFlowBoxLabel(barcode);

    if(batch && label){
      const itemIndexes = getBatchItemIndexes(selectedTask, batch);

      if(!itemIndexes.length){
        addHistory({
          type: 'error',
          message: 'La etiqueta Fast Flow no pertenece a esta tarea.',
          label: barcode,
          batch
        });
        return;
      }

      const sourceItem = selectedTask.items[itemIndexes[0]];
      const collectedTotals = getBatchAllocationTotals(selectedTask, batch, 'collect');
      const stagedTotals = getBatchAllocationTotals(selectedTask, batch, 'stage');

      const collectedFromLabel = asNumber(collectedTotals[label]);
      const stagedFromLabel = asNumber(stagedTotals[label]);

      if(collectedFromLabel <= 0){
        addHistory({
          type: 'warning',
          message: 'Esa caja no ha sido recolectada todavía.',
          sku: sourceItem?.product_sku,
          batch,
          label
        });
        return;
      }

      if(stagedFromLabel >= collectedFromLabel){
        addHistory({
          type: 'warning',
          message: 'Esa caja Fast Flow ya fue ubicada completamente.',
          sku: sourceItem?.product_sku,
          batch,
          label
        });
        return;
      }

      const items = cloneTaskItems(selectedTask.items);
      let remainingToStage = collectedFromLabel - stagedFromLabel;
      let distributed = 0;
      let affectedSku = sourceItem?.product_sku || '';

      for(const idx of itemIndexes){
        const item = { ...items[idx] };

        const collectedOnItem = allocationPiecesForLabel(item.box_allocations, label);
        const stagedOnItem = allocationPiecesForLabel(item.stage_box_allocations, label);
        const availableFromLabelOnItem = Math.max(0, collectedOnItem - stagedOnItem);
        const totalPendingOnItem = Math.max(0, asNumber(item.quantity_picked) - asNumber(item.quantity_staged));

        if(availableFromLabelOnItem <= 0 || totalPendingOnItem <= 0) continue;
        if(remainingToStage <= 0) break;

        const take = Math.min(availableFromLabelOnItem, totalPendingOnItem, remainingToStage);
        if(take <= 0) continue;

        item.quantity_staged = asNumber(item.quantity_staged) + take;
        item.staged = item.quantity_staged >= asNumber(item.quantity_required);
        item.staging_location_code = currentStagingLocation;
        item.staged_at = new Date().toISOString();
        item.stage_box_allocations = mergeAllocation(item.stage_box_allocations, label, take);
        item.staged_boxes = uniqueLabels([...item.staged_boxes, label]);
        items[idx] = item;

        remainingToStage -= take;
        distributed += take;
        if(!affectedSku) affectedSku = item.product_sku || '';
      }

      if(distributed <= 0){
        addHistory({
          type: 'warning',
          message: 'No hay piezas recolectadas disponibles de esa caja para ubicar.',
          sku: affectedSku,
          batch,
          label
        });
        return;
      }

      const allStaged = items.every(i => asNumber(i.quantity_staged) >= asNumber(i.quantity_required));

      await patchTask(selectedTask.id, {
        items,
        status: allStaged ? 'completed' : 'in_progress',
        completed_at: allStaged ? new Date().toISOString() : null
      }, { render: false });

      addHistory({
        type: 'success',
        message: `Caja Fast Flow ubicada (+${distributed} piezas)`,
        sku: affectedSku,
        batch,
        label,
        location: currentStagingLocation
      });

      if(allStaged){
        addHistory({ type: 'info', message: 'Toda la mercancía quedó ubicada. La tarea está lista para cierre.' });
      }

      return;
    }

    const product = findProductByScan(barcode);
    if(!product){
      addHistory({ type: 'error', message: 'Código no encontrado.', sku: barcode });
      return;
    }

    const items = cloneTaskItems(selectedTask.items);
    const idx = findTaskItemIndexByProduct(product);

    if(idx === -1){
      addHistory({ type: 'error', message: 'Ese producto no pertenece a esta tarea.', sku: product.sku || barcode });
      return;
    }

    const item = { ...items[idx] };
    const req = asNumber(item.quantity_required);
    const pck = asNumber(item.quantity_picked);
    const stg = asNumber(item.quantity_staged);

    if(pck <= stg){
      addHistory({ type: 'warning', message: 'No hay unidades recolectadas disponibles para ubicar.', sku: product.sku });
      return;
    }

    if(stg >= req){
      addHistory({ type: 'warning', message: 'Ese producto ya quedó completamente ubicado.', sku: product.sku });
      return;
    }

    item.quantity_staged = stg + 1;
    item.staged = item.quantity_staged >= req;
    item.staging_location_code = currentStagingLocation;
    item.staged_at = new Date().toISOString();
    items[idx] = item;

    const allStaged = items.every(i => asNumber(i.quantity_staged) >= asNumber(i.quantity_required));

    await patchTask(selectedTask.id, {
      items,
      status: allStaged ? 'completed' : 'in_progress',
      completed_at: allStaged ? new Date().toISOString() : null
    }, { render: false });

    addHistory({
      type: 'success',
      message: 'Ubicación registrada +1',
      sku: item.product_sku,
      location: currentStagingLocation
    });

    if(allStaged){
      addHistory({ type: 'info', message: 'Toda la mercancía quedó ubicada. La tarea está lista para cierre.' });
    }
  }

  async function completeTask(){
    if(!selectedTask || !isStageDone(selectedTask)){
      addHistory({ type: 'error', message: 'No se puede cerrar la tarea porque aún faltan cantidades por ubicar.' });
      renderPanel();
      return;
    }

    try{
      await patchTask(selectedTask.id, {
        items: selectedTask.items,
        status: 'completed',
        completed_at: new Date().toISOString()
      });
      addHistory({ type: 'success', message: 'La tarea se cerró correctamente.' });
    }catch(e){
      addHistory({ type: 'error', message: 'Error al cerrar la tarea: ' + e.message });
    }

    renderPanel();
  }

  taskList.addEventListener('click', e => {
    const btn = e.target.closest('.e-open-start');
    if(btn){
      e.stopPropagation();
      openStartModal(btn.dataset.taskId);
      return;
    }

    const row = e.target.closest('[data-task-id]');
    if(row){
      openStartModal(row.dataset.taskId);
    }
  });

  function autoSelectServerTask(){
    if(selectedTaskFromServer){
      selectedTask = ensureTaskShape(selectedTaskFromServer);
      currentMode = isCollectDone(selectedTask) ? 'stage' : 'collect';
      renderTaskList();
      renderPanel();
      return;
    }

    if(selectedTaskIdFromServer){
      const found = tasks.find(t => Number(t.id) === Number(selectedTaskIdFromServer));
      if(found){
        selectedTask = ensureTaskShape(found);
        currentMode = isCollectDone(selectedTask) ? 'stage' : 'collect';
        renderTaskList();
        renderPanel();
        return;
      }
    }

    renderTaskList();
    renderEmptyPanel();
  }

  autoSelectServerTask();
})();
</script>
@endpush