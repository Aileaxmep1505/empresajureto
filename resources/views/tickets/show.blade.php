{{-- resources/views/tickets/show.blade.php --}}
@extends('layouts.app')
@section('title', $ticket->folio)

@section('content')
<div id="tktshow" class="container-fluid p-0">
  <style>
    #tktshow{
      --ink:#0e1726; --muted:#64748b; --line:#e6eaf2; --bg:#f6f9ff; --card:#fff;
      --brand:#8ec5ff; --brand-ink:#0b1220; --ok:#12b886; --ring:0 0 0 4px rgba(142,197,255,.25);
      color:var(--ink); background:linear-gradient(180deg,#fbfdff,#f6f9ff);
      font-synthesis-weight:none;
    }
    #tktshow *{box-sizing:border-box}
    #tktshow .wrap{max-width:1200px;margin:24px auto;padding:0 16px}
    #tktshow .grid{display:grid;grid-template-columns:1.2fr .8fr;gap:16px}
    #tktshow .card{background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:0 12px 28px rgba(2,8,23,.05);overflow:hidden}
    #tktshow .head{display:flex;justify-content:space-between;align-items:center;padding:16px;border-bottom:1px solid var(--line);background:linear-gradient(180deg,#ffffff,#f8fbff)}
    #tktshow .body{padding:16px}
    #tktshow .k{font-weight:800}
    #tktshow .muted{color:var(--muted)}
    #tktshow .mini{font-size:.85rem}
    #tktshow .row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    #tktshow .chip{padding:.35rem .7rem;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:.82rem}
    #tktshow .btn{background:linear-gradient(180deg,#fff,#f2f7ff);border:1px solid #dbe4ff;border-radius:12px;padding:.6rem .9rem;cursor:pointer;transition:transform .15s,opacity .15s}
    #tktshow .btn:hover{transform:translateY(-1px)}
    #tktshow .btn[disabled]{opacity:.6;cursor:not-allowed}
    #tktshow input[type="text"],#tktshow input[type="url"],#tktshow input[type="number"],#tktshow select,#tktshow textarea{
      width:100%;border:1px solid var(--line);border-radius:12px;padding:.65rem .75rem;outline:none;background:#fff
    }
    #tktshow input:focus,#tktshow select:focus,#tktshow textarea:focus{border-color:#c7ddff;box-shadow:var(--ring)}
    #tktshow .stage{border:1px dashed var(--line);border-radius:12px;padding:12px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:flex-start;gap:12px;background:#fff}
    #tktshow .stage-actions{display:flex;gap:8px;justify-content:flex-end}
    #tktshow .divider{margin:16px 0;border:none;border-top:1px solid var(--line)}
    @media (max-width:1000px){ #tktshow .grid{grid-template-columns:1fr} }
  </style>

  <div class="wrap">
    {{-- CABECERA --}}
    <div class="head card">
      <div style="display:flex;flex-direction:column;gap:6px">
        <div class="k">{{ $ticket->folio }} · {{ strtoupper($ticket->type) }}</div>
        <div class="muted mini">
          {{ $ticket->client_name ?? ($ticket->client->name ?? '—') }}
          · Prioridad: <b>{{ ucfirst($ticket->priority) }}</b>
          · Estado: <b>{{ ucfirst($ticket->status) }}</b>
        </div>
      </div>

      <form class="row" method="POST" action="{{ route('tickets.update',$ticket) }}">
        @csrf @method('PUT')
        <input type="text" name="title" value="{{ old('title',$ticket->title) }}" placeholder="Título/Asunto" style="min-width:260px">
        <select name="priority">
          @foreach(['alta'=>'Alta','media'=>'Media','baja'=>'Baja'] as $v=>$lbl)
            <option value="{{ $v }}" @selected($ticket->priority===$v)>{{ $lbl }}</option>
          @endforeach
        </select>
        <select name="status">
          @foreach(['revision','proceso','finalizado','cerrado'] as $s)
            <option value="{{ $s }}" @selected($ticket->status===$s)>{{ ucfirst($s) }}</option>
          @endforeach
        </select>
        <button class="btn">Guardar</button>
      </form>
    </div>

    <div class="grid">
      {{-- IZQ: Etapas --}}
      <div class="card">
        <div class="body">
          <h3 class="k">Etapas (configuración)</h3>

          @forelse($ticket->stages as $st)
            <div class="stage" id="stage-{{ $st->id }}">
              <div style="flex:1">
                <div class="k">{{ $st->position }}. {{ $st->name }}</div>
                <div class="muted mini">
                  Estado inicial: <b>{{ ucfirst(str_replace('_',' ',$st->status)) }}</b>
                  · Responsable: <b>{{ optional($st->assignee)->name ?? '—' }}</b>
                </div>

                @foreach($st->checklists as $chk)
                  <div class="row mini" style="margin-top:6px">
                    <span class="chip">🧾 {{ $chk->title }}</span>
                    <a class="chip" href="{{ route('checklists.export.pdf',$chk) }}">PDF</a>
                    <a class="chip" href="{{ route('checklists.export.word',$chk) }}">Word</a>
                    <form method="POST" action="{{ route('checklists.destroy',$chk) }}" onsubmit="return confirm('¿Eliminar checklist?')" style="display:inline">
                      @csrf @method('DELETE')
                      <button class="chip" type="submit">Eliminar</button>
                    </form>
                  </div>
                @endforeach

                <form class="row" style="margin-top:8px" method="POST" action="{{ route('tickets.checklists.store',$ticket) }}">
                  @csrf
                  <input type="hidden" name="stage_id" value="{{ $st->id }}">
                  <input type="text" name="title" placeholder="Nueva checklist para esta etapa">
                  <button class="chip">Agregar</button>
                </form>
              </div>

              <div class="stage-actions">
                <form method="POST" action="{{ route('tickets.stages.destroy',[$ticket,$st]) }}" onsubmit="return confirm('¿Eliminar la etapa \"{{ $st->name }}\"? Se borrarán sus checklists, items y evidencias.')">
                  @csrf @method('DELETE')
                  <button class="btn" type="submit">🗑️ Eliminar etapa</button>
                </form>
              </div>
            </div>
          @empty
            <div class="muted">Sin etapas. Crea la primera abajo.</div>
          @endforelse

          <form class="mt-3 row" method="POST" action="{{ route('tickets.stages.store',$ticket) }}">
            @csrf
            <input type="text" name="name" placeholder="Nueva etapa (p. ej. Post-venta)" required />
            <button class="btn">Agregar etapa</button>
          </form>
        </div>
      </div>

      {{-- DER: IA + Documentos --}}
      <div class="card">
        <div class="body">
          <h3 class="k">Asistente IA (generar checklists)</h3>
          <div class="row">
            <select id="ai-stage">
              @foreach($ticket->stages as $st)
                <option value="{{ $st->id }}">{{ $st->position }}. {{ $st->name }}</option>
              @endforeach
            </select>
            <span class="chip">8–12 puntos medibles</span>
          </div>
          <textarea id="ai-prompt" rows="5" style="margin-top:8px" placeholder="Describe lo que se tiene que hacer (ej. 'Integración de aprobación y seguimiento: estados, reglas, notificaciones, PDF firmado, registro de auditoría, etc.')"></textarea>
          <div class="row" style="justify-content:flex-end">
            <button class="btn" type="button" id="btnSuggest">✨ Sugerir con IA</button>
          </div>

          <div id="ai-result" style="display:none;margin-top:10px">
            <div class="k" id="ai-title">Checklist sugerido</div>
            <div class="muted mini" id="ai-instructions" style="margin-top:4px"></div>
            <div id="ai-items" style="display:flex;flex-direction:column;gap:8px;margin-top:8px"></div>
            <div class="row" style="justify-content:space-between;margin-top:8px">
              <span class="muted mini">Edita puntos antes de crear.</span>
              <button class="btn" type="button" id="btnCreate">🧾 Crear checklist en la etapa</button>
            </div>
          </div>

          <hr class="divider">

          <h3 class="k">Licitación</h3>
          <form class="row" method="POST" action="{{ route('tickets.update',$ticket) }}">
            @csrf @method('PUT')
            <input type="text" name="numero_licitacion" value="{{ old('numero_licitacion',$ticket->numero_licitacion) }}" placeholder="Número de licitación" />
            <input type="number" step="0.01" name="monto_propuesta" value="{{ old('monto_propuesta',$ticket->monto_propuesta) }}" placeholder="Monto de la propuesta" />
            <select name="estatus_adjudicacion">
              <option value="">— Estatus —</option>
              @foreach(['en_espera','ganada','perdida'] as $e)
                <option value="{{ $e }}" @selected($ticket->estatus_adjudicacion===$e)>{{ ucfirst(str_replace('_',' ',$e)) }}</option>
              @endforeach
            </select>
            <button class="btn">Guardar</button>
          </form>

          <div style="margin-top:10px">
            @foreach($ticket->links as $lnk)
              <div class="mini">🔗 <a href="{{ $lnk->url }}" target="_blank">{{ $lnk->label }}</a></div>
            @endforeach
          </div>

          <hr class="divider">

          <h3 class="k">Documentos del ticket</h3>
          <form method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data" class="row">
            @csrf
            <input type="text" name="name" placeholder="Nombre del documento" />
            <input type="text" name="category" placeholder="Categoría (propuesta, evidencia...)" />
            <input type="file" name="file" />
            <input type="url" name="external_url" placeholder="o URL externa (Drive)" />
            <button class="btn">Subir</button>
          </form>

          <ul style="margin-top:10px">
            @foreach($ticket->documents as $d)
              <li class="mini" id="doc-{{ $d->id }}">
                <strong>{{ $d->name }}</strong> v{{ $d->version }} <span class="muted">({{ $d->category ?? '—' }})</span>
                @if($d->path) · <a href="{{ route('tickets.documents.download',[$ticket,$d]) }}">Descargar</a>@endif
                @if($d->external_url) · <a href="{{ $d->external_url }}" target="_blank">Enlace</a>@endif
                <form method="POST" action="{{ route('tickets.documents.destroy',[$ticket,$d]) }}" style="display:inline" onsubmit="return confirm('¿Eliminar documento?')">
                  @csrf @method('DELETE')
                  · <button class="chip" type="submit">Eliminar</button>
                </form>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- IA: JS sin dependencias (100% OpenAI) --}}
<script>
  const CSRF = "{{ csrf_token() }}";
  const suggestRouteTmpl = @json(route('tickets.ai.suggest', ['ticket'=>$ticket->id,'stage'=>'__STAGE__']));
  const AI_CREATE_URL    = @json(route('tickets.ai.create', ['ticket'=>$ticket->id]));

  let AI_CACHE = { title: '', instructions: '', items: [] };

  const $btnSuggest = document.getElementById('btnSuggest');
  const $btnCreate  = document.getElementById('btnCreate');

  $btnSuggest?.addEventListener('click', async () => {
    const stageId = document.getElementById('ai-stage').value;
    const prompt  = (document.getElementById('ai-prompt').value || '').trim();
    if(!prompt){ alert('Escribe lo que se tiene que hacer.'); return; }

    const url = suggestRouteTmpl.replace('__STAGE__', stageId);
    $btnSuggest.disabled = true; $btnSuggest.textContent = 'Generando…';

    try{
      const res = await fetch(url, {
        method:'POST',
        headers: {'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({ prompt })
      });
      const j = await res.json();
      if(!res.ok || !j.ok){ throw new Error(j.message || 'La IA no pudo generar el checklist (8–12 puntos).'); }

      AI_CACHE.title        = j.title || 'Checklist sugerido';
      AI_CACHE.instructions = j.instructions || '';
      AI_CACHE.items        = Array.isArray(j.items) ? j.items : [];

      renderAiPreview();
    }catch(e){ alert(e.message || 'Error IA'); }
    finally{ $btnSuggest.disabled = false; $btnSuggest.textContent = '✨ Sugerir con IA'; }
  });

  $btnCreate?.addEventListener('click', async () => {
    const stageId = document.getElementById('ai-stage').value;
    const items = (AI_CACHE.items||[]).map(s => (s||'').trim()).filter(Boolean);
    if(items.length < 8 || items.length > 12){ alert('Debes tener entre 8 y 12 puntos.'); return; }

    $btnCreate.disabled = true; $btnCreate.textContent = 'Creando…';

    try{
      const fd = new FormData();
      fd.append('stage_id', stageId);
      fd.append('title', AI_CACHE.title || 'Checklist IA');
      items.forEach(it => fd.append('items[]', it));

      const res = await fetch(AI_CREATE_URL, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'}, body: fd });
      const j = await res.json();
      if(!res.ok || !j.ok){ throw new Error(j.message || 'No se pudo crear el checklist.'); }

      alert('Checklist creada en la etapa.');
      location.reload();
    }catch(e){ alert(e.message || 'Error al crear checklist'); }
    finally{ $btnCreate.disabled = false; $btnCreate.textContent = '🧾 Crear checklist en la etapa'; }
  });

  function renderAiPreview(){
    const box   = document.getElementById('ai-result');
    const title = document.getElementById('ai-title');
    const inst  = document.getElementById('ai-instructions');
    const list  = document.getElementById('ai-items');

    box.style.display = 'block';
    title.textContent = AI_CACHE.title;
    inst.textContent  = AI_CACHE.instructions || '';

    list.innerHTML = '';
    if(!AI_CACHE.items.length){
      list.innerHTML = '<div class="muted mini">No hubo ítems. Ajusta el prompt.</div>';
      return;
    }

    AI_CACHE.items.forEach((t,i)=>{
      const row = document.createElement('div');
      row.style.display='grid';
      row.style.gridTemplateColumns='1fr auto';
      row.style.gap='8px';

      const input = document.createElement('input');
      input.type='text';
      input.value=t;
      input.maxLength=500;
      input.oninput=()=>AI_CACHE.items[i]=input.value;

      const del = document.createElement('button');
      del.type='button'; del.className='chip'; del.textContent='✕';
      del.onclick=()=>{ AI_CACHE.items.splice(i,1); renderAiPreview(); };

      row.appendChild(input); row.appendChild(del);
      list.appendChild(row);
    });

    const plus = document.createElement('button');
    plus.type='button'; plus.className='chip'; plus.textContent='＋ Agregar punto';
    plus.onclick=()=>{ AI_CACHE.items.push(''); renderAiPreview(); };
    list.appendChild(plus);
  }
</script>
@endsection
