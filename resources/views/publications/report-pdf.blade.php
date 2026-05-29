@php
    use Illuminate\Support\Str;

    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $pct   = fn ($v, $max) => $max > 0 ? round(((float) $v / $max) * 100, 1) : 0;

    $C = '#10b981'; // compras
    $V = '#3b82f6'; // ventas

    $tCompra = (float) ($totalSpentCompra ?? 0);
    $tVenta  = (float) ($totalSpentVenta ?? 0);
    $balance = $tVenta - $tCompra;

    // Mensual: combinar y omitir meses vacíos
    $monthlyTable = [];
    $maxMonthly = 0;
    foreach (($chartLabels ?? []) as $i => $lbl) {
        $c = (float) ($monthlyCompra[$i] ?? 0);
        $v = (float) ($monthlyVenta[$i] ?? 0);
        if ($c == 0 && $v == 0) continue;
        $monthlyTable[] = ['label' => $lbl, 'compra' => $c, 'venta' => $v];
        $maxMonthly = max($maxMonthly, $c, $v);
    }

    // Diario: combinar y omitir días vacíos
    $dailyTable = [];
    $maxDaily = 0;
    foreach (($dailyLabels ?? []) as $i => $lbl) {
        $c = (float) ($dailyCompra[$i] ?? 0);
        $v = (float) ($dailyVenta[$i] ?? 0);
        if ($c == 0 && $v == 0) continue;
        $dailyTable[] = ['label' => $lbl, 'compra' => $c, 'venta' => $v];
        $maxDaily = max($maxDaily, $c, $v);
    }

    $rows = ($allPurchases ?? collect())->take(80);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { margin: 0; color: #0f172a; font-size: 11px; }
    .wrap { padding: 4px 2px; }

    .head { border-bottom: 3px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px; }
    .head h1 { margin: 0; font-size: 20px; }
    .head .sub { color: #64748b; font-size: 11px; margin-top: 3px; }
    .head .right { text-align: right; font-size: 10px; color: #64748b; }

    .meta { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .meta td { padding: 5px 8px; border: 1px solid #e2e8f0; font-size: 10px; }
    .meta td.k { background: #f8fafc; font-weight: bold; color: #475569; width: 18%; }

    .kpis { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-bottom: 18px; }
    .kpis td { width: 33%; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; vertical-align: top; }
    .kpis .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: .06em; color: #64748b; font-weight: bold; }
    .kpis .val { font-size: 18px; font-weight: bold; margin-top: 4px; }

    h2 { font-size: 13px; margin: 18px 0 8px; padding-bottom: 5px; border-bottom: 2px solid #e2e8f0; }

    table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.data th { background: #0f172a; color: #fff; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; }
    table.data td { padding: 5px 8px; border-bottom: 1px solid #eef2f7; font-size: 10px; }
    table.data tr:nth-child(even) td { background: #fafcff; }
    .right { text-align: right; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }

    .bar { height: 7px; border-radius: 4px; }
    .barbg { background: #eef2f7; border-radius: 4px; width: 100%; }

    .chip { display: inline-block; padding: 1px 7px; border-radius: 8px; font-size: 8px; font-weight: bold; }
    .chip.c { background: #ecfdf5; color: #047857; }
    .chip.v { background: #eff6ff; color: #1d4ed8; }

    .legend { font-size: 9px; color: #64748b; margin-bottom: 6px; }
    .legend .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin: 0 3px 0 10px; }

    .section { page-break-inside: avoid; }
    .twocol { width: 100%; border-collapse: separate; border-spacing: 10px 0; }
    .twocol > tbody > tr > td { width: 50%; vertical-align: top; }
    .foot { margin-top: 14px; padding-top: 8px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 9px; text-align: center; }
</style>
</head>
<body>
<div class="wrap">

    {{-- Encabezado --}}
    <table style="width:100%; border:0;"><tr>
        <td style="border:0; vertical-align:top;">
            <div class="head" style="border:0; padding:0; margin:0;">
                <h1>Reporte de Compras y Ventas</h1>
                <div class="sub">Comparativo de remisiones y facturas</div>
            </div>
        </td>
        <td style="border:0; vertical-align:top;" class="right">
            <div class="head right" style="border:0; padding:0; margin:0;">
                Generado: {{ $reportMeta['generated_at'] ?? '' }}<br>
                Periodo: {{ $reportMeta['period_label'] ?? 'Histórico completo' }}
            </div>
        </td>
    </tr></table>
    <div style="border-bottom:3px solid #0f172a; margin-bottom:14px;"></div>

    {{-- Filtros aplicados --}}
    <table class="meta">
        <tr>
            <td class="k">Periodo</td><td>{{ $reportMeta['period_label'] ?? '—' }}</td>
            <td class="k">Tipo</td><td>{{ ucfirst($reportMeta['cat'] ?? 'ambos') }}</td>
        </tr>
        <tr>
            <td class="k">Proveedor/Cliente</td><td>{{ $reportMeta['supplier'] ?? '—' }}</td>
            <td class="k">Producto</td><td>{{ $reportMeta['product'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="k">Docs. compra</td><td>{{ $reportMeta['doc_count_compra'] ?? 0 }}</td>
            <td class="k">Docs. venta</td><td>{{ $reportMeta['doc_count_venta'] ?? 0 }}</td>
        </tr>
    </table>

    {{-- KPIs --}}
    <table class="kpis"><tr>
        <td>
            <div class="lbl">Total Compras</div>
            <div class="val" style="color:{{ $C }}">{{ $money($tCompra) }}</div>
        </td>
        <td>
            <div class="lbl">Total Ventas</div>
            <div class="val" style="color:{{ $V }}">{{ $money($tVenta) }}</div>
        </td>
        <td>
            <div class="lbl">Balance (Ventas − Compras)</div>
            <div class="val" style="color:{{ $balance >= 0 ? $V : '#ef4444' }}">{{ $money($balance) }}</div>
        </td>
    </tr></table>

    {{-- Estadísticas por mes --}}
    <div class="section">
        <h2>Estadísticas por mes</h2>
        <div class="legend">
            <span class="dot" style="background:{{ $C }}"></span>Compras
            <span class="dot" style="background:{{ $V }}"></span>Ventas
        </div>
        <table class="data">
            <thead><tr>
                <th style="width:16%">Mes</th>
                <th style="width:38%">Compras</th>
                <th style="width:38%">Ventas</th>
                <th class="right" style="width:8%">Balance</th>
            </tr></thead>
            <tbody>
            @forelse($monthlyTable as $r)
                @php $bal = $r['venta'] - $r['compra']; @endphp
                <tr>
                    <td><strong>{{ $r['label'] }}</strong></td>
                    <td>
                        <div class="barbg"><div class="bar" style="background:{{ $C }}; width:{{ max($pct($r['compra'], $maxMonthly), $r['compra'] > 0 ? 2 : 0) }}%"></div></div>
                        <span class="num">{{ $money($r['compra']) }}</span>
                    </td>
                    <td>
                        <div class="barbg"><div class="bar" style="background:{{ $V }}; width:{{ max($pct($r['venta'], $maxMonthly), $r['venta'] > 0 ? 2 : 0) }}%"></div></div>
                        <span class="num">{{ $money($r['venta']) }}</span>
                    </td>
                    <td class="num" style="color:{{ $bal >= 0 ? $V : '#ef4444' }}">{{ $money($bal) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:14px;">Sin datos en el periodo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Movimiento por día --}}
    <div class="section">
        <h2>Movimiento por día</h2>
        <table class="data">
            <thead><tr>
                <th style="width:16%">Día</th>
                <th style="width:38%">Compras</th>
                <th style="width:38%">Ventas</th>
                <th class="right" style="width:8%">Balance</th>
            </tr></thead>
            <tbody>
            @forelse($dailyTable as $r)
                @php $bal = $r['venta'] - $r['compra']; @endphp
                <tr>
                    <td><strong>{{ $r['label'] }}</strong></td>
                    <td>
                        <div class="barbg"><div class="bar" style="background:{{ $C }}; width:{{ max($pct($r['compra'], $maxDaily), $r['compra'] > 0 ? 2 : 0) }}%"></div></div>
                        <span class="num">{{ $money($r['compra']) }}</span>
                    </td>
                    <td>
                        <div class="barbg"><div class="bar" style="background:{{ $V }}; width:{{ max($pct($r['venta'], $maxDaily), $r['venta'] > 0 ? 2 : 0) }}%"></div></div>
                        <span class="num">{{ $money($r['venta']) }}</span>
                    </td>
                    <td class="num" style="color:{{ $bal >= 0 ? $V : '#ef4444' }}">{{ $money($bal) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color:#94a3b8; padding:14px;">Sin datos en el periodo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top proveedores --}}
    <div class="section">
        <h2>Top proveedores / clientes</h2>
        <table class="twocol"><tr>
            <td>
                <table class="data">
                    <thead><tr><th colspan="3"><span class="chip c">Compras</span></th></tr>
                    <tr><th>Proveedor</th><th class="right">Docs</th><th class="right">Total</th></tr></thead>
                    <tbody>
                    @forelse(($topSuppliersCompra ?? []) as $s)
                        <tr>
                            <td>{{ Str::limit($s->supplier_name, 28) }}</td>
                            <td class="num">{{ $s->count ?? '' }}</td>
                            <td class="num">{{ $money($s->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8;">Sin datos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <table class="data">
                    <thead><tr><th colspan="3"><span class="chip v">Ventas</span></th></tr>
                    <tr><th>Cliente</th><th class="right">Docs</th><th class="right">Total</th></tr></thead>
                    <tbody>
                    @forelse(($topSuppliersVenta ?? []) as $s)
                        <tr>
                            <td>{{ Str::limit($s->supplier_name, 28) }}</td>
                            <td class="num">{{ $s->count ?? '' }}</td>
                            <td class="num">{{ $money($s->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center; color:#94a3b8;">Sin datos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
        </tr></table>
    </div>

    {{-- Top productos --}}
    <div class="section">
        <h2>Top productos</h2>
        <table class="twocol"><tr>
            <td>
                <table class="data">
                    <thead><tr><th colspan="2"><span class="chip c">Compras</span></th></tr>
                    <tr><th>Producto</th><th class="right">Total</th></tr></thead>
                    <tbody>
                    @forelse(($prodChartDataCompra ?? []) as $p)
                        <tr><td>{{ $p['x'] }}</td><td class="num">{{ $money($p['y']) }}</td></tr>
                    @empty
                        <tr><td colspan="2" style="text-align:center; color:#94a3b8;">Sin datos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <table class="data">
                    <thead><tr><th colspan="2"><span class="chip v">Ventas</span></th></tr>
                    <tr><th>Producto</th><th class="right">Total</th></tr></thead>
                    <tbody>
                    @forelse(($prodChartDataVenta ?? []) as $p)
                        <tr><td>{{ $p['x'] }}</td><td class="num">{{ $money($p['y']) }}</td></tr>
                    @empty
                        <tr><td colspan="2" style="text-align:center; color:#94a3b8;">Sin datos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </td>
        </tr></table>
    </div>

    {{-- Desglose --}}
    <div class="section">
        <h2>Desglose (máx. 80 registros más recientes)</h2>
        <table class="data">
            <thead><tr>
                <th>Tipo</th><th>Fecha</th><th>Concepto</th><th>Proveedor/Cliente</th>
                <th class="right">Precio</th><th class="right">Cant</th><th class="right">Total</th>
            </tr></thead>
            <tbody>
            @forelse($rows as $r)
                @php
                    $fecha = $r->document_datetime ?? $r->doc_created_at ?? null;
                    try { $fecha = $fecha ? \Carbon\Carbon::parse($fecha)->format('d/m/Y') : '—'; } catch (\Throwable $e) { $fecha = '—'; }
                @endphp
                <tr>
                    <td><span class="chip {{ $r->category === 'venta' ? 'v' : 'c' }}">{{ $r->category }}</span></td>
                    <td>{{ $fecha }}</td>
                    <td>{{ Str::limit($r->item_name ?: $r->item_raw, 40) }}</td>
                    <td>{{ Str::limit($r->supplier_name ?: '—', 24) }}</td>
                    <td class="num">{{ $money($r->unit_price) }}</td>
                    <td class="num">{{ number_format((float) $r->qty, 2) }}</td>
                    <td class="num"><strong>{{ $money($r->line_total) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:14px;">Sin registros en el periodo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="foot">
        Reporte generado automáticamente · {{ $reportMeta['generated_at'] ?? '' }}
    </div>

</div>
</body>
</html>