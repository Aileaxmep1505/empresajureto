@extends('layouts.app')

@section('title', 'Propuestas económicas')

@section('content')
<style>
    :root{
        --ink:#0f172a;
        --muted:#6b7280;
        --accent:#4f46e5;
        --accent-soft:#eef2ff;
        --border:#e5e7eb;
        --radius:18px;
        --shadow-soft:0 18px 40px rgba(15,23,42,0.06);
        --success:#16a34a;
        --danger:#ef4444;
        --warning:#f59e0b;
    }
    .page-wrapper{
        max-width: 1100px;
        margin: 0 auto;
        padding: 16px;
    }
    .header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:16px;
        margin-bottom:18px;
    }
    .title{
        font-size:22px;
        font-weight:700;
        color:var(--ink);
    }
    .subtitle{
        font-size:13px;
        color:var(--muted);
    }
    .btn-primary{
        border-radius:999px;
        border:none;
        padding:8px 16px;
        font-size:14px;
        font-weight:600;
        background:linear-gradient(135deg,var(--accent),#6366f1);
        color:white;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:8px;
        box-shadow:0 12px 30px rgba(79,70,229,0.22);
    }
    .filters{
        display:flex;
        flex-wrap:wrap;
        gap:10px 14px;
        margin-bottom:16px;
    }
    .filters label{
        font-size:11px;
        color:var(--muted);
        display:block;
        margin-bottom:3px;
    }
    .filters input,
    .filters select{
        border-radius:999px;
        border:1px solid var(--border);
        padding:7px 11px;
        font-size:12px;
    }
    .table-wrapper{
        border-radius:var(--radius);
        border:1px solid var(--border);
        overflow:hidden;
        background:white;
        box-shadow:var(--shadow-soft);
    }
    table{
        width:100%;
        border-collapse:collapse;
        font-size:12px;
    }
    thead{
        background:#f9fafb;
    }
    th,td{
        padding:8px 10px;
        border-bottom:1px solid #e5e7eb;
        text-align:left;
        vertical-align:middle;
    }
    th{
        color:var(--muted);
        font-size:11px;
        font-weight:600;
    }
    tbody tr:hover{
        background:#f9fafb;
    }
    .status-pill{
        border-radius:999px;
        padding:3px 8px;
        font-size:11px;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }
    .status-dot{
        width:7px;
        height:7px;
        border-radius:999px;
        background:currentColor;
    }
    .status-draft{ color:var(--warning); background:#fffbeb; }
    .status-revisar{ color:var(--accent); background:#eef2ff; }
    .status-enviada{ color:var(--accent); background:#e0f2fe; }
    .status-adjudicada{ color:var(--success); background:#ecfdf3; }
    .status-no_adjudicada{ color:var(--danger); background:#fef2f2; }

    .link-row{
        color:var(--accent);
        text-decoration:none;
        font-weight:500;
    }
</style>

<div class="page-wrapper">
    <div class="header">
        <div>
            <div class="title">Propuestas económicas comparativas</div>
            <div class="subtitle">
                Revisa las propuestas generadas a partir de las requisiciones procesadas con IA.
            </div>
        </div>
        <a href="{{ route('admin.licitacion-propuestas.create') }}" class="btn-primary">
            <span>+ Nueva propuesta</span>
        </a>
    </div>

    <form method="GET" class="filters">
        <div>
            <label for="licitacion_id">Licitación ID</label>
            <input type="number" name="licitacion_id" id="licitacion_id" value="{{ request('licitacion_id') }}">
        </div>
        <div>
            <label for="requisicion_id">Requisición ID</label>
            <input type="number" name="requisicion_id" id="requisicion_id" value="{{ request('requisicion_id') }}">
        </div>
        <div>
            <label for="status">Estatus</label>
            <select name="status" id="status">
                <option value="">Todos</option>
                <option value="draft" {{ request('status')==='draft'?'selected':'' }}>Borrador</option>
                <option value="revisar" {{ request('status')==='revisar'?'selected':'' }}>En revisión</option>
                <option value="enviada" {{ request('status')==='enviada'?'selected':'' }}>Enviada</option>
                <option value="adjudicada" {{ request('status')==='adjudicada'?'selected':'' }}>Adjudicada</option>
                <option value="no_adjudicada" {{ request('status')==='no_adjudicada'?'selected':'' }}>No adjudicada</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn-primary" style="padding:7px 14px; font-size:12px; box-shadow:none;">
                Filtrar
            </button>
        </div>
    </form>

    @if($propuestas->count())
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Licitación / Requisición</th>
                        <th>Título</th>
                        <th>Fecha</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($propuestas as $p)
                        <tr>
                            <td>
                                <a href="{{ route('admin.licitacion-propuestas.show',$p) }}" class="link-row">
                                    {{ $p->codigo }}
                                </a>
                            </td>
                            <td>
                                @if($p->licitacion_id)
                                    Licitación {{ $p->licitacion_id }}<br>
                                @endif
                                @if($p->requisicion_id)
                                    <span style="font-size:11px; color:var(--muted);">
                                        Req. {{ $p->requisicion_id }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $p->titulo }}</td>
                            <td>{{ $p->fecha?->format('d/m/Y') }}</td>
                            <td>
                                @php
                                    $statusClass = match($p->status) {
                                        'draft' => 'status-draft',
                                        'revisar' => 'status-revisar',
                                        'enviada' => 'status-enviada',
                                        'adjudicada' => 'status-adjudicada',
                                        'no_adjudicada' => 'status-no_adjudicada',
                                        default => 'status-draft',
                                    };
                                    $labels = [
                                        'draft' => 'Borrador',
                                        'revisar' => 'En revisión',
                                        'enviada' => 'Enviada',
                                        'adjudicada' => 'Adjudicada',
                                        'no_adjudicada' => 'No adjudicada',
                                    ];
                                @endphp
                                <span class="status-pill {{ $statusClass }}">
                                    <span class="status-dot"></span>
                                    {{ $labels[$p->status] ?? $p->status }}
                                </span>
                            </td>
                            <td>
                                {{ $p->moneda ?? 'MXN' }}
                                {{ number_format($p->total,2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:16px;">
            {{ $propuestas->withQueryString()->links() }}
        </div>
    @else
        <p style="font-size:13px; color:var(--muted);">
            Aún no hay propuestas registradas. Crea una nueva a partir de una requisición.
        </p>
    @endif
</div>
@endsection
