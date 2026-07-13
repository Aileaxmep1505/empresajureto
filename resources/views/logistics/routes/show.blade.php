@extends('layouts.app')
@section('title','Ruta #'.$routePlan->id)
@section('content_class', 'content--flush')
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
    /* Fuente corporativa */
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

    /* =========================
       Minimal / Modern UI
       Namespace: #route-show.rs
       ========================= */
    #route-show.rs{
      --bg:#f4f5f7;
      --card:#ffffff;
      --input-bg:#f9fafb;

      --ink-dark:#0f172a;
      --ink:#334155;
      --muted:#64748b;
      --muted-light:#94a3b8;

      --line:#e2e8f0;

      --blue:#007aff;
      --blue-soft:#eff6ff;

      --success:#15803d;
      --success-soft:#f0fdf4;

      --danger:#ef4444;
      --danger-soft:#fef2f2;

      --warning:#c2410c;
      --warning-soft:#fff7ed;

      --radius:16px;
      --shadow-card:0 4px 12px rgba(0,0,0,.02);
      --shadow-hover:0 12px 24px rgba(15,23,42,.06);

      font-family:'Quicksand', system-ui, -apple-system, 'Segoe UI', sans-serif;
      color:var(--ink);
      background:var(--bg);
      padding:36px 20px 48px;
      -webkit-font-smoothing:antialiased;
    }
    #route-show.rs *{ box-sizing:border-box; }
    #route-show.rs a{ color:inherit; text-decoration:none; }
    #route-show.rs a:hover{ color:var(--blue); text-decoration:none; }

    .wrap{ max-width:1200px; margin:0 auto; }

    /* Header */
    .head{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:12px;
      flex-wrap:wrap;
      margin-bottom:20px;
    }
    .title{
      font-weight:700;
      letter-spacing:-.02em;
      font-size:clamp(20px,2.2vw,28px);
      line-height:1.15;
      color:var(--ink-dark);
    }
    .sub{
      color:var(--muted);
      margin-top:8px;
      font-size:.95rem;
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      align-items:center;
      font-weight:500;
    }

    /* Buttons */
    .actions{ display:flex; gap:10px; flex-wrap:wrap; }
    .btnx{
      font-family:inherit;
      border:1px solid var(--line);
      background:var(--card);
      color:var(--ink);
      border-radius:999px;
      padding:.55rem .95rem;
      font-weight:600;
      font-size:.9rem;
      line-height:1;
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      transition:transform .12s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
      user-select:none;
      white-space:nowrap;
      cursor:pointer;
    }
    .btnx:hover{
      transform:translateY(-1px);
      background:#f9fafb;
      text-decoration:none;
    }
    .btnx:active{ transform:scale(.98); }
    .btnx.primary{
      background:var(--blue);
      border-color:var(--blue);
      color:#fff;
    }
    .btnx.primary:hover{
      background:#0069e0;
      border-color:#0069e0;
      color:#fff;
      box-shadow:0 8px 18px rgba(0,122,255,.22);
    }

    /* Cards */
    .card{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius);
      box-shadow:var(--shadow-card);
      overflow:hidden;
    }
    .card .hd{
      padding:14px 16px;
      border-bottom:1px solid var(--line);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:10px;
      background:#fbfcfe;
    }
    .card .hd .muted{ font-weight:600; }
    .card .hd strong{ color:var(--ink-dark); }
    .card .bd{ padding:18px; }

    /* Status chip (pastel + texto fuerte) */
    .chip{
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      padding:.28rem .7rem;
      border-radius:999px;
      border:1px solid var(--line);
      background:var(--card);
      font-weight:600;
      font-size:.82rem;
      white-space:nowrap;
    }
    .chip .dot{ width:7px; height:7px; border-radius:50%; background:currentColor; opacity:.65; }
    .chip.st-scheduled  { background:var(--blue-soft);    color:var(--blue);    border-color:#dbeafe; }
    .chip.st-draft      { background:#f1f5f9;             color:var(--muted);   border-color:#e2e8f0; }
    .chip.st-in_progress{ background:var(--warning-soft); color:var(--warning); border-color:#fed7aa; }
    .chip.st-done       { background:var(--success-soft); color:var(--success); border-color:#bbf7d0; }
    .chip.st-cancelled  { background:var(--danger-soft);  color:var(--danger);  border-color:#fecaca; }

    /* KPI row */
    .kpis{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:12px;
    }
    .kpi{
      border:1px solid var(--line);
      border-radius:12px;
      padding:14px 16px;
      background:var(--input-bg);
    }
    .kpi .lbl{
      color:var(--muted);
      font-size:.74rem;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:.04em;
    }
    .kpi .val{
      margin-top:6px;
      font-weight:700;
      font-size:1.2rem;
      letter-spacing:-.01em;
      color:var(--ink-dark);
    }
    .kpi .mini{
      margin-top:6px;
      color:var(--muted);
      font-size:.9rem;
      font-weight:500;
    }
    .kpi .mini strong{ color:var(--ink-dark); }

    /* Progress */
    .prog{
      height:8px;
      border-radius:999px;
      background:#eef1f5;
      overflow:hidden;
      margin-top:10px;
    }
    .prog > span{
      display:block;
      height:100%;
      width:0%;
      background:var(--blue);
      border-radius:999px;
      transition:width .6s ease;
    }

    /* Toolbar */
    .toolbar{
      display:flex;
      justify-content:space-between;
      align-items:center;
      flex-wrap:wrap;
      gap:12px;
      margin:22px 0 12px;
    }
    .search{
      display:flex;
      align-items:center;
      gap:.5rem;
      background:var(--input-bg);
      border:1px solid var(--line);
      border-radius:8px;
      padding:0 .8rem;
      height:42px;
      min-width:280px;
      transition:border-color .18s ease, box-shadow .18s ease;
    }
    .search:focus-within{
      border-color:var(--blue);
      box-shadow:0 0 0 3px var(--blue-soft);
    }
    .search input{
      border:0;
      outline:none;
      background:transparent;
      width:min(420px, 52vw);
      color:var(--ink);
      font-size:.95rem;
      font-family:inherit;
      font-weight:500;
      height:100%;
    }
    .search input::placeholder{ color:var(--muted-light); }
    .toolbar .muted{ font-weight:500; }
    .toolbar .muted strong{ color:var(--ink-dark); }
    .xbtn{
      border:0;
      background:transparent;
      padding:.2rem .4rem;
      border-radius:8px;
      cursor:pointer;
      color:var(--muted);
      display:none;
    }
    .xbtn:hover{ background:#eef1f5; color:var(--ink); }

    /* Table */
    .table-wrap{
      border:1px solid var(--line);
      border-radius:var(--radius);
      overflow:hidden;
      background:var(--card);
      box-shadow:var(--shadow-card);
    }
    table{ width:100%; border-collapse:separate; border-spacing:0; }
    thead th{
      text-align:left;
      font-weight:600;
      color:var(--muted);
      background:#fbfcfe;
      border-bottom:1px solid var(--line);
      padding:14px 16px;
      font-size:.82rem;
      text-transform:uppercase;
      letter-spacing:.02em;
    }
    tbody td{
      padding:16px;
      border-bottom:1px solid var(--line);
      vertical-align:middle;
      font-size:.95rem;
      font-weight:500;
    }
    tbody tr:last-child td{ border-bottom:0; }
    tbody tr:hover{ background:#fbfcfe; }
    .td-idx{ width:70px; color:var(--muted); }
    .muted{ color:var(--muted); font-size:.9rem; }
    .nowrap{ white-space:nowrap; }

    /* Stop badge */
    .badge{
      display:inline-flex;
      align-items:center;
      gap:.4rem;
      padding:.28rem .7rem;
      border-radius:999px;
      border:1px solid var(--line);
      font-weight:600;
      font-size:.82rem;
      background:var(--card);
      white-space:nowrap;
    }
    .badge.done   { background:var(--success-soft); border-color:#bbf7d0; color:var(--success); }
    .badge.pending{ background:#f1f5f9;             border-color:#e2e8f0; color:var(--muted); }
    .badge.skipped{ background:var(--warning-soft); border-color:#fed7aa; color:var(--warning); }
    .badge .dot{ background:currentColor; opacity:.6; }

    /* Lat/Lng display */
    .coord{
      display:flex;
      flex-direction:column;
      gap:3px;
      line-height:1.15;
    }
    .coord .addr{
      font-weight:600;
      color:var(--ink-dark);
      font-size:.92rem;
    }
    .coord .ll{
      color:var(--muted);
      font-size:.86rem;
    }

    /* Mobile cards */
    .mgrid{ display:grid; gap:12px; }
    .mcard{
      border:1px solid var(--line);
      border-radius:12px;
      padding:16px;
      background:var(--card);
      box-shadow:var(--shadow-card);
      transition:transform .18s ease, box-shadow .18s ease;
    }
    .mcard:hover{ transform:translateY(-2px); box-shadow:var(--shadow-hover); }
    .mrow{ display:flex; justify-content:space-between; gap:10px; align-items:flex-start; }
    .mname{ font-weight:700; color:var(--ink-dark); }
    .mmeta{ margin-top:6px; color:var(--muted); font-size:.92rem; font-weight:500; }
    .mmeta strong{ color:var(--ink-dark); }

    /* Responsive */
    .only-desktop{ display:block; }
    .only-mobile{ display:none; }
    @media (max-width: 991.98px){
      #route-show.rs{ padding:24px 14px 36px; }
      .kpis{ grid-template-columns:repeat(2,minmax(0,1fr)); }
      .only-desktop{ display:none; }
      .only-mobile{ display:block; }
      .actions{ width:100%; }
      .actions .btnx{ flex:1 1 auto; justify-content:center; }
      .search{ min-width:unset; width:100%; }
      .search input{ width:100%; }
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
        <div class="muted" style="font-weight:600;">Resumen</div>
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
                <td style="font-weight:700; color:var(--ink-dark);">{{ $s->name ?? '—' }}</td>
                <td>
                  <div class="coord">
                    @if($addr)
                      <div class="addr">{{ $addr }}</div>
                    @else
                      <div class="addr" style="color:var(--muted); font-weight:600;">Dirección no disponible</div>
                    @endif
                    <div class="ll">{{ $latTxt }}, {{ $lngTxt }}</div>
                  </div>
                </td>
                <td class="nowrap">{{ $eta }}</td>
                <td><span class="badge {{ $st }}"><span class="dot" style="width:6px;height:6px;border-radius:50%;"></span>{{ $stEs }}</span></td>
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
                  <div class="mmeta" style="color:var(--ink-dark); font-weight:600;">{{ $addr }}</div>
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