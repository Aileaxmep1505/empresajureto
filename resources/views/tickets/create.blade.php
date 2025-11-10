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
      --ink:#0e1726; --muted:#64748b; --line:#e6ecf4; --bg:#f7fbff; --card:#ffffff;
      --brand:#a9d4ff; --brand-ink:#0b1220; --ok:#16a34a; --warn:#f59e0b; --danger:#ef4444;
      --ring:0 0 0 5px rgba(169,212,255,.30);
      --shadow:0 18px 45px rgba(2,8,23,.08);
      --radius:16px;
    }
    #tkt-create *{box-sizing:border-box}
    #tkt-create .wrap{max-width:1200px;margin:clamp(16px,2vw,28px) auto;padding:0 16px}
    #tkt-create .layout{display:grid;grid-template-columns:2fr 1.1fr;gap:18px}
    #tkt-create .h{font-weight:800;color:var(--ink);margin:0}
    #tkt-create .sub{color:var(--muted);margin:.25rem 0 1rem}

    /* Cards */
    #tkt-create .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow)}
    #tkt-create .head{padding:16px 18px;border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;gap:10px}
    #tkt-create .body{padding:18px}
    #tkt-create .footer{display:flex;gap:10px;justify-content:flex-end;padding:14px 18px;border-top:1px solid var(--line)}

    /* Grid formulario */
    #tkt-create .grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    #tkt-create .row{display:flex;flex-direction:column;gap:6px}
    #tkt-create label{font-weight:700;color:var(--ink);font-size:.94rem}

    /* Inputs */
    #tkt-create input[type="text"],
    #tkt-create input[type="number"],
    #tkt-create input[type="datetime-local"],
    #tkt-create select,
    #tkt-create input[type="url"],
    #tkt-create textarea{
      width:100%; padding:.7rem .8rem; border:1px solid var(--line); border-radius:12px;
      background:#fff; color:var(--ink); outline:none; transition:box-shadow .2s, border-color .2s, transform .05s;
    }
    #tkt-create input:focus, #tkt-create select:focus, #tkt-create textarea:focus{
      border-color:#c7ddff; box-shadow:var(--ring);
    }
    #tkt-create .hint{font-size:.82rem;color:var(--muted)}
    #tkt-create .error{font-size:.82rem;color:#b91c1c}

    /* Chips + Badges */
    #tkt-create .chips{display:flex;gap:8px;flex-wrap:wrap}
    #tkt-create .chip{
      padding:.32rem .72rem;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:.82rem;cursor:pointer;
      user-select:none; transition:transform .08s ease, background .2s ease, border-color .2s ease;
    }
    #tkt-create .chip:hover{transform:translateY(-1px)}
    #tkt-create .chip.active{background:#eef6ff;border-color:#c7ddff}
    #tkt-create .badge{display:inline-flex;align-items:center;gap:6px;padding:.24rem .6rem;border:1px solid var(--line);border-radius:999px;font-size:.78rem;background:#fff}

    /* Botones */
    #tkt-create .btn{
      appearance:none; border:1px solid #dbe4ff; background:linear-gradient(180deg,#fff,#f2f7ff);
      border-radius:12px; padding:.6rem 1rem; font-weight:700; cursor:pointer;
      transition:transform .15s, box-shadow .15s, opacity .1s;
    }
    #tkt-create .btn:hover{transform:translateY(-1px); box-shadow:0 10px 22px rgba(2,6,23,.09)}
    #tkt-create .btn.primary{border-color:#b6d7ff;background:linear-gradient(180deg,#eaf3ff,#d3e6ff)}
    #tkt-create .btn[aria-busy="true"]{opacity:.7;pointer-events:none}

    /* Panel lateral (preview) */
    #tkt-create .aside{position:sticky; top:16px; align-self:start}
    #tkt-create .kv{display:grid;grid-template-columns:auto 1fr;gap:6px 10px;align-items:center}
    #tkt-create .kv .k{color:var(--muted);font-size:.86rem}
    #tkt-create .kv .v{font-weight:700}
    #tkt-create .progress{height:10px;border-radius:999px;background:#eef2ff;overflow:hidden}
    #tkt-create .progress>span{display:block;height:100%;background:linear-gradient(90deg,#b6d7ff,#d7eaff)}
    #tkt-create .pill{display:inline-flex;gap:6px;align-items:center;padding:.2rem .55rem;border:1px solid var(--line);border-radius:999px;background:#fff;font-size:.78rem}

    /* Secciones */
    #tkt-create .section-title{font-weight:800;color:var(--ink);margin:.4rem 0 .6rem}
    #tkt-create .section{border:1px dashed var(--line);border-radius:12px;padding:12px;background:#fbfdff}

    /* Responsivo */
    @media (max-width: 1100px){
      #tkt-create .layout{grid-template-columns:1fr}
    }
  </style>

  <div class="wrap">
    <div class="top" style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:10px;gap:12px">
      <div>
        <h1 class="h">Crear nuevo ticket</h1>
        <p class="sub">Define asunto, tipo, prioridad y plazo. El folio se genera automáticamente al guardar.</p>
      </div>
      <div class="pill" title="Atajo de teclado">
        ⌨️ <span class="hint">Guardar con <strong>Ctrl/⌘+S</strong></span>
      </div>
    </div>

    <div class="layout">
      {{-- ======== COLUMNA FORMULARIO ======== --}}
      <form id="tktForm" method="POST" action="{{ route('tickets.store') }}" class="card" enctype="multipart/form-data" novalidate>
        @csrf
        <div class="head">
          <div class="left" style="display:flex;flex-direction:column;gap:4px">
            <div class="h" style="font-size:1.05rem">Datos generales</div>
            <div class="hint">Se crearán etapas base: Recepción, Análisis, Cotización, Aprobación y Entrega.</div>
          </div>
          <div class="right" style="display:flex;gap:8px;align-items:center">
            <a href="{{ route('tickets.index') }}" class="btn">Volver</a>
            <button id="submitBtn" type="submit" class="btn primary">
              <span class="save-label">Guardar ticket</span>
              <span class="save-spin" style="display:none;margin-left:6px">⏳</span>
            </button>
          </div>
        </div>

        <div class="body">
          {{-- Mensajes rápidos --}}
          @if(session('err'))
            <div class="badge" style="border-color:#fecaca;background:#fff1f2">⚠️ {{ session('err') }}</div>
          @endif
          @if($errors->any())
            <div class="badge" style="border-color:#fde68a;background:#fffbeb">⚠️ Revisa los campos marcados.</div>
          @endif

          {{-- 1) Identificación --}}
          <div class="section-title">1) Identificación</div>
          <div class="grid">
            <div class="row">
              <label for="title">Título / Asunto <span class="hint">(obligatorio)</span></label>
              <input id="title" type="text" name="title" maxlength="180"
                     value="{{ old('title') }}" placeholder="Ej. Licitación de mobiliario escolar turno matutino" required>
              @error('title') <div class="error">{{ $message }}</div> @enderror
              <div class="hint" id="title-count">0 / 180</div>
            </div>

            <div class="row">
              <label for="client_name">Cliente / institución</label>
              <input id="client_name" type="text" name="client_name" value="{{ old('client_name') }}" placeholder="Hospital San Lucas, SEP, etc.">
              @error('client_name') <div class="error">{{ $message }}</div> @enderror
            </div>
          </div>

          {{-- 2) Tipo y prioridad --}}
          <div class="section-title">2) Tipo y prioridad</div>
          <div class="grid">
            <div class="row">
              <label for="type">Tipo de ticket</label>
              <select id="type" name="type" required>
                @foreach(['licitacion'=>'Licitación','pedido'=>'Pedido','cotizacion'=>'Cotización','entrega'=>'Entrega','queja'=>'Queja'] as $val=>$label)
                  <option value="{{ $val }}" @selected(old('type')===$val)>{{ $label }}</option>
                @endforeach
              </select>
              @error('type') <div class="error">{{ $message }}</div> @enderror>

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
          <div class="section-title">3) Responsable y plazos</div>
          <div class="grid">
            <div class="row">
              <label for="owner_id">Responsable asignado (ID de usuario)</label>
              <input id="owner_id" type="number" name="owner_id" value="{{ old('owner_id', auth()->id()) }}" placeholder="Ej. {{ auth()->id() }}">
              @error('owner_id') <div class="error">{{ $message }}</div> @enderror
              <div class="hint">Más adelante puedes cambiarlo por un selector de usuarios.</div>
            </div>

            <div class="row">
              <label for="due_at">Fecha límite estimada (SLA)</label>
              <input id="due_at" type="datetime-local" name="due_at" value="{{ old('due_at') }}">
              @error('due_at') <div class="error">{{ $message }}</div> @enderror
              <div class="chips" id="sla-shortcuts" style="margin-top:8px">
                <span class="chip" data-hours="24">+24h</span>
                <span class="chip" data-hours="48">+48h</span>
                <span class="chip" data-hours="72">+72h</span>
                <span class="chip" data-hours="168">+7d</span>
              </div>
              <div class="hint">Usa atajos para fijar rápidamente un SLA relativo desde ahora.</div>
            </div>
          </div>

          {{-- 4) (Opcional) Licitación y link --}}
          <div class="section-title">4) (Opcional) Datos de licitación y vínculo</div>
          <div class="grid">
            <div class="row">
              <label for="numero_licitacion">Número de licitación</label>
              <input id="numero_licitacion" type="text" name="numero_licitacion" value="{{ old('numero_licitacion') }}" placeholder="Ej. LA-012345-ABC-2025">
              @error('numero_licitacion') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="row">
              <label for="monto_propuesta">Monto de la propuesta</label>
              <input id="monto_propuesta" type="number" step="0.01" name="monto_propuesta" value="{{ old('monto_propuesta') }}" placeholder="0.00">
              @error('monto_propuesta') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="row">
              <label for="link_inicial">Link relacionado (Compranet, Drive, etc.)</label>
              <input id="link_inicial" type="url" name="link_inicial" value="{{ old('link_inicial') }}" placeholder="https://...">
              <div class="hint">Podrás agregar más enlaces dentro del ticket.</div>
            </div>

            <div class="row">
              <label for="nota">Notas rápidas (no se guardan)</label>
              <textarea id="nota" rows="3" placeholder="Contexto inicial, instrucciones, etc." spellcheck="false"></textarea>
              <div class="hint">Área temporal para copiar/pegar — no se guarda.</div>
            </div>
          </div>

          <div class="section" style="margin-top:12px">
            <span class="badge">🧭 El flujo por etapas se configurará automáticamente y podrás editarlo después.</span>
          </div>
        </div>

        <div class="footer">
          <button id="submitBtn2" type="submit" class="btn primary">
            <span class="save-label">Guardar ticket</span>
            <span class="save-spin" style="display:none;margin-left:6px">⏳</span>
          </button>
        </div>
      </form>

      {{-- ======== COLUMNA PREVIEW ======== --}}
      <aside class="aside">
        <div class="card">
          <div class="head">
            <div style="display:flex;flex-direction:column">
              <div class="h" style="font-size:1.05rem">Previsualización</div>
              <div class="hint">Resumen en vivo de lo que estás capturando</div>
            </div>
            <span class="pill">🗂️ Ticket</span>
          </div>
          <div class="body" style="display:flex;flex-direction:column;gap:12px">
            <div class="kv">
              <div class="k">Folio (estimado)</div><div class="v">Se asigna al guardar</div>
              <div class="k">Título</div><div class="v" id="pv-title">—</div>
              <div class="k">Cliente</div><div class="v" id="pv-client">—</div>
              <div class="k">Tipo</div><div class="v" id="pv-type">—</div>
              <div class="k">Prioridad</div><div class="v" id="pv-priority">—</div>
              <div class="k">Responsable</div><div class="v" id="pv-owner">ID {{ auth()->id() }}</div>
              <div class="k">SLA</div><div class="v" id="pv-due">—</div>
            </div>

            <div>
              <div class="hint" style="margin-bottom:6px">Carga de trabajo estimada</div>
              <div class="progress" aria-hidden="true"><span id="pv-progress" style="width:12%"></span></div>
              <div class="hint" id="pv-progress-label" style="margin-top:4px">Inicial • 12%</div>
            </div>

            <div class="section">
              <div class="section-title" style="margin:0 0 6px">Etapas por defecto</div>
              <ol id="pv-stages" style="margin:0 0 6px 1.2rem;padding:0;line-height:1.45">
                <li>Recepción de ticket</li>
                <li>Análisis técnico/comercial</li>
                <li>Cotización y envío</li>
                <li>Aprobación / Seguimiento</li>
                <li>Entrega / Cierre</li>
              </ol>
              <div class="hint">Podrás reordenar, asignar y usar IA para generar checklists por etapa.</div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

{{-- JS UX: chips ↔ select, contador, atajos SLA, preview, submit spinner, Ctrl/⌘+S --}}
<script>
(function(){
  const $ = (s,root=document) => root.querySelector(s);
  const $$ = (s,root=document) => Array.from(root.querySelectorAll(s));

  const form = $('#tktForm');
  const submitBtns = $$('#submitBtn, #submitBtn2');

  // 1) Chips que sincronizan con selects (tipo / prioridad)
  $$(".chips[data-sync-select]").forEach(group => {
    const sel = group.getAttribute('data-sync-select');
    const selectEl = document.querySelector(sel);

    const setActive = (val) => {
      $$(".chip", group).forEach(ch => ch.classList.toggle('active', ch.dataset.value === val));
      selectEl.value = val;
      selectEl.dispatchEvent(new Event('change', {bubbles:true}));
    };

    group.addEventListener('click', e => {
      const chip = e.target.closest('.chip');
      if (!chip) return;
      setActive(chip.dataset.value);
    });

    // Estado inicial
    setActive(selectEl.value || selectEl.options?.[0]?.value || '');
  });

  // 2) Contador de caracteres para Título + preview
  const title = $("#title"), counter = $("#title-count"), pvTitle = $("#pv-title");
  if (title && counter) {
    const update = () => {
      counter.textContent = `${title.value.length} / ${title.maxLength || 180}`;
      pvTitle.textContent = title.value.trim() || '—';
      // Edición sutil para progreso "estimado" en función de existencia de título
      progressTo(Math.min(12 + (title.value.trim() ? 8 : 0), 20), title.value.trim() ? 'Datos básicos • 20%' : 'Inicial • 12%');
    };
    title.addEventListener('input', update); update();
  }

  // 3) Preview de cliente, tipo, prioridad, responsable y SLA
  const pvClient = $("#pv-client");
  $("#client_name")?.addEventListener('input', e => { pvClient.textContent = e.target.value.trim() || '—'; });

  const pvType = $("#pv-type"), pvPriority = $("#pv-priority");
  $("#type")?.addEventListener('change', e => { pvType.textContent = e.target.options[e.target.selectedIndex]?.text || '—'; });
  $("#priority")?.addEventListener('change', e => { pvPriority.textContent = e.target.options[e.target.selectedIndex]?.text || '—'; });

  const pvOwner = $("#pv-owner");
  $("#owner_id")?.addEventListener('input', e => { pvOwner.textContent = "ID " + (e.target.value || "{{ auth()->id() }}"); });

  const due = $("#due_at"), pvDue = $("#pv-due");
  const fmtLocal = (dt) => {
    if (!dt) return '—';
    const d = new Date(dt);
    if (isNaN(d)) return '—';
    return d.toLocaleString();
  };
  due?.addEventListener('input', () => {
    pvDue.textContent = fmtLocal(due.value);
    due.value ? progressTo(28, 'Planificado • 28%') : progressTo(20, 'Datos básicos • 20%');
  });

  // 4) Atajos SLA (+24/+48/+72h +7d)
  $("#sla-shortcuts")?.addEventListener('click', e => {
    const chip = e.target.closest('.chip'); if (!chip || !due) return;
    const h = parseInt(chip.dataset.hours||"0",10);
    const now = new Date();
    now.setHours(now.getHours() + h);
    // Formato 'YYYY-MM-DDTHH:MM'
    const pad = n => String(n).padStart(2,'0');
    const v = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
    due.value = v;
    pvDue.textContent = fmtLocal(v);
    $$("#sla-shortcuts .chip").forEach(c=>c.classList.remove('active'));
    chip.classList.add('active');
    progressTo(28, 'Planificado • 28%');
  });

  // 5) Barra de progreso estimada en preview
  const pvBar = $("#pv-progress"), pvLabel = $("#pv-progress-label");
  function progressTo(pct, label){
    if (!pvBar || !pvLabel) return;
    pvBar.style.width = Math.max(12, Math.min(60, pct)) + "%";
    pvLabel.textContent = label;
  }

  // 6) Submit spinner + bloqueo doble click
  function setBusy(busy){
    submitBtns.forEach(btn=>{
      btn.setAttribute('aria-busy', busy ? 'true':'false');
      const spin = btn.querySelector('.save-spin');
      const lbl  = btn.querySelector('.save-label');
      if (spin) spin.style.display = busy ? '' : 'none';
      if (lbl)  lbl.textContent = busy ? 'Guardando...' : 'Guardar ticket';
    });
  }
  form?.addEventListener('submit', ()=> setBusy(true));

  // 7) Atajo Ctrl/⌘+S para enviar
  document.addEventListener('keydown', (e)=>{
    const isMac = navigator.platform.toUpperCase().indexOf('MAC')>=0;
    if ((isMac && e.metaKey && e.key.toLowerCase()==='s') || (!isMac && e.ctrlKey && e.key.toLowerCase()==='s')) {
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
