{{-- resources/views/routes/index.blade.php --}}
@extends('layouts.app')
@section('title','Rutas programadas')
@section('content_class', 'content--flush')
@section('content')
@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;
@endphp

<div id="routes-index" class="ri-wrap">
  {{-- ================== ESTILOS ENCAPSULADOS ================== --}}
  <style>
    /* Fuente corporativa */
    @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap');

    /* =========================================================
       FIX para layout.app (main#content.content)
       ========================================================= */
    body:has(#routes-index) main#content.content{
      padding: 0 !important;
      background: transparent !important;
      min-height: calc(100vh - var(--topbar-h)) !important;
    }
    body:has(#routes-index){ overflow-x:hidden; }

    #routes-index{
      width: 100vw;
      margin-left: calc(50% - 50vw);
      margin-right: calc(50% - 50vw);
    }

    /* =========================
       VARIABLES BASE
       ========================= */
    #routes-index{
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

      --shadow-card:0 4px 12px rgba(0,0,0,.02);
      --shadow-hover:0 12px 24px rgba(15,23,42,.06);

      font-family:'Quicksand', system-ui, -apple-system, 'Segoe UI', sans-serif;
      color:var(--ink);
      background:var(--bg);
      min-height: calc(100vh - var(--topbar-h));
      padding:40px 24px 56px;
      -webkit-font-smoothing:antialiased;
    }

    #routes-index a{ color:inherit; text-decoration:none }
    #routes-index a:hover{ color:var(--blue) }
    #routes-index .ri-container{ max-width:1200px; margin:0 auto }

    /* =========================
       ENCABEZADO
       ========================= */
    #routes-index .ri-titlebar{
      display:flex;
      gap:16px;
      align-items:center;
      justify-content:space-between;
      flex-wrap:wrap;
      margin-bottom:28px;
    }

    #routes-index .ri-title{
      font-weight:700;
      font-size:clamp(22px,2.6vw,30px);
      letter-spacing:-.02em;
      color:var(--ink-dark);
    }

    #routes-index .ri-actions{
      display:flex;
      gap:12px;
      flex-wrap:wrap;
      align-items:center;
    }

    /* =========================
       BUSCADOR
       ========================= */
    #routes-index .ri-search{
      display:flex;
      align-items:center;
      gap:.55rem;
      background:var(--input-bg);
      border:1px solid var(--line);
      border-radius:8px;
      padding:0 .8rem;
      height:42px;
      min-width:300px;
      transition:border-color .18s ease, box-shadow .18s ease;
    }

    #routes-index .ri-search:focus-within{
      border-color:var(--blue);
      box-shadow:0 0 0 3px var(--blue-soft);
    }

    #routes-index .ri-search input{
      border:0;
      outline:none;
      width:230px;
      font-size:.95rem;
      color:var(--ink);
      background:transparent;
      font-family:inherit;
      font-weight:500;
      height:100%;
    }

    #routes-index .ri-search input::placeholder{
      color:var(--muted-light);
    }

    #routes-index .ri-search .ri-icon{
      color:var(--muted);
      font-size:1rem;
    }

    /* =========================
       BOTONES
       ========================= */
    #routes-index .ri-btn{
      appearance:none;
      font-family:inherit;
      border:1px solid var(--line);
      background:var(--card);
      color:var(--ink);
      padding:.56rem .9rem;
      border-radius:8px;
      font-weight:600;
      font-size:.9rem;
      cursor:pointer;
      line-height:1;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:.5rem;
      transition:transform .12s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
    }

    #routes-index .ri-btn:hover{
      background:#f9fafb;
      text-decoration:none;
      transform:translateY(-1px);
    }

    #routes-index .ri-btn:active{
      transform:scale(.98);
    }

    #routes-index .ri-btn--primary{
      background:var(--blue);
      border-color:var(--blue);
      color:#fff;
      border-radius:999px;
      padding:.6rem 1.1rem;
    }

    #routes-index .ri-btn--primary:hover{
      background:#0069e0;
      border-color:#0069e0;
      color:#fff;
      transform:translateY(-1px);
      box-shadow:0 8px 18px rgba(0,122,255,.22);
    }

    #routes-index .ri-btn--primary .ri-bullet{
      width:7px;
      height:7px;
      border-radius:999px;
      background:#fff;
      opacity:.9;
      display:inline-block;
    }

    #routes-index .ri-btn--super{
      background:var(--card);
      border-color:var(--blue);
      color:var(--blue);
    }

    #routes-index .ri-btn--super:hover{
      background:var(--blue-soft);
      transform:translateY(-1px);
    }

    #routes-index .ri-btn--edit{
      background:#fff;
      border-color:#dbeafe;
      color:var(--blue);
    }

    #routes-index .ri-btn--edit:hover{
      background:var(--blue-soft);
      border-color:var(--blue);
      color:var(--blue);
    }

    #routes-index .ri-btn--soft{
      background:transparent;
      border-color:transparent;
      color:var(--muted);
    }

    #routes-index .ri-btn--soft:hover{
      background:#f9fafb;
      color:var(--ink);
    }

    #routes-index .ri-btn--mini{
      padding:.32rem .5rem;
      border-radius:8px;
    }

    /* =========================
       ALERTA ÉXITO
       ========================= */
    #routes-index .ri-alert{
      background:var(--success-soft);
      border:1px solid #bbf7d0;
      color:var(--success);
      padding:.75rem .9rem;
      border-radius:12px;
      margin-bottom:22px;
      font-weight:600;
      display:flex;
      align-items:center;
      gap:.5rem;
    }

    /* =========================
       CHIPS / BADGES
       ========================= */
    #routes-index .ri-chip{
      display:inline-flex;
      align-items:center;
      gap:.4rem;
      font-weight:600;
      font-size:.78rem;
      border-radius:999px;
      padding:.28rem .66rem;
      border:1px solid var(--line);
      background:var(--card);
      color:var(--ink);
      white-space:nowrap;
    }

    #routes-index .ri-dot{
      width:7px;
      height:7px;
      border-radius:50%;
      background:currentColor;
      opacity:.65;
    }

    #routes-index .ri-chip.borrador{ background:#f1f5f9; color:var(--muted); border-color:#e2e8f0 }
    #routes-index .ri-chip.programada{ background:var(--blue-soft); color:var(--blue); border-color:#dbeafe }
    #routes-index .ri-chip.en-curso{ background:var(--warning-soft); color:var(--warning); border-color:#fed7aa }
    #routes-index .ri-chip.completada{ background:var(--success-soft); color:var(--success); border-color:#bbf7d0 }
    #routes-index .ri-chip.cancelada{ background:var(--danger-soft); color:var(--danger); border-color:#fecaca }

    /* =========================
       PROGRESO
       ========================= */
    #routes-index .ri-prog{
      height:7px;
      background:#eef1f5;
      border-radius:999px;
      overflow:hidden;
    }

    #routes-index .ri-prog > span{
      display:block;
      height:100%;
      background:var(--blue);
      border-radius:999px;
      width:0%;
    }

    /* =========================
       TARJETAS MÓVIL
       ========================= */
    #routes-index .ri-grid{
      display:grid;
      gap:16px;
    }

    #routes-index .ri-card{
      border:1px solid var(--line);
      background:var(--card);
      border-radius:16px;
      padding:18px;
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      box-shadow:var(--shadow-card);
    }

    #routes-index .ri-card:hover{
      transform:translateY(-2px);
      box-shadow:var(--shadow-hover);
    }

    #routes-index .ri-card .ri-title-sm{
      font-weight:700;
      font-size:1rem;
      color:var(--ink-dark);
    }

    #routes-index .ri-meta{
      color:var(--muted);
      font-size:.88rem;
      font-weight:500;
    }

    #routes-index .ri-tags{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      margin:.55rem 0 .85rem;
    }

    #routes-index .ri-card-actions{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
    }

    /* =========================
       TABLA ESCRITORIO
       ========================= */
    #routes-index .ri-table-wrap{
      border:1px solid var(--line);
      background:var(--card);
      border-radius:16px;
      overflow:hidden;
      box-shadow:var(--shadow-card);
    }

    #routes-index table{
      width:100%;
      border-collapse:separate;
      border-spacing:0;
    }

    #routes-index thead th{
      background:#fbfcfe;
      border-bottom:1px solid var(--line);
      color:var(--muted);
      font-weight:600;
      font-size:.82rem;
      letter-spacing:.02em;
      text-transform:uppercase;
      padding:14px 16px;
      text-align:left;
      white-space:nowrap;
    }

    #routes-index tbody td{
      padding:16px;
      border-bottom:1px solid var(--line);
      vertical-align:middle;
      font-weight:500;
    }

    #routes-index tbody tr:last-child td{
      border-bottom:0;
    }

    #routes-index tbody tr:hover{
      background:#fbfcfe;
    }

    #routes-index tbody td .fw-semibold{
      color:var(--ink-dark);
      font-weight:700;
    }

    #routes-index .ri-table-actions{
      display:flex;
      justify-content:flex-end;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
    }

    /* =========================
       VACÍO
       ========================= */
    #routes-index .ri-empty{
      border:1px dashed var(--line);
      border-radius:16px;
      background:var(--card);
      padding:48px 26px;
      text-align:center;
      color:var(--muted);
      box-shadow:var(--shadow-card);
    }

    #routes-index .ri-empty .h5{
      color:var(--ink-dark);
    }

    /* =========================
       RESPONSIVE
       ========================= */
    @media (min-width: 992px){
      #routes-index .ri-mobile{
        display:none!important;
      }

      #routes-index .ri-desktop{
        display:block!important;
      }
    }

    @media (max-width: 991.98px){
      #routes-index{
        padding:28px 16px 40px;
      }

      #routes-index .ri-desktop{
        display:none!important;
      }

      #routes-index .ri-mobile{
        display:block!important;
      }

      #routes-index .ri-grid{
        grid-template-columns:1fr;
      }

      #routes-index .ri-search{
        min-width:unset;
        width:100%;
      }

      #routes-index .ri-search input{
        width:100%;
      }

      #routes-index .ri-card-actions .ri-btn{
        flex:1 1 auto;
        justify-content:center;
      }
    }

    #routes-index .ri-fade{
      animation:ri-fade .28s ease both;
    }

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
          <button id="ri-clear" class="ri-btn ri-btn--soft ri-btn--mini" title="Borrar búsqueda" style="display:none" type="button">
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
        <div class="h5 mb-1" style="font-weight:700;">Aún no tienes rutas</div>
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
                ->lower()
                ->ascii();

              $planned = $plan->planned_at ? Carbon::parse($plan->planned_at) : null;

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
                <a href="{{ route('routes.show', $plan) }}" class="ri-btn">
                  <i class="bi bi-eye"></i> Ver
                </a>

                <a href="{{ route('routes.edit', $plan) }}" class="ri-btn ri-btn--edit">
                  <i class="bi bi-pencil-square"></i> Editar
                </a>

                <a href="{{ route('driver.routes.show', $plan) }}" class="ri-btn">
                  <i class="bi bi-phone"></i> Chofer
                </a>

                <a href="{{ $supervisorUrl }}" class="ri-btn ri-btn--super" title="Supervisor (vista en tiempo real)">
                  <i class="bi bi-broadcast-pin"></i> Supervisor
                </a>
              </div>
            </div>
          @endforeach
        </div>

        <div style="margin-top:16px">
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
                <th style="width:390px; text-align:right">Acciones</th>
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
                    ->lower()
                    ->ascii();

                  $planned = $plan->planned_at ? Carbon::parse($plan->planned_at) : null;

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
                    <div class="ri-table-actions">
                      <a href="{{ route('routes.show', $plan) }}" class="ri-btn">
                        <i class="bi bi-eye"></i> Ver
                      </a>

                      <a href="{{ route('routes.edit', $plan) }}" class="ri-btn ri-btn--edit">
                        <i class="bi bi-pencil-square"></i> Editar
                      </a>

                      <a href="{{ route('driver.routes.show', $plan) }}" class="ri-btn">
                        <i class="bi bi-phone"></i> Chofer
                      </a>

                      <a href="{{ $supervisorUrl }}" class="ri-btn ri-btn--super" title="Supervisor (vista en tiempo real)">
                        <i class="bi bi-broadcast-pin"></i> Supervisor
                      </a>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div style="margin-top:16px">
          {{ $plans->onEachSide(1)->links() }}
        </div>
      </div>
    @endif
  </div>

  {{-- Icons --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>

  {{-- ================== JS ENCAPSULADO ================== --}}
  <script>
    (function(){
      const root = document.getElementById('routes-index');
      if(!root) return;

      const q = root.querySelector('#ri-q');
      const clearBtn = root.querySelector('#ri-clear');
      const cards = root.querySelectorAll('#ri-cards .ri-card');
      const rows = root.querySelectorAll('#ri-tbody tr');

      const norm = s => (s || '').toString().trim().toLowerCase();

      function filtrar(){
        const x = norm(q?.value);

        cards.forEach(card => {
          card.style.display = norm(card.getAttribute('data-ri-search')).includes(x) ? '' : 'none';
        });

        rows.forEach(row => {
          row.style.display = norm(row.getAttribute('data-ri-search')).includes(x) ? '' : 'none';
        });

        if(clearBtn) {
          clearBtn.style.display = x ? '' : 'none';
        }
      }

      q?.addEventListener('input', filtrar);

      clearBtn?.addEventListener('click', () => {
        if(q) {
          q.value = '';
        }

        filtrar();
        q?.focus();
      });

      root.querySelectorAll('.ri-prog > span').forEach(el => {
        const w = el.style.width || '0%';
        el.style.width = '0%';

        setTimeout(() => {
          el.style.transition = 'width .6s ease';
          el.style.width = w;
        }, 30);
      });
    })();
  </script>
</div>
@endsection