@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  .av-page { font-family:'Quicksand',sans-serif; background:#f8fafc; color:#334155; min-height:100vh; padding:32px 24px; }
  .av-page * { box-sizing:border-box; }
  .av-wrap { max-width:1000px; margin:0 auto; }
  .av-page h1 { color:#0f172a; font-size:26px; margin:0 0 6px; font-weight:700; }
  .av-sub { color:#64748b; font-size:14px; margin:0 0 24px; }
  .av-topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
  .av-actions { display:flex; gap:10px; flex-wrap:wrap; }
  .back-link { display:inline-flex; gap:8px; color:#64748b; font-weight:600; font-size:14px; text-decoration:none; }
  .back-link:hover { color:#0f172a; }
  .av-btn { display:inline-flex; align-items:center; gap:8px; font-weight:700; font-size:14px; padding:0 16px; height:42px; border-radius:10px; text-decoration:none; transition:.2s; border:1px solid transparent; }
  .av-btn:hover { transform:translateY(-1px); }
  .av-btn-dark { background:#0f172a; color:#fff; }
  .av-btn-green { background:#16a34a; color:#fff; }
  .av-section { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:22px 24px; margin-bottom:22px; }
  .av-section h2 { font-size:18px; margin:0 0 4px; color:#0f172a; }
  .av-section .desc { font-size:13px; color:#64748b; margin:0 0 16px; }
  table { width:100%; border-collapse:collapse; }
  th { text-align:left; font-size:11px; color:#64748b; font-weight:700; padding:9px 8px; border-bottom:1px solid #e2e8f0; text-transform:uppercase; }
  td { font-size:13px; color:#0f172a; padding:11px 8px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
  .tr { text-align:right; } .tc { text-align:center; }
  .lose-row td { background:#fffafa; }
  .badge { display:inline-block; font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; }
  .b-up { background:#fef2f2; color:#ef4444; } .b-down { background:#f0fdf4; color:#16a34a; }
  .av-total { display:flex; justify-content:flex-end; gap:32px; margin-top:14px; font-size:14px; }
  .av-total strong { color:#0f172a; font-size:18px; }
  .ant-note { font-size:12.5px; color:#475569; line-height:1.5; white-space:pre-wrap; margin-top:6px; }
  .empty { color:#94a3b8; font-size:14px; }
  .stat-row { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:22px; }
  .stat-box { flex:1; min-width:140px; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; }
  .stat-box .v { font-size:22px; font-weight:700; color:#0f172a; }
  .stat-box .l { font-size:12px; color:#64748b; font-weight:600; margin-top:3px; }
</style>

@php $gCount = count($ganadas); $pCount = count($perdidas); @endphp

<div class="av-page">
  <div class="av-wrap">
    <div class="av-topbar">
      <a href="{{ route('propuestas-comerciales.adjudicacion.create', $adjudicacion->propuesta_comercial_id) }}" class="back-link">← Volver a marcar partidas</a>
      @if($gCount)
        <div class="av-actions">
          <a href="{{ route('propuestas-comerciales.resultado.remision', $adjudicacion) }}" target="_blank" class="av-btn av-btn-dark">📄 Remisión</a>
          <a href="{{ route('propuestas-comerciales.resultado.facturar', $adjudicacion) }}" class="av-btn av-btn-green">🧾 Facturar</a>
        </div>
      @endif
    </div>

    @if(session('status'))
      <div class="av-section" style="background:#f0fdf4; border-color:#bbf7d0; color:#15803d; font-weight:600;">{{ session('status') }}</div>
    @endif

    <h1>Resultado de adjudicación {{ $adjudicacion->folio }}</h1>
    <p class="av-sub">{{ $adjudicacion->titulo }} · {{ $gCount }} ganadas · {{ $pCount }} perdidas</p>

    <div class="stat-row">
      <div class="stat-box"><div class="v" style="color:#16a34a;">{{ $gCount }}</div><div class="l">Ganadas</div></div>
      <div class="stat-box"><div class="v" style="color:#ef4444;">{{ $pCount }}</div><div class="l">Perdidas</div></div>
      <div class="stat-box"><div class="v">${{ number_format($subtotalGanadas,2) }}</div><div class="l">Subtotal ganado</div></div>
      <div class="stat-box"><div class="v">${{ number_format($totalGanadas,2) }}</div><div class="l">Total con IVA</div></div>
    </div>

    {{-- ===== VENTA (ganadas) ===== --}}
    <div class="av-section">
      <h2>Venta a surtir</h2>
      <p class="desc">Todas las partidas que NO marcaste como perdidas. Esto es lo que se factura y entrega.</p>

      @if($gCount)
        <table>
          <thead>
            <tr><th>#</th><th>Descripción</th><th class="tc">Cant.</th><th class="tr">P. Unit.</th><th class="tr">Subtotal</th></tr>
          </thead>
          <tbody>
            @foreach($ganadas as $g)
              <tr>
                <td>{{ $g['num'] }}</td>
                <td>{{ $g['desc'] }}</td>
                <td class="tc">{{ rtrim(rtrim(number_format($g['qty'],2),'0'),'.') }} {{ $g['unit'] }}</td>
                <td class="tr">${{ number_format($g['offered'],2) }}</td>
                <td class="tr"><strong>${{ number_format($g['subtotal'],2) }}</strong></td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="av-total"><span>Subtotal ganadas</span><strong>${{ number_format($subtotalGanadas,2) }}</strong></div>
        <div class="av-total"><span>Total con impuesto</span><strong>${{ number_format($totalGanadas,2) }}</strong></div>
      @else
        <p class="empty">No hay partidas ganadas (marcaste todas como perdidas).</p>
      @endif
    </div>

    {{-- ===== ANTECEDENTE (perdidas) ===== --}}
    <div class="av-section">
      <h2>Antecedente — partidas no ganadas</h2>
      <p class="desc">Histórico de lo que no se ganó, con el análisis de por qué. No afecta la venta.</p>

      @if($pCount)
        <table>
          <thead>
            <tr><th>#</th><th>Descripción</th><th class="tr">Tu precio</th><th class="tr">Ganador</th><th class="tr">Diferencia</th><th>Licitante</th></tr>
          </thead>
          <tbody>
            @foreach($perdidas as $p)
              <tr class="lose-row">
                <td>{{ $p['num'] }}</td>
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
                      {{ $p['dif'] > 0 ? '▲' : '▼' }} ${{ number_format(abs($p['dif']),2) }}
                      ({{ number_format(abs($p['difPct']),2) }}%)
                    </span>
                  @else — @endif
                </td>
                <td>{{ $p['proveedor'] ?: '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p class="empty">No hay partidas perdidas registradas.</p>
      @endif
    </div>
  </div>
</div>
@endsection