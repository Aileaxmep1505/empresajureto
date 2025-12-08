{{-- resources/views/tickets/show.blade.php --}}
@extends('layouts.app')
@section('title', $ticket->folio)

@section('content')
@php
    // Fases disponibles
    $phaseOptions = [
        'analisis_bases' => 'Análisis de bases',
        'preguntas'      => 'Preguntas / aclaraciones',
        'cotizacion'     => 'Cotización',
        'muestras'       => 'Muestras',
        'ir_por_pedido'  => 'Ir por pedido',
        'entrega'        => 'Entrega',
        'seguimiento'    => 'Seguimiento / otros',
    ];

    // Etapas sugeridas según fase
    $phaseStageTemplates = [
        'analisis_bases' => [
            'Descarga y organización de bases',
            'Revisión de requisitos legales',
            'Revisión de requisitos técnicos',
            'Revisión de anexos y formatos',
            'Detección de riesgos y restricciones',
        ],
        'preguntas' => [
            'Detección de dudas y temas críticos',
            'Redacción de preguntas por área',
            'Revisión interna de preguntas',
            'Carga / envío de preguntas en plataforma',
            'Seguimiento a respuestas de la convocante',
        ],
        'cotizacion' => [
            'Definición de alcances y cantidades',
            'Armado de costos por partida',
            'Revisión de márgenes y topes',
            'Validación contra bases y anexos',
            'Integración de propuesta económica',
            'Revisión general y autorización',
        ],
        'muestras' => [
            'Definición de muestras requeridas',
            'Localización / compra de muestras',
            'Preparación de etiquetas y documentación',
            'Entrega de muestras en sede',
            'Seguimiento de veredicto de muestras',
        ],
        'ir_por_pedido' => [
            'Validación de pedido adjudicado',
            'Coordinación con almacén / proveedor',
            'Programación de recolección',
            'Verificación de cantidades y estado',
        ],
        'entrega' => [
            'Planificación de ruta y horarios',
            'Entrega en sitio / formalización',
            'Firma de actas y evidencias',
            'Cierre de entrega en sistema',
        ],
        'seguimiento' => [
            'Seguimiento a pagos',
            'Atención a garantías / incidencias',
            'Cierre administrativo',
        ],
    ];

    $phaseKey   = $ticket->licitacion_phase ?? 'analisis_bases';
    $phaseLabel = $phaseOptions[$phaseKey] ?? 'Sin fase asignada';
    $suggestedStages = $phaseStageTemplates[$phaseKey] ?? [];

    $priorityClass = match ($ticket->priority) {
        'alta'  => 'tag-prio-alta',
        'media' => 'tag-prio-media',
        'baja'  => 'tag-prio-baja',
        default => 'tag-prio-neutral',
    };
@endphp

<div id="tktshow" class="container-fluid p-0">
  <style>
    #tktshow{
      --ink:#0f172a;
      --muted:#6b7280;
      --line:#e2e8f0;
      --bg:#f8fafc;
      --card:#ffffff;

      --accent-soft:#e0edff;
      --accent-border:#c7d2fe;
      --accent-ink:#1d4ed8;

      --ok:#16a34a;
      --warn:#f59e0b;
      --danger:#ef4444;

      --ring:0 0 0 4px rgba(129,140,248,.18);
      --shadow:0 18px 40px rgba(15,23,42,.08);
      --radius:16px;

      color:var(--ink);
      background:radial-gradient(circle at top left,#e0ecff 0,#f8fafc 45%,#ffffff 100%);
      font-synthesis-weight:none;
    }
    #tktshow *{box-sizing:border-box}

    #tktshow .wrap{
      max-width:1200px;
      margin:clamp(16px,3vw,28px) auto;
      padding:0 16px 32px;
    }

    #tktshow .grid{
      display:grid;
      grid-template-columns:1.4fr .9fr;
      gap:18px;
      align-items:flex-start;
      margin-top:14px;
    }

    #tktshow .card{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
      animation:fadeIn .45s ease-out both;
    }

    #tktshow .head-main{
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:16px;
      padding:16px 18px;
      border-radius:var(--radius);
      background:linear-gradient(135deg,#f3f6ff,#ffffff);
      border:1px solid var(--accent-border);
      box-shadow:0 18px 40px rgba(15,23,42,.10);
    }

    #tktshow .body{padding:16px 18px 18px;}

    #tktshow .h{
      margin:0;
      font-weight:800;
      color:var(--ink);
      letter-spacing:.03em;
      font-size:1.25rem;
    }
    #tktshow .sub{
      margin-top:4px;
      font-size:.88rem;
      color:var(--muted);
    }

    #tktshow .pill-row{
      display:flex;
      flex-wrap:wrap;
      gap:6px 10px;
      margin-top:6px;
      font-size:.8rem;
    }

    #tktshow .pill{
      padding:.26rem .7rem;
      border-radius:999px;
      border:1px solid var(--line);
      background:#ffffff;
      font-size:.78rem;
      color:var(--muted);
      white-space:nowrap;
    }
    #tktshow .pill-strong{
      border-color:var(--accent-border);
      background:var(--accent-soft);
      color:var(--accent-ink);
      font-weight:600;
    }

    /* Prioridad */
    #tktshow .tag-prio{
      padding:.25rem .7rem;
      border-radius:999px;
      font-size:.78rem;
      font-weight:600;
      border:1px solid transparent;
    }
    #tktshow .tag-prio-alta{
      background:#fee2e2;
      border-color:#fecaca;
      color:#b91c1c;
    }
    #tktshow .tag-prio-media{
      background:#fef9c3;
      border-color:#facc15;
      color:#92400e;
    }
    #tktshow .tag-prio-baja{
      background:#dcfce7;
      border-color:#bbf7d0;
      color:#166534;
    }
    #tktshow .tag-prio-neutral{
      background:#e5e7eb;
      border-color:#d1d5db;
      color:#374151;
    }

    /* Formulario cabecera */
    #tktshow .head-form{
      display:flex;
      flex-direction:column;
      gap:8px;
      min-width:260px;
    }
    #tktshow .row-inline{
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      align-items:center;
    }

    #tktshow input[type="text"],
    #tktshow input[type="url"],
    #tktshow input[type="number"],
    #tktshow input[type="datetime-local"],
    #tktshow select,
    #tktshow textarea{
      width:100%;
      border:1px solid var(--line);
      border-radius:12px;
      padding:.6rem .75rem;
      outline:none;
      background:#ffffff;
      font-size:.88rem;
      transition:
        border-color .18s ease,
        box-shadow .18s ease,
        background .15s ease,
        transform .08s ease;
    }
    #tktshow input::placeholder,
    #tktshow textarea::placeholder{
      color:#9ca3af;
    }
    #tktshow input:focus,
    #tktshow select:focus,
    #tktshow textarea:focus{
      border-color:var(--accent-border);
      box-shadow:var(--ring);
      background:#f9fbff;
      transform:translateY(-1px);
    }
    #tktshow textarea{resize:vertical;min-height:90px;}

    #tktshow label{
      font-size:.8rem;
      font-weight:600;
      color:var(--muted);
    }

    /* Buttons */
    #tktshow .btn{
      appearance:none;
      border-radius:999px;
      padding:.55rem 1.05rem;
      font-weight:600;
      font-size:.86rem;
      cursor:pointer;
      border:1px solid #d4ddff;
      background:linear-gradient(120deg,#ffffff,#f4f7ff);
      transition:
        transform .12s ease,
        box-shadow .15s ease,
        opacity .12s ease,
        background-position .2s ease;
      background-size:220% 220%;
      background-position:0 0;
      display:inline-flex;
      align-items:center;
      gap:6px;
      color:var(--ink);
      white-space:nowrap;
    }
    #tktshow .btn:hover{
      transform:translateY(-1px);
      box-shadow:0 12px 28px rgba(15,23,42,.12);
      background-position:100% 0;
    }
    #tktshow .btn:active{
      transform:translateY(0);
      box-shadow:0 6px 14px rgba(15,23,42,.10);
    }
    #tktshow .btn.primary{
      border-color:var(--accent-border);
      background-image:linear-gradient(120deg,#e0edff,#f5f7ff);
      color:var(--accent-ink);
    }
    #tktshow .btn[disabled]{opacity:.7;cursor:not-allowed;}

    /* Etapas */
    #tktshow .section-title{
      font-size:.92rem;
      font-weight:700;
      margin:0 0 8px;
      color:var(--ink);
    }
    #tktshow .stage{
      border:1px dashed var(--line);
      border-radius:12px;
      padding:10px 12px;
      margin-bottom:10px;
      display:flex;
      justify-content:space-between;
      align-items:flex-start;
      gap:10px;
      background:#ffffff;
    }
    #tktshow .stage-main{
      flex:1;
      min-width:0;
    }
    #tktshow .stage-meta{
      font-size:.8rem;
      color:var(--muted);
      margin-top:2px;
    }
    #tktshow .stage-actions{
      display:flex;
      flex-direction:column;
      gap:6px;
      align-items:flex-end;
    }

    #tktshow .chip{
      padding:.3rem .7rem;
      border-radius:999px;
      border:1px solid var(--line);
      background:#ffffff;
      font-size:.78rem;
      cursor:pointer;
      text-decoration:none;
      display:inline-flex;
      align-items:center;
      gap:4px;
    }
    #tktshow .chip-button{
      background:#f9fafb;
    }

    #tktshow .mini{font-size:.8rem;color:var(--muted);}
    #tktshow .mt-sm{margin-top:8px;}
    #tktshow .mt-md{margin-top:12px;}
    #tktshow .divider{
      margin:14px 0;
      border:none;
      border-top:1px solid var(--line);
    }

    #tktshow ul{padding-left:1.1rem;margin:0;}
    #tktshow li{margin-bottom:4px;}

    /* Animación */
    @keyframes fadeIn{
      from{opacity:0;transform:translateY(12px) scale(.98);}
      to{opacity:1;transform:translateY(0) scale(1);}
    }

    /* Responsive */
    @media (max-width:1000px){
      #tktshow .grid{grid-template-columns:1fr;}
    }
    @media (max-width:768px){
      #tktshow .head-main{flex-direction:column;align-items:flex-start;}
      #tktshow .head-form{width:100%;}
    }
  </style>

  <div class="wrap">
    {{-- CABECERA --}}
    <div class="head-main">
      <div>
        <div class="h">{{ $ticket->folio }}</div>
        <p class="sub">
          Ticket de licitación pública ·
          Cliente:
          <strong>
            {{ $ticket->client_name ?? ($ticket->client->name ?? 'Sin cliente') }}
          </strong>
        </p>

        <div class="pill-row">
          <span class="pill pill-strong">{{ $phaseLabel }}</span>
          <span class="tag-prio {{ $priorityClass }}">
            Prioridad: {{ ucfirst($ticket->priority ?? '—') }}
          </span>
          <span class="pill">
            Estado: <strong>{{ ucfirst($ticket->status) }}</strong>
          </span>
          <span class="pill">
            Responsable:
            <strong>
              @if(method_exists($ticket, 'owner') && $ticket->owner)
                {{ $ticket->owner->name }}
              @elseif(isset($users))
                {{ optional($users->firstWhere('id',$ticket->owner_id))->name ?? 'Sin asignar' }}
              @else
                {{ $ticket->owner_id ? 'ID '.$ticket->owner_id : 'Sin asignar' }}
              @endif
            </strong>
          </span>
          <span class="pill">
            Fecha límite:
            <strong>{{ optional($ticket->due_at)->format('d/m/Y H:i') ?? 'Sin definir' }}</strong>
          </span>
        </div>
      </div>

      {{-- Edición rápida --}}
      <form class="head-form" method="POST" action="{{ route('tickets.update',$ticket) }}">
        @csrf
        @method('PUT')

        <div>
          <label for="title">Título</label>
          <input
            id="title"
            type="text"
            name="title"
            value="{{ old('title',$ticket->title) }}"
            placeholder="Título o asunto del ticket"
          >
        </div>

        <div class="row-inline">
          <div style="flex:1;min-width:130px;">
            <label for="priority">Prioridad</label>
            <select id="priority" name="priority">
              @foreach(['alta'=>'Alta','media'=>'Media','baja'=>'Baja'] as $v=>$lbl)
                <option value="{{ $v }}" @selected($ticket->priority===$v)>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>

          <div style="flex:1;min-width:130px;">
            <label for="status">Estado</label>
            <select id="status" name="status">
              @foreach(['revision'=>'Revisión','proceso'=>'En proceso','finalizado'=>'Finalizado','cerrado'=>'Cerrado'] as $v=>$lbl)
                <option value="{{ $v }}" @selected($ticket->status===$v)>{{ $lbl }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row-inline">
          <div style="flex:1;min-width:130px;">
            <label for="licitacion_phase">Fase de licitación</label>
            <select id="licitacion_phase" name="licitacion_phase">
              @foreach($phaseOptions as $key=>$label)
                <option value="{{ $key }}" @selected($ticket->licitacion_phase===$key)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>

          <div style="flex:1;min-width:160px;">
            <label for="owner_id">Responsable</label>
            @if(isset($users) && count($users))
              <select id="owner_id" name="owner_id">
                <option value="">Sin asignar</option>
                @foreach($users as $user)
                  <option value="{{ $user->id }}" @selected($ticket->owner_id === $user->id)>
                    {{ $user->name }}
                  </option>
                @endforeach
              </select>
            @else
              <input
                id="owner_id"
                type="number"
                name="owner_id"
                value={{ old('owner_id',$ticket->owner_id) }}
                placeholder="ID de usuario"
              >
            @endif
          </div>
        </div>

        <div class="row-inline">
          <div style="flex:1;min-width:170px;">
            <label for="due_at">Fecha límite</label>
            <input
              id="due_at"
              type="datetime-local"
              name="due_at"
              value="{{ old('due_at', optional($ticket->due_at)->format('Y-m-d\TH:i')) }}"
            >
          </div>
          <div style="align-self:flex-end;">
            <button class="btn primary" type="submit">Guardar cambios</button>
          </div>
        </div>
      </form>
    </div>

    <div class="grid">
      {{-- IZQUIERDA: Etapas y plantillas por fase --}}
      <div class="card">
        <div class="body">
          <div class="section-title">Etapas del ticket</div>

          {{-- Sugerencias según fase --}}
          @if(count($suggestedStages))
            <div class="mini">
              Sugerencias de etapas para esta fase. Puedes agregarlas con un clic.
            </div>
            <div class="mt-sm" style="display:flex;flex-wrap:wrap;gap:6px;">
              @foreach($suggestedStages as $name)
                <form
                  method="POST"
                  action="{{ route('tickets.stages.store',$ticket) }}"
                  style="display:inline"
                >
                  @csrf
                  <input type="hidden" name="name" value="{{ $name }}">
                  <button type="submit" class="chip chip-button">
                    + {{ $name }}
                  </button>
                </form>
              @endforeach
            </div>
            <hr class="divider">
          @endif

          {{-- Etapas creadas --}}
          @forelse($ticket->stages as $st)
            <div class="stage" id="stage-{{ $st->id }}">
              <div class="stage-main">
                <div class="section-title" style="margin-bottom:2px;">
                  {{ $st->position }}. {{ $st->name }}
                </div>
                <div class="stage-meta">
                  Estado:
                  <strong>{{ ucfirst(str_replace('_',' ',$st->status)) }}</strong>
                  · Responsable:
                  <strong>{{ optional($st->assignee)->name ?? 'Sin asignar' }}</strong>
                </div>

                @foreach($st->checklists as $chk)
                  <div class="mt-sm mini">
                    <span class="chip">{{ $chk->title }}</span>
                    <a class="chip" href="{{ route('checklists.export.pdf',$chk) }}">PDF</a>
                    <a class="chip" href="{{ route('checklists.export.word',$chk) }}">Word</a>
                    <form
                      method="POST"
                      action="{{ route('checklists.destroy',$chk) }}"
                      onsubmit="return confirm('¿Eliminar esta checklist?')"
                      style="display:inline"
                    >
                      @csrf
                      @method('DELETE')
                      <button class="chip chip-button" type="submit">Eliminar</button>
                    </form>
                  </div>
                @endforeach

                {{-- Nueva checklist para la etapa --}}
                <form
                  class="mt-sm"
                  method="POST"
                  action="{{ route('tickets.checklists.store',$ticket) }}"
                >
                  @csrf
                  <input type="hidden" name="stage_id" value="{{ $st->id }}">
                  <div class="row-inline">
                    <input
                      type="text"
                      name="title"
                      placeholder="Checklist para esta etapa"
                    >
                    <button class="chip chip-button" type="submit">
                      Agregar checklist
                    </button>
                  </div>
                </form>
              </div>

              <div class="stage-actions">
                <form
                  method="POST"
                  action="{{ route('tickets.stages.destroy',[$ticket,$st]) }}"
                  onsubmit="return confirm('¿Eliminar la etapa \"{{ $st->name }}\"? Se borrarán sus checklists, items y evidencias.')"
                >
                  @csrf
                  @method('DELETE')
                  <button class="btn" type="submit">Eliminar etapa</button>
                </form>
              </div>
            </div>
          @empty
            <p class="mini">Sin etapas configuradas. Usa las sugerencias de la fase o crea una etapa nueva.</p>
          @endforelse

          {{-- Nueva etapa manual --}}
          <form class="mt-md" method="POST" action="{{ route('tickets.stages.store',$ticket) }}">
            @csrf
            <div class="row-inline">
              <input
                type="text"
                name="name"
                placeholder="Nueva etapa (por ejemplo, Post-venta)"
                required
              />
              <button class="btn" type="submit">Agregar etapa</button>
            </div>
          </form>
        </div>
      </div>

      {{-- DERECHA: IA + datos de licitación + documentos --}}
      <div class="card">
        <div class="body">
          {{-- Asistente de checklist IA --}}
          <div class="section-title">Asistente para checklist</div>
          <div class="mini" id="ai-phase-hint">
            Genera una checklist enfocada a la fase actual de la licitación.
          </div>

          <div class="row-inline mt-sm">
            <div style="flex:1;min-width:140px;">
              <label for="ai-stage">Etapa</label>
              <select id="ai-stage">
                @foreach($ticket->stages as $st)
                  <option value="{{ $st->id }}">
                    {{ $st->position }}. {{ $st->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="mt-sm">
            <label for="ai-prompt">Descripción breve</label>
            <textarea
              id="ai-prompt"
              rows="4"
              placeholder="Describe qué debe lograrse en esta etapa."
            ></textarea>
          </div>

          <div class="row-inline" style="justify-content:flex-end;margin-top:8px;">
            <button class="btn primary" type="button" id="btnSuggest">
              Sugerir con IA
            </button>
          </div>

          <div id="ai-result" style="display:none;margin-top:10px;">
            <div class="section-title" id="ai-title" style="margin-bottom:2px;">
              Checklist sugerida
            </div>
            <div class="mini" id="ai-instructions"></div>
            <div id="ai-items" class="mt-sm" style="display:flex;flex-direction:column;gap:8px;"></div>
            <div class="row-inline" style="justify-content:space-between;margin-top:8px;">
              <span class="mini">Puedes editar los puntos antes de guardar.</span>
              <button class="btn primary" type="button" id="btnCreate">
                Crear checklist en la etapa
              </button>
            </div>
          </div>

          <hr class="divider">

          {{-- Datos de licitación y notas --}}
          <div class="section-title">Datos de la licitación</div>
          <form method="POST" action="{{ route('tickets.update',$ticket) }}">
            @csrf
            @method('PUT')

            <div class="mt-sm">
              <label for="numero_licitacion">Número de licitación</label>
              <input
                id="numero_licitacion"
                type="text"
                name="numero_licitacion"
                value="{{ old('numero_licitacion',$ticket->numero_licitacion) }}"
                placeholder="Ej. LA-012345-ABC-2025"
              >
            </div>

            <div class="mt-sm">
              <label for="monto_propuesta">Monto de la propuesta</label>
              <input
                id="monto_propuesta"
                type="number"
                step="0.01"
                name="monto_propuesta"
                value="{{ old('monto_propuesta',$ticket->monto_propuesta) }}"
                placeholder="0.00"
              >
            </div>

            <div class="mt-sm">
              <label for="estatus_adjudicacion">Estatus de adjudicación</label>
              <select id="estatus_adjudicacion" name="estatus_adjudicacion">
                <option value="">Sin definir</option>
                @foreach(['en_espera'=>'En espera','ganada'=>'Ganada','perdida'=>'Perdida'] as $val=>$lbl)
                  <option value="{{ $val }}" @selected($ticket->estatus_adjudicacion === $val)>
                    {{ $lbl }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mt-sm">
              <label for="quick_notes">Notas rápidas</label>
              <textarea
                id="quick_notes"
                name="quick_notes"
                rows="3"
                placeholder="Puntos clave, recordatorios o acuerdos importantes."
              >{{ old('quick_notes',$ticket->quick_notes) }}</textarea>
            </div>

            <div class="row-inline" style="justify-content:flex-end;margin-top:10px;">
              <button class="btn primary" type="submit">Guardar datos</button>
            </div>
          </form>

          @if($ticket->links->count())
            <div class="mt-md">
              <div class="section-title" style="margin-bottom:6px;">Enlaces relacionados</div>
              <ul>
                @foreach($ticket->links as $lnk)
                  <li class="mini">
                    <a href="{{ $lnk->url }}" target="_blank">{{ $lnk->label }}</a>
                  </li>
                @endforeach
              </ul>
            </div>
          @endif

          <hr class="divider">

          {{-- Documentos --}}
          <div class="section-title">Documentos del ticket</div>

          <form
            method="POST"
            action="{{ route('tickets.documents.store',$ticket) }}"
            enctype="multipart/form-data"
          >
            @csrf
            <div class="row-inline mt-sm">
              <input
                type="text"
                name="name"
                placeholder="Nombre del documento"
                style="flex:1;min-width:140px;"
              >
              <input
                type="text"
                name="category"
                placeholder="Categoría (propuesta, evidencia...)"
                style="flex:1;min-width:140px;"
              >
            </div>
            <div class="row-inline mt-sm">
              <input
                type="file"
                name="file"
                style="flex:1;min-width:180px;"
              >
              <input
                type="url"
                name="external_url"
                placeholder="o URL externa (Drive, etc.)"
                style="flex:1;min-width:180px;"
              >
              <button class="btn" type="submit">Subir</button>
            </div>
          </form>

          @if($ticket->documents->count())
            <ul class="mt-md">
              @foreach($ticket->documents as $d)
                <li class="mini" id="doc-{{ $d->id }}">
                  <strong>{{ $d->name }}</strong>
                  v{{ $d->version }}
                  <span class="mini">
                    ({{ $d->category ?? 'Sin categoría' }})
                  </span>
                  @if($d->path)
                    · <a href="{{ route('tickets.documents.download',[$ticket,$d]) }}">Descargar</a>
                  @endif
                  @if($d->external_url)
                    · <a href="{{ $d->external_url }}" target="_blank">Enlace</a>
                  @endif
                  <form
                    method="POST"
                    action="{{ route('tickets.documents.destroy',[$ticket,$d]) }}"
                    style="display:inline"
                    onsubmit="return confirm('¿Eliminar este documento?')"
                  >
                    @csrf
                    @method('DELETE')
                    · <button class="chip chip-button" type="submit">Eliminar</button>
                  </form>
                </li>
              @endforeach
            </ul>
          @else
            <p class="mini mt-sm">Aún no hay documentos cargados.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- IA: JS sin dependencias, ajustado por fase --}}
<script>
  const CSRF = "{{ csrf_token() }}";
  const suggestRouteTmpl = @json(route('tickets.ai.suggest', ['ticket'=>$ticket->id,'stage'=>'__STAGE__']));
  const AI_CREATE_URL    = @json(route('tickets.ai.create', ['ticket'=>$ticket->id]));

  let AI_CACHE = { title: '', instructions: '', items: [] };

  const PHASE_PROMPTS = {
    analisis_bases: {
      placeholder: 'Ejemplo: revisar requisitos legales, técnicos, anexos y detectar riesgos o puntos críticos.',
      hint: 'Enfocado al análisis de bases: requisitos legales, técnicos, anexos y riesgos.'
    },
    preguntas: {
      placeholder: 'Ejemplo: listar dudas por partida, consolidar preguntas, revisar internamente y cargarlas en la plataforma.',
      hint: 'Enfocado a preparar, revisar y enviar preguntas de aclaración.'
    },
    cotizacion: {
      placeholder: 'Ejemplo: armar costos por partida, revisar márgenes, validar contra bases y preparar propuesta económica.',
      hint: 'Enfocado al armado y revisión de la propuesta económica.'
    },
    muestras: {
      placeholder: 'Ejemplo: identificar muestras requeridas, prepararlas, etiquetarlas y coordinar la entrega.',
      hint: 'Enfocado a preparación y entrega de muestras.'
    },
    ir_por_pedido: {
      placeholder: 'Ejemplo: validar pedido adjudicado, coordinar con almacén y programar la recolección.',
      hint: 'Enfocado a coordinación para ir por el pedido.'
    },
    entrega: {
      placeholder: 'Ejemplo: planear ruta, coordinar horarios, entregar y recabar firmas / evidencias.',
      hint: 'Enfocado a la entrega final y cierre operativo.'
    },
    seguimiento: {
      placeholder: 'Ejemplo: dar seguimiento a pagos, garantías, incidencias y cierre administrativo.',
      hint: 'Enfocado a pagos, garantías y cierre administrativo.'
    },
    _default: {
      placeholder: 'Describe qué debe lograrse en esta etapa.',
      hint: 'Genera una checklist enfocada a la fase actual de la licitación.'
    }
  };

  const $btnSuggest = document.getElementById('btnSuggest');
  const $btnCreate  = document.getElementById('btnCreate');

  function syncAiHelper() {
    const phaseSel = document.getElementById('licitacion_phase');
    const phase    = phaseSel ? phaseSel.value : '';
    const cfg      = PHASE_PROMPTS[phase] || PHASE_PROMPTS._default;

    const prompt   = document.getElementById('ai-prompt');
    const hintNode = document.getElementById('ai-phase-hint');

    if (prompt && !prompt.value) {
      prompt.placeholder = cfg.placeholder;
    }
    if (hintNode) {
      hintNode.textContent = cfg.hint;
    }
  }

  document.getElementById('licitacion_phase')?.addEventListener('change', syncAiHelper);
  document.addEventListener('DOMContentLoaded', syncAiHelper);

  $btnSuggest?.addEventListener('click', async () => {
    const stageId = document.getElementById('ai-stage')?.value;
    const prompt  = (document.getElementById('ai-prompt')?.value || '').trim();
    if (!stageId) { alert('Selecciona una etapa.'); return; }
    if (!prompt)  { alert('Escribe una descripción breve.'); return; }

    const url = suggestRouteTmpl.replace('__STAGE__', stageId);
    $btnSuggest.disabled = true;
    $btnSuggest.textContent = 'Generando…';

    try {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ prompt })
      });

      const j = await res.json();
      if (!res.ok || !j.ok) {
        throw new Error(j.message || 'No se pudo generar la checklist.');
      }

      AI_CACHE.title        = j.title || 'Checklist sugerida';
      AI_CACHE.instructions = j.instructions || '';
      AI_CACHE.items        = Array.isArray(j.items) ? j.items : [];

      renderAiPreview();
    } catch (e) {
      alert(e.message || 'Error al generar la checklist.');
    } finally {
      $btnSuggest.disabled = false;
      $btnSuggest.textContent = 'Sugerir con IA';
    }
  });

  $btnCreate?.addEventListener('click', async () => {
    const stageId = document.getElementById('ai-stage')?.value;
    const items = (AI_CACHE.items || [])
      .map(s => (s || '').trim())
      .filter(Boolean);

    if (!stageId) { alert('Selecciona una etapa.'); return; }
    if (items.length < 1) { alert('Agrega al menos un punto.'); return; }

    $btnCreate.disabled = true;
    $btnCreate.textContent = 'Creando…';

    try {
      const fd = new FormData();
      fd.append('stage_id', stageId);
      fd.append('title', AI_CACHE.title || 'Checklist IA');
      items.forEach(it => fd.append('items[]', it));

      const res = await fetch(AI_CREATE_URL, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': CSRF,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: fd
      });

      const j = await res.json();
      if (!res.ok || !j.ok) {
        throw new Error(j.message || 'No se pudo crear la checklist.');
      }

      alert('Checklist creada en la etapa.');
      location.reload();
    } catch (e) {
      alert(e.message || 'Error al crear la checklist.');
    } finally {
      $btnCreate.disabled = false;
      $btnCreate.textContent = 'Crear checklist en la etapa';
    }
  });

  function renderAiPreview() {
    const box   = document.getElementById('ai-result');
    const title = document.getElementById('ai-title');
    const inst  = document.getElementById('ai-instructions');
    const list  = document.getElementById('ai-items');

    if (!box || !title || !inst || !list) return;

    box.style.display = 'block';
    title.textContent = AI_CACHE.title || 'Checklist sugerida';
    inst.textContent  = AI_CACHE.instructions || '';

    list.innerHTML = '';

    if (!AI_CACHE.items.length) {
      list.innerHTML = '<div class="mini">Ajusta la descripción y vuelve a intentar.</div>';
      return;
    }

    AI_CACHE.items.forEach((text, index) => {
      const row = document.createElement('div');
      row.style.display = 'grid';
      row.style.gridTemplateColumns = '1fr auto';
      row.style.gap = '8px';

      const input = document.createElement('input');
      input.type = 'text';
      input.value = text;
      input.maxLength = 500;
      input.oninput = () => { AI_CACHE.items[index] = input.value; };

      const del = document.createElement('button');
      del.type = 'button';
      del.className = 'chip chip-button';
      del.textContent = 'Eliminar';
      del.onclick = () => {
        AI_CACHE.items.splice(index, 1);
        renderAiPreview();
      };

      row.appendChild(input);
      row.appendChild(del);
      list.appendChild(row);
    });

    const add = document.createElement('button');
    add.type = 'button';
    add.className = 'chip chip-button';
    add.textContent = 'Agregar punto';
    add.onclick = () => {
      AI_CACHE.items.push('');
      renderAiPreview();
    };

    list.appendChild(add);
  }
</script>
@endsection
