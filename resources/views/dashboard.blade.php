{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700&display=swap"/>

<style>
  :root{
    --bg:#f6f7fb; --surface:#ffffff; --ink:#0f172a; --muted:#667085; --border:#e6e8ef;
    --brand:#7ea2ff; --brand-ink:#14206a; --ok:#16a34a; --warn:#d97706; --bad:#ef4444;
    --shadow: 0 18px 40px rgba(10, 30, 60, .08);
    --r:16px;
  }

  .dash{max-width:1200px;margin:14px auto 28px;padding:0 14px}
  .welcome{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px;box-shadow:var(--shadow)}
  .welcome h3{margin:0 0 8px 0;color:var(--ink);font-weight:800}
  .welcome p{margin:0;color:var(--muted)}

  .grid{display:grid;gap:14px}
  @media (min-width:740px){ .grid.cols-3{grid-template-columns:repeat(3,1fr)} }
  @media (min-width:1024px){ .grid.cols-4{grid-template-columns:repeat(4,1fr)} }

  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow)}
  .card .hd{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:10px}
  .card .hd h4{margin:0;font-size:14px;letter-spacing:.2px;color:var(--muted);font-weight:700}
  .card .bd{padding:16px}

  /* Icons (Material Symbols) */
  .msi{
    font-family:'Material Symbols Outlined';
    font-weight:500;
    font-style:normal;
    font-size:20px;
    line-height:1;
    letter-spacing:normal;
    text-transform:none;
    display:inline-block;
    white-space:nowrap;
    word-wrap:normal;
    direction:ltr;
    -webkit-font-feature-settings:'liga';
    -webkit-font-smoothing:antialiased;
  }

  /* KPI */
  .kpi{display:flex;align-items:center;gap:12px}
  .kpi .icon{
    width:42px;height:42px;border-radius:12px;display:grid;place-items:center;
    background:linear-gradient(180deg,#f1f5ff,#eef3ff);
    color:var(--brand-ink);
    border:1px solid var(--border)
  }
  .kpi .val{font-size:20px;font-weight:900;color:var(--ink);line-height:1}
  .kpi .sub{font-size:12px;color:var(--muted)}
  .kpi .trend{font-size:12px;margin-left:auto;display:flex;align-items:center;gap:6px}
  .up{color:var(--ok)} .down{color:var(--bad)}
  .trend .arrow{font-size:16px}

  /* Quick actions */
  .actions{display:grid;gap:10px}
  @media (min-width:600px){ .actions{grid-template-columns:repeat(2,1fr)} }
  @media (min-width:900px){ .actions{grid-template-columns:repeat(4,1fr)} }
  .btn{
    display:flex;gap:10px;align-items:center;justify-content:center;
    padding:12px 14px;border-radius:12px;
    border:1px solid var(--border);background:#f8faff;color:var(--brand-ink);
    font-weight:700;text-decoration:none;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease
  }
  .btn:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(10,30,60,.06);background:#f3f6ff}
  .btn .bico{width:20px;height:20px;display:inline-grid;place-items:center}
  .btn .lbl{white-space:nowrap}
  @media (max-width:520px){
    .btn{justify-content:flex-start}
    .btn .lbl{white-space:normal}
  }

  /* Table */
  .table{width:100%;border-collapse:separate;border-spacing:0 8px;min-width:720px}
  .table th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);text-align:left;padding:0 10px}
  .table tr{background:#fff;border:1px solid var(--border)}
  .table td{padding:12px 10px;border-top:1px solid var(--border);border-bottom:1px solid var(--border);color:var(--ink);vertical-align:top}
  .table tr td:first-child{border-left:1px solid var(--border);border-top-left-radius:12px;border-bottom-left-radius:12px}
  .table tr td:last-child{border-right:1px solid var(--border);border-top-right-radius:12px;border-bottom-right-radius:12px}
  .table .t-muted{color:var(--muted);font-size:12px}
  .table .t-strong{font-weight:800}

  .badge{font-size:12px;padding:4px 8px;border-radius:999px;border:1px solid var(--border);background:#f6f8ff;color:var(--brand-ink);white-space:nowrap}
  .badge.ok{background:#ecfdf5;border-color:#bbf7d0;color:#065f46}
  .badge.warn{background:#fffbeb;border-color:#fde68a;color:#92400e}
  .badge.bad{background:#fef2f2;border-color:#fecaca;color:#991b1b}

  /* Progress */
  .progress{height:10px;background:#f1f5f9;border:1px solid var(--border);border-radius:999px;overflow:hidden}
  .progress > span{display:block;height:100%;background:linear-gradient(90deg,#b7c8ff,#7ea2ff)}

  /* Sparkline */
  .spark{width:100%;height:48px}

  /* Small screens: keep layout clean */
  @media (max-width:739px){
    .welcome{padding:14px}
    .card .bd{padding:14px}
  }
</style>
@endpush

@section('content')
<div class="dash">

  {{-- Bienvenida --}}
  <div class="welcome">
    <h3>Hola, {{ auth()->user()->name }}</h3>
    <p>Panel de <strong>Propuestas comparativas</strong>: métricas rápidas, accesos directos y actividad reciente.</p>
  </div>

  {{-- KPIs --}}
  @php
    $kpiPropuestasMes   = $kpiPropuestasMes   ?? 0;
    $kpiEnRevision      = $kpiEnRevision      ?? 0;
    $kpiAdjudicadasMes  = $kpiAdjudicadasMes  ?? 0;
    $kpiPendientes      = $kpiPendientes      ?? 0;

    $trendPropuestas  = $trendPropuestas  ?? 6;
    $trendRevision    = $trendRevision    ?? -2;
    $trendAdjudicadas = $trendAdjudicadas ?? 3;
    $trendPend        = $trendPend        ?? 0;

    $seriePropuestas  = $seriePropuestas  ?? [2,3,2,4,5,4,6,5,7,6,8,9];
    $serieAdjudicadas = $serieAdjudicadas ?? [0,1,1,1,2,1,2,2,3,2,3,4];
  @endphp

  <div class="grid cols-4" style="margin-top:14px">
    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon" aria-hidden="true"><span class="msi">description</span></div>
          <div>
            <div class="val">{{ number_format($kpiPropuestasMes) }}</div>
            <div class="sub">Propuestas comparativas (mes)</div>
          </div>
          <div class="trend {{ $trendPropuestas >= 0 ? 'up' : 'down' }}">
            <span class="msi arrow" aria-hidden="true">{{ $trendPropuestas >= 0 ? 'trending_up' : 'trending_down' }}</span>
            {{ abs($trendPropuestas) }}%
          </div>
        </div>
        <svg class="spark" data-points="{{ implode(',', $seriePropuestas) }}"></svg>
      </div>
    </div>

    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon" aria-hidden="true"><span class="msi">manage_search</span></div>
          <div>
            <div class="val">{{ number_format($kpiEnRevision) }}</div>
            <div class="sub">En revisión</div>
          </div>
          <div class="trend {{ $trendRevision >= 0 ? 'up' : 'down' }}">
            <span class="msi arrow" aria-hidden="true">{{ $trendRevision >= 0 ? 'trending_up' : 'trending_down' }}</span>
            {{ abs($trendRevision) }}%
          </div>
        </div>
        <div style="height:48px;display:grid;place-items:center;color:var(--muted);font-size:12px">
          Seguimiento de análisis / validación
        </div>
      </div>
    </div>

    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon" aria-hidden="true"><span class="msi">fact_check</span></div>
          <div>
            <div class="val">{{ number_format($kpiAdjudicadasMes) }}</div>
            <div class="sub">Adjudicadas (mes)</div>
          </div>
          <div class="trend {{ $trendAdjudicadas >= 0 ? 'up' : 'down' }}">
            <span class="msi arrow" aria-hidden="true">{{ $trendAdjudicadas >= 0 ? 'trending_up' : 'trending_down' }}</span>
            {{ abs($trendAdjudicadas) }}%
          </div>
        </div>
        <svg class="spark" data-points="{{ implode(',', $serieAdjudicadas) }}"></svg>
      </div>
    </div>

    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon" aria-hidden="true"><span class="msi">hourglass_top</span></div>
          <div>
            <div class="val">{{ number_format($kpiPendientes) }}</div>
            <div class="sub">Pendientes</div>
          </div>
          <div class="trend {{ $trendPend >= 0 ? 'up' : 'down' }}">
            <span class="msi arrow" aria-hidden="true">{{ $trendPend >= 0 ? 'trending_up' : 'trending_down' }}</span>
            {{ abs($trendPend) }}%
          </div>
        </div>
        <div class="progress" style="margin-top:12px">
          @php $pct = min(100, max(0, (int)($kpiPendientes))); @endphp
          <span style="width: {{ $pct }}%"></span>
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:6px">
          Avance general de seguimiento
        </div>
      </div>
    </div>
  </div>

  {{-- Acciones rápidas --}}
  <div class="card" style="margin-top:14px">
    <div class="hd">
      <h4>Acciones rápidas</h4>
      <div style="font-size:12px;color:var(--muted)">Atajos frecuentes</div>
    </div>
    <div class="bd">
      <div class="actions">

        @if (Route::has('propuestas-comparativas.create'))
          <a class="btn" href="{{ route('propuestas-comparativas.create') }}">
            <span class="bico" aria-hidden="true"><span class="msi">add_circle</span></span>
            <span class="lbl">Nueva propuesta comparativa</span>
          </a>
        @elseif (Route::has('licitacion-propuestas.create'))
          <a class="btn" href="{{ route('licitacion-propuestas.create') }}">
            <span class="bico" aria-hidden="true"><span class="msi">add_circle</span></span>
            <span class="lbl">Nueva propuesta comparativa</span>
          </a>
        @endif

        @if (Route::has('licitacion-pdfs.index'))
          <a class="btn" href="{{ route('licitacion-pdfs.index') }}">
            <span class="bico" aria-hidden="true"><span class="msi">attach_file</span></span>
            <span class="lbl">PDFs / Bases</span>
          </a>
        @elseif (Route::has('admin.licitacion-pdfs.index'))
          <a class="btn" href="{{ route('admin.licitacion-pdfs.index') }}">
            <span class="bico" aria-hidden="true"><span class="msi">attach_file</span></span>
            <span class="lbl">PDFs / Bases</span>
          </a>
        @endif

        @if (Route::has('productos.index'))
          <a class="btn" href="{{ route('productos.index') }}">
            <span class="bico" aria-hidden="true"><span class="msi">inventory_2</span></span>
            <span class="lbl">Ver productos</span>
          </a>
        @endif

        @if (Route::has('proveedores.index'))
          <a class="btn" href="{{ route('proveedores.index') }}">
            <span class="bico" aria-hidden="true"><span class="msi">domain</span></span>
            <span class="lbl">Ver proveedores</span>
          </a>
        @elseif (Route::has('clientes.index'))
          <a class="btn" href="{{ route('clientes.index') }}">
            <span class="bico" aria-hidden="true"><span class="msi">groups</span></span>
            <span class="lbl">Ver clientes</span>
          </a>
        @endif

      </div>
    </div>
  </div>

  <div class="grid cols-3" style="margin-top:14px">

    {{-- Actividad reciente --}}
    <div class="card" style="grid-column:span 2">
      <div class="hd">
        <h4>Actividad reciente</h4>
        <a href="{{ Route::has('propuestas-comparativas.index') ? route('propuestas-comparativas.index') : (Route::has('licitacion-propuestas.index') ? route('licitacion-propuestas.index') : '#') }}"
           style="font-size:12px;color:var(--brand-ink);text-decoration:none;white-space:nowrap">Ver todo →</a>
      </div>

      <div class="bd" style="overflow:auto">
        @php
          $ultimasPropuestas = $ultimasPropuestas ?? collect();

          $estadoBadge = function($estado){
            $e = mb_strtolower((string)$estado);
            return match(true){
              str_contains($e,'adjud') || str_contains($e,'ganad') => 'ok',
              str_contains($e,'rech')  || str_contains($e,'perd')  => 'bad',
              str_contains($e,'revis') || str_contains($e,'anal')  => 'warn',
              default => '',
            };
          };
        @endphp

        <table class="table">
          <thead>
            <tr>
              <th>Folio</th>
              <th>Propuesta</th>
              <th>Entidad</th>
              <th>Monto</th>
              <th>Fecha</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($ultimasPropuestas as $p)
              @php
                $folio = $p->codigo ?? $p->folio ?? $p->id ?? '—';
                $titulo = $p->titulo ?? $p->nombre ?? ('Propuesta #' . ($p->id ?? ''));
                $entidad = $p->cliente->nombre
                          ?? $p->dependencia->nombre
                          ?? $p->entidad
                          ?? '—';
                $monto = $p->total
                        ?? $p->monto
                        ?? $p->total_estimado
                        ?? 0;
                $estado = $p->estado ?? $p->status ?? 'pendiente';
                $badgeClass = $estadoBadge($estado);
                $fechaObj = $p->created_at ?? $p->fecha ?? null;
              @endphp
              <tr>
                <td class="t-strong">#{{ $folio }}</td>
                <td style="font-weight:700">{{ $titulo }}</td>
                <td>{{ $entidad }}</td>
                <td>${{ number_format((float)$monto, 2) }}</td>
                <td class="t-muted">{{ optional($fechaObj)->format('d/m/Y H:i') }}</td>
                <td><span class="badge {{ $badgeClass }}">{{ ucfirst($estado) }}</span></td>
              </tr>
            @empty
              <tr>
                <td colspan="6" style="text-align:center;color:var(--muted);padding:18px">
                  Sin actividad por ahora.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Pipeline --}}
    <div class="card">
      <div class="hd">
        <h4>Pipeline</h4>
        <a href="{{ Route::has('propuestas-comparativas.index') ? route('propuestas-comparativas.index') : (Route::has('licitacion-propuestas.index') ? route('licitacion-propuestas.index') : '#') }}"
           style="font-size:12px;color:var(--brand-ink);text-decoration:none;white-space:nowrap">Ver pipeline →</a>
      </div>

      <div class="bd">
        @php
          $pipe = $pipe ?? [
            ['label'=>'Borrador',     'count'=>$pipeBorrador   ?? 0, 'pct'=>$pipePctBorrador   ?? 10],
            ['label'=>'En revisión',  'count'=>$pipeRevision   ?? 0, 'pct'=>$pipePctRevision   ?? 45],
            ['label'=>'Enviado',      'count'=>$pipeEnviado    ?? 0, 'pct'=>$pipePctEnviado    ?? 70],
            ['label'=>'Adjudicado',   'count'=>$pipeAdjudicado ?? 0, 'pct'=>$pipePctAdjudicado ?? 100],
          ];
        @endphp

        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:10px">
          @foreach ($pipe as $s)
            <li style="border:1px solid var(--border);border-radius:12px;padding:10px">
              <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                <div>
                  <div style="font-weight:800">{{ $s['label'] }}</div>
                  <div style="font-size:12px;color:var(--muted)">{{ number_format((int)$s['count']) }} registros</div>
                </div>
                <div style="min-width:110px">
                  <div class="progress" style="height:8px"><span style="width: {{ (int)$s['pct'] }}%"></span></div>
                  <div style="font-size:12px;color:var(--muted);text-align:right;margin-top:4px">{{ (int)$s['pct'] }}%</div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>

        <div style="margin-top:12px;font-size:12px;color:var(--muted)">
          Tip: usa estados consistentes (borrador → revisión → enviado → adjudicado).
        </div>
      </div>
    </div>

  </div>
</div>

{{-- Mini script para dibujar sparklines (sin librerías) --}}
<script>
  (function(){
    const svgs = document.querySelectorAll('.spark');
    svgs.forEach(svg => {
      const pts = (svg.dataset.points || '').split(',').map(n => parseFloat(n)).filter(n => !isNaN(n));
      const w = svg.clientWidth || 280, h = svg.clientHeight || 48, pad = 4;
      svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
      svg.innerHTML = '';
      if (!pts.length) return;

      const min = Math.min(...pts), max = Math.max(...pts);
      const nx = i => pad + (i * (w - pad*2) / (pts.length - 1 || 1));
      const ny = v => {
        if (max === min) return h/2;
        const t = (v - min) / (max - min);
        return h - pad - t * (h - pad*2);
      };

      const d = pts.map((v,i)=>`${i===0?'M':'L'} ${nx(i)} ${ny(v)}`).join(' ');
      const path = document.createElementNS('http://www.w3.org/2000/svg','path');
      path.setAttribute('d', d);
      path.setAttribute('fill', 'none');
      path.setAttribute('stroke', 'currentColor');
      path.setAttribute('stroke-width', '2');

      const stroke = getComputedStyle(svg.parentElement.querySelector('.kpi .icon') || svg).color || '#6272a4';
      svg.style.color = stroke;

      const area = document.createElementNS('http://www.w3.org/2000/svg','path');
      const dArea = `${d} L ${nx(pts.length-1)} ${h-pad} L ${nx(0)} ${h-pad} Z`;
      area.setAttribute('d', dArea);
      area.setAttribute('fill', stroke);
      area.setAttribute('opacity', '0.08');

      svg.appendChild(area);
      svg.appendChild(path);
    });
  })();
</script>
@endsection
