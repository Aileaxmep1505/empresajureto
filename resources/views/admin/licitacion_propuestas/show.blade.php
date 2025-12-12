@extends('layouts.app')

@section('title', $propuesta->codigo.' - Propuesta económica')

@section('content')
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
        margin-bottom:16px;
        border-radius:16px;
        background:white;
        border:1px solid var(--border);
        box-shadow:var(--shadow-soft);
        padding:10px 14px;
        display:flex;
        flex-wrap:wrap;
        gap:8px 24px;
        align-items:center;
        font-size:12px;
    }
    .summary-item{
        color:var(--muted);
    }
    .summary-item strong{
        color:var(--ink);
    }
    .total-badge{
        border-radius:20px;
        padding:8px 16px;
        background:linear-gradient(135deg,#22c55e,#16a34a);
        color:white;
        font-size:13px;
        font-weight:600;
        display:inline-flex;
        align-items:center;
        gap:8px;
    }

    /* PDF + splits */
    .pdf-card{
        margin-bottom:12px;
        border-radius:16px;
        background:white;
        border:1px solid var(--border);
        box-shadow:var(--shadow-soft);
        padding:10px 14px;
        display:flex;
        flex-direction:column;
        gap:10px;
        font-size:12px;
    }
    .pdf-top{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:8px;
    }
    .pdf-name{
        color:var(--ink);
        font-weight:500;
    }
    .pdf-link{
        font-size:12px;
        color:#2563eb;
        text-decoration:none;
        font-weight:500;
    }

    .splits-row{
        display:flex;
        flex-direction:column;
        gap:6px;
        margin-top:4px;
    }

    .split-item{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        padding:6px 8px;
        border-radius:999px;
        background:#f9fafb;
        border:1px solid #e5e7eb;
        font-size:11px;
    }
    .split-meta{
        display:flex;
        align-items:center;
        gap:6px;
        flex-wrap:wrap;
    }
    .split-badge{
        padding:2px 8px;
        border-radius:999px;
        font-size:11px;
        background:#eef2ff;
        color:#4f46e5;
    }
    .split-pages{
        background:#ecfdf5;
        color:#15803d;
    }
    .split-pending{
        background:#fef3c7;
        color:#92400e;
    }
    .split-current{
        background:#dbeafe;
        color:#1d4ed8;
    }
    .split-done{
        background:#dcfce7;
        color:#166534;
    }

    .btn-small{
        border-radius:999px;
        border:none;
        padding:5px 10px;
        font-size:11px;
        font-weight:500;
        background:#4f46e5;
        color:white;
        display:inline-flex;
        align-items:center;
        gap:4px;
        cursor:pointer;
    }
    .btn-small-outline{
        border-radius:999px;
        border:1px solid #d1d5db;
        padding:5px 10px;
        font-size:11px;
        background:#ffffff;
        color:#4b5563;
    }
    .btn-small[disabled]{
        opacity:.55;
        cursor:not-allowed;
        box-shadow:none;
    }

    .merge-row{
        margin-bottom:16px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        font-size:11px;
        color:#6b7280;
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

    // ✅ Definir $allSplitsProcessed para que no marque undefined
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

<div class="page-wrapper">

    <div class="header">
        <div class="title-block">
            <div class="title-icon">📊</div>
            <div>
                <div class="title-main">{{ $propuesta->codigo }} · {{ $propuesta->titulo }}</div>
                <div class="title-sub">
                    Propuesta económica comparativa — Fecha {{ $propuesta->fecha?->format('d/m/Y') }}
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

    {{-- PDF asociado + splits --}}
    @if($licitacionPdf)
        <div class="pdf-card">
            <div class="pdf-top">
                <div>
                    <div style="font-size:12px; color:#6b7280; margin-bottom:2px;">
                        PDF de licitación asociado:
                    </div>
                    <div class="pdf-name">
                        {{ $licitacionPdf->id }}. {{ $licitacionPdf->original_filename ?? 'Archivo de licitación' }}
                    </div>
                </div>
                <a
                    href="{{ route('admin.licitacion-pdfs.preview', $licitacionPdf) }}"
                    target="_blank"
                    class="pdf-link"
                >
                    Ver PDF completo
                </a>
            </div>

            @if(!empty($splitsInfo))
                <div class="splits-row">
                    @foreach($splitsInfo as $i => $s)
                        @php
                            $stateClass = match($s['state']) {
                                'done', 'done-current' => 'split-done',
                                'current'              => 'split-current',
                                default                => 'split-pending',
                            };
                        @endphp
                        <div class="split-item">
                            <div class="split-meta">
                                <span class="split-badge {{ $stateClass }}">
                                    Req. {{ $i+1 }}
                                    @if($s['state'] === 'done' || $s['state'] === 'done-current')
                                        · procesada
                                    @elseif($s['state'] === 'current')
                                        · en curso
                                    @else
                                        · pendiente
                                    @endif
                                </span>
                                @if($s['from'] && $s['to'])
                                    <span class="split-badge">
                                        págs {{ $s['from'] }}–{{ $s['to'] }}
                                    </span>
                                @endif
                                @if($s['pages'])
                                    <span class="split-badge split-pages">
                                        {{ $s['pages'] }} pág.
                                    </span>
                                @endif
                            </div>

                            <form
                                method="POST"
                                action="{{ route('admin.licitacion-propuestas.splits.process', [
                                    'licitacionPropuesta' => $propuesta->id,
                                    'splitIndex'          => $s['index'],
                                ]) }}"
                            >
                                @csrf
                                <button
                                    type="submit"
                                    class="btn-small"
                                >
                                    @if($s['state'] === 'done' || $s['state'] === 'done-current')
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
    @endif

    {{-- resumen + merge --}}
    <div class="merge-row">
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

        <form
            method="POST"
            action="{{ route('admin.licitacion-propuestas.merge', $propuesta) }}"
        >
            @csrf
            <button
                type="submit"
                class="btn-small"
                {{ !$allSplitsProcessed ? 'disabled' : '' }}
            >
                Hacer merge global
            </button>
        </form>
    </div>

    {{-- tabla --}}
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
                            Esta propuesta aún no tiene renglones generados. Procesa un PDF con IA en el bloque superior.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection
