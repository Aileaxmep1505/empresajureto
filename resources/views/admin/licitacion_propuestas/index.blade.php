@extends('layouts.app')

@section('title', 'Propuestas económicas')

@section('content')
<style>
  /* ===================== Minimal + Pro UI ===================== */
  .pe-page{
    --ink:#0f172a;
    --muted:#64748b;
    --muted2:#94a3b8;
    --line:#e5e7eb;
    --bg:#f6f7fb;
    --card:#ffffff;
    --shadow:0 14px 44px rgba(2,6,23,.08);
    --radius:18px;

    --primary:#0b1220;      /* negro pro */
    --primary-2:#111827;
    --primary-soft:#11182710;

    --blue:#2563eb;
    --green:#16a34a;
    --amber:#f59e0b;
    --red:#ef4444;
  }

  .pe-wrap{
    max-width: 1200px;
    margin: 0 auto;
    padding: 18px 14px 26px;
  }

  /* ===================== Header ===================== */
  .pe-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:14px;
    margin-bottom:14px;
  }
  .pe-title{
    margin:0;
    color:var(--ink);
    font-size:20px;
    font-weight:900;
    letter-spacing:-.02em;
  }
  .pe-sub{
    margin:6px 0 0;
    color:var(--muted);
    font-size:13px;
    line-height:1.45;
    max-width: 72ch;
  }
  .pe-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:flex-end;
  }

  /* ===================== Buttons ===================== */
  .pe-btn{
    border:1px solid var(--line);
    border-radius:999px;
    padding:10px 14px;
    font-weight:800;
    font-size:13px;
    display:inline-flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    text-decoration:none;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
    user-select:none;
    white-space:nowrap;
  }
  .pe-btn:active{ transform: translateY(1px); }
  .pe-btn-primary{
    background: linear-gradient(180deg, var(--primary), var(--primary-2));
    color:#fff;
    border-color: transparent;
    box-shadow: 0 14px 34px rgba(2,6,23,.22);
  }
  .pe-btn-primary:hover{ box-shadow: 0 18px 44px rgba(2,6,23,.26); }
  .pe-btn-ghost{
    background:#fff;
    color:var(--ink);
  }
  .pe-btn-ghost:hover{ background:#f9fafb; border-color:#d1d5db; }
  .pe-btn-sm{ padding:9px 12px; font-size:12px; gap:8px; }

  .pe-ico{ width:16px; height:16px; display:inline-block; }

  /* ===================== Card shell ===================== */
  .pe-card{
    background: var(--card);
    border:1px solid var(--line);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow:hidden;
  }

  /* ===================== Filters ===================== */
  .pe-filters{
    display:grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap:10px;
    padding: 12px;
    border-bottom:1px solid var(--line);
    background: linear-gradient(180deg, #ffffff, #fbfbfd);
  }
  .pe-field{ min-width:0; }
  .pe-label{
    display:block;
    font-size:11px;
    color:var(--muted);
    margin: 0 0 6px;
    font-weight:900;
    letter-spacing:.02em;
    text-transform:uppercase;
  }
  .pe-input, .pe-select{
    width:100%;
    border:1px solid var(--line);
    border-radius: 14px;
    padding: 10px 12px;
    font-size: 13px;
    color: var(--ink);
    background:#fff;
    outline:none;
    transition:border-color .12s ease, box-shadow .12s ease;
  }
  .pe-input:focus, .pe-select:focus{
    border-color:#c7d2fe;
    box-shadow: 0 0 0 4px rgba(99,102,241,.12);
  }

  /* ===================== Table (desktop) ===================== */
  .pe-table-wrap{
    overflow:auto;
    -webkit-overflow-scrolling: touch;
  }
  .pe-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    font-size:13px;
    min-width: 980px;
  }
  .pe-table thead th{
    position: sticky;
    top: 0;
    background:#f8fafc;
    color: var(--muted);
    font-size:11px;
    letter-spacing:.06em;
    text-transform:uppercase;
    font-weight:900;
    padding: 12px 12px;
    border-bottom:1px solid var(--line);
    z-index: 1;
  }
  .pe-table tbody td{
    padding: 12px 12px;
    border-bottom:1px solid #eef2f7;
    vertical-align:middle;
    color:var(--ink);
    background:#fff;
  }
  .pe-table tbody tr:hover td{ background:#fafbff; }

  .pe-code{
    display:flex;
    flex-direction:column;
    gap:4px;
  }
  .pe-link{
    color: var(--ink);
    text-decoration:none;
    font-weight:900;
    letter-spacing:-.01em;
  }
  .pe-link:hover{ text-decoration: underline; }
  .pe-meta{
    font-size:12px;
    color:var(--muted);
  }

  /* ===================== Status pill ===================== */
  .pe-pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
    border:1px solid var(--line);
    background:#fff;
  }
  .pe-dot{ width:8px; height:8px; border-radius:999px; background: currentColor; }
  .pe-draft{ color: var(--amber); background: #fffbeb; border-color:#fde68a; }
  .pe-revisar{ color: #4f46e5; background:#eef2ff; border-color:#c7d2fe; }
  .pe-enviada{ color: var(--blue); background:#eff6ff; border-color:#bfdbfe; }
  .pe-adjudicada{ color: var(--green); background:#ecfdf5; border-color:#bbf7d0; }
  .pe-no{ color: var(--red); background:#fef2f2; border-color:#fecaca; }

  /* ===================== Money ===================== */
  .pe-money{ font-weight:900; letter-spacing:-.01em; }
  .pe-currency{ color:var(--muted); font-weight:900; margin-right:6px; }

  /* ===================== Mobile cards ===================== */
  .pe-mobile{ display:none; }
  .pe-m-list{ padding: 10px 12px 12px; display:grid; gap:10px; }
  .pe-m-card{
    border:1px solid var(--line);
    border-radius: 16px;
    background:#fff;
    padding: 12px;
    box-shadow: 0 10px 24px rgba(2,6,23,.06);
  }
  .pe-m-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:10px;
  }
  .pe-m-title{
    font-weight:900;
    color:var(--ink);
    line-height:1.2;
  }
  .pe-m-sub{
    margin-top:4px;
    color:var(--muted);
    font-size:12px;
    line-height:1.35;
  }
  .pe-m-grid{
    margin-top:10px;
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:10px;
  }
  .pe-m-item{
    border:1px solid #eef2f7;
    border-radius: 14px;
    padding: 10px;
    background: #fbfcff;
  }
  .pe-m-k{
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.06em;
    color:var(--muted);
  }
  .pe-m-v{
    margin-top:4px;
    font-weight:900;
    color:var(--ink);
    font-size:13px;
  }
  .pe-m-actions{
    margin-top:12px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }
  .pe-btn-block{
    width:100%;
    justify-content:center;
  }

  /* ===================== Empty ===================== */
  .pe-empty{
    padding:18px 14px;
    color:var(--muted);
    font-size:13px;
  }

  /* ===================== Pagination ===================== */
  .pe-pager{ margin-top:14px; }
  .pe-pager .pagination{ gap:6px; flex-wrap:wrap; }
  .pe-pager .page-link{
    border-radius:999px !important;
    border:1px solid var(--line) !important;
    color:var(--ink) !important;
    font-weight:900;
    font-size:12px;
    padding:8px 12px;
  }
  .pe-pager .page-item.active .page-link{
    background: linear-gradient(180deg, var(--primary), var(--primary-2)) !important;
    border-color: transparent !important;
    color:#fff !important;
  }

  /* ===================== Responsive ===================== */
  @media (max-width: 980px){
    .pe-top{ flex-direction:column; align-items:stretch; }
    .pe-actions{ justify-content:flex-start; }
    .pe-filters{ grid-template-columns: 1fr 1fr; }
    .pe-filters .pe-actions-inline{ grid-column: 1 / -1; display:flex; justify-content:flex-end; }
  }
  @media (max-width: 720px){
    .pe-table-wrap{ display:none; }
    .pe-mobile{ display:block; }
  }
  @media (max-width: 520px){
    .pe-wrap{ padding: 14px 10px 20px; }
    .pe-filters{ grid-template-columns: 1fr; }
    .pe-filters .pe-actions-inline{ justify-content:stretch; }
    .pe-btn{ width:100%; justify-content:center; }
  }
</style>

@php
  $labels = [
    'draft' => 'Borrador',
    'revisar' => 'En revisión',
    'enviada' => 'Enviada',
    'adjudicada' => 'Adjudicada',
    'no_adjudicada' => 'No adjudicada',
  ];
@endphp

<div class="pe-page">
  <div class="pe-wrap">
    <div class="pe-top">
      <div>
        <h1 class="pe-title">Propuestas económicas comparativas</h1>
        <p class="pe-sub">
          Revisa las propuestas generadas. Puedes <strong>entrar a una cotización</strong> para ver detalles o crear una nueva desde PDFs.
        </p>
      </div>

      <div class="pe-actions">
        {{-- ✅ Botón NUEVO: manda a /admin/licitacion-pdfs/create --}}
        <a href="{{ url('/admin/licitacion-pdfs/create') }}" class="pe-btn pe-btn-primary">
          <svg class="pe-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Nueva cotización (PDF)
        </a>

        <a href="{{ route('admin.licitacion-propuestas.index') }}" class="pe-btn pe-btn-ghost">
          <svg class="pe-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
          </svg>
          Actualizar
        </a>
      </div>
    </div>

    <div class="pe-card">
      <form method="GET" class="pe-filters">
        <div class="pe-field">
          <label class="pe-label" for="licitacion_id">Licitación ID</label>
          <input class="pe-input" type="number" name="licitacion_id" id="licitacion_id" value="{{ request('licitacion_id') }}" inputmode="numeric">
        </div>

        <div class="pe-field">
          <label class="pe-label" for="requisicion_id">Requisición ID</label>
          <input class="pe-input" type="number" name="requisicion_id" id="requisicion_id" value="{{ request('requisicion_id') }}" inputmode="numeric">
        </div>

        <div class="pe-field">
          <label class="pe-label" for="status">Estatus</label>
          <select class="pe-select" name="status" id="status">
            <option value="">Todos</option>
            <option value="draft" {{ request('status')==='draft'?'selected':'' }}>Borrador</option>
            <option value="revisar" {{ request('status')==='revisar'?'selected':'' }}>En revisión</option>
            <option value="enviada" {{ request('status')==='enviada'?'selected':'' }}>Enviada</option>
            <option value="adjudicada" {{ request('status')==='adjudicada'?'selected':'' }}>Adjudicada</option>
            <option value="no_adjudicada" {{ request('status')==='no_adjudicada'?'selected':'' }}>No adjudicada</option>
          </select>
        </div>

        <div class="pe-actions-inline" style="display:flex; align-items:flex-end; gap:10px;">
          <button type="submit" class="pe-btn pe-btn-primary pe-btn-sm">
            <svg class="pe-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M3 5h18M6 12h12M10 19h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Filtrar
          </button>

          <a href="{{ route('admin.licitacion-propuestas.index') }}" class="pe-btn pe-btn-ghost pe-btn-sm">
            <svg class="pe-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M21 12a9 9 0 1 1-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M21 3v6h-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Limpiar
          </a>
        </div>
      </form>

      @if($propuestas->count())
        {{-- ===================== Desktop table ===================== --}}
        <div class="pe-table-wrap">
          <table class="pe-table">
            <thead>
              <tr>
                <th style="min-width:160px;">Código</th>
                <th style="min-width:190px;">Licitación / Requisición</th>
                <th style="min-width:260px;">Título</th>
                <th style="min-width:120px;">Fecha</th>
                <th style="min-width:160px;">Status</th>
                <th style="min-width:140px; text-align:right;">Total</th>
                <th style="min-width:150px; text-align:right;">Acción</th>
              </tr>
            </thead>
            <tbody>
              @foreach($propuestas as $p)
                @php
                  $statusClass = match($p->status) {
                    'draft' => 'pe-draft',
                    'revisar' => 'pe-revisar',
                    'enviada' => 'pe-enviada',
                    'adjudicada' => 'pe-adjudicada',
                    'no_adjudicada' => 'pe-no',
                    default => 'pe-draft',
                  };
                @endphp
                <tr>
                  <td>
                    <div class="pe-code">
                      <a href="{{ route('admin.licitacion-propuestas.show',$p) }}" class="pe-link">
                        {{ $p->codigo }}
                      </a>
                      <span class="pe-meta">ID #{{ $p->id }}</span>
                    </div>
                  </td>

                  <td>
                    @if($p->licitacion_id)
                      <div style="font-weight:900;">Licitación {{ $p->licitacion_id }}</div>
                    @endif
                    @if($p->requisicion_id)
                      <div class="pe-meta">Req. {{ $p->requisicion_id }}</div>
                    @endif
                    @if(!$p->licitacion_id && !$p->requisicion_id)
                      <div class="pe-meta">—</div>
                    @endif
                  </td>

                  <td>
                    <div style="font-weight:900; line-height:1.25;">
                      {{ $p->titulo }}
                    </div>
                  </td>

                  <td>
                    <div style="font-weight:900;">
                      {{ $p->fecha?->format('d/m/Y') ?? '—' }}
                    </div>
                    <div class="pe-meta">
                      {{ $p->created_at?->format('d/m/Y H:i') ?? '' }}
                    </div>
                  </td>

                  <td>
                    <span class="pe-pill {{ $statusClass }}">
                      <span class="pe-dot"></span>
                      {{ $labels[$p->status] ?? $p->status }}
                    </span>
                  </td>

                  <td style="text-align:right;">
                    <span class="pe-currency">{{ $p->moneda ?? 'MXN' }}</span>
                    <span class="pe-money">{{ number_format($p->total, 2) }}</span>
                  </td>

                  {{-- ✅ Volver a entrar a la cotización --}}
                  <td style="text-align:right;">
                    <a href="{{ route('admin.licitacion-propuestas.show',$p) }}" class="pe-btn pe-btn-primary pe-btn-sm">
                      <svg class="pe-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M10 7l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                      Entrar
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- ===================== Mobile cards ===================== --}}
        <div class="pe-mobile">
          <div class="pe-m-list">
            @foreach($propuestas as $p)
              @php
                $statusClass = match($p->status) {
                  'draft' => 'pe-draft',
                  'revisar' => 'pe-revisar',
                  'enviada' => 'pe-enviada',
                  'adjudicada' => 'pe-adjudicada',
                  'no_adjudicada' => 'pe-no',
                  default => 'pe-draft',
                };
              @endphp

              <div class="pe-m-card">
                <div class="pe-m-top">
                  <div style="min-width:0;">
                    <div class="pe-m-title">{{ $p->codigo }}</div>
                    <div class="pe-m-sub">
                      {{ $p->titulo }}
                    </div>
                    <div class="pe-m-sub" style="margin-top:6px;">
                      <span class="pe-pill {{ $statusClass }}">
                        <span class="pe-dot"></span>
                        {{ $labels[$p->status] ?? $p->status }}
                      </span>
                    </div>
                  </div>
                  <div style="text-align:right;">
                    <div class="pe-m-sub">Total</div>
                    <div class="pe-money" style="font-size:14px;">
                      <span class="pe-currency">{{ $p->moneda ?? 'MXN' }}</span>{{ number_format($p->total, 2) }}
                    </div>
                  </div>
                </div>

                <div class="pe-m-grid">
                  <div class="pe-m-item">
                    <div class="pe-m-k">Licitación / Req</div>
                    <div class="pe-m-v">
                      @if($p->licitacion_id) Lic {{ $p->licitacion_id }} @endif
                      @if($p->requisicion_id) · Req {{ $p->requisicion_id }} @endif
                      @if(!$p->licitacion_id && !$p->requisicion_id) — @endif
                    </div>
                  </div>
                  <div class="pe-m-item">
                    <div class="pe-m-k">Fecha</div>
                    <div class="pe-m-v">
                      {{ $p->fecha?->format('d/m/Y') ?? '—' }}
                      <div class="pe-meta" style="margin-top:2px;">
                        {{ $p->created_at?->format('d/m/Y H:i') ?? '' }}
                      </div>
                    </div>
                  </div>
                </div>

                <div class="pe-m-actions">
                  <a href="{{ route('admin.licitacion-propuestas.show',$p) }}" class="pe-btn pe-btn-primary pe-btn-block">
                    <svg class="pe-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M10 7l5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Entrar a la cotización
                  </a>

                  <a href="{{ url('/admin/licitacion-pdfs/create') }}" class="pe-btn pe-btn-ghost pe-btn-block">
                    <svg class="pe-ico" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                      <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    Nueva cotización (PDF)
                  </a>
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="pe-pager" style="padding: 10px 12px 12px;">
          {{ $propuestas->withQueryString()->links() }}
        </div>
      @else
        <div class="pe-empty">
          Aún no hay propuestas registradas. Crea una nueva desde PDFs con el botón superior.
        </div>
      @endif
    </div>
  </div>
</div>
@endsection
