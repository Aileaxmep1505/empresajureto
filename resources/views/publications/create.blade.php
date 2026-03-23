@extends('layouts.app')

@section('title', 'Subir publicación')

@section('content')
@php
  $v = fn($k,$d=null) => old($k,$d);
@endphp
<link rel="stylesheet" href="{{ asset('css/publications.css') }}?v={{ time() }}">
<style>
  #pubCreateClean{
    --glass-bg: rgba(255,255,255,.78);
    --glass-brd: rgba(148,163,184,.22);
    --deep-shadow: 0 22px 60px rgba(15,23,42,.10);
  }
  #pubCreateClean .pageHead{
    align-items: flex-start;
    margin-bottom: 20px;
  }
  #pubCreateClean .titleRow{
    letter-spacing: -.02em;
  }
  #pubCreateClean .subtitle{
    max-width: 840px;
    line-height: 1.6;
  }
  #pubCreateClean .card{
    background: linear-gradient(180deg, rgba(255,255,255,.94), rgba(248,250,252,.90));
    border: 1px solid var(--glass-brd);
    box-shadow: var(--deep-shadow);
    backdrop-filter: blur(14px);
  }
  #pubCreateClean .cardHead{
    border-bottom-color: rgba(148,163,184,.18);
  }
  #pubCreateClean .drop{
    position: relative;
    border: 1px dashed rgba(59,130,246,.28);
    background:
      radial-gradient(circle at top right, rgba(59,130,246,.08), transparent 35%),
      radial-gradient(circle at bottom left, rgba(16,185,129,.08), transparent 35%),
      linear-gradient(180deg, rgba(255,255,255,.88), rgba(248,250,252,.82));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.75);
  }
  #pubCreateClean .drop::after{
    content:'Cualquier archivo';
    position:absolute;
    right:14px;
    bottom:12px;
    font-size:11px;
    font-weight:800;
    letter-spacing:.06em;
    text-transform:uppercase;
    color:rgba(71,85,105,.72);
  }
  #pubCreateClean .premiumHint{
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid rgba(148,163,184,.18);
    background: linear-gradient(180deg, rgba(248,250,252,.95), rgba(255,255,255,.92));
    color: #334155;
    font-size: 12px;
    line-height: 1.55;
  }
  #pubCreateClean .premiumHint strong{
    color:#0f172a;
  }
  #pubCreateClean .aiBanner{
    margin-top: 12px;
    padding: 11px 14px;
    border-radius: 12px;
    border: 1px solid rgba(59,130,246,.16);
    background: rgba(59,130,246,.06);
    color: #1e3a8a;
    font-size: 12px;
    display:none;
  }
  #pubCreateClean .aiBanner.show{
    display:block;
  }
  #pubCreateClean .multiItem{
    overflow: hidden;
    border: 1px solid rgba(148,163,184,.16);
    box-shadow: 0 10px 30px rgba(15,23,42,.06);
  }
  #pubCreateClean .miHead{
    background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(248,250,252,.82));
  }
  #pubCreateClean .miniArea{
    min-height: 46px;
  }
</style>
<div class="container py-5" id="pubCreateClean">
  {{-- ✅ Overlay loader --}}
  <div class="overlay" id="aiOverlay" aria-hidden="true">
    <div class="grain"></div>
    <div class="box">
      <div class="boxTop">
        <div class="t">Analizando documentos…</div>
        <div class="s" id="ovFile">—</div>
      </div>

      <div class="loader-wrapper" aria-label="Generando">
        <span class="loader-letter">G</span>
        <span class="loader-letter">e</span>
        <span class="loader-letter">n</span>
        <span class="loader-letter">e</span>
        <span class="loader-letter">r</span>
        <span class="loader-letter">a</span>
        <span class="loader-letter">n</span>
        <span class="loader-letter">d</span>
        <span class="loader-letter">o</span>
        <div class="loader"></div>
      </div>

      <div class="bar"><span id="ovBar"></span></div>
      <div style="margin-top:10px; color:rgba(255,255,255,.78); font-size:12px; font-weight:700;">
        <span id="ovTxt">Preparando…</span>
      </div>
    </div>
  </div>

  <div class="pageHead">
    <div>
      <h1 class="titleRow">
        @include('publications.partials.icons', ['name' => 'upload'])
        Subir publicación
      </h1>
      <div class="subtitle">
        Carga uno o varios archivos y revisa la extracción antes de guardar. El flujo acepta cualquier archivo. La IA extrae mejor en PDF, imágenes y archivos con texto legible, y si no puede leer uno te deja corregirlo manualmente sin romper el lote.
      </div>
    </div>
    <div class="topActions">
      <a class="btnx" href="{{ route('publications.index') }}">
        @include('publications.partials.icons', ['name' => 'arrowLeft'])
        Volver
      </a>
    </div>
  </div>
  @if($errors->any())
    <div class="card" style="margin-bottom:14px; border-color: var(--rose-brd);">
      <div class="cardBody" style="padding:14px 16px;">
        <ul style="margin:0; padding-left:18px; color: var(--rose-ink); font-size:13px;">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    </div>
  @endif
  <form id="pubCreateForm" action="{{ route('publications.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="ai_extract" id="ai_extract" value="1">
    <input type="hidden" name="ai_skip" id="ai_skip" value="0">

    {{-- ✅ Single payload (cuando subes 1) --}}
    <input type="hidden" name="ai_payload" id="ai_payload" value="">

    {{-- ✅ Bulk payload (cuando subes varios) --}}
    <input type="hidden" name="ai_payload_bulk" id="ai_payload_bulk" value="">
    <div id="fpInputs"></div>

    <input type="hidden" name="ai_tax_mode" id="ai_tax_mode" value="included">
    <input type="hidden" name="ai_tax_rate" id="ai_tax_rate" value="0.16">

    <div class="grid">
      {{-- COLUMNA IZQUIERDA: DETALLES --}}
      <div class="stack">
        <div class="card">
          <div class="cardHead">
            <div class="cardTitle">@include('publications.partials.icons', ['name' => 'edit']) Detalles Generales</div>
          </div>
          <div class="cardBody stack">

            <div class="field @error('title') invalid @enderror">
              <input type="text" name="title" id="f-title" value="{{ $v('title') }}" placeholder=" " required>
              <label for="f-title">Título de la publicación</label>
            </div>

            <div class="switchWrap">
              <div class="switchText">
                <div>Tipo de operación</div>
                <small>¿Es una factura de compra o venta?</small>
              </div>
              <div class="catToggle">
                <input type="radio" name="category" value="compra" id="cat-compra" class="hidden" {{ $v('category', 'compra') == 'compra' ? 'checked' : '' }}>
                <label for="cat-compra" class="catOption opt-compra">Compra</label>
                <input type="radio" name="category" value="venta" id="cat-venta" class="hidden" {{ $v('category') == 'venta' ? 'checked' : '' }}>
                <label for="cat-venta" class="catOption opt-venta">Venta</label>
              </div>
            </div>

            <div class="field">
              <textarea name="description" id="f-desc" placeholder=" ">{{ $v('description') }}</textarea>
              <label for="f-desc">Descripción (Opcional)</label>
            </div>

            <div class="switchWrap">
              <div class="switchText">
                <div>Fijar publicación</div>
                <small>Mostrar al principio de la lista.</small>
              </div>
              <label class="switch">
                <input type="checkbox" name="pinned" value="1" {{ $v('pinned') ? 'checked' : '' }}>
                <span class="track"><span class="thumb"></span></span>
              </label>
            </div>

            <div style="margin-top:10px; display:flex; gap:10px; justify-content:flex-end;">
              <button class="btnx mint" type="submit" id="submitBtn">
                @include('publications.partials.icons', ['name' => 'check'])
                Guardar Publicación
              </button>
            </div>

          </div>
        </div>
      </div>

      {{-- COLUMNA DERECHA: ARCHIVO + EXTRACTOR --}}
      <div class="stack">
        <div class="card">
          <div class="cardHead">
            <div class="cardTitle">@include('publications.partials.icons', ['name' => 'paperclip']) Documento</div>
            <div style="display:flex; gap:6px;">
              <span class="btnx blue tiny hidden" id="pillAiRun">Procesando...</span>
              <span class="btnx mint tiny hidden" id="pillAiOk">Extraído</span>
              <span class="btnx rose tiny hidden" id="pillAiFail">Error</span>
            </div>
          </div>

          <div class="cardBody">
            {{-- ✅ MULTI: files[] --}}
            <input type="file" name="files[]" id="f-file" style="display:none;" multiple required accept="*/*">

            <div class="drop" id="dropZone" title="Click para seleccionar archivos">
              <div class="fileRow">
                <div style="display:flex; gap:12px; align-items:center; min-width:0;">
                  <div style="background:var(--blue-bg); color:var(--blue-ink); padding:8px; border-radius:8px;">
                    @include('publications.partials.icons', ['name' => 'file'])
                  </div>
                  <div style="min-width:0;">
                    <div class="fileName" id="fileName">Seleccionar archivo(s)...</div>
                    <div class="fileMini"><span id="fileType">Cualquier tipo de archivo</span> • <span id="fileSize">Máx. 50 MB por archivo</span></div>
                  </div>
                </div>
                <label class="btnx ghost tiny" for="f-file">Examinar</label>
              </div>
            </div>

                        </div>

            <div class="premiumHint">
              <strong>Modo inteligente:</strong> la IA intenta leer el archivo automáticamente. Funciona mejor con PDF, imágenes y documentos con texto legible. Si no detecta renglones o el archivo es binario, puedes corregirlo aquí mismo sin perder la subida.
            </div>

            <div class="aiBanner" id="aiBanner"></div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
              <small id="aiStatus" style="color:var(--muted); font-size:12px;">Esperando archivo(s)...</small>
              <div style="display:flex; gap:6px;">
                <button type="button" class="btnx ghost tiny hidden" id="btnClearAi">Limpiar</button>
                <button type="button" class="btnx blue tiny" id="btnRetry" disabled>Analizar IA</button>
                <button type="button" class="btnx ghost tiny" id="btnSkipIA">Manual</button>
              </div>
            </div>

            {{-- ✅ MULTI: resultados por documento (editable) --}}
            <div class="hidden" id="multiBox" style="margin-top:14px;">
              <div class="multiList" id="multiList"></div>
            </div>

            {{-- ✅ SINGLE: editor --}}
            <div class="hidden" id="aiResult">
              <div class="tableWrap">

                <div class="docMetaRow">
                  <input class="miniField" id="docSupplier" placeholder="Proveedor (ej. Office Depot, Walmart, etc.)">
                  <input class="miniField" id="docDatetime" type="datetime-local" placeholder="Fecha del documento">
                </div>

                <div class="tblHeader">
                  <div>Concepto</div>
                  <div style="text-align:right;">Cant.</div>
                  <div style="text-align:right;">Precio</div>
                  <div style="text-align:right;">Total</div>
                  <div>Unidad</div>
                  <div></div>
                </div>

                <div id="aiEditRows" class="aiRowsArea"></div>

                <div class="tblFooter">
                  <div style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="button" class="btnx ghost tiny" id="btnAiAddRow">+ Fila</button>

                    <div class="ivaToggle" title="Si el total del documento YA incluye IVA, déjalo activado. Si no, desactívalo y lo sumamos.">
                      <input type="checkbox" id="taxIncluded" checked>
                      <label for="taxIncluded">Total ya incluye IVA</label>
                    </div>
                  </div>

                  <div class="totalsBox">
                    <div class="totRow"><span>Subtotal</span><strong id="aiSubtotal">$0.00</strong></div>
                    <div class="totRow"><span>IVA (16%)</span><strong id="aiTax">$0.00</strong></div>
                    <div class="totBig">
                      <div><small>Total documento</small></div>
                      <div><span id="aiTotal">$0.00</span></div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            {{-- ✅ SINGLE: manual --}}
            <div class="hidden" id="manualBox" style="margin-top:16px; border-top:1px solid rgba(15,23,42,.1); padding-top:16px;">
              <h4 style="font-size:13px; color:var(--ink); margin:0 0 10px 0;">Captura Manual</h4>

              <div class="docMetaRow" style="margin-bottom:10px; border:1px solid rgba(15,23,42,.08); border-radius:12px;">
                <input class="miniField" id="m_supplier" placeholder="Proveedor (manual)">
                <input class="miniField" id="m_datetime" type="datetime-local" placeholder="Fecha (manual)">
              </div>

              <div class="manualGrid">
                <input class="miniField" id="m_name" placeholder="Descripción del ítem">
                <input class="miniField num" id="m_qty" placeholder="1">
                <input class="miniField num" id="m_price" placeholder="0.00">
              </div>
              <div class="manualGrid2">
                <input class="miniField" id="m_unit" placeholder="Unidad (pza)">
                <button type="button" class="btnx blue tiny" id="btnAddRow">Agregar</button>
              </div>

              <div class="tableWrap" style="min-height:auto;">
                <table>
                  <thead><tr><th>Ítem</th><th align="right">Cant.</th><th align="right">Total</th><th></th></tr></thead>
                  <tbody id="mTbody"></tbody>
                  <tfoot><tr><td colspan="2" align="right">Total</td><td align="right" id="mTotal">$0.00</td><td></td></tr></tfoot>
                </table>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.getElementById('pubCreateForm');

      const fileInput = document.getElementById('f-file');
      const dropZone = document.getElementById('dropZone');

      const aiEditRows = document.getElementById('aiEditRows');

      const aiPayloadHidden = document.getElementById('ai_payload');
      const aiPayloadBulkHidden = document.getElementById('ai_payload_bulk');
      const fpInputs = document.getElementById('fpInputs');

      const aiSubtotalEl = document.getElementById('aiSubtotal');
      const aiTaxEl = document.getElementById('aiTax');
      const aiTotalEl = document.getElementById('aiTotal');

      const taxIncluded = document.getElementById('taxIncluded');
      const taxModeHidden = document.getElementById('ai_tax_mode');
      const taxRateHidden = document.getElementById('ai_tax_rate');

      const docSupplier = document.getElementById('docSupplier');
      const docDatetime = document.getElementById('docDatetime');
      const mSupplier = document.getElementById('m_supplier');
      const mDatetime = document.getElementById('m_datetime');

      const aiExtractHidden = document.getElementById('ai_extract');
      const aiSkipHidden = document.getElementById('ai_skip');

      const multiBox = document.getElementById('multiBox');
      const multiList = document.getElementById('multiList');

      const aiResult = document.getElementById('aiResult');
      const manualBox = document.getElementById('manualBox');

      const fileNameEl = document.getElementById('fileName');
      const fileSizeEl = document.getElementById('fileSize');
      const aiStatus = document.getElementById('aiStatus');
      const aiBanner = document.getElementById('aiBanner');

      const btnRetry = document.getElementById('btnRetry');
      const btnSkipIA = document.getElementById('btnSkipIA');
      const btnClearAi = document.getElementById('btnClearAi');

      const overlay = document.getElementById('aiOverlay');
      const ovFile = document.getElementById('ovFile');
      const ovBar = document.getElementById('ovBar');
      const ovTxt = document.getElementById('ovTxt');

      // ✅ Icono file (para usar en templates JS sin romper)
      const iconFileHtml = @json(view('publications.partials.icons', ['name' => 'file'])->render());

      let aiRows = [];
      let manualRows = [];

      // ✅ documento single
      let aiDoc = { supplier_name:'', document_datetime:'' };

      // ✅ multi store
      const multi = new Map(); // fp -> object

      const money = n => '$' + Number(n||0).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
      const num = v => parseFloat(String(v ?? '').replace(/[^0-9.\-]/g,'')) || 0;
      const cleanTxt = s => String(s||'').trim();

      function escapeHtml(str){
        return String(str||'')
          .replaceAll('&','&amp;')
          .replaceAll('<','&lt;')
          .replaceAll('>','&gt;')
          .replaceAll('"','&quot;')
          .replaceAll("'","&#039;");
      }

      function round2(n){ return Math.round((Number(n||0) + Number.EPSILON) * 100) / 100; }

      function currentTaxRate(){
        const r = parseFloat(taxRateHidden?.value ?? '0.16');
        return isFinite(r) ? r : 0.16;
      }

      function toDatetimeLocal(val){
        if(!val) return '';
        const s = String(val).trim();
        if(s.includes('T')) return s.slice(0,16);
        const parts = s.split(' ');
        if(parts.length >= 2) return (parts[0] + 'T' + parts[1].slice(0,5));
        return s;
      }

      function fromDatetimeLocal(val){
        if(!val) return '';
        const s = String(val).trim();
        if(!s) return '';
        if(s.includes('T')){
          const [d,t] = s.split('T');
          return d + ' ' + (t.length === 5 ? (t + ':00') : t);
        }
        return s;
      }

      function autoGrowTextarea(el){
        if(!el) return;
        el.style.height = 'auto';
        el.style.height = Math.min(el.scrollHeight, 220) + 'px';
      }

      function toggleState(s){
        document.getElementById('pillAiRun').classList.add('hidden');
        document.getElementById('pillAiOk').classList.add('hidden');
        document.getElementById('pillAiFail').classList.add('hidden');
        if(s === 'run') document.getElementById('pillAiRun').classList.remove('hidden');
        if(s === 'ok') document.getElementById('pillAiOk').classList.remove('hidden');
        if(s === 'fail') document.getElementById('pillAiFail').classList.remove('hidden');
      }


      function setBanner(msg='', kind='info'){
        if(!aiBanner) return;
        aiBanner.textContent = msg || '';
        aiBanner.classList.toggle('show', !!msg);
        aiBanner.style.borderColor = kind === 'warn'
          ? 'rgba(245,158,11,.24)'
          : kind === 'error'
            ? 'rgba(244,63,94,.24)'
            : 'rgba(59,130,246,.18)';
        aiBanner.style.background = kind === 'warn'
          ? 'rgba(245,158,11,.10)'
          : kind === 'error'
            ? 'rgba(244,63,94,.08)'
            : 'rgba(59,130,246,.06)';
        aiBanner.style.color = kind === 'warn'
          ? '#92400e'
          : kind === 'error'
            ? '#9f1239'
            : '#1e3a8a';
      }

      function ensureAtLeastOneRow(items){
        return Array.isArray(items) && items.length
          ? items
          : [{ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' }];
      }

      function showOverlay(on, txt='', file='', pct=0){
        overlay.classList.toggle('show', !!on);
        if(on){
          ovTxt.textContent = txt || 'Analizando…';
          ovFile.textContent = file || '—';
          ovBar.style.width = Math.max(0, Math.min(100, pct)) + '%';
        }
      }

      function categoryVal(){
        return document.querySelector('input[name="category"]:checked')?.value || 'compra';
      }

      function toggleView(mode){
        // mode: 'ai' | 'manual' | 'multi' | ''
        aiResult.classList.toggle('hidden', mode !== 'ai');
        manualBox.classList.toggle('hidden', mode !== 'manual');
        multiBox.classList.toggle('hidden', mode !== 'multi');
        btnClearAi.classList.toggle('hidden', mode !== 'ai');
      }

      function syncAiDocFromInputs(){
        aiDoc.supplier_name = cleanTxt(docSupplier?.value || '');
        aiDoc.document_datetime = fromDatetimeLocal(docDatetime?.value || '');
      }

      // ✅ FIX: updateTotalsSingle (antes estaba updateTotals() y tronaba)
      docSupplier?.addEventListener('input', () => { syncAiDocFromInputs(); updateTotalsSingle(); });
      docDatetime?.addEventListener('change', () => { syncAiDocFromInputs(); updateTotalsSingle(); });

      function fingerprint(f){
        return `${f.name}|${f.size}|${f.type || ''}`;
      }

      function buildFpInputs(){
        fpInputs.innerHTML = '';
        Array.from(fileInput.files || []).forEach(f => {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'file_fps[]';
          inp.value = fingerprint(f);
          fpInputs.appendChild(inp);
        });
      }

      function updateMultiBulkHidden(){
        const payloads = [];
        for (const [fp, o] of multi.entries()){
          if(!o.doc) continue;
          payloads.push({
            fp,
            document: o.doc,
            items: o.items || [],
            notes: o.notes || null
          });
        }
        aiPayloadBulkHidden.value = payloads.length ? JSON.stringify(payloads) : '';
      }

      async function extractSingleFile(file){
        const fd = new FormData();
        fd.append('file', file);
        fd.append('category', categoryVal());

        const res = await fetch("{{ route('publications.ai.extract') }}", {
          method: 'POST',
          headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept':'application/json'},
          body: fd
        });

        const data = await res.json();
        if(!res.ok) throw new Error(data.error || 'Error en extracción');
        return data;
      }

      function makeEmptyDoc(){
        return {
          document_type: 'otro',
          supplier_name: null,
          currency: 'MXN',
          document_datetime: null,
          subtotal: 0,
          tax: 0,
          total: 0,
          tax_mode: 'manual',
          tax_rate: currentTaxRate()
        };
      }

      function recalcRowModel(r){
        const q = num(r.qty) || 0;
        const p = num(r.unit_price) || 0;
        const lt = num(r.line_total) || 0;
        if(lt <= 0 && q > 0 && p > 0) r.line_total = q * p;
        if(p <= 0 && q > 0 && lt > 0) r.unit_price = lt / q;
      }

      function computeTotalsFromItems(items, included){
        const base = (items || []).reduce((acc, r) => acc + (num(r.line_total) || 0), 0);
        const rate = currentTaxRate();

        let subtotal = 0, iva = 0, total = 0;

        if(included){
          total = base;
          subtotal = (rate > 0) ? (total / (1 + rate)) : total;
          iva = total - subtotal;
        } else {
          subtotal = base;
          iva = subtotal * rate;
          total = subtotal + iva;
        }

        return { subtotal: round2(subtotal), tax: round2(iva), total: round2(total), rate };
      }

      function renderMultiList(){
        multiList.innerHTML = '';

        const files = Array.from(fileInput.files || []);
        if(!files.length) return;

        files.forEach((f) => {
          const fp = fingerprint(f);
          if(!multi.has(fp)){
            multi.set(fp, {
              fp,
              file: f,
              status: 'queued', // queued|run|ok|fail
              err: null,
              doc: null,
              items: [],
              notes: null,
              taxIncluded: true,
              expanded: false,
            });
          }

          const o = multi.get(fp);

          const pillClass = o.status === 'ok' ? 'ok' : (o.status === 'run' ? 'run' : (o.status === 'fail' ? 'fail' : ''));
          const pillText  = o.status === 'ok' ? 'Listo' : (o.status === 'run' ? 'Analizando' : (o.status === 'fail' ? 'Error' : 'En cola'));

          const supplier = o.doc?.supplier_name || '—';
          const dt = o.doc?.document_datetime ? toDatetimeLocal(o.doc.document_datetime).replace('T',' ') : '—';
          const total = (o.doc?.total != null) ? money(o.doc.total) : '—';
          const itemsCount = (o.items?.length ?? 0);

          const wrap = document.createElement('div');
          wrap.className = 'multiItem';
          wrap.innerHTML = `
            <div class="miHead">
              <div class="miLeft">
                <div class="miIcon">${iconFileHtml}</div>
                <div style="min-width:0">
                  <div class="miName" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
                  <div class="miMeta">
                    <span>Proveedor/Cliente: <b>${escapeHtml(supplier)}</b></span>
                    <span>Fecha: <b>${escapeHtml(dt)}</b></span>
                    <span>Total: <b>${escapeHtml(total)}</b></span>
                    <span>Items: <b>${itemsCount}</b></span>
                  </div>
                  ${o.err ? `<div class="miMeta" style="color:var(--rose-ink);">Error: <b>${escapeHtml(o.err)}</b></div>` : ``}
                </div>
              </div>

              <div class="miPills">
                <span class="pill ${pillClass}">${pillText}</span>
                ${o.notes?.warnings?.length ? `<span class="pill" title="Warnings">${o.notes.warnings.length} warning(s)</span>` : ``}
                ${o.notes?.confidence != null ? `<span class="pill" title="Confianza">${Number(o.notes.confidence).toFixed(2)}</span>` : ``}
              </div>
            </div>

            <div class="miBody ${o.expanded ? '' : 'hidden'}" data-body="1"></div>

            <div style="padding:10px 12px; border-top:1px solid rgba(15,23,42,.06); display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
              <div class="miActions">
                <button type="button" class="btnx ghost tiny" data-act="toggle">${o.expanded ? 'Cerrar' : 'Editar'}</button>
                <button type="button" class="btnx blue tiny" data-act="analyze" ${o.status==='run'?'disabled':''}>Analizar</button>
                <button type="button" class="btnx amber tiny" data-act="manual">Manual</button>
              </div>
              <div class="miActions">
                <button type="button" class="btnx rose tiny" data-act="remove">Quitar</button>
              </div>
            </div>
          `;

          const body = wrap.querySelector('[data-body="1"]');

          function buildEditor(){
            if(!o.doc) o.doc = makeEmptyDoc();
            if(!Array.isArray(o.items)) o.items = [];

            o.doc.category = categoryVal();

            const included = !!o.taxIncluded;
            const totals = computeTotalsFromItems(o.items, included);

            o.doc.subtotal = totals.subtotal;
            o.doc.tax = totals.tax;
            o.doc.total = totals.total;
            o.doc.tax_mode = included ? 'included' : 'add';
            o.doc.tax_rate = totals.rate;

            const supplierVal = o.doc.supplier_name ?? '';
            const dtVal = toDatetimeLocal(o.doc.document_datetime ?? '');

            body.innerHTML = `
              <div class="tableWrap">
                <div class="docMetaRow">
                  <input class="miniField" data-k="supplier" placeholder="Proveedor / Cliente" value="${escapeHtml(supplierVal)}">
                  <input class="miniField" data-k="datetime" type="datetime-local" placeholder="Fecha operación" value="${escapeHtml(dtVal)}">
                </div>

                <div class="tblHeader">
                  <div>Concepto</div>
                  <div style="text-align:right;">Cant.</div>
                  <div style="text-align:right;">Precio</div>
                  <div style="text-align:right;">Total</div>
                  <div>Unidad</div>
                  <div></div>
                </div>

                <div class="aiRowsArea" data-rows="1"></div>

                <div class="tblFooter">
                  <div style="display:flex; gap:10px; align-items:flex-end;">
                    <button type="button" class="btnx ghost tiny" data-add="1">+ Fila</button>

                    <div class="ivaToggle">
                      <input type="checkbox" data-tax="1" ${included?'checked':''}>
                      <label>Total ya incluye IVA</label>
                    </div>
                  </div>

                  <div class="totalsBox">
                    <div class="totRow"><span>Subtotal</span><strong data-sub="1">${money(totals.subtotal)}</strong></div>
                    <div class="totRow"><span>IVA (16%)</span><strong data-taxv="1">${money(totals.tax)}</strong></div>
                    <div class="totBig">
                      <div><small>Total documento</small></div>
                      <div><span data-tot="1">${money(totals.total)}</span></div>
                    </div>
                  </div>
                </div>
              </div>
            `;

            const rowsArea = body.querySelector('[data-rows="1"]');
            const subEl = body.querySelector('[data-sub="1"]');
            const taxEl = body.querySelector('[data-taxv="1"]');
            const totEl = body.querySelector('[data-tot="1"]');
            const taxChk = body.querySelector('[data-tax="1"]');

            function syncTotalsUI(){
              const t = computeTotalsFromItems(o.items, !!o.taxIncluded);
              o.doc.subtotal = t.subtotal;
              o.doc.tax = t.tax;
              o.doc.total = t.total;
              o.doc.tax_mode = o.taxIncluded ? 'included' : 'add';
              o.doc.tax_rate = t.rate;

              subEl.textContent = money(t.subtotal);
              taxEl.textContent = money(t.tax);
              totEl.textContent = money(t.total);

              updateMultiBulkHidden();
            }

            function renderRows(){
              rowsArea.innerHTML = '';
              o.items.forEach((r, ridx) => {
                const rr = document.createElement('div');
                rr.className = 'aiEditGrid';
                rr.innerHTML = `
                  <textarea class="miniArea" data-k="item_name" placeholder="Descripción">${escapeHtml(r.item_name || '')}</textarea>
                  <input class="miniField num" data-k="qty" value="${(num(r.qty)||1)}" placeholder="1">
                  <input class="miniField num" data-k="unit_price" value="${num(r.unit_price).toFixed(2)}" placeholder="0.00">
                  <input class="miniField num" data-k="line_total" value="${num(r.line_total).toFixed(2)}" placeholder="0.00">
                  <input class="miniField" data-k="unit" value="${escapeHtml(r.unit || '')}" placeholder="pza">
                  <button type="button" class="iconBtn" data-del="1" title="Eliminar">✕</button>
                `;

                rr.querySelectorAll('input, textarea').forEach(inp => {
                  inp.addEventListener('input', (e) => {
                    const k = e.target.dataset.k;
                    const v = e.target.value;

                    if(k === 'item_name' || k === 'unit') r[k] = v;
                    else r[k] = num(v);

                    if(k === 'qty' || k === 'unit_price'){
                      const q = num(r.qty) || 0;
                      const p = num(r.unit_price) || 0;
                      r.line_total = q * p;
                      const tInp = rr.querySelector('[data-k="line_total"]');
                      if(tInp) tInp.value = num(r.line_total).toFixed(2);
                    } else if(k === 'line_total'){
                      const q = num(r.qty) || 0;
                      if(q > 0){
                        r.unit_price = num(r.line_total) / q;
                        const pInp = rr.querySelector('[data-k="unit_price"]');
                        if(pInp) pInp.value = num(r.unit_price).toFixed(2);
                      }
                    }

                    if(e.target.tagName === 'TEXTAREA') autoGrowTextarea(e.target);
                    syncTotalsUI();
                  });

                  if(inp.classList.contains('num')){
                    inp.addEventListener('blur', (e) => {
                      if(e.target.dataset.k === 'qty') e.target.value = (num(e.target.value) || 1).toString();
                      else e.target.value = num(e.target.value).toFixed(2);
                    });
                  }
                });

                autoGrowTextarea(rr.querySelector('textarea'));

                rr.querySelector('[data-del="1"]').addEventListener('click', () => {
                  o.items.splice(ridx, 1);
                  renderRows();
                  syncTotalsUI();
                });

                rowsArea.appendChild(rr);
              });

              syncTotalsUI();
            }

            // header inputs
            const inpSupplier = body.querySelector('[data-k="supplier"]');
            const inpDt = body.querySelector('[data-k="datetime"]');

            inpSupplier.addEventListener('input', () => {
              o.doc.supplier_name = cleanTxt(inpSupplier.value) || null;
              updateMultiBulkHidden();
              renderMultiList();
            });

            inpDt.addEventListener('change', () => {
              o.doc.document_datetime = fromDatetimeLocal(inpDt.value) || null;
              updateMultiBulkHidden();
              renderMultiList();
            });

            // tax included
            taxChk.addEventListener('change', () => {
              o.taxIncluded = !!taxChk.checked;
              syncTotalsUI();
              renderMultiList();
            });

            // add row
            body.querySelector('[data-add="1"]').addEventListener('click', () => {
              o.items.push({ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' });
              renderRows();
              rowsArea.scrollTop = rowsArea.scrollHeight;
            });

            if(o.items.length === 0){
              o.items.push({ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' });
            }

            renderRows();
            updateMultiBulkHidden();
          }

          // actions
          wrap.querySelector('[data-act="toggle"]').addEventListener('click', () => {
            o.expanded = !o.expanded;
            renderMultiList();
          });

          wrap.querySelector('[data-act="analyze"]').addEventListener('click', async () => {
            await analyzeOne(fp);
          });

          wrap.querySelector('[data-act="manual"]').addEventListener('click', () => {
            o.err = null;
            o.status = 'ok';
            o.notes = { warnings: ['Manual'], confidence: 0.0 };
            o.doc = makeEmptyDoc();
            o.items = [{ item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza' }];
            o.taxIncluded = true;
            o.expanded = true;
            updateMultiBulkHidden();
            renderMultiList();
          });

          wrap.querySelector('[data-act="remove"]').addEventListener('click', () => {
            const filesNow = Array.from(fileInput.files || []);
            const keep = filesNow.filter(x => fingerprint(x) !== fp);

            const dt = new DataTransfer();
            keep.forEach(x => dt.items.add(x));
            fileInput.files = dt.files;

            multi.delete(fp);
            buildFpInputs();
            updateMultiBulkHidden();
            handleSelection();
          });

          multiList.appendChild(wrap);

          if(o.expanded){
            buildEditor();
          }
        });

        updateMultiBulkHidden();
      }

      async function analyzeOne(fp){
        const o = multi.get(fp);
        if(!o) return;
        if(o.status === 'run') return;

        o.status = 'run';
        o.err = null;
        renderMultiList();

        const files = Array.from(fileInput.files || []);
        const idx = files.findIndex(x => fingerprint(x) === fp);
        const totalN = Math.max(1, files.length);

        showOverlay(true, `Analizando ${idx+1}/${totalN}`, o.file?.name || '—', ((idx) / totalN) * 100);

        try{
          const data = await extractSingleFile(o.file);

          const doc = data.document || {};
          const items = Array.isArray(data.items) ? data.items : [];
          const notes = data.notes || {};

          const normItems = ensureAtLeastOneRow(items.map(it => ({
            item_name: cleanTxt(it.item_name || ''),
            qty: num(it.qty) || 1,
            unit_price: num(it.unit_price) || 0,
            line_total: num(it.line_total) || 0,
            unit: cleanTxt(it.unit || 'pza')
          })));
          normItems.forEach(recalcRowModel);

          const totals = computeTotalsFromItems(normItems, true);

          o.doc = {
            document_type: doc.document_type || 'otro',
            supplier_name: cleanTxt(doc.supplier_name || '') || null,
            currency: doc.currency || 'MXN',
            document_datetime: cleanTxt(doc.document_datetime || '') || null,
            subtotal: totals.subtotal,
            tax: totals.tax,
            total: totals.total,
            tax_mode: 'included',
            tax_rate: totals.rate,
            category: categoryVal()
          };

          o.items = normItems;
          o.notes = notes;
          o.taxIncluded = true;
          o.status = 'ok';
          o.expanded = true;

          updateMultiBulkHidden();
          renderMultiList();
          if(data.warning){ setBanner(data.warning, 'warn'); } else { setBanner('Extracción completada. Revisa y ajusta cada documento antes de guardar.', 'info'); }
        }catch(e){
          o.status = 'fail';
          o.err = e.message || 'No se pudo extraer';
          o.notes = { warnings: ['AI failed'], confidence: 0.0 };
          setBanner(o.err || 'No se pudo extraer este documento.', 'error');
          renderMultiList();
        } finally {
          showOverlay(false);
        }
      }

      async function analyzeAll(){
        const files = Array.from(fileInput.files || []);
        if(files.length <= 1) return;

        toggleState('run');
        aiStatus.textContent = `Analizando ${files.length} documento(s) con IA...`;
        btnRetry.disabled = true;

        for(let i=0; i<files.length; i++){
          const fp = fingerprint(files[i]);
          await analyzeOne(fp);
        }

        showOverlay(false);
        toggleState('ok');
        aiStatus.textContent = 'Listo. Revisa/edita cada documento antes de guardar.';
        setBanner('Análisis terminado. Puedes editar cada documento o pasar alguno a captura manual.', 'info');
        btnRetry.disabled = false;
      }

      // ===== SINGLE =====
      async function aiExtractAutoSingle(){
        const f = fileInput.files[0];
        if(!f) return;

        toggleState('run');
        aiStatus.innerText = 'Analizando documento con IA...';

        const fd = new FormData();
        fd.append('file', f);
        fd.append('category', categoryVal());

        try{
          const res = await fetch("{{ route('publications.ai.extract') }}", {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept':'application/json'},
            body: fd
          });
          const data = await res.json();
          if(!res.ok) throw new Error(data.error || 'Error en extracción');

          aiDoc = {
            supplier_name: cleanTxt(data?.document?.supplier_name || ''),
            document_datetime: cleanTxt(data?.document?.document_datetime || '')
          };
          docSupplier.value = aiDoc.supplier_name || '';
          docDatetime.value = toDatetimeLocal(aiDoc.document_datetime || '');

          aiRows = ensureAtLeastOneRow((data.items || []).map(it => ({
            item_name: cleanTxt(it.item_name),
            qty: num(it.qty) || 1,
            unit_price: num(it.unit_price),
            line_total: num(it.line_total),
            unit: cleanTxt(it.unit) || 'pza'
          })));

          aiRows.forEach(recalcRowModel);

          renderAiEditorSingle();
          toggleView('ai');
          toggleState('ok');
          aiStatus.innerText = 'Revisa los datos extraídos. Puedes editar, agregar o borrar filas.';
          if(data.warning){
            setBanner(data.warning, 'warn');
          }else{
            setBanner('Extracción completada. Verifica proveedor, fecha y conceptos antes de guardar.', 'info');
          }
        }catch(e){
          console.error(e);
          toggleState('fail');
          aiStatus.innerText = e.message || 'No se pudo extraer información. Intenta manual.';
          setBanner(e.message || 'No se pudo analizar el archivo.', 'error');
        }
      }

      function renderAiEditorSingle(){
        aiEditRows.innerHTML = '';
        aiRows.forEach((r, idx) => {
          const div = document.createElement('div');
          div.className = 'aiEditGrid';
          div.innerHTML = `
            <textarea class="miniArea" data-k="item_name" placeholder="Descripción">${escapeHtml(r.item_name)}</textarea>
            <input class="miniField num" data-k="qty" value="${(num(r.qty)||1)}" placeholder="1">
            <input class="miniField num" data-k="unit_price" value="${num(r.unit_price).toFixed(2)}" placeholder="0.00">
            <input class="miniField num" data-k="line_total" value="${num(r.line_total).toFixed(2)}" placeholder="0.00">
            <input class="miniField" data-k="unit" value="${escapeHtml(r.unit || '')}" placeholder="pza">
            <button type="button" class="iconBtn" data-del="${idx}" title="Eliminar">✕</button>
          `;

          div.querySelectorAll('input, textarea').forEach(inp => {
            inp.addEventListener('input', (e) => {
              updateAiModelSingle(idx, e.target.dataset.k, e.target.value);
              if(e.target.tagName === 'TEXTAREA') autoGrowTextarea(e.target);
            });

            if(inp.classList.contains('num')){
              inp.addEventListener('blur', (e) => {
                if(e.target.dataset.k === 'qty') e.target.value = (num(e.target.value) || 1).toString();
                else e.target.value = num(e.target.value).toFixed(2);
              });
            }
          });

          autoGrowTextarea(div.querySelector('textarea[data-k="item_name"]'));

          div.querySelector('[data-del]').addEventListener('click', () => {
            aiRows.splice(idx, 1);
            renderAiEditorSingle();
          });

          aiEditRows.appendChild(div);
        });

        updateTotalsSingle();
      }

      function updateAiModelSingle(idx, key, val){
        if(!aiRows[idx]) return;

        if(key === 'item_name' || key === 'unit') aiRows[idx][key] = val;
        else aiRows[idx][key] = num(val);

        if(key === 'qty' || key === 'unit_price'){
          const q = num(aiRows[idx].qty) || 0;
          const p = num(aiRows[idx].unit_price) || 0;
          aiRows[idx].line_total = q * p;
          const totalInp = aiEditRows.children[idx]?.querySelector('[data-k="line_total"]');
          if(totalInp) totalInp.value = num(aiRows[idx].line_total).toFixed(2);
        } else if(key === 'line_total'){
          const q = num(aiRows[idx].qty) || 0;
          if(q > 0){
            aiRows[idx].unit_price = num(aiRows[idx].line_total) / q;
            const priceInp = aiEditRows.children[idx]?.querySelector('[data-k="unit_price"]');
            if(priceInp) priceInp.value = num(aiRows[idx].unit_price).toFixed(2);
          }
        }

        updateTotalsSingle();
      }

      document.getElementById('btnAiAddRow').addEventListener('click', () => {
        aiRows.push({item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza'});
        renderAiEditorSingle();
        aiEditRows.scrollTop = aiEditRows.scrollHeight;
      });

      taxIncluded.addEventListener('change', () => updateTotalsSingle());

      function updateTotalsSingle(){
        syncAiDocFromInputs();

        const base = aiRows.reduce((acc, r) => acc + (num(r.line_total) || 0), 0);
        const rate = currentTaxRate();

        let subtotal = 0, iva = 0, total = 0;

        if(taxIncluded.checked){
          total = base;
          subtotal = (rate > 0) ? (total / (1 + rate)) : total;
          iva = total - subtotal;
          if(taxModeHidden) taxModeHidden.value = 'included';
        }else{
          subtotal = base;
          iva = subtotal * rate;
          total = subtotal + iva;
          if(taxModeHidden) taxModeHidden.value = 'add';
        }

        aiSubtotalEl.textContent = money(subtotal);
        aiTaxEl.textContent = money(iva);
        aiTotalEl.textContent = money(total);

        aiPayloadHidden.value = JSON.stringify({
          document: {
            category: categoryVal(),
            supplier_name: aiDoc.supplier_name || null,
            document_datetime: aiDoc.document_datetime || null,
            subtotal: round2(subtotal),
            tax: round2(iva),
            total: round2(total),
            tax_mode: taxIncluded.checked ? 'included' : 'add',
            tax_rate: rate
          },
          items: aiRows.map(r => ({
            item_name: cleanTxt(r.item_name),
            qty: num(r.qty) || 1,
            unit_price: num(r.unit_price),
            line_total: num(r.line_total),
            unit: cleanTxt(r.unit) || 'pza'
          }))
        });
      }

      // ===== Manual single =====
      document.getElementById('btnAddRow').onclick = () => {
        const item_name = cleanTxt(document.getElementById('m_name').value);
        const qty = num(document.getElementById('m_qty').value) || 1;
        const unit_price = num(document.getElementById('m_price').value) || 0;
        const unit = cleanTxt(document.getElementById('m_unit').value) || 'pza';
        if(!item_name) return;

        const row = {item_name, qty, unit_price, unit};
        manualRows.push(row);

        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${escapeHtml(item_name)}</td>
          <td align="right">${qty}</td>
          <td align="right">${money(qty*unit_price)}</td>
          <td><button type="button" class="iconBtn" title="Eliminar">✕</button></td>
        `;
        tr.querySelector('button').addEventListener('click', () => {
          const idx = Array.from(document.getElementById('mTbody').children).indexOf(tr);
          if(idx >= 0) manualRows.splice(idx, 1);
          tr.remove();
          syncManualPayload();
        });
        document.getElementById('mTbody').appendChild(tr);

        ['m_name','m_qty','m_price','m_unit'].forEach(id => document.getElementById(id).value = '');
        syncManualPayload();
      };

      mSupplier?.addEventListener('input', syncManualPayload);
      mDatetime?.addEventListener('change', syncManualPayload);

      function syncManualPayload(){
        const supplier_name = cleanTxt(mSupplier?.value || '');
        const document_datetime = fromDatetimeLocal(mDatetime?.value || '');

        const tot = manualRows.reduce((a,r) => a + (num(r.qty)*num(r.unit_price)), 0);
        document.getElementById('mTotal').textContent = money(tot);

        aiPayloadHidden.value = JSON.stringify({
          document: {
            category: categoryVal(),
            supplier_name: supplier_name || null,
            document_datetime: document_datetime || null,
            subtotal: round2(tot),
            tax: 0,
            total: round2(tot),
            tax_mode: 'manual'
          },
          items: manualRows.map(r => ({
            item_name: r.item_name,
            qty: r.qty,
            unit_price: r.unit_price,
            line_total: round2(num(r.qty)*num(r.unit_price)),
            unit: r.unit || 'pza'
          }))
        });
      }

      // ===== Selection handler =====
      function handleSelection(){
        const files = Array.from(fileInput.files || []);
        buildFpInputs();

        if(!files.length){
          fileNameEl.textContent = 'Seleccionar archivo(s)...';
          fileSizeEl.textContent = 'Max 10MB';
          btnRetry.disabled = true;
          aiStatus.textContent = 'Esperando archivo(s)...';
          toggleView('');
          toggleState('');
          aiPayloadHidden.value = '';
          aiPayloadBulkHidden.value = '';
          multi.clear();
          setBanner('');
          return;
        }

        const totalBytes = files.reduce((a,f)=>a+(f.size||0),0);
        fileNameEl.textContent = (files.length === 1) ? files[0].name : `${files.length} archivos`;
        fileSizeEl.textContent = (files.length === 1) ? `${(files[0].size/1024/1024).toFixed(2)} MB` : `${(totalBytes/1024/1024).toFixed(2)} MB total`;

        aiSkipHidden.value = '0';
        aiExtractHidden.value = '1';

        if(files.length === 1){
          btnRetry.disabled = false;
          btnRetry.textContent = 'Reintentar IA';
          aiStatus.textContent = 'Archivo listo. Analizando con IA...';
          setBanner('Extrayendo encabezado y conceptos del documento...', 'info');
          toggleView('ai');
          aiPayloadBulkHidden.value = '';
          multi.clear();
          aiExtractAutoSingle();
        } else {
          btnRetry.disabled = false;
          btnRetry.textContent = 'Analizar IA';
          aiStatus.textContent = `Listo. Se analizarán ${files.length} documentos aquí mismo (editable antes de guardar).`;
          setBanner('Lote listo. Cada documento quedará editable antes de guardar.', 'info');
          toggleView('multi');
          toggleState('');
          aiPayloadHidden.value = '';

          files.forEach(f => {
            const fp = fingerprint(f);
            if(!multi.has(fp)){
              multi.set(fp, { fp, file:f, status:'queued', err:null, doc:null, items:[], notes:null, taxIncluded:true, expanded:false });
            }
          });

          renderMultiList();
          analyzeAll();
        }
      }

      fileInput.addEventListener('change', handleSelection);

      // ✅ FIX: evita doble apertura del selector (label + dropzone)
      const browseLabel = document.querySelector('label[for="f-file"]');
      browseLabel?.addEventListener('click', (e) => {
        e.stopPropagation();
      });

      dropZone.addEventListener('click', (e) => {
        if (e.target.closest('label[for="f-file"]')) return;
        fileInput.click();
      });

      btnRetry.addEventListener('click', () => {
        const files = Array.from(fileInput.files || []);
        if(files.length === 1) aiExtractAutoSingle();
        else analyzeAll();
      });

      btnClearAi.addEventListener('click', () => {
        aiRows = [];
        aiEditRows.innerHTML = '';
        aiPayloadHidden.value = '';
        aiDoc = {supplier_name:'', document_datetime:''};
        docSupplier.value = '';
        docDatetime.value = '';
        toggleView('');
        toggleState('');
        aiStatus.innerText = 'Listo. Sube o reintenta con otro archivo.';
        setBanner('');
        updateTotalsSingle();
      });

      btnSkipIA.addEventListener('click', () => {
        const files = Array.from(fileInput.files || []);
        if(files.length > 1){
          aiSkipHidden.value = '1';
          aiExtractHidden.value = '0';
          aiPayloadBulkHidden.value = '';
          aiStatus.textContent = `Modo manual: se subirán ${files.length} documentos SIN análisis IA.`;
          setBanner('Modo manual activo para el lote. Se subirán sin análisis IA.', 'warn');
          toggleState('');
          return;
        }

        toggleView('manual');
        toggleState('');
        aiStatus.innerText = 'Captura manual habilitada.';
        setBanner('Captura manual activa. Puedes registrar proveedor, fecha y conceptos sin usar IA.', 'warn');
        aiSkipHidden.value = '1';
        aiExtractHidden.value = '0';
        syncManualPayload();
      });

      // submit hook: si hay bulk, guardas usando ai_payload_bulk (sin batch)
      form.addEventListener('submit', () => {
        const files = Array.from(fileInput.files || []);
        buildFpInputs();

        if(files.length > 1){
          if(aiPayloadBulkHidden.value){
            aiExtractHidden.value = '0';
            aiSkipHidden.value = '0';
          }
        }
      });

      // init
      toggleView('');
      toggleState('');
    });
  </script>
</div>
@endsection