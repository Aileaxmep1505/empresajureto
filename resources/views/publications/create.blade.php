@extends('layouts.app')

@section('title', 'Subir publicación')

@section('content')
@php
  $v = fn($k,$d=null) => old($k,$d);
@endphp

<div class="container py-4" id="pubCreatePro">
  <style>
    #pubCreatePro{
      --ink:#0b1220;
      --muted:#6b7280;
      --line:rgba(15,23,42,.10);
      --card:rgba(255,255,255,.86);
      --shadow: 0 18px 60px rgba(2,6,23,.08);
      --shadow2: 0 10px 30px rgba(2,6,23,.08);
      --radius:18px;

      /* Pastel system (igual que index) */
      --pastel-blue-bg: rgba(59,130,246,.12);
      --pastel-blue-ink:#1d4ed8;
      --pastel-blue-brd: rgba(59,130,246,.22);

      --pastel-mint-bg: rgba(16,185,129,.12);
      --pastel-mint-ink:#047857;
      --pastel-mint-brd: rgba(16,185,129,.22);

      --pastel-rose-bg: rgba(244,63,94,.12);
      --pastel-rose-ink:#be123c;
      --pastel-rose-brd: rgba(244,63,94,.22);

      --soft:rgba(2,6,23,.04);
    }

    #pubCreatePro .shell{
      max-width: 980px;
      margin: 0 auto;
      background:
        radial-gradient(1200px 500px at 10% 0%, rgba(59,130,246,.10), transparent 55%),
        radial-gradient(900px 420px at 90% 0%, rgba(16,185,129,.08), transparent 55%),
        linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,.25));
      border:1px solid rgba(15,23,42,.08);
      border-radius: calc(var(--radius) + 6px);
      padding: 18px;
      box-shadow: 0 18px 70px rgba(2,6,23,.06);
    }

    #pubCreatePro .panel{
      background: var(--card);
      border:1px solid rgba(15,23,42,.10);
      border-radius: var(--radius);
      box-shadow: var(--shadow2);
      overflow:hidden;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }

    #pubCreatePro .head{
      padding: 18px 18px;
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:14px;
      border-bottom:1px solid rgba(15,23,42,.10);
      background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.65));
    }

    #pubCreatePro .hgroup{ min-width:0; }
    #pubCreatePro h1{
      margin:0;
      font-weight:950;
      letter-spacing:-.03em;
      color:var(--ink);
      font-size: 22px;
      display:flex;
      align-items:center;
      gap:10px;
    }
    #pubCreatePro .sub{
      margin:6px 0 0;
      color: rgba(15,23,42,.60);
      font-weight: 800;
      line-height:1.5;
      font-size: 13px;
      max-width: 720px;
    }

    #pubCreatePro .topActions{
      display:flex;
      gap:10px;
      align-items:center;
      justify-content:flex-end;
      flex-wrap:wrap;
    }

    /* Pastel buttons */
    #pubCreatePro .btnx{
      border:1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.80);
      color: rgba(15,23,42,.88);
      padding: 10px 12px;
      border-radius: 14px;
      font-weight: 950;
      text-decoration:none;
      display:inline-flex; align-items:center; gap:10px;
      transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
      box-shadow: 0 12px 26px rgba(2,6,23,.08);
    }
    #pubCreatePro .btnx:hover{ transform: translateY(-1px); box-shadow: 0 16px 34px rgba(2,6,23,.12); }

    #pubCreatePro .btnx.blue{
      background: var(--pastel-blue-bg);
      border-color: var(--pastel-blue-brd);
      color: var(--pastel-blue-ink);
    }
    #pubCreatePro .btnx.blue:hover{ background: rgba(59,130,246,.15); border-color: rgba(59,130,246,.30); }

    #pubCreatePro .btnx.mint{
      background: var(--pastel-mint-bg);
      border-color: var(--pastel-mint-brd);
      color: var(--pastel-mint-ink);
    }
    #pubCreatePro .btnx.mint:hover{ background: rgba(16,185,129,.15); border-color: rgba(16,185,129,.30); }

    /* Body */
    #pubCreatePro .body{
      padding: 16px 18px 18px;
      display:grid;
      grid-template-columns: 1.05fr .95fr;
      gap: 14px;
      background: rgba(248,250,252,.55);
    }
    @media (max-width: 992px){
      #pubCreatePro .body{ grid-template-columns: 1fr; }
    }

    /* Card sections inside */
    #pubCreatePro .box{
      background: rgba(255,255,255,.78);
      border:1px solid rgba(15,23,42,.10);
      border-radius: 18px;
      box-shadow: 0 16px 45px rgba(2,6,23,.07);
      overflow:hidden;
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
    }
    #pubCreatePro .box .boxHead{
      padding: 12px 14px;
      border-bottom:1px solid rgba(15,23,42,.10);
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.65));
    }
    #pubCreatePro .box .boxHead .t{
      font-weight: 950;
      letter-spacing: .10em;
      text-transform: uppercase;
      font-size: 11px;
      color: rgba(15,23,42,.70);
      display:flex; align-items:center; gap:10px;
    }
    #pubCreatePro .box .boxBody{ padding: 14px; }

    /* Floating field style (similar to client form) */
    #pubCreatePro .field{
      position:relative;
      background: rgba(255,255,255,.88);
      border:1px solid rgba(15,23,42,.10);
      border-radius: 14px;
      padding: 16px 14px 10px;
      transition: box-shadow .2s, border-color .2s, transform .12s;
    }
    #pubCreatePro .field:focus-within{
      border-color: rgba(59,130,246,.28);
      box-shadow: 0 16px 34px rgba(2,6,23,.10);
      transform: translateY(-1px);
    }
    #pubCreatePro .field input,
    #pubCreatePro .field textarea{
      width:100%;
      border:0;
      outline:0;
      background:transparent;
      font-size: 14px;
      color: var(--ink);
      padding-top: 8px;
      font-weight: 800;
    }
    #pubCreatePro .field textarea{ min-height: 140px; resize: vertical; }
    #pubCreatePro .field label{
      position:absolute;
      left:14px;
      top:12px;
      color: rgba(15,23,42,.55);
      font-size: 13px;
      font-weight: 900;
      transition: transform .15s, color .15s, font-size .15s, top .15s;
      pointer-events:none;
    }
    #pubCreatePro .field input::placeholder,
    #pubCreatePro .field textarea::placeholder{ color:transparent; }

    #pubCreatePro .field input:focus + label,
    #pubCreatePro .field input:not(:placeholder-shown) + label,
    #pubCreatePro .field textarea:focus + label,
    #pubCreatePro .field textarea:not(:placeholder-shown) + label{
      top:6px;
      transform: translateY(-9px);
      font-size: 11px;
      color: var(--pastel-blue-ink);
    }

    #pubCreatePro .help{
      margin-top: 8px;
      color: rgba(15,23,42,.58);
      font-size: 12px;
      font-weight: 800;
      line-height:1.5;
    }

    /* File drop */
    #pubCreatePro .drop{
      border: 1.5px dashed rgba(15,23,42,.18);
      border-radius: 18px;
      background:
        radial-gradient(650px 220px at 20% 0%, rgba(59,130,246,.10), transparent 60%),
        radial-gradient(650px 220px at 85% 0%, rgba(16,185,129,.10), transparent 60%),
        rgba(255,255,255,.70);
      padding: 14px;
      transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
      position:relative;
      overflow:hidden;
    }
    #pubCreatePro .drop.drag{
      border-color: rgba(16,185,129,.35);
      box-shadow: 0 18px 55px rgba(2,6,23,.12);
      transform: translateY(-2px);
      background:
        radial-gradient(650px 220px at 20% 0%, rgba(16,185,129,.14), transparent 60%),
        radial-gradient(650px 220px at 85% 0%, rgba(59,130,246,.12), transparent 60%),
        rgba(255,255,255,.78);
    }

    #pubCreatePro .fileRow{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap: 12px;
    }
    #pubCreatePro .fileMeta{
      display:flex;
      align-items:flex-start;
      gap: 10px;
      min-width:0;
    }
    #pubCreatePro .fileMeta .name{
      font-weight: 950;
      color: rgba(15,23,42,.85);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      max-width: 420px;
    }
    #pubCreatePro .fileMeta .mini{
      margin-top: 4px;
      color: rgba(15,23,42,.55);
      font-size: 12px;
      font-weight: 850;
      display:flex;
      gap: 10px;
      flex-wrap:wrap;
      align-items:center;
    }
    #pubCreatePro .sep{ width:4px; height:4px; border-radius:999px; background: rgba(15,23,42,.25); display:inline-block; }

    #pubCreatePro input[type="file"]{ display:none; }

    /* Switch pinned */
    #pubCreatePro .switchWrap{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      border:1px solid rgba(15,23,42,.10);
      border-radius: 16px;
      background: rgba(255,255,255,.78);
      padding: 12px 12px;
    }
    #pubCreatePro .switchLegend{
      display:flex; align-items:center; gap:10px;
      color: rgba(15,23,42,.70);
      font-weight: 900;
    }
    #pubCreatePro .dot{
      width:8px; height:8px; border-radius:999px;
      background: rgba(15,23,42,.18);
    }
    #pubCreatePro .dot.on{ background: rgba(16,185,129,.95); }
    #pubCreatePro .switch{
      display:inline-flex; align-items:center; gap:10px; user-select:none;
    }
    #pubCreatePro .switch input{ display:none; }
    #pubCreatePro .track{
      width:48px; height:26px; border-radius:999px;
      background: rgba(15,23,42,.10);
      position:relative;
      transition: background .2s;
    }
    #pubCreatePro .thumb{
      width:22px; height:22px; border-radius:50%;
      background:#fff;
      position:absolute; top:2px; left:2px;
      box-shadow:0 2px 8px rgba(0,0,0,.15);
      transition:left .18s ease;
    }
    #pubCreatePro .switch input:checked + .track{
      background: rgba(16,185,129,.55);
    }
    #pubCreatePro .switch input:checked + .track .thumb{ left:24px; }

    /* Error box */
    #pubCreatePro .alertx{
      border-radius: 18px;
      border: 1px solid rgba(244,63,94,.18);
      background: rgba(244,63,94,.06);
      color: rgba(15,23,42,.85);
      padding: 12px 14px;
      font-weight: 800;
    }
    #pubCreatePro .alertx ul{ margin:8px 0 0; padding-left: 18px; }
    #pubCreatePro .invalid{ border-color: rgba(244,63,94,.35) !important; }

    /* Micro motion */
    @keyframes fadeUp{ from{opacity:0; transform: translateY(8px)} to{opacity:1; transform:none} }
    #pubCreatePro .panel{ animation: fadeUp .26s ease both; }
  </style>

  <div class="shell">
    <div class="panel">
      <div class="head">
        <div class="hgroup">
          <h1>
            @include('publications.partials.icons', ['name' => 'upload'])
            Subir publicación
          </h1>
          <div class="sub">PDF, Excel, Word, imágenes o videos. Solo validamos por tamaño (ej. 50 MB).</div>
        </div>

        <div class="topActions">
          <a class="btnx" href="{{ route('publications.index') }}">
            @include('publications.partials.icons', ['name' => 'arrowLeft'])
            Volver
          </a>
        </div>
      </div>

      <div class="body">
        <div class="box">
          <div class="boxHead">
            <div class="t">
              @include('publications.partials.icons', ['name' => 'edit'])
              Detalles
            </div>
          </div>
          <div class="boxBody">
            @if($errors->any())
              <div class="alertx mb-3">
                Hay campos por revisar:
                <ul class="mb-0">
                  @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
              </div>
            @endif

            <form id="pubCreateForm" action="{{ route('publications.store') }}" method="POST" enctype="multipart/form-data">
              @csrf

              <div class="mb-3">
                <div class="field @error('title') invalid @enderror">
                  <input type="text" name="title" id="f-title" value="{{ $v('title') }}" placeholder=" " required>
                  <label for="f-title">Título</label>
                </div>
                @error('title')<div class="help" style="color:var(--pastel-rose-ink)">{{ $message }}</div>@enderror
              </div>

              <div class="mb-3">
                <div class="field @error('description') invalid @enderror">
                  <textarea name="description" id="f-desc" placeholder=" ">{{ $v('description') }}</textarea>
                  <label for="f-desc">Descripción</label>
                </div>
                <div class="help">Describe de forma breve el contenido y el contexto de la publicación.</div>
                @error('description')<div class="help" style="color:var(--pastel-rose-ink)">{{ $message }}</div>@enderror
              </div>

              {{-- pinned --}}
              <div class="switchWrap mb-3">
                <div class="switchLegend">
                  <span class="dot" id="pinDot"></span>
                  <span>Fijar publicación</span>
                  <span style="color:rgba(15,23,42,.55); font-weight:900;" id="pinTxt">No</span>
                </div>
                <label class="switch">
                  <input type="checkbox" name="pinned" value="1" id="pinChk" {{ $v('pinned') ? 'checked' : '' }}>
                  <span class="track"><span class="thumb"></span></span>
                </label>
              </div>

              {{-- acciones (abajo) --}}
              <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                <a class="btnx" href="{{ route('publications.index') }}">
                  @include('publications.partials.icons', ['name' => 'x'])
                  Cancelar
                </a>
                <button class="btnx mint" type="submit" id="submitBtn">
                  @include('publications.partials.icons', ['name' => 'check'])
                  Subir
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="box">
          <div class="boxHead">
            <div class="t">
              @include('publications.partials.icons', ['name' => 'paperclip'])
              Archivo
            </div>
          </div>

          <div class="boxBody">
            <div class="drop" id="dropZone">
              <div class="fileRow">
                <div class="fileMeta">
                  <div style="margin-top:2px;">
                    @include('publications.partials.icons', ['name' => 'file'])
                  </div>
                  <div style="min-width:0;">
                    <div class="name" id="fileName">Ningún archivo seleccionado</div>
                    <div class="mini">
                      <span id="fileType">—</span>
                      <span class="sep"></span>
                      <span id="fileSize">—</span>
                    </div>
                  </div>
                </div>

                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                  <label class="btnx blue" for="f-file" style="cursor:pointer;">
                    @include('publications.partials.icons', ['name' => 'folder'])
                    Elegir
                  </label>
                </div>
              </div>

              <div class="help" style="margin-top:10px;">
                Puedes arrastrar y soltar el archivo aquí. Se aceptan todos los formatos (validación por tamaño).
              </div>
            </div>

            <input type="file" name="file" id="f-file" form="pubCreateForm" required>

            @error('file')<div class="help" style="color:var(--pastel-rose-ink); margin-top:10px;">{{ $message }}</div>@enderror
          </div>
        </div>

      </div>
    </div>
  </div>

  <script>
    (function(){
      const fileInput = document.getElementById('f-file');
      const fileName  = document.getElementById('fileName');
      const fileType  = document.getElementById('fileType');
      const fileSize  = document.getElementById('fileSize');
      const dropZone  = document.getElementById('dropZone');

      const pinChk = document.getElementById('pinChk');
      const pinDot = document.getElementById('pinDot');
      const pinTxt = document.getElementById('pinTxt');

      const fmtBytes = (n) => {
        if(!n && n !== 0) return '—';
        const units = ['B','KB','MB','GB'];
        let i = 0;
        let v = n;
        while(v >= 1024 && i < units.length-1){ v/=1024; i++; }
        return (i === 0 ? v : v.toFixed(1)) + ' ' + units[i];
      };

      function refreshFileUI(f){
        if(!f){
          fileName.textContent = 'Ningún archivo seleccionado';
          fileType.textContent = '—';
          fileSize.textContent = '—';
          return;
        }
        fileName.textContent = f.name;
        fileType.textContent = f.type ? f.type : 'archivo';
        fileSize.textContent = fmtBytes(f.size);
      }

      fileInput?.addEventListener('change', () => {
        refreshFileUI(fileInput.files && fileInput.files[0] ? fileInput.files[0] : null);
      });

      // Drag & drop
      ;['dragenter','dragover'].forEach(ev => {
        dropZone?.addEventListener(ev, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropZone.classList.add('drag');
        });
      });
      ;['dragleave','drop'].forEach(ev => {
        dropZone?.addEventListener(ev, (e) => {
          e.preventDefault();
          e.stopPropagation();
          dropZone.classList.remove('drag');
        });
      });

      dropZone?.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const f = dt && dt.files && dt.files[0] ? dt.files[0] : null;
        if(!f) return;

        // set files to input
        const transfer = new DataTransfer();
        transfer.items.add(f);
        fileInput.files = transfer.files;

        refreshFileUI(f);
      });

      // pinned UI
      function syncPinned(){
        const on = !!pinChk?.checked;
        if(pinDot) pinDot.classList.toggle('on', on);
        if(pinTxt) pinTxt.textContent = on ? 'Sí' : 'No';
      }
      pinChk?.addEventListener('change', syncPinned);
      syncPinned();

      // init
      refreshFileUI(fileInput?.files && fileInput.files[0] ? fileInput.files[0] : null);
    })();
  </script>
</div>
@endsection
