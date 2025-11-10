@extends('layouts.app')
@section('title','Ruta #'.$routePlan->id)

@section('content')
<div id="route-show" class="rs-wrap">
  {{-- ================== ESTILOS ENCAPSULADOS ================== --}}
  <style>
    #route-show{
      --rs-ink:#0e1726; --rs-muted:#6b7280; --rs-line:#e5e7eb; --rs-bg:#f5f7fb; --rs-card:#ffffff;
      --rs-brand:#2563eb; --rs-ok:#16a34a; --rs-warn:#f59e0b; --rs-danger:#ef4444; --rs-info:#0ea5e9;
      font-family: system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Inter",sans-serif;
      color:var(--rs-ink); background:var(--rs-bg);
      padding:24px 16px;
    }
    #route-show a{ color:var(--rs-brand); text-decoration:none }
    #route-show a:hover{ text-decoration:underline }

    #route-show .rs-container{ max-width:1200px; margin:0 auto }
    /* Titlebar */
    #route-show .rs-titlebar{ display:flex; gap:12px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:16px }
    #route-show .rs-title{ font-weight:800; font-size:clamp(18px,3vw,24px); letter-spacing:.2px }
    #route-show .rs-actions{ display:flex; gap:8px; flex-wrap:wrap }
    #route-show .rs-btn{
      appearance:none; border:1px solid var(--rs-line); background:var(--rs-card); color:var(--rs-ink);
      padding:.5rem .75rem; border-radius:10px; font-weight:700; line-height:1; cursor:pointer;
      display:inline-flex; align-items:center; gap:.5rem;
      transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
    }
    #route-show .rs-btn:hover{ transform:translateY(-1px); box-shadow:0 10px 24px rgba(2,8,23,.06); border-color:#dbeafe }
    #route-show .rs-btn--primary{ background:var(--rs-brand); color:#fff; border-color:var(--rs-brand) }
    #route-show .rs-btn--primary:hover{ box-shadow:0 12px 28px rgba(37,99,235,.25) }

    /* Header card */
    #route-show .rs-card{
      border:1px solid var(--rs-line); background:var(--rs-card); border-radius:16px;
      padding:14px; box-shadow:0 12px 32px rgba(2,8,23,.06);
    }
    #route-show .rs-grid{ display:grid; gap:12px }
    #route-show .g3{ grid-template-columns:repeat(3,minmax(0,1fr)) }

    #route-show .rs-meta{ color:var(--rs-muted); font-size:.92rem }
    #route-show .rs-kpis{ display:grid; gap:10px; grid-template-columns:repeat(4,minmax(0,1fr)) }
    #route-show .rs-kpi{ border:1px solid var(--rs-line); border-radius:12px; padding:.65rem .8rem; background:#fff }
    #route-show .rs-kpi .lbl{ font-size:.73rem; color:var(--rs-muted); text-transform:uppercase; letter-spacing:.02em }
    #route-show .rs-kpi .val{ font-weight:800; font-size:1.15rem; margin-top:.15rem }

    /* Chips de estado */
    #route-show .rs-chip{display:inline-flex; align-items:center; gap:.4rem; font-weight:700; font-size:.8rem;
      border-radius:999px; padding:.2rem .6rem; border:1px solid var(--rs-line); background:#fff}
    #route-show .rs-dot{width:8px;height:8px;border-radius:50%;background:#94a3b8}
    #route-show .status-scheduled .rs-dot{background:var(--rs-info)}
    #route-show .status-in_progress .rs-dot{background:var(--rs-warn)}
    #route-show .status-done .rs-dot{background:var(--rs-ok)}
    #route-show .status-cancelled .rs-dot{background:var(--rs-danger)}

    /* Progreso */
    #route-show .rs-prog{ height:10px; background:#eef2f7; border-radius:999px; overflow:hidden }
    #route-show .rs-prog > span{ display:block; height:100%; width:0%; background:linear-gradient(90deg,#60a5fa,#2563eb) }

    /* Search */
    #route-show .rs-toolbar{ display:flex; align-items:center; gap:10px; margin:14px 0 10px }
    #route-show .rs-search{ display:flex; align-items:center; gap:.5rem; background:#fff; border:1px solid var(--rs-line);
      border-radius:12px; padding:.4rem .6rem; min-width:260px }
    #route-show .rs-search input{ border:0; outline:none; width:220px; font-size:.95rem; color:var(--rs-ink) }
    #route-show .rs-icon{ color:var(--rs-muted) }

    /* Tabla / tarjetas de paradas */
    #route-show .rs-table-wrap{ border:1px solid var(--rs-line); background:#fff; border-radius:16px; overflow:hidden }
    #route-show table{ width:100%; border-collapse:separate; border-spacing:0 }
    #route-show thead th{ background:#f9fafb; border-bottom:1px solid var(--rs-line); padding:10px 12px; color:#334155; font-weight:700; text-align:left }
    #route-show tbody td{ padding:12px; border-bottom:1px solid #f1f5f9; vertical-align:middle }
    #route-show tbody tr:hover{ background:#f8fafc }
    #route-show .td-idx{ width:60px }

    /* badge de parada */
    #route-show .rs-badge{ font-weight:700; border-radius:999px; padding:.18rem .6rem; border:1px solid var(--rs-line); background:#fff }
    #route-show .rs-badge.done{ color:#065f46; background:#dcfce7; border-color:#bbf7d0 }
    #route-show .rs-badge.pending{ color:#111827; background:#f3f4f6; border-color:#e5e7eb }
    #route-show .rs-badge.skipped{ color:#92400e; background:#ffedd5; border-color:#fed7aa }

    /* Mobile cards */
    #route-show .rs-mgrid{ display:grid; gap:10px }
    #route-show .rs-mcard{ border:1px solid var(--rs-line); background:#fff; border-radius:14px; padding:10px }
    #route-show .rs-mrow{ display:flex; justify-content:space-between; align-items:center; gap:10px }
    #route-show .rs-muted{ color:var(--rs-muted); font-size:.9rem }

    /* Responsive */
    @media (max-width: 991.98px){
      #route-show{ padding:18px 12px }
      #route-show .rs-kpis{ grid-template-columns:repeat(2,minmax(0,1fr)) }
      #route-show .only-desktop{ display:none!important }
      #route-show .only-mobile{ display:block!important }
    }
    @media (min-width: 992px){
      #route-show .only-desktop{ display:block!important }
      #route-show .only-mobile{ display:none!important }
    }

    /* Animaciones */
    #route-show .fade-in{ animation:rs-fade .28s ease both }
    @keyframes rs-fade{ from{opacity:0; transform:translateY(4px)} to{opacity:1; transform:translateY(0)} }
  </style>

  <div class="rs-container fade-in">
    {{-- ============ TITLE / ACTIONS ============ --}}
    <div class="rs-titlebar">
      <div class="rs-title">
        {{ $routePlan->name ?? 'Ruta #'.$routePlan->id }}
      </div>
      <div class="rs-actions">
        <a href="{{ route('driver.routes.show',$routePlan) }}" class="rs-btn rs-btn--primary">
          <i class="bi bi-phone"></i> Ver como chofer
        </a>
        <a href="{{ route('routes.index') }}" class="rs-btn">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    {{-- ============ HEADER SUMMARY ============ --}}
    @php
      $status = $routePlan->status ?? 'scheduled';
      $statusHuman = [
        'draft'       => 'Borrador',
        'scheduled'   => 'Programada',
        'in_progress' => 'En progreso',
        'done'        => 'Completada',
        'cancelled'   => 'Cancelada',
      ][$status] ?? ucfirst($status);

      $totalStops = $routePlan->stops_count ?? $routePlan->stops->count();
      $doneStops  = $routePlan->done_stops_count ?? $routePlan->stops->where('status','done')->count();
      $pending    = max(0, $totalStops - $doneStops);
      $pct        = $totalStops ? intval(($doneStops/$totalStops)*100) : 0;

      // Etiquetas en español para paradas:
      $stopLabel = ['done'=>'Hecho','pending'=>'Pendiente','skipped'=>'Omitida'];
    @endphp

    <div class="rs-card rs-grid g3">
      <div style="grid-column:1/-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="rs-chip status-{{ $status }}"><span class="rs-dot"></span>{{ $statusHuman }}</span>
          <span class="rs-meta"><i class="bi bi-person"></i> Chofer: <strong>{{ $routePlan->driver->name ?? 'N/D' }}</strong></span>
          @if($routePlan->planned_at)
            <span class="rs-meta"><i class="bi bi-calendar-event"></i> Programada: {{ $routePlan->planned_at->format('Y-m-d H:i') }}</span>
          @endif
          <span class="rs-meta"><i class="bi bi-geo"></i> Paradas: <strong>{{ $totalStops }}</strong></span>
        </div>
      </div>

      <div class="rs-kpis" style="grid-column:1/-1">
        <div class="rs-kpi">
          <div class="lbl">Progreso</div>
          <div class="rs-prog" title="{{ $pct }}%"><span style="width: {{ $pct }}%"></span></div>
          <div class="rs-meta mt-1"><strong>{{ $doneStops }}</strong> / {{ $totalStops }} hechas</div>
        </div>
        <div class="rs-kpi">
          <div class="lbl">Pendientes</div>
          <div class="val">{{ $pending }}</div>
          <div class="rs-meta">Paradas por completar</div>
        </div>
        <div class="rs-kpi">
          <div class="lbl">Estado</div>
          <div class="val">{{ $statusHuman }}</div>
          <div class="rs-meta">Actual</div>
        </div>
        <div class="rs-kpi">
          <div class="lbl">ID ruta</div>
          <div class="val">#{{ $routePlan->id }}</div>
          <div class="rs-meta">Referencia</div>
        </div>
      </div>
    </div>

    {{-- ============ TOOLBAR PARADAS ============ --}}
    <div class="rs-toolbar">
      <div class="rs-search">
        <i class="bi bi-search rs-icon"></i>
        <input id="rs-q" type="text" placeholder="Buscar parada por nombre o estado… (Hecho, Pendiente, Omitida)">
        <button id="rs-clear" class="rs-btn" style="padding:.25rem .45rem"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="rs-meta">Total: {{ $totalStops }}</div>
    </div>

    {{-- ============ PARADAS (tabla desktop / tarjetas mobile) ============ --}}
    <div class="only-desktop">
      <div class="rs-table-wrap">
        <table id="rs-table">
          <thead>
            <tr>
              <th class="td-idx">#</th>
              <th>Punto</th>
              <th>Lat / Lng</th>
              <th>ETA</th>
              <th>Estatus</th>
            </tr>
          </thead>
          <tbody id="rs-tbody">
          @foreach($routePlan->stops as $s)
            @php
              $idx = $s->sequence_index !== null ? $s->sequence_index + 1 : '—';
              $eta = $s->eta_seconds ? round($s->eta_seconds/60).' min' : '—';
              $st  = $s->status ?? 'pending';
              $stEs = $stopLabel[$st] ?? ucfirst($st);
              $searchHay = Str::lower(trim(($s->name ?? 'punto').' '.$stEs));
            @endphp
            <tr data-rs-search="{{ $searchHay }}">
              <td class="text-muted">#{{ $idx }}</td>
              <td class="fw-semibold">{{ $s->name }}</td>
              <td><span class="rs-meta">{{ $s->lat }}, {{ $s->lng }}</span></td>
              <td>{{ $eta }}</td>
              <td>
                <span class="rs-badge {{ $st }}">{{ $stEs }}</span>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="only-mobile">
      <div id="rs-cards" class="rs-mgrid">
        @foreach($routePlan->stops as $s)
          @php
            $idx = $s->sequence_index !== null ? $s->sequence_index + 1 : '—';
            $eta = $s->eta_seconds ? round($s->eta_seconds/60).' min' : '—';
            $st  = $s->status ?? 'pending';
            $stEs = $stopLabel[$st] ?? ucfirst($st);
            $searchHay = Str::lower(trim(($s->name ?? 'punto').' '.$stEs));
          @endphp
          <div class="rs-mcard" data-rs-search="{{ $searchHay }}">
            <div class="rs-mrow">
              <div><strong>#{{ $idx }}.</strong> {{ $s->name }}</div>
              <span class="rs-badge {{ $st }}">{{ $stEs }}</span>
            </div>
            <div class="rs-muted mt-1">{{ $s->lat }}, {{ $s->lng }}</div>
            <div class="mt-1"><strong>ETA:</strong> {{ $eta }}</div>
          </div>
        @endforeach
      </div>
    </div>

  </div>

  {{-- Bootstrap Icons (por si tu layout no los incluye) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

  {{-- ================== JS ENCAPSULADO ================== --}}
  <script>
    (function(){
      const root   = document.getElementById('route-show');
      if(!root) return;

      // Progreso animado
      const bar = root.querySelector('.rs-prog > span');
      if(bar){
        const w = bar.style.width || '0%';
        bar.style.width = '0%';
        setTimeout(()=>{ bar.style.transition = 'width .6s ease'; bar.style.width = w; }, 40);
      }

      // Búsqueda client-side (tabla + tarjetas)
      const q       = root.querySelector('#rs-q');
      const clear   = root.querySelector('#rs-clear');
      const rows    = root.querySelectorAll('#rs-tbody tr');
      const cards   = root.querySelectorAll('#rs-cards .rs-mcard');

      function norm(s){ return (s||'').toString().toLowerCase().trim(); }
      function filter(){
        const needle = norm(q.value);
        rows.forEach(r=>{
          const hay = norm(r.getAttribute('data-rs-search'));
          r.style.display = hay.includes(needle) ? '' : 'none';
        });
        cards.forEach(c=>{
          const hay = norm(c.getAttribute('data-rs-search'));
          c.style.display = hay.includes(needle) ? '' : 'none';
        });
        clear.style.display = needle ? '' : 'none';
      }

      q?.addEventListener('input', filter);
      clear?.addEventListener('click', ()=>{ q.value=''; filter(); q.focus(); });

      // Inicializa estado del botón limpiar
      filter();
    })();
  </script>
</div>
@endsection
