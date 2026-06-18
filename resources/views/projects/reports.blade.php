@extends('layouts.app')
@section('content_class', 'content--flush')
@section('title', 'Reportes - ' . $project->name)

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

  :root {
    --bg: #f9fafb;
    --card: #ffffff;
    --ink: #333333;
    --title: #111111;
    --muted: #888888;
    --line: #ebebeb;
    --blue: #007aff;
    --blue-soft: #e6f0ff;
    --success: #15803d;
    --success-soft: #e6ffe6;
    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  body {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    color: var(--ink);
  }

  .prv-wrap {
    min-height: calc(100vh - 60px);
    background: var(--bg);
  }

  .prv-topbar {
    position: sticky;
    top: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    gap: 18px;
    min-height: 62px;
    padding: 12px 28px;
    background: rgba(255,255,255,.94);
    border-bottom: 1px solid var(--line);
    backdrop-filter: blur(12px);
    box-shadow: 0 2px 10px rgba(0,0,0,.03);
  }

  .prv-back {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    color: #555555;
    text-decoration: none;
    transition: all .18s ease;
  }

  .prv-back:hover {
    background: #f3f4f6;
    color: var(--blue);
  }

  .prv-project {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    font-size: .94rem;
    font-weight: 700;
    color: var(--title);
    letter-spacing: .01em;
  }

  .prv-dot {
    width: 6px;
    height: 6px;
    border-radius: 999px;
    background: #d6d6d6;
  }

  .prv-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 14px;
    border-radius: 999px;
    background: var(--blue-soft);
    color: var(--blue);
    border: 1px solid #c7dcfd;
    font-size: .78rem;
    font-weight: 700;
    white-space: nowrap;
  }

  .prv-status-pill::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: currentColor;
  }

  .prv-nav {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-left: 4px;
  }

  .prv-nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border-radius: 8px;
    color: #333333;
    font-size: .88rem;
    font-weight: 700;
    text-decoration: none;
    transition: all .18s ease;
  }

  .prv-nav-link:hover {
    background: #f3f4f6;
    color: var(--blue);
  }

  .prv-nav-link.is-active {
    background: var(--blue);
    color: #ffffff;
    box-shadow: 0 8px 18px rgba(0,122,255,.18);
  }

  .prv-nav-link svg,
  .prv-back svg,
  .prv-doc-link svg {
    width: 18px;
    height: 18px;
    flex-shrink: 0;
  }

  .prv-doc-link {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #111111;
    font-size: .88rem;
    font-weight: 700;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all .18s ease;
  }

  .prv-doc-link:hover {
    background: #f3f4f6;
    color: var(--blue);
  }

  .prv-main {
    width: min(1500px, calc(100% - 48px));
    margin: 0 auto;
    padding: 36px 0 52px;
  }

  .prv-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 22px;
  }

  .prv-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--blue);
    background: var(--blue-soft);
    border: 1px solid #c7dcfd;
    border-radius: 999px;
    padding: 6px 12px;
    font-size: .78rem;
    font-weight: 700;
    margin-bottom: 10px;
  }

  .prv-title {
    margin: 0;
    color: var(--title);
    font-size: clamp(1.8rem, 3vw, 2.7rem);
    line-height: 1.05;
    font-weight: 700;
    letter-spacing: -.04em;
  }

  .prv-subtitle {
    margin: 10px 0 0;
    max-width: 760px;
    color: #667085;
    font-size: 1rem;
    line-height: 1.55;
    font-weight: 600;
  }

  .prv-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(260px, 1fr));
    gap: 26px;
    align-items: stretch;
  }

  .prv-card {
    position: relative;
    min-height: 318px;
    display: flex;
    flex-direction: column;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 4px 12px rgba(0,0,0,.02);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    overflow: hidden;
  }

  .prv-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(15,23,42,.06);
    border-color: #dce8ff;
  }

  .prv-card.is-selected {
    border-color: #9fc8ff;
    box-shadow: 0 0 0 2px rgba(0,122,255,.10), 0 14px 30px rgba(0,122,255,.06);
  }

  .prv-card.is-generated {
    border-color: #a7f3c5;
    background: linear-gradient(135deg, #ffffff 0%, #f0fff6 100%);
  }

  .prv-generated-badge {
    position: absolute;
    top: 28px;
    right: 28px;
    display: none;
    align-items: center;
    min-height: 28px;
    padding: 0 16px;
    border-radius: 999px;
    background: #16a34a;
    color: #ffffff;
    font-size: .78rem;
    font-weight: 700;
  }

  .prv-card.is-generated .prv-generated-badge {
    display: inline-flex;
  }

  .prv-icon {
    width: 64px;
    height: 64px;
    display: grid;
    place-items: center;
    border-radius: 16px;
    background: var(--blue-soft);
    color: var(--blue);
    margin-bottom: 28px;
  }

  .prv-card.is-generated .prv-icon {
    background: #dcfce7;
    color: #16a34a;
  }

  .prv-icon svg {
    width: 34px;
    height: 34px;
    stroke-width: 1.9;
  }

  .prv-card h2 {
    margin: 0 0 14px;
    color: var(--title);
    font-size: 1.22rem;
    line-height: 1.25;
    font-weight: 700;
    letter-spacing: -.02em;
  }

  .prv-card p {
    margin: 0;
    color: #5d6675;
    font-size: .96rem;
    line-height: 1.5;
    font-weight: 600;
  }

  .prv-card-footer {
    margin-top: auto;
    padding-top: 34px;
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .prv-card.is-generated .prv-card-footer {
    grid-template-columns: 1fr 1fr;
  }

  .prv-btn {
    width: 100%;
    min-height: 54px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border: none;
    border-radius: 8px;
    background: var(--blue);
    color: #ffffff;
    font-family: inherit;
    font-size: .95rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    box-shadow: 0 6px 16px rgba(0,122,255,.18);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, opacity .16s ease;
  }

  .prv-btn:hover {
    background: #0a84ff;
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(0,122,255,.22);
  }

  .prv-btn:active { transform: scale(.98); }

  .prv-btn:disabled {
    opacity: .58;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
  }

  .prv-btn svg {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
  }

  .prv-btn.is-outline {
    background: #ffffff;
    color: var(--title);
    border: 1px solid #dfe5ee;
    box-shadow: none;
  }

  .prv-btn.is-outline:hover {
    background: #f9fafb;
    border-color: #cfd8e6;
    box-shadow: 0 8px 18px rgba(15,23,42,.05);
  }

  .prv-loader {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    border: 2px solid rgba(255,255,255,.45);
    border-top-color: #ffffff;
    animation: prvSpin .8s linear infinite;
  }

  @keyframes prvSpin { to { transform: rotate(360deg); } }

  .prv-tip {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 32px;
    padding: 18px 22px;
    background: #f8fbff;
    border: 1px solid #c7dcfd;
    border-radius: 14px;
    color: #475569;
    font-size: .96rem;
    font-weight: 600;
  }

  .prv-tip svg {
    width: 24px;
    height: 24px;
    color: var(--blue);
    flex-shrink: 0;
  }

  /* Modal de reporte */
  .prv-modal {
    position: fixed;
    inset: 0;
    z-index: 500;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 28px;
  }

  .prv-modal.is-open {
    display: flex;
  }

  .prv-modal-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(248,250,252,.56);
    backdrop-filter: blur(14px);
    animation: prvFadeIn .2s ease both;
  }

  .prv-modal-card {
    position: relative;
    z-index: 1;
    width: min(1120px, calc(100vw - 64px));
    height: min(86vh, 850px);
    display: flex;
    flex-direction: column;
    background: #ffffff;
    border: 1px solid var(--line);
    border-radius: 24px;
    box-shadow: 0 28px 80px rgba(15,23,42,.16);
    overflow: hidden;
    animation: prvModalIn .24s cubic-bezier(.22,1,.36,1) both;
  }

  .prv-modal.is-fullscreen .prv-modal-card {
    width: calc(100vw - 32px);
    height: calc(100vh - 32px);
    border-radius: 20px;
  }

  .prv-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 22px 12px;
    border-bottom: 1px solid var(--line);
    background: #ffffff;
  }

  .prv-modal-title-wrap {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
  }

  .prv-modal-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: var(--blue-soft);
    color: var(--blue);
    flex-shrink: 0;
  }

  .prv-modal-icon svg {
    width: 24px;
    height: 24px;
  }

  .prv-modal-title {
    margin: 0;
    color: var(--title);
    font-size: 1.28rem;
    font-weight: 700;
    line-height: 1.15;
    letter-spacing: -.02em;
  }

  .prv-ai-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 7px;
    padding: 4px 10px;
    border-radius: 999px;
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    color: #7e22ce;
    font-size: .7rem;
    font-weight: 700;
  }

  .prv-modal-close {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: none;
    border-radius: 999px;
    background: transparent;
    color: #475569;
    cursor: pointer;
    transition: all .16s ease;
  }

  .prv-modal-close:hover {
    background: #f3f4f6;
    color: var(--danger);
  }

  .prv-modal-close svg { width: 20px; height: 20px; }

  .prv-modal-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 11px 22px;
    border-bottom: 1px solid var(--line);
    background: #fbfcfe;
  }

  .prv-view-tabs,
  .prv-modal-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
  }

  .prv-view-tab,
  .prv-icon-action {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid transparent;
    border-radius: 9px;
    background: transparent;
    color: #64748b;
    font-family: inherit;
    font-size: .88rem;
    font-weight: 700;
    cursor: pointer;
    padding: 0 14px;
    transition: all .16s ease;
  }

  .prv-view-tab:hover,
  .prv-icon-action:hover {
    background: #f3f6fb;
    color: var(--title);
  }

  .prv-view-tab.is-active {
    background: #ffffff;
    color: var(--title);
    border-color: var(--line);
    box-shadow: 0 2px 7px rgba(15,23,42,.04);
  }

  .prv-view-tab svg,
  .prv-icon-action svg { width: 18px; height: 18px; }

  .prv-icon-action {
    width: 40px;
    padding: 0;
    color: var(--blue);
  }

  .prv-editor-toolbar {
    display: none;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
    padding: 12px 22px;
    border-bottom: 1px solid var(--line);
    background: #ffffff;
  }

  .prv-modal.is-editing .prv-editor-toolbar {
    display: flex;
  }

  .prv-edit-btn {
    height: 34px;
    min-width: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid transparent;
    border-radius: 9px;
    background: transparent;
    color: #475569;
    font-family: inherit;
    font-size: .86rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .15s ease;
  }

  .prv-edit-btn:hover {
    background: #f3f6fb;
    color: var(--title);
  }

  .prv-edit-btn svg { width: 17px; height: 17px; }

  .prv-edit-sep {
    width: 1px;
    height: 26px;
    background: var(--line);
    margin: 0 5px;
  }

  .prv-modal-body {
    flex: 1;
    min-height: 0;
    overflow: auto;
    padding: 28px 30px;
    background: #ffffff;
  }

  .prv-report-canvas {
    max-width: 980px;
    margin: 0 auto;
    color: #111827;
    font-size: 1rem;
    line-height: 1.62;
    outline: none;
  }

  .prv-report-canvas h1 {
    margin: 0 0 18px;
    color: #020617;
    font-size: clamp(1.75rem, 3vw, 2.65rem);
    line-height: 1.08;
    font-weight: 700;
    letter-spacing: -.04em;
  }

  .prv-report-canvas h2 {
    margin: 18px 0 10px;
    color: #020617;
    font-size: 1.55rem;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: -.025em;
  }

  .prv-report-canvas h3 {
    margin: 16px 0 8px;
    color: #0f172a;
    font-size: 1.22rem;
    font-weight: 700;
  }

  .prv-report-canvas p {
    margin: 8px 0 12px;
  }

  .prv-report-canvas ul,
  .prv-report-canvas ol {
    margin: 8px 0 14px;
    padding-left: 24px;
  }

  .prv-report-canvas li {
    margin: 5px 0;
  }

  .prv-report-canvas table {
    width: 100%;
    border-collapse: collapse;
    margin: 14px 0;
    font-size: .95rem;
  }

  .prv-report-canvas td,
  .prv-report-canvas th {
    border: 1px solid var(--line);
    padding: 10px 12px;
    text-align: left;
    vertical-align: top;
  }

  .prv-report-canvas th {
    background: #f8fafc;
    color: #111827;
  }

  .prv-modal.is-editing .prv-report-canvas {
    min-height: calc(86vh - 250px);
    padding: 22px 26px;
    border: 1px solid var(--line);
    border-radius: 14px;
    background: #ffffff;
    box-shadow: inset 0 0 0 1px rgba(15,23,42,.01);
  }

  .prv-modal.is-editing .prv-report-canvas:focus {
    border-color: #c7dcfd;
    box-shadow: 0 0 0 3px var(--blue-soft);
  }

  .prv-toast {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 9999;
    max-width: 360px;
    padding: 17px 22px;
    border-radius: 12px;
    background: #ffffff;
    color: #111827;
    border: 1px solid var(--line);
    font-size: .94rem;
    font-weight: 700;
    box-shadow: 0 14px 36px rgba(15,23,42,.12);
    animation: prvToastIn .22s ease both;
  }

  .prv-toast span {
    display: block;
    margin-top: 6px;
    color: #475569;
    font-weight: 600;
    font-size: .86rem;
  }

  .prv-toast.is-error {
    border-color: #fecaca;
    background: #fff5f5;
    color: var(--danger);
  }

  @keyframes prvFadeIn { from { opacity: 0; } to { opacity: 1; } }
  @keyframes prvModalIn { from { opacity: 0; transform: translateY(14px) scale(.985); } to { opacity: 1; transform: translateY(0) scale(1); } }
  @keyframes prvToastIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }

  @media (max-width: 1180px) {
    .prv-grid { grid-template-columns: repeat(2, minmax(260px, 1fr)); }
  }

  @media (max-width: 760px) {
    .prv-topbar { padding: 10px 16px; gap: 10px; }
    .prv-project { max-width: 42vw; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .prv-doc-link { display: none; }
    .prv-main { width: calc(100% - 28px); padding: 24px 0 40px; }
    .prv-heading { display: block; }
    .prv-grid { grid-template-columns: 1fr; gap: 16px; }
    .prv-card { min-height: 280px; padding: 24px; }
    .prv-card.is-generated .prv-card-footer { grid-template-columns: 1fr; }
    .prv-modal { padding: 10px; }
    .prv-modal-card { width: 100%; height: calc(100vh - 20px); border-radius: 18px; }
    .prv-modal-toolbar { align-items: flex-start; flex-direction: column; }
    .prv-modal-body { padding: 18px; }
  }
</style>
@endpush

@section('content')
@php
  $workflowOptions = [
      'analisis_bases'      => 'Análisis de Bases',
      'revision'            => 'Revisión',
      'participa'           => 'Participa',
      'junta_aclaraciones'  => 'Junta de Aclaraciones',
      'armado_propuesta'    => 'Armado de Propuesta',
      'entrega'             => 'Entrega',
      'no_participa'        => 'No participa',
      'ganado'              => 'Ganado',
      'perdido'             => 'Perdido',
      'desierta'            => 'Desierta',
  ];

  $workflowKey = $project->workflow_status ?: 'analisis_bases';
  $workflowLabel = $workflowOptions[$workflowKey] ?? 'Análisis de Bases';
  $firstDoc = $project->documents->first();
  $firstDocUrl = $firstDoc?->file_path ? Storage::disk('public')->url($firstDoc->file_path) : null;
  $reportUrl = route('projects.report', $project);
  $initialReportHtml = trim((string) ($project->report_content ?: $project->draft_content));
  $generatedReports = data_get($project->structured_data ?? [], 'generated_reports', []);
  $generatedReports = is_array($generatedReports) ? $generatedReports : [];

  $reportHtmlAnalysis = trim((string) data_get($generatedReports, 'analysis.html', $initialReportHtml));
  $reportHtmlFinance = trim((string) data_get($generatedReports, 'finance.html', ''));
  $reportHtmlLogistics = trim((string) data_get($generatedReports, 'logistics.html', ''));
  $reportHtmlTechnical = trim((string) data_get($generatedReports, 'technical.html', ''));

  $hasInitialReport = $reportHtmlAnalysis !== '';
  $hasFinanceReport = $reportHtmlFinance !== '';
  $hasLogisticsReport = $reportHtmlLogistics !== '';
  $hasTechnicalReport = $reportHtmlTechnical !== '';
@endphp

<div class="prv-wrap">
  <header class="prv-topbar">
    <a href="{{ route('projects.show', $project) }}" class="prv-back" title="Volver al proyecto">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
    </a>

    <div class="prv-project">
      <span>{{ $project->name }}</span>
      <span class="prv-dot"></span>
    </div>

    <span class="prv-status-pill">{{ $workflowLabel }}</span>

    <nav class="prv-nav">
      <a href="{{ route('projects.show', $project) }}" class="prv-nav-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
        Inicio
      </a>
      <a href="{{ route('projects.reports', $project) }}" class="prv-nav-link is-active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
        Reportes
      </a>
    </nav>

    @if($firstDocUrl)
      <a href="{{ $firstDocUrl }}" target="_blank" class="prv-doc-link">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Ver documento
      </a>
    @endif
  </header>

  <main class="prv-main">
    <section class="prv-heading">
      <div>
        <div class="prv-kicker">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M3 12h4M17 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
          Centro de reportes
        </div>
        <h1 class="prv-title">Reportes del proyecto</h1>
        <p class="prv-subtitle">Genera documentos ejecutivos desde la información analizada, el checklist y los documentos cargados del proyecto.</p>
      </div>
    </section>

    <section class="prv-grid">
      <article class="prv-card {{ $hasInitialReport ? 'is-generated' : 'is-selected' }}" data-report-card="analysis" data-title="Reporte de análisis de bases">
        <span class="prv-generated-badge">Generado</span>
        <div class="prv-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 6.2 19 5"/><path d="M17.8 11.8 19 13"/><path d="M3 21l9-9"/><path d="M12.5 3.5l8 8-8 8-8-8z"/></svg>
        </div>
        <h2>Reporte de análisis de bases</h2>
        <p>Genera un reporte automático basado en la información de la licitación, fechas, ficha general y requisitos principales.</p>
        <div class="prv-card-footer">
          <button type="button" class="prv-btn is-outline" data-open-report style="{{ $hasInitialReport ? '' : 'display:none;' }}">Ver/Editar</button>
          <button type="button" class="prv-btn" data-generate-report data-report-url="{{ $reportUrl }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M3 12h4M17 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            {{ $hasInitialReport ? 'Regenerar' : 'Generar reporte' }}
          </button>
        </div>
      </article>

      <article class="prv-card {{ $hasFinanceReport ? 'is-generated' : '' }}" data-report-card="finance" data-title="Reporte de finanzas">
        <span class="prv-generated-badge">Generado</span>
        <div class="prv-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M15 9.5A3 3 0 0 0 12 8a3 3 0 0 0 0 6 3 3 0 0 1 0 6 3 3 0 0 1-3-1.5"/></svg>
        </div>
        <h2>Reporte de finanzas</h2>
        <p>Análisis financiero detallado con indicadores clave, estimaciones, costos, presupuesto y proyecciones.</p>
        <div class="prv-card-footer">
          <button type="button" class="prv-btn is-outline" data-open-report style="{{ $hasFinanceReport ? '' : 'display:none;' }}">Ver/Editar</button>
          <button type="button" class="prv-btn" data-generate-report data-report-url="{{ $reportUrl }}" data-report-type="finanzas">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M3 12h4M17 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            {{ $hasFinanceReport ? 'Regenerar' : 'Generar reporte' }}
          </button>
        </div>
      </article>

      <article class="prv-card {{ $hasLogisticsReport ? 'is-generated' : '' }}" data-report-card="logistics" data-title="Reporte de logística">
        <span class="prv-generated-badge">Generado</span>
        <div class="prv-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M10 17h4V5H2v12h3"/><path d="M14 9h4l4 4v4h-3"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="16.5" cy="17.5" r="2.5"/></svg>
        </div>
        <h2>Reporte de logística</h2>
        <p>Documento detallado sobre entregas, almacenes, inventario, rutas, operación y condiciones logísticas.</p>
        <div class="prv-card-footer">
          <button type="button" class="prv-btn is-outline" data-open-report style="{{ $hasLogisticsReport ? '' : 'display:none;' }}">Ver/Editar</button>
          <button type="button" class="prv-btn" data-generate-report data-report-url="{{ $reportUrl }}" data-report-type="logistica">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M3 12h4M17 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            {{ $hasLogisticsReport ? 'Regenerar' : 'Generar reporte' }}
          </button>
        </div>
      </article>

      <article class="prv-card {{ $hasTechnicalReport ? 'is-generated' : '' }}" data-report-card="technical" data-title="Reporte de soporte técnico">
        <span class="prv-generated-badge">Generado</span>
        <div class="prv-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M14.7 6.3a4 4 0 0 0-5.66 5.66L4 17v3h3l5.04-5.04A4 4 0 0 0 17.7 9.3"/><path d="M16 3l5 5"/><path d="M19 2l3 3"/></svg>
        </div>
        <h2>Reporte de soporte técnico</h2>
        <p>Resumen de tickets, resoluciones, pendientes, hallazgos técnicos y métricas de atención al cliente.</p>
        <div class="prv-card-footer">
          <button type="button" class="prv-btn is-outline" data-open-report style="{{ $hasTechnicalReport ? '' : 'display:none;' }}">Ver/Editar</button>
          <button type="button" class="prv-btn" data-generate-report data-report-url="{{ $reportUrl }}" data-report-type="soporte_tecnico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M3 12h4M17 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            {{ $hasTechnicalReport ? 'Regenerar' : 'Generar reporte' }}
          </button>
        </div>
      </article>
    </section>

    <div class="prv-tip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M3 12h4M17 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
      <span><strong>Tip:</strong> Puedes generar reportes, abrirlos en modal, editarlos y guardarlos sin salir de esta pantalla.</span>
    </div>
  </main>
</div>

<div class="prv-modal" id="prvReportModal" aria-hidden="true">
  <div class="prv-modal-backdrop" data-close-modal></div>
  <section class="prv-modal-card" role="dialog" aria-modal="true" aria-labelledby="prvModalTitle">
    <header class="prv-modal-head">
      <div class="prv-modal-title-wrap">
        <div class="prv-modal-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
        </div>
        <div>
          <h2 class="prv-modal-title" id="prvModalTitle">Reporte</h2>
          <span class="prv-ai-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v4M12 17v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M3 12h4M17 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            Generado con IA
          </span>
        </div>
      </div>
      <button type="button" class="prv-modal-close" data-close-modal aria-label="Cerrar modal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      </button>
    </header>

    <div class="prv-modal-toolbar">
      <div class="prv-view-tabs">
        <button type="button" class="prv-view-tab is-active" data-modal-mode="preview">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Vista Previa
        </button>
        <button type="button" class="prv-view-tab" data-modal-mode="edit">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
          Editar
        </button>
      </div>

      <div class="prv-modal-actions">
        <button type="button" class="prv-icon-action" id="prvDownloadDoc" title="Descargar Word">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
        </button>
        <button type="button" class="prv-icon-action" id="prvSaveReport" title="Guardar">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
        </button>
        <button type="button" class="prv-icon-action" id="prvFullscreen" title="Pantalla completa">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H3v5"/><path d="M3 3l6 6"/><path d="M16 3h5v5"/><path d="m21 3-6 6"/><path d="M8 21H3v-5"/><path d="m3 21 6-6"/><path d="M16 21h5v-5"/><path d="m21 21-6-6"/></svg>
        </button>
      </div>
    </div>

    <div class="prv-editor-toolbar" id="prvEditorToolbar">
      <button type="button" class="prv-edit-btn" data-cmd="undo" title="Deshacer">↶</button>
      <button type="button" class="prv-edit-btn" data-cmd="redo" title="Rehacer">↷</button>
      <span class="prv-edit-sep"></span>
      <button type="button" class="prv-edit-btn" data-cmd="bold" title="Negrita"><b>B</b></button>
      <button type="button" class="prv-edit-btn" data-cmd="italic" title="Cursiva"><i>I</i></button>
      <button type="button" class="prv-edit-btn" data-cmd="underline" title="Subrayado"><u>U</u></button>
      <span class="prv-edit-sep"></span>
      <button type="button" class="prv-edit-btn" data-block="H1">H1</button>
      <button type="button" class="prv-edit-btn" data-block="H2">H2</button>
      <button type="button" class="prv-edit-btn" data-block="H3">H3</button>
      <span class="prv-edit-sep"></span>
      <button type="button" class="prv-edit-btn" data-cmd="justifyLeft" title="Izquierda">☰</button>
      <button type="button" class="prv-edit-btn" data-cmd="justifyCenter" title="Centro">≡</button>
      <button type="button" class="prv-edit-btn" data-cmd="justifyRight" title="Derecha">☷</button>
      <button type="button" class="prv-edit-btn" data-cmd="justifyFull" title="Justificar">▤</button>
      <span class="prv-edit-sep"></span>
      <button type="button" class="prv-edit-btn" data-cmd="insertUnorderedList" title="Lista">•</button>
      <button type="button" class="prv-edit-btn" data-cmd="insertOrderedList" title="Numerada">1.</button>
      <span class="prv-edit-sep"></span>
      <button type="button" class="prv-edit-btn" data-action="table" title="Tabla">▦</button>
      <button type="button" class="prv-edit-btn" data-cmd="insertHorizontalRule" title="Separador">─</button>
      <button type="button" class="prv-edit-btn" data-cmd="removeFormat" title="Limpiar formato">⌫</button>
    </div>

    <div class="prv-modal-body">
      <div class="prv-report-canvas" id="prvReportCanvas" contenteditable="false"></div>
    </div>
  </section>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const modal = document.getElementById('prvReportModal');
    const modalTitle = document.getElementById('prvModalTitle');
    const canvas = document.getElementById('prvReportCanvas');
    const saveBtn = document.getElementById('prvSaveReport');
    const downloadBtn = document.getElementById('prvDownloadDoc');
    const fullscreenBtn = document.getElementById('prvFullscreen');
    const reportStore = {
      analysis: @json($reportHtmlAnalysis),
      finance: @json($reportHtmlFinance),
      logistics: @json($reportHtmlLogistics),
      technical: @json($reportHtmlTechnical)
    };
    let currentType = 'analysis';
    let currentCard = document.querySelector('[data-report-card="analysis"]');

    const toast = (title, text = '', type = '') => {
      const old = document.querySelector('.prv-toast');
      if (old) old.remove();
      const el = document.createElement('div');
      el.className = 'prv-toast' + (type ? ' is-' + type : '');
      el.innerHTML = `${title}${text ? `<span>${text}</span>` : ''}`;
      document.body.appendChild(el);
      setTimeout(() => el.remove(), 3200);
    };

    const setLoading = (btn, loading) => {
      if (!btn) return;
      btn.disabled = loading;
      if (loading) {
        btn.dataset.originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="prv-loader"></span><span>Generando...</span>';
      } else if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
      }
    };

    const filenameSafe = (text) => (text || 'reporte')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'reporte';

    const setGeneratedState = (card, generated = true) => {
      if (!card) return;
      card.classList.toggle('is-generated', generated);
      card.classList.remove('is-selected');
      const openBtn = card.querySelector('[data-open-report]');
      const generateBtn = card.querySelector('[data-generate-report]');
      if (openBtn) openBtn.style.display = generated ? '' : 'none';
      if (generateBtn) {
        const svg = generateBtn.querySelector('svg')?.outerHTML || '';
        generateBtn.innerHTML = svg + (generated ? '<span>Regenerar</span>' : '<span>Generar reporte</span>');
      }
    };

    const openModal = (card, mode = 'preview') => {
      currentCard = card || currentCard;
      currentType = currentCard?.dataset.reportCard || 'analysis';
      const title = currentCard?.dataset.title || 'Reporte';
      modalTitle.textContent = title;
      canvas.innerHTML = reportStore[currentType] || '<p>No hay contenido generado todavía.</p>';
      setMode(mode);
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    };

    const closeModal = () => {
      modal.classList.remove('is-open', 'is-editing', 'is-fullscreen');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    };

    const setMode = (mode) => {
      const editing = mode === 'edit';
      modal.classList.toggle('is-editing', editing);
      canvas.contentEditable = editing ? 'true' : 'false';
      document.querySelectorAll('[data-modal-mode]').forEach((btn) => {
        btn.classList.toggle('is-active', btn.dataset.modalMode === mode);
      });
      if (editing) {
        setTimeout(() => canvas.focus(), 80);
      }
    };

    const saveReport = async () => {
      const html = canvas.innerHTML;
      reportStore[currentType] = html;

      try {
        const response = await fetch(@json($reportUrl), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            action: 'save',
            report_content: html,
            draft_content: html,
            report_type: currentType
          })
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
          throw new Error(data.message || 'No se pudo guardar el documento.');
        }

        setGeneratedState(currentCard, true);
        toast('Documento guardado', 'El documento se ha guardado exitosamente');
      } catch (error) {
        toast('Error al guardar', error.message || 'No se pudo guardar el documento.', 'error');
      }
    };

    const downloadDoc = () => {
      const title = modalTitle.textContent || 'Reporte';
      const html = `<!doctype html><html><head><meta charset="utf-8"><title>${title}</title></head><body>${canvas.innerHTML}</body></html>`;
      const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filenameSafe(title) + '.doc';
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    };

    document.querySelectorAll('[data-generate-report]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const card = btn.closest('[data-report-card]');
        const type = card?.dataset.reportCard || 'analysis';
        const title = card?.dataset.title || 'Reporte';
        const url = btn.dataset.reportUrl;

        setLoading(btn, true);
        card?.classList.add('is-selected');

        try {
          const response = await fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              action: 'generate',
              report_type: type,
              report_title: title
            })
          });

          const data = await response.json().catch(() => ({}));
          if (!response.ok || !data.ok) {
            throw new Error(data.message || 'No se pudo generar el reporte.');
          }

          reportStore[type] = data.html || '<p>Reporte generado correctamente.</p>';
          setGeneratedState(card, true);
          openModal(card, 'preview');
          toast('Documento generado', 'El reporte se abrió en vista previa.');
        } catch (error) {
          toast('Error al generar', error.message || 'No se pudo generar el reporte.', 'error');
        } finally {
          setLoading(btn, false);
        }
      });
    });

    document.querySelectorAll('[data-open-report]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const card = btn.closest('[data-report-card]');
        openModal(card, 'preview');
      });
    });

    document.querySelectorAll('[data-close-modal]').forEach((el) => {
      el.addEventListener('click', closeModal);
    });

    document.querySelectorAll('[data-modal-mode]').forEach((btn) => {
      btn.addEventListener('click', () => setMode(btn.dataset.modalMode));
    });

    document.getElementById('prvEditorToolbar')?.addEventListener('click', (event) => {
      const btn = event.target.closest('button');
      if (!btn) return;
      event.preventDefault();
      canvas.focus();

      if (btn.dataset.cmd) {
        document.execCommand(btn.dataset.cmd, false, null);
      }

      if (btn.dataset.block) {
        document.execCommand('formatBlock', false, btn.dataset.block);
      }

      if (btn.dataset.action === 'table') {
        document.execCommand('insertHTML', false, '<table><tbody><tr><th>Concepto</th><th>Detalle</th></tr><tr><td></td><td></td></tr></tbody></table>');
      }
    });

    saveBtn?.addEventListener('click', saveReport);
    downloadBtn?.addEventListener('click', downloadDoc);
    fullscreenBtn?.addEventListener('click', () => modal.classList.toggle('is-fullscreen'));

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }

      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's' && modal.classList.contains('is-open')) {
        event.preventDefault();
        saveReport();
      }
    });
  });
</script>
@endsection
