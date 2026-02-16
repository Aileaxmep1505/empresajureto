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

      /* Pastel system */
      --blue-bg: rgba(59,130,246,.10);
      --blue-ink:#1d4ed8;
      --blue-brd: rgba(59,130,246,.22);

      --mint-bg: rgba(16,185,129,.10);
      --mint-ink:#047857;
      --mint-brd: rgba(16,185,129,.22);

      --rose-bg: rgba(244,63,94,.09);
      --rose-ink:#be123c;
      --rose-brd: rgba(244,63,94,.18);
    }

    /* Quita cualquier “marco” extra: sin shell/panel */
    #pubCreateClean .pageHead{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:16px;
      margin-bottom: 18px;
    }
    @media (max-width: 768px){
      #pubCreateClean .pageHead{ flex-direction:column; gap:10px; }
    }

    #pubCreateClean .titleRow{
      display:flex;
      align-items:center;
      gap:10px;
      color: var(--ink);
      font-size: 20px;
      line-height:1.2;
      font-weight: 400; /* sin negritas */
      margin:0;
    }
    #pubCreateClean .subtitle{
      margin-top: 6px;
      color: var(--muted);
      font-size: 13px;
      line-height:1.55;
      font-weight: 400;
      max-width: 820px;
    }

    #pubCreateClean .btnx{
      border:1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.92);
      color: rgba(15,23,42,.85);
      padding: 10px 12px;
      border-radius: 12px;
      font-weight: 400; /* sin negritas */
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:10px;
      transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease, background .14s ease, opacity .14s ease;
      box-shadow: 0 8px 18px rgba(2,6,23,.06);
      cursor:pointer;
      user-select:none;
      white-space:nowrap;
    }
    #pubCreateClean .btnx:hover{ transform: translateY(-1px); box-shadow: 0 12px 26px rgba(2,6,23,.08); }
    #pubCreateClean .btnx:disabled{ opacity:.55; cursor:not-allowed; transform:none; box-shadow: 0 8px 18px rgba(2,6,23,.06); }

    #pubCreateClean .btnx.blue{ background: var(--blue-bg); border-color: var(--blue-brd); color: var(--blue-ink); }
    #pubCreateClean .btnx.mint{ background: var(--mint-bg); border-color: var(--mint-brd); color: var(--mint-ink); }
    #pubCreateClean .btnx.rose{ background: var(--rose-bg); border-color: var(--rose-brd); color: var(--rose-ink); }
    #pubCreateClean .btnx.ghost{ background: rgba(255,255,255,.75); }

    #pubCreateClean .grid{
      display:grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
      align-items:start;
    }
    @media (max-width: 992px){
      #pubCreateClean .grid{ grid-template-columns:1fr; gap:14px; }
    }

    #pubCreateClean .card{
      background: var(--card);
      border:1px solid rgba(15,23,42,.10);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow:hidden;
    }

    #pubCreateClean .cardHead{
      padding: 14px 16px;
      border-bottom:1px solid rgba(15,23,42,.08);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      background: rgba(248,250,252,.70);
    }

    #pubCreateClean .cardTitle{
      display:flex;
      align-items:center;
      gap:10px;
      color: rgba(15,23,42,.72);
      letter-spacing:.12em;
      text-transform: uppercase;
      font-size: 11px;
      font-weight: 400; /* sin negritas */
      margin:0;
    }

    #pubCreateClean .cardBody{ padding: 18px; }
    @media (max-width: 768px){ #pubCreateClean .cardBody{ padding: 14px; } }

    /* Inputs: más aire (evitar pegado) */
    #pubCreateClean .stack{
      display:flex;
      flex-direction:column;
      gap: 14px; /* aquí separa Título y Descripción */
    }

    #pubCreateClean .field{
      position:relative;
      background: rgba(255,255,255,.96);
      border:1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      padding: 18px 14px 12px; /* +aire */
      transition: box-shadow .18s, border-color .18s;
    }
    #pubCreateClean .field:focus-within{
      border-color: rgba(59,130,246,.25);
      box-shadow: 0 12px 26px rgba(2,6,23,.08);
    }

    #pubCreateClean .field input,
    #pubCreateClean .field textarea{
      width:100%;
      border:0;
      outline:0;
      background:transparent;
      font-size: 14px;
      color: var(--ink);
      padding-top: 8px;
      font-weight: 400; /* sin negritas */
    }
    #pubCreateClean .field textarea{ min-height: 140px; resize: vertical; }

    #pubCreateClean .field label{
      position:absolute;
      left:14px;
      top:14px;
      color: rgba(15,23,42,.55);
      font-size: 13px;
      font-weight: 400; /* sin negritas */
      transition: transform .14s, color .14s, font-size .14s, top .14s;
      pointer-events:none;
    }
    #pubCreateClean .field input::placeholder,
    #pubCreateClean .field textarea::placeholder{ color:transparent; }

    #pubCreateClean .field input:focus + label,
    #pubCreateClean .field input:not(:placeholder-shown) + label,
    #pubCreateClean .field textarea:focus + label,
    #pubCreateClean .field textarea:not(:placeholder-shown) + label{
      top:8px;
      transform: translateY(-10px);
      font-size: 11px;
      color: rgba(29,78,216,.85);
    }

    #pubCreateClean .help{
      margin-top: 10px;
      color: rgba(15,23,42,.56);
      font-size: 12px;
      line-height:1.55;
      font-weight: 400; /* sin negritas */
    }

    #pubCreateClean .rowActions{
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:flex-end;
      align-items:center;
      margin-top: 16px;
      padding-top: 16px;
      border-top:1px solid rgba(15,23,42,.08);
    }

    /* Toggle (simple) */
    #pubCreateClean .switchWrap{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      border:1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      background: rgba(255,255,255,.92);
      padding: 12px 12px;
    }
    #pubCreateClean .switchText{
      display:flex;
      flex-direction:column;
      gap:4px;
      min-width:0;
      font-weight: 400;
      color: rgba(15,23,42,.75);
    }
    #pubCreateClean .switchText small{
      color: rgba(15,23,42,.55);
      font-size: 12px;
      line-height:1.4;
      font-weight: 400;
    }

    #pubCreateClean .switch{ display:inline-flex; align-items:center; gap:10px; user-select:none; }
    #pubCreateClean .switch input{ display:none; }
    #pubCreateClean .track{
      width:48px; height:26px; border-radius:999px;
      background: rgba(15,23,42,.10);
      position:relative;
      transition: background .2s;
    }
    #pubCreateClean .thumb{
      width:22px; height:22px; border-radius:50%;
      background:#fff;
      position:absolute; top:2px; left:2px;
      box-shadow:0 2px 8px rgba(0,0,0,.12);
      transition:left .18s ease;
    }
    #pubCreateClean .switch input:checked + .track{ background: rgba(16,185,129,.45); }
    #pubCreateClean .switch input:checked + .track .thumb{ left:24px; }

    /* Drop */
    #pubCreateClean .drop{
      border:1.5px dashed rgba(15,23,42,.16);
      border-radius: 16px;
      background:
        radial-gradient(650px 200px at 20% 0%, rgba(59,130,246,.06), transparent 60%),
        radial-gradient(650px 200px at 85% 0%, rgba(16,185,129,.06), transparent 60%),
        rgba(255,255,255,.92);
      padding: 16px;
      transition: transform .14s, box-shadow .14s, border-color .14s, background .14s;
    }
    #pubCreateClean .drop.drag{
      border-color: rgba(16,185,129,.30);
      box-shadow: 0 14px 34px rgba(2,6,23,.10);
      transform: translateY(-1px);
      background:
        radial-gradient(650px 200px at 20% 0%, rgba(16,185,129,.10), transparent 60%),
        radial-gradient(650px 200px at 85% 0%, rgba(59,130,246,.08), transparent 60%),
        rgba(255,255,255,.96);
    }

    #pubCreateClean .fileRow{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:14px;
    }
    @media (max-width: 576px){
      #pubCreateClean .fileRow{ flex-direction:column; align-items:stretch; }
    }
    #pubCreateClean .fileMeta{ display:flex; align-items:flex-start; gap:10px; min-width:0; }
    #pubCreateClean .fileName{
      color: rgba(15,23,42,.84);
      font-weight: 400;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      max-width: 520px;
    }
    @media (max-width: 576px){ #pubCreateClean .fileName{ max-width: 100%; } }
    #pubCreateClean .fileMini{
      margin-top: 4px;
      color: rgba(15,23,42,.55);
      font-size: 12px;
      font-weight: 400;
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      align-items:center;
    }
    #pubCreateClean .sep{ width:4px; height:4px; border-radius:999px; background: rgba(15,23,42,.22); display:inline-block; }
    #pubCreateClean input[type="file"]{ display:none; }

    /* IA state minimal */
    #pubCreateClean .stateBox{
      margin-top: 14px;
      border:1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      background: rgba(255,255,255,.92);
      padding: 12px 14px;
    }
    #pubCreateClean .stateTop{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap: 12px;
      flex-wrap:wrap;
    }
    #pubCreateClean .stateMsg{
      color: rgba(15,23,42,.70);
      font-size: 12.5px;
      line-height:1.5;
      font-weight: 400;
      margin-top: 4px;
    }

    #pubCreateClean .hintFail{
      margin-top: 10px;
      color: rgba(15,23,42,.60);
      font-size: 12px;
      line-height:1.55;
      font-weight: 400;
    }

    /* Tables: fixed + word break (sin scroll horizontal) */
    #pubCreateClean .tableWrap{
      margin-top: 12px;
      border:1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      overflow:hidden;
      background: rgba(255,255,255,.94);
    }
    #pubCreateClean table{
      width:100%;
      border-collapse: collapse;
      font-size: 13px;
      table-layout: fixed;
    }
    #pubCreateClean thead th{
      text-align:left;
      font-size: 11px;
      letter-spacing:.10em;
      text-transform: uppercase;
      color: rgba(15,23,42,.55);
      padding: 12px 12px;
      background: rgba(248,250,252,.75);
      border-bottom:1px solid rgba(15,23,42,.08);
      font-weight: 400;
      white-space:nowrap;
    }
    #pubCreateClean tbody td{
      padding: 10px 12px;
      border-bottom:1px solid rgba(15,23,42,.06);
      vertical-align: top;
      overflow-wrap: anywhere;
      word-break: break-word;
      font-weight: 400;
    }
    #pubCreateClean tfoot td{
      padding: 12px 12px;
      background: rgba(248,250,252,.75);
      border-top:1px solid rgba(15,23,42,.08);
      font-weight: 400;
    }

    /* Mobile: tabla a cards (sin scroll) */
    @media (max-width: 640px){
      #pubCreateClean thead{ display:none; }
      #pubCreateClean tbody, #pubCreateClean tr, #pubCreateClean td{ display:block; width:100%; }
      #pubCreateClean tbody tr{
        border-bottom:1px solid rgba(15,23,42,.08);
        padding: 10px 12px;
      }
      #pubCreateClean tbody td{
        border-bottom: 0;
        padding: 6px 0;
        display:flex;
        justify-content:space-between;
        gap: 12px;
      }
      #pubCreateClean tbody td::before{
        content: attr(data-label);
        color: rgba(15,23,42,.55);
        text-transform: uppercase;
        letter-spacing:.08em;
        font-size: 10px;
        font-weight: 400;
      }
      #pubCreateClean tfoot tr, #pubCreateClean tfoot td{ display:block; width:100%; }
      #pubCreateClean tfoot td{ text-align:right; }
    }

    /* Manual inputs */
    #pubCreateClean .manualBox{ margin-top: 12px; }
    #pubCreateClean .manualGrid{
      display:grid;
      grid-template-columns: 1fr 120px 160px;
      gap: 10px;
      align-items:center;
      margin-top: 10px;
    }
    #pubCreateClean .manualGrid2{
      display:grid;
      grid-template-columns: 1fr auto;
      gap: 10px;
      align-items:center;
      margin-top: 10px;
    }
    @media (max-width: 768px){
      #pubCreateClean .manualGrid,
      #pubCreateClean .manualGrid2{
        grid-template-columns: 1fr;
      }
      #pubCreateClean .manualGrid2 > div{ justify-content:flex-end; }
    }

    #pubCreateClean .miniField{
      background: rgba(255,255,255,.96);
      border:1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      padding: 10px 12px;
      font-weight: 400;
      color: rgba(15,23,42,.85);
      outline:0;
      width:100%;
    }
    #pubCreateClean .miniField:focus{
      border-color: rgba(59,130,246,.22);
      box-shadow: 0 12px 26px rgba(2,6,23,.08);
    }

    #pubCreateClean .hidden{ display:none !important; }
  </style>

  <div class="pageHead">
    <div>
      <h1 class="titleRow">
        @include('publications.partials.icons', ['name' => 'upload'])
        Subir publicación
      </h1>
      <div class="subtitle">
        Sube un archivo. Si es PDF o imagen, intentaremos extraer conceptos con IA. Si falla, puedes capturar manualmente.
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
    <div class="card" style="margin-bottom:14px;">
      <div class="cardBody" style="padding:14px 16px;">
        <div style="color: rgba(15,23,42,.78); font-size:13px; line-height:1.5;">
          Revisa los campos marcados.
        </div>
        <ul style="margin:10px 0 0; padding-left:18px; color: rgba(15,23,42,.70);">
          @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
      </div>
    </div>
  @endif

  <form id="pubCreateForm" action="{{ route('publications.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- Flags para backend --}}
    <input type="hidden" name="ai_extract" id="ai_extract" value="1">
    <input type="hidden" name="ai_skip" id="ai_skip" value="0">
    <input type="hidden" name="ai_payload" id="ai_payload" value="">

    <div class="grid">
      {{-- ================== DETALLES ================== --}}
      <div class="card">
        <div class="cardHead">
          <div class="cardTitle">
            @include('publications.partials.icons', ['name' => 'edit'])
            Detalles
          </div>
          <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end;">
            <span class="btnx blue hidden" id="pillAiRun" style="padding:8px 10px; box-shadow:none;">Analizando…</span>
            <span class="btnx mint hidden" id="pillAiOk" style="padding:8px 10px; box-shadow:none;">IA lista</span>
            <span class="btnx rose hidden" id="pillAiFail" style="padding:8px 10px; box-shadow:none;">IA falló</span>
          </div>
        </div>

        <div class="cardBody">
          <div class="stack">
            <div>
              <div class="field @error('title') invalid @enderror">
                <input type="text" name="title" id="f-title" value="{{ $v('title') }}" placeholder=" " required>
                <label for="f-title">Título</label>
              </div>
              @error('title')<div class="help" style="color:var(--rose-ink)">{{ $message }}</div>@enderror
            </div>

            <div>
              <div class="field @error('description') invalid @enderror">
                <textarea name="description" id="f-desc" placeholder=" ">{{ $v('description') }}</textarea>
                <label for="f-desc">Descripción</label>
              </div>
              @error('description')<div class="help" style="color:var(--rose-ink)">{{ $message }}</div>@enderror
            </div>

            <div class="switchWrap">
              <div class="switchText">
                <div>Fijar publicación</div>
                <small>Se muestra primero en el listado.</small>
              </div>

              <label class="switch">
                <input type="checkbox" name="pinned" value="1" id="pinChk" {{ $v('pinned') ? 'checked' : '' }}>
                <span class="track"><span class="thumb"></span></span>
              </label>
            </div>
          </div>

          <div class="rowActions">
            <button type="button" class="btnx ghost" id="btnSkipIA">
              @include('publications.partials.icons', ['name' => 'edit'])
              Captura manual
            </button>

            <a class="btnx" href="{{ route('publications.index') }}">
              @include('publications.partials.icons', ['name' => 'x'])
              Cancelar
            </a>

            <button class="btnx mint" type="submit" id="submitBtn">
              @include('publications.partials.icons', ['name' => 'check'])
              Subir
            </button>
          </div>

          <div class="help">
            Si no eliges captura manual, intentaremos extraer con IA antes de subir.
          </div>
        </div>
      </div>

      {{-- ================== ARCHIVO + IA ================== --}}
      <div class="card">
        <div class="cardHead">
          <div class="cardTitle">
            @include('publications.partials.icons', ['name' => 'paperclip'])
            Archivo + extracción
          </div>
          <span class="btnx blue" id="pillFileHint" style="padding:8px 10px; box-shadow:none;">Arrastra o elige</span>
        </div>

        <div class="cardBody">
          <div class="drop" id="dropZone">
            <div class="fileRow">
              <div class="fileMeta">
                <div style="margin-top:2px;">
                  @include('publications.partials.icons', ['name' => 'file'])
                </div>

                <div style="min-width:0;">
                  <div class="fileName" id="fileName">Ningún archivo seleccionado</div>
                  <div class="fileMini">
                    <span id="fileType">—</span>
                    <span class="sep"></span>
                    <span id="fileSize">—</span>
                  </div>
                </div>
              </div>

              <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                <label class="btnx blue" for="f-file" style="cursor:pointer;">
                  @include('publications.partials.icons', ['name' => 'folder'])
                  Elegir archivo
                </label>
              </div>
            </div>

            <div class="help" style="margin-top:12px;">
              Para mejor extracción: PDF legible y nítido.
            </div>
          </div>

          <input type="file" name="file" id="f-file" required>
          @error('file')
            <div class="help" style="color:var(--rose-ink); margin-top:10px;">{{ $message }}</div>
          @enderror

          {{-- Estado IA --}}
          <div class="stateBox" id="aiStateBox">
            <div class="stateTop">
              <div>
                <div style="color: rgba(15,23,42,.78); font-size: 13px;">Estado de extracción</div>
                <div class="stateMsg" id="aiStatus">Selecciona un PDF o imagen para iniciar.</div>
              </div>

              <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                <button type="button" class="btnx blue" id="btnRetry" disabled>
                  @include('publications.partials.icons', ['name' => 'upload'])
                  Reintentar
                </button>
                <button type="button" class="btnx rose hidden" id="btnManual">
                  @include('publications.partials.icons', ['name' => 'edit'])
                  Captura manual
                </button>
              </div>
            </div>

            <div class="hintFail hidden" id="aiFailHint">
              No se pudo extraer bien (PDF borroso o escaneado como imagen). Sube un PDF más nítido o usa captura manual.
            </div>
          </div>

          {{-- Resultado IA --}}
          <div class="hidden" id="aiResult" style="margin-top:12px;">
            <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap; align-items:center;">
              <div style="color: rgba(15,23,42,.70); font-size: 12px;" id="aiDocMeta">Documento —</div>
              <div style="color: rgba(15,23,42,.70); font-size: 12px;" id="aiTotalsMeta">Total —</div>
            </div>

            <div class="tableWrap">
              <table>
                <colgroup>
                  <col style="width:46%">
                  <col style="width:14%">
                  <col style="width:20%">
                  <col style="width:20%">
                </colgroup>
                <thead>
                  <tr>
                    <th>Concepto</th>
                    <th style="text-align:right;">Cantidad</th>
                    <th style="text-align:right;">Precio unitario</th>
                    <th style="text-align:right;">Importe</th>
                  </tr>
                </thead>
                <tbody id="aiItemsTbody"></tbody>
                <tfoot>
                  <tr>
                    <td colspan="3" style="text-align:right;">Total</td>
                    <td style="text-align:right;" id="aiTotalCell">—</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          {{-- Manual --}}
          <div class="manualBox hidden" id="manualBox">
            <div class="help" style="margin-top:12px;">Captura manual</div>

            <div class="manualGrid">
              <input class="miniField" id="m_name" placeholder="Concepto">
              <input class="miniField" id="m_qty" placeholder="Cantidad" inputmode="decimal">
              <input class="miniField" id="m_price" placeholder="Precio unitario" inputmode="decimal">
            </div>

            <div class="manualGrid2">
              <input class="miniField" id="m_unit" placeholder="Unidad (pza, caja, paquete…)">
              <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                <button type="button" class="btnx blue" id="btnAddRow">Agregar</button>
                <button type="button" class="btnx rose" id="btnClearManual">Limpiar</button>
              </div>
            </div>

            <div class="tableWrap">
              <table>
                <colgroup>
                  <col style="width:46%">
                  <col style="width:14%">
                  <col style="width:20%">
                  <col style="width:20%">
                </colgroup>
                <thead>
                  <tr>
                    <th>Concepto</th>
                    <th style="text-align:right;">Cantidad</th>
                    <th style="text-align:right;">Precio unitario</th>
                    <th style="text-align:right;">Importe</th>
                  </tr>
                </thead>
                <tbody id="mTbody"></tbody>
                <tfoot>
                  <tr>
                    <td colspan="3" style="text-align:right;">Total</td>
                    <td style="text-align:right;" id="mTotal">—</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>
  </form>

  <script>
    (function(){
      const fileInput = document.getElementById('f-file');
      const fileName  = document.getElementById('fileName');
      const fileType  = document.getElementById('fileType');
      const fileSize  = document.getElementById('fileSize');
      const dropZone  = document.getElementById('dropZone');

      const form = document.getElementById('pubCreateForm');
      const submitBtn = document.getElementById('submitBtn');

      // Backend flags
      const aiExtractFlag = document.getElementById('ai_extract');
      const aiSkipFlag = document.getElementById('ai_skip');
      const aiPayloadHidden = document.getElementById('ai_payload');

      // Pills
      const pillAiOk = document.getElementById('pillAiOk');
      const pillAiFail = document.getElementById('pillAiFail');
      const pillAiRun = document.getElementById('pillAiRun');

      // IA state
      const aiStatus = document.getElementById('aiStatus');
      const btnRetry = document.getElementById('btnRetry');
      const btnManual = document.getElementById('btnManual');
      const aiFailHint = document.getElementById('aiFailHint');

      const aiResult = document.getElementById('aiResult');
      const aiDocMeta = document.getElementById('aiDocMeta');
      const aiTotalsMeta = document.getElementById('aiTotalsMeta');
      const aiItemsTbody = document.getElementById('aiItemsTbody');
      const aiTotalCell  = document.getElementById('aiTotalCell');

      // Manual
      const btnSkipIA = document.getElementById('btnSkipIA');
      const manualBox = document.getElementById('manualBox');
      const m_name = document.getElementById('m_name');
      const m_qty  = document.getElementById('m_qty');
      const m_unit = document.getElementById('m_unit');
      const m_price= document.getElementById('m_price');
      const btnAddRow = document.getElementById('btnAddRow');
      const btnClearManual = document.getElementById('btnClearManual');
      const mTbody = document.getElementById('mTbody');
      const mTotal = document.getElementById('mTotal');

      const csrf = document.querySelector('input[name="_token"]')?.value;

      let lastAiOk = false;
      let lastAiTried = false;
      let manualRows = [];

      const fmtBytes = (n) => {
        if(!n && n !== 0) return '—';
        const units = ['B','KB','MB','GB'];
        let i = 0, v = n;
        while(v >= 1024 && i < units.length-1){ v/=1024; i++; }
        return (i === 0 ? v : v.toFixed(1)) + ' ' + units[i];
      };

      const money = (n) => {
        const v = Number(n || 0);
        return v.toLocaleString('es-MX', { style:'currency', currency:'MXN' });
      };

      function setPills({run=false, ok=false, fail=false}){
        pillAiRun?.classList.toggle('hidden', !run);
        pillAiOk?.classList.toggle('hidden', !ok);
        pillAiFail?.classList.toggle('hidden', !fail);
      }

      function isAiSupportedFile(f){
        if(!f) return false;
        const ext = (f.name.split('.').pop() || '').toLowerCase();
        return (f.type && f.type.startsWith('image/')) || f.type === 'application/pdf' || ext === 'pdf';
      }

      function setAiStatus(text, kind=''){
        aiStatus.textContent = text || '';
        aiStatus.style.color = (kind === 'err') ? 'rgba(190,18,60,.95)' : 'rgba(15,23,42,.70)';
      }

      function td(label, value, alignRight=false){
        const style = alignRight ? 'text-align:right;' : '';
        return `<td data-label="${label}" style="${style}">${value}</td>`;
      }

      function resetAiUI(){
        lastAiOk = false;
        lastAiTried = false;
        aiPayloadHidden.value = '';
        aiResult.classList.add('hidden');
        aiItemsTbody.innerHTML = '';
        aiTotalCell.textContent = '—';
        aiDocMeta.textContent = 'Documento —';
        aiTotalsMeta.textContent = 'Total —';
        aiFailHint.classList.add('hidden');
        btnManual.classList.add('hidden');
        btnRetry.disabled = true;
        setPills({run:false, ok:false, fail:false});
      }

      function refreshFileUI(f){
        if(!f){
          fileName.textContent = 'Ningún archivo seleccionado';
          fileType.textContent = '—';
          fileSize.textContent = '—';
          resetAiUI();
          setAiStatus('Selecciona un PDF o imagen para iniciar.');
          return;
        }

        fileName.textContent = f.name;
        fileType.textContent = f.type ? f.type : 'archivo';
        fileSize.textContent = fmtBytes(f.size);

        if(isAiSupportedFile(f)){
          btnRetry.disabled = false;
          setAiStatus('Archivo listo. Iniciando extracción…');
          if(aiSkipFlag.value !== '1') aiExtractAuto();
        }else{
          resetAiUI();
          setAiStatus('Este archivo no aplica para extracción. Puedes subirlo igualmente.', 'err');
          btnRetry.disabled = true;
        }
      }

      // Drag & drop
      ;['dragenter','dragover'].forEach(ev => {
        dropZone?.addEventListener(ev, (e) => {
          e.preventDefault(); e.stopPropagation();
          dropZone.classList.add('drag');
        });
      });
      ;['dragleave','drop'].forEach(ev => {
        dropZone?.addEventListener(ev, (e) => {
          e.preventDefault(); e.stopPropagation();
          dropZone.classList.remove('drag');
        });
      });
      dropZone?.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const f = dt && dt.files && dt.files[0] ? dt.files[0] : null;
        if(!f) return;
        const transfer = new DataTransfer();
        transfer.items.add(f);
        fileInput.files = transfer.files;
        refreshFileUI(f);
      });

      fileInput?.addEventListener('change', () => {
        refreshFileUI(fileInput.files && fileInput.files[0] ? fileInput.files[0] : null);
      });

      async function aiExtractAuto(){
        const f = fileInput?.files?.[0];
        if(!f || !isAiSupportedFile(f)) return;

        resetAiUI();
        lastAiTried = true;

        setPills({run:true, ok:false, fail:false});
        setAiStatus('Analizando con IA…');
        btnRetry.disabled = true;
        submitBtn.disabled = true;

        const fd = new FormData();
        fd.append('file', f);

        let res, data;
        try {
          res = await fetch("{{ route('publications.ai.extract', [], false) }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept':'application/json' },
            body: fd
          });
          data = await res.json().catch(()=>null);
        } catch (e){
          res = null;
        }

        submitBtn.disabled = false;
        btnRetry.disabled = false;

        if(!res || !res.ok){
          lastAiOk = false;
          setPills({run:false, ok:false, fail:true});
          setAiStatus((data && data.error) ? data.error : 'No se pudo extraer. Prueba con un PDF más nítido.', 'err');
          aiFailHint.classList.remove('hidden');
          btnManual.classList.remove('hidden');
          return;
        }

        lastAiOk = true;
        setPills({run:false, ok:true, fail:false});
        setAiStatus('Extracción lista.');

        aiPayloadHidden.value = JSON.stringify(data);

        const d = data.document || {};
        const supplier = d.supplier_name ? d.supplier_name : 'proveedor no detectado';
        const dtTxt = d.document_datetime ? d.document_datetime : 'fecha no detectada';
        const total = (d.total || data.stats?.sum_lines || 0);

        aiDocMeta.textContent = `Documento: ${supplier} · ${dtTxt}`;
        aiTotalsMeta.textContent = `Total: ${money(total)}`;

        aiItemsTbody.innerHTML = '';
        (data.items || []).forEach(it=>{
          const name = (it.item_name || it.item_raw || '').toString();
          const qty = Number(it.qty||0).toLocaleString('es-MX');
          const unit = money(it.unit_price||0);
          const line = money(it.line_total||0);

          const tr = document.createElement('tr');
          tr.innerHTML = `
            ${td('Concepto', name)}
            ${td('Cantidad', qty, true)}
            ${td('Precio unitario', unit, true)}
            ${td('Importe', line, true)}
          `;
          aiItemsTbody.appendChild(tr);
        });

        aiTotalCell.textContent = money(total);
        aiResult.classList.remove('hidden');
        aiFailHint.classList.add('hidden');
        btnManual.classList.add('hidden');
      }

      btnRetry?.addEventListener('click', () => {
        aiSkipFlag.value = '0';
        aiExtractFlag.value = '1';
        manualBox.classList.add('hidden');
        aiExtractAuto();
      });

      function openManualMode(){
        aiSkipFlag.value = '1';
        aiExtractFlag.value = '0';
        setPills({run:false, ok:false, fail:false});
        setAiStatus('Modo manual activo.');
        aiFailHint.classList.add('hidden');
        btnManual.classList.add('hidden');
        aiResult.classList.add('hidden');
        manualBox.classList.remove('hidden');
        renderManual();
      }

      btnManual?.addEventListener('click', openManualMode);
      btnSkipIA?.addEventListener('click', openManualMode);

      function renderManual(){
        mTbody.innerHTML = '';
        let sum = 0;

        manualRows.forEach((r) => {
          const line = (Number(r.qty||0) * Number(r.unit_price||0)) || 0;
          sum += line;

          const tr = document.createElement('tr');
          tr.innerHTML = `
            ${td('Concepto', (r.item_name||'').toString())}
            ${td('Cantidad', Number(r.qty||0).toLocaleString('es-MX'), true)}
            ${td('Precio unitario', money(r.unit_price||0), true)}
            ${td('Importe', money(line), true)}
          `;
          mTbody.appendChild(tr);
        });

        mTotal.textContent = money(sum);

        const payload = {
          document: { document_type:'manual', supplier_name:null, currency:'MXN', document_datetime:null, subtotal:0, tax:0, total:sum },
          items: manualRows.map(r => ({
            item_raw: r.item_name,
            item_name: r.item_name,
            qty: Number(r.qty||1),
            unit: r.unit || 'pza',
            unit_price: Number(r.unit_price||0),
            line_total: Number(r.qty||1) * Number(r.unit_price||0)
          })),
          notes: { warnings:['captura_manual'], confidence: 1.0 }
        };
        aiPayloadHidden.value = JSON.stringify(payload);
      }

      btnAddRow?.addEventListener('click', () => {
        const name = (m_name.value||'').trim();
        if(!name) return;

        const qty = Number((m_qty.value||'1').toString().replace(',','.')) || 1;
        const unit = (m_unit.value||'pza').trim() || 'pza';
        const price = Number((m_price.value||'0').toString().replace(',','.')) || 0;

        manualRows.push({ item_name:name, qty, unit, unit_price: price });
        m_name.value = ''; m_qty.value = ''; m_unit.value = ''; m_price.value = '';
        renderManual();
      });

      btnClearManual?.addEventListener('click', () => {
        manualRows = [];
        renderManual();
      });

      // Submit: si NO manual y falló IA => bloquea
      form?.addEventListener('submit', (e) => {
        const skip = (aiSkipFlag.value === '1');
        if(skip) return;

        if(!isAiSupportedFile(fileInput?.files?.[0] || null)) return;

        if(lastAiTried && !lastAiOk){
          e.preventDefault();
          setPills({run:false, ok:false, fail:true});
          setAiStatus('La IA no pudo extraer. Sube un PDF más nítido o usa captura manual.', 'err');
          aiFailHint.classList.remove('hidden');
          btnManual.classList.remove('hidden');
          return;
        }

        if(!lastAiTried){
          e.preventDefault();
          aiExtractAuto();
          return;
        }
      });

      // init
      refreshFileUI(fileInput?.files && fileInput.files[0] ? fileInput.files[0] : null);
    })();
  </script>
</div>
@endsection