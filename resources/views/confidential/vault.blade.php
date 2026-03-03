{{-- resources/views/confidential/vault.blade.php --}}
@extends('layouts.app')
@section('title','Vault confidencial')

@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;

  $q = $q ?? request('q','');

  $welcomeSessionKey = "conf_welcome_{$owner->id}";
  $welcomeData = session($welcomeSessionKey);
  $userName = auth()->user()->name ?? 'Usuario';
  $welcomeCloseKey = "conf_welcome_closed_{$owner->id}";
@endphp

@section('content')

<div class="pc-bg" aria-hidden="true"></div>
<div class="pc-toast-wrap" id="pcToastWrap" aria-live="polite" aria-atomic="true"></div>

<link rel="stylesheet" href="{{ asset('css/company.css') }}?v={{ time() }}">

<style>
  /* ✅ Asegura que no “desaparezcan” por estilos globales */
  .vault-wrap, .vault-wrap *{ box-sizing:border-box; }
  .vault-wrap a, .vault-wrap button{ position:relative; z-index: 10; }
  .vault-wrap{ position:relative; z-index: 2; }

  .vault-wrap{
    max-width: 1240px;
    margin: 0 auto;
    padding: 18px 14px 36px;
  }

  /* ===== Header (siempre visible) ===== */
  .vault-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
    flex-wrap:wrap;
    margin-bottom: 12px;
  }

  .vault-left{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  .pc-back{
    text-decoration:none;
    font-weight:900;
    color:#0f172a;
  }

  .conf-chip{
    display:inline-flex;
    gap:8px;
    align-items:center;
    padding:6px 10px;
    border-radius:999px;
    background: rgba(16,185,129,.10);
    border:1px solid rgba(16,185,129,.18);
    color:#065f46;
    font-weight:800;
    font-size:.85rem;
  }

  .vault-title{
    margin:0;
    font-size: 22px;
    letter-spacing: -0.02em;
    font-weight: 900;
    color:#0f172a;
    white-space:nowrap;
  }

  /* ===== Documentación row + acciones ===== */
  .vault-title-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin: 10px 0 14px;
  }
  .vault-h2{
    margin:0;
    font-size: 28px;
    letter-spacing: -0.01em;
    color:#0f172a;
    font-weight:900;
  }
  .vault-actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
  }

  /* ===== Botones (visibles siempre) ===== */
  .btn-solid, .btn-ghost{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 10px 12px;
    border-radius: 14px;
    font-weight: 900;
    letter-spacing:.01em;
    text-decoration:none;
    white-space:nowrap;
    cursor:pointer;
    user-select:none;
  }
  .btn-solid{
    border:0;
    background: rgba(2,6,23,.92);
    color:#fff;
    box-shadow: 0 10px 22px rgba(2,6,23,.14);
  }
  .btn-solid:hover{ transform: translateY(-1px); }
  .btn-ghost{
    background: rgba(255,255,255,.90);
    color:#0f172a;
    border:1px solid rgba(148,163,184,.35);
  }
  .btn-ghost:hover{ transform: translateY(-1px); }

  /* ===== Buscador pill + botón agregar a la derecha ===== */
  .vault-bar{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    margin: 10px 0 18px;
    flex-wrap:wrap;
  }

  .vault-pill{
    width:min(860px, 100%);
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 12px;
    border-radius: 999px;
    background: rgba(255,255,255,.88);
    border:1px solid rgba(148,163,184,.35);
    box-shadow: 0 10px 30px rgba(2,6,23,.06);
    backdrop-filter: blur(8px);
    position:relative;
    z-index:2;
  }
  .vault-pill:focus-within{
    border-color: rgba(59,130,246,.35);
    box-shadow: 0 12px 36px rgba(2,6,23,.10);
  }
  .vp-ic{
    width:34px;height:34px;
    border-radius:999px;
    display:grid;
    place-items:center;
    background: rgba(2,6,23,.05);
    color: rgba(2,6,23,.75);
    flex: 0 0 auto;
  }
  .vp-input{
    border:0;
    outline:0;
    background:transparent;
    width: 100%;
    font-size: 14px;
    color:#0f172a;
  }
  .vp-input::placeholder{ color: rgba(2,6,23,.40); }

  /* ===== Grid ===== */
  .vault-grid{
    display:grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    align-items: stretch;
  }
  @media(max-width: 1020px){ .vault-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); } }
  @media(max-width: 640px){ .vault-grid{ grid-template-columns: 1fr; } }

  /* ===== Card ===== */
  .vcard{
    background: rgba(255,255,255,.92);
    border: 1px solid rgba(148,163,184,.25);
    border-radius: 18px;
    box-shadow: 0 16px 42px rgba(2,6,23,.08);
    overflow:hidden;
    display:flex;
    flex-direction:column;
    min-height: 230px;
    position:relative;
  }
  .vcard:hover{
    transform: translateY(-2px);
    box-shadow: 0 22px 60px rgba(2,6,23,.12);
  }

  .vlink-cover{
    position:absolute;
    inset:0;
    z-index: 1;
  }
  .vcard-head, .vcard-body, .vcard-foot{ position:relative; z-index:2; }

  .vcard-head{
    padding: 14px 14px 0 14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 10px;
  }

  .vbadge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 7px 10px;
    border-radius: 999px;
    font-weight:900;
    font-size: 12px;
    letter-spacing:.02em;
    border:1px solid rgba(239,68,68,.18);
    background: rgba(239,68,68,.10);
    color: rgba(153,27,27,1);
  }

  .vhead-actions{ display:flex; gap:10px; align-items:center; }
  .vicon{
    width:42px;height:42px;
    border-radius: 14px;
    border:1px solid rgba(148,163,184,.25);
    background: rgba(255,255,255,.92);
    display:grid;
    place-items:center;
    cursor:pointer;
    z-index: 3;
  }
  .vicon:hover{ transform: translateY(-1px); }
  .vicon.danger{
    border-color: rgba(239,68,68,.25);
    background: rgba(239,68,68,.08);
    color: rgba(185,28,28,1);
  }

  .vcard-body{
    padding: 14px;
    display:flex;
    gap: 12px;
    align-items:flex-start;
    flex: 1 1 auto;
  }

  .vdoc-ic{
    width:52px;height:52px;
    border-radius: 18px;
    background: rgba(239,68,68,.12);
    border:1px solid rgba(239,68,68,.18);
    display:grid;
    place-items:center;
    flex: 0 0 auto;
  }
  .vdoc-ic svg{ width:22px;height:22px; }

  .vmeta{
    display:flex;
    flex-direction:column;
    gap:6px;
    min-width: 0;
    width: 100%;
  }
  .vtitle{
    font-weight: 900;
    font-size: 15px;
    letter-spacing: -0.01em;
    color:#0f172a;
    line-height:1.25;
    word-break: break-word;
  }
  .vsubmeta{
    color: rgba(15,23,42,.55);
    font-size: 13px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }

  .vcard-foot{
    padding: 12px 14px 14px 14px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    border-top: 1px solid rgba(148,163,184,.18);
    background: rgba(2,6,23,.02);
  }
  .vfoot-left{
    min-width:0;
    color: rgba(15,23,42,.55);
    font-size: 12px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .vdownload{
    width:44px;height:44px;
    border-radius: 14px;
    border:0;
    cursor:pointer;
    background: rgba(2,6,23,.92);
    color:#fff;
    display:grid;
    place-items:center;
    flex: 0 0 auto;
    box-shadow: 0 10px 22px rgba(2,6,23,.18);
    text-decoration:none;
    z-index: 3;
  }
  .vdownload:hover{ transform: translateY(-1px); }
  .vdownload.is-downloading{ opacity:.75; pointer-events:none; }
  .vdownload .spin{ display:none; }
  .vdownload.is-downloading .dl{ display:none; }
  .vdownload.is-downloading .spin{ display:block; animation: vspin .85s linear infinite; }
  @keyframes vspin{ to{ transform: rotate(360deg);} }

  .vault-empty{
    text-align:center;
    padding: 40px 10px;
    color: rgba(15,23,42,.55);
  }

  /* =========================
     ✅ Mejoras tipo “Google”
     ========================= */

  /* highlight */
  mark.qmark{
    background: rgba(250, 204, 21, .35);
    border-radius: 6px;
    padding: 0 .18em;
  }

  /* dropdown suggestions */
  .vp-suggest{
    position:absolute;
    left: 10px;
    right: 10px;
    top: calc(100% + 8px);
    background: rgba(255,255,255,.96);
    border:1px solid rgba(148,163,184,.35);
    border-radius: 16px;
    box-shadow: 0 18px 55px rgba(2,6,23,.14);
    overflow:hidden;
    display:none;
    z-index: 50;
    backdrop-filter: blur(8px);
  }
  .vp-suggest.show{ display:block; }
  .vp-s-item{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding: 10px 12px;
    cursor:pointer;
    border-top:1px solid rgba(148,163,184,.14);
  }
  .vp-s-item:first-child{ border-top:0; }
  .vp-s-item:hover,
  .vp-s-item.active{
    background: rgba(59,130,246,.08);
  }
  .vp-s-l{
    min-width:0;
    font-weight:900;
    color:#0f172a;
    font-size: 13px;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }
  .vp-s-r{
    flex:0 0 auto;
    color: rgba(15,23,42,.55);
    font-weight:800;
    font-size: 12px;
    white-space:nowrap;
  }

  /* mini loader en la lupita */
  .vp-ic.is-loading{ opacity:.75; }
  .vp-ic.is-loading svg{ animation: vspin .9s linear infinite; }
</style>

<div class="vault-wrap">
  {{-- ✅ Documentación + acciones (sí o sí visibles) --}}
  <div class="vault-title-row">
    <h2 class="vault-h2">Documentación</h2>
  </div>

  {{-- ✅ Barra superior (NO debe desaparecer) --}}
  <div class="vault-top">
    <div class="vault-left">
      <a href="{{ url()->previous() }}" class="pc-back">← Volver</a>
      <span class="conf-chip" title="Sesión desbloqueada temporalmente">🔐 PIN activo</span>

      <form method="POST" action="{{ route('confidential.vault.lock', $owner->id) }}" style="margin:0;">
        @csrf
        <input type="hidden" name="redirectTo" value="{{ route('confidential.vault', $owner->id) }}">
        <button type="submit" class="pc-btn" style="border:0;">Reingresar NIP</button>
      </form>
    </div>
  </div>

  @if(!empty($welcomeData))
    <div class="pc-welcome" id="pcWelcome" data-close-key="{{ $welcomeCloseKey }}">
      <div class="pc-welcome-left">
        <div class="pc-welcome-title">
          Bienvenido, accediste como <span class="pc-welcome-user">{{ $userName }}</span>
        </div>
        <div class="pc-welcome-sub">Acceso protegido por NIP · Tus acciones quedan registradas.</div>
      </div>
      <button type="button" class="pc-welcome-close" id="pcWelcomeClose" aria-label="Cerrar bienvenida">✕</button>
    </div>
  @endif

  {{-- ✅ Buscador centrado + botón agregar a un lado --}}
  <div class="vault-bar">
    <form id="vaultSearchForm" method="GET" action="{{ route('confidential.vault', $owner->id) }}" class="vault-pill" role="search">
      <div class="vp-ic" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="M21 21l-4.3-4.3"></path>
        </svg>
      </div>

      <input id="vaultQ" class="vp-input" type="text" name="q" value="{{ $q }}"
             placeholder="Buscar por nombre, doc_key o descripción…" autocomplete="off" />

      {{-- ✅ Sugerencias tipo Google --}}
      <div id="vpSuggest" class="vp-suggest" role="listbox" aria-label="Sugerencias"></div>
    </form>

    <a class="btn-solid" href="{{ route('confidential.documents.create', $owner->id) }}?return={{ urlencode(request()->fullUrl()) }}">
      + Agregar
    </a>
  </div>

  {{-- ✅ Resultados (se reemplazan por AJAX sin recargar) --}}
  <div id="vaultResults">
    @php
      /*
        OJO:
        Para que esto funcione tal cual, crea el partial:
        resources/views/confidential/partials/vault_results.blade.php

        (Es el que te pasé antes, con el grid + paginación).
      */
    @endphp
    @include('confidential.partials.vault_results', ['documents' => $documents])
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
(function(){
  'use strict';

  if (window.__confVaultBooted) return;
  window.__confVaultBooted = true;

  /* ===========================
   *  Toasts (igual)
   * =========================== */
  function showToast(type, title, message){
    const wrap = document.getElementById('pcToastWrap');
    if(!wrap) return;

    const t = document.createElement('div');
    const isErr  = (type === 'err');
    const isInfo = (type === 'info');

    t.className = 'pc-toast' + (isErr ? ' err' : (isInfo ? ' info' : ''));
    t.innerHTML = `
      <div class="ic">${isErr ? '!' : (isInfo ? 'i' : '✓')}</div>
      <div class="twrap">
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
    setTimeout(kill, isInfo ? 3400 : 2800);
  }

  /* ===========================
   *  Helpers download (igual)
   * =========================== */
  function parseFilenameFromContentDisposition(cd){
    if(!cd) return null;
    try{
      const mStar = cd.match(/filename\*\s*=\s*UTF-8''([^;]+)/i);
      if(mStar && mStar[1]) return decodeURIComponent(mStar[1].replace(/["']/g,'').trim());
      const m = cd.match(/filename\s*=\s*("?)([^";]+)\1/i);
      if(m && m[2]) return m[2].trim();
    }catch(_){}
    return null;
  }

  async function handleDownloadClick(e){
    e.preventDefault();
    e.stopPropagation();

    const btn = e.currentTarget;
    if(!btn || btn.classList.contains('is-downloading')) return;

    const url = btn.getAttribute('href');
    if(!url) return;

    const suggested = (btn.getAttribute('data-filename') || '').trim();
    btn.classList.add('is-downloading');
    showToast('info','Descargando','Preparando descarga…');

    try{
      const res = await fetch(url, { credentials:'same-origin' });
      if(!res.ok) throw new Error('HTTP ' + res.status);

      const cd = res.headers.get('Content-Disposition') || res.headers.get('content-disposition');
      const fromHeader = parseFilenameFromContentDisposition(cd);
      const filename = fromHeader || suggested || 'archivo';

      const blob = await res.blob();
      const blobUrl = URL.createObjectURL(blob);

      const a = document.createElement('a');
      a.href = blobUrl;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();

      setTimeout(() => URL.revokeObjectURL(blobUrl), 30000);
      showToast('ok','Listo','Descarga iniciada.');
    }catch(err){
      showToast('err','No se pudo','Error al descargar el archivo.');
    }finally{
      btn.classList.remove('is-downloading');
    }
  }

  // ✅ IMPORTANTE para rebind tras AJAX:
  window.__vaultHandleDownloadClick = handleDownloadClick;

  /* ===========================
   *  Click en card => preview
   * =========================== */
  function shouldIgnoreClick(target){
    if(!target) return false;
    return !!target.closest('a,button,form,input,textarea,select,label,.vhead-actions,.vdownload');
  }

  function bindCardClicks(scope){
    (scope || document).querySelectorAll('.vcard').forEach(function(card){
      if(card.__bound_click) return;
      card.__bound_click = true;

      const url = card.getAttribute('data-preview') || '';
      if(!url) return;

      card.addEventListener('click', function(e){
        if(shouldIgnoreClick(e.target)) return;
        window.open(url, '_blank', 'noopener');
      });

      card.setAttribute('tabindex','0');
      card.addEventListener('keydown', function(e){
        if(shouldIgnoreClick(document.activeElement)) return;
        if(e.key === 'Enter' || e.key === ' '){
          e.preventDefault();
          window.open(url, '_blank', 'noopener');
        }
      });
    });
  }

  /* ===========================
   *  Bind deletes (SweetAlert2)
   * =========================== */
  function bindDeletes(scope){
    (scope || document).querySelectorAll('.vdel-form').forEach(function(form){
      if(form.__bound_del) return;
      form.__bound_del = true;

      form.addEventListener('submit', async function(ev){
        ev.preventDefault();

        const result = await Swal.fire({
          title: '¿Eliminar documento?',
          text: 'Esta acción no se puede deshacer.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          focusCancel: true,
        });

        if(!result.isConfirmed) return;

        const url = form.action;
        const token = form.querySelector('input[name="_token"]').value;
        const methodInput = form.querySelector('input[name="_method"]');
        const method = methodInput ? methodInput.value : 'DELETE';

        try{
          const res = await fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': token,
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            credentials: 'same-origin',
            body: new URLSearchParams({'_method': method})
          });

          if(res.ok){
            const card = form.closest('.vcard');
            if(card) card.remove();
            showToast('ok','Eliminado','Documento eliminado correctamente.');
          } else {
            showToast('err','No se pudo','No se pudo eliminar el documento.');
          }
        }catch(e){
          showToast('err','Error','Error de red al intentar eliminar.');
        }
      });
    });
  }

  /* ===========================
   *  Bind downloads
   * =========================== */
  function bindDownloads(scope){
    (scope || document).querySelectorAll('.vdownload-btn').forEach(btn => {
      if(btn.__bound_dl) return;
      btn.__bound_dl = true;
      btn.addEventListener('click', window.__vaultHandleDownloadClick, { passive:false });
    });
  }

  /* ===========================
   *  Welcome close
   * =========================== */
  function bindWelcome(){
    const welcome = document.getElementById('pcWelcome');
    const closeBtn = document.getElementById('pcWelcomeClose');
    if (!welcome) return;

    const key = welcome.getAttribute('data-close-key') || 'conf_welcome_closed_global';
    const closed = localStorage.getItem(key);
    if (closed === '1') welcome.style.display = 'none';

    if (closeBtn) {
      closeBtn.addEventListener('click', function(){
        localStorage.setItem(key, '1');
        welcome.style.display = 'none';
      });
    }
  }

  /* ===========================
   * ✅ LIVE SEARCH tipo Google (AJAX, sin recargar)
   * =========================== */
  const form = document.getElementById('vaultSearchForm');
  const input = document.getElementById('vaultQ');
  const results = document.getElementById('vaultResults');
  const suggest = document.getElementById('vpSuggest');
  const ic = form ? form.querySelector('.vp-ic') : null;

  let activeIndex = -1;
  let currentSuggestions = [];
  let lastQuerySent = null;
  let aborter = null;

  function debounce(fn, wait){
    let t = null;
    return function(...args){
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), wait);
    };
  }

  function escRegExp(s){
    return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function highlight(container, q){
    if(!container) return;
    const query = (q || '').trim();
    if(query.length < 2) return;

    const rx = new RegExp(escRegExp(query), 'ig');

    container.querySelectorAll('.vhl').forEach(el => {
      const txt = el.textContent || '';
      if(!txt) return;
      if(el.querySelector('mark.qmark')) return;
      if(!rx.test(txt)) return;
      el.innerHTML = txt.replace(rx, (m) => `<mark class="qmark">${m}</mark>`);
    });
  }

  function buildUrl(q, pageUrl){
    const base = pageUrl ? new URL(pageUrl, window.location.origin) : new URL(form.action, window.location.origin);
    const params = new URLSearchParams(base.search);

    const v = (q || '').trim();
    if(v) params.set('q', v);
    else params.delete('q');

    if(!pageUrl) params.delete('page');

    base.search = params.toString();
    return base;
  }

  function setLoading(on){
    if(!ic) return;
    ic.classList.toggle('is-loading', !!on);
  }

  function closeSuggest(){
    if(!suggest) return;
    suggest.classList.remove('show');
    suggest.innerHTML = '';
    activeIndex = -1;
    currentSuggestions = [];
  }

  function openSuggest(items){
    if(!suggest) return;
    if(!items || !items.length){ closeSuggest(); return; }

    currentSuggestions = items.slice(0, 6);
    activeIndex = -1;

    suggest.innerHTML = currentSuggestions.map((it, idx) => `
      <div class="vp-s-item" role="option" data-idx="${idx}">
        <div class="vp-s-l">${(it.label || '').replace(/</g,'&lt;')}</div>
        <div class="vp-s-r">${(it.key || '').replace(/</g,'&lt;')}</div>
      </div>
    `).join('');

    suggest.classList.add('show');

    suggest.querySelectorAll('.vp-s-item').forEach(row => {
      row.addEventListener('mousedown', (e) => {
        e.preventDefault();
        const idx = parseInt(row.getAttribute('data-idx'), 10);
        const it = currentSuggestions[idx];
        if(!it) return;
        input.value = it.label || input.value;
        triggerSearch(true);
        closeSuggest();
      });
    });
  }

  function rebindDynamicHandlers(){
    bindCardClicks(results);
    bindDeletes(results);
    bindDownloads(results);
  }

  async function fetchResults(q, pageUrl){
    if(!results) return;

    const query = (q || '').trim();
    if(!pageUrl && lastQuerySent === query) return;
    lastQuerySent = query;

    if(aborter) aborter.abort();
    aborter = new AbortController();

    const urlObj = buildUrl(query, pageUrl);
    urlObj.searchParams.set('ajax', '1');

    setLoading(true);

    try{
      const res = await fetch(urlObj.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'X-Vault-Ajax': '1',
          'Accept': 'application/json'
        },
        signal: aborter.signal
      });

      if(!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();

      results.innerHTML = data.html || '';
      highlight(results, query);
      rebindDynamicHandlers();

      openSuggest(data.suggestions || []);

      // URL sin recargar
      const cleanUrl = buildUrl(query, pageUrl).toString();
      window.history.replaceState({}, '', cleanUrl);
    }catch(err){
      if(err && err.name === 'AbortError') return;
      // opcional: showToast('err','Error','No se pudo buscar.');
    }finally{
      setLoading(false);
    }
  }

  const triggerSearch = debounce(function(){
    if(!input) return;
    fetchResults(input.value, null);
  }, 220);

  if(form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      fetchResults(input.value, null);
    });
  }

  if(input){
    input.addEventListener('input', function(){
      triggerSearch();
    });

    input.addEventListener('keydown', function(e){
      if(!suggest || !suggest.classList.contains('show')) return;

      const rows = Array.from(suggest.querySelectorAll('.vp-s-item'));
      if(!rows.length) return;

      if(e.key === 'ArrowDown'){
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, rows.length - 1);
      } else if(e.key === 'ArrowUp'){
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
      } else if(e.key === 'Escape'){
        closeSuggest();
        return;
      } else if(e.key === 'Enter'){
        if(activeIndex >= 0 && rows[activeIndex]){
          e.preventDefault();
          rows[activeIndex].dispatchEvent(new MouseEvent('mousedown', { bubbles:true }));
        }
        return;
      } else {
        return;
      }

      rows.forEach((r,i)=> r.classList.toggle('active', i === activeIndex));
    });

    input.addEventListener('blur', function(){
      setTimeout(closeSuggest, 120);
    });

    input.addEventListener('focus', function(){
      if(currentSuggestions.length) openSuggest(currentSuggestions);
    });
  }

  // paginación sin recargar
  document.addEventListener('click', function(e){
    const a = e.target && e.target.closest ? e.target.closest('#vaultResults .pc-pagination a') : null;
    if(!a) return;
    e.preventDefault();
    const href = a.getAttribute('href');
    if(!href) return;
    fetchResults(input ? input.value : '', href);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });

  /* ===========================
   *  Boot inicial
   * =========================== */
  document.addEventListener('DOMContentLoaded', function(){
    bindWelcome();
    bindCardClicks(document);
    bindDeletes(document);
    bindDownloads(document);

    // highlight inicial
    highlight(results, (input && input.value) ? input.value : '');
  });
})();
</script>

@endsection