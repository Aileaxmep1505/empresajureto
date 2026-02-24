@extends('layouts.app')
@section('title', $ticket->folio.' | '.$ticket->title)

@section('content')
@php
  $statuses   = $statuses   ?? \App\Http\Controllers\Tickets\TicketController::STATUSES;
  $priorities = $priorities ?? \App\Http\Controllers\Tickets\TicketController::PRIORITIES;
  $areas      = $areas      ?? \App\Http\Controllers\Tickets\TicketController::AREAS;

  $actionLabels = [
    'ticket_created'    => 'Ticket creado',
    'ticket_updated'    => 'Ticket actualizado',
    'comment_added'     => 'Comentario agregado',
    'doc_uploaded'      => 'Archivo adjunto',
    'evidence_uploaded' => 'Evidencia subida',
    'ticket_completed'  => 'Ticket completado',
    'ticket_cancelled'  => 'Ticket cancelado',
  ];

  $prettyKey = function(string $k){
    $map = [
      'title'       => 'Título',
      'description' => 'Descripción',
      'priority'    => 'Prioridad',
      'area'        => 'Área',
      'status'      => 'Estatus',
      'assignee'    => 'Asignado a',
      'assignee_id' => 'Asignado a',
      'due_at'      => 'Vencimiento',
      'impact'      => 'Impacto',
      'urgency'     => 'Urgencia',
      'effort'      => 'Esfuerzo',
      'score'       => 'Score',
      'files'       => 'Archivos',
      'files_uploaded' => 'Archivos',
    ];
    return $map[$k] ?? ucfirst(str_replace('_',' ', $k));
  };

  $fmt = function($v){
    if (is_null($v) || $v === '') return '—';
    if (is_bool($v)) return $v ? 'Sí' : 'No';
    if (is_array($v)) return '—';
    if (is_string($v)) return str_replace('T', ' ', trim($v));
    return (string) $v;
  };

  $userName = fn($u) => $u ? ($u->name ?? '—') : '—';

  $userById = [];
  if (!empty($users ?? [])) {
    foreach ($users as $u) $userById[(string)$u->id] = $u->name;
  }
  $fmtAssignee = function($id) use ($userById){
    if (!$id) return '—';
    $k = (string)$id;
    return $userById[$k] ?? "Usuario #{$k}";
  };

  $sla = $ticket->sla_signal ?? 'neutral';
  $slaClass = $sla==='overdue' ? 'red' : ($sla==='due_soon' ? 'amber' : ($sla==='ok' ? 'green' : ''));

  $statusColor = function($st){
    return match($st){
      'completado' => 'green',
      'cancelado'  => 'red',
      'bloqueado'  => 'amber',
      'revision'   => 'amber',
      'pruebas'    => 'amber',
      default      => '',
    };
  };

  $canWork = auth()->id() && ((int)auth()->id() === (int)($ticket->assignee_id ?? 0));
@endphp

<div class="container py-4" id="tkShow">
  <style>
    #tkShow{
      --ink:#0b1220; --muted:rgba(15,23,42,.62); --line:rgba(15,23,42,.10);
      --card:rgba(255,255,255,.94); --radius:16px;
      --shadow:0 14px 44px rgba(2,6,23,.08);
      --shadow2:0 10px 24px rgba(2,6,23,.07);
      --blue:rgba(59,130,246,.13);
      --green:rgba(16,185,129,.14);
      --amber:rgba(245,158,11,.14);
      --red:rgba(239,68,68,.14);
    }
    #tkShow .grid{ display:grid; grid-template-columns: 1.05fr .95fr; gap:14px; }
    #tkShow .cardx{ background:var(--card); border:1px solid var(--line); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
    #tkShow .head{ padding:16px 18px; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#f7f9ff); }
    #tkShow .title{ margin:0; font-weight:1000; color:var(--ink); letter-spacing:-.3px; }
    #tkShow .sub{ color:var(--muted); font-weight:800; font-size:12px; margin-top:6px; }
    #tkShow .btnx{ border:1px solid var(--line); border-radius:12px; padding:10px 12px; font-weight:900; background:#fff; color:var(--ink); text-decoration:none; display:inline-flex; align-items:center; gap:8px; box-shadow:var(--shadow2); }
    #tkShow .btnx.primary{ background:var(--blue); border-color:rgba(59,130,246,.25); }
    #tkShow .btnx.good{ background:var(--green); border-color:rgba(16,185,129,.25); }
    #tkShow .btnx.danger{ background:var(--red); border-color:rgba(239,68,68,.25); }
    #tkShow .btnx:active{ transform:translateY(1px); }

    #tkShow .body{ padding:16px 18px; background:#fff; }
    #tkShow .sect{ padding:14px 18px; border-top:1px solid var(--line); background:#fff; }
    #tkShow .sect h5{ margin:0 0 10px 0; font-weight:1000; color:var(--ink); }

    #tkShow .pill{
      display:inline-flex; align-items:center; gap:8px;
      padding:7px 10px; border-radius:999px; font-weight:1000;
      border:1px solid var(--line); background:rgba(2,6,23,.03); color:var(--ink);
      font-size:12px; white-space:nowrap;
    }
    #tkShow .pill.red{ background:var(--red); border-color:rgba(239,68,68,.25); }
    #tkShow .pill.amber{ background:var(--amber); border-color:rgba(245,158,11,.25); }
    #tkShow .pill.green{ background:var(--green); border-color:rgba(16,185,129,.25); }

    #tkShow .info{ border:1px solid var(--line); border-radius:14px; background:#fff; padding:12px; display:grid; gap:10px; }
    #tkShow .kv{
      display:grid; grid-template-columns: 160px 1fr; gap:10px; align-items:start;
      border:1px solid var(--line); border-radius:12px; padding:10px 12px; background:rgba(2,6,23,.015);
    }
    #tkShow .k{ font-weight:1000; color:var(--muted); font-size:12px; }
    #tkShow .v{ font-weight:900; color:var(--ink); white-space:pre-wrap; }

    #tkShow .comment{
      border:1px solid var(--line);
      border-radius:14px;
      padding:10px 12px;
      background:rgba(2,6,23,.02);
      margin-bottom:10px;
    }
    #tkShow .c-top{ display:flex; justify-content:space-between; gap:10px; }
    #tkShow .c-name{ font-weight:1000; color:var(--ink); }
    #tkShow .c-time{ font-weight:800; color:var(--muted); font-size:12px; }
    #tkShow .c-body{ margin-top:6px; white-space:pre-wrap; color:var(--ink); font-weight:700; }

    #tkShow .doc{
      display:flex; align-items:center; justify-content:space-between;
      gap:10px; padding:10px 12px; border:1px solid var(--line);
      border-radius:14px; background:#fff; margin-bottom:10px;
    }
    #tkShow .doc .name{ font-weight:1000; color:var(--ink); }
    #tkShow .doc .meta{ font-weight:800; color:var(--muted); font-size:12px; }

    #tkShow .audit{ padding:10px 12px; border:1px solid var(--line); border-radius:14px; background:#fff; margin-bottom:10px; }
    #tkShow .audit .a1{ font-weight:1000; color:var(--ink); }
    #tkShow .audit .a2{ font-weight:800; color:var(--muted); font-size:12px; margin-top:4px; }
    #tkShow .audit pre{ margin:8px 0 0 0; font-size:12px; background:rgba(2,6,23,.03); padding:10px; border-radius:12px; overflow:auto; }
    #tkShow details summary{ user-select:none; cursor:pointer; font-weight:900; color:var(--muted); }

    @media(max-width: 992px){
      #tkShow .grid{ grid-template-columns:1fr; }
      #tkShow .kv{ grid-template-columns:1fr; }
    }
  </style>

  @if(session('ok')) <div class="alert alert-success">{{ session('ok') }}</div> @endif
  @if(session('err')) <div class="alert alert-danger">{{ session('err') }}</div> @endif
  @if($errors->any())
    <div class="alert alert-danger">
      <strong>Revisa:</strong>
      <ul class="mb-0">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="cardx mb-3">
    <div class="head">
      <div style="min-width:0">
        <h3 class="title">{{ $ticket->folio }} — {{ $ticket->title }}</h3>
        <div class="sub">
          Creado por: <strong>{{ optional($ticket->creator)->name ?: '—' }}</strong> ·
          Asignado: <strong>{{ optional($ticket->assignee)->name ?: '—' }}</strong> ·
          Vence: <strong>{{ $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : '—' }}</strong>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-2">
          <span class="pill {{ $statusColor($ticket->status) }}">{{ $statuses[$ticket->status] ?? $ticket->status }}</span>
          <span class="pill">{{ $priorities[$ticket->priority] ?? $ticket->priority }}</span>
          <span class="pill">{{ $areas[$ticket->area] ?? ($ticket->area ?: 'Sin área') }}</span>
          <span class="pill {{ $slaClass }}">
            @if($sla==='overdue') Vencido
            @elseif($sla==='due_soon') Por vencer
            @elseif($sla==='ok') En tiempo
            @else Sin fecha
            @endif
          </span>
          <span class="pill">Score: {{ $ticket->score ?? '—' }}</span>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <a class="btnx" href="{{ route('tickets.index') }}">← Volver</a>

        @if(\Illuminate\Support\Facades\Route::has('tickets.work') && $canWork)
          <a class="btnx primary" href="{{ route('tickets.work',$ticket) }}">🚦 Trabajar</a>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('tickets.complete'))
          <form method="POST" action="{{ route('tickets.complete',$ticket) }}">
            @csrf
            <button class="btnx good" type="submit" onclick="return confirm('¿Marcar como completado?');">✅ Completar</button>
          </form>
        @endif

        @if(\Illuminate\Support\Facades\Route::has('tickets.cancel'))
          <form method="POST" action="{{ route('tickets.cancel',$ticket) }}">
            @csrf
            <button class="btnx danger" type="submit" onclick="return confirm('¿Cancelar este ticket?');">🛑 Cancelar</button>
          </form>
        @endif
      </div>
    </div>

    <div class="body">
      <div class="grid">

        {{-- Izquierda: lectura --}}
        <div class="cardx" style="box-shadow:none">
          <div class="sect" style="border-top:0">
            <h5>Información del ticket</h5>

            <div class="info">
              <div class="kv"><div class="k">Título</div><div class="v">{{ $ticket->title }}</div></div>
              <div class="kv"><div class="k">Descripción</div><div class="v">{{ $ticket->description ?: '—' }}</div></div>
              <div class="kv"><div class="k">Asignado a</div><div class="v">{{ optional($ticket->assignee)->name ?: '—' }}</div></div>
              <div class="kv"><div class="k">Área</div><div class="v">{{ $areas[$ticket->area] ?? ($ticket->area ?: '—') }}</div></div>
              <div class="kv"><div class="k">Prioridad</div><div class="v">{{ $priorities[$ticket->priority] ?? $ticket->priority }}</div></div>
              <div class="kv"><div class="k">Estatus</div><div class="v">{{ $statuses[$ticket->status] ?? $ticket->status }}</div></div>
              <div class="kv"><div class="k">Vencimiento</div><div class="v">{{ $ticket->due_at ? $ticket->due_at->format('Y-m-d H:i') : '—' }}</div></div>
              <div class="kv">
                <div class="k">Matriz (1–5)</div>
                <div class="v">
                  Impacto: <strong>{{ $ticket->impact ?? '—' }}</strong> ·
                  Urgencia: <strong>{{ $ticket->urgency ?? '—' }}</strong> ·
                  Esfuerzo: <strong>{{ $ticket->effort ?? '—' }}</strong>
                </div>
              </div>
              <div class="kv"><div class="k">Score</div><div class="v">{{ $ticket->score ?? '—' }}</div></div>

              @if($ticket->completed_at)
                <div class="kv"><div class="k">Completado</div><div class="v">{{ $ticket->completed_at->format('Y-m-d H:i') }}</div></div>
              @endif
              @if($ticket->cancelled_at)
                <div class="kv"><div class="k">Cancelado</div><div class="v">{{ $ticket->cancelled_at->format('Y-m-d H:i') }}</div></div>
              @endif
            </div>
          </div>
        </div>

        {{-- Derecha: adjuntos + comentarios + historial --}}
        <div>

          {{-- Adjuntos --}}
          <div class="cardx mb-3" style="box-shadow:none">
            <div class="sect" style="border-top:0">
              <h5>Adjuntos</h5>

              <form method="POST" action="{{ route('tickets.documents.store',$ticket) }}" enctype="multipart/form-data" class="mb-3">
                @csrf

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                  <div>
                    <label>Nombre</label>
                    <input class="input" name="name" placeholder="Ej. Evidencia, PDF, Captura…" value="{{ old('name') }}">
                  </div>
                  <div>
                    <label>Categoría</label>
                    <select name="category">
                      <option value="adjunto">Adjunto</option>
                      <option value="evidencia">Evidencia</option>
                      <option value="doc">Documento</option>
                      <option value="link">Link</option>
                    </select>
                  </div>
                </div>

                <div style="height:10px"></div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                  <div>
                    <label>Archivo</label>
                    <input class="input" type="file" name="file" accept="*/*">
                    <div class="sub">PDF, Word, Excel, imágenes, videos, etc.</div>
                  </div>
                  <div>
                    <label>o Link externo</label>
                    <input class="input" name="external_url" placeholder="https://..." value="{{ old('external_url') }}">
                  </div>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                  <button class="btnx primary" type="submit">Subir</button>
                </div>
              </form>

              @forelse($ticket->documents as $d)
                <div class="doc">
                  <div style="min-width:0">
                    <div class="name" style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                      {{ $d->name }}
                      <span class="pill" style="padding:4px 8px">v{{ $d->version ?? 1 }}</span>
                    </div>
                    <div class="meta">
                      {{ $d->category ?? 'adjunto' }}
                      · {{ optional($d->uploader)->name ?: '—' }}
                      · {{ optional($d->created_at)->format('Y-m-d H:i') }}
                    </div>
                    @if(!empty($d->external_url))
                      <div class="meta">
                        Link:
                        <a href="{{ $d->external_url }}" target="_blank" rel="noopener noreferrer">{{ $d->external_url }}</a>
                      </div>
                    @endif
                  </div>

                  <div class="d-flex gap-2">
                    @if($d->path)
                      <a class="btnx" href="{{ route('tickets.documents.download',[$ticket,$d]) }}">Descargar</a>
                    @endif

                    <form method="POST" action="{{ route('tickets.documents.destroy',[$ticket,$d]) }}" onsubmit="return confirm('¿Eliminar adjunto?');">
                      @csrf
                      @method('DELETE')
                      <button class="btnx danger" type="submit">Eliminar</button>
                    </form>
                  </div>
                </div>
              @empty
                <div class="sub">Aún no hay adjuntos.</div>
              @endforelse
            </div>
          </div>

          {{-- Comentarios --}}
          <div class="cardx mb-3" style="box-shadow:none">
            <div class="sect" style="border-top:0">
              <h5>Comentarios</h5>

              <form method="POST" action="{{ route('tickets.comments.store',$ticket) }}" class="mb-3">
                @csrf
                <label>Nuevo comentario (usa @nombre para mencionar)</label>
                <textarea name="body" class="mt-1" placeholder="Escribe aquí…">{{ old('body') }}</textarea>
                <div class="mt-2 d-flex justify-content-end">
                  <button class="btnx primary" type="submit">Publicar</button>
                </div>
              </form>

              @forelse($ticket->comments as $c)
                <div class="comment">
                  <div class="c-top">
                    <div class="c-name">{{ optional($c->user)->name ?: '—' }}</div>
                    <div class="c-time">{{ optional($c->created_at)->format('Y-m-d H:i') }}</div>
                  </div>
                  <div class="c-body">{{ $c->body }}</div>
                </div>
              @empty
                <div class="sub">Aún no hay comentarios.</div>
              @endforelse
            </div>
          </div>

          {{-- Historial --}}
          <div class="cardx" style="box-shadow:none">
            <div class="sect" style="border-top:0">
              <h5>Historial</h5>

              @forelse($ticket->audits as $a)
                @php
                  $label = $actionLabels[$a->action] ?? $a->action;
                  $diff  = (array) ($a->diff ?? []);

                  // Nuevo formato del controlador: diff['ticket'] + diff['files_uploaded'] + diff['files']
                  $tinfo = (array) ($diff['ticket'] ?? []);
                  $filesUploaded = $diff['files_uploaded'] ?? null;
                  $filesList = (array) ($diff['files'] ?? []);

                  // Compatibilidad con logs viejos:
                  $payload = (array) ($diff['payload'] ?? []);
                  if (isset($payload['files']) && is_array($payload['files'])) unset($payload['files']);
                @endphp

                <div class="audit">
                  <div class="a1">{{ $label }} · {{ $userName($a->user) }}</div>
                  <div class="a2">{{ optional($a->created_at)->format('Y-m-d H:i') }}</div>

                  {{-- Caso: ticket_created (nuevo) --}}
                  @if($a->action === 'ticket_created' && !empty($tinfo))
                    <div class="mt-2" style="display:grid; gap:8px;">
                      @foreach($tinfo as $k => $v)
                        @php
                          $val = $fmt($v);
                          if ($k === 'assignee') $val = $fmtAssignee($v);
                        @endphp
                        <div class="kv" style="background:#fff;">
                          <div class="k">{{ $prettyKey($k) }}</div>
                          <div class="v">{{ $val }}</div>
                        </div>
                      @endforeach

                      @if(!is_null($filesUploaded))
                        <div class="kv" style="background:#fff;">
                          <div class="k">Archivos subidos</div>
                          <div class="v">{{ (int)$filesUploaded }}</div>
                        </div>
                      @endif

                      @if(!empty($filesList))
                        <div class="kv" style="background:#fff;">
                          <div class="k">Archivos</div>
                          <div class="v">
                            @foreach($filesList as $f)
                              @php
                                $n = $f['name'] ?? 'Archivo';
                                $m = $f['mime'] ?? '';
                                $s = isset($f['size']) ? number_format(((int)$f['size'])/1024/1024, 2).' MB' : '';
                              @endphp
                              <div style="font-weight:900;">• {{ $n }} <span style="color:rgba(15,23,42,.55); font-weight:800;">{{ $m ? "({$m})" : '' }} {{ $s ? "· {$s}" : '' }}</span></div>
                            @endforeach
                          </div>
                        </div>
                      @endif
                    </div>
                  @endif

                  {{-- Caso: ticket_created (viejo) --}}
                  @if($a->action === 'ticket_created' && empty($tinfo) && !empty($payload))
                    <div class="mt-2" style="display:grid; gap:8px;">
                      @foreach($payload as $k => $v)
                        @php
                          $val = $fmt($v);
                          if ($k === 'assignee_id') $val = $fmtAssignee($v);
                        @endphp
                        <div class="kv" style="background:#fff;">
                          <div class="k">{{ $prettyKey($k) }}</div>
                          <div class="v">{{ $val }}</div>
                        </div>
                      @endforeach
                    </div>
                  @endif

                  {{-- Caso: ticket_updated (nuevo) --}}
                  @if($a->action === 'ticket_updated' && !empty($diff['before']) && !empty($diff['after']))
                    @php
                      $before = (array) $diff['before'];
                      $after  = (array) $diff['after'];
                      $keys = ['title','description','priority','area','status','assignee_id','due_at','impact','urgency','effort','score'];
                      $changes = [];
                      foreach ($keys as $k){
                        $b = $before[$k] ?? null;
                        $n = $after[$k] ?? null;
                        if ($b != $n) $changes[$k] = ['from'=>$b,'to'=>$n];
                      }
                    @endphp

                    @if(!empty($changes))
                      <div class="mt-2" style="border:1px solid var(--line); border-radius:14px; overflow:hidden; background:#fff;">
                        <div style="padding:10px 12px; font-weight:1000; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#f7f9ff);">
                          Cambios realizados
                        </div>
                        <div style="padding:10px 12px; display:grid; gap:8px;">
                          @foreach($changes as $k => $c)
                            @php
                              $from = $fmt($c['from']);
                              $to   = $fmt($c['to']);
                              if ($k === 'assignee_id') { $from = $fmtAssignee($c['from']); $to = $fmtAssignee($c['to']); }
                            @endphp
                            <div style="display:grid; grid-template-columns: 160px 1fr; gap:10px;">
                              <div class="k">{{ $prettyKey($k) }}</div>
                              <div class="v">
                                <span style="color:rgba(15,23,42,.55); font-weight:900;">{{ $from }}</span>
                                <span style="margin:0 8px; color:rgba(15,23,42,.35)">→</span>
                                <span>{{ $to }}</span>
                              </div>
                            </div>
                          @endforeach
                        </div>
                      </div>
                    @else
                      <div class="mt-2" style="font-weight:800; color:var(--muted);">Se guardó sin cambios visibles.</div>
                    @endif
                  @endif

                  {{-- Detalle opcional --}}
                  @if(!empty($a->diff))
                    <details style="margin-top:10px;">
                      <summary>Ver detalles (soporte)</summary>
                      <pre style="margin-top:8px;">{{ json_encode($a->diff, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                    </details>
                  @endif
                </div>
              @empty
                <div class="sub">Sin historial todavía.</div>
              @endforelse
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
@endsection