@extends('layouts.app')

@section('title', 'Subir publicación')

@section('content')
@php
  $v = fn($k,$d=null) => old($k,$d);
@endphp

<div class="container py-5" id="pubCreateClean">
  <style>
    #pubCreateClean{
      --ink:#0b1220;
      --muted:rgba(15,23,42,.62);
      --line:rgba(15,23,42,.10);
      --card:rgba(255,255,255,.96);
      --radius:16px;
      --shadow: 0 10px 28px rgba(2,6,23,.06);

      --blue-bg: rgba(59,130,246,.10);
      --blue-ink:#1d4ed8;
      --blue-brd: rgba(59,130,246,.22);

      --mint-bg: rgba(16,185,129,.10);
      --mint-ink:#047857;
      --mint-brd: rgba(16,185,129,.22);

      --rose-bg: rgba(244,63,94,.09);
      --rose-ink:#be123c;
      --rose-brd: rgba(244,63,94,.18);

      --amber-bg: rgba(245,158,11,.10);
      --amber-ink:#92400e;
      --amber-brd: rgba(245,158,11,.22);
    }

    /* --- General Layout --- */
    #pubCreateClean .pageHead{ display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom: 18px; }
    @media (max-width: 768px){ #pubCreateClean .pageHead{ flex-direction:column; gap:10px; } }

    #pubCreateClean .titleRow{ display:flex; align-items:center; gap:10px; color: var(--ink); font-size: 20px; line-height:1.2; font-weight: 600; margin:0; }
    #pubCreateClean .subtitle{ margin-top: 6px; color: var(--muted); font-size: 13px; line-height:1.55; max-width: 820px; }

    /* --- Buttons --- */
    #pubCreateClean .btnx{
      border:1px solid rgba(15,23,42,.10); background: rgba(255,255,255,.92); color: rgba(15,23,42,.85);
      padding: 10px 12px; border-radius: 12px; font-weight: 500; text-decoration:none;
      display:inline-flex; align-items:center; gap:10px; cursor:pointer; user-select:none; white-space:nowrap;
      transition: all .2s ease; box-shadow: 0 4px 12px rgba(2,6,23,.03);
    }
    #pubCreateClean .btnx:hover{ transform: translateY(-1px); box-shadow: 0 8px 20px rgba(2,6,23,.08); }
    #pubCreateClean .btnx:disabled{ opacity:.55; cursor:not-allowed; transform:none; }
    #pubCreateClean .btnx.blue{ background: var(--blue-bg); border-color: var(--blue-brd); color: var(--blue-ink); }
    #pubCreateClean .btnx.mint{ background: var(--mint-bg); border-color: var(--mint-brd); color: var(--mint-ink); }
    #pubCreateClean .btnx.rose{ background: var(--rose-bg); border-color: var(--rose-brd); color: var(--rose-ink); }
    #pubCreateClean .btnx.amber{ background: var(--amber-bg); border-color: var(--amber-brd); color: var(--amber-ink); }
    #pubCreateClean .btnx.ghost{ background: rgba(255,255,255,.6); border-color:transparent; box-shadow:none; }
    #pubCreateClean .btnx.tiny{ padding: 6px 10px; border-radius: 8px; font-size:12px; box-shadow:none; }

    /* --- Cards --- */
    #pubCreateClean .grid{ display:grid; grid-template-columns: 1fr 1fr; gap: 18px; align-items:start; }
    @media (max-width: 992px){ #pubCreateClean .grid{ grid-template-columns:1fr; gap:14px; } }

    #pubCreateClean .card{ background: var(--card); border:1px solid rgba(15,23,42,.08); border-radius: var(--radius); box-shadow: var(--shadow); overflow:hidden; }
    #pubCreateClean .cardHead{ padding: 14px 16px; border-bottom:1px solid rgba(15,23,42,.06); display:flex; align-items:center; justify-content:space-between; gap:12px; background: rgba(248,250,252,.5); }
    #pubCreateClean .cardTitle{ display:flex; align-items:center; gap:8px; color: rgba(15,23,42,.6); text-transform: uppercase; font-size: 11px; font-weight: 600; letter-spacing:.05em; margin:0; }
    #pubCreateClean .cardBody{ padding: 18px; }
    #pubCreateClean .stack{ display:flex; flex-direction:column; gap: 14px; }

    /* --- Fields --- */
    #pubCreateClean .field{ position:relative; background: #fff; border:1px solid rgba(15,23,42,.12); border-radius: 12px; padding: 18px 14px 12px; transition: all .2s; }
    #pubCreateClean .field:focus-within{ border-color: var(--blue-ink); box-shadow: 0 4px 12px rgba(59,130,246,.1); }
    #pubCreateClean .field input, #pubCreateClean .field textarea{ width:100%; border:0; outline:0; background:transparent; font-size: 14px; color: var(--ink); padding-top: 4px; font-weight: 500; }
    #pubCreateClean .field textarea{ min-height: 120px; resize: vertical; line-height:1.5; }
    #pubCreateClean .field label{ position:absolute; left:14px; top:16px; color: rgba(15,23,42,.5); font-size: 13px; transition: all .2s; pointer-events:none; }
    #pubCreateClean .field input::placeholder, #pubCreateClean .field textarea::placeholder{ color:transparent; }
    #pubCreateClean .field input:focus + label, #pubCreateClean .field input:not(:placeholder-shown) + label,
    #pubCreateClean .field textarea:focus + label, #pubCreateClean .field textarea:not(:placeholder-shown) + label{
      top:8px; transform: translateY(-2px); font-size: 10px; color: var(--blue-ink); font-weight:600;
    }

    /* --- Toggles & Switches --- */
    #pubCreateClean .switchWrap{ display:flex; align-items:center; justify-content:space-between; gap: 12px; border:1px solid rgba(15,23,42,.08); border-radius: 12px; background: rgba(255,255,255,.5); padding: 10px 12px; }
    #pubCreateClean .switchText div{ font-size:13px; font-weight:500; color:var(--ink); }
    #pubCreateClean .switchText small{ color: var(--muted); font-size: 11px; }

    #pubCreateClean .switch{ display:inline-flex; align-items:center; gap:10px; cursor:pointer; }
    #pubCreateClean .switch input{ display:none; }
    #pubCreateClean .track{ width:42px; height:24px; border-radius:99px; background: rgba(15,23,42,.15); position:relative; transition: background .2s; }
    #pubCreateClean .thumb{ width:20px; height:20px; border-radius:50%; background:#fff; position:absolute; top:2px; left:2px; box-shadow:0 2px 4px rgba(0,0,0,.1); transition:left .2s cubic-bezier(0.4, 0.0, 0.2, 1); }
    #pubCreateClean .switch input:checked + .track{ background: var(--mint-ink); }
    #pubCreateClean .switch input:checked + .track .thumb{ left:20px; }

    #pubCreateClean .catToggle{ display:flex; background: rgba(15,23,42,.05); padding: 3px; border-radius: 10px; }
    #pubCreateClean .catOption{ padding: 6px 16px; border-radius: 8px; font-size: 12px; cursor: pointer; transition: all .2s; color: rgba(15,23,42,.6); font-weight:500; }
    #pubCreateClean input[name="category"]:checked + .catOption.opt-compra{ background: #fff; color: var(--blue-ink); box-shadow: 0 2px 8px rgba(0,0,0,.05); }
    #pubCreateClean input[name="category"]:checked + .catOption.opt-venta{ background: #fff; color: var(--mint-ink); box-shadow: 0 2px 8px rgba(0,0,0,.05); }

    /* --- File Upload --- */
    #pubCreateClean .drop{
      border:2px dashed rgba(15,23,42,.15); border-radius: 16px; padding: 20px; text-align:center;
      background: radial-gradient(circle at center, rgba(59,130,246,.03), transparent 70%);
      transition: all .2s;
    }
    #pubCreateClean .drop:hover{ border-color: var(--blue-ink); background: rgba(59,130,246,.02); }
    #pubCreateClean .fileRow{ display:flex; align-items:center; justify-content:space-between; gap:14px; text-align:left; }
    #pubCreateClean .fileName{ color: var(--ink); font-weight: 500; font-size:14px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width: 220px; }
    #pubCreateClean .fileMini{ font-size: 11px; color: var(--muted); margin-top:2px; }

    /* =========================================================
       ✅ AI TABLE
       ========================================================= */
    #pubCreateClean .tableWrap{
      margin-top: 16px;
      border:1px solid rgba(15,23,42,.08);
      border-radius: 12px;
      overflow:hidden;
      background: #fff;
      display:flex;
      flex-direction:column;
      min-height: 360px;
    }
    @media (min-height: 800px){
      #pubCreateClean .tableWrap{ min-height: 460px; }
    }

    /* ✅ mini header doc meta (proveedor/fecha) */
    #pubCreateClean .docMetaRow{
      display:grid;
      grid-template-columns: 1fr 240px;
      gap:10px;
      padding: 10px 12px;
      border-bottom: 1px solid rgba(15,23,42,.06);
      background: rgba(248,250,252,.55);
    }
    @media (max-width: 992px){
      #pubCreateClean .docMetaRow{ grid-template-columns: 1fr; }
    }
    #pubCreateClean .docMetaRow .miniField{
      background: rgba(255,255,255,.92);
      border:1px solid rgba(15,23,42,.10);
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 13px;
      font-weight: 700;
    }

    /* Headers de tabla */
    #pubCreateClean .tblHeader{
      display:grid;
      /* ✅ MÁS ancho para Concepto (resto más compacto) */
      grid-template-columns: 1fr 62px 86px 92px 62px 34px;
      gap:8px;
      background: rgba(248,250,252,.8);
      padding: 10px 12px;
      border-bottom: 1px solid rgba(15,23,42,.08);
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: var(--muted);
      font-weight: 600;
      flex: 0 0 auto;
    }

    /* ✅ area scroll (queda para la lista, no para el concepto) */
    #pubCreateClean .aiRowsArea{
      flex: 1 1 auto;
      min-height: 0;
      overflow:auto;
      max-height: 520px;
    }
    @media (max-width: 992px){
      #pubCreateClean .aiRowsArea{ max-height: 420px; }
    }

    /* Fila de resultados de IA */
    #pubCreateClean .aiEditGrid{
      display:grid;
      grid-template-columns: 1fr 62px 86px 92px 62px 34px;
      gap: 8px;
      align-items:start;
      padding: 6px 10px;
      border-bottom: 1px solid rgba(15,23,42,.04);
      transition: background .15s;
    }
    @media (max-width: 992px){
      #pubCreateClean .tblHeader, #pubCreateClean .aiEditGrid{
        grid-template-columns: 1fr 58px 78px 78px 1fr 34px;
      }
    }
    #pubCreateClean .aiEditGrid:hover{ background: rgba(248,250,252, 1); }
    #pubCreateClean .aiEditGrid:last-child{ border-bottom: none; }

    /* Inputs dentro de la tabla */
    #pubCreateClean .miniField{
      background: transparent; border: 1px solid transparent; border-radius: 8px;
      padding: 8px 10px; font-size: 13px; color: var(--ink); width:100%;
      transition: all .2s; outline:none; font-family: inherit;
      font-weight: 700;
    }
    #pubCreateClean .miniField:hover{ background: rgba(255,255,255, 0.85); border-color: rgba(15,23,42,.10); }
    #pubCreateClean .miniField:focus{ background: #fff; border-color: var(--blue-brd); box-shadow: 0 2px 6px rgba(59,130,246,.1); }
    #pubCreateClean .miniField.num { text-align: right; font-variant-numeric: tabular-nums; }

    /* ✅ Concepto: MÁS alto, SIN SCROLL feo */
    #pubCreateClean .miniArea{
      width:100%;
      border: 1px solid transparent;
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 13px;
      color: var(--ink);
      background: transparent;
      outline: none;
      font-family: inherit;
      font-weight: 750;
      line-height: 1.25;

      min-height: 52px;    /* ✅ más grande */
      max-height: 220px;   /* ✅ permite crecer */
      overflow: hidden;    /* ✅ QUITA el scrollbar */
      resize: none;
      white-space: pre-wrap;
      word-break: break-word;
      transition: all .2s;
    }
    #pubCreateClean .miniArea:hover{
      background: rgba(255,255,255, 0.85);
      border-color: rgba(15,23,42,.10);
    }
    #pubCreateClean .miniArea:focus{
      background: #fff;
      border-color: var(--blue-brd);
      box-shadow: 0 2px 6px rgba(59,130,246,.1);
    }

    /* ✅ Los campos numéricos se ven centrados aunque el concepto crezca */
    #pubCreateClean .aiEditGrid .miniField.num,
    #pubCreateClean .aiEditGrid .miniField[data-k="unit"]{
      margin-top: 6px;
    }

    #pubCreateClean .iconBtn{
      width: 28px; height: 28px; display:inline-flex; align-items:center; justify-content:center;
      border-radius: 8px; border: none; background: transparent; color: var(--muted); cursor:pointer;
      transition: all .2s;
      margin-top: 8px;
      font-weight: 900;
    }
    #pubCreateClean .iconBtn:hover{ background: var(--rose-bg); color: var(--rose-ink); }

    /* Footer de totales */
    #pubCreateClean .tblFooter{
      background: rgba(248,250,252,.5);
      padding: 12px 16px;
      border-top:1px solid rgba(15,23,42,.08);
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      gap:16px;
      flex: 0 0 auto;
    }

    #pubCreateClean .totalsBox{
      display:flex;
      flex-direction:column;
      align-items:flex-end;
      gap:6px;
      min-width: 240px;
    }
    #pubCreateClean .totRow{
      width:100%;
      display:flex;
      justify-content:space-between;
      gap:12px;
      font-size: 12px;
      color: rgba(15,23,42,.72);
      font-weight: 800;
    }
    #pubCreateClean .totRow strong{ color: var(--ink); font-variant-numeric: tabular-nums; }

    #pubCreateClean .totBig{
      display:flex;
      justify-content:space-between;
      width:100%;
      gap:12px;
      padding-top:8px;
      margin-top:4px;
      border-top:1px dashed rgba(15,23,42,.14);
    }
    #pubCreateClean .totBig small{
      display:block;
      color:var(--muted);
      font-size:10px;
      text-transform:uppercase;
      letter-spacing:.05em;
      font-weight: 800;
    }
    #pubCreateClean .totBig span{
      font-size: 16px;
      font-weight: 900;
      color: var(--ink);
      font-variant-numeric: tabular-nums;
    }

    #pubCreateClean .ivaToggle{
      display:flex;
      align-items:center;
      gap:10px;
      padding: 6px 10px;
      border:1px solid rgba(15,23,42,.10);
      border-radius: 10px;
      background: rgba(255,255,255,.65);
    }
    #pubCreateClean .ivaToggle label{
      font-size:12px;
      color: rgba(15,23,42,.72);
      user-select:none;
      cursor:pointer;
      font-weight:800;
    }
    #pubCreateClean .ivaToggle input{ width:16px; height:16px; }

    #pubCreateClean .hidden{ display:none !important; }

    /* Manual Table fallback */
    #pubCreateClean table{ width:100%; border-collapse: collapse; font-size: 13px; }
    #pubCreateClean th{ text-align:left; color:var(--muted); font-weight:800; font-size:11px; padding:10px; border-bottom:1px solid rgba(15,23,42,.08); }
    #pubCreateClean td{ padding:10px; border-bottom:1px solid rgba(15,23,42,.05); color:var(--ink); font-weight:700; }

    /* Manual Input Grid */
    #pubCreateClean .manualGrid{ display:grid; grid-template-columns: 1fr 80px 100px; gap:8px; margin-top:10px; }
    #pubCreateClean .manualGrid2{ display:grid; grid-template-columns: 1fr auto; gap:8px; margin-top:8px; }
    #pubCreateClean .manualGrid .miniField { border-color: rgba(15,23,42,.15); background: #fff; }
  </style>

  <div class="pageHead">
    <div>
      <h1 class="titleRow">
        @include('publications.partials.icons', ['name' => 'upload'])
        Subir publicación
      </h1>
      <div class="subtitle">
        Carga tu archivo (PDF/Imagen) y extraeremos los datos automáticamente. Verifica la tabla antes de guardar.
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
    <input type="hidden" name="ai_payload" id="ai_payload" value="">
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
            <input type="file" name="file" id="f-file" style="display:none;" required>

            <div class="drop" id="dropZone">
              <div class="fileRow">
                <div style="display:flex; gap:12px; align-items:center; min-width:0;">
                  <div style="background:var(--blue-bg); color:var(--blue-ink); padding:8px; border-radius:8px;">
                    @include('publications.partials.icons', ['name' => 'file'])
                  </div>
                  <div style="min-width:0;">
                    <div class="fileName" id="fileName">Seleccionar archivo...</div>
                    <div class="fileMini"><span id="fileType">PDF o Imagen</span> • <span id="fileSize">Max 10MB</span></div>
                  </div>
                </div>
                <label class="btnx ghost tiny" for="f-file">Examinar</label>
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
              <small id="aiStatus" style="color:var(--muted); font-size:12px;">Esperando archivo...</small>
              <div style="display:flex; gap:6px;">
                <button type="button" class="btnx ghost tiny hidden" id="btnClearAi">Limpiar</button>
                <button type="button" class="btnx blue tiny" id="btnRetry" disabled>Reintentar IA</button>
                <button type="button" class="btnx ghost tiny" id="btnSkipIA">Manual</button>
              </div>
            </div>

            <div class="hidden" id="aiResult">
              <div class="tableWrap">

                {{-- ✅ NUEVO: proveedor + fecha (se guardan en ai_payload.document) --}}
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

            <div class="hidden" id="manualBox" style="margin-top:16px; border-top:1px solid rgba(15,23,42,.1); padding-top:16px;">
              <h4 style="font-size:13px; color:var(--ink); margin:0 0 10px 0;">Captura Manual</h4>

              {{-- ✅ Manual también: proveedor y fecha --}}
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
      const fileInput = document.getElementById('f-file');
      const aiEditRows = document.getElementById('aiEditRows');
      const aiPayloadHidden = document.getElementById('ai_payload');

      const aiSubtotalEl = document.getElementById('aiSubtotal');
      const aiTaxEl = document.getElementById('aiTax');
      const aiTotalEl = document.getElementById('aiTotal');

      const taxIncluded = document.getElementById('taxIncluded');
      const taxModeHidden = document.getElementById('ai_tax_mode');
      const taxRateHidden = document.getElementById('ai_tax_rate');

      // ✅ NUEVO: inputs proveedor/fecha
      const docSupplier = document.getElementById('docSupplier');
      const docDatetime = document.getElementById('docDatetime');
      const mSupplier = document.getElementById('m_supplier');
      const mDatetime = document.getElementById('m_datetime');

      let aiRows = [];
      let manualRows = [];

      // ✅ Estado documento (para guardar)
      let aiDoc = {
        supplier_name: '',
        document_datetime: '' // en formato YYYY-MM-DD HH:MM:SS o ISO
      };

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

      // ✅ Convierte "YYYY-MM-DD HH:MM:SS" -> "YYYY-MM-DDTHH:MM" para datetime-local
      function toDatetimeLocal(val){
        if(!val) return '';
        const s = String(val).trim();
        if(s.includes('T')) return s.slice(0,16); // ya ISO
        // si viene con espacio
        const parts = s.split(' ');
        if(parts.length >= 2){
          return (parts[0] + 'T' + parts[1].slice(0,5));
        }
        return s;
      }
      // ✅ Convierte datetime-local -> "YYYY-MM-DD HH:MM:SS"
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
        // ✅ subimos el límite para que “alargue” el concepto sin scroll
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

      const toggleView = (mode) => {
        document.getElementById('aiResult').classList.toggle('hidden', mode !== 'ai');
        document.getElementById('manualBox').classList.toggle('hidden', mode !== 'manual');
        document.getElementById('btnClearAi').classList.toggle('hidden', mode !== 'ai');
        document.getElementById('ai_skip').value = (mode === 'manual' ? '1' : '0');
      };

      function setDocInputsFromAiDoc(){
        if(docSupplier) docSupplier.value = aiDoc.supplier_name || '';
        if(docDatetime) docDatetime.value = toDatetimeLocal(aiDoc.document_datetime || '');
      }

      function syncAiDocFromInputs(){
        aiDoc.supplier_name = cleanTxt(docSupplier?.value || '');
        aiDoc.document_datetime = fromDatetimeLocal(docDatetime?.value || '');
      }

      // ✅ listeners meta doc
      docSupplier?.addEventListener('input', () => { syncAiDocFromInputs(); updateTotals(); });
      docDatetime?.addEventListener('change', () => { syncAiDocFromInputs(); updateTotals(); });

      async function aiExtractAuto(){
        const f = fileInput.files[0];
        if(!f) return;

        toggleState('run');
        document.getElementById('aiStatus').innerText = 'Analizando documento con IA...';

        const fd = new FormData();
        fd.append('file', f);
        fd.append('category', document.querySelector('input[name="category"]:checked').value);

        try{
          const res = await fetch("{{ route('publications.ai.extract') }}", {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': "{{ csrf_token() }}", 'Accept':'application/json'},
            body: fd
          });
          const data = await res.json();
          if(!res.ok) throw new Error(data.error || 'Error en extracción');

          // ✅ Traer proveedor/fecha del documento
          aiDoc = {
            supplier_name: cleanTxt(data?.document?.supplier_name || ''),
            document_datetime: cleanTxt(data?.document?.document_datetime || '')
          };
          setDocInputsFromAiDoc();

          aiRows = (data.items || []).map(it => ({
            item_name: cleanTxt(it.item_name),
            qty: num(it.qty) || 1,
            unit_price: num(it.unit_price),
            line_total: num(it.line_total),
            unit: cleanTxt(it.unit) || 'pza'
          }));

          aiRows.forEach(recalcRowModel);

          renderAiEditor();
          toggleView('ai');
          toggleState('ok');
          document.getElementById('aiStatus').innerText = 'Revisa los datos extraídos. Puedes editar, agregar o borrar filas.';
        }catch(e){
          console.error(e);
          toggleState('fail');
          document.getElementById('aiStatus').innerText = e.message || 'No se pudo extraer información. Intenta manual.';
        }
      }

      function recalcRowModel(r){
        const q = num(r.qty) || 0;
        const p = num(r.unit_price) || 0;
        const lt = num(r.line_total) || 0;
        if(lt <= 0 && q > 0 && p > 0) r.line_total = q * p;
        if(p <= 0 && q > 0 && lt > 0) r.unit_price = lt / q;
      }

      function renderAiEditor(){
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
              updateAiModel(idx, e.target.dataset.k, e.target.value);
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
            renderAiEditor();
          });

          aiEditRows.appendChild(div);
        });

        updateTotals();
      }

      function updateAiModel(idx, key, val){
        if(!aiRows[idx]) return;

        if(key === 'item_name' || key === 'unit') aiRows[idx][key] = val;
        else aiRows[idx][key] = num(val);

        if(key === 'qty' || key === 'unit_price'){
          const q = num(aiRows[idx].qty) || 0;
          const p = num(aiRows[idx].unit_price) || 0;
          aiRows[idx].line_total = q * p;
          const totalInp = aiEditRows.children[idx]?.querySelector('[data-k="line_total"]');
          if(totalInp) totalInp.value = num(aiRows[idx].line_total).toFixed(2);
        }else if(key === 'line_total'){
          const q = num(aiRows[idx].qty) || 0;
          if(q > 0){
            aiRows[idx].unit_price = num(aiRows[idx].line_total) / q;
            const priceInp = aiEditRows.children[idx]?.querySelector('[data-k="unit_price"]');
            if(priceInp) priceInp.value = num(aiRows[idx].unit_price).toFixed(2);
          }
        }

        updateTotals();
      }

      document.getElementById('btnAiAddRow').addEventListener('click', () => {
        aiRows.push({item_name:'', qty:1, unit_price:0, line_total:0, unit:'pza'});
        renderAiEditor();
        aiEditRows.scrollTop = aiEditRows.scrollHeight;
      });

      taxIncluded.addEventListener('change', () => updateTotals());

      function updateTotals(){
        // ✅ traer lo último de inputs proveedor/fecha
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

        if(aiSubtotalEl) aiSubtotalEl.textContent = money(subtotal);
        if(aiTaxEl) aiTaxEl.textContent = money(iva);
        if(aiTotalEl) aiTotalEl.textContent = money(total);

        // ✅ Ahora SÍ mandamos proveedor/fecha al backend para que se guarde
        aiPayloadHidden.value = JSON.stringify({
          document: {
            category: document.querySelector('input[name="category"]:checked').value,
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

      fileInput.addEventListener('change', function(){
        const f = this.files[0];
        if(f){
          document.getElementById('fileName').innerText = f.name;
          document.getElementById('fileSize').innerText = (f.size/1024/1024).toFixed(2) + ' MB';
          document.getElementById('btnRetry').disabled = false;
          if(f.type.includes('image') || f.type.includes('pdf')) aiExtractAuto();
        }
      });

      document.getElementById('btnRetry').onclick = aiExtractAuto;

      document.getElementById('btnClearAi').onclick = () => {
        aiRows = [];
        aiEditRows.innerHTML = '';
        aiPayloadHidden.value = '';
        aiDoc = {supplier_name:'', document_datetime:''};
        setDocInputsFromAiDoc();
        toggleView('');
        toggleState('');
        document.getElementById('aiStatus').innerText = 'Listo. Sube o reintenta con otro archivo.';
        updateTotals();
      };

      document.getElementById('btnSkipIA').onclick = () => {
        toggleView('manual');
        toggleState('');
        document.getElementById('aiStatus').innerText = 'Captura manual habilitada.';
        syncManualPayload();
      };

      // Manual Logic
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

      // ✅ manual meta inputs actualizan payload también
      mSupplier?.addEventListener('input', syncManualPayload);
      mDatetime?.addEventListener('change', syncManualPayload);

      function syncManualPayload(){
        const supplier_name = cleanTxt(mSupplier?.value || '');
        const document_datetime = fromDatetimeLocal(mDatetime?.value || '');

        const tot = manualRows.reduce((a,r) => a + (num(r.qty)*num(r.unit_price)), 0);
        document.getElementById('mTotal').textContent = money(tot);

        aiPayloadHidden.value = JSON.stringify({
          document: {
            category: document.querySelector('input[name="category"]:checked').value,
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

      // init
      updateTotals();
    });
  </script>
</div>
@endsection
