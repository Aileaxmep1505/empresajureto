@props(['ticket','stage'])

<div class="step">
  <h3 class="h" style="font-weight:800">Actividades de la etapa</h3>
  <p class="muted">Responsable: {{ optional($stage->assignee)->name ?? 'Sin asignar' }}</p>

  @if($stage->status === 'pendiente')
    <form method="POST" action="{{ route('tickets.wizard.start',[$ticket,$stage]) }}">
      @csrf
      <button class="btn">Comenzar etapa</button>
    </form>
  @endif

  @if($stage->status !== 'terminado')
    <form method="POST" action="{{ route('tickets.wizard.complete',[$ticket,$stage]) }}" style="margin-top:10px">
      @csrf
      <button class="btn">Marcar como completada</button>
    </form>
  @else
    <div class="chip">✅ Completada {{ $stage->finished_at? $stage->finished_at->diffForHumans() : '' }}</div>
  @endif
</div>
