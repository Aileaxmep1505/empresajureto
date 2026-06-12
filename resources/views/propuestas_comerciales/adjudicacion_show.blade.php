@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f9fafb;
    --card: #ffffff;

    --ink-dark: #111111;
    --ink: #333333;
    --muted: #888888;
    --muted-light: #b8b8b8;

    --line: #ebebeb;

    --blue: #007aff;
    --blue-soft: #e6f0ff;

    --success: #15803d;
    --success-soft: #e6ffe6;

    --danger: #ff4a4a;
    --danger-soft: #ffebeb;
  }

  /* ===== BASE & TYPOGRAPHY ===== */
  .av-page {
    font-family: 'Quicksand', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
    padding: 28px 20px;
    font-weight: 500;
    -webkit-font-smoothing: antialiased;
  }
  .av-page * { box-sizing: border-box; }
  .av-wrap {
    max-width: 980px;
    margin: 0 auto;
  }

  .av-page h1 {
    color: var(--ink-dark);
    font-size: 22px;
    margin: 0 0 6px;
    font-weight: 700;
    letter-spacing: -0.4px;
  }
  .av-sub {
    color: var(--muted);
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 28px;
  }

  /* ===== TOPBAR & LINKS ===== */
  .av-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 28px;
  }
  .av-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .back-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--muted);
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    transition: color 0.2s ease;
  }
  .back-link:hover { color: var(--blue); }

  /* ===== BUTTONS ===== */
  .btn {
    font-family: inherit;
    font-weight: 700;
    height: 40px;
    padding: 0 20px;
    border-radius: 8px;
    border: 1px solid transparent;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 13.5px;
    text-decoration: none;
    transition: transform 0.18s ease, background 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }
  .btn:hover { transform: translateY(-1px); }
  .btn:active { transform: scale(0.98); }

  .btn-outline {
    background: var(--card);
    color: var(--blue);
    border-color: var(--blue);
  }
  .btn-outline:hover { background: var(--blue-soft); box-shadow: 0 4px 12px rgba(0,122,255,0.08); }

  .btn-primary {
    background: var(--blue);
    color: var(--card);
  }
  .btn-primary:hover { background: #0066d6; box-shadow: 0 4px 12px rgba(0,122,255,0.22); }

  /* ===== ALERTS & STATS ===== */
  .alert-success {
    background: var(--success-soft);
    border: 1px solid rgba(21,128,61,0.18);
    color: var(--success);
    font-weight: 700;
    font-size: 13.5px;
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 24px;
  }

  .stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
  }
  .stat-box {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .stat-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.04);
  }
  .stat-box .v { font-size: 24px; font-weight: 700; color: var(--ink-dark); line-height: 1.1; letter-spacing: -0.5px; }
  .stat-box .l { font-size: 11px; color: var(--muted); font-weight: 700; margin-top: 8px; text-transform: uppercase; letter-spacing: 0.04em; }

  /* ===== SECTIONS & TABLES ===== */
  .av-section {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 28px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .av-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.04);
  }
  .av-section h2 { font-size: 17px; margin: 0 0 6px; color: var(--ink-dark); font-weight: 700; letter-spacing: -0.3px; }
  .av-section .desc { font-size: 13.5px; color: var(--muted); font-weight: 500; margin: 0 0 24px; }

  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 8px;
  }

  .av-table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
  }
  .av-table th {
    font-size: 11px;
    color: var(--muted);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 12px 12px;
    border-bottom: 1px solid var(--line);
    white-space: nowrap;
  }
  .av-table td {
    font-size: 13.5px;
    color: var(--ink);
    padding: 16px 12px;
    border-bottom: 1px solid var(--line);
    vertical-align: top;
  }
  .av-table tbody tr:last-child td { border-bottom: none; }
  .av-table tbody tr { transition: background 0.15s ease; }
  .av-table tbody tr:hover td { background: #fcfcfd; }

  .tr { text-align: right; }
  .tc { text-align: center; }

  .col-mm { color: #374151; font-weight: 600; min-width: 90px; }
  .col-mm .mm-empty { color: var(--muted-light); font-weight: 600; }

  /* ===== BADGES & MISC ===== */
  .badge {
    display: inline-block;
    font-size: 11.5px;
    font-weight: 700;
    padding: 4px 9px;
    border-radius: 8px;
    white-space: nowrap;
  }
  .b-up { background: var(--danger-soft); color: var(--danger); }
  .b-down { background: var(--success-soft); color: var(--success); }

  .av-total {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 28px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid var(--line);
    font-size: 14px;
    font-weight: 600;
    color: var(--muted);
  }
  .av-total strong { color: var(--ink-dark); font-size: 18px; font-weight: 700; }

  .ant-note {
    font-size: 12.5px;
    color: var(--muted);
    font-weight: 500;
    line-height: 1.6;
    white-space: pre-wrap;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed var(--line);
  }
  .empty {
    color: var(--muted-light);
    font-size: 14px;
    font-weight: 600;
    text-align: center;
    padding: 40px 16px;
    border: 1px dashed var(--line);
    border-radius: 12px;
  }

  /* ===== RESPONSIVE ===== */
  @media (max-width: 600px) {
    .av-page { padding: 20px 14px; }
    .av-topbar { flex-direction: column; align-items: stretch; gap: 16px; }
    .av-actions { display: grid; grid-template-columns: 1fr 1fr; }
    .stat-row { grid-template-columns: 1fr 1fr; }
    .av-section { padding: 20px; }
    .av-total { justify-content: space-between; gap: 12px; }
    .av-table { min-width: 680px; }
  }
</style>

@php
  $gCount = count($ganadas);
  $pCount = count($perdidas);

  // Marca y modelo ofertado por partida (cruzado por descripción de la propuesta)
  $adjudicacion->loadMissing(['propuesta.items.matches.product', 'propuesta.items.productoSeleccionado']);
  $propItems = optional($adjudicacion->propuesta)->items ?: collect();
  $ofertaInfo = $propItems->mapWithKeys(function ($item) {
      $selectedMatch = $item->matches->firstWhere('seleccionado', true);
      $selectedProduct = $item->productoSeleccionado ?: optional($selectedMatch)->product;

      $brand = data_get($item->meta, 'external_supplier')
          ?: data_get($item->meta, 'brand')
          ?: data_get($item->meta, 'marca')
          ?: data_get($selectedProduct, 'brand')
          ?: data_get($selectedProduct, 'marca')
          ?: '';

      $model = data_get($item->meta, 'modelo')
          ?: data_get($item->meta, 'model')
          ?: data_get($selectedProduct, 'model')
          ?: data_get($selectedProduct, 'modelo')
          ?: data_get($selectedProduct, 'model_name')
          ?: '';

      $key = mb_strtoupper(trim((string) ($item->descripcion_original ?? '')));

      return [$key => ['brand' => $brand, 'model' => $model]];
  });
@endphp

<div class="av-page">
  <div class="av-wrap">

    <div class="av-topbar">
      <a href="{{ route('propuestas-comerciales.adjudicacion.create', $adjudicacion->propuesta_comercial_id) }}" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        Volver a marcar partidas
      </a>
      @if($gCount)
        <div class="av-actions">
          <a href="{{ route('propuestas-comerciales.resultado.remision', $adjudicacion) }}" target="_blank" class="btn btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
            Remisión
          </a>
          <a href="{{ route('propuestas-comerciales.resultado.picking.empate', $adjudicacion) }}" class="btn btn-outline">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.5 4.21"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            Surtir / Picking
          </a>
          <a href="{{ route('propuestas-comerciales.resultado.facturar', $adjudicacion) }}" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 17.5v-11"/></svg>
            Facturar
          </a>
        </div>
      @endif
    </div>

    @if(session('status'))
      <div class="alert-success">{{ session('status') }}</div>
    @endif

    <div>
      <h1>Resultado de adjudicación {{ $adjudicacion->folio }}</h1>
      <p class="av-sub">{{ $adjudicacion->titulo }} &nbsp;·&nbsp; {{ $gCount }} ganadas &nbsp;·&nbsp; {{ $pCount }} perdidas</p>
    </div>

    <div class="stat-row">
      <div class="stat-box"><div class="v" style="color:var(--success);">{{ $gCount }}</div><div class="l">Ganadas</div></div>
      <div class="stat-box"><div class="v" style="color:var(--danger);">{{ $pCount }}</div><div class="l">Perdidas</div></div>
      <div class="stat-box"><div class="v">${{ number_format($subtotalGanadas,2) }}</div><div class="l">Subtotal ganado</div></div>
      <div class="stat-box"><div class="v">${{ number_format($totalGanadas,2) }}</div><div class="l">Total con IVA</div></div>
    </div>

    {{-- ===== VENTA (ganadas) ===== --}}
    <div class="av-section">
      <h2>Venta a surtir</h2>
      <p class="desc">Partidas adjudicadas a favor. Listas para remisión, surtido y facturación.</p>

      @if($gCount)
        <div class="table-responsive">
          <table class="av-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Descripción</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th class="tc">Cant.</th>
                <th class="tr">P. Unit.</th>
                <th class="tr">Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @foreach($ganadas as $g)
                @php $oi = $ofertaInfo[mb_strtoupper(trim((string) $g['desc']))] ?? ['brand' => '', 'model' => '']; @endphp
                <tr>
                  <td style="font-weight:700; color:var(--ink-dark);">{{ $g['num'] }}</td>
                  <td>{{ $g['desc'] }}</td>
                  <td class="col-mm">{!! $oi['brand'] !== '' ? e($oi['brand']) : '<span class="mm-empty">—</span>' !!}</td>
                  <td class="col-mm">{!! $oi['model'] !== '' ? e($oi['model']) : '<span class="mm-empty">—</span>' !!}</td>
                  <td class="tc">{{ rtrim(rtrim(number_format($g['qty'],2),'0'),'.') }} {{ $g['unit'] }}</td>
                  <td class="tr">${{ number_format($g['offered'],2) }}</td>
                  <td class="tr" style="font-weight:700; color:var(--ink-dark);">${{ number_format($g['subtotal'],2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="av-total"><span>Subtotal ganadas</span><strong>${{ number_format($subtotalGanadas,2) }}</strong></div>
        <div class="av-total" style="border-top: none; padding-top: 8px; margin-top: 0;"><span>Total con impuesto</span><strong style="color:var(--success);">${{ number_format($totalGanadas,2) }}</strong></div>
      @else
        <p class="empty">No hay partidas ganadas registradas.</p>
      @endif
    </div>

    {{-- ===== ANTECEDENTE (perdidas) ===== --}}
    <div class="av-section">
      <h2>Antecedente (Partidas no ganadas)</h2>
      <p class="desc">Histórico y análisis de partidas falladas en contra.</p>

      @if($pCount)
        <div class="table-responsive">
          <table class="av-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Descripción</th>
                <th class="tr">Tu precio</th>
                <th class="tr">Ganador</th>
                <th class="tr">Diferencia</th>
                <th>Licitante</th>
              </tr>
            </thead>
            <tbody>
              @foreach($perdidas as $p)
                <tr>
                  <td style="font-weight:700; color:var(--ink-dark);">{{ $p['num'] }}</td>
                  <td>
                    {{ $p['desc'] }}
                    @if($p['motivo'] || $p['analisis'])
                      <div class="ant-note">{{ $p['analisis'] ?: $p['motivo'] }}</div>
                    @endif
                  </td>
                  <td class="tr">${{ number_format($p['offered'],2) }}</td>
                  <td class="tr">{{ $p['ganador'] !== null ? '$'.number_format($p['ganador'],2) : '—' }}</td>
                  <td class="tr">
                    @if($p['dif'] !== null)
                      <span class="badge {{ $p['dif'] > 0 ? 'b-up' : 'b-down' }}">
                        {{ $p['dif'] > 0 ? '+' : '-' }} ${{ number_format(abs($p['dif']),2) }}
                        ({{ number_format(abs($p['difPct']),2) }}%)
                      </span>
                    @else
                      <span style="color:var(--muted-light);">—</span>
                    @endif
                  </td>
                  <td>
                    @if($p['proveedor'])
                      {{ $p['proveedor'] }}
                    @else
                      <span style="color:var(--muted-light);">Sin dato</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="empty">No hay partidas perdidas registradas.</p>
      @endif
    </div>
  </div>
</div>
@endsection