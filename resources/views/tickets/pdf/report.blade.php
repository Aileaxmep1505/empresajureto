<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Reporte Ticket</title>
  <style>
    *{ box-sizing:border-box; }
    @page { margin: 24px; }

    body{
      font-family: DejaVu Sans, Arial, sans-serif;
      color:#0f172a;
      font-size: 12px;
      margin: 0;
      line-height: 1.35;
      background: #ffffff;
    }

    /* ===== Tokens (PDF-safe) ===== */
    .wrap{
      --ink:#0b1220;
      --muted:#64748b;
      --line:#e7edf6;
      --soft:#f6f9ff;
      --soft2:#f8fafc;

      --brand:#2563eb;     /* azul */
      --brand2:#7c3aed;    /* violeta */
      --ok:#16a34a;
      --warn:#f59e0b;
      --bad:#ef4444;

      --r:14px;
      --shadow:none; /* dompdf no maneja sombras reales bien */
    }

    /* ===== Header ===== */
    .header{
      border:1px solid var(--line);
      border-radius: var(--r);
      padding: 14px 16px;
      margin-bottom: 12px;
      background: var(--soft);
    }

    .header-top{
      display:flex;
      gap:12px;
      align-items:flex-start;
      justify-content:space-between;
    }

    .h1{
      font-size: 17px;
      margin: 0;
      font-weight: 800;
      color: var(--ink);
    }

    .folio{
      font-weight: 800;
      color: var(--brand);
    }

    .sub{
      margin-top: 6px;
      color: var(--muted);
      font-size: 11px;
    }

    .badge-row{ margin-top: 10px; }

    .pill{
      display:inline-block;
      padding: 4px 10px;
      border-radius: 999px;
      border:1px solid var(--line);
      background:#fff;
      font-size: 11px;
      color:#0f172a;
      margin-right: 6px;
      margin-bottom: 6px;
      vertical-align: middle;
    }
    .pill strong{ font-weight:800; }
    .pill.brand{ border-color: #bfdbfe; background:#eff6ff; color:#1e3a8a; }
    .pill.purple{ border-color:#e9d5ff; background:#faf5ff; color:#581c87; }
    .pill.ok{ border-color:#bbf7d0; background:#ecfdf5; color:#065f46; }
    .pill.bad{ border-color:#fecaca; background:#fef2f2; color:#991b1b; }
    .pill.warn{ border-color:#fde68a; background:#fffbeb; color:#92400e; }

    .right-box{
      text-align:right;
      font-size: 11px;
      color: var(--muted);
      min-width: 190px;
    }
    .right-box .stamp{
      margin-top: 6px;
      display:inline-block;
      padding: 6px 10px;
      border-radius: 10px;
      border:1px solid var(--line);
      background:#fff;
      color:#334155;
      font-weight:700;
    }

    /* ===== Layout (PDF friendly) ===== */
    .row{
      display:flex;
      gap:12px;
      margin-bottom: 12px;
    }
    .col{ flex:1; min-width:0; }

    .card{
      border:1px solid var(--line);
      border-radius: var(--r);
      padding: 12px 14px;
      background:#fff;
      margin-bottom: 12px;
    }

    .card.soft{
      background: var(--soft2);
    }

    .title{
      font-weight: 900;
      margin: 0 0 8px 0;
      font-size: 12.5px;
      color:#0b1220;
      letter-spacing: .2px;
    }

    .divider{
      height: 1px;
      background: var(--line);
      margin: 10px 0;
    }

    table{ width:100%; border-collapse: collapse; }
    th, td{
      border-bottom:1px solid #eef2f7;
      padding: 7px 6px;
      vertical-align: top;
    }
    th{
      text-align:left;
      font-weight:900;
      color:#334155;
      background:#f1f5f9;
      border-top:1px solid #e8eef7;
      font-size: 11px;
    }

    .k{ width: 170px; color: var(--muted); font-weight: 800; }
    .v{ color: var(--ink); font-weight:700; }
    .small{ font-size: 11px; color: #334155; }
    .muted{ color: var(--muted); }

    .block{
      white-space:pre-wrap;
      background:#ffffff;
      border:1px solid #e8eef7;
      border-radius: 12px;
      padding: 10px;
      color:#334155;
      line-height: 1.6;
    }

    .block.note{
      background: #f8fafc;
      border-color: #e8eef7;
    }

    .block.success{
      background:#ecfdf5;
      border-color:#bbf7d0;
    }
    .block.warn{
      background:#fffbeb;
      border-color:#fde68a;
    }
    .block.danger{
      background:#fef2f2;
      border-color:#fecaca;
    }

    /* ===== Section helpers ===== */
    .two-cols{
      display:flex;
      gap:12px;
    }
    .two-cols .col{ flex:1; }

    .nowrap{ white-space:nowrap; }

    .footer{
      margin-top: 8px;
      color: var(--muted);
      font-size: 10.5px;
      text-align:center;
    }

    /* DomPDF sometimes breaks long tables oddly; help it */
    tr{ page-break-inside: avoid; }
    .card{ page-break-inside: avoid; }
  </style>
</head>

<body>
<div class="wrap">

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

  // ✅ NUEVO: detalle de finalización desde tickets.completion_detail (si existe)
  $completionDetail = '';
  try{
    $completionDetail = (string)($ticket->completion_detail ?? '');
  }catch(\Throwable $e){ $completionDetail = ''; }

  // ✅ NUEVO: extraer "completion_detail" guardado en auditoría (diff.note) en ticket_completed o ticket_review_approved
  $completionAuditNote = '';
  try{
    $completionAuditNote = collect($audits ?? [])
      ->reverse()
      ->first(function($a){
        return in_array((string)($a->action ?? ''), ['ticket_completed','ticket_review_approved'], true);
      });

    if ($completionAuditNote) {
      $d = (array)($completionAuditNote->diff ?? []);
      $completionAuditNote = (string) data_get($d, 'note', '');
    } else {
      $completionAuditNote = '';
    }
  }catch(\Throwable $e){ $completionAuditNote = ''; }

  $evidences = $evidences ?? collect();

  // ✅ prioridad pill color
  $prioKey = (string)($ticket->priority ?? '');
  $prioLabel = $priorities[$prioKey] ?? ($ticket->priority ?? '—');
  $prioPillClass = 'brand';
  if (in_array($prioKey, ['critica','alta'], true)) $prioPillClass = 'bad';
  elseif (in_array($prioKey, ['media'], true)) $prioPillClass = 'warn';
  else $prioPillClass = 'ok';

  // ✅ score
  $score = $ticket->score ?? null;
@endphp

  <!-- ===== Header ===== -->
  <div class="header">
    <div class="header-top">
      <div>
        <div class="h1">
          Reporte del Ticket <span class="folio">{{ $ticket->folio }}</span>
        </div>
        <div class="sub">
          {{ $ticket->title }}
        </div>

        <div class="badge-row">
          <span class="pill purple"><strong>Estado:</strong> {{ $statusLabel }}</span>
          <span class="pill {{ $prioPillClass }}"><strong>Prioridad:</strong> {{ $prioLabel }}</span>
          <span class="pill brand"><strong>Área:</strong> {{ $areas[$ticket->area] ?? $ticket->area }}</span>

          @if((string)$ticket->status === 'completado')
            <span class="pill ok"><strong>Cerrado</strong></span>
          @elseif((string)$ticket->status === 'cancelado')
            <span class="pill bad"><strong>Cancelado</strong></span>
          @elseif((string)$ticket->status === 'reabierto')
            <span class="pill warn"><strong>Reabierto</strong></span>
          @elseif((string)$ticket->status === 'por_revisar')
            <span class="pill warn"><strong>En revisión final</strong></span>
          @endif

          @if(!is_null($score))
            <span class="pill"><strong>Score:</strong> {{ $score }}</span>
          @endif
        </div>
      </div>

      <div class="right-box">
        <div>Generado: <span class="nowrap">{{ now()->format('d/m/Y H:i') }}</span></div>
        <div class="stamp">Grupo Medibuy · Tickets</div>
      </div>
    </div>
  </div>

  <!-- ===== Summary + Times ===== -->
  <div class="row">
    <div class="col">
      <div class="card">
        <div class="title">Resumen</div>
        <table>
          <tr><td class="k">Encomendado a</td><td class="v">{{ optional($ticket->assignee)->name ?: 'Sin asignar' }}</td></tr>
          <tr><td class="k">Creador</td><td class="v">{{ optional($ticket->creator)->name ?: '—' }}</td></tr>
          <tr><td class="k">Creado</td><td class="v">{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td></tr>
          <tr><td class="k">Vence</td><td class="v">{{ $ticket->due_at ? $ticket->due_at->format('d/m/Y H:i') : 'N/A' }}</td></tr>
          <tr><td class="k">Completado</td><td class="v">{{ $ticket->completed_at ? $ticket->completed_at->format('d/m/Y H:i') : '—' }}</td></tr>
          <tr><td class="k">Cancelado</td><td class="v">{{ $ticket->cancelled_at ? $ticket->cancelled_at->format('d/m/Y H:i') : '—' }}</td></tr>
        </table>

        @if($cancelReason)
          <div class="divider"></div>
          <div class="title">Motivo de cancelación</div>
          <div class="block danger">{{ $cancelReason }}</div>
        @elseif($lastCancelReason)
          <div class="divider"></div>
          <div class="title">Motivo de cancelación (auditoría)</div>
          <div class="block danger">{{ $lastCancelReason }}</div>
        @endif

        @if($lastRejectReason)
          <div class="divider"></div>
          <div class="title">Motivo de rechazo en revisión</div>
          <div class="block warn">{{ $lastRejectReason }}</div>
        @endif

        @if($lastReopenReason)
          <div class="divider"></div>
          <div class="title">Motivo de reapertura</div>
          <div class="block warn">{{ $lastReopenReason }}</div>
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

        <div class="divider"></div>

        <div class="title">Duración por etapa (servidor)</div>
        <table>
          <thead>
            <tr>
              <th>Etapa</th>
              <th style="width:120px">Desde</th>
              <th style="width:120px">Hasta</th>
              <th style="width:80px">Duración</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($segments ?? []) as $s)
              <tr>
                <td>{{ $statuses[$s['status']] ?? $s['status'] }}</td>
                <td class="small">{{ optional($s['from'])->format('d/m/Y H:i') }}</td>
                <td class="small">{{ optional($s['to'])->format('d/m/Y H:i') }}</td>
                <td class="v">{{ $fmtSecs($s['secs'] ?? 0) }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="muted">Sin información de etapas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ===== Description ===== -->
  <div class="card soft">
    <div class="title">Descripción</div>
    <div class="block note">{{ $ticket->description ?: 'Sin descripción.' }}</div>
  </div>

  <!-- ===== Completion detail (NEW) ===== -->
  @php
    $finalDetail = trim($completionDetail) !== '' ? $completionDetail : $completionAuditNote;
  @endphp
  @if(trim((string)$finalDetail) !== '')
    <div class="card">
      <div class="title">Detalle de finalización</div>
      <div class="block success">{{ $finalDetail }}</div>
    </div>
  @endif

  <!-- ===== Checklist (if loaded) ===== -->
  @php
    $cl = null;
    try{ $cl = optional($ticket->checklists)->sortByDesc('id')->first(); }catch(\Throwable $e){ $cl = null; }
  @endphp
  @if($cl && $cl->items && count($cl->items))
    <div class="card">
      <div class="title">Checklist ({{ $cl->title ?: 'Checklist' }})</div>
      <table>
        <thead>
          <tr>
            <th style="width:70px">Estado</th>
            <th>Actividad</th>
            <th style="width:140px">Responsable</th>
            <th style="width:120px">Fecha</th>
          </tr>
        </thead>
        <tbody>
          @foreach($cl->items->sortBy('sort_order') as $it)
            @php
              $done = (bool)($it->done ?? false);
              $by = '';
              try{ $by = $it->done_by ? (optional(\App\Models\User::find($it->done_by))->name ?: '') : ''; }catch(\Throwable $e){ $by=''; }
            @endphp
            <tr>
              <td>
                @if($done)
                  <span class="pill ok">Hecho</span>
                @else
                  <span class="pill warn">Pendiente</span>
                @endif
              </td>
              <td>
                <div class="v">{{ $it->title }}</div>
                @if(!empty($it->detail))
                  <div class="muted small">{{ $it->detail }}</div>
                @endif
                @if(!empty($it->evidence_note))
                  <div class="muted small">Nota: {{ $it->evidence_note }}</div>
                @endif
              </td>
              <td class="small">{{ $by ?: '—' }}</td>
              <td class="small">
                @if(!empty($it->done_at))
                  {{ \Carbon\Carbon::parse($it->done_at)->format('d/m/Y H:i') }}
                @else
                  —
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  <!-- ===== History ===== -->
  <div class="card">
    <div class="title">Historial (auditoría)</div>
    <table>
      <thead>
        <tr>
          <th style="width:118px">Fecha</th>
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
            <td class="small">{{ $a->action }}</td>
            <td class="small">
              @if($from || $to)
                <div>
                  <span class="pill purple">
                    {{ $from ? (($statuses[$from] ?? $from).' → ') : '' }}{{ $to ? ($statuses[$to] ?? $to) : '' }}
                  </span>
                </div>
              @endif

              @if($elapsed > 0)
                <div class="muted">UI: {{ $fmtSecs($elapsed) }}</div>
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

  <!-- ===== Comments + Evidence ===== -->
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
              <th style="width:120px">Tipo</th>
              <th style="width:130px">Subido por</th>
              <th style="width:118px">Fecha</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($evidences ?? []) as $d)
              @php $mime = (string) data_get($d, 'meta.mime', $d->mime ?? ''); @endphp
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

        <div class="divider"></div>

        <div class="title">Archivos adjuntos (generales)</div>
        <table>
          <thead>
            <tr>
              <th>Nombre</th>
              <th style="width:120px">Tipo</th>
              <th style="width:130px">Subido por</th>
              <th style="width:118px">Fecha</th>
            </tr>
          </thead>
          <tbody>
            @forelse(($attachments ?? []) as $d)
              @php $mime = (string) data_get($d, 'meta.mime', $d->mime ?? ''); @endphp
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

  <div class="footer">
    Documento generado automáticamente. Si hay diferencias, la auditoría del sistema es la fuente de verdad.
  </div>

</div>
</body>
</html>