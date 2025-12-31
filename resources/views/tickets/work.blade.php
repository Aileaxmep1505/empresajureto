{{-- resources/views/tickets/work.blade.php --}}
@extends('layouts.app')

@section('title', ($ticket->folio ?? 'Ticket') . ' · Trabajo')

@section('content')
@php
  use App\Models\TicketChecklist;

  $progress = (int) ($ticket->progress ?? 0);

  // Helper local: asegura colección
  $asCollection = function($value){
    if ($value instanceof \Illuminate\Support\Collection) return $value;
    if (is_array($value)) return collect($value);
    if (is_null($value)) return collect();
    return collect($value); // por si viene iterable
  };

  // ✅ Checklists generales: ticket_id pero SIN stage_id (muy común que se "pierdan" así)
  $generalChecklists = TicketChecklist::query()
    ->where('ticket_id', $ticket->id)
    ->whereNull('stage_id')
    ->with(['items' => fn($q) => $q->orderBy('position')])
    ->orderBy('id')
    ->get();
@endphp

<div id="tktwork" class="container-fluid p-0">
  <style>
    #tktwork{
      --ink:#0f172a;
      --muted:#6b7280;
      --line:#e2e8f0;
      --bg:#f8fafc;
      --card:#ffffff;

      --accent:#4f46e5;
      --accent-soft:#eef2ff;
      --accent-border:#c7d2fe;
      --accent-ink:#1d4ed8;

      --ok:#16a34a;
      --warn:#f59e0b;
      --danger:#ef4444;

      --radius:16px;
      --shadow:0 18px 40px rgba(15,23,42,.08);
      --ring:0 0 0 4px rgba(129,140,248,.18);

      color:var(--ink);
      background:radial-gradient(circle at top left,#e0ecff 0,#f8fafc 45%,#ffffff 100%);
      font-synthesis-weight:none;
    }
    #tktwork *{box-sizing:border-box;}

    #tktwork .wrap{
      max-width:1180px;
      margin:clamp(16px,3vw,28px) auto;
      padding:0 16px 32px;
    }

    /* HEADER */
    #tktwork .head{
      margin-bottom:18px;
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      align-items:flex-start;
      justify-content:space-between;
    }
    #tktwork .head-main h1{
      margin:0;
      font-size:1.3rem;
      letter-spacing:.03em;
      font-weight:800;
    }
    #tktwork .head-main p{
      margin:4px 0 0;
      font-size:.86rem;
      color:var(--muted);
    }
    #tktwork .head-badges{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      margin-top:8px;
    }
    #tktwork .pill{
      padding:.25rem .7rem;
      border-radius:999px;
      border:1px solid var(--line);
      background:#ffffff;
      font-size:.78rem;
      color:var(--muted);
      white-space:nowrap;
    }
    #tktwork .pill-strong{
      background:var(--accent-soft);
      border-color:var(--accent-border);
      color:var(--accent-ink);
      font-weight:600;
    }
    #tktwork .tag-prio{
      padding:.25rem .7rem;
      border-radius:999px;
      font-size:.78rem;
      font-weight:600;
      border:1px solid transparent;
    }
    #tktwork .tag-prio-alta{ background:#fee2e2;border-color:#fecaca;color:#b91c1c; }
    #tktwork .tag-prio-media{ background:#fef9c3;border-color:#facc15;color:#92400e; }
    #tktwork .tag-prio-baja{ background:#dcfce7;border-color:#bbf7d0;color:#166534; }
    #tktwork .tag-prio-neutral{ background:#e5e7eb;border-color:#d1d5db;color:#374151; }

    /* PROGRESO GLOBAL */
    #tktwork .progress-card{
      background:var(--card);
      border-radius:var(--radius);
      border:1px solid var(--line);
      box-shadow:var(--shadow);
      padding:14px 16px;
      display:flex;
      flex-wrap:wrap;
      gap:10px 20px;
      align-items:center;
      margin-bottom:18px;
    }
    #tktwork .progress-title{ font-size:.9rem; font-weight:600; }
    #tktwork .progress-sub{ font-size:.78rem; color:var(--muted); margin-top:2px; }
    #tktwork .progress-bar-wrap{ flex:1; min-width:180px; }
    #tktwork .progress-bar-outer{
      width:100%;
      height:8px;
      border-radius:999px;
      background:#e5e7eb;
      overflow:hidden;
    }
    #tktwork .progress-bar-inner{
      height:100%;
      border-radius:999px;
      background:linear-gradient(90deg,#4f46e5,#22c55e);
      width:0%;
      transition:width .35s cubic-bezier(.22,1,.36,1);
    }
    #tktwork .progress-badge{
      font-size:.8rem;
      font-weight:600;
      padding:.3rem .7rem;
      border-radius:999px;
      background:#ecfdf3;
      border:1px solid #bbf7d0;
      color:#166534;
    }

    /* LAYOUT PRINCIPAL */
    #tktwork .grid{
      display:grid;
      grid-template-columns:1.1fr .9fr;
      gap:16px;
      align-items:flex-start;
    }
    #tktwork .card{
      background:var(--card);
      border-radius:var(--radius);
      border:1px solid var(--line);
      box-shadow:var(--shadow);
      padding:14px 16px 16px;
      animation:fadeUp .4s ease-out both;
    }
    #tktwork .card-title{
      font-size:.95rem;
      font-weight:700;
      margin:0 0 6px;
    }
    #tktwork .card-sub{
      font-size:.78rem;
      color:var(--muted);
      margin-bottom:10px;
    }

    /* ETAPAS */
    #tktwork .stage{
      border-radius:14px;
      border:1px dashed #d1d5db;
      padding:10px 10px 12px;
      margin-bottom:10px;
      background:#fafafa;
      position:relative;
      overflow:hidden;
    }
    #tktwork .stage.is-current{
      background:linear-gradient(135deg,#eef2ff,#ffffff);
      border-style:solid;
      border-color:var(--accent-border);
      box-shadow:0 10px 30px rgba(79,70,229,0.18);
    }
    #tktwork .stage-header{
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:flex-start;
      margin-bottom:6px;
    }
    #tktwork .stage-name{
      font-size:.9rem;
      font-weight:600;
    }
    #tktwork .stage-meta{
      font-size:.75rem;
      color:var(--muted);
    }
    #tktwork .stage-progress{
      margin:4px 0 6px;
      display:flex;
      align-items:center;
      gap:8px;
      font-size:.75rem;
      color:var(--muted);
    }
    #tktwork .stage-progress-bar{
      flex:1;
      height:5px;
      border-radius:999px;
      background:#e5e7eb;
      overflow:hidden;
    }
    #tktwork .stage-progress-inner{
      height:100%;
      border-radius:999px;
      background:linear-gradient(90deg,#4f46e5,#22c55e);
      width:0%;
      transition:width .25s ease-out;
    }

    /* CHECKLISTS */
    #tktwork .checklist{
      margin-top:4px;
      padding:6px 8px;
      border-radius:10px;
      background:#ffffff;
      border:1px solid #e5e7eb;
    }
    #tktwork .checklist-title{
      font-size:.8rem;
      font-weight:600;
      margin-bottom:4px;
      display:flex;
      justify-content:space-between;
      gap:8px;
      align-items:center;
    }
    #tktwork .checklist-items{
      list-style:none;
      padding:0;
      margin:0;
      display:flex;
      flex-direction:column;
      gap:3px;
    }
    #tktwork .checklist-item{
      font-size:.8rem;
      display:flex;
      align-items:flex-start;
      gap:6px;
    }
    #tktwork .checklist-item input[type="checkbox"]{
      margin-top:2px;
      cursor:pointer;
    }
    #tktwork .checklist-item label{
      display:flex;
      gap:6px;
      align-items:flex-start;
      cursor:pointer;
    }
    #tktwork .checklist-item span.text{
      transition:opacity .15s ease, transform .15s ease;
    }
    #tktwork .checklist-item.is-done span.text{
      text-decoration:line-through;
      opacity:.6;
    }

    /* EVIDENCIA + BOTONES */
    #tktwork .stage-actions{
      margin-top:8px;
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      justify-content:space-between;
      align-items:center;
    }
    #tktwork .btn{
      appearance:none;
      border-radius:999px;
      padding:.45rem .95rem;
      font-weight:600;
      font-size:.82rem;
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
    #tktwork .btn:hover{
      transform:translateY(-1px);
      box-shadow:0 10px 24px rgba(15,23,42,.1);
      background-position:100% 0;
    }
    #tktwork .btn:active{
      transform:translateY(0);
      box-shadow:0 5px 14px rgba(15,23,42,.08);
    }
    #tktwork .btn.primary{
      border-color:var(--accent-border);
      background-image:linear-gradient(120deg,#4f46e5,#6366f1);
      color:#eef2ff;
    }
    #tktwork .btn[disabled]{
      opacity:.6;
      cursor:not-allowed;
      box-shadow:none;
      transform:none;
    }
    #tktwork .btn-ghost{
      border-style:dashed;
      background:#ffffff;
    }

    #tktwork .evidence{
      margin-top:6px;
      padding:6px 8px;
      border-radius:10px;
      background:#f9fafb;
      border:1px dashed #d1d5db;
      font-size:.78rem;
    }
    #tktwork .evidence-row{
      display:flex;
      flex-wrap:wrap;
      gap:6px;
      margin-top:4px;
    }
    #tktwork .evidence-row input[type="file"],
    #tktwork .evidence-row input[type="url"]{
      flex:1;
      min-width:140px;
      font-size:.78rem;
    }

    #tktwork input[type="file"],
    #tktwork input[type="url"],
    #tktwork textarea{
      border-radius:10px;
      border:1px solid #d1d5db;
      padding:.4rem .6rem;
      font-size:.8rem;
      width:100%;
    }
    #tktwork input[type="url"]:focus,
    #tktwork textarea:focus{
      outline:none;
      border-color:var(--accent-border);
      box-shadow:var(--ring);
      background:#f9fbff;
    }

    #tktwork .mini{
      font-size:.78rem;
      color:var(--muted);
    }

    /* PANEL DERECHO: RESUMEN */
    #tktwork .summary-row{
      display:flex;
      flex-direction:column;
      gap:6px;
      margin-top:4px;
    }
    #tktwork .summary-label{
      font-size:.8rem;
      font-weight:600;
      color:var(--muted);
    }
    #tktwork .summary-value{
      font-size:.86rem;
    }
    #tktwork .summary-notes{
      margin-top:6px;
    }

    /* Debug box */
    #tktwork .debugBox{
      border:1px dashed #c7d2fe;
      background:#eef2ff;
      border-radius:12px;
      padding:10px 12px;
      margin:10px 0 14px;
      font-size:.78rem;
      color:#1e3a8a;
    }

    @keyframes fadeUp{
      from{opacity:0;transform:translateY(12px) scale(.98);}
      to{opacity:1;transform:translateY(0) scale(1);}
    }

    @media (max-width:1000px){
      #tktwork .grid{grid-template-columns:1fr;}
    }
  </style>

  <div class="wrap">
    {{-- HEADER --}}
    <div class="head">
      <div class="head-main">
        <h1>{{ $ticket->folio ?? 'Ticket' }}</h1>
        <p>
          Orden de trabajo de licitación ·
          Cliente:
          <strong>{{ $ticket->client_name ?? optional($ticket->client)->name ?? 'Sin cliente' }}</strong>
        </p>

        <div class="head-badges">
          @php
            $prioClass = match ($ticket->priority) {
              'alta'  => 'tag-prio-alta',
              'media' => 'tag-prio-media',
              'baja'  => 'tag-prio-baja',
              default => 'tag-prio-neutral',
            };
          @endphp
          <span class="tag-prio {{ $prioClass }}">
            Prioridad: {{ ucfirst($ticket->priority ?? '—') }}
          </span>
          <span class="pill pill-strong">
            Estado: {{ ucfirst($ticket->status ?? '—') }}
          </span>
          <span class="pill">
            Responsable: <strong>{{ optional($ticket->owner)->name ?? 'Sin asignar' }}</strong>
          </span>
          <span class="pill">
            Fecha límite:
            <strong>{{ optional($ticket->due_at)->format('d/m/Y H:i') ?? 'Sin definir' }}</strong>
          </span>
        </div>
      </div>

      <div>
        <span class="mini">Vista operador</span>
        <div class="mini">Aquí solo marcas avance, checklist y evidencia.</div>
      </div>
    </div>

    {{-- DEBUG (solo si APP_DEBUG=true) --}}
    @if(config('app.debug'))
      @php
        $stagesDbg = $asCollection($ticket->stages ?? []);
        $firstStage = $stagesDbg->first();
        $firstStageChecklists = $asCollection(data_get($firstStage, 'checklists', []));
        $firstChecklist = $firstStageChecklists->first();
        $firstChecklistItems = $asCollection(data_get($firstChecklist, 'items', []));
      @endphp
      <div class="debugBox">
        <b>DEBUG</b> · stages: {{ $stagesDbg->count() }}
        | checklists(1ra etapa): {{ $firstStageChecklists->count() }}
        | items(1ra checklist): {{ $firstChecklistItems->count() }}
        <div style="margin-top:6px">
          generales (stage_id NULL): {{ $generalChecklists->count() }}
        </div>
      </div>
    @endif

    {{-- PROGRESO GLOBAL --}}
    <div class="progress-card">
      <div>
        <div class="progress-title">Avance del ticket</div>
        <div class="progress-sub">
          Marca los puntos de checklist y cierra cada etapa cuando esté completa.
        </div>
      </div>
      <div class="progress-bar-wrap">
        <div class="progress-bar-outer">
          <div class="progress-bar-inner" id="ticketProgressBar" style="width: {{ $progress }}%;"></div>
        </div>
      </div>
      <div>
        <span class="progress-badge">{{ $progress }}%</span>
      </div>
    </div>

    <div class="grid">
      {{-- IZQUIERDA: ETAPAS Y CHECKLISTS --}}
      <div class="card">
        <h2 class="card-title">Etapas a realizar</h2>
        <div class="card-sub">
          Sigue el orden propuesto. Inicia una etapa, completa su checklist, sube evidencia y márcala como terminada.
        </div>

        @php
          $stages = $asCollection($ticket->stages ?? []);
        @endphp

        @forelse($stages as $stage)
          @php
            // ✅ Si NO viene eager loaded, lo cargamos aquí con items ordenados
            $stageChecklists = $stage->relationLoaded('checklists')
              ? $asCollection($stage->checklists)
              : $asCollection($stage->checklists()->with(['items' => fn($q) => $q->orderBy('position')])->orderBy('id')->get());

            $stageItems = $stageChecklists->flatMap(fn($c) => $asCollection($c->items ?? []));
            $done  = $stageItems->where('is_done', true)->count();
            $totalRaw = $stageItems->count();
            $total = max(1, $totalRaw);
            $pct   = (int) round($done * 100 / $total);
            $isCurrent = ($stage->status ?? null) !== 'terminado';
          @endphp

          <section class="stage {{ $isCurrent ? 'is-current' : '' }}" data-stage-id="{{ $stage->id }}">
            <header class="stage-header">
              <div>
                <div class="stage-name">
                  {{ $stage->position ?? $loop->iteration }}. {{ $stage->name ?? 'Etapa' }}
                </div>
                <div class="stage-meta">
                  Estado:
                  <strong>{{ ucfirst(str_replace('_',' ', $stage->status ?? 'pendiente')) }}</strong>
                  @if($stage->assignee)
                    · Encargado: <strong>{{ $stage->assignee->name }}</strong>
                  @endif
                </div>
              </div>
            </header>

            <div class="stage-progress">
              <span>{{ $done }} / {{ $totalRaw ?: '—' }} puntos completados</span>
              <div class="stage-progress-bar">
                <div class="stage-progress-inner" style="width: {{ $pct }}%;"></div>
              </div>
            </div>

            {{-- CHECKLISTS --}}
            @forelse($stageChecklists as $chk)
              @php
                $chkItems = $asCollection($chk->items ?? []);
                $chkDone  = $chkItems->where('is_done', true)->count();
                $chkTotal = $chkItems->count();
                $chkTitle = $chk->title ?? 'Checklist';
              @endphp

              <div class="checklist">
                <div class="checklist-title">
                  <span>{{ $chkTitle }}</span>
                  <span class="mini">{{ $chkDone }} / {{ $chkTotal }} hechos</span>
                </div>

                <ul class="checklist-items">
                  @forelse($chkItems as $item)
                    @php
                      $label = $item->label ?? $item->text ?? $item->name ?? ('Punto '.$loop->iteration);
                      $isDone = (bool) ($item->is_done ?? false);
                    @endphp

                    <li class="checklist-item {{ $isDone ? 'is-done' : '' }}">
                      <label>
                        <input
                          type="checkbox"
                          class="js-check-item"
                          data-item-id="{{ $item->id }}"
                          @checked($isDone)
                        >
                        <span class="text">{{ $label }}</span>
                      </label>
                    </li>
                  @empty
                    <li class="mini">Esta checklist aún no tiene puntos definidos.</li>
                  @endforelse
                </ul>
              </div>
            @empty
              <div class="mini" style="margin-top:8px">
                Esta etapa no tiene checklists configuradas.
              </div>
            @endforelse

            {{-- EVIDENCIA --}}
            <div class="evidence">
              <div class="mini">Evidencia de esta etapa (opcional o requerida según el coordinador).</div>
              <form class="evidence-row js-evidence-form" data-stage-id="{{ $stage->id }}">
                <input type="file" name="file">
                <input type="url" name="link" placeholder="o pega aquí un enlace (Drive, plataforma, etc.)">
                <button type="submit" class="btn btn-ghost js-evidence-btn">
                  Subir evidencia
                </button>
              </form>
            </div>

            {{-- ACCIONES --}}
            <div class="stage-actions">
              <div class="mini">
                1) Inicia la etapa · 2) Marca el checklist · 3) Subir evidencia · 4) Terminar etapa.
              </div>

              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <button
                  type="button"
                  class="btn btn-ghost js-start-stage"
                  data-stage-id="{{ $stage->id }}"
                  @disabled(($stage->status ?? 'pendiente') !== 'pendiente')
                >
                  Iniciar etapa
                </button>

                <button
                  type="button"
                  class="btn primary js-complete-stage"
                  data-stage-id="{{ $stage->id }}"
                  @disabled(($stage->status ?? null) === 'terminado')
                >
                  Marcar etapa como terminada
                </button>
              </div>
            </div>
          </section>
        @empty
          <p class="mini">Este ticket aún no tiene etapas configuradas. El coordinador debe definirlas.</p>
        @endforelse

        {{-- ✅ CHECKLISTS GENERALES --}}
        <div style="margin-top:16px">
          <h3 class="card-title" style="margin:0 0 6px">Checklists generales</h3>
          <div class="card-sub">
            Estas checklists no están asociadas a una etapa (stage_id = NULL). Si aquí aparecen, tu problema es que se están creando “sueltas”.
          </div>

          @forelse($generalChecklists as $chk)
            @php
              $chkItems = $asCollection($chk->items ?? []);
              $chkDone  = $chkItems->where('is_done', true)->count();
              $chkTotal = $chkItems->count();
              $chkTitle = $chk->title ?? 'Checklist';
            @endphp

            <div class="checklist">
              <div class="checklist-title">
                <span>{{ $chkTitle }}</span>
                <span class="mini">{{ $chkDone }} / {{ $chkTotal }} hechos</span>
              </div>

              <ul class="checklist-items">
                @forelse($chkItems as $item)
                  @php
                    $label = $item->label ?? $item->text ?? $item->name ?? ('Punto '.$loop->iteration);
                    $isDone = (bool) ($item->is_done ?? false);
                  @endphp
                  <li class="checklist-item {{ $isDone ? 'is-done' : '' }}">
                    <label>
                      <input
                        type="checkbox"
                        class="js-check-item"
                        data-item-id="{{ $item->id }}"
                        @checked($isDone)
                      >
                      <span class="text">{{ $label }}</span>
                    </label>
                  </li>
                @empty
                  <li class="mini">Esta checklist aún no tiene puntos definidos.</li>
                @endforelse
              </ul>
            </div>
          @empty
            <div class="mini">No hay checklists generales.</div>
          @endforelse
        </div>
      </div>

      {{-- DERECHA: RESUMEN / NOTAS --}}
      <aside class="card">
        <h2 class="card-title">Resumen rápido</h2>
        <div class="card-sub">
          Información clave de la licitación para que entiendas qué estás ejecutando.
        </div>

        <div class="summary-row">
          <div>
            <div class="summary-label">Título del ticket</div>
            <div class="summary-value">{{ $ticket->title ?? 'Sin título' }}</div>
          </div>

          <div>
            <div class="summary-label">Número de licitación</div>
            <div class="summary-value">{{ $ticket->numero_licitacion ?? 'No capturado' }}</div>
          </div>

          <div>
            <div class="summary-label">Monto de la propuesta</div>
            <div class="summary-value">
              @if(!is_null($ticket->monto_propuesta))
                $ {{ number_format($ticket->monto_propuesta, 2) }}
              @else
                Sin definir
              @endif
            </div>
          </div>

          <div>
            <div class="summary-label">Estatus de adjudicación</div>
            <div class="summary-value">
              @php
                $map = [
                  'en_espera' => 'En espera',
                  'ganada'    => 'Ganada',
                  'perdida'   => 'Perdida',
                ];
              @endphp
              {{ $map[$ticket->estatus_adjudicacion] ?? 'Sin definir' }}
            </div>
          </div>

          @if(($ticket->links ?? collect())->count())
            <div>
              <div class="summary-label">Enlaces clave</div>
              <ul class="mini" style="margin-top:2px;padding-left:1.1rem;">
                @foreach($ticket->links as $lnk)
                  <li>
                    <a href="{{ $lnk->url }}" target="_blank" rel="noopener">
                      {{ $lnk->label }}
                    </a>
                  </li>
                @endforeach
              </ul>
            </div>
          @endif
        </div>

        <form method="POST" action="{{ route('tickets.update',$ticket) }}" class="summary-notes">
          @csrf
          @method('PUT')
          <label class="summary-label" for="quick_notes">
            Notas de avance (lo que ya hiciste, pendientes, riesgos)
          </label>
          <textarea
            id="quick_notes"
            name="quick_notes"
            rows="4"
            placeholder="Ejemplo: bases revisadas, se detectó requisito de garantía extendida; falta confirmar con proveedor."
          >{{ old('quick_notes',$ticket->quick_notes) }}</textarea>
          <div style="margin-top:6px;display:flex;justify-content:flex-end;">
            <button type="submit" class="btn btn-ghost">
              Guardar notas
            </button>
          </div>
        </form>
      </aside>
    </div>
  </div>
</div>

{{-- JS: trabajar etapas / checklist / evidencia vía AJAX --}}
<script>
  const CSRF = "{{ csrf_token() }}";

  const START_URL_TPL    = @json(route('tickets.ajax.stage.start',    ['ticket'=>$ticket->id,'stage'=>'__STAGE__']));
  const COMPLETE_URL_TPL = @json(route('tickets.ajax.stage.complete', ['ticket'=>$ticket->id,'stage'=>'__STAGE__']));
  const EVIDENCE_URL_TPL = @json(route('tickets.ajax.stage.evidence', ['ticket'=>$ticket->id,'stage'=>'__STAGE__']));

  // Para marcar items como hechos: usamos updateItem con _method=PUT
  const ITEM_UPDATE_URL_TPL = @json(route('checklists.items.update', ['item'=>'__ITEM__']));

  function toast(msg){
    if (window.Swal){
      Swal.fire({
        toast:true,
        position:'top-end',
        icon:'success',
        title:msg,
        showConfirmButton:false,
        timer:2200,
      });
    }else{
      alert(msg);
    }
  }

  async function toggleChecklistItem(input){
    const itemId = input.dataset.itemId;
    if (!itemId){
      alert('No se encontró el item_id. Revisa que el item tenga id.');
      input.checked = !input.checked;
      return;
    }

    const url = ITEM_UPDATE_URL_TPL.replace('__ITEM__', encodeURIComponent(itemId));
    const formData = new FormData();
    formData.append('_method','PUT');
    formData.append('is_done', input.checked ? '1' : '0');

    try{
      const res = await fetch(url, {
        method:'POST',
        headers:{
          'X-CSRF-TOKEN': CSRF,
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
        },
        body:formData,
      });

      const j = await res.json().catch(()=> ({}));

      if (!res.ok || (j.ok === false)){
        throw new Error(j.message || 'No se pudo actualizar el punto.');
      }

      const li = input.closest('.checklist-item');
      if (li){
        li.classList.toggle('is-done', input.checked);
      }

      toast('Checklist actualizado');
    }catch(e){
      console.error(e);
      alert(e.message || 'Error al actualizar el checklist.');
      input.checked = !input.checked; // revertir
    }
  }

  async function startStage(btn){
    const stageId = btn.dataset.stageId;
    if (!stageId) return;

    const url = START_URL_TPL.replace('__STAGE__', encodeURIComponent(stageId));
    btn.disabled = true;

    try{
      const res = await fetch(url, {
        method:'POST',
        headers:{
          'X-CSRF-TOKEN': CSRF,
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
        }
      });
      const j = await res.json().catch(()=> ({}));

      if (!res.ok || !j.ok){
        throw new Error(j.msg || 'No se pudo iniciar la etapa.');
      }

      toast('Etapa iniciada');
      location.reload();
    }catch(e){
      console.error(e);
      alert(e.message || 'Error al iniciar la etapa.');
    }finally{
      btn.disabled = false;
    }
  }

  async function completeStage(btn){
    const stageId = btn.dataset.stageId;
    if (!stageId) return;

    const url = COMPLETE_URL_TPL.replace('__STAGE__', encodeURIComponent(stageId));
    btn.disabled = true;

    try{
      const res = await fetch(url, {
        method:'POST',
        headers:{
          'X-CSRF-TOKEN': CSRF,
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
        }
      });
      const j = await res.json().catch(()=> ({}));

      if (!res.ok || !j.ok){
        throw new Error(j.msg || 'No se pudo cerrar la etapa.');
      }

      toast('Etapa marcada como terminada');
      location.reload();
    }catch(e){
      console.error(e);
      alert(e.message || 'Error al cerrar la etapa.');
    }finally{
      btn.disabled = false;
    }
  }

  async function uploadEvidence(form){
    const stageId = form.dataset.stageId;
    if (!stageId) return;

    const url = EVIDENCE_URL_TPL.replace('__STAGE__', encodeURIComponent(stageId));
    const btn = form.querySelector('.js-evidence-btn');

    const fd = new FormData(form); // incluye file + link
    if (btn) btn.disabled = true;

    try{
      const res = await fetch(url, {
        method:'POST',
        headers:{
          'X-CSRF-TOKEN': CSRF,
          'Accept':'application/json',
          'X-Requested-With':'XMLHttpRequest',
        },
        body:fd,
      });

      const j = await res.json().catch(()=> ({}));

      if (!res.ok || !j.ok){
        throw new Error(j.message || 'No se pudo subir la evidencia.');
      }

      toast('Evidencia registrada');
      form.reset();
    }catch(e){
      console.error(e);
      alert(e.message || 'Error al subir la evidencia.');
    }finally{
      if (btn) btn.disabled = false;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Checklist
    document.querySelectorAll('.js-check-item').forEach(input => {
      input.addEventListener('change', () => toggleChecklistItem(input));
    });

    // Iniciar etapa
    document.querySelectorAll('.js-start-stage').forEach(btn => {
      btn.addEventListener('click', () => startStage(btn));
    });

    // Completar etapa
    document.querySelectorAll('.js-complete-stage').forEach(btn => {
      btn.addEventListener('click', () => completeStage(btn));
    });

    // Evidencia
    document.querySelectorAll('.js-evidence-form').forEach(form => {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        uploadEvidence(form);
      });
    });
  });
</script>
@endsection
