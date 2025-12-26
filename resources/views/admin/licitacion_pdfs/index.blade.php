@extends('layouts.app')

@section('title', 'Requisiciones PDF')

@section('content')
<style>
/* =========================
   REQUISICIONES PDF — PRO UI
   Minimalista + premium + animaciones
   (sin fuentes externas, respeta app.blade)
========================= */
.rq-page{
  --ink:#0b1220;
  --muted:#667085;
  --muted2:#94a3b8;
  --line:#e6eaf2;
  --line2:#eef2f7;
  --card:#ffffff;
  --shadow:0 18px 55px rgba(2,6,23,.08);
  --shadow2:0 10px 30px rgba(2,6,23,.08);
  --radius:18px;

  --black:#0b1220;          /* ✅ botones negros */
  --black2:#0f172a;
  --blackSoft:rgba(2,6,23,.08);

  --blue:#2563eb;
  --green:#16a34a;
  --amber:#f59e0b;
  --red:#ef4444;

  --ease:cubic-bezier(.2,.8,.2,1);
}

.rq-wrap{
  max-width:1200px;
  margin:0 auto;
  padding:18px 14px 26px;
}

/* ===== Header ===== */
.rq-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:14px;
  margin-bottom:14px;
}
.rq-title{
  margin:0;
  color:var(--ink);
  font-size:20px;
  font-weight:900;
  letter-spacing:-.02em;
}
.rq-sub{
  margin:6px 0 0;
  color:var(--muted);
  font-size:13px;
  line-height:1.35;
  max-width:76ch;
}

/* ===== Buttons ===== */
.rq-btn{
  border-radius:999px;
  border:1px solid var(--line);
  padding:10px 14px;
  font-weight:850;
  font-size:13px;
  display:inline-flex;
  align-items:center;
  gap:10px;
  cursor:pointer;
  text-decoration:none;
  user-select:none;
  white-space:nowrap;
  background:#fff;
  color:var(--ink);
  transition:transform .18s var(--ease), box-shadow .18s var(--ease), filter .18s var(--ease), background .18s var(--ease), border-color .18s var(--ease);
  will-change:transform;
}
.rq-btn:active{ transform: translateY(1px); }
.rq-btn-primary{
  background: linear-gradient(180deg, var(--black), var(--black2));
  color:#fff;
  border-color: transparent;
  box-shadow: 0 16px 40px rgba(2,6,23,.20);
}
.rq-btn-primary:hover{ box-shadow: 0 22px 56px rgba(2,6,23,.26); filter:brightness(1.02); transform: translateY(-1px); }
.rq-btn-ghost:hover{ background:#f8fafc; border-color:#d7dde7; transform: translateY(-1px); }
.rq-btn-sm{ padding:9px 12px; font-size:12px; gap:8px; }
.rq-ico{ width:16px; height:16px; display:inline-block; }

/* ===== Surface / Card ===== */
.rq-surface{
  border:1px solid var(--line);
  border-radius: var(--radius);
  background: linear-gradient(180deg, #fff, #fcfdff);
  box-shadow: var(--shadow);
  overflow:hidden;
  position:relative;
}
.rq-surface::before{
  content:"";
  position:absolute;
  inset:-1px;
  background:
    radial-gradient(800px 300px at 0% 0%, rgba(37,99,235,.10), transparent 55%),
    radial-gradient(900px 360px at 100% 0%, rgba(2,6,23,.06), transparent 55%);
  pointer-events:none;
}

/* ===== Filters ===== */
.rq-filters{
  position:relative;
  z-index:1;
  display:grid;
  grid-template-columns: 1fr 1fr 1fr auto;
  gap:10px;
  padding: 12px;
  border-bottom:1px solid var(--line);
  background: rgba(255,255,255,.72);
  backdrop-filter: blur(10px);
}
.rq-field{ min-width:0; }
.rq-label{
  display:block;
  font-size:11px;
  color:var(--muted);
  margin: 0 0 6px;
  font-weight:850;
  letter-spacing:.02em;
}
.rq-input, .rq-select{
  width:100%;
  border:1px solid var(--line2);
  border-radius: 14px;
  padding: 10px 12px;
  font-size: 13px;
  color: var(--ink);
  background: rgba(255,255,255,.9);
  outline:none;
  transition: border-color .18s var(--ease), box-shadow .18s var(--ease), transform .18s var(--ease);
}
.rq-input::placeholder{ color:#a3afc2; }
.rq-input:focus, .rq-select:focus{
  border-color: rgba(37,99,235,.35);
  box-shadow: 0 0 0 4px rgba(37,99,235,.10);
}
.rq-actions{
  display:flex;
  align-items:flex-end;
  gap:10px;
  justify-content:flex-end;
}

/* ===== Grid ===== */
.rq-grid{
  position:relative;
  z-index:1;
  padding: 12px;
  display:grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap:12px;
}

/* ===== Item card ===== */
.rq-item{
  position:relative;
  display:flex;
  flex-direction:column;
  gap:10px;
  padding:14px;
  border-radius:16px;
  border:1px solid rgba(226,232,240,.9);
  background: rgba(255,255,255,.92);
  text-decoration:none;
  color:inherit;
  transform: translateY(0);
  transition: transform .22s var(--ease), box-shadow .22s var(--ease), border-color .22s var(--ease), background .22s var(--ease);
  will-change: transform;
  overflow:hidden;
  isolation:isolate;
}
.rq-item::before{
  content:"";
  position:absolute;
  inset:-1px;
  background:
    radial-gradient(400px 220px at 0% 0%, rgba(37,99,235,.14), transparent 55%),
    radial-gradient(420px 240px at 100% 20%, rgba(2,6,23,.08), transparent 60%);
  opacity:0;
  transition:opacity .22s var(--ease);
  pointer-events:none;
  z-index:-1;
}
.rq-item:hover{
  transform: translateY(-3px);
  border-color: rgba(99,102,241,.28);
  box-shadow: var(--shadow2);
}
.rq-item:hover::before{ opacity:1; }
.rq-item:focus-visible{
  outline:none;
  box-shadow: 0 0 0 4px rgba(37,99,235,.16), var(--shadow2);
  border-color: rgba(37,99,235,.35);
}

/* subtle entrance */
@media (prefers-reduced-motion: no-preference){
  .rq-item{
    animation: rqPop .36s var(--ease) both;
  }
  .rq-item:nth-child(1){ animation-delay:.02s }
  .rq-item:nth-child(2){ animation-delay:.04s }
  .rq-item:nth-child(3){ animation-delay:.06s }
  .rq-item:nth-child(4){ animation-delay:.08s }
  .rq-item:nth-child(5){ animation-delay:.10s }
  .rq-item:nth-child(6){ animation-delay:.12s }
  .rq-item:nth-child(7){ animation-delay:.14s }
  .rq-item:nth-child(8){ animation-delay:.16s }
  .rq-item:nth-child(9){ animation-delay:.18s }
  @keyframes rqPop{
    from{ transform: translateY(6px); opacity:0; }
    to{ transform: translateY(0); opacity:1; }
  }
}

/* Header inside card */
.rq-toprow{
  display:flex;
  gap:10px;
  align-items:flex-start;
  min-width:0;
}
.rq-icon{
  width:36px;
  height:36px;
  border-radius: 14px;
  display:flex;
  align-items:center;
  justify-content:center;
  background: rgba(2,6,23,.05);
  border: 1px solid rgba(2,6,23,.06);
  flex:0 0 auto;
}
.rq-name{
  font-weight:950;
  letter-spacing:-.01em;
  font-size:13px;
  color:var(--ink);
  line-height:1.25;
  overflow:hidden;
  text-overflow:ellipsis;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
}
.rq-meta{
  margin-top:4px;
  font-size:12px;
  color:var(--muted);
  display:flex;
  gap:10px;
  flex-wrap:wrap;
}

/* Pills */
.rq-pills{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
}
.rq-pill{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:6px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:850;
  border:1px solid rgba(226,232,240,.95);
  background:#fff;
  color:var(--ink);
}
.rq-dot{ width:8px; height:8px; border-radius:999px; background: currentColor; }

/* Status variants (mantiene minimalismo) */
.rq-status{ color:var(--muted); background: rgba(255,255,255,.9); }
.rq-status-uploaded{ color: var(--ink); background: rgba(2,6,23,.04); border-color: rgba(2,6,23,.10); }
.rq-status-processing{ color: var(--amber); background: #fffbeb; border-color:#fde68a; }
.rq-status-items_extracted{ color: var(--green); background:#ecfdf5; border-color:#bbf7d0; }
.rq-status-proposal_ready{ color: #4f46e5; background:#eef2ff; border-color:#c7d2fe; }
.rq-status-error{ color: var(--red); background:#fef2f2; border-color:#fecaca; }

/* Footer */
.rq-foot{
  margin-top:auto;
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:10px;
}
.rq-hint{
  font-size:12px;
  color:var(--muted2);
  font-weight:800;
}
.rq-open{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 10px;
  border-radius:999px;
  border:1px solid rgba(226,232,240,.95);
  background:#fff;
  color:var(--ink);
  font-size:12px;
  font-weight:900;
  transition: transform .18s var(--ease), background .18s var(--ease), border-color .18s var(--ease);
}
.rq-item:hover .rq-open{
  transform: translateY(-1px);
  background: #f8fafc;
  border-color:#d7dde7;
}

/* ===== Empty ===== */
.rq-empty{
  position:relative;
  z-index:1;
  padding: 18px 14px;
}
.rq-empty-box{
  border-radius: var(--radius);
  border:1px dashed rgba(148,163,184,.55);
  padding: 28px 16px;
  text-align:center;
  background: linear-gradient(135deg, #ffffff, #f3f4f6);
  color:var(--muted);
}
.rq-empty-box h3{
  margin:0 0 6px;
  color:var(--ink);
  font-size:16px;
  font-weight:950;
}
.rq-empty-box p{ margin:0; font-size:13px; }

/* ===== Toast ===== */
.rq-toast{
  position:fixed;
  right:16px;
  bottom:16px;
  padding:12px 14px;
  border-radius:999px;
  background: rgba(2,6,23,.94);
  color:#fff;
  font-size:13px;
  display:flex;
  align-items:center;
  gap:10px;
  box-shadow: 0 18px 40px rgba(2,6,23,.35);
  animation: rqToast .26s var(--ease);
  z-index: 80;
  max-width: min(520px, calc(100vw - 32px));
}
@keyframes rqToast{
  from{ transform:translateY(10px); opacity:0; }
  to{ transform:translateY(0); opacity:1; }
}

/* ===== Pagination (bootstrap-friendly) ===== */
.rq-pager{ padding: 10px 12px 12px; position:relative; z-index:1; }
.rq-pager .pagination{ gap:6px; flex-wrap:wrap; }
.rq-pager .page-link{
  border-radius:999px !important;
  border:1px solid var(--line) !important;
  color:var(--ink) !important;
  font-weight:950;
  font-size:12px;
  padding:8px 12px;
}
.rq-pager .page-item.active .page-link{
  background: linear-gradient(180deg, var(--black), var(--black2)) !important;
  border-color: transparent !important;
  color:#fff !important;
}

/* ===== Responsive ===== */
@media (max-width: 1100px){
  .rq-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 900px){
  .rq-head{ flex-direction:column; align-items:stretch; }
  .rq-actionsTop{ display:flex; gap:10px; flex-wrap:wrap; }
  .rq-filters{ grid-template-columns: 1fr 1fr; }
  .rq-actions{ grid-column: 1 / -1; justify-content:flex-end; }
}
@media (max-width: 560px){
  .rq-wrap{ padding: 14px 10px 20px; }
  .rq-filters{ grid-template-columns: 1fr; }
  .rq-actions{ justify-content:stretch; }
  .rq-btn{ width:100%; justify-content:center; }
  .rq-grid{ grid-template-columns: 1fr; padding: 10px; }
}

/* Motion safety */
@media (prefers-reduced-motion: reduce){
  .rq-btn, .rq-item, .rq-open, .rq-input, .rq-select{ transition:none !important; animation:none !important; }
}
</style>

@if(session('status'))
  <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3500)" class="rq-toast">
    <span>✅</span>
    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ session('status') }}</span>
  </div>
@endif

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

@php
  $labels = [
    'uploaded' => 'Subido',
    'processing' => 'Procesando con IA',
    'items_extracted' => 'Items extraídos',
    'proposal_ready' => 'Propuesta lista',
    'error' => 'Error',
  ];
@endphp

<div class="rq-page">
  <div class="rq-wrap">

    <div class="rq-head">
      <div>
        <h1 class="rq-title">Requisiciones en PDF</h1>
        <p class="rq-sub">Sube PDFs de las bases / requisiciones para que la IA las convierta en renglones perfectos.</p>
      </div>

      <div class="rq-actionsTop">
        <a href="{{ route('admin.licitacion-pdfs.create') }}" class="rq-btn rq-btn-primary">
          <svg class="rq-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Subir PDF de requisición
        </a>
      </div>
    </div>

    <div class="rq-surface">

      <form method="GET" class="rq-filters">
        <div class="rq-field">
          <label class="rq-label" for="licitacion_id">Licitación ID</label>
          <input class="rq-input" type="number" name="licitacion_id" id="licitacion_id"
                 value="{{ request('licitacion_id') }}" placeholder="Ej. 1024" inputmode="numeric">
        </div>

        <div class="rq-field">
          <label class="rq-label" for="requisicion_id">Requisición ID</label>
          <input class="rq-input" type="number" name="requisicion_id" id="requisicion_id"
                 value="{{ request('requisicion_id') }}" placeholder="Opcional" inputmode="numeric">
        </div>

        <div class="rq-field">
          <label class="rq-label" for="status">Estatus</label>
          <select class="rq-select" name="status" id="status">
            <option value="">Todos</option>
            <option value="uploaded" {{ request('status')==='uploaded'?'selected':'' }}>Subido</option>
            <option value="processing" {{ request('status')==='processing'?'selected':'' }}>Procesando</option>
            <option value="items_extracted" {{ request('status')==='items_extracted'?'selected':'' }}>Items extraídos</option>
            <option value="proposal_ready" {{ request('status')==='proposal_ready'?'selected':'' }}>Propuesta lista</option>
            <option value="error" {{ request('status')==='error'?'selected':'' }}>Error</option>
          </select>
        </div>

        <div class="rq-actions">
          <button class="rq-btn rq-btn-primary rq-btn-sm" type="submit">
            <svg class="rq-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M3 5h18M6 12h12M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Filtrar
          </button>

          <a href="{{ route('admin.licitacion-pdfs.index') }}" class="rq-btn rq-btn-ghost rq-btn-sm">
            <svg class="rq-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M21 12a9 9 0 1 1-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Limpiar
          </a>
        </div>
      </form>

      @if($pdfs->count())
        <div class="rq-grid">
          @foreach($pdfs as $pdf)
            @php
              $statusClass = match($pdf->status) {
                'uploaded' => 'rq-status-uploaded',
                'processing' => 'rq-status-processing',
                'items_extracted' => 'rq-status-items_extracted',
                'proposal_ready' => 'rq-status-proposal_ready',
                'error' => 'rq-status-error',
                default => 'rq-status-uploaded',
              };
            @endphp

            {{-- ✅ Ya NO es <a> para poder tener 2 botones (Abrir + Chat IA) --}}
            <div class="rq-item" tabindex="0">
              <div class="rq-toprow">
                <div class="rq-icon" aria-hidden="true">
                  <svg class="rq-ico" viewBox="0 0 24 24" fill="none">
                    <path d="M7 3h7l3 3v15a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M14 3v4h4" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                  </svg>
                </div>

                <div style="min-width:0;">
                  <div class="rq-name">{{ $pdf->original_filename }}</div>
                  <div class="rq-meta">
                    <span>#{{ $pdf->id }}</span>
                    <span>•</span>
                    <span>{{ $pdf->created_at?->format('d/m/Y H:i') }}</span>
                  </div>
                </div>
              </div>

              <div class="rq-pills">
                @if($pdf->licitacion_id)
                  <span class="rq-pill">
                    <span class="rq-dot" style="color: var(--blue);"></span>
                    Licitación: {{ $pdf->licitacion_id }}
                  </span>
                @endif

                @if($pdf->requisicion_id)
                  <span class="rq-pill">
                    <span class="rq-dot" style="color: var(--blue);"></span>
                    Req: {{ $pdf->requisicion_id }}
                  </span>
                @endif

                <span class="rq-pill">
                  <span class="rq-dot" style="color: var(--muted2);"></span>
                  {{ $pdf->pages_count ?? '—' }} páginas
                </span>

                <span class="rq-pill rq-status {{ $statusClass }}">
                  <span class="rq-dot"></span>
                  {{ $labels[$pdf->status] ?? $pdf->status }}
                </span>
              </div>

              <div class="rq-foot">
                <span class="rq-hint">Acciones</span>

                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                  <a class="rq-open" href="{{ route('admin.licitacion-pdfs.show', $pdf) }}">
                    Abrir
                    <svg class="rq-ico" viewBox="0 0 24 24" fill="none">
                      <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                  </a>

                  {{-- ✅ Botón: Chat IA del PDF (RUTA CORRECTA) --}}
                  <a class="rq-open" href="{{ route('admin.licitacion-pdfs.ai.show', $pdf) }}">
                    Chat IA
                    <svg class="rq-ico" viewBox="0 0 24 24" fill="none">
                      <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"
                            stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                  </a>
                </div>
              </div>
            </div>
          @endforeach
        </div>

        <div class="rq-pager">
          {{ $pdfs->withQueryString()->links() }}
        </div>
      @else
        <div class="rq-empty">
          <div class="rq-empty-box">
            <h3>No hay PDFs de requisiciones todavía</h3>
            <p>Sube tu primer PDF para que la IA comience a extraer los renglones de la licitación.</p>
            <div style="margin-top:14px;">
              <a href="{{ route('admin.licitacion-pdfs.create') }}" class="rq-btn rq-btn-primary">
                <svg class="rq-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Subir PDF
              </a>
            </div>
          </div>
        </div>
      @endif

    </div>
  </div>
</div>
@endsection
