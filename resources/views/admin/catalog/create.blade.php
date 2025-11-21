@extends('layouts.app')
@section('title','Nuevo producto web')

@section('content')
@php
  /** @var \App\Models\CatalogItem|null $item */
  $item = null;
@endphp

<div class="wrap-ai">
  {{-- Header --}}
  <div class="head-ai">
    <div class="head-ai__text">
      <h1 class="h1-ai">Nuevo producto <span class="h1-ai__muted">(Catálogo web)</span></h1>
      <p class="p-ai">
        Completa la información del producto. Puedes hacerlo manual o capturarlo con IA desde factura/remisión.
      </p>
    </div>
    <a class="btn-ai btn-ai--ghost" href="{{ route('admin.catalog.index') }}">
      <span class="ico-ai" aria-hidden="true">
        {{-- arrow-left --}}
        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/><path d="M9 12h12"/></svg>
      </span>
      Volver
    </a>
  </div>

  {{-- Tabs --}}
  <div class="tabs-ai card-ai">
    <button type="button" id="tabManual" class="tab-ai tab-ai--active">
      <span class="ico-ai" aria-hidden="true">
        {{-- edit --}}
        <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5l4 4L7 21l-4 1 1-4 12.5-14.5z"/></svg>
      </span>
      Manual
    </button>
    <button type="button" id="tabAi" class="tab-ai">
      <span class="ico-ai" aria-hidden="true">
        {{-- sparkles --}}
        <svg viewBox="0 0 24 24">
          <path d="M5 3l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5z" transform="translate(7 2)"/>
          <path d="M4 17l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/>
        </svg>
      </span>
      Capturar con IA
    </button>

    <div class="tabs-ai__mode">
      <span class="pill-ai" id="modeLabel">
        <span class="dot-ai dot-ai--mint"></span>
        Modo: Manual
      </span>
    </div>
  </div>

  {{-- Panel IA --}}
  <section id="panelAi" class="panel-ai card-ai" style="display:none">
    <div class="panel-ai__grid">

      {{-- Columna izquierda --}}
      <div class="panel-ai__left">
        <div class="step-ai">
          <div class="step-ai__title">
            <span class="step-ai__num">1</span>
            <div>
              <h3 class="h3-ai">Genera el QR</h3>
              <p class="hint-ai">Escanéalo con el celular, toma fotos y súbelas. Al terminar, se llenará el formulario.</p>
            </div>
          </div>

          <div class="row-ai">
            <div class="select-wrap-ai">
              <label class="lbl-ai">Tipo de documento</label>
              <select id="aiSourceType" class="inp-ai">
                <option value="factura">Factura</option>
                <option value="remision">Remisión</option>
                <option value="otro">Otro</option>
              </select>
            </div>

            <button type="button" id="btnAiStart" class="btn-ai btn-ai--primary">
              <span class="ico-ai" aria-hidden="true">
                {{-- qr --}}
                <svg viewBox="0 0 24 24">
                  <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                  <path d="M14 14h3v3h-3z"/><path d="M20 14h1v1h-1z"/><path d="M14 20h7"/>
                </svg>
              </span>
              Generar QR
            </button>
          </div>

          {{-- QR Box --}}
          <div id="qrWrap" class="qr-wrap-ai" style="display:none;">
            <div class="qr-card-ai">
              <div class="qr-card-ai__header">
                <div class="qr-chip-ai">
                  <span class="ico-ai" aria-hidden="true">
                    {{-- phone --}}
                    <svg viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                  </span>
                  Escanea con tu celular
                </div>
                <div class="qr-status-ai" id="qrMiniStatus">
                  <span class="dot-ai dot-ai--slate"></span>
                  Pendiente
                </div>
              </div>

              <div class="qr-box-ai">
                <div class="qr-box-ai__frame">
                  <div id="qrBox"></div>
                  <div class="qr-box-ai__glow"></div>
                </div>
              </div>

              <div class="qr-card-ai__footer">
                <div class="qr-url-ai">
                  <div class="qr-url-ai__label">URL móvil</div>
                  <a id="mobileUrl" href="#" target="_blank" class="qr-url-ai__link"></a>
                </div>

                <div class="timeline-ai">
                  <div class="timeline-ai__item" data-st="0">
                    <span class="timeline-ai__dot"></span>
                    QR listo
                  </div>
                  <div class="timeline-ai__item" data-st="1">
                    <span class="timeline-ai__dot"></span>
                    Fotos subidas
                  </div>
                  <div class="timeline-ai__item" data-st="2">
                    <span class="timeline-ai__dot"></span>
                    Procesando
                  </div>
                  <div class="timeline-ai__item" data-st="3">
                    <span class="timeline-ai__dot"></span>
                    Listo
                  </div>
                </div>
              </div>
            </div>

            {{-- Status grande --}}
            <div class="status-ai">
              <div class="status-ai__badge" id="aiStatusBadge">
                <span class="ico-ai" aria-hidden="true">
                  {{-- clock --}}
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>
                </span>
                <span id="aiStatusText">Pendiente</span>
              </div>
              <div class="status-ai__hint" id="aiStatusHint">
                Esperando fotos del celular…
              </div>
            </div>
          </div>
        </div>

        {{-- Tips --}}
        <div class="tips-ai">
          <div class="tips-ai__title">
            <span class="ico-ai" aria-hidden="true">
              {{-- info --}}
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01"/><path d="M11 12h1v4h1"/></svg>
            </span>
            Recomendaciones rápidas
          </div>
          <ul class="tips-ai__list">
            <li>Fotos claras y sin sombras.</li>
            <li>Incluye encabezado y tabla de productos.</li>
            <li>Si son varias hojas, toma una foto por página.</li>
          </ul>
        </div>
      </div>

      {{-- Columna derecha --}}
      <div class="panel-ai__right">
        <div class="step-ai">
          <div class="step-ai__title">
            <span class="step-ai__num">2</span>
            <div>
              <h3 class="h3-ai">Resultado IA</h3>
              <p class="hint-ai">Se mostrará el extracto detectado. Tú decides qué ítem usar.</p>
            </div>
          </div>

          {{-- Waiting --}}
          <div id="aiWaiting" class="waiting-ai">
            <div class="waiting-ai__skeleton"></div>
            <div class="waiting-ai__skeleton"></div>
            <div class="waiting-ai__skeleton short"></div>
            <div class="waiting-ai__msg muted-ai">Aquí aparecerán los datos extraídos.</div>
          </div>

          {{-- Result --}}
          <div id="aiResult" style="display:none;">
            <div class="summary-ai">
              <div class="summary-ai__item">
                <div class="summary-ai__label">Proveedor</div>
                <div class="summary-ai__value" id="exSupplier">—</div>
              </div>
              <div class="summary-ai__item">
                <div class="summary-ai__label">Folio</div>
                <div class="summary-ai__value" id="exFolio">—</div>
              </div>
              <div class="summary-ai__item">
                <div class="summary-ai__label">Fecha</div>
                <div class="summary-ai__value" id="exDate">—</div>
              </div>
              <div class="summary-ai__item">
                <div class="summary-ai__label">Total</div>
                <div class="summary-ai__value" id="exTotal">—</div>
              </div>
            </div>

            <div class="table-ai">
              <table>
                <thead>
                  <tr>
                    <th>SKU</th>
                    <th>Descripción</th>
                    <th>Cant.</th>
                    <th>U.M.</th>
                    <th>P. unit</th>
                    <th>Total</th>
                    <th class="right">Usar</th>
                  </tr>
                </thead>
                <tbody id="aiItemsTbody"></tbody>
              </table>
            </div>

            <div class="actions-ai">
              <button type="button" id="btnFillFirst" class="btn-ai btn-ai--primary btn-ai--sm">
                <span class="ico-ai" aria-hidden="true">
                  {{-- auto / arrow --}}
                  <svg viewBox="0 0 24 24"><path d="M3 12h6"/><path d="M15 12h6"/><path d="M9 6l6 6-6 6"/></svg>
                </span>
                Rellenar primer ítem
              </button>
              <button type="button" id="btnBackManual" class="btn-ai btn-ai--ghost btn-ai--sm">
                <span class="ico-ai" aria-hidden="true">
                  {{-- edit --}}
                  <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5l4 4L7 21l-4 1 1-4 12.5-14.5z"/></svg>
                </span>
                Volver a Manual
              </button>
            </div>

            <div class="hint-ai" style="margin-top:8px">
              Puedes editar todo antes de guardar.
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  {{-- Errors --}}
  @if($errors->any())
    <div class="alert-ai">
      <div class="alert-ai__title">Revisa estos puntos antes de guardar:</div>
      <ul class="alert-ai__list">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  {{-- Manual panel --}}
  <div id="panelManual">
    <form class="card-ai form-ai"
          action="{{ route('admin.catalog.store') }}" method="POST">
      @include('admin.catalog._form', ['item' => null])
    </form>
  </div>
</div>
@endsection


@push('styles')
<style>
  :root{
    --bg:#f6f8fc;
    --surface:#ffffff;
    --surface-2:#f9fbff;
    --ink:#0f172a;
    --muted:#6b7280;
    --line:#e7edf6;

    --mint:#a7f3d0;
    --mint-ink:#0f766e;

    --lilac:#e9d5ff;
    --lilac-ink:#6d28d9;

    --sky:#bfdbfe;
    --sky-ink:#1d4ed8;

    --rose:#fecdd3;
    --rose-ink:#be123c;

    --radius:16px;
    --shadow:0 14px 34px rgba(12,18,30,0.06);
  }

  .wrap-ai{max-width:1100px;margin:18px auto;padding:0 14px;}
  .card-ai{
    background:var(--surface);
    border:1px solid var(--line);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
  }

  .head-ai{
    display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin:8px 0 12px;
  }
  .h1-ai{margin:0;font-weight:900;letter-spacing:-.02em;color:var(--ink);font-size:1.55rem;}
  .h1-ai__muted{color:var(--muted);font-weight:700;font-size:1.1rem;}
  .p-ai{margin:4px 0 0;font-size:.96rem;color:var(--muted);}

  .btn-ai{
    display:inline-flex;align-items:center;gap:10px;
    border:0;cursor:pointer;text-decoration:none;font-weight:800;
    border-radius:12px;padding:10px 14px;transition:.15s transform,.15s box-shadow,.15s background;
    background:var(--surface);color:var(--ink);border:1px solid var(--line);
  }
  .btn-ai:hover{transform:translateY(-1px);box-shadow:0 10px 20px rgba(13,23,38,.08);}
  .btn-ai--primary{
    background:linear-gradient(180deg, #f1f7ff, #eaf2ff);
    border-color:#dbeafe;
    color:#0b1220;
  }
  .btn-ai--ghost{background:var(--surface);}
  .btn-ai--sm{padding:8px 12px;font-size:.9rem;border-radius:10px;}

  .ico-ai svg{width:18px;height:18px;stroke:currentColor;stroke-width:2;fill:none;stroke-linecap:round;stroke-linejoin:round;}

  .tabs-ai{
    padding:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:12px;
    background:var(--surface-2);
  }
  .tab-ai{
    padding:9px 12px;border-radius:12px;border:1px solid var(--line);
    background:transparent;font-weight:900;color:var(--ink);display:flex;align-items:center;gap:8px;cursor:pointer;
    transition:.15s background,.15s border,.15s transform;
  }
  .tab-ai:hover{transform:translateY(-1px);background:#fff;}
  .tab-ai--active{
    background:#fff;border-color:#dbeafe;
    box-shadow:0 8px 18px rgba(29,78,216,.08);
  }
  .tabs-ai__mode{margin-left:auto;}
  .pill-ai{
    display:inline-flex;align-items:center;gap:8px;font-weight:800;font-size:.86rem;color:var(--muted);
    background:#fff;border:1px dashed var(--line);border-radius:999px;padding:6px 10px;
  }
  .dot-ai{width:8px;height:8px;border-radius:999px;display:inline-block;}
  .dot-ai--mint{background:var(--mint-ink);}
  .dot-ai--slate{background:#94a3b8;}

  .panel-ai{padding:14px;margin-bottom:12px;}
  .panel-ai__grid{display:grid;grid-template-columns:350px 1fr;gap:14px;}
  @media (max-width: 980px){ .panel-ai__grid{grid-template-columns:1fr;} }

  .step-ai{padding:12px;border-radius:14px;background:#fff;border:1px solid var(--line);}
  .step-ai__title{display:flex;gap:12px;align-items:flex-start;margin-bottom:10px;}
  .step-ai__num{
    width:34px;height:34px;border-radius:999px;display:grid;place-items:center;font-weight:900;
    background:#f1f5ff;color:#1d4ed8;border:1px solid #dbeafe;flex:0 0 auto;
  }
  .h3-ai{margin:0;font-weight:900;font-size:1.1rem;}
  .hint-ai{margin:4px 0 0;font-size:.86rem;color:var(--muted);}

  .row-ai{display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;}
  .select-wrap-ai{flex:1 1 180px;}
  .lbl-ai{display:block;font-weight:800;color:var(--ink);margin:0 0 6px;font-size:.9rem;}
  .inp-ai{
    width:100%;background:#fff;border:1px solid var(--line);border-radius:12px;
    padding:10px 12px;min-height:42px;font-size:.95rem;color:var(--ink);
  }
  .inp-ai:focus{outline:none;border-color:#c7d2fe;box-shadow:0 0 0 3px rgba(199,210,254,.5);}

  .qr-wrap-ai{margin-top:12px;display:flex;flex-direction:column;gap:10px;}
  .qr-card-ai{
    border-radius:16px;border:1px solid var(--line);
    background:linear-gradient(180deg,#ffffff, #f7faff);
    padding:12px;
  }
  .qr-card-ai__header{
    display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px;
  }
  .qr-chip-ai{
    display:inline-flex;align-items:center;gap:8px;font-weight:900;font-size:.85rem;
    background:#fff;border:1px solid var(--line);padding:6px 10px;border-radius:999px;color:var(--muted);
  }
  .qr-status-ai{
    font-weight:900;font-size:.84rem;color:var(--muted);display:flex;align-items:center;gap:8px;
  }

  .qr-box-ai{
    border-radius:14px;border:1px dashed #dbeafe;background:#fff;min-height:260px;
    display:grid;place-items:center;position:relative;overflow:hidden;
  }
  .qr-box-ai__frame{
    position:relative;background:#fff;border-radius:12px;padding:10px;border:1px solid var(--line);
  }
  .qr-box-ai__glow{
    position:absolute;inset:-60%;
    background:radial-gradient(closest-side, rgba(191,219,254,.8), transparent 60%);
    filter:blur(24px);opacity:.5;pointer-events:none;
    animation:qrGlow 2.2s ease-in-out infinite;
  }
  @keyframes qrGlow{0%,100%{transform:scale(.9)}50%{transform:scale(1.1)}}

  .qr-card-ai__footer{margin-top:10px;display:grid;grid-template-columns:1fr;gap:10px;}
  .qr-url-ai__label{font-weight:900;font-size:.8rem;color:var(--muted);margin-bottom:4px;}
  .qr-url-ai__link{
    display:block;word-break:break-all;font-weight:800;font-size:.9rem;color:var(--sky-ink);
    text-decoration:none;background:#fff;border:1px solid var(--line);padding:8px 10px;border-radius:12px;
  }

  .timeline-ai{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;}
  .timeline-ai__item{
    font-size:.8rem;font-weight:900;color:#94a3b8;background:#fff;border:1px solid var(--line);border-radius:12px;
    padding:8px;display:flex;align-items:center;gap:6px;justify-content:center;
  }
  .timeline-ai__dot{
    width:8px;height:8px;border-radius:999px;background:#cbd5e1;display:inline-block;
  }
  .timeline-ai__item.active{
    color:var(--mint-ink);border-color:#ccfbf1;background:#f0fdfa;
  }
  .timeline-ai__item.active .timeline-ai__dot{background:var(--mint-ink);}

  .status-ai{padding:10px 0;display:grid;gap:4px;}
  .status-ai__badge{
    display:inline-flex;align-items:center;gap:8px;font-weight:900;font-size:.95rem;
    background:#fff;border:1px solid var(--line);padding:8px 10px;border-radius:12px;width:fit-content;
  }
  .status-ai__hint{font-size:.9rem;color:var(--muted);}

  .waiting-ai{
    border:1px dashed var(--line);border-radius:14px;padding:12px;background:#fbfdff;
  }
  .waiting-ai__skeleton{
    height:12px;border-radius:999px;background:linear-gradient(90deg,#eef2ff,#f8fafc,#eef2ff);
    background-size:200% 100%;animation:sheen 1.2s infinite;margin-bottom:8px;
  }
  .waiting-ai__skeleton.short{width:60%;}
  @keyframes sheen{0%{background-position:200% 0}100%{background-position:-200% 0}}
  .waiting-ai__msg{margin-top:6px;font-weight:800;}

  .summary-ai{
    display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:10px;
  }
  @media (max-width: 780px){ .summary-ai{grid-template-columns:repeat(2,1fr);} }
  .summary-ai__item{
    background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px;
  }
  .summary-ai__label{font-size:.8rem;font-weight:900;color:var(--muted);}
  .summary-ai__value{font-size:.98rem;font-weight:900;color:var(--ink);margin-top:2px;}

  .table-ai{border:1px solid var(--line);border-radius:14px;overflow:auto;background:#fff;}
  .table-ai table{width:100%;border-collapse:collapse;font-size:.95rem;}
  .table-ai th,.table-ai td{padding:10px;border-bottom:1px solid var(--line);white-space:nowrap;}
  .table-ai th{background:#fbfdff;font-weight:900;text-align:left;color:var(--ink);}
  .table-ai td{color:var(--ink);}
  .table-ai .right{text-align:right;}

  .actions-ai{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;}

  .tips-ai{
    margin-top:10px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:10px 12px;
  }
  .tips-ai__title{font-weight:900;color:var(--ink);display:flex;align-items:center;gap:8px;margin-bottom:6px;}
  .tips-ai__list{margin:0 0 0 18px;padding:0;color:var(--muted);font-size:.9rem;font-weight:700;display:grid;gap:4px;}

  .alert-ai{
    background:#fff;border:1px solid #fee2e2;border-radius:14px;padding:12px;margin-bottom:12px;color:#991b1b;
  }
  .alert-ai__title{font-weight:900;margin-bottom:6px;}
  .alert-ai__list{margin:0 0 0 18px;font-weight:700;font-size:.92rem;}

  .form-ai{padding:16px;}
  .muted-ai{color:var(--muted);}
</style>
@endpush


@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  // ========= Tabs =========
  const tabManual = document.getElementById('tabManual');
  const tabAi = document.getElementById('tabAi');
  const panelManual = document.getElementById('panelManual');
  const panelAi = document.getElementById('panelAi');
  const modeLabel = document.getElementById('modeLabel');

  function setMode(mode){
    const isAi = mode === 'ai';
    panelAi.style.display = isAi ? 'block' : 'none';
    panelManual.style.display = isAi ? 'none' : 'block';
    tabAi.classList.toggle('tab-ai--active', isAi);
    tabManual.classList.toggle('tab-ai--active', !isAi);
    modeLabel.innerHTML = isAi
      ? `<span class="dot-ai dot-ai--mint"></span> Modo: IA`
      : `<span class="dot-ai dot-ai--mint"></span> Modo: Manual`;
  }
  tabManual.onclick = ()=>setMode('manual');
  tabAi.onclick = ()=>setMode('ai');

  // ========= IA START / POLL =========
  let intakeId = null;
  let pollTimer = null;
  let extractedCache = null;

  const btnAiStart   = document.getElementById('btnAiStart');
  const qrWrap       = document.getElementById('qrWrap');
  const qrBox        = document.getElementById('qrBox');
  const mobileUrlA   = document.getElementById('mobileUrl');

  const aiStatusText = document.getElementById('aiStatusText');
  const aiStatusHint = document.getElementById('aiStatusHint');
  const qrMiniStatus = document.getElementById('qrMiniStatus');

  const aiWaiting    = document.getElementById('aiWaiting');
  const aiResult     = document.getElementById('aiResult');

  const exSupplier   = document.getElementById('exSupplier');
  const exFolio      = document.getElementById('exFolio');
  const exDate       = document.getElementById('exDate');
  const exTotal      = document.getElementById('exTotal');
  const aiItemsTbody = document.getElementById('aiItemsTbody');

  const stMap = {
    0:{txt:'Pendiente', hint:'Esperando fotos del celular…'},
    1:{txt:'Fotos subidas', hint:'Fotos recibidas. Iniciando análisis…'},
    2:{txt:'Procesando IA', hint:'Analizando documento…'},
    3:{txt:'Listo', hint:'IA lista. Elige un ítem para rellenar.'},
    4:{txt:'Confirmado', hint:'Esta captura ya fue aplicada.'},
    9:{txt:'Falló', hint:'No se pudo analizar. Intenta otra vez.'},
  };

  function setTimelineActive(status){
    document.querySelectorAll('.timeline-ai__item').forEach(el=>{
      const st = parseInt(el.getAttribute('data-st'));
      el.classList.toggle('active', st <= status);
    });
  }

  function setStatusUI(status, meta){
    const st = stMap[status] || {txt:String(status), hint:''};
    aiStatusText.textContent = st.txt;
    aiStatusHint.textContent = (meta && meta.error) ? meta.error : st.hint;

    if(qrMiniStatus){
      qrMiniStatus.innerHTML = `<span class="dot-ai dot-ai--mint"></span> ${st.txt}`;
    }

    setTimelineActive(status);
  }

  btnAiStart.addEventListener('click', async ()=>{
    btnAiStart.disabled = true;

    try{
      const source_type = document.getElementById('aiSourceType').value;

      const res = await fetch(`{{ route('admin.catalog.ai.start') }}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ source_type })
      });

      const j = await res.json();
      if(!j.ok) throw new Error('No se pudo iniciar IA');

      intakeId = j.intake_id;

      qrWrap.style.display = 'block';
      qrBox.innerHTML = '';
      new QRCode(qrBox, { text: j.mobile_url, width: 228, height: 228 });

      mobileUrlA.href = j.mobile_url;
      mobileUrlA.textContent = j.mobile_url;

      setStatusUI(0);
      aiWaiting.style.display = 'block';
      aiResult.style.display = 'none';

      if(pollTimer) clearInterval(pollTimer);
      pollTimer = setInterval(pollStatus, 2200);

    }catch(e){
      alert(e.message || 'Error');
    }finally{
      btnAiStart.disabled = false;
    }
  });

  async function pollStatus(){
    if(!intakeId) return;

    const res = await fetch(`/admin/catalog/ai/${intakeId}/status`, {
      headers:{'X-Requested-With':'XMLHttpRequest'}
    });
    const j = await res.json();

    setStatusUI(j.status, j.meta);

    // al subir fotos, ocultar QR visualmente
    if (j.status >= 1 && j.status < 3) {
      const qrCard = document.querySelector('.qr-card-ai');
      if (qrCard) qrCard.style.display = 'none';
      aiWaiting.style.display = 'block';
    }

    if(j.status === 3){
      clearInterval(pollTimer);
      extractedCache = j.extracted || {};
      renderExtracted(extractedCache);
    }

    if(j.status === 9){
      clearInterval(pollTimer);
      aiWaiting.innerHTML = `
        <div style="font-weight:900;color:#991b1b;">
          ${ (j.meta && j.meta.error) ? j.meta.error : 'Falló la IA.'}
        </div>`;
    }
  }

  function renderExtracted(ex){
    aiWaiting.style.display = 'none';
    aiResult.style.display = 'block';

    exSupplier.textContent = ex.supplier_name || '—';
    exFolio.textContent    = ex.folio || '—';
    exDate.textContent     = ex.invoice_date || '—';
    exTotal.textContent    = (ex.total ?? '—');

    const items = ex.items || [];
    aiItemsTbody.innerHTML = '';

    items.forEach((it, idx)=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${it.sku || '—'}</td>
        <td style="white-space:normal;min-width:240px;">${it.description || '—'}</td>
        <td>${it.quantity ?? '—'}</td>
        <td>${it.unit || '—'}</td>
        <td>${it.unit_price ?? '—'}</td>
        <td>${it.line_total ?? '—'}</td>
        <td class="right">
          <button type="button" class="btn-ai btn-ai--ghost btn-ai--sm" data-use="${idx}">
            Usar
          </button>
        </td>
      `;
      aiItemsTbody.appendChild(tr);
    });

    aiItemsTbody.querySelectorAll('button[data-use]').forEach(btn=>{
      btn.onclick = ()=>{
        const i = parseInt(btn.getAttribute('data-use'));
        fillFormFromItem(items[i]);
        setMode('manual');
      };
    });

    const btnBackManual = document.getElementById('btnBackManual');
    if(btnBackManual) btnBackManual.onclick = ()=>setMode('manual');
  }

  const btnFillFirst = document.getElementById('btnFillFirst');
  if(btnFillFirst){
    btnFillFirst.onclick = ()=>{
      const items = (extractedCache && extractedCache.items) ? extractedCache.items : [];
      if(!items.length) return alert('No hay ítems.');
      fillFormFromItem(items[0]);
      setMode('manual');
    };
  }

  function fillFormFromItem(it){
    if(!it) return;

    const setVal = (name, val)=>{
      const el = document.querySelector(`[name="${name}"]`);
      if(el && val !== undefined && val !== null && val !== '') el.value = val;
    };

    const desc  = (it.description || '').trim();
    const brand = (it.brand || '').trim();
    const model = (it.model || '').trim();

    let finalName = desc;
    if(brand && !finalName.toLowerCase().includes(brand.toLowerCase())) finalName += ' ' + brand;
    if(model && !finalName.toLowerCase().includes(model.toLowerCase())) finalName += ' ' + model;

    setVal('name', finalName || desc || 'PRODUCTO SIN NOMBRE');
    setVal('sku', it.sku || '');
    setVal('price', it.unit_price || 0);
    setVal('brand_name', brand);
    setVal('model_name', model);
    setVal('excerpt', desc ? desc.slice(0, 140) : '');

    const extra = extractedCache || {};
    let longDesc = '';
    if(extra.supplier_name) longDesc += `Proveedor: ${extra.supplier_name}\n`;
    if(extra.folio) longDesc += `Folio: ${extra.folio}\n`;
    if(extra.invoice_date) longDesc += `Fecha: ${extra.invoice_date}\n\n`;
    longDesc += `Descripción en documento:\n${desc}\n\nCantidad: ${it.quantity ?? '—'} ${it.unit || ''}\nPrecio unitario: ${it.unit_price ?? '—'}\nTotal línea: ${it.line_total ?? '—'}`;

    const dEl = document.querySelector('[name="description"]');
    if(dEl) dEl.value = longDesc;

    if(it.gtin) setVal('meli_gtin', it.gtin);

    window.scrollTo({top:0, behavior:'smooth'});
  }
</script>
@endpush
