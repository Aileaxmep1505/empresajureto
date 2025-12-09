@extends('layouts.app')

@section('title', $propuesta->codigo.' - Propuesta económica')

@section('content')
<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<style>
    :root{
        --ink:#0f172a;
        --muted:#6b7280;
        --accent:#4f46e5;
        --accent-soft:#eef2ff;
        --accent-soft-2:#f9fafb;
        --border:#e5e7eb;
        --radius:18px;
        --shadow-soft:0 20px 45px rgba(15,23,42,0.06);
        --success:#16a34a;
        --danger:#ef4444;
        --warning:#f59e0b;
    }
    .page-wrapper{
        max-width: 1180px;
        margin: 0 auto;
        padding: 16px;
    }
    .header{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
        margin-bottom:18px;
    }
    .title-block{
        display:flex;
        gap:12px;
    }
    .title-icon{
        width:40px;
        height:40px;
        border-radius:16px;
        background:var(--accent-soft);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:18px;
    }
    .title-main{
        font-size:20px;
        font-weight:700;
        color:var(--ink);
    }
    .title-sub{
        font-size:12px;
        color:var(--muted);
        margin-top:2px;
    }
    .meta-grid{
        display:flex;
        flex-wrap:wrap;
        gap:8px 14px;
        font-size:11px;
        color:var(--muted);
        margin-top:6px;
    }
    .status-pill{
        border-radius:999px;
        padding:4px 10px;
        font-size:11px;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }
    .status-dot{
        width:8px;
        height:8px;
        border-radius:999px;
        background:currentColor;
    }
    .status-draft{ color:var(--warning); background:#fffbeb; }
    .status-revisar{ color:var(--accent); background:#eef2ff; }
    .status-enviada{ color:var(--accent); background:#e0f2fe; }
    .status-adjudicada{ color:var(--success); background:#ecfdf3; }
    .status-no_adjudicada{ color:var(--danger); background:#fef2f2; }

    .summary-card{
        margin-bottom:18px;
        border-radius:var(--radius);
        background:white;
        border:1px solid var(--border);
        box-shadow:var(--shadow-soft);
        padding:14px 16px;
        display:flex;
        flex-wrap:wrap;
        gap:12px 24px;
        align-items:center;
    }
    .summary-item{
        font-size:12px;
        color:var(--muted);
    }
    .summary-item strong{
        color:var(--ink);
    }
    .total-badge{
        border-radius:14px;
        padding:8px 14px;
        background:linear-gradient(135deg,#22c55e,#16a34a);
        color:white;
        font-size:13px;
        font-weight:600;
        display:inline-flex;
        align-items:center;
        gap:8px;
    }

    .table-wrapper{
        border-radius:var(--radius);
        overflow:hidden;
        border:1px solid var(--border);
        background:white;
        box-shadow:var(--shadow-soft);
    }
    table{
        width:100%;
        border-collapse:collapse;
        font-size:12px;
    }
    thead{
        background:var(--accent-soft-2);
    }
    th,td{
        padding:8px 10px;
        border-bottom:1px solid #e5e7eb;
        vertical-align:top;
    }
    th{
        font-size:11px;
        color:var(--muted);
        font-weight:600;
        text-align:left;
    }
    tbody tr:hover{
        background:#f9fafb;
    }

    .pill-match{
        border-radius:999px;
        padding:3px 8px;
        font-size:11px;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }
    .pill-match-bar{
        width:46px;
        height:4px;
        border-radius:999px;
        overflow:hidden;
        background:#e5e7eb;
    }
    .pill-match-bar-inner{
        height:100%;
        border-radius:999px;
        background:linear-gradient(90deg,#22c55e,#4ade80);
        transform-origin:left;
    }
    .product-name{
        font-weight:600;
        color:var(--ink);
    }
    .product-meta{
        font-size:11px;
        color:var(--muted);
        margin-top:2px;
    }
    .badge-tag{
        display:inline-flex;
        align-items:center;
        gap:6px;
        border-radius:999px;
        padding:3px 7px;
        font-size:10px;
        background:#eef2ff;
        color:#4f46e5;
    }
    .amount{
        text-align:right;
        white-space:nowrap;
    }
</style>

@php
    $statusClass = match($propuesta->status) {
        'draft' => 'status-draft',
        'revisar' => 'status-revisar',
        'enviada' => 'status-enviada',
        'adjudicada' => 'status-adjudicada',
        'no_adjudicada' => 'status-no_adjudicada',
        default => 'status-draft',
    };
    $statusLabels = [
        'draft' => 'Borrador',
        'revisar' => 'En revisión',
        'enviada' => 'Enviada',
        'adjudicada' => 'Adjudicada',
        'no_adjudicada' => 'No adjudicada',
    ];
@endphp

<div class="page-wrapper">
    <div class="header">
        <div class="title-block">
            <div class="title-icon">📊</div>
            <div>
                <div class="title-main">{{ $propuesta->codigo }} · {{ $propuesta->titulo }}</div>
                <div class="title-sub">
                    Propuesta económica comparativa &mdash; Fecha {{ $propuesta->fecha?->format('d/m/Y') }}
                </div>
                <div class="meta-grid">
                    @if($propuesta->licitacion_id)
                        <div>🔗 Licitación ID: <strong>{{ $propuesta->licitacion_id }}</strong></div>
                    @endif
                    @if($propuesta->requisicion_id)
                        <div>📌 Requisición ID: <strong>{{ $propuesta->requisicion_id }}</strong></div>
                    @endif
                    <div>🧾 Renglones ofertados: <strong>{{ $propuesta->items->count() }}</strong></div>
                </div>
            </div>
        </div>
        <div>
            <div class="status-pill {{ $statusClass }}">
                <span class="status-dot"></span>
                {{ $statusLabels[$propuesta->status] ?? $propuesta->status }}
            </div>
        </div>
    </div>

    <div class="summary-card">
        <div class="summary-item">
            Subtotal: <strong>{{ $propuesta->moneda ?? 'MXN' }} {{ number_format($propuesta->subtotal,2) }}</strong>
        </div>
        <div class="summary-item">
            IVA: <strong>{{ $propuesta->moneda ?? 'MXN' }} {{ number_format($propuesta->iva,2) }}</strong>
        </div>
        <div class="summary-item">
            <span class="total-badge">
                Total: {{ $propuesta->moneda ?? 'MXN' }} {{ number_format($propuesta->total,2) }}
            </span>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Solicitado en licitación</th>
                    <th>Producto ofertado (catálogo)</th>
                    <th>Match IA</th>
                    <th>Cant.</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($propuesta->items as $item)
                    @php
                        $req = $item->requestItem;
                        $prod = $item->product;
                        $score = $item->match_score ?? 0;
                        $scorePercent = max(0,min(100,$score));
                    @endphp
                    <tr>
                        <td>{{ $req?->renglon ?? $loop->iteration }}</td>
                        <td>
                            @if($req)
                                <div style="font-size:12px; color:var(--ink); white-space:pre-wrap;">
                                    {{ $req->line_raw }}
                                </div>
                                <div style="font-size:11px; color:var(--muted); margin-top:3px;">
                                    Página {{ $req->page?->page_number ?? '—' }}
                                </div>
                            @else
                                <span style="color:var(--muted); font-size:11px;">Sin renglón asociado</span>
                            @endif
                        </td>
                        <td>
                            @if($prod)
                                <div class="product-name">
                                    {{ $prod->sku ?? '' }} {{ $prod->name ?? '' }}
                                </div>
                                <div class="product-meta">
                                    @if(!empty($prod->brand))
                                        Marca: {{ $prod->brand }} ·
                                    @endif
                                    Unidad: {{ $item->unidad_propuesta ?? $prod->unit ?? '—' }}
                                </div>
                                @if(!empty($prod->description))
                                    <div class="product-meta" style="margin-top:3px;">
                                        {{ \Illuminate\Support\Str::limit($prod->description,110) }}
                                    </div>
                                @endif
                            @else
                                <span style="font-size:11px; color:var(--muted);">
                                    Aún sin producto sugerido. Ajusta en la edición o lanza nuevamente el match.
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($item->match_score !== null)
                                <div class="pill-match">
                                    <div class="pill-match-bar">
                                        <div class="pill-match-bar-inner"
                                             style="transform:scaleX({{ $scorePercent/100 }});"></div>
                                    </div>
                                    <span>{{ $scorePercent }}%</span>
                                </div>
                                @if($item->motivo_seleccion)
                                    <div style="font-size:11px; color:var(--muted); margin-top:3px;">
                                        {{ $item->motivo_seleccion }}
                                    </div>
                                @endif
                            @else
                                <span class="badge-tag">IA pendiente</span>
                            @endif
                        </td>
                        <td class="amount">
                            {{ $item->cantidad_propuesta ?? $req?->cantidad ?? '—' }}
                        </td>
                        <td class="amount">
                            @if($item->precio_unitario)
                                {{ $propuesta->moneda ?? 'MXN' }} {{ number_format($item->precio_unitario,2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="amount">
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
                        <td colspan="7" style="text-align:center; font-size:13px; color:var(--muted); padding:16px;">
                            Esta propuesta aún no tiene renglones generados.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
