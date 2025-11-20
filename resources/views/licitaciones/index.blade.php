@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root{
  --indigo-50:#eef2ff; --indigo-100:#e0e7ff; --indigo-600:#4f46e5; --indigo-700:#4338ca;
  --mint-50:#ecfdf7; --mint-100:#d1fae5; --mint-600:#10b981; --mint-700:#059669;
  --amber-50:#fffbeb; --amber-200:#fde68a; --amber-800:#92400e;
  --sky-50:#eff6ff; --sky-200:#bfdbfe; --sky-700:#1d4ed8;
  --stone-50:#fafafa; --stone-100:#f5f6f8; --stone-200:#e6eef6; --stone-300:#d7dee7;

  --ink:#0f172a;
  --muted:#6b7280;
  --muted-2:#94a3b8;
  --line:#e6eef6;
  --card:#ffffff;

  --shadow-lg: 0 18px 50px rgba(12,18,30,0.07);
  --shadow-md: 0 8px 20px rgba(12,18,30,0.06);
  --radius-xl:18px;
  --radius-lg:14px;
}

*{ box-sizing:border-box; }
body{
  font-family:"Open Sans",system-ui,-apple-system,Segoe UI,sans-serif;
  color:var(--ink);
}

/* ---------- Icons ---------- */
.svg-ico{
  width:16px;height:16px;display:inline-block;vertical-align:middle;
  stroke:currentColor; fill:none; stroke-width:2; stroke-linecap:round; stroke-linejoin:round;
}
.svg-ico.sm{ width:14px; height:14px; }
.svg-ico.lg{ width:18px; height:18px; }

/* ---------- Page ---------- */
.page{
  max-width:1140px;
  margin:56px auto;
  padding:0 16px 28px;
}
.header{
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  margin-bottom:16px;
}
.hgroup h1{
  margin:0;
  font-size:26px;
  font-weight:700;
  letter-spacing:-0.02em;
}
.hgroup p{
  margin:6px 0 0;
  font-size:13px;
  color:var(--muted);
  max-width:680px;
}

/* ---------- Badges ---------- */
.badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:5px 10px;
  font-size:11px;
  border-radius:999px;
  background:var(--stone-50);
  color:var(--muted);
  border:1px solid var(--line);
}
.badge strong{ color:var(--ink); font-weight:700; }

/* ---------- Buttons (pastel + vivid text) ---------- */
.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:9px 14px;border-radius:999px;border:1px solid transparent;
  font-size:13px;font-weight:700;text-decoration:none;cursor:pointer;white-space:nowrap;
  transition:transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease, color .18s ease;
  will-change:transform;
}
.btn:active{ transform:translateY(1px) scale(.98); }

.btn-pastel-indigo{
  background:var(--indigo-50);
  color:var(--indigo-700);
  border-color:var(--indigo-100);
  box-shadow:0 8px 18px rgba(79,70,229,0.12);
}
.btn-pastel-indigo:hover{
  background:var(--indigo-100);
  box-shadow:0 12px 26px rgba(79,70,229,0.18);
  transform:translateY(-1px);
}

.btn-pastel-mint{
  background:var(--mint-50);
  color:var(--mint-700);
  border-color:var(--mint-100);
  box-shadow:0 8px 18px rgba(16,185,129,0.12);
}
.btn-pastel-mint:hover{
  background:var(--mint-100);
  box-shadow:0 12px 26px rgba(16,185,129,0.18);
  transform:translateY(-1px);
}

.btn-ghost{
  background:#fff;
  color:var(--ink);
  border-color:var(--line);
}
.btn-ghost:hover{
  background:var(--stone-50);
  transform:translateY(-1px);
}

.btn-xs{ padding:6px 12px; font-size:12px; font-weight:700; }

/* icon bubble inside buttons */
.btn .ico{
  width:26px;height:26px;border-radius:999px;display:grid;place-items:center;
  background:#fff;border:1px solid var(--line);
}
.btn .ico .svg-ico{ width:14px;height:14px; }

/* ---------- Card ---------- */
.card{
  background:var(--card);
  border:1px solid var(--line);
  border-radius:var(--radius-xl);
  box-shadow:var(--shadow-lg);
  overflow:hidden;
  animation:cardIn .45s ease both;
}
@keyframes cardIn{
  from{opacity:0; transform:translateY(8px);}
  to{opacity:1; transform:translateY(0);}
}

/* toolbar */
.toolbar{
  padding:12px 16px;
  border-bottom:1px solid var(--line);
  display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;
  background:
    radial-gradient(800px 120px at 0% 0%, rgba(79,70,229,.06), transparent 40%),
    radial-gradient(700px 120px at 110% -10%, rgba(16,185,129,.06), transparent 38%),
    #fff;
}
.chip{
  display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;
  font-size:11px;color:var(--muted);background:var(--stone-50);border:1px dashed var(--line);
}
.chip .dot{
  width:8px;height:8px;border-radius:999px;background:var(--mint-600);
  box-shadow:0 0 0 3px var(--mint-50);
}

/* search */
.search{
  position:relative;
}
.search input{
  width:240px;max-width:280px;
  padding:8px 10px 8px 30px;border-radius:999px;border:1px solid var(--line);
  background:var(--stone-50);font-size:12px;color:var(--ink);outline:none;
  transition:border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
}
.search input:focus{
  background:#fff;border-color:var(--indigo-600);
  box-shadow:0 0 0 4px rgba(79,70,229,.08);
}
.search .icon{
  position:absolute;left:10px;top:50%;transform:translateY(-50%);
  color:var(--muted-2);
}
.search input[disabled]{opacity:.6;cursor:not-allowed;}

/* ---------- Table ---------- */
.table-wrap{ overflow-x:auto; }
.table{
  width:100%;
  border-collapse:separate;border-spacing:0;
  font-size:13px;
}
.table thead th{
  text-align:left;
  font-size:11px;text-transform:uppercase;letter-spacing:.08em;
  color:var(--muted);font-weight:700;
  padding:10px 16px;border-bottom:1px solid var(--line);
  background:rgba(250,250,250,.9);
  position:sticky;top:0;z-index:1;
}
.table tbody td{
  padding:14px 16px;border-top:1px solid #f1f5f9;vertical-align:top;
}
.table tbody tr{
  background:#fff;
  transition:background-color .16s ease, transform .18s ease, box-shadow .18s ease, opacity .3s ease;
  opacity:0; transform:translateY(6px);
}
.table tbody tr.reveal{
  opacity:1; transform:translateY(0);
}
.table tbody tr:hover{
  background:var(--stone-50);
  box-shadow:inset 0 0 0 9999px rgba(79,70,229,0.02);
}

/* Licitacion cell */
.l-main{ display:flex; flex-direction:column; gap:6px; max-width:520px; }
.l-head{ display:flex; align-items:center; gap:10px; }
.avatar{
  width:30px;height:30px;border-radius:999px;
  display:grid;place-items:center;
  font-size:11px;font-weight:800;letter-spacing:.02em;
  background:var(--indigo-50);color:var(--indigo-700);border:1px solid var(--indigo-100);
}
.l-title{
  font-weight:700;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.l-desc{
  padding-left:40px;
  font-size:11px;color:var(--muted);
  display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;
}

/* Pills */
.pill{
  display:inline-flex;align-items:center;gap:6px;
  padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700;border:1px solid transparent;
  white-space:nowrap;
}
.pill .dot{width:7px;height:7px;border-radius:999px;}
.pill-presencial{ background:var(--sky-50); color:var(--sky-700); border-color:var(--sky-200); }
.pill-presencial .dot{ background:var(--sky-700); }
.pill-linea{ background:var(--mint-50); color:var(--mint-700); border-color:var(--mint-100); }
.pill-linea .dot{ background:var(--mint-700); }
.pill-borrador{ background:var(--amber-50); color:var(--amber-800); border-color:var(--amber-200); }
.pill-proceso{ background:var(--indigo-50); color:var(--indigo-700); border-color:var(--indigo-100); }
.pill-cerrado{ background:var(--stone-100); color:#475569; border-color:var(--stone-300); }

/* Progress */
.progress{
  display:flex;flex-direction:column;gap:6px;min-width:160px;
}
.progress-top{
  display:flex;align-items:center;justify-content:space-between;font-size:11px;color:var(--muted);
}
.progress-bar{
  height:7px;border-radius:999px;background:var(--stone-100);overflow:hidden;border:1px solid var(--stone-200);
}
.progress-fill{
  height:100%;
  border-radius:999px;
  background:linear-gradient(90deg, var(--indigo-600), #0ea5e9, var(--mint-600));
  transition:width .6s cubic-bezier(.2,.8,.2,1);
  position:relative;
}
.progress-fill:after{
  content:"";
  position:absolute;inset:0;
  background:linear-gradient(90deg, rgba(255,255,255,.25), transparent 60%);
  animation:shine 1.8s ease-in-out infinite;
}
@keyframes shine{
  0%{transform:translateX(-80%);}
  60%{transform:translateX(80%);}
  100%{transform:translateX(80%);}
}

.actions{ text-align:right;white-space:nowrap; }
.actions .stack{
  display:inline-flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap;
}

/* Empty state */
.empty{
  padding:44px 16px;text-align:center;color:var(--muted);
}
.empty .bubble{
  width:56px;height:56px;border-radius:18px;margin:0 auto 10px;
  display:grid;place-items:center;
  background:var(--stone-50);border:1px dashed var(--line);
  color:#64748b;
}
.empty h3{ margin:0; font-size:14px; font-weight:700; color:var(--ink); }
.empty p{ margin:6px 0 10px; font-size:12px; }

/* Footer */
.footer{
  padding:10px 16px;border-top:1px solid var(--line);
  display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;
  font-size:11px;color:var(--muted);
  background:#fff;
}

/* reduced motion */
@media (prefers-reduced-motion: reduce){
  .card, .table tbody tr, .progress-fill:after{ animation:none !important; transition:none !important; }
}
</style>

<div class="page">
  {{-- Header --}}
  <div class="header">
    <div class="hgroup">
      <h1>Licitaciones</h1>
      <p>Administra el ciclo completo de tus licitaciones: convocatoria, aclaraciones, fallo y seguimiento.</p>
    </div>

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      @if(method_exists($licitaciones,'total'))
        <span class="badge">Total <strong>{{ $licitaciones->total() }}</strong></span>
      @endif

      <a href="{{ route('licitaciones.create.step1') }}" class="btn btn-pastel-indigo">
        <span class="ico" aria-hidden="true">
          {{-- plus icon --}}
          <svg class="svg-ico sm" viewBox="0 0 24 24">
            <path d="M12 5v14M5 12h14"/>
          </svg>
        </span>
        Nueva licitación
      </a>
    </div>
  </div>

  {{-- success --}}
  @if(session('success'))
    <div style="margin:12px 0 0;">
      <div class="badge" style="background:var(--mint-50);border-color:var(--mint-100);color:var(--mint-700);font-weight:700;">
        {{-- check-circle icon --}}
        <svg class="svg-ico sm" viewBox="0 0 24 24" aria-hidden="true">
          <circle cx="12" cy="12" r="9"></circle>
          <path d="M8.5 12.5l2.2 2.2 4.8-4.8"></path>
        </svg>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif

  <div class="card" style="margin-top:14px;">
    {{-- Toolbar --}}
    <div class="toolbar">
      <div class="chip"><span class="dot"></span> Flujo activo de licitaciones</div>

      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <div class="search">
          <span class="icon" aria-hidden="true">
            {{-- search icon --}}
            <svg class="svg-ico sm" viewBox="0 0 24 24">
              <circle cx="11" cy="11" r="7"></circle>
              <path d="M20 20l-3.5-3.5"></path>
            </svg>
          </span>
          <input type="text" placeholder="Buscar (próximamente)" disabled>
        </div>
      </div>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Licitación</th>
            <th>Convocatoria</th>
            <th>Modalidad</th>
            <th>Estatus</th>
            <th>Progreso</th>
            <th style="text-align:right;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        @forelse($licitaciones as $licitacion)
          @php
            $totalSteps = 12;
            $step = $licitacion->current_step ?? 0;
            $progress = $totalSteps > 0 ? min(100, max(0, ($step / $totalSteps) * 100)) : 0;

            $status = $licitacion->estatus;
            $statusLabel = ucfirst(str_replace('_',' ',$status));
            $statusClass = match($status){
              'borrador' => 'pill-borrador',
              'en_proceso' => 'pill-proceso',
              'cerrado' => 'pill-cerrado',
              default => 'pill-cerrado'
            };

            $isPresencial = $licitacion->modalidad === 'presencial';

            if ($step <= 9) {
              $continuarRoute = route('licitaciones.edit.step'.$step, $licitacion);
            } elseif ($step === 10) {
              $continuarRoute = route('licitaciones.checklist.compras.edit', $licitacion);
            } elseif ($step === 11) {
              $continuarRoute = route('licitaciones.checklist.facturacion.edit', $licitacion);
            } else {
              $continuarRoute = route('licitaciones.contabilidad.edit', $licitacion);
            }
          @endphp

          <tr>
            {{-- Licitación --}}
            <td>
              <div class="l-main">
                <div class="l-head">
                  <span class="avatar">{{ strtoupper(substr($licitacion->titulo,0,2)) }}</span>
                  <div class="l-title" title="{{ $licitacion->titulo }}">
                    {{ $licitacion->titulo }}
                  </div>
                </div>
                <div class="l-desc">{{ $licitacion->descripcion }}</div>
              </div>
            </td>

            {{-- Convocatoria --}}
            <td>
              @if($licitacion->fecha_convocatoria)
                <div style="font-weight:700;color:var(--ink);">
                  {{ $licitacion->fecha_convocatoria->format('d/m/Y') }}
                </div>
                <div style="font-size:11px;color:var(--muted-2);margin-top:2px;">
                  {{ $licitacion->fecha_convocatoria->diffForHumans() }}
                </div>
              @else
                <span style="font-size:11px;color:var(--muted-2);">Sin fecha</span>
              @endif
            </td>

            {{-- Modalidad --}}
            <td>
              <span class="pill {{ $isPresencial ? 'pill-presencial' : 'pill-linea' }}">
                <span class="dot"></span>
                {{ $isPresencial ? 'Presencial' : 'En línea' }}
              </span>
            </td>

            {{-- Estatus --}}
            <td>
              <span class="pill {{ $statusClass }}">{{ $statusLabel }}</span>
            </td>

            {{-- Progreso --}}
            <td>
              <div class="progress" data-progress="{{ $progress }}">
                <div class="progress-top">
                  <span>Paso {{ $step ?: '—' }} / {{ $totalSteps }}</span>
                  <span>{{ number_format($progress,0) }}%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: {{ $progress }}%"></div>
                </div>
              </div>
            </td>

            {{-- Acciones --}}
            <td class="actions">
              <div class="stack">
                <a href="{{ route('licitaciones.show', $licitacion) }}" class="btn btn-ghost btn-xs">
                  Ver detalle
                </a>
                <a href="{{ $continuarRoute }}" class="btn btn-pastel-mint btn-xs">
                  Continuar
                </a>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">
              <div class="empty">
                <div class="bubble" aria-hidden="true">
                  {{-- document icon --}}
                  <svg class="svg-ico lg" viewBox="0 0 24 24">
                    <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                    <path d="M14 2v6h6"/>
                    <path d="M9 13h6M9 17h6M9 9h2"/>
                  </svg>
                </div>
                <h3>Aún no hay licitaciones</h3>
                <p>Crea tu primera licitación para comenzar el flujo.</p>
                <a href="{{ route('licitaciones.create.step1') }}" class="btn btn-pastel-indigo btn-xs">
                  <span class="ico" aria-hidden="true">
                    <svg class="svg-ico sm" viewBox="0 0 24 24">
                      <path d="M12 5v14M5 12h14"/>
                    </svg>
                  </span>
                  Crear licitación
                </a>
              </div>
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>
    </div>

    {{-- Footer --}}
    <div class="footer">
      <div>
        @if(method_exists($licitaciones,'firstItem') && $licitaciones->total() > 0)
          Mostrando <strong style="color:var(--ink)">{{ $licitaciones->firstItem() }}</strong>–
          <strong style="color:var(--ink)">{{ $licitaciones->lastItem() }}</strong>
          de <strong style="color:var(--ink)">{{ $licitaciones->total() }}</strong>
          licitación(es)
        @endif
      </div>
      <div>{{ $licitaciones->links() }}</div>
    </div>
  </div>
</div>

<script>
(function(){
  const rows = document.querySelectorAll('.table tbody tr');
  rows.forEach((tr, i) => {
    requestAnimationFrame(() => {
      setTimeout(() => tr.classList.add('reveal'), 60 * i);
    });
  });
})();
</script>
@endsection
