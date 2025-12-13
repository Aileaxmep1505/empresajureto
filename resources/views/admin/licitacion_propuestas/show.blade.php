@extends('layouts.app')

@section('title', $propuesta->codigo.' - Propuesta económica')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
  :root{
    --ink:#0f172a;
    --muted:#6b7280;
    --border:#e5e7eb;
    --soft:#f8fafc;
    --accent:#2563eb;
    --accent-soft:#dbeafe;
    --success:#16a34a;
    --success-soft:#dcfce7;
    --warning:#f59e0b;
    --warning-soft:#fffbeb;
    --danger:#ef4444;
    --danger-soft:#fef2f2;
    --radius:18px;
    --shadow:0 18px 44px rgba(15,23,42,.10);
  }

  .pe-wrap{
    font-family:"Inter",system-ui,-apple-system,"Segoe UI",sans-serif;
    color:var(--ink);
    max-width:1180px;
    margin:0 auto;
    padding:16px;
    display:flex;
    flex-direction:column;
    gap:14px;
  }

  .pe-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }

  .pe-title{
    display:flex;
    align-items:flex-start;
    gap:12px;
    min-width:0;
  }

  .pe-icon{
    width:44px; height:44px;
    border-radius:14px;
    display:grid; place-items:center;
    background:linear-gradient(135deg,var(--accent-soft),#fff);
    border:1px solid rgba(191,219,254,1);
    box-shadow:0 10px 26px rgba(37,99,235,.10);
    flex:0 0 auto;
  }

  .pe-h1{
    font-weight:800;
    font-size:1.05rem;
    line-height:1.2;
    margin:0;
    word-break:break-word;
  }

  .pe-sub{
    margin-top:6px;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    color:var(--muted);
    font-size:.82rem;
  }

  .pe-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:4px 10px;
    border-radius:999px;
    border:1px solid var(--border);
    background:#fff;
    font-size:.78rem;
    color:var(--muted);
  }

  .pe-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
  }

  .pe-btn{
    border:none;
    border-radius:999px;
    padding:9px 14px;
    font-weight:700;
    font-size:.88rem;
    display:inline-flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    text-decoration:none;
    transition:transform .08s ease, box-shadow .18s ease, filter .18s ease;
    white-space:nowrap;
  }

  .pe-btn-primary{
    background:linear-gradient(135deg,#eff6ff,var(--accent-soft));
    color:#1d4ed8;
    box-shadow:0 14px 32px rgba(37,99,235,.18);
  }
  .pe-btn-primary:hover{ transform:translateY(-1px); box-shadow:0 18px 44px rgba(37,99,235,.24); }

  .pe-btn-ghost{
    background:#fff;
    color:var(--ink);
    border:1px solid var(--border);
    box-shadow:0 10px 26px rgba(15,23,42,.08);
  }
  .pe-btn-ghost:hover{ transform:translateY(-1px); box-shadow:0 14px 34px rgba(15,23,42,.10); }

  .pe-btn[disabled], .pe-btn[aria-disabled="true"]{
    opacity:.55;
    cursor:not-allowed;
    transform:none !important;
    box-shadow:none !important;
  }

  .pe-btn-mini{
    padding:7px 10px;
    font-size:.78rem;
    font-weight:800;
  }

  .pe-btn-green{
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff;
    box-shadow:0 14px 32px rgba(22,163,74,.18);
  }
  .pe-btn-red{
    background:#fff;
    color:#b91c1c;
    border:1px solid #fecaca;
    box-shadow:0 10px 26px rgba(15,23,42,.06);
  }

  .pe-status{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:6px 12px;
    border-radius:999px;
    font-size:.8rem;
    font-weight:700;
    border:1px solid var(--border);
    background:#fff;
    color:var(--muted);
  }
  .pe-dot{ width:8px; height:8px; border-radius:999px; background:currentColor; }

  .st-draft{ color:var(--warning); background:var(--warning-soft); border-color:#fde68a; }
  .st-revisar{ color:var(--accent); background:#eff6ff; border-color:rgba(191,219,254,1); }
  .st-enviada{ color:#0284c7; background:#e0f2fe; border-color:#bae6fd; }
  .st-adjudicada{ color:var(--success); background:var(--success-soft); border-color:#bbf7d0; }
  .st-no_adjudicada{ color:var(--danger); background:var(--danger-soft); border-color:#fecaca; }

  .pe-card{
    border-radius:var(--radius);
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
    overflow:hidden;
  }
  .pe-card-h{
    padding:12px 14px;
    border-bottom:1px solid rgba(229,231,235,.85);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    flex-wrap:wrap;
  }
  .pe-card-title{
    display:flex;
    align-items:center;
    gap:10px;
    font-weight:800;
    font-size:.92rem;
  }
  .pe-card-b{ padding:14px; }

  .pe-link{
    text-decoration:none;
    border-radius:999px;
    padding:8px 12px;
    font-size:.82rem;
    font-weight:800;
    border:1px solid rgba(191,219,254,1);
    background:#eff6ff;
    color:#1d4ed8;
    transition:transform .08s ease, box-shadow .18s ease;
    white-space:nowrap;
  }
  .pe-link:hover{
    transform:translateY(-1px);
    box-shadow:0 14px 34px rgba(37,99,235,.14);
  }

  .pe-splits{
    display:flex;
    flex-direction:column;
    gap:10px;
    margin-top:12px;
  }
  .pe-split{
    border:1px solid rgba(229,231,235,.9);
    border-radius:14px;
    background:#fff;
    padding:10px 12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .pe-split-left{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    align-items:center;
  }
  .pe-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 10px;
    border-radius:999px;
    border:1px solid var(--border);
    background:var(--soft);
    font-size:.78rem;
    color:var(--muted);
    white-space:nowrap;
  }
  .pe-pill strong{ color:var(--ink); }

  .pe-state{ border:1px solid transparent; font-weight:800; }
  .pe-state--done{ background:var(--success-soft); border-color:#bbf7d0; color:#166534; }
  .pe-state--pending{ background:var(--warning-soft); border-color:#fde68a; color:#92400e; }
  .pe-state--current{ background:#dbeafe; border-color:rgba(191,219,254,1); color:#1d4ed8; }

  .pe-summary-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
  }
  .pe-summary{
    display:flex;
    gap:10px 18px;
    flex-wrap:wrap;
    align-items:center;
    font-size:.88rem;
    color:var(--muted);
  }
  .pe-summary strong{ color:var(--ink); }
  .pe-total{
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:8px 12px;
    border-radius:999px;
    background:linear-gradient(135deg,#22c55e,#16a34a);
    color:#fff;
    font-weight:800;
    font-size:.9rem;
  }

  .pe-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    font-size:.88rem;
  }
  .pe-table thead th{
    position:sticky;
    top:0;
    z-index:2;
    background:#f8fafc;
    border-bottom:1px solid var(--border);
    font-size:.78rem;
    letter-spacing:.01em;
    color:var(--muted);
    text-align:left;
    padding:10px 12px;
    white-space:nowrap;
  }
  .pe-table td{
    border-bottom:1px solid rgba(229,231,235,.85);
    padding:10px 12px;
    vertical-align:top;
  }
  .pe-table tbody tr:hover{ background:#fbfdff; }

  .pe-req{
    white-space:pre-wrap;
    color:var(--ink);
    line-height:1.35;
    font-size:.9rem;
  }
  .pe-mini{
    margin-top:4px;
    font-size:.78rem;
    color:var(--muted);
  }

  .pe-prod{ font-weight:800; color:var(--ink); font-size:.9rem; line-height:1.25; }
  .pe-prod-meta{ margin-top:4px; font-size:.78rem; color:var(--muted); line-height:1.25; }

  .pe-amount{ text-align:right; white-space:nowrap; }

  .pe-match{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:.82rem;
    color:var(--muted);
    white-space:nowrap;
  }
  .pe-bar{
    width:54px; height:6px;
    border-radius:999px;
    background:#e5e7eb;
    overflow:hidden;
  }
  .pe-bar > div{
    height:100%;
    border-radius:999px;
    background:linear-gradient(90deg,#22c55e,#4ade80);
    transform-origin:left;
  }

  .pe-tag{
    display:inline-flex;
    align-items:center;
    gap:6px;
    border-radius:999px;
    padding:5px 10px;
    font-size:.78rem;
    border:1px solid rgba(191,219,254,1);
    background:#eff6ff;
    color:#1d4ed8;
    font-weight:800;
    white-space:nowrap;
  }

  .pe-suggest{
    margin-top:8px;
    padding:10px 10px;
    border-radius:14px;
    border:1px dashed rgba(191,219,254,1);
    background:#f8fbff;
  }
  .pe-suggest-row{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    align-items:center;
    justify-content:space-between;
  }
  .pe-suggest-left{
    min-width:240px;
  }
  .pe-suggest-title{
    font-weight:900;
    font-size:.82rem;
    color:#1d4ed8;
    margin-bottom:4px;
  }

  @media (max-width: 860px){
    .pe-table thead{ display:none; }
    .pe-table, .pe-table tbody, .pe-table tr, .pe-table td{ display:block; width:100%; }
    .pe-table tr{ border-bottom:1px solid var(--border); padding:10px 0; }
    .pe-table td{ border:none; padding:6px 12px; }
    .pe-table td::before{
      content:attr(data-label);
      display:block;
      font-size:.74rem;
      color:var(--muted);
      font-weight:800;
      margin-bottom:4px;
    }
    .pe-amount{ text-align:left; }
  }
</style>

@php
  $statusClass = match($propuesta->status) {
      'draft' => 'st-draft',
      'revisar' => 'st-revisar',
      'enviada' => 'st-enviada',
      'adjudicada' => 'st-adjudicada',
      'no_adjudicada' => 'st-no_adjudicada',
      default => 'st-draft',
  };

  $statusLabels = [
      'draft' => 'Borrador',
      'revisar' => 'En revisión',
      'enviada' => 'Enviada',
      'adjudicada' => 'Adjudicada',
      'no_adjudicada' => 'No adjudicada',
  ];

  $allSplitsProcessed = false;
  if (!empty($splitsInfo) && is_array($splitsInfo)) {
      $allSplitsProcessed = true;
      foreach ($splitsInfo as $s) {
          if (!in_array($s['state'] ?? null, ['done', 'done-current'], true)) {
              $allSplitsProcessed = false;
              break;
          }
      }
  }
@endphp

<div class="pe-wrap">

  {{-- Header --}}
  <div class="pe-top">
    <div class="pe-title">
      <div class="pe-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M4 19V5"/><path d="M20 19V5"/><path d="M8 17V9"/><path d="M12 17V7"/><path d="M16 17V11"/>
        </svg>
      </div>
      <div style="min-width:0;">
        <h1 class="pe-h1">{{ $propuesta->codigo }} · {{ $propuesta->titulo }}</h1>
        <div class="pe-sub">
          <span class="pe-badge">Fecha: <strong style="color:var(--ink);">{{ $propuesta->fecha?->format('d/m/Y') }}</strong></span>
          @if($propuesta->licitacion_id)
            <span class="pe-badge">Licitación: <strong style="color:var(--ink);">#{{ $propuesta->licitacion_id }}</strong></span>
          @endif
          @if($propuesta->requisicion_id)
            <span class="pe-badge">Requisición: <strong style="color:var(--ink);">#{{ $propuesta->requisicion_id }}</strong></span>
          @endif
          <span class="pe-badge">Renglones: <strong style="color:var(--ink);">{{ $propuesta->items->count() }}</strong></span>
        </div>
      </div>
    </div>

    <div class="pe-actions">
      <span class="pe-status {{ $statusClass }}">
        <span class="pe-dot"></span>
        {{ $statusLabels[$propuesta->status] ?? $propuesta->status }}
      </span>

      <a href="{{ url()->previous() }}" class="pe-btn pe-btn-ghost">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
          <path d="M15 18l-6-6 6-6"/>
        </svg>
        Volver
      </a>
    </div>
  </div>

  {{-- PDF + splits --}}
  @if($licitacionPdf)
    <div class="pe-card">
      <div class="pe-card-h">
        <div class="pe-card-title">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <rect x="4" y="3" width="16" height="18" rx="2"/>
            <path d="M8 7h8M8 11h6M8 15h8"/>
          </svg>
          Documento base
        </div>

        <a class="pe-link" href="{{ route('admin.licitacion-pdfs.preview', $licitacionPdf) }}" target="_blank">
          Ver PDF completo
        </a>
      </div>

      <div class="pe-card-b">
        <div>
          <div class="pe-prod">{{ $licitacionPdf->id }}. {{ $licitacionPdf->original_filename ?? 'Archivo de licitación' }}</div>
          <div class="pe-mini">Procesa cada requisición para generar renglones y luego realiza el merge global.</div>
        </div>

        @if(!empty($splitsInfo))
          <div class="pe-splits">
            @foreach($splitsInfo as $i => $s)
              @php
                $stateClass = match($s['state']) {
                  'done', 'done-current' => 'pe-state--done',
                  'current'              => 'pe-state--current',
                  default                => 'pe-state--pending',
                };

                $label = match($s['state']) {
                  'done', 'done-current' => 'Procesada',
                  'current'              => 'En curso',
                  default                => 'Pendiente',
                };
              @endphp

              <div class="pe-split">
                <div class="pe-split-left">
                  <span class="pe-pill pe-state {{ $stateClass }}">
                    <strong>Req. {{ $i+1 }}</strong> · {{ $label }}
                  </span>

                  @if($s['from'] && $s['to'])
                    <span class="pe-pill"><strong>Págs</strong> {{ $s['from'] }}–{{ $s['to'] }}</span>
                  @endif

                  @if($s['pages'])
                    <span class="pe-pill"><strong>{{ $s['pages'] }}</strong> pág.</span>
                  @endif
                </div>

                <form method="POST"
                      action="{{ route('admin.licitacion-propuestas.splits.process', ['licitacionPropuesta'=>$propuesta->id,'splitIndex'=>$s['index']]) }}">
                  @csrf
                  <button type="submit" class="pe-btn pe-btn-primary pe-btn-mini">
                    @if(in_array($s['state'], ['done','done-current'], true))
                      Reprocesar con IA
                    @else
                      Procesar con IA
                    @endif
                  </button>
                </form>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @endif

  {{-- Totales + Merge --}}
  <div class="pe-card">
    <div class="pe-card-h">
      <div class="pe-card-title">Totales</div>

      <form method="POST" action="{{ route('admin.licitacion-propuestas.merge', $propuesta) }}">
        @csrf
        <button type="submit" class="pe-btn pe-btn-primary" {{ !$allSplitsProcessed ? 'disabled' : '' }}>
          Merge global
        </button>
      </form>
    </div>

    <div class="pe-card-b">
      <div class="pe-summary-row">
        <div class="pe-summary">
          <div>Subtotal: <strong>{{ $propuesta->moneda ?? 'MXN' }} {{ number_format($propuesta->subtotal,2) }}</strong></div>
          <div>IVA: <strong>{{ $propuesta->moneda ?? 'MXN' }} {{ number_format($propuesta->iva,2) }}</strong></div>
          <div class="pe-total">Total: {{ $propuesta->moneda ?? 'MXN' }} {{ number_format($propuesta->total,2) }}</div>
        </div>

        @if(!$allSplitsProcessed)
          <div class="pe-badge" style="border-color:#fde68a; background:var(--warning-soft); color:#92400e;">
            Falta procesar requisiciones antes del merge.
          </div>
        @endif
      </div>
    </div>
  </div>

  {{-- Tabla --}}
  <div class="pe-card">
    <div class="pe-card-h">
      <div class="pe-card-title">Comparativo</div>
      <span class="pe-badge">{{ $propuesta->items->count() }} renglones</span>
    </div>

    <div class="pe-card-b" style="padding:0;">
      <div style="overflow:auto; max-height:70vh;">
        <table class="pe-table">
          <thead>
            <tr>
              <th style="width:56px;">#</th>
              <th>Solicitado</th>
              <th>Producto ofertado</th>
              <th style="width:150px;">Match</th>
              <th style="width:90px; text-align:right;">Cant.</th>
              <th style="width:140px; text-align:right;">Precio unit.</th>
              <th style="width:140px; text-align:right;">Subtotal</th>
            </tr>
          </thead>

          <tbody>
            @foreach($propuesta->items as $item)
              @php
                $req  = $item->requestItem;
                $prod = $item->product;

                $score = $item->match_score ?? null;
                $scorePercent = is_null($score) ? null : max(0, min(100, (int)$score));

                // Sugerencia (si existe columna suggested_product_id + relación)
                $hasSuggestedCol = array_key_exists('suggested_product_id', $item->getAttributes());
                $suggested = null;
                if ($hasSuggestedCol) {
                  // si tienes relación suggestedProduct, úsala; si no, al menos muestra el ID
                  $suggested = method_exists($item, 'suggestedProduct') ? $item->suggestedProduct : null;
                }
              @endphp

              <tr>
                <td data-label="#" style="color:var(--muted); font-weight:800;">
                  {{ $req?->renglon ?? $loop->iteration }}
                </td>

                <td data-label="Solicitado">
                  @if($req)
                    <div class="pe-req">{{ $req->line_raw }}</div>
                    <div class="pe-mini">Página {{ $req->page?->page_number ?? '—' }}</div>
                  @else
                    {{-- ✅ AQUÍ MOSTRAMOS LO EXTRAÍDO POR IA --}}
                    <div class="pe-req">{{ $item->descripcion_raw }}</div>
                    <div class="pe-mini">Sin renglón asociado (extraído por IA)</div>
                  @endif
                </td>

                <td data-label="Producto ofertado">
                  @if($prod)
                    <div class="pe-prod">{{ trim(($prod->sku ?? '').' '.($prod->name ?? '')) }}</div>
                    <div class="pe-prod-meta">
                      @if(!empty($prod->brand))
                        Marca: {{ $prod->brand }} ·
                      @endif
                      Unidad: {{ $item->unidad_propuesta ?? ($prod->unit ?? '—') }}
                    </div>
                  @else
                    <span class="pe-tag">Sin producto (pendiente)</span>
                  @endif

                  {{-- ✅ BLOQUE DE SUGERENCIA (si existe suggested_product_id) --}}
                  @if($hasSuggestedCol && empty($item->product_id) && !empty($item->suggested_product_id))
                    <div class="pe-suggest">
                      <div class="pe-suggest-row">
                        <div class="pe-suggest-left">
                          <div class="pe-suggest-title">Sugerencia IA</div>
                          @if($suggested)
                            <div class="pe-prod">{{ trim(($suggested->sku ?? '').' '.($suggested->name ?? '')) }}</div>
                            <div class="pe-prod-meta">
                              @if(!empty($suggested->brand)) Marca: {{ $suggested->brand }} · @endif
                              Unidad: {{ $suggested->unit ?? '—' }}
                            </div>
                          @else
                            <div class="pe-prod">Producto ID: {{ $item->suggested_product_id }}</div>
                            <div class="pe-prod-meta">Activa relación suggestedProduct() para ver nombre/SKU.</div>
                          @endif
                        </div>

                        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                          <form method="POST" action="{{ route('admin.licitacion-propuestas.items.apply', $item) }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $item->suggested_product_id }}">
                            <button class="pe-btn pe-btn-green pe-btn-mini" type="submit">Aplicar</button>
                          </form>

                          <form method="POST" action="{{ route('admin.licitacion-propuestas.items.reject', $item) }}">
                            @csrf
                            <button class="pe-btn pe-btn-red pe-btn-mini" type="submit">No aplica</button>
                          </form>
                        </div>
                      </div>

                      @if(!empty($item->match_reason))
                        <div class="pe-mini" style="margin-top:8px;">
                          {{ $item->match_reason }}
                        </div>
                      @endif
                    </div>
                  @endif
                </td>

                <td data-label="Match">
                  @if(!is_null($scorePercent))
                    <div class="pe-match">
                      <div class="pe-bar"><div style="transform:scaleX({{ $scorePercent/100 }});"></div></div>
                      <strong style="color:var(--ink);">{{ $scorePercent }}%</strong>
                    </div>
                    @if($item->motivo_seleccion)
                      <div class="pe-mini" style="margin-top:6px;">{{ $item->motivo_seleccion }}</div>
                    @endif
                  @else
                    <span class="pe-tag">IA pendiente</span>
                  @endif
                </td>

                <td data-label="Cant." class="pe-amount">
                  {{ $item->cantidad_propuesta ?? ($req?->cantidad ?? '—') }}
                </td>

                <td data-label="Precio unit." class="pe-amount">
                  @if($item->precio_unitario)
                    {{ $propuesta->moneda ?? 'MXN' }} {{ number_format($item->precio_unitario,2) }}
                  @else
                    —
                  @endif
                </td>

                <td data-label="Subtotal" class="pe-amount">
                  @if($item->subtotal)
                    {{ $propuesta->moneda ?? 'MXN' }} {{ number_format($item->subtotal,2) }}
                  @else
                    —
                  @endif
                </td>
              </tr>
            @endforeach

            @if($propuesta->items->isEmpty())
              <tr>
                <td colspan="7" style="text-align:center; padding:16px; color:var(--muted);">
                  No hay renglones aún. Procesa requisiciones con IA desde el bloque superior.
                </td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection
