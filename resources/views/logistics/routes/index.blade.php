{{-- resources/views/routes/index.blade.php --}}
@extends('layouts.app')
@section('title','Rutas programadas')

@section('content')
@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;
@endphp

<div id="routes-index" class="ri-wrap">
  {{-- ================== ESTILOS ENCAPSULADOS ================== --}}
  <style>
    /* ✅ Usa fuente global (NO declaramos font-family aquí) */
    #routes-index{
      --ri-ink:#0f172a;
      --ri-muted:#64748b;
      --ri-line:#e8eef7;

      --ri-bg:#f7f9fc;
      --ri-card:#ffffff;

      /* Pasteles profesionales / minimalistas */
      --ri-primary:#b9ddff;     /* azul pastel */
      --ri-mint:#bff3e7;        /* menta pastel */
      --ri-lilac:#d9d4ff;       /* lila pastel */
      --ri-amber:#ffe6b8;       /* ámbar pastel */
      --ri-rose:#ffd1e1;        /* rosa pastel */

      --ri-shadow:0 14px 34px rgba(2,8,23,.08);
      --ri-shadow2:0 20px 48px rgba(2,8,23,.10);

      color:var(--ri-ink);
      background:linear-gradient(180deg,#fbfdff,var(--ri-bg));
      padding:22px 14px;
    }

    #routes-index a{ color:inherit; text-decoration:none }
    #routes-index a:hover{ text-decoration:underline }
    #routes-index .ri-container{ max-width:1200px; margin:0 auto }

    /* Encabezado */
    #routes-index .ri-titlebar{
      display:flex; gap:12px; align-items:center; justify-content:space-between;
      flex-wrap:wrap; margin-bottom:14px
    }
    #routes-index .ri-title{
      font-weight:900; font-size:clamp(20px,2.6vw,30px);
      letter-spacing:.2px
    }
    #routes-index .ri-actions{display:flex; gap:10px; flex-wrap:wrap; align-items:center}

    /* Buscador */
    #routes-index .ri-search{
      display:flex; align-items:center; gap:.55rem;
      background:#fff; border:1px solid var(--ri-line);
      border-radius:14px; padding:.45rem .6rem;
      min-width:280px;
      box-shadow:0 10px 26px rgba(2,8,23,.05)
    }
    #routes-index .ri-search input{
      border:0; outline:none; width:220px; font-size:.95rem;
      color:var(--ri-ink); background:transparent
    }
    #routes-index .ri-search .ri-icon{ color:var(--ri-muted); font-size:1rem }

    /* Botones (pastel, minimalistas) */
    #routes-index .ri-btn{
      appearance:none;
      border:1px solid var(--ri-line);
      background:#fff;
      color:var(--ri-ink);
      padding:.56rem .86rem;
      border-radius:14px;
      font-weight:800;
      cursor:pointer;
      line-height:1;
      display:inline-flex; align-items:center; gap:.5rem;
      transition:transform .14s ease, box-shadow .14s ease, background .14s ease, border-color .14s ease;
      box-shadow:0 8px 22px rgba(2,8,23,.06)
    }
    #routes-index .ri-btn:hover{
      transform:translateY(-1px);
      box-shadow:var(--ri-shadow);
      border-color:#d6e6ff;
    }
    #routes-index .ri-btn:active{ transform:translateY(0px) }

    /* Variantes pastel */
    #routes-index .ri-btn--primary{
      background:linear-gradient(180deg, #eaf4ff, #ffffff);
      border-color:#d6e6ff;
      box-shadow:0 10px 24px rgba(2,8,23,.07)
    }
    #routes-index .ri-btn--primary .ri-bullet{
      width:8px;height:8px;border-radius:999px;background:var(--ri-primary); display:inline-block
    }

    #routes-index .ri-btn--super{
      background:linear-gradient(180deg, #ecfffa, #ffffff);
      border-color:#c9f1e8;
    }

    #routes-index .ri-btn--soft{
      background:#fff;
      border-color:var(--ri-line);
      box-shadow:none;
    }

    /* Botón mini (clear) */
    #routes-index .ri-btn--mini{
      padding:.28rem .48rem;
      border-radius:12px;
      box-shadow:none;
    }

    /* Alertas */
    #routes-index .ri-alert{
      background:#ecfffa;
      border:1px solid #c9f1e8;
      color:#0f4c3a;
      padding:.65rem .8rem;
      border-radius:14px;
      margin-bottom:12px;
      font-weight:800;
      display:flex; align-items:center; gap:.5rem
    }

    /* Chips / estados */
    #routes-index .ri-chip{
      display:inline-flex; align-items:center; gap:.4rem;
      font-weight:900; font-size:.78rem;
      border-radius:999px; padding:.22rem .62rem;
      border:1px solid var(--ri-line);
      background:#fff; color:#0f172a;
      white-space:nowrap;
    }
    #routes-index .ri-dot{width:8px; height:8px; border-radius:50%}

    #routes-index .ri-chip.borrador   {background:#f8fafc}
    #routes-index .ri-chip.programada {background:var(--ri-mint);  border-color:#a8ecd9}
    #routes-index .ri-chip.en-curso   {background:var(--ri-amber); border-color:#ffd48a}
    #routes-index .ri-chip.completada {background:#d7f9e1;         border-color:#a8f0bd}
    #routes-index .ri-chip.cancelada  {background:var(--ri-rose);  border-color:#ffb7cf}

    /* Progreso */
    #routes-index .ri-prog{
      height:8px; background:#eef2f7; border-radius:999px; overflow:hidden
    }
    #routes-index .ri-prog > span{
      display:block; height:100%;
      background:linear-gradient(90deg,#d9ecff,#9cc9ff);
      width:0%;
    }

    /* Tarjetas (móvil) */
    #routes-index .ri-grid{display:grid; gap:12px}
    #routes-index .ri-card{
      border:1px solid var(--ri-line);
      background:rgba(255,255,255,.92);
      backdrop-filter:saturate(1.1) blur(6px);
      border-radius:18px;
      padding:12px;
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      box-shadow:0 12px 28px rgba(2,8,23,.06)
    }
    #routes-index .ri-card:hover{
      transform:translateY(-2px);
      border-color:#d6e6ff;
      box-shadow:var(--ri-shadow2);
    }
    #routes-index .ri-card .ri-title-sm{font-weight:900; font-size:1rem}
    #routes-index .ri-meta{ color:var(--ri-muted); font-size:.9rem }
    #routes-index .ri-tags{ display:flex; gap:8px; flex-wrap:wrap; margin:.45rem 0 .75rem }
    #routes-index .ri-card-actions{ display:flex; gap:8px; flex-wrap:wrap }

    /* Tabla (escritorio) */
    #routes-index .ri-table-wrap{
      border:1px solid var(--ri-line);
      background:rgba(255,255,255,.92);
      backdrop-filter:saturate(1.1) blur(6px);
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 14px 34px rgba(2,8,23,.07)
    }
    #routes-index table{ width:100%; border-collapse:separate; border-spacing:0 }
    #routes-index thead th{
      background:linear-gradient(180deg,#fbfdff,#f6f9ff);
      border-bottom:1px solid var(--ri-line);
      color:#334155;
      font-weight:900;
      padding:10px 12px;
      text-align:left;
      white-space:nowrap;
    }
    #routes-index tbody td{
      padding:12px;
      border-bottom:1px solid #f1f5f9;
      vertical-align:middle
    }
    #routes-index tbody tr:hover{ background:#f8fbff }

    /* Vacío */
    #routes-index .ri-empty{
      border:1px dashed var(--ri-line);
      border-radius:18px;
      background:linear-gradient(180deg,#f8fbff,transparent);
      padding:26px;
      text-align:center;
      color:var(--ri-muted)
    }

    /* Responsive */
    @media (min-width: 992px){
      #routes-index .ri-mobile{display:none!important}
      #routes-index .ri-desktop{display:block!important}
    }
    @media (max-width: 991.98px){
      #routes-index{ padding:18px 12px }
      #routes-index .ri-desktop{display:none!important}
      #routes-index .ri-mobile{display:block!important}
      #routes-index .ri-grid{ grid-template-columns:1fr }
      #routes-index .ri-search{ min-width:unset; width:100% }
      #routes-index .ri-search input{ width:100% }
    }

    /* Animación */
    #routes-index .ri-fade{ animation:ri-fade .28s ease both }
    @keyframes ri-fade{
      from{opacity:0; transform:translateY(4px)}
      to{opacity:1; transform:translateY(0)}
    }
  </style>

  <div class="ri-container">
    {{-- Encabezado / acciones --}}
    <div class="ri-titlebar">
      <div class="ri-title">Rutas programadas</div>

      <div class="ri-actions">
        <div class="ri-search">
          <i class="bi bi-search ri-icon"></i>
          <input id="ri-q" type="text" placeholder="Buscar por nombre, chofer o estado…">
          <button id="ri-clear" class="ri-btn ri-btn--soft ri-btn--mini" title="Borrar búsqueda" style="display:none">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>

        <a href="{{ route('routes.create') }}" class="ri-btn ri-btn--primary" title="Crear nueva ruta">
          <span class="ri-bullet"></span>
          <i class="bi bi-plus-lg"></i>
          Nueva ruta
        </a>
      </div>
    </div>

    {{-- Alerta de éxito --}}
    @if(session('ok'))
      <div class="ri-alert ri-fade">
        <i class="bi bi-check2-circle"></i> {{ session('ok') }}
      </div>
    @endif

    {{-- Estado vacío --}}
    @if($plans->count() === 0)
      <div class="ri-empty ri-fade">
        <div class="h5 mb-1" style="font-weight:900;">Aún no tienes rutas</div>
        <div class="mb-3">Crea la primera con “Nueva ruta”.</div>
        <a href="{{ route('routes.create') }}" class="ri-btn ri-btn--primary">
          <span class="ri-bullet"></span><i class="bi bi-plus-circle"></i> Crear ruta
        </a>
      </div>
    @else
      {{-- ===== MÓVIL: tarjetas ===== --}}
      <div class="ri-mobile ri-fade">
        <div id="ri-cards" class="ri-grid">
          @foreach($plans as $plan)
            @php
              $totalStops = $plan->stops_count ?? 0;
              $doneStops  = $plan->done_stops_count ?? 0;
              $pending    = max(0, $totalStops - $doneStops);
              $pct        = $totalStops ? intval(($doneStops / $totalStops) * 100) : 0;

              $status_key = $plan->status ?? 'scheduled';
              $status_es  = [
                'draft'       => 'Borrador',
                'scheduled'   => 'Programada',
                'in_progress' => 'En curso',
                'done'        => 'Completada',
                'cancelled'   => 'Cancelada',
              ][$status_key] ?? 'Programada';

              $chip_class = [
                'draft'       => 'borrador',
                'scheduled'   => 'programada',
                'in_progress' => 'en-curso',
                'done'        => 'completada',
                'cancelled'   => 'cancelada',
              ][$status_key] ?? 'programada';

              $driver = $plan->driver->name ?? ($plan->driver->email ?? '—');

              $labelSearch = Str::of(($plan->name ?? 'ruta '.$plan->id).' '.$driver.' '.$status_es)
                ->lower()->ascii();

              $planned = $plan->planned_at ? Carbon::parse($plan->planned_at) : null;

              // ✅ Supervisor (VISTA) - ajusta el name si el tuyo es diferente
              $supervisorUrl = route('supervisor.routes.show', $plan);
            @endphp

            <div class="ri-card" data-ri-search="{{ $labelSearch }}">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="ri-title-sm">
                  <a href="{{ route('routes.show', $plan) }}">{{ $plan->name ?? ('Ruta #'.$plan->id) }}</a>
                </div>
                <span class="ri-chip {{ $chip_class }}"><span class="ri-dot"></span>{{ $status_es }}</span>
              </div>

              <div class="ri-meta mb-2">
                <i class="bi bi-person"></i> {{ $driver }}
                @if($planned)
                  <span class="ms-2"><i class="bi bi-calendar-event"></i> {{ $planned->format('Y-m-d H:i') }}</span>
                @endif
              </div>

              <div class="ri-tags">
                <span class="ri-chip"><i class="bi bi-flag"></i> {{ $doneStops }}/{{ $totalStops }} hechas</span>
                @if($pending > 0)
                  <span class="ri-chip"><i class="bi bi-clock-history"></i> {{ $pending }} pendientes</span>
                @endif
              </div>

              <div class="ri-prog mb-3" aria-label="Progreso">
                <span style="width: {{ $pct }}%"></span>
              </div>

              <div class="ri-card-actions">
                <a href="{{ route('routes.show', $plan) }}" class="ri-btn"><i class="bi bi-eye"></i> Ver</a>

                <a href="{{ route('driver.routes.show', $plan) }}" class="ri-btn">
                  <i class="bi bi-phone"></i> Chofer
                </a>

                {{-- ✅ Supervisor (VISTA HTML) --}}
                <a href="{{ $supervisorUrl }}" class="ri-btn ri-btn--super" title="Supervisor (vista en tiempo real)">
                  <i class="bi bi-broadcast-pin"></i> Supervisor
                </a>

                {{-- (Opcional) Live JSON --}}
                <a href="{{ route('api.supervisor.routes.poll', $plan) }}" target="_blank" class="ri-btn ri-btn--soft"
                   title="Endpoint de polling (JSON)">
                  <i class="bi bi-activity"></i>
                </a>
              </div>
            </div>
          @endforeach
        </div>

        <div style="margin-top:12px">
          {{ $plans->onEachSide(1)->links() }}
        </div>
      </div>

      {{-- ===== ESCRITORIO: tabla ===== --}}
      <div class="ri-desktop ri-fade">
        <div class="ri-table-wrap">
          <table id="ri-table">
            <thead>
              <tr>
                <th style="width:70px">#</th>
                <th>Nombre</th>
                <th>Chofer</th>
                <th style="width:220px">Progreso</th>
                <th>Estado</th>
                <th>Fecha programada</th>
                <th style="width:360px; text-align:right">Acciones</th>
              </tr>
            </thead>

            <tbody id="ri-tbody">
              @foreach($plans as $plan)
                @php
                  $totalStops = $plan->stops_count ?? 0;
                  $doneStops  = $plan->done_stops_count ?? 0;
                  $pending    = max(0, $totalStops - $doneStops);
                  $pct        = $totalStops ? intval(($doneStops / $totalStops) * 100) : 0;

                  $status_key = $plan->status ?? 'scheduled';
                  $status_es  = [
                    'draft'       => 'Borrador',
                    'scheduled'   => 'Programada',
                    'in_progress' => 'En curso',
                    'done'        => 'Completada',
                    'cancelled'   => 'Cancelada',
                  ][$status_key] ?? 'Programada';

                  $chip_class = [
                    'draft'       => 'borrador',
                    'scheduled'   => 'programada',
                    'in_progress' => 'en-curso',
                    'done'        => 'completada',
                    'cancelled'   => 'cancelada',
                  ][$status_key] ?? 'programada';

                  $driver = $plan->driver->name ?? ($plan->driver->email ?? '—');

                  $labelSearch = Str::of(($plan->name ?? 'ruta '.$plan->id).' '.$driver.' '.$status_es)
                    ->lower()->ascii();

                  $planned = $plan->planned_at ? Carbon::parse($plan->planned_at) : null;

                  // ✅ Supervisor (VISTA)
                  $supervisorUrl = route('supervisor.routes.show', $plan);
                @endphp

                <tr data-ri-search="{{ $labelSearch }}">
                  <td class="text-muted">#{{ $plan->id }}</td>

                  <td>
                    <a href="{{ route('routes.show', $plan) }}" class="fw-semibold">
                      {{ $plan->name ?? ('Ruta #'.$plan->id) }}
                    </a>
                    <div class="ri-meta">Pendientes: {{ $pending }} • Hechas: {{ $doneStops }}</div>
                  </td>

                  <td>{{ $driver }}</td>

                  <td>
                    <div class="ri-prog" title="{{ $pct }}%">
                      <span style="width: {{ $pct }}%"></span>
                    </div>
                    <div class="ri-meta mt-1">{{ $doneStops }}/{{ $totalStops }}</div>
                  </td>

                  <td>
                    <span class="ri-chip {{ $chip_class }}"><span class="ri-dot"></span>{{ $status_es }}</span>
                  </td>

                  <td>
                    @if($planned)
                      <span class="ri-meta"><i class="bi bi-calendar-event"></i> {{ $planned->format('Y-m-d H:i') }}</span>
                    @else
                      <span class="ri-meta">—</span>
                    @endif
                  </td>

                  <td style="text-align:right; white-space:nowrap">
                    <a href="{{ route('routes.show', $plan) }}" class="ri-btn"><i class="bi bi-eye"></i> Ver</a>

                    <a href="{{ route('driver.routes.show', $plan) }}" class="ri-btn">
                      <i class="bi bi-phone"></i> Chofer
                    </a>

                    <a href="{{ $supervisorUrl }}" class="ri-btn ri-btn--super" title="Supervisor (vista en tiempo real)">
                      <i class="bi bi-broadcast-pin"></i> Supervisor
                    </a>

                    <a href="{{ route('api.supervisor.routes.poll', $plan) }}" target="_blank" class="ri-btn ri-btn--soft"
                       title="Endpoint de polling (JSON)">
                      <i class="bi bi-activity"></i>
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div style="margin-top:12px">
          {{ $plans->onEachSide(1)->links() }}
        </div>
      </div>
    @endif
  </div>

  {{-- Icons (si tu layout no las trae) --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

  {{-- ================== JS ENCAPSULADO ================== --}}
  <script>
    (function(){
      const root  = document.getElementById('routes-index');
      if(!root) return;

      const q        = root.querySelector('#ri-q');
      const clearBtn = root.querySelector('#ri-clear');
      const cards    = root.querySelectorAll('#ri-cards .ri-card');
      const rows     = root.querySelectorAll('#ri-tbody tr');

      const norm = s => (s||'').toString().trim().toLowerCase();

      function filtrar(){
        const x = norm(q?.value);
        cards.forEach(c => c.style.display = norm(c.getAttribute('data-ri-search')).includes(x) ? '' : 'none');
        rows.forEach(r  => r.style.display  = norm(r.getAttribute('data-ri-search')).includes(x) ? '' : 'none');
        if(clearBtn) clearBtn.style.display = x ? '' : 'none';
      }

      q?.addEventListener('input', filtrar);
      clearBtn?.addEventListener('click', ()=>{ if(q){ q.value=''; } filtrar(); q?.focus(); });

      // animación de barras
      root.querySelectorAll('.ri-prog > span').forEach(el=>{
        const w = el.style.width || '0%';
        el.style.width='0%';
        setTimeout(()=>{ el.style.transition='width .6s ease'; el.style.width=w; }, 30);
      });
    })();
  </script>
</div>
@endsection
