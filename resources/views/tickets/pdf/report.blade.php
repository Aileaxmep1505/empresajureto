<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte Ticket</title>
  <style>
    *{ box-sizing:border-box; }
    body{
      font-family: DejaVu Sans, Arial, sans-serif;
      color:#0f172a;
      font-size: 12px;
      margin: 24px;
    }
    .top{
      border:1px solid #e2e8f0;
      border-radius: 12px;
      padding: 14px 16px;
      margin-bottom: 14px;
    }
    .h1{
      font-size: 18px;
      margin: 0 0 6px 0;
      font-weight: 700;
    }
    .muted{ color:#64748b; }
    .row{ display:flex; gap: 12px; margin-top: 10px; }
    .col{ flex:1; }
    .card{
      border:1px solid #e2e8f0;
      border-radius: 12px;
      padding: 12px 14px;
      margin-bottom: 12px;
    }
    .title{
      font-weight: 700;
      margin:0 0 8px 0;
      font-size: 13px;
    }
    table{ width:100%; border-collapse: collapse; }
    th, td{
      border-bottom:1px solid #eef2f7;
      padding: 7px 6px;
      vertical-align: top;
    }
    th{
      text-align:left;
      font-weight:700;
      color:#334155;
      background:#f8fafc;
      border-top:1px solid #eef2f7;
    }
    .pill{
      display:inline-block;
      padding: 2px 8px;
      border-radius: 999px;
      border:1px solid #e2e8f0;
      background:#f8fafc;
      font-size: 11px;
      color:#334155;
    }
    .pill.ok{ background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
    .pill.bad{ background:#fef2f2; border-color:#fecaca; color:#991b1b; }
    .pill.warn{ background:#fffbeb; border-color:#fde68a; color:#92400e; }
    .k{ width: 180px; color:#64748b; font-weight:700; }
    .v{ color:#0f172a; }
    .small{ font-size: 11px; }
    .sep{ height: 10px; }
    .block{
      white-space:pre-wrap;
      background:#f8fafc;
      border:1px solid #eef2f7;
      border-radius: 10px;
      padding: 10px;
      color:#334155;
      line-height: 1.55;
    }
  </style>
</head>
<body>

@php
  /** @var \App\Models\Ticket $ticket */
  $ticket = $ticket ?? null;
  $fmtSecs = $fmtSecs ?? fn($s) => '00:00:00';

  $statusLabel = $statuses[$ticket->status] ?? ($ticket->status ?? '—');

  $cancelReason = '';
  try{
    $cancelReason = (string)($ticket->cancel_reason ?? '');
  }catch(\Throwable $e){ $cancelReason = ''; }

  $lastCancelReason = (string)($lastCancelReason ?? '');
  $lastReopenReason = (string)($lastReopenReason ?? '');
  $lastRejectReason = (string)($lastRejectReason ?? '');

  $evidences = $evidences ?? collect();
@endphp

<div class="top">
  <div class="h1">Reporte del Ticket: {{ $ticket->folio }} — {{ $ticket->title }}</div>
  <div class="muted small">
    Generado: {{ now()->format('d/m/Y H:i') }}
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Estado actual:
    <span class="pill">{{ $statusLabel }}</span>

    @if((string)$ticket->status === 'completado')
      &nbsp;&nbsp;<span class="pill ok">Cerrado</span>
    @elseif((string)$ticket->status === 'cancelado')
      &nbsp;&nbsp;<span class="pill bad">Cancelado</span>
    @elseif((string)$ticket->status === 'reabierto')
      &nbsp;&nbsp;<span class="pill warn">Reabierto</span>
    @elseif((string)$ticket->status === 'por_revisar')
      &nbsp;&nbsp;<span class="pill warn">En revisión final</span>
    @endif
  </div>
</div>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="title">Resumen</div>
      <table>
        <tr><td class="k">Prioridad</td><td class="v">{{ $priorities[$ticket->priority] ?? $ticket->priority }}</td></tr>
        <tr><td class="k">Área</td><td class="v">{{ $areas[$ticket->area] ?? $ticket->area }}</td></tr>
        <tr><td class="k">Encomendado a</td><td class="v">{{ optional($ticket->assignee)->name ?: 'Sin asignar' }}</td></tr>
        <tr><td class="k">Quién asigna / Creador</td><td class="v">{{ optional($ticket->creator)->name ?: '—' }}</td></tr>
        <tr><td class="k">Creado</td><td class="v">{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="k">Vence</td><td class="v">{{ $ticket->due_at ? $ticket->due_at->format('d/m/Y H:i') : 'N/A' }}</td></tr>
        <tr><td class="k">Completado</td><td class="v">{{ $ticket->completed_at ? $ticket->completed_at->format('d/m/Y H:i') : '—' }}</td></tr>
        <tr><td class="k">Cancelado</td><td class="v">{{ $ticket->cancelled_at ? $ticket->cancelled_at->format('d/m/Y H:i') : '—' }}</td></tr>
        <tr><td class="k">Score</td><td class="v">{{ $ticket->score ?? '—' }}</td></tr>
      </table>

      @if($cancelReason)
        <div class="sep"></div>
        <div class="title">Motivo de cancelación (guardado en el ticket)</div>
        <div class="block">{{ $cancelReason }}</div>
      @elseif($lastCancelReason)
        <div class="sep"></div>
        <div class="title">Motivo de cancelación (auditoría)</div>
        <div class="block">{{ $lastCancelReason }}</div>
      @endif

      @if($lastRejectReason)
        <div class="sep"></div>
        <div class="title">Motivo de rechazo en revisión</div>
        <div class="block">{{ $lastRejectReason }}</div>
      @endif

      @if($lastReopenReason)
        <div class="sep"></div>
        <div class="title">Motivo de reapertura</div>
        <div class="block">{{ $lastReopenReason }}</div>
      @endif
    </div>
  </div>

  <div class="col">
    <div class="card">
      <div class="title">Tiempos</div>
      <table>
        <tr>
          <td class="k">Tiempo total (servidor)</td>
          <td class="v">{{ $fmtSecs($totalSpan ?? 0) }}</td>
        </tr>
        <tr>
          <td class="k">Tiempo reportado UI (timer)</td>
          <td class="v">{{ $fmtSecs($uiElapsed ?? 0) }}</td>
        </tr>
      </table>

      <div class="sep"></div>

      <div class="title">Duración por etapa (servidor)</div>
      <table>
        <thead>
          <tr>
            <th>Etapa</th>
            <th>Desde</th>
            <th>Hasta</th>
            <th>Duración</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($segments ?? []) as $s)
            <tr>
              <td>{{ $statuses[$s['status']] ?? $s['status'] }}</td>
              <td class="small">{{ optional($s['from'])->format('d/m/Y H:i') }}</td>
              <td class="small">{{ optional($s['to'])->format('d/m/Y H:i') }}</td>
              <td>{{ $fmtSecs($s['secs'] ?? 0) }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">Sin información de etapas.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="title">Descripción</div>
  <div class="block">{{ $ticket->description ?: 'Sin descripción.' }}</div>
</div>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="title">Historial (auditoría)</div>
      <table>
        <thead>
          <tr>
            <th style="width:120px">Fecha</th>
            <th style="width:130px">Usuario</th>
            <th style="width:170px">Acción</th>
            <th>Detalle</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($audits ?? []) as $a)
            @php
              $diff = (array)($a->diff ?? []);
              $elapsed = (int) data_get($diff, 'elapsed_seconds', 0);
              $from = data_get($diff, 'from') ?? data_get($diff, 'before.status');
              $to   = data_get($diff, 'to')   ?? data_get($diff, 'after.status');
              $reason = (string) data_get($diff, 'reason', '');
              $note   = (string) data_get($diff, 'note', '');
              $docName = (string) data_get($diff, 'document.name', '');
              $docCat  = (string) data_get($diff, 'document.category', '');
            @endphp
            <tr>
              <td class="small">{{ optional($a->created_at)->format('d/m/Y H:i') }}</td>
              <td>{{ optional($a->user)->name ?: 'Sistema' }}</td>
              <td>{{ $a->action }}</td>
              <td class="small">
                @if($from || $to)
                  {{ $from ? (($statuses[$from] ?? $from).' → ') : '' }}{{ $to ? ($statuses[$to] ?? $to) : '' }}
                @endif

                @if($elapsed > 0)
                  <span class="muted"> | UI: {{ $fmtSecs($elapsed) }}</span>
                @endif

                @if($reason)
                  <div class="muted">Motivo: {{ $reason }}</div>
                @endif

                @if($note)
                  <div class="muted">Nota: {{ $note }}</div>
                @endif

                @if($docName)
                  <div class="muted">Archivo: {{ $docName }} @if($docCat) ({{ $docCat }}) @endif</div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">Sin auditoría.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="title">Comentarios</div>
      <table>
        <thead>
          <tr>
            <th style="width:120px">Fecha</th>
            <th style="width:140px">Usuario</th>
            <th>Comentario</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($ticket->comments ?? []) as $c)
            <tr>
              <td class="small">{{ optional($c->created_at)->format('d/m/Y H:i') }}</td>
              <td>{{ optional($c->user)->name ?: 'Usuario' }}</td>
              <td style="white-space:pre-wrap;">{{ $c->body }}</td>
            </tr>
          @empty
            <tr><td colspan="3" class="muted">Sin comentarios.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="col">
    <div class="card">
      <div class="title">Evidencias</div>
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Subido por</th>
            <th style="width:120px">Fecha</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($evidences ?? []) as $d)
            @php
              $mime = (string) data_get($d, 'meta.mime', $d->mime ?? '');
            @endphp
            <tr>
              <td>{{ $d->name }}</td>
              <td class="small">
                {{ $d->category ?: '—' }}
                @if($mime) <span class="muted">({{ $mime }})</span> @endif
              </td>
              <td>{{ optional($d->uploader)->name ?: '—' }}</td>
              <td class="small">{{ optional($d->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">Sin evidencias.</td></tr>
          @endforelse
        </tbody>
      </table>

      <div class="sep"></div>

      <div class="title">Archivos adjuntos (generales)</div>
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Subido por</th>
            <th style="width:120px">Fecha</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($attachments ?? []) as $d)
            @php
              $mime = (string) data_get($d, 'meta.mime', $d->mime ?? '');
            @endphp
            <tr>
              <td>{{ $d->name }}</td>
              <td class="small">
                {{ $d->category ?: '—' }}
                @if($mime) <span class="muted">({{ $mime }})</span> @endif
              </td>
              <td>{{ optional($d->uploader)->name ?: '—' }}</td>
              <td class="small">{{ optional($d->created_at)->format('d/m/Y H:i') }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">Sin archivos.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>