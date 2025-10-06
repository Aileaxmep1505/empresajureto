{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@push('styles')
<style>
  :root{
    /* Si ya tienes estas variables en tu layout, se respetan. Las de aquí son fallback. */
    --bg:#f6f7fb; --surface:#ffffff; --ink:#0f172a; --muted:#667085; --border:#e6e8ef;
    --brand:#7ea2ff; --brand-ink:#14206a; --ok:#16a34a; --warn:#d97706; --bad:#ef4444;
    --shadow: 0 18px 40px rgba(10, 30, 60, .08);
    --r:16px;
  }
  .dash{max-width:1200px;margin:14px auto 28px;padding:0 14px}
  .welcome{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:16px;box-shadow:var(--shadow)}
  .welcome h3{margin:0 0 8px 0;color:var(--ink);font-weight:700}
  .welcome p{margin:0;color:var(--muted)}

  .grid{display:grid;gap:14px}
  @media (min-width:740px){ .grid.cols-3{grid-template-columns:repeat(3,1fr)} }
  @media (min-width:1024px){ .grid.cols-4{grid-template-columns:repeat(4,1fr)} }

  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);box-shadow:var(--shadow)}
  .card .hd{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
  .card .hd h4{margin:0;font-size:14px;letter-spacing:.2px;color:var(--muted);font-weight:600}
  .card .bd{padding:16px}

  /* KPI */
  .kpi{display:flex;align-items:center;gap:12px}
  .kpi .icon{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;
             background:linear-gradient(180deg,#f1f5ff,#eef3ff);color:var(--brand-ink);border:1px solid var(--border)}
  .kpi .val{font-size:20px;font-weight:800;color:var(--ink);line-height:1}
  .kpi .sub{font-size:12px;color:var(--muted)}
  .kpi .trend{font-size:12px;margin-left:auto}
  .up{color:var(--ok)} .down{color:var(--bad)}

  /* Quick actions */
  .actions{display:grid;gap:10px}
  @media (min-width:600px){ .actions{grid-template-columns:repeat(2,1fr)} }
  @media (min-width:900px){ .actions{grid-template-columns:repeat(4,1fr)} }
  .btn{display:flex;gap:10px;align-items:center;justify-content:center;padding:12px 14px;border-radius:12px;
       border:1px solid var(--border);background:#f8faff;color:var(--brand-ink);font-weight:600;transition:transform .12s ease, box-shadow .12s ease}
  .btn:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(10,30,60,.06)}
  .btn .bico{width:20px;height:20px;display:inline-grid;place-items:center}

  /* Table */
  .table{width:100%;border-collapse:separate;border-spacing:0 8px}
  .table th{font-size:12px;text-transform:uppercase;letter-spacing:.4px;color:var(--muted);text-align:left;padding:0 10px}
  .table tr{background:#fff;border:1px solid var(--border)}
  .table td{padding:12px 10px;border-top:1px solid var(--border);border-bottom:1px solid var(--border);color:var(--ink)}
  .table tr td:first-child{border-left:1px solid var(--border);border-top-left-radius:12px;border-bottom-left-radius:12px}
  .table tr td:last-child{border-right:1px solid var(--border);border-top-right-radius:12px;border-bottom-right-radius:12px}
  .badge{font-size:12px;padding:4px 8px;border-radius:999px;border:1px solid var(--border);background:#f6f8ff;color:var(--brand-ink)}

  /* Progress */
  .progress{height:10px;background:#f1f5f9;border:1px solid var(--border);border-radius:999px;overflow:hidden}
  .progress > span{display:block;height:100%;background:linear-gradient(90deg,#b7c8ff,#7ea2ff)}

  /* Sparkline */
  .spark{width:100%;height:48px}
</style>
@endpush

@section('content')
<div class="dash">

  {{-- Bienvenida --}}
  <div class="welcome">
    <h3>¡Hola, {{ auth()->user()->name }}!</h3>
    <p>Bienvenido a tu panel. Aquí tienes un resumen rápido y accesos directos.</p>
  </div>

  {{-- KPIs principales --}}
  @php
    // Valores de ejemplo / fallback (sustituye por tus verdaderas métricas)
    $kpiVentasHoy = $kpiVentasHoy ?? 0;
    $kpiIngresosMes = $kpiIngresosMes ?? 0;
    $kpiClientes = $kpiClientes ?? 0;
    $kpiPendientes = $kpiPendientes ?? 0;

    $trendVentas = $trendVentas ?? 8;   // % arriba/abajo
    $trendIngresos = $trendIngresos ?? -3;
    $trendClientes = $trendClientes ?? 2;
    $trendPend = $trendPend ?? 0;

    // Serie para sparkline (reemplaza por datos reales, ej. últimos 12 días)
    $serieVentas = $serieVentas ?? [4,6,5,7,9,8,10,9,12,11,14,15];
    $serieIngresos = $serieIngresos ?? [12,9,11,10,13,15,14,16,18,17,20,22];
  @endphp

  <div class="grid cols-4" style="margin-top:14px">
    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon">💳</div>
          <div>
            <div class="val">{{ number_format($kpiVentasHoy) }}</div>
            <div class="sub">Ventas hoy</div>
          </div>
          <div class="trend {{ $trendVentas >= 0 ? 'up' : 'down' }}">
            {{ $trendVentas >= 0 ? '▲' : '▼' }} {{ abs($trendVentas) }}%
          </div>
        </div>
        <svg class="spark" data-points="{{ implode(',', $serieVentas) }}"></svg>
      </div>
    </div>

    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon">💵</div>
          <div>
            <div class="val">${{ number_format($kpiIngresosMes, 2) }}</div>
            <div class="sub">Ingresos del mes</div>
          </div>
          <div class="trend {{ $trendIngresos >= 0 ? 'up' : 'down' }}">
            {{ $trendIngresos >= 0 ? '▲' : '▼' }} {{ abs($trendIngresos) }}%
          </div>
        </div>
        <svg class="spark" data-points="{{ implode(',', $serieIngresos) }}"></svg>
      </div>
    </div>

    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon">👥</div>
          <div>
            <div class="val">{{ number_format($kpiClientes) }}</div>
            <div class="sub">Clientes</div>
          </div>
          <div class="trend {{ $trendClientes >= 0 ? 'up' : 'down' }}">
            {{ $trendClientes >= 0 ? '▲' : '▼' }} {{ abs($trendClientes) }}%
          </div>
        </div>
        <div style="height:48px;display:grid;place-items:center;color:var(--muted);font-size:12px">
          Últimos registros de clientes
        </div>
      </div>
    </div>

    <div class="card">
      <div class="bd">
        <div class="kpi">
          <div class="icon">⏳</div>
          <div>
            <div class="val">{{ number_format($kpiPendientes) }}</div>
            <div class="sub">Pendientes</div>
          </div>
          <div class="trend {{ $trendPend >= 0 ? 'up' : 'down' }}">
            {{ $trendPend >= 0 ? '▲' : '▼' }} {{ abs($trendPend) }}%
          </div>
        </div>
        <div class="progress" style="margin-top:12px">
          @php $pct = min(100, max(0, (int)($kpiPendientes))); @endphp
          <span style="width: {{ $pct }}%"></span>
        </div>
        <div style="font-size:12px;color:var(--muted);margin-top:6px">
          Avance general de tareas
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
        @if (Route::has('ventas.create'))
          <a class="btn" href="{{ route('ventas.create') }}">
            <span class="bico">🧾</span> Nueva venta
          </a>
        @endif
        @if (Route::has('cotizaciones.create'))
          <a class="btn" href="{{ route('cotizaciones.create') }}">
            <span class="bico">📝</span> Nueva cotización
          </a>
        @endif
        @if (Route::has('productos.index'))
          <a class="btn" href="{{ route('productos.index') }}">
            <span class="bico">📦</span> Ver productos
          </a>
        @endif
        @if (Route::has('clientes.index'))
          <a class="btn" href="{{ route('clientes.index') }}">
            <span class="bico">👤</span> Ver clientes
          </a>
        @endif
      </div>
    </div>
  </div>

  <div class="grid cols-3" style="margin-top:14px">

    {{-- Actividad reciente (ej. últimas ventas/cotizaciones) --}}
    <div class="card" style="grid-column:span 2">
      <div class="hd">
        <h4>Actividad reciente</h4>
        <a href="{{ Route::has('ventas.index') ? route('ventas.index') : '#' }}"
           style="font-size:12px;color:var(--brand-ink)">Ver todo →</a>
      </div>
      <div class="bd" style="overflow:auto">
        @php
          /** @var \Illuminate\Support\Collection|\App\Models\Venta[] $ultimasVentas */
          $ultimasVentas = $ultimasVentas ?? collect();
        @endphp
        <table class="table">
          <thead>
            <tr>
              <th>Folio</th>
              <th>Cliente</th>
              <th>Total</th>
              <th>Fecha</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($ultimasVentas as $v)
              <tr>
                <td style="font-weight:700">#{{ $v->id }}</td>
                <td>{{ $v->cliente->nombre ?? '—' }}</td>
                <td>${{ number_format($v->total ?? 0, 2) }}</td>
                <td>{{ optional($v->created_at)->format('d/m/Y H:i') }}</td>
                <td>
                  <span class="badge">
                    {{ ucfirst($v->estado ?? 'pendiente') }}
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" style="text-align:center;color:var(--muted);padding:18px">
                  Sin actividad por ahora.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Progreso (pensado para escuela/módulos) --}}
    <div class="card">
      <div class="hd">
        <h4>Progreso de módulos</h4>
        <a href="{{ Route::has('alumno.modulos.index') ? route('alumno.modulos.index') : '#' }}"
           style="font-size:12px;color:var(--brand-ink)">Ir a módulos →</a>
      </div>
      <div class="bd">
        @php
          // Puedes pasar $progresoAlumno desde el controlador (0..100)
          $progresoAlumno = $progresoAlumno ?? 0;
        @endphp
        <div class="progress"><span style="width: {{ (int)$progresoAlumno }}%"></span></div>
        <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;color:var(--muted)">
          <span>Completado</span>
          <strong style="color:var(--ink)">{{ (int)$progresoAlumno }}%</strong>
        </div>

        <ul style="list-style:none;margin:14px 0 0 0;padding:0;display:grid;gap:10px">
          @php
            /** @var array<int,array{titulo:string,estado:string,pct:int}> $modulos */
            $modulos = $modulos ?? [
              ['titulo'=>'Módulo 1: Introducción','estado'=>'completado','pct'=>100],
              ['titulo'=>'Módulo 2: Intermedio','estado'=>'en progreso','pct'=>45],
              ['titulo'=>'Módulo 3: Avanzado','estado'=>'pendiente','pct'=>0],
            ];
          @endphp
          @foreach ($modulos as $m)
            <li style="border:1px solid var(--border);border-radius:12px;padding:10px">
              <div style="display:flex;justify-content:space-between;gap:8px">
                <div>
                  <div style="font-weight:700">{{ $m['titulo'] }}</div>
                  <div style="font-size:12px;color:var(--muted)">{{ ucfirst($m['estado']) }}</div>
                </div>
                <div style="min-width:90px">
                  <div class="progress" style="height:8px"><span style="width: {{ $m['pct'] }}%"></span></div>
                  <div style="font-size:12px;color:var(--muted);text-align:right;margin-top:4px">{{ $m['pct'] }}%</div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>
      </div>
    </div>

  </div>
</div>

{{-- Mini script para dibujar las sparklines sin librerías --}}
<script>
  (function(){
    const svgs = document.querySelectorAll('.spark');
    svgs.forEach(svg => {
      const pts = (svg.dataset.points || '').split(',').map(n => parseFloat(n)).filter(n => !isNaN(n));
      const w = svg.clientWidth || 280, h = svg.clientHeight || 48, pad = 4;
      svg.setAttribute('viewBox', `0 0 ${w} ${h}`);
      svg.innerHTML = '';
      if (!pts.length) { return; }
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
