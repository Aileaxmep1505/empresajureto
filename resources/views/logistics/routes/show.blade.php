@extends('layouts.app')
@section('title','Ruta #'.$routePlan->id)

@section('content')
@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;

  $status = $routePlan->status ?? 'scheduled';
  $statusHuman = [
    'draft'       => 'Borrador',
    'scheduled'   => 'Programada',
    'in_progress' => 'En progreso',
    'done'        => 'Completada',
    'cancelled'   => 'Cancelada',
  ][$status] ?? ucfirst($status);

  $planned = $routePlan->planned_at ? Carbon::parse($routePlan->planned_at) : null;

  $stopsSorted = $routePlan->stops
    ->sortBy(function($s){
      $si = $s->sequence_index ?? 999999;
      return sprintf('%06d-%06d', $si, $s->id);
    })
    ->values();

  $totalStops = $routePlan->stops_count ?? $stopsSorted->count();
  $doneStops  = $routePlan->done_stops_count ?? $stopsSorted->where('status','done')->count();
  $pending    = max(0, $totalStops - $doneStops);
  $pct        = $totalStops ? intval(($doneStops / $totalStops) * 100) : 0;

  $stopLabel = ['done'=>'Hecho','pending'=>'Pendiente','skipped'=>'Omitida'];

  $driverLabel = $routePlan->driver->name ?? ($routePlan->driver->email ?? 'N/D');
@endphp

<div id="route-show" class="rs">
  <style>
    /* =========================
       Minimal / Modern UI
       Namespace: #route-show.rs
       ========================= */
    #route-show.rs{
      --ink:#0b1220;
      --muted:#6b7280;
      --line:#e7eef7;
      --bg:#f7f9fc;
      --card:#ffffff;

      --brand:#a6d3ff;
      --brand-ink:#0b1220;

      --ok:#c6f6d5;
      --warn:#ffe8b2;
      --danger:#ffd6e7;
      --info:#b7f0e2;

      --radius:16px;
      --shadow:0 14px 40px rgba(2,8,23,.08);

      color:var(--ink);
      background:var(--bg);
      padding:20px 14px;
    }
    #route-show.rs *{ box-sizing:border-box; }
    #route-show.rs a{ color:inherit; text-decoration:none; }
    #route-show.rs a:hover{ text-decoration:underline; }

    .wrap{ max-width:1200px; margin:0 auto; }

    /* Header */
    .head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:14px;
    }
    .title{
      font-weight:900;
      letter-spacing:.2px;
      font-size:clamp(18px,2.2vw,26px);
      line-height:1.1;
    }
    .sub{
      color:var(--muted);
      margin-top:6px;
      font-size:.95rem;
      display:flex;
      flex-wrap:wrap;
      gap:10px;
      align-items:center;
    }

    /* Buttons */
    .actions{ display:flex; gap:8px; flex-wrap:wrap; }
    .btnx{
      border:1px solid var(--line);
      background:var(--card);
      color:var(--ink);
      border-radius:999px;
      padding:.48rem .85rem;
      font-weight:800;
      line-height:1;
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      transition:.18s;
      box-shadow:0 6px 18px rgba(2,8,23,.06);
      user-select:none;
      white-space:nowrap;
    }
    .btnx:hover{
      transform:translateY(-1px);
      background:#fff;
      box-shadow:var(--shadow);
      border-color:#d6ecff;
      text-decoration:none;
    }
    .btnx.primary{
      background:var(--brand);
      border-color:#d6ecff;
      color:var(--brand-ink);
    }

    /* Cards */
    .card{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
    }
    .card .hd{
      padding:12px 14px;
      border-bottom:1px solid var(--line);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      background:linear-gradient(180deg,#fbfdff,transparent);
    }
    .card .bd{ padding:14px; }

    /* Status chip */
    .chip{
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      padding:.22rem .65rem;
      border-radius:999px;
      border:1px solid var(--line);
      background:#fff;
      font-weight:900;
      font-size:.82rem;
      white-space:nowrap;
    }
    .dot{ width:8px; height:8px; border-radius:50%; background:#94a3b8; }
    .st-scheduled .dot{ background:var(--info); }
    .st-draft .dot{ background:#cbd5e1; }
    .st-in_progress .dot{ background:var(--warn); }
    .st-done .dot{ background:var(--ok); }
    .st-cancelled .dot{ background:var(--danger); }

    /* KPI row */
    .kpis{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:10px;
    }
    .kpi{
      border:1px solid var(--line);
      border-radius:14px;
      padding:10px 12px;
      background:#fff;
    }
    .kpi .lbl{
      color:var(--muted);
      font-size:.74rem;
      font-weight:800;
      text-transform:uppercase;
      letter-spacing:.04em;
    }
    .kpi .val{
      margin-top:4px;
      font-weight:900;
      font-size:1.15rem;
      letter-spacing:.2px;
    }
    .kpi .mini{
      margin-top:4px;
      color:var(--muted);
      font-size:.9rem;
    }

    /* Progress */
    .prog{
      height:10px;
      border-radius:999px;
      background:#eef2f7;
      overflow:hidden;
      border:1px solid #eef2f7;
      margin-top:8px;
    }
    .prog > span{
      display:block;
      height:100%;
      width:0%;
      background:linear-gradient(90deg,#cfe4ff,#7fb7ff);
      transition:width .6s ease;
    }

    /* Toolbar */
    .toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      flex-wrap:wrap;
      gap:10px;
      margin:14px 0 10px;
    }
    .search{
      display:flex;
      align-items:center;
      gap:.5rem;
      background:#fff;
      border:1px solid var(--line);
      border-radius:999px;
      padding:.42rem .7rem;
      box-shadow:0 8px 22px rgba(2,8,23,.04);
      min-width:260px;
    }
    .search input{
      border:0;
      outline:none;
      background:transparent;
      width:min(420px, 52vw);
      color:var(--ink);
      font-size:.95rem;
    }
    .xbtn{
      border:0;
      background:transparent;
      padding:.2rem .35rem;
      border-radius:999px;
      cursor:pointer;
      color:var(--muted);
      display:none;
    }
    .xbtn:hover{ background:#f3f6fb; color:var(--ink); }

    /* Table */
    .table-wrap{
      border:1px solid var(--line);
      border-radius:var(--radius);
      overflow:hidden;
      background:#fff;
    }
    table{ width:100%; border-collapse:separate; border-spacing:0; }
    thead th{
      text-align:left;
      font-weight:900;
      color:#334155;
      background:#f9fbff;
      border-bottom:1px solid var(--line);
      padding:10px 12px;
      font-size:.92rem;
    }
    tbody td{
      padding:12px;
      border-bottom:1px solid #f1f5f9;
      vertical-align:middle;
      font-size:.95rem;
    }
    tbody tr:hover{ background:#f8fbff; }
    .td-idx{ width:70px; color:var(--muted); }
    .muted{ color:var(--muted); font-size:.9rem; }
    .nowrap{ white-space:nowrap; }

    /* Stop badge */
    .badge{
      display:inline-flex;
      align-items:center;
      gap:.4rem;
      padding:.18rem .65rem;
      border-radius:999px;
      border:1px solid var(--line);
      font-weight:900;
      font-size:.82rem;
      background:#fff;
      white-space:nowrap;
    }
    .badge.done{ background:#eefcf7; border-color:#bbf7d0; color:#065f46; }
    .badge.pending{ background:#f8fafc; border-color:#e5e7eb; color:#111827; }
    .badge.skipped{ background:#fff7ed; border-color:#fed7aa; color:#92400e; }

    /* Lat/Lng display -> más útil que “0.000000,0.000000” */
    .coord{
      display:flex;
      flex-direction:column;
      gap:2px;
      line-height:1.1;
    }
    .coord .addr{
      font-weight:800;
      color:#111827;
      font-size:.92rem;
    }
    .coord .ll{
      color:var(--muted);
      font-size:.86rem;
    }

    /* Mobile cards */
    .mgrid{ display:grid; gap:10px; }
    .mcard{
      border:1px solid var(--line);
      border-radius:14px;
      padding:10px 12px;
      background:#fff;
      box-shadow:0 10px 24px rgba(2,8,23,.05);
    }
    .mrow{ display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
    .mname{ font-weight:900; }
    .mmeta{ margin-top:6px; color:var(--muted); font-size:.92rem; }

    /* Responsive */
    .only-desktop{ display:block; }
    .only-mobile{ display:none; }
    @media (max-width: 991.98px){
      #route-show.rs{ padding:18px 12px; }
      .kpis{ grid-template-columns:repeat(2,minmax(0,1fr)); }
      .only-desktop{ display:none; }
      .only-mobile{ display:block; }
    }
  </style>

  <div class="wrap">
    {{-- Header --}}
    <div class="head">
      <div>
        <div class="title">{{ $routePlan->name ?? ('Ruta #'.$routePlan->id) }}</div>
        <div class="sub">
          <span class="chip st-{{ $status }}"><span class="dot"></span>{{ $statusHuman }}</span>
          <span class="muted"><i class="bi bi-person"></i> {{ $driverLabel }}</span>
          @if($planned)
            <span class="muted nowrap"><i class="bi bi-calendar-event"></i> {{ $planned->format('Y-m-d H:i') }}</span>
          @endif
          <span class="muted nowrap"><i class="bi bi-geo"></i> {{ $totalStops }} paradas</span>
        </div>
      </div>

      <div class="actions">
        <a href="{{ route('driver.routes.show',$routePlan) }}" class="btnx primary">
          <i class="bi bi-phone"></i> Ver como chofer
        </a>
        <a href="{{ route('routes.index') }}" class="btnx">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    {{-- Resumen / KPIs --}}
    <div class="card" style="margin-bottom:12px;">
      <div class="hd">
        <div class="muted" style="font-weight:800;">Resumen</div>
        <div class="muted">ID: <strong>#{{ $routePlan->id }}</strong></div>
      </div>
      <div class="bd">
        <div class="kpis">
          <div class="kpi">
            <div class="lbl">Progreso</div>
            <div class="val">{{ $pct }}%</div>
            <div class="prog" title="{{ $pct }}%">
              <span id="rsProgBar" style="width: {{ $pct }}%"></span>
            </div>
            <div class="mini"><strong>{{ $doneStops }}</strong> / {{ $totalStops }} hechas</div>
          </div>

          <div class="kpi">
            <div class="lbl">Pendientes</div>
            <div class="val">{{ $pending }}</div>
            <div class="mini">Paradas por completar</div>
          </div>

          <div class="kpi">
            <div class="lbl">Estado</div>
            <div class="val">{{ $statusHuman }}</div>
            <div class="mini">Actual</div>
          </div>

          <div class="kpi">
            <div class="lbl">Chofer</div>
            <div class="val" style="font-size:1rem;">{{ Str::limit($driverLabel, 22) }}</div>
            <div class="mini">Asignado</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Toolbar --}}
    <div class="toolbar">
      <div class="search">
        <i class="bi bi-search" style="color:var(--muted)"></i>
        <input id="rsQ" type="text" placeholder="Buscar parada (nombre / estado)…">
        <button id="rsClear" class="xbtn" type="button" title="Limpiar">✕</button>
      </div>
      <div class="muted">Mostrando: <strong id="rsCount">{{ $stopsSorted->count() }}</strong></div>
    </div>

    {{-- Stops: Desktop --}}
    <div class="only-desktop">
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th class="td-idx">#</th>
              <th>Punto</th>
              <th>Ubicación</th>
              <th class="nowrap">ETA</th>
              <th>Estatus</th>
            </tr>
          </thead>
          <tbody id="rsTbody">
            @foreach($stopsSorted as $s)
              @php
                $idx = $s->sequence_index !== null ? ($s->sequence_index + 1) : '—';
                $eta = $s->eta_seconds ? (int) round($s->eta_seconds/60) . ' min' : '—';
                $st  = $s->status ?? 'pending';
                $stEs = $stopLabel[$st] ?? ucfirst($st);

                $lat = is_numeric($s->lat ?? null) ? (float)$s->lat : null;
                $lng = is_numeric($s->lng ?? null) ? (float)$s->lng : null;

                // Si tienes columna address en route_stops úsala, si no, quedará vacío.
                $addr = trim((string)($s->address ?? ''));

                // Si por alguna razón te quedaron 0,0 viejos, se ven como "—"
                $latTxt = ($lat !== null && abs($lat) > 0.000001) ? number_format($lat, 6) : '—';
                $lngTxt = ($lng !== null && abs($lng) > 0.000001) ? number_format($lng, 6) : '—';

                $hay = Str::of(trim(($s->name ?? 'punto').' '.$stEs.' '.$addr.' '.$latTxt.' '.$lngTxt))->lower()->ascii();
              @endphp
              <tr data-hay="{{ $hay }}">
                <td class="td-idx">#{{ $idx }}</td>
                <td style="font-weight:900;">{{ $s->name ?? '—' }}</td>
                <td>
                  <div class="coord">
                    @if($addr)
                      <div class="addr">{{ $addr }}</div>
                    @else
                      <div class="addr" style="color:var(--muted); font-weight:800;">Dirección no disponible</div>
                    @endif
                    <div class="ll">{{ $latTxt }}, {{ $lngTxt }}</div>
                  </div>
                </td>
                <td class="nowrap">{{ $eta }}</td>
                <td><span class="badge {{ $st }}"><span class="dot" style="width:7px;height:7px;"></span>{{ $stEs }}</span></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{-- Stops: Mobile --}}
    <div class="only-mobile">
      <div id="rsCards" class="mgrid">
        @foreach($stopsSorted as $s)
          @php
            $idx = $s->sequence_index !== null ? ($s->sequence_index + 1) : '—';
            $eta = $s->eta_seconds ? (int) round($s->eta_seconds/60) . ' min' : '—';
            $st  = $s->status ?? 'pending';
            $stEs = $stopLabel[$st] ?? ucfirst($st);

            $lat = is_numeric($s->lat ?? null) ? (float)$s->lat : null;
            $lng = is_numeric($s->lng ?? null) ? (float)$s->lng : null;

            $addr = trim((string)($s->address ?? ''));

            $latTxt = ($lat !== null && abs($lat) > 0.000001) ? number_format($lat, 6) : '—';
            $lngTxt = ($lng !== null && abs($lng) > 0.000001) ? number_format($lng, 6) : '—';

            $hay = Str::of(trim(($s->name ?? 'punto').' '.$stEs.' '.$addr.' '.$latTxt.' '.$lngTxt))->lower()->ascii();
          @endphp
          <div class="mcard" data-hay="{{ $hay }}">
            <div class="mrow">
              <div style="min-width:0;">
                <div class="mname">#{{ $idx }} · {{ $s->name ?? '—' }}</div>

                @if($addr)
                  <div class="mmeta" style="color:#111827; font-weight:800;">{{ $addr }}</div>
                @else
                  <div class="mmeta">Dirección no disponible</div>
                @endif

                <div class="mmeta">{{ $latTxt }}, {{ $lngTxt }}</div>
                <div class="mmeta"><strong>ETA:</strong> {{ $eta }}</div>
              </div>
              <span class="badge {{ $st }}">{{ $stEs }}</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

  </div>

  {{-- Icons --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

  <script>
    (function(){
      const root = document.getElementById('route-show');
      if(!root) return;

      // Progress animate
      const bar = document.getElementById('rsProgBar');
      if(bar){
        const w = bar.style.width || '0%';
        bar.style.width = '0%';
        requestAnimationFrame(()=>{ bar.style.width = w; });
      }

      // Search
      const q = document.getElementById('rsQ');
      const clear = document.getElementById('rsClear');
      const tbody = document.getElementById('rsTbody');
      const cardsWrap = document.getElementById('rsCards');
      const countEl = document.getElementById('rsCount');

      const rows = tbody ? Array.from(tbody.querySelectorAll('tr')) : [];
      const cards = cardsWrap ? Array.from(cardsWrap.querySelectorAll('.mcard')) : [];

      const norm = (s)=> (s||'').toString().toLowerCase().trim();

      function filter(){
        const needle = norm(q.value);
        if(clear) clear.style.display = needle ? '' : 'none';

        let shown = 0;

        if(rows.length){
          rows.forEach(r=>{
            const hay = norm(r.getAttribute('data-hay'));
            const ok = hay.includes(needle);
            r.style.display = ok ? '' : 'none';
            if(ok) shown++;
          });
        }

        if(cards.length){
          shown = 0;
          cards.forEach(c=>{
            const hay = norm(c.getAttribute('data-hay'));
            const ok = hay.includes(needle);
            c.style.display = ok ? '' : 'none';
            if(ok) shown++;
          });
        }

        if(countEl) countEl.textContent = shown;
      }

      if(q) q.addEventListener('input', filter);
      if(clear) clear.addEventListener('click', ()=>{
        q.value = '';
        filter();
        q.focus();
      });

      filter();
    })();
  </script>
</div>
@endsection
