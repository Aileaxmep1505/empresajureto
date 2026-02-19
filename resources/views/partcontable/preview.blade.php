{{-- resources/views/partcontable/preview.blade.php --}}
@extends('layouts.app')

@section('title', 'Previsualización | ' . $document->title)

@php
    use Illuminate\Support\Str;

    // URL principal
    $url  = $document->url ?: asset('storage/' . $document->file_path);
    $mime = $document->mime_type ?? 'application/octet-stream';

    $isImage = Str::startsWith($mime, 'image/');
    $isVideo = Str::startsWith($mime, 'video/');
    $isPdf   = $mime === 'application/pdf';

    $fileName = basename($document->file_path);

    $uploaderName = optional($document->uploader)->name ?? 'Usuario del sistema';

    // FICTICIO: reglas
    $allowedSectionKeys = ['declaracion_anual', 'declaracion_mensual'];
    $allowedSubtypeKeys = [
      'acuse_anual','pago_anual','declaracion_anual',
      'acuse_mensual','pago_mensual','declaracion_mensual',
    ];

    $sectionKey = optional($document->section)->key;
    $subKey     = optional($document->subtype)->key;

    $allowFicticioHere = in_array($sectionKey, $allowedSectionKeys, true)
      && in_array($subKey, $allowedSubtypeKeys, true);

    $hasFicticio  = !empty($document->ficticio_file_path);

    // No abrir ficticio al entrar
    $openFicticio = false;

    // Siempre volver a /part-contable
    $backUrl = url('/part-contable');

    // URL FICTICIO
    $ficticioUrl = $hasFicticio
      ? asset('storage/' . ltrim($document->ficticio_file_path, '/'))
      : null;

    $fictMime = $document->ficticio_mime_type ?? null;
    $fictExt  = $hasFicticio ? strtolower(pathinfo($document->ficticio_file_path, PATHINFO_EXTENSION)) : '';
    $fictIsImage = $hasFicticio && (
      ($fictMime && Str::startsWith($fictMime,'image/')) || in_array($fictExt, ['jpg','jpeg','png','gif','webp','svg'], true)
    );
    $fictIsVideo = $hasFicticio && (
      ($fictMime && Str::startsWith($fictMime,'video/')) || in_array($fictExt, ['mp4','mov','webm','mkv'], true)
    );
    $fictIsPdf = $hasFicticio && (
      ($fictMime === 'application/pdf') || $fictExt === 'pdf'
    );
@endphp

@section('content')
<style>
    :root {
        --ui-bg-coffee: #F4F1EA;
        --ui-surface: #FFFFFF;
        --ui-border: #E5E0D8;
        --ui-text-main: #1C1917;
        --ui-text-muted: #78716C;
        --ui-accent: #111827;
        --radius-lg: 24px;
        --radius-md: 12px;
        --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.08);
    }

    body{ background: var(--ui-bg-coffee) !important; }
    main, .container, .container-fluid{ background: transparent !important; }
    .app-content, #app, .page-content{ background: transparent !important; }

    .doc-viewer-breakout {
        width: 100vw;
        position: relative;
        left: 50%;
        right: 50%;
        margin-left: -50vw;
        margin-right: -50vw;
        padding: 0 !important;
        background: var(--ui-bg-coffee);
        min-height: calc(100vh - 64px);
    }

    .doc-viewer-layout {
        display: flex;
        min-height: calc(100vh - 64px);
        background-color: var(--ui-bg-coffee);
        font-family: 'Inter', system-ui, sans-serif;
        color: var(--ui-text-main);
    }

    .doc-sidebar {
        width: 340px;
        background-color: var(--ui-surface);
        border-right: 1px solid var(--ui-border);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
        z-index: 10;
    }

    .sidebar-header { padding: 32px 24px 24px; }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--ui-text-muted);
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        margin-bottom: 32px;
        transition: color 0.2s;
    }
    .btn-back:hover { color: var(--ui-text-main); }

    .doc-title {
        font-size: 24px;
        font-weight: 700;
        line-height: 1.2;
        margin: 0 0 12px 0;
        letter-spacing: -0.02em;
    }

    .doc-badge {
        display: inline-block;
        padding: 6px 12px;
        background: #F3F4F6;
        color: var(--ui-text-main);
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .sidebar-body {
        padding: 0 24px 24px;
        flex-grow: 1;
        overflow: visible;
    }

    .meta-card {
        background: #FAFAFA;
        border: 1px solid var(--ui-border);
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 24px;
    }

    .meta-group { margin-bottom: 16px; }
    .meta-group:last-child { margin-bottom: 0; }
    .meta-label {
        font-size: 11px;
        text-transform: uppercase;
        color: var(--ui-text-muted);
        font-weight: 600;
        margin-bottom: 4px;
        letter-spacing: 0.5px;
    }
    .meta-value {
        font-size: 14px;
        font-weight: 500;
        color: var(--ui-text-main);
        word-break: break-all;
    }

    .sidebar-footer {
        padding: 24px;
        background: var(--ui-surface);
        border-top: 1px solid var(--ui-border);
        display:flex;
        flex-direction:column;
        gap:12px;
    }

    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px 16px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
        border: 1px solid transparent;
        background: transparent;
    }

    .btn-primary {
        background-color: var(--ui-accent);
        color: white;
    }
    .btn-primary:hover {
        background-color: #000000;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .btn-secondary {
        background-color: var(--ui-surface);
        color: var(--ui-text-main);
        border-color: #D6D3D1;
    }
    .btn-secondary:hover { background-color: #F5F5F4; }

    .btn-toggle{
      background:#111827;
      color:#fff;
    }
    .btn-toggle:hover{ background:#0b1220; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }

    .btn-ficticio-outline{
      background:#fff;
      color:#0b1220;
      border-color:#111827;
    }
    .btn-ficticio-outline:hover{ background:#f5f5f4; }

    .btn-ficticio{
      background:#0b1220;
      color:#fff;
    }
    .btn-ficticio:hover{ background:#000; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }

    .btn-row{
      display:flex;
      gap:10px;
      width:100%;
      align-items:stretch;
    }
    .btn-row .btn{
      flex:1 1 0;
      width:auto;
      white-space:nowrap;
      padding: 13px 12px;
    }

    .doc-stage {
        flex-grow: 1;
        padding: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        overflow: hidden;
    }

    .viewer-frame {
        width: 100%;
        max-width: 1100px;
        height: calc(100vh - 64px - 80px);
        min-height: 520px;
        background: #FFFFFF;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-soft);
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .viewer-layer{
      position:absolute; inset:0;
      display:none;
      opacity:0;
      transition: opacity .18s ease;
    }
    .viewer-layer.active{
      display:block;
      opacity:1;
    }
    .viewer-layer > iframe,
    .viewer-layer > img,
    .viewer-layer > video{
      width:100%;
      height:100%;
      border:0;
      display:block;
    }
    .viewer-layer > img{ object-fit:contain; background:#fff; }
    .viewer-layer > video{ background:#111; }

    @media (max-width: 1024px) {
        .doc-viewer-layout { flex-direction: column; display: block; }
        .doc-sidebar { width: 100%; border-right: none; border-bottom: 1px solid var(--ui-border); }
        .doc-stage { padding: 20px; }
        .viewer-frame { height: 70vh; min-height: 420px; }
        .doc-viewer-breakout { min-height: 100vh; }
    }

    /* ======= TOAST simple ======= */
    .pv-toast-wrap{
      position:fixed; top:14px; right:14px; z-index:99999;
      display:flex; flex-direction:column; gap:10px;
    }
    .pv-toast{
      background:#fff; border:1px solid rgba(0,0,0,.08); border-radius:14px;
      box-shadow:0 18px 50px rgba(16,24,40,.16);
      padding:10px 12px; display:flex; gap:10px; align-items:flex-start;
      transform: translateY(-6px); opacity:0; pointer-events:none;
      transition: transform .18s ease, opacity .18s ease;
      min-width: 260px;
      max-width: 380px;
    }
    .pv-toast.show{ transform: translateY(0); opacity:1; pointer-events:auto; }
    .pv-toast .ic{
      width:30px;height:30px;border-radius:10px; display:grid; place-items:center;
      background:rgba(16,185,129,.14); color:#065f46; font-weight:900; flex:0 0 30px;
    }
    .pv-toast.err .ic{ background:rgba(239,68,68,.14); color:#991b1b; }
    .pv-toast.info .ic{ background:rgba(59,130,246,.14); color:#1d4ed8; }
    .pv-toast .t1{ font-weight:900; color:#0f172a; font-size:13px; line-height:1.15; }
    .pv-toast .t2{ color:#667085; font-size:12px; margin-top:2px; white-space:pre-line; }
    .pv-toast .x{
      border:none;background:transparent;cursor:pointer;padding:6px;border-radius:10px;color:#667085;
    }
    .pv-toast .x:hover{ background:#f2f4f7; color:#0f172a; }

    /* ======= MODAL FICTICIO ======= */
    .pv-modal{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; z-index:90000; }
    .pv-modal[aria-hidden="false"]{ display:flex; }
    .pv-backdrop{ position:absolute; inset:0; background:rgba(0,0,0,0.48); }
    .pv-panel{
      position:relative;
      width:min(720px, calc(100% - 26px));
      background:#fff;
      border-radius:16px;
      border:1px solid rgba(0,0,0,.08);
      box-shadow:0 28px 80px rgba(0,0,0,.25);
      overflow:hidden;
    }
    .pv-head{ padding:14px 16px; border-bottom:1px solid rgba(0,0,0,.06); display:flex; justify-content:space-between; gap:12px; align-items:flex-start; }
    .pv-head .kicker{ font-size:11px; font-weight:900; letter-spacing:.08em; text-transform:uppercase; color:#64748b; }
    .pv-head h3{ margin:2px 0 0; font-size:18px; font-weight:950; color:#0b1220; }
    .pv-head .sub{ margin-top:6px; font-size:12px; color:#667085; font-weight:700; }
    .pv-x{
      border:none;background:transparent;cursor:pointer;color:#6b7280;
      padding:6px 10px;border-radius:12px; transition:background .15s ease, color .15s ease, transform .12s ease;
    }
    .pv-x:hover{ background:#f3f4f6; color:#111827; transform: translateY(-1px); }

    .pv-body{ padding:14px 16px; }
    .pv-dropzone{
      display:flex; gap:12px; align-items:flex-start;
      padding:14px; border-radius:14px;
      border:1px dashed rgba(15,23,42,0.22);
      background:linear-gradient(180deg, rgba(248,250,252,0.9), rgba(255,255,255,1));
      cursor:pointer;
      transition: border-color .15s ease, transform .15s ease, box-shadow .15s ease;
    }
    .pv-dropzone:hover{ border-color: rgba(17,24,39,.6); box-shadow: 0 18px 45px rgba(2,6,23,0.08); transform: translateY(-1px); }
    .pv-ic{
      width:38px;height:38px;border-radius:12px; display:grid; place-items:center;
      background: rgba(17,24,39,.10); color:#111827; flex:0 0 38px;
    }
    .pv-title{ font-weight: 900; color:#0b1220; font-size: 13px; }
    .pv-hint{ margin-top: 4px; font-size: 12px; color:#667085; font-weight: 650; }
    .pv-fname{
      margin-top: 8px; font-size: 12px; font-weight: 900;
      color:#065f46; background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.22);
      padding: 6px 8px; border-radius: 999px; display: inline-block;
      max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    .pv-foot{ padding:12px 16px; border-top:1px solid rgba(0,0,0,.06); display:flex; gap:10px; align-items:center; justify-content:flex-end; flex-wrap:wrap; }
    .pv-btn{
      border-radius:999px; padding:10px 16px; font-weight:900; cursor:pointer;
      border:1px solid rgba(0,0,0,.10); background:#fff; color:#0b1220;
      transition: background .15s ease, transform .12s ease, border-color .15s ease;
    }
    .pv-btn:hover{ background:#f8fafc; border-color: rgba(0,0,0,.16); transform: translateY(-1px); }
    .pv-btn-solid{ border:none; background:#111827; color:#fff; font-weight:950; }
    .pv-btn-solid:hover{ background:#0b1220; box-shadow:0 14px 30px rgba(2,6,23,0.18); }
    .pv-mini{ font-size:12px; color:#475467; font-weight:900; }
</style>

<div class="pv-toast-wrap" id="pvToastWrap" aria-live="polite" aria-atomic="true"></div>

<div class="doc-viewer-breakout" style="margin-top:-30px;">
  <div class="doc-viewer-layout">

    <aside class="doc-sidebar">
      <div class="sidebar-header">
        <a href="{{ $backUrl }}" class="btn-back">
          <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
          Volver al listado
        </a>

        <h1 class="doc-title">{{ $document->title }}</h1>
        <span class="doc-badge">{{ strtoupper(explode('/', $mime)[1] ?? 'FILE') }}</span>
      </div>

      <div class="sidebar-body">
        <div class="meta-card">
          <div class="meta-group">
            <div class="meta-label">Subido por</div>
            <div class="meta-value" style="display:flex;align-items:center;gap:8px;">
              <div style="width:24px;height:24px;background:#E5E0D8;border-radius:50%;display:grid;place-items:center;font-size:10px;font-weight:bold;">
                {{ mb_substr($uploaderName, 0, 1) }}
              </div>
              {{ $uploaderName }}
            </div>
          </div>

          <div class="meta-group">
            <div class="meta-label">Fecha de subida</div>
            <div class="meta-value">{{ $document->created_at->format('d/m/Y h:i A') }}</div>
          </div>
        </div>

        <div class="meta-group">
          <div class="meta-label">Nombre original</div>
          <div class="meta-value" style="font-size:13px;">{{ $fileName }}</div>
        </div>

        @if($document->description)
          <div class="meta-group" style="margin-top:24px;">
            <div class="meta-label">Descripción</div>
            <p style="font-size:13px;line-height:1.6;color:var(--ui-text-muted);margin:0;">
              {{ $document->description }}
            </p>
          </div>
        @endif

        @if($allowFicticioHere)
          <div class="meta-card" style="margin-top:18px;">
            <div class="meta-group">
              <div class="meta-label">Ficticio</div>
              <div class="meta-value">
                @if($hasFicticio)
                  Este documento ya tiene ficticio.
                @else
                  Aún no tiene ficticio.
                @endif
              </div>
            </div>
          </div>
        @endif
      </div>

      <div class="sidebar-footer">

        {{-- ✅ Si YA tiene ficticio: NO mostrar el botón negro "Descargar Documento" --}}
        @if(!$hasFicticio)
          <a href="{{ route('partcontable.documents.download', $document) }}" class="btn btn-primary">
            Descargar Documento
          </a>
        @endif

        {{-- Si tiene ficticio: toggle + descargas juntas --}}
        @if($hasFicticio)
          <button type="button" class="btn btn-toggle" id="pvTogglePreview" data-mode="main">
            Ver ficticio
          </button>

          <div class="btn-row">
            <a href="{{ route('partcontable.documents.download', $document) }}" class="btn btn-secondary">
              Descargar Doc
            </a>

            <a href="{{ route('partcontable.documents.ficticio.download', $document) }}" class="btn btn-ficticio-outline">
              Descargar Fic
            </a>
          </div>
        @endif

        {{-- Subir ficticio cuando aplica --}}
        @if($allowFicticioHere && !$hasFicticio)
          <button type="button" class="btn btn-ficticio" id="pvOpenFicticio">
            Subir Ficticio
          </button>
        @endif
      </div>
    </aside>

    <main class="doc-stage">
      <div class="viewer-frame" id="pvViewerFrame">

        <div class="viewer-layer active" id="pvLayerMain" data-layer="main" aria-hidden="false">
          @if($isImage)
            <img src="{{ $url }}" alt="{{ $document->title }}">
          @elseif($isVideo)
            <video controls>
              <source src="{{ $url }}" type="{{ $mime }}">
            </video>
          @elseif($isPdf)
            <iframe src="{{ $url }}#toolbar=0" title="Visor PDF"></iframe>
          @else
            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;color:var(--ui-text-muted);padding:24px;">
              <div>
                <svg style="width:48px;height:48px;margin:0 auto 16px auto;opacity:.5;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                <p>No hay vista previa disponible.</p>
              </div>
            </div>
          @endif
        </div>

        @if($hasFicticio && $ficticioUrl)
          <div class="viewer-layer" id="pvLayerFict" data-layer="fict" aria-hidden="true">
            @if($fictIsImage)
              <img src="{{ $ficticioUrl }}" alt="{{ $document->title }} (ficticio)">
            @elseif($fictIsVideo)
              <video controls>
                <source src="{{ $ficticioUrl }}" type="{{ $fictMime ?: 'video/mp4' }}">
              </video>
            @elseif($fictIsPdf)
              <iframe src="{{ $ficticioUrl }}#toolbar=0" title="Visor PDF Ficticio"></iframe>
            @else
              <iframe src="{{ $ficticioUrl }}" title="Archivo ficticio"></iframe>
            @endif
          </div>
        @endif

      </div>
    </main>

  </div>
</div>

{{-- ✅ MODAL FICTICIO (NO SE QUITA) --}}
@if($allowFicticioHere && !$hasFicticio)
<div class="pv-modal" id="pvFicticioModal" aria-hidden="true" aria-labelledby="pvFicticioTitle" role="dialog">
  <div class="pv-backdrop" data-pv-close="1"></div>

  <div class="pv-panel" role="document">
    <div class="pv-head">
      <div>
        <div class="kicker">Archivo adicional</div>
        <h3 id="pvFicticioTitle">Subir ficticio</h3>
        <div class="sub">Documento: <strong>{{ $document->title }}</strong></div>
      </div>
      <button type="button" class="pv-x" data-pv-close="1" aria-label="Cerrar">✕</button>
    </div>

    <form id="pvFicticioForm" enctype="multipart/form-data" class="pv-body">
      @csrf

      <input id="pvFicticioFile" type="file"
        accept=".pdf,.jpg,.jpeg,.png,.webp,.gif,.svg,.mp4,.mov,.doc,.docx,.xls,.xlsx,.heic,.heif"
        style="display:none" />

      <div class="pv-dropzone" id="pvDropzone" role="button" tabindex="0" aria-label="Seleccionar archivo ficticio">
        <div class="pv-ic" aria-hidden="true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="17 8 12 3 7 8"></polyline>
            <line x1="12" y1="3" x2="12" y2="15"></line>
          </svg>
        </div>
        <div>
          <div class="pv-title">Arrastra tu archivo aquí o <span style="text-decoration:underline; text-underline-offset:3px;">selecciónalo</span></div>
          <div class="pv-hint">PDF, imagen, video u Office.</div>
          <div class="pv-fname" id="pvFileName" style="display:none;"></div>
        </div>
      </div>

      <div class="pv-foot">
        <button type="button" class="pv-btn" data-pv-close="1">Cancelar</button>
        <button type="submit" class="pv-btn pv-btn-solid" id="pvSubmit">Subir</button>
        <span class="pv-mini" id="pvLoading" style="display:none;">Subiendo...</span>
      </div>
    </form>
  </div>
</div>
@endif

<script>
(function(){
  'use strict';

  const CSRF   = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const ORIGIN = @json(request()->getSchemeAndHttpHost());
  const uploadUrl   = `${ORIGIN}/partcontable/documents/{{ $document->id }}/ficticio`;

  function toast(type, title, message){
    const wrap = document.getElementById('pvToastWrap');
    if(!wrap) return;

    const t = document.createElement('div');
    const isErr  = (type === 'err');
    const isInfo = (type === 'info');

    t.className = 'pv-toast' + (isErr ? ' err' : (isInfo ? ' info' : ''));
    t.innerHTML = `
      <div class="ic">${isErr ? '!' : (isInfo ? 'i' : '✓')}</div>
      <div style="flex:1;">
        <div class="t1"></div>
        <div class="t2"></div>
      </div>
      <button class="x" type="button" aria-label="Cerrar">✕</button>
    `;
    t.querySelector('.t1').textContent = title || (isErr ? 'Error' : (isInfo ? 'Info' : 'Listo'));
    t.querySelector('.t2').textContent = message || '';
    wrap.appendChild(t);

    requestAnimationFrame(() => t.classList.add('show'));

    const kill = () => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 220);
    };
    t.querySelector('.x').addEventListener('click', kill);
    setTimeout(kill, isInfo ? 3800 : 3200);
  }

  async function safeJson(res){
    const txt = await res.text().catch(()=> '');
    try { return { json: JSON.parse(txt), text: txt }; }
    catch(e){ return { json: null, text: txt }; }
  }

  function extOf(name){
    if(!name) return '';
    const i = name.lastIndexOf('.');
    return i >= 0 ? name.slice(i+1).toLowerCase() : '';
  }

  // Toggle preview
  const toggleBtn = document.getElementById('pvTogglePreview');
  const layerMain = document.getElementById('pvLayerMain');
  const layerFict = document.getElementById('pvLayerFict');

  function setMode(mode){
    if(!layerMain) return;

    if(mode === 'fict' && layerFict){
      layerMain.classList.remove('active');
      layerMain.setAttribute('aria-hidden','true');

      layerFict.classList.add('active');
      layerFict.setAttribute('aria-hidden','false');

      if(toggleBtn){
        toggleBtn.dataset.mode = 'fict';
        toggleBtn.textContent = 'Ver documento';
      }
      return;
    }

    layerMain.classList.add('active');
    layerMain.setAttribute('aria-hidden','false');

    if(layerFict){
      layerFict.classList.remove('active');
      layerFict.setAttribute('aria-hidden','true');

      const v = layerFict.querySelector('video');
      if(v){ try{ v.pause(); }catch(_){} }
    }

    if(toggleBtn){
      toggleBtn.dataset.mode = 'main';
      toggleBtn.textContent = 'Ver ficticio';
    }
  }

  if(toggleBtn){
    toggleBtn.addEventListener('click', (e)=>{
      e.preventDefault();
      const current = toggleBtn.dataset.mode || 'main';
      if(current === 'main') setMode('fict');
      else setMode('main');
    });
  }

  // Modal ficticio
  const modal   = document.getElementById('pvFicticioModal');
  const btnOpen = document.getElementById('pvOpenFicticio');
  const dz      = document.getElementById('pvDropzone');
  const input   = document.getElementById('pvFicticioFile');
  const fname   = document.getElementById('pvFileName');
  const form    = document.getElementById('pvFicticioForm');
  const loading = document.getElementById('pvLoading');
  const submit  = document.getElementById('pvSubmit');

  function openModal(){
    if(!modal) return;
    modal.setAttribute('aria-hidden','false');
    setTimeout(()=> dz?.focus(), 50);
  }
  function closeModal(){
    if(!modal) return;
    modal.setAttribute('aria-hidden','true');
    if(input) input.value = '';
    if(fname){ fname.style.display='none'; fname.textContent=''; }
  }
  function refreshName(){
    const f = input?.files?.[0];
    if(!fname) return;
    if(f){
      const ext = extOf(f.name);
      const m = f.type || '(sin mime)';
      fname.textContent = `Seleccionado: ${f.name}  •  ext:${ext || '-'}  •  mime:${m}`;
      fname.style.display = 'inline-block';
    }else{
      fname.style.display = 'none';
      fname.textContent = '';
    }
  }

  document.querySelectorAll('[data-pv-close="1"]').forEach(el=>{
    el.addEventListener('click', (e)=>{ e.preventDefault(); closeModal(); });
  });
  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape' && modal && modal.getAttribute('aria-hidden') === 'false'){
      closeModal();
    }
  });

  if(btnOpen) btnOpen.addEventListener('click', openModal);

  if(dz && input){
    dz.addEventListener('click', (e)=>{ e.preventDefault(); input.click(); });
    dz.addEventListener('keydown', (e)=>{
      if(e.key === 'Enter' || e.key === ' '){
        e.preventDefault();
        input.click();
      }
    });
  }
  if(input) input.addEventListener('change', refreshName);

  // Upload ficticio
  if(form){
    form.addEventListener('submit', async (e)=>{
      e.preventDefault();

      const file = input?.files?.[0];
      if(!file){
        toast('err','Falta archivo','Selecciona un archivo para subir.');
        return;
      }

      const ext = extOf(file.name);
      const allowedExt = new Set(['pdf','jpg','jpeg','png','webp','gif','svg','mp4','mov','doc','docx','xls','xlsx']);
      if(ext && !allowedExt.has(ext)){
        toast('err','Formato no permitido',
          `Tu archivo parece ser .${ext}.\nNombre: ${file.name}\nMIME: ${file.type || '(sin mime)'}`
        );
        return;
      }

      const fd = new FormData();
      fd.append('file', file);
      fd.append('_token', CSRF);

      try{
        if(loading) loading.style.display = 'inline';
        if(submit) submit.disabled = true;

        const res = await fetch(uploadUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          },
          body: fd
        });

        const { json, text } = await safeJson(res);

        if(!res.ok){
          const serverMsg =
            (json && json.errors && json.errors.file && json.errors.file[0]) ? json.errors.file[0] :
            (json && json.message) ? json.message :
            (text ? text.slice(0, 220) : `Error HTTP ${res.status}`);

          const extra = `\n\nArchivo: ${file.name}\nExt: ${ext || '-'}\nMIME: ${file.type || '(sin mime)'}`;
          toast('err','No se pudo', serverMsg + extra);
          return;
        }

        toast('ok','Subido','Ficticio subido y ligado correctamente.');
        closeModal();
        setTimeout(()=> window.location.reload(), 650);

      }catch(err){
        toast('err','Error','Error de red al subir ficticio.');
      }finally{
        if(loading) loading.style.display = 'none';
        if(submit) submit.disabled = false;
      }
    });
  }

})();
</script>

@endsection
