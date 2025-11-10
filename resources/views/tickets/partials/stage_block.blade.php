<div class="stage" id="stage-{{ $st->id }}">
  <div>
    <div class="k">{{ $st->position }}. {{ $st->name }}</div>
    <div class="muted">
      Estado: {{ ucfirst(str_replace('_',' ',$st->status)) }} ·
      Responsable: {{ optional($st->assignee)->name ?? '—' }} ·
      Vence: {{ $st->due_at ? $st->due_at->format('d/m H:i') : '—' }}
    </div>

    {{-- IA prompt + botón --}}
    <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
      <input type="text" id="prompt-{{ $st->id }}" placeholder="Describe la tarea (p. ej. 'Generar ficha técnica con normas X')"
             style="flex:1; border:1px solid var(--line); border-radius:8px; padding:8px">
      <button class="chip" onclick="aiChecklist({{ $ticket->id }}, {{ $st->id }})">🤖 Checklist IA</button>
    </div>

    @foreach($st->checklists as $chk)
      <div style="margin-top:10px; border:1px dashed var(--line); border-radius:10px; padding:10px">
        <div class="k">{{ $chk->title ?? 'Checklist' }}</div>
        @if($chk->instructions)
          <div class="muted" style="white-space:pre-wrap">{{ $chk->instructions }}</div>
        @endif
        <ul style="margin-top:8px">
          @foreach($chk->items as $it)
            <li style="display:flex; align-items:center; gap:8px; margin-bottom:4px">
              <input type="checkbox" {{ $it->is_done ? 'checked' : '' }}
                     onchange="toggleItem({{ $ticket->id }}, {{ $it->id }}, this.checked)">
              <span>{{ $it->text }}</span>
            </li>
          @endforeach
        </ul>
      </div>
    @endforeach

    {{-- Evidencia --}}
    <div style="margin-top:10px">
      <form onsubmit="return uploadEvidence(event, {{ $ticket->id }}, {{ $st->id }});" enctype="multipart/form-data">
        <input type="file" name="file" accept="image/*,video/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
        <input type="url"  name="link" placeholder="o pega un enlace a la evidencia (Drive, etc.)">
        <button class="chip">Subir evidencia</button>
      </form>
    </div>
  </div>

  <div style="text-align:right">
    <div style="margin-bottom:6px">
      @php $signal = $st->slaSignal(); @endphp
      @if($signal==='overdue') <span class="chip" style="background:#ffecec;border-color:#ffc9c9">🔴 Vencido</span>
      @elseif($signal==='due_soon') <span class="chip" style="background:#fff7e6;border-color:#fde7b0">🟡 Por vencer</span>
      @else <span class="chip" style="background:#e9fbe9;border-color:#c0f2c0">🟢 Ok</span>
      @endif
    </div>

    <div style="display:flex; gap:6px; justify-content:flex-end">
      <button class="chip" onclick="startStage({{ $ticket->id }}, {{ $st->id }})">Iniciar</button>
      <button class="chip" onclick="completeStage({{ $ticket->id }}, {{ $st->id }})">Completar</button>
    </div>
  </div>
</div>

<script>
async function aiChecklist(ticketId, stageId){
  const prompt = document.getElementById('prompt-'+stageId).value.trim();
  if(!prompt){ alert('Escribe qué se debe hacer.'); return; }
  const res = await fetch(`{{ url('/tickets') }}/${ticketId}/stages/${stageId}/ai-checklist`,{
    method:'POST',
    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'},
    body: JSON.stringify({prompt})
  });
  const j = await res.json();
  if(j.ok){ location.reload(); } else { alert(j.msg || 'Error IA'); }
}

async function toggleItem(ticketId, itemId, done){
  const res = await fetch(`{{ url('/tickets') }}/${ticketId}/checklist-items/${itemId}/toggle`,{
    method:'POST',
    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'},
    body: JSON.stringify({done})
  });
  const j = await res.json();
  if(!j.ok){ alert(j.msg || 'No se pudo actualizar'); }
}

async function startStage(ticketId, stageId){
  const res = await fetch(`{{ url('/tickets') }}/${ticketId}/stages/${stageId}/start`,{
    method:'POST',
    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
  });
  const j = await res.json();
  if(j.ok){ location.reload(); } else { alert(j.msg || 'No se pudo iniciar'); }
}

async function completeStage(ticketId, stageId){
  const res = await fetch(`{{ url('/tickets') }}/${ticketId}/stages/${stageId}/complete`,{
    method:'POST',
    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
  });
  const j = await res.json();
  if(j.ok){ location.reload(); } else { alert(j.msg || 'No se pudo completar'); }
}

async function uploadEvidence(ev, ticketId, stageId){
  ev.preventDefault();
  const fd = new FormData(ev.target);
  const res = await fetch(`{{ url('/tickets') }}/${ticketId}/stages/${stageId}/evidence`,{
    method:'POST',
    headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','X-Requested-With':'XMLHttpRequest'},
    body: fd
  });
  const j = await res.json();
  if(j.ok){ location.reload(); } else { alert(j.msg || 'No se pudo subir evidencia'); }
  return false;
}
</script>
