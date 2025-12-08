{{-- resources/views/tickets/create.blade.php --}}
@extends('layouts.app')
@section('title','Nuevo ticket')

@section('content')
<div id="tkt-create" class="container-fluid p-0">
  <style>
    /* =========================
       NAMESPACE: #tkt-create
       ========================= */
    #tkt-create{
      --ink:#0f172a;
      --muted:#6b7280;
      --line:#e2e8f0;
      --bg:#f8fafc;
      --card:#ffffff;

      --accent-soft:#e0edff;
      --accent-soft-2:#f1f5ff;
      --accent-border:#c7d2fe;
      --accent-ink:#1d4ed8;

      --ok:#16a34a;
      --warn:#f59e0b;
      --danger:#ef4444;

      --ring:0 0 0 5px rgba(129,140,248,.18);
      --shadow:0 20px 45px rgba(15,23,42,.10);
      --radius:16px;
    }

    #tkt-create *{box-sizing:border-box}
    #tkt-create .wrap{
      max-width:1200px;
      margin:clamp(16px,3vw,28px) auto;
      padding:0 16px 32px;
    }

    body{
      background:radial-gradient(circle at top left,#e0ecff 0,#f8fafc 40%,#ffffff 100%);
    }

    /* Top header */
    #tkt-create .top{
      display:flex;
      justify-content:space-between;
      align-items:flex-end;
      gap:12px;
      margin-bottom:16px;
    }
    #tkt-create .h{
      font-weight:800;
      color:var(--ink);
      margin:0;
      letter-spacing:.02em;
    }
    #tkt-create .h-main{
      font-size:1.35rem;
    }
    #tkt-create .sub{
      color:var(--muted);
      margin:.25rem 0 0;
      font-size:.93rem;
    }

    #tkt-create .shortcut-pill{
      display:flex;
      flex-direction:column;
      align-items:flex-end;
      padding:.45rem .75rem;
      border-radius:999px;
      border:1px solid var(--accent-border);
      background:linear-gradient(135deg,#f5f7ff,#ffffff);
      font-size:.8rem;
      gap:2px;
      box-shadow:0 10px 28px rgba(15,23,42,.08);
      animation:fadeInUp .5s ease-out both;
    }
    #tkt-create .shortcut-label{
      font-weight:600;
      color:var(--muted);
    }
    #tkt-create .shortcut-keys{
      font-weight:700;
      letter-spacing:.04em;
      color:var(--accent-ink);
    }

    /* Layout */
    #tkt-create .layout{
      display:grid;
      grid-template-columns:2fr 1.05fr;
      gap:18px;
      align-items:flex-start;
    }

    /* Cards */
    #tkt-create .card{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
      animation:floatIn .55s ease-out both;
    }
    #tkt-create .card-main{animation-delay:.02s}
    #tkt-create .card-aside{animation-delay:.08s}

    #tkt-create .head{
      padding:14px 18px;
      border-bottom:1px solid var(--line);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      background:linear-gradient(135deg,#f5f7ff,#ffffff);
    }
    #tkt-create .body{
      padding:18px 18px 20px;
    }
    #tkt-create .footer{
      display:flex;
      gap:10px;
      justify-content:flex-end;
      padding:14px 18px;
      border-top:1px solid var(--line);
      background:#f9fafb;
    }

    #tkt-create .head-main{
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    #tkt-create .h-small{
      font-size:1.02rem;
      font-weight:700;
    }

    /* Step indicator (solo visual) */
    #tkt-create .steps{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      font-size:.78rem;
    }
    #tkt-create .step{
      padding:.16rem .6rem;
      border-radius:999px;
      border:1px solid transparent;
      background:transparent;
      color:var(--muted);
    }
    #tkt-create .step.is-active{
      background:var(--accent-soft);
      border-color:var(--accent-border);
      color:var(--accent-ink);
      font-weight:600;
    }

    /* Grid formulario */
    #tkt-create .grid{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:14px;
    }
    #tkt-create .row{
      display:flex;
      flex-direction:column;
      gap:6px;
    }
    #tkt-create label{
      font-weight:600;
      color:var(--ink);
      font-size:.9rem;
    }

    /* Inputs */
    #tkt-create input[type="text"],
    #tkt-create input[type="number"],
    #tkt-create input[type="datetime-local"],
    #tkt-create select,
    #tkt-create input[type="url"],
    #tkt-create textarea{
      width:100%;
      padding:.7rem .8rem;
      border:1px solid var(--line);
      border-radius:12px;
      background:#ffffff;
      color:var(--ink);
      outline:none;
      transition:
        box-shadow .2s ease,
        border-color .2s ease,
        transform .08s ease,
        background .15s ease;
      font-size:.9rem;
    }
    #tkt-create input::placeholder,
    #tkt-create textarea::placeholder{
      color:#9ca3af;
    }
    #tkt-create input:focus,
    #tkt-create select:focus,
    #tkt-create textarea:focus{
      border-color:var(--accent-border);
      box-shadow:var(--ring);
      background:#f9fbff;
      transform:translateY(-1px);
    }
    #tkt-create textarea{
      resize:vertical;
      min-height:90px;
    }

    #tkt-create .hint{
      font-size:.8rem;
      color:var(--muted);
    }
    #tkt-create .error{
      font-size:.8rem;
      color:#b91c1c;
    }

    /* Chips + Badges */
    #tkt-create .chips{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
    }
    #tkt-create .chip{
      padding:.32rem .75rem;
      border:1px solid var(--line);
      border-radius:999px;
      background:#ffffff;
      font-size:.8rem;
      cursor:pointer;
      user-select:none;
      transition:
        transform .09s ease,
        background .18s ease,
        border-color .18s ease,
        box-shadow .18s ease;
      white-space:nowrap;
    }
    #tkt-create .chip:hover{
      transform:translateY(-1px);
      box-shadow:0 8px 18px rgba(15,23,42,.06);
    }
    #tkt-create .chip.active{
      background:var(--accent-soft);
      border-color:var(--accent-border);
      color:var(--accent-ink);
      font-weight:600;
    }

    #tkt-create .badge{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding:.28rem .7rem;
      border-radius:999px;
      font-size:.78rem;
      background:#fef2f2;
      border:1px solid #fecaca;
      color:#991b1b;
    }

    /* Alerts compactas */
    #tkt-create .alert{
      border-radius:12px;
      padding:.6rem .75rem;
      font-size:.8rem;
      margin-bottom:10px;
      display:flex;
      align-items:center;
      gap:8px;
    }
    #tkt-create .alert-error{
      background:#fef2f2;
      border:1px solid #fecaca;
      color:#991b1b;
    }
    #tkt-create .alert-warn{
      background:#fffbeb;
      border:1px solid #fde68a;
      color:#92400e;
    }

    /* Botones */
    #tkt-create .btn{
      appearance:none;
      border-radius:999px;
      padding:.6rem 1.1rem;
      font-weight:600;
      font-size:.9rem;
      cursor:pointer;
      border:1px solid #d4ddff;
      background:linear-gradient(120deg,#ffffff,#f4f7ff);
      transition:
        transform .12s ease,
        box-shadow .15s ease,
        background-position .2s ease,
        opacity .1s ease;
      background-size:220% 220%;
      background-position:0 0;
      display:inline-flex;
      align-items:center;
      gap:6px;
      color:var(--ink);
      text-decoration:none;
      white-space:nowrap;
    }
    #tkt-create .btn:hover{
      transform:translateY(-1px);
      box-shadow:0 12px 28px rgba(15,23,42,.12);
      background-position:100% 0;
    }
    #tkt-create .btn:active{
      transform:translateY(0);
      box-shadow:0 6px 14px rgba(15,23,42,.10);
    }
    #tkt-create .btn.primary{
      border-color:var(--accent-border);
      background-image:linear-gradient(120deg,#e0edff,#f5f7ff);
      color:var(--accent-ink);
    }
    #tkt-create .btn[aria-busy="true"]{
      opacity:.75;
      pointer-events:none;
    }

    /* Spinner sutil */
    #tkt-create .save-spin{
      width:14px;
      height:14px;
      border-radius:999px;
      border:2px solid rgba(148,163,184,.6);
      border-top-color:rgba(37,99,235,.95);
      animation:spin .65s linear infinite;
      flex-shrink:0;
    }

    /* Panel lateral (preview) */
    #tkt-create .aside{
      position:sticky;
      top:18px;
      align-self:flex-start;
    }
    #tkt-create .kv{
      display:grid;
      grid-template-columns:auto 1fr;
      gap:6px 10px;
      align-items:center;
    }
    #tkt-create .kv .k{
      color:var(--muted);
      font-size:.83rem;
    }
    #tkt-create .kv .v{
      font-weight:600;
      font-size:.86rem;
      color:var(--ink);
    }
    #tkt-create .label-pill{
      padding:.24rem .6rem;
      border-radius:999px;
      border:1px solid var(--accent-border);
      background:#f5f7ff;
      font-size:.78rem;
      color:var(--accent-ink);
      font-weight:600;
    }

    #tkt-create .progress{
      height:9px;
      border-radius:999px;
      background:#eef2ff;
      overflow:hidden;
    }
    #tkt-create .progress>span{
      display:block;
      height:100%;
      background:linear-gradient(90deg,#c4d7ff,#e0e7ff);
      transform-origin:left;
      transition:width .25s ease-out;
    }

    /* Secciones */
    #tkt-create .section-title{
      font-weight:700;
      color:var(--ink);
      margin:.6rem 0 .45rem;
      font-size:.92rem;
    }
    #tkt-create .section{
      border-radius:12px;
      padding:12px 12px 10px;
      background:var(--accent-soft-2);
      border:1px dashed var(--accent-border);
      margin-top:10px;
    }
    #tkt-create .section-note{
      font-size:.8rem;
      color:var(--muted);
    }

    /* Animaciones */
    @keyframes floatIn{
      from{
        opacity:0;
        transform:translateY(12px) scale(.98);
      }
      to{
        opacity:1;
        transform:translateY(0) scale(1);
      }
    }
    @keyframes fadeInUp{
      from{
        opacity:0;
        transform:translateY(8px);
      }
      to{
        opacity:1;
        transform:translateY(0);
      }
    }
    @keyframes spin{
      to{transform:rotate(360deg);}
    }

    /* Responsivo */
    @media (max-width:1100px){
      #tkt-create .layout{grid-template-columns:1fr}
      #tkt-create .aside{position:static}
    }
    @media (max-width:768px){
      #tkt-create .grid{grid-template-columns:1fr}
      #tkt-create .top{
        flex-direction:column;
        align-items:flex-start;
      }
      #tkt-create .shortcut-pill{
        align-items:flex-start;
      }
    }
  </style>

  <div class="wrap">
    <div class="top">
      <div>
        <h1 class="h h-main">Nuevo ticket</h1>
        <p class="sub">Completa los datos básicos para registrar el ticket.</p>
      </div>
      <div class="shortcut-pill" aria-label="Atajo para guardar el ticket">
        <span class="shortcut-label">Atajo de guardado</span>
        <span class="shortcut-keys">Ctrl + S / Cmd + S</span>
      </div>
    </div>

    <div class="layout">
      {{-- ======== COLUMNA FORMULARIO ======== --}}
      <form id="tktForm" method="POST" action="{{ route('tickets.store') }}" class="card card-main" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="head">
          <div class="head-main">
            <div class="h h-small">Datos del ticket</div>
            <div class="steps">
              <span class="step is-active">1 Datos</span>
              <span class="step">2 Tipo</span>
              <span class="step">3 Responsable</span>
              <span class="step">4 Detalles</span>
            </div>
          </div>
          <div class="right" style="display:flex;gap:8px;align-items:center">
            <a href="{{ route('tickets.index') }}" class="btn">Cancelar</a>
            <button id="submitBtn" type="submit" class="btn primary">
              <span class="save-label">Guardar ticket</span>
              <span class="save-spin" style="display:none"></span>
            </button>
          </div>
        </div>

        <div class="body">
          {{-- Mensajes rápidos --}}
          @if(session('err'))
            <div class="alert alert-error">
              <span>{{ session('err') }}</span>
            </div>
          @endif
          @if($errors->any())
            <div class="alert alert-warn">
              <span>Revisa los campos marcados.</span>
            </div>
          @endif

          {{-- 1) Identificación --}}
          <div class="section-title">1. Datos básicos</div>
          <div class="grid">
            <div class="row">
              <label for="title">Título / asunto <span class="hint">(obligatorio)</span></label>
              <input
                id="title"
                type="text"
                name="title"
                maxlength="180"
                value="{{ old('title') }}"
                placeholder="Ej. Licitación de mobiliario escolar turno matutino"
                required
              >
              @error('title') <div class="error">{{ $message }}</div> @enderror
              <div class="hint" id="title-count">0 / 180</div>
            </div>

            <div class="row">
              <label for="client_name">Cliente / institución</label>
              <input
                id="client_name"
                type="text"
                name="client_name"
                value="{{ old('client_name') }}"
                placeholder="Ej. Hospital San Lucas, SEP"
              >
              @error('client_name') <div class="error">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- 2) Tipo y prioridad --}}
          <div class="section-title">2. Tipo y prioridad</div>
          <div class="grid">
            <div class="row">
              <label for="type">Tipo de ticket</label>
              <select id="type" name="type" required>
                @foreach(['licitacion'=>'Licitación','pedido'=>'Pedido','cotizacion'=>'Cotización','entrega'=>'Entrega','queja'=>'Queja'] as $val=>$label)
                  <option value="{{ $val }}" @selected(old('type')===$val)>{{ $label }}</option>
                @endforeach
              </select>
              @error('type') <div class="error">{{ $message }}</div> @enderror
              <div class="chips" data-sync-select="#type" style="margin-top:8px">
                <span class="chip" data-value="licitacion">Licitación</span>
                <span class="chip" data-value="pedido">Pedido</span>
                <span class="chip" data-value="cotizacion">Cotización</span>
                <span class="chip" data-value="entrega">Entrega</span>
                <span class="chip" data-value="queja">Queja</span>
              </div>
            </div>

            <div class="row">
              <label for="priority">Prioridad</label>
              <select id="priority" name="priority" required>
                @foreach(['alta'=>'Alta','media'=>'Media','baja'=>'Baja'] as $val=>$label)
                  <option value="{{ $val }}" @selected(old('priority')===$val)>{{ $label }}</option>
                @endforeach
              </select>
              @error('priority') <div class="error">{{ $message }}</div> @enderror

              <div class="chips" data-sync-select="#priority" style="margin-top:8px">
                <span class="chip" data-value="alta">Alta</span>
                <span class="chip" data-value="media">Media</span>
                <span class="chip" data-value="baja">Baja</span>
              </div>
            </div>
          </div>

          {{-- 3) Responsable y plazo --}}
          <div class="section-title">3. Responsable y tiempo</div>
          <div class="grid">
            <div class="row">
              <label for="owner_id">Responsable asignado (ID)</label>
              <input
                id="owner_id"
                type="number"
                name="owner_id"
                value="{{ old('owner_id', auth()->id()) }}"
                placeholder="Ej. {{ auth()->id() }}"
              >
              @error('owner_id') <div class="error">{{ $message }}</div> @enderror
              <div class="hint">Puedes ajustarlo después.</div>
            </div>

            <div class="row">
              <label for="due_at">Fecha límite estimada</label>
              <input
                id="due_at"
                type="datetime-local"
                name="due_at"
                value="{{ old('due_at') }}"
              >
              @error('due_at') <div class="error">{{ $message }}</div> @enderror
              <div class="chips" id="sla-shortcuts" style="margin-top:8px">
                <span class="chip" data-hours="24">+24 h</span>
                <span class="chip" data-hours="48">+48 h</span>
                <span class="chip" data-hours="72">+72 h</span>
                <span class="chip" data-hours="168">+7 días</span>
              </div>
              <div class="hint">Elige fecha o usa un atajo rápido.</div>
            </div>
          </div>

          {{-- 4) (Opcional) Licitación y link --}}
          <div class="section-title">4. Información adicional (opcional)</div>
          <div class="grid">
            <div class="row">
              <label for="numero_licitacion">Número de licitación</label>
              <input
                id="numero_licitacion"
                type="text"
                name="numero_licitacion"
                value="{{ old('numero_licitacion') }}"
                placeholder="Ej. LA-012345-ABC-2025"
              >
              @error('numero_licitacion') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="row">
              <label for="monto_propuesta">Monto de la propuesta</label>
              <input
                id="monto_propuesta"
                type="number"
                step="0.01"
                name="monto_propuesta"
                value="{{ old('monto_propuesta') }}"
                placeholder="0.00"
              >
              @error('monto_propuesta') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="row">
              <label for="link_inicial">Link relacionado</label>
              <input
                id="link_inicial"
                type="url"
                name="link_inicial"
                value="{{ old('link_inicial') }}"
                placeholder="https://..."
              >
              <div class="hint">Podrás agregar más enlaces dentro del ticket.</div>
            </div>

            <div class="row">
              <label for="nota">Notas rápidas (temporal)</label>
              <textarea
                id="nota"
                rows="3"
                placeholder="Zona temporal para copiar/pegar (no se guarda)"
                spellcheck="false"
              ></textarea>
              <div class="hint">Solo uso temporal; no se guarda.</div>
            </div>
          </div>

          <div class="section">
            <span class="section-note">
              Las etapas base del flujo (recepción, análisis, cotización, aprobación y cierre)
              se crean automáticamente. Después podrás ajustarlas.
            </span>
          </div>
        </div>

        <div class="footer">
          <button id="submitBtn2" type="submit" class="btn primary">
            <span class="save-label">Guardar ticket</span>
            <span class="save-spin" style="display:none"></span>
          </button>
        </div>
      </form>

      {{-- ======== COLUMNA PREVIEW ======== --}}
      <aside class="aside">
        <div class="card card-aside">
          <div class="head">
            <div style="display:flex;flex-direction:column;gap:2px">
              <div class="h h-small">Previsualización</div>
              <div class="hint">Resumen en vivo del ticket.</div>
            </div>
            <span class="label-pill">Vista previa</span>
          </div>
          <div class="body" style="display:flex;flex-direction:column;gap:14px">
            <div class="kv">
              <div class="k">Folio estimado</div><div class="v">Se asigna al guardar</div>
              <div class="k">Título</div><div class="v" id="pv-title">—</div>
              <div class="k">Cliente</div><div class="v" id="pv-client">—</div>
              <div class="k">Tipo</div><div class="v" id="pv-type">—</div>
              <div class="k">Prioridad</div><div class="v" id="pv-priority">—</div>
              <div class="k">Responsable</div><div class="v" id="pv-owner">ID {{ auth()->id() }}</div>
              <div class="k">Fecha límite</div><div class="v" id="pv-due">—</div>
            </div>

            <div>
              <div class="hint" style="margin-bottom:6px">Avance estimado de datos</div>
              <div class="progress" aria-hidden="true">
                <span id="pv-progress" style="width:12%"></span>
              </div>
              <div class="hint" id="pv-progress-label" style="margin-top:4px">Inicial • 12%</div>
            </div>

            <div class="section" style="margin-top:2px">
              <div class="section-title" style="margin:0 0 4px">Etapas base</div>
              <ol id="pv-stages" style="margin:0 0 4px 1.2rem;padding:0;line-height:1.45;font-size:.86rem">
                <li>Recepción de ticket</li>
                <li>Análisis técnico / comercial</li>
                <li>Cotización y envío</li>
                <li>Aprobación y seguimiento</li>
                <li>Entrega y cierre</li>
              </ol>
              <div class="hint">Más adelante podrás ajustar etapas y checklists.</div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

{{-- JS UX: chips ↔ select, contador, atajos SLA, preview, submit spinner, Ctrl/Cmd+S --}}
<script>
(function(){
  const $  = (s,root=document) => root.querySelector(s);
  const $$ = (s,root=document) => Array.from(root.querySelectorAll(s));

  const form       = $('#tktForm');
  const submitBtns = $$('#submitBtn, #submitBtn2');

  // 1) Chips que sincronizan con selects (tipo / prioridad)
  $$(".chips[data-sync-select]").forEach(group => {
    const sel      = group.getAttribute('data-sync-select');
    const selectEl = document.querySelector(sel);

    const setActive = (val) => {
      $$(".chip", group).forEach(ch =>
        ch.classList.toggle('active', ch.dataset.value === val)
      );
      if (!selectEl) return;
      selectEl.value = val;
      selectEl.dispatchEvent(new Event('change', {bubbles:true}));
    };

    group.addEventListener('click', e => {
      const chip = e.target.closest('.chip');
      if (!chip) return;
      setActive(chip.dataset.value);
    });

    // Estado inicial
    if (selectEl) {
      setActive(selectEl.value || selectEl.options?.[0]?.value || '');
    }
  });

  // 2) Contador de caracteres para Título + preview
  const title    = $("#title");
  const counter  = $("#title-count");
  const pvTitle  = $("#pv-title");

  const pvBar    = $("#pv-progress");
  const pvLabel  = $("#pv-progress-label");

  function progressTo(pct, label){
    if (!pvBar || !pvLabel) return;
    const clamped = Math.max(12, Math.min(60, pct));
    pvBar.style.width = clamped + "%";
    pvLabel.textContent = label;
  }

  if (title && counter) {
    const update = () => {
      const len   = title.value.length;
      const max   = title.maxLength || 180;
      const has   = title.value.trim().length > 0;

      counter.textContent = `${len} / ${max}`;
      pvTitle.textContent = has ? title.value.trim() : '—';

      progressTo(
        has ? 20 : 12,
        has ? 'Datos básicos • 20%' : 'Inicial • 12%'
      );
    };
    title.addEventListener('input', update);
    update();
  }

  // 3) Preview de cliente, tipo, prioridad, responsable y SLA
  const pvClient   = $("#pv-client");
  const pvType     = $("#pv-type");
  const pvPriority = $("#pv-priority");
  const pvOwner    = $("#pv-owner");
  const pvDue      = $("#pv-due");
  const due        = $("#due_at");

  $("#client_name")?.addEventListener('input', e => {
    pvClient.textContent = e.target.value.trim() || '—';
  });

  $("#type")?.addEventListener('change', e => {
    const opt = e.target.options[e.target.selectedIndex];
    pvType.textContent = opt?.text || '—';
  });

  $("#priority")?.addEventListener('change', e => {
    const opt = e.target.options[e.target.selectedIndex];
    pvPriority.textContent = opt?.text || '—';
  });

  $("#owner_id")?.addEventListener('input', e => {
    const val = e.target.value || "{{ auth()->id() }}";
    pvOwner.textContent = "ID " + val;
  });

  const fmtLocal = (dt) => {
    if (!dt) return '—';
    const d = new Date(dt);
    if (isNaN(d)) return '—';
    return d.toLocaleString();
  };

  due?.addEventListener('input', () => {
    pvDue.textContent = fmtLocal(due.value);
    if (due.value) {
      progressTo(28, 'Planificado • 28%');
    } else {
      progressTo(20, 'Datos básicos • 20%');
    }
  });

  // 4) Atajos SLA (+24/+48/+72h +7d)
  $("#sla-shortcuts")?.addEventListener('click', e => {
    const chip = e.target.closest('.chip');
    if (!chip || !due) return;

    const h = parseInt(chip.dataset.hours || "0", 10);
    const now = new Date();
    now.setHours(now.getHours() + h);

    const pad = n => String(n).padStart(2,'0');
    const v = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;

    due.value = v;
    pvDue.textContent = fmtLocal(v);

    $$("#sla-shortcuts .chip").forEach(c => c.classList.remove('active'));
    chip.classList.add('active');

    progressTo(28, 'Planificado • 28%');
  });

  // 5) Submit spinner + bloqueo doble click
  function setBusy(busy){
    submitBtns.forEach(btn => {
      btn.setAttribute('aria-busy', busy ? 'true':'false');
      const spin = btn.querySelector('.save-spin');
      const lbl  = btn.querySelector('.save-label');
      if (spin) spin.style.display = busy ? '' : 'none';
      if (lbl)  lbl.textContent = busy ? 'Guardando...' : 'Guardar ticket';
    });
  }

  form?.addEventListener('submit', () => setBusy(true));

  // 6) Atajo Ctrl/Cmd+S para enviar
  document.addEventListener('keydown', (e) => {
    const isMac = navigator.platform.toUpperCase().includes('MAC');
    const key   = e.key && e.key.toLowerCase();

    if ((isMac && e.metaKey && key === 's') || (!isMac && e.ctrlKey && key === 's')) {
      e.preventDefault();
      if (form) form.requestSubmit();
    }
  });

  // Inicializar previews con valores existentes
  $("#type")?.dispatchEvent(new Event('change'));
  $("#priority")?.dispatchEvent(new Event('change'));
  $("#client_name")?.dispatchEvent(new Event('input'));
  $("#owner_id")?.dispatchEvent(new Event('input'));
  if (due?.value) due.dispatchEvent(new Event('input'));
})();
</script>
@endsection
