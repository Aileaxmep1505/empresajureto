@extends('layouts.app')

@section('title', 'Requisiciones PDF')

@section('content')
<style>
    :root{
        --ink:#0f172a;
        --muted:#6b7280;
        --accent:#4f46e5;
        --accent-soft:#eef2ff;
        --accent-soft-2:#f1f5f9;
        --danger:#ef4444;
        --success:#16a34a;
        --warning:#f59e0b;
        --radius:18px;
        --border:#e5e7eb;
        --shadow-soft:0 18px 40px rgba(15,23,42,0.06);
    }

    .page-wrapper{
        max-width: 1100px;
        margin: 0 auto;
        padding: 16px;
    }
    .page-header{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:16px;
        margin-bottom:24px;
    }
    .page-title{
        font-size:24px;
        font-weight:700;
        color:var(--ink);
    }
    .page-subtitle{
        font-size:14px;
        color:var(--muted);
    }
    .btn-primary{
        border-radius:999px;
        border:none;
        padding:10px 18px;
        font-size:14px;
        font-weight:600;
        background:linear-gradient(135deg,var(--accent),#6366f1);
        color:white;
        display:inline-flex;
        align-items:center;
        gap:8px;
        cursor:pointer;
        box-shadow:0 12px 30px rgba(79,70,229,0.22);
        transition:transform 0.18s ease, box-shadow 0.18s ease, filter 0.18s ease;
    }
    .btn-primary:hover{
        transform:translateY(-1px);
        box-shadow:0 18px 40px rgba(79,70,229,0.25);
        filter:brightness(1.03);
    }

    .filters-bar{
        display:flex;
        flex-wrap:wrap;
        gap:12px;
        margin-bottom:18px;
        align-items:flex-end;
    }
    .filters-bar label{
        font-size:12px;
        font-weight:500;
        color:var(--muted);
        margin-bottom:4px;
        display:block;
    }
    .filters-bar input,
    .filters-bar select{
        border-radius:999px;
        border:1px solid var(--border);
        padding:7px 12px;
        font-size:13px;
        outline:none;
        min-width:140px;
    }
    .filters-bar input:focus,
    .filters-bar select:focus{
        border-color:var(--accent);
        box-shadow:0 0 0 1px rgba(79,70,229,0.15);
    }

    .card-grid{
        display:grid;
        grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
        gap:16px;
    }
    .pdf-card{
        background:white;
        border-radius:var(--radius);
        padding:16px;
        border:1px solid rgba(226,232,240,0.9);
        box-shadow:var(--shadow-soft);
        display:flex;
        flex-direction:column;
        gap:10px;
        position:relative;
        overflow:hidden;
        cursor:pointer;
        transition:transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }
    .pdf-card::before{
        content:"";
        position:absolute;
        inset:0;
        background:radial-gradient(circle at 0 0,rgba(79,70,229,0.12),transparent 55%);
        opacity:0;
        transition:opacity 0.2s ease;
        pointer-events:none;
    }
    .pdf-card:hover{
        transform:translateY(-2px);
        box-shadow:0 20px 45px rgba(15,23,42,0.12);
        border-color:rgba(129,140,248,0.6);
    }
    .pdf-card:hover::before{
        opacity:1;
    }
    .pdf-title{
        font-size:14px;
        font-weight:600;
        color:var(--ink);
        display:flex;
        align-items:center;
        gap:8px;
    }
    .pdf-title-icon{
        width:28px;
        height:28px;
        border-radius:10px;
        background:var(--accent-soft);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:14px;
    }
    .pdf-meta{
        display:flex;
        flex-wrap:wrap;
        gap:8px 12px;
        font-size:11px;
        color:var(--muted);
    }
    .pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        border-radius:999px;
        padding:4px 9px;
        font-size:11px;
        background:var(--accent-soft-2);
        color:var(--muted);
    }
    .pill-dot{
        width:7px;
        height:7px;
        border-radius:999px;
        background:var(--accent);
    }
    .status-pill{
        background:rgba(15,23,42,0.02);
    }
    .status-uploaded{ color:#0f172a; }
    .status-processing{ color:var(--warning); }
    .status-items_extracted{ color:var(--success); }
    .status-proposal_ready{ color:var(--accent); }
    .status-error{ color:var(--danger); }

    .card-footer{
        margin-top:4px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        font-size:11px;
        color:var(--muted);
    }
    .card-actions{
        display:flex;
        gap:8px;
    }
    .btn-ghost{
        border-radius:999px;
        border:1px solid var(--border);
        padding:4px 10px;
        font-size:11px;
        background:white;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:6px;
        transition:background 0.16s ease, border-color 0.16s ease, transform 0.12s ease;
    }
    .btn-ghost:hover{
        background:var(--accent-soft);
        border-color:rgba(129,140,248,0.9);
        transform:translateY(-1px);
    }

    .empty-state{
        border-radius:var(--radius);
        border:1px dashed var(--border);
        padding:28px;
        text-align:center;
        background:linear-gradient(135deg,#fdfdfd,#f1f5f9);
        color:var(--muted);
        margin-top:10px;
    }
    .empty-state h3{
        font-size:16px;
        font-weight:600;
        color:var(--ink);
        margin-bottom:4px;
    }

    .toast-status{
        position:fixed;
        right:20px;
        bottom:20px;
        padding:12px 16px;
        border-radius:999px;
        background:rgba(22,163,74,0.96);
        color:white;
        font-size:13px;
        display:flex;
        align-items:center;
        gap:8px;
        box-shadow:0 18px 40px rgba(22,163,74,0.4);
        animation:toastIn .25s ease-out;
        z-index:50;
    }
    @keyframes toastIn{
        from{ transform:translateY(10px); opacity:0;}
        to{ transform:translateY(0); opacity:1;}
    }

    @media (max-width:768px){
        .page-header{
            flex-direction:column;
            align-items:flex-start;
        }
    }
</style>

@if(session('status'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,3500)" class="toast-status">
        <span>✅</span>
        <span>{{ session('status') }}</span>
    </div>
@endif

<script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

<div class="page-wrapper">
    <div class="page-header">
        <div>
            <div class="page-title">Requisiciones en PDF</div>
            <p class="page-subtitle">
                Sube PDFs de las bases / requisiciones para que la IA las convierta en renglones perfectos.
            </p>
        </div>
        <a href="{{ route('admin.licitacion-pdfs.create') }}" class="btn-primary">
            <span>+ Subir PDF de requisición</span>
        </a>
    </div>

    <form method="GET" class="filters-bar">
        <div>
            <label for="licitacion_id">Licitación ID</label>
            <input type="number" name="licitacion_id" id="licitacion_id"
                   value="{{ request('licitacion_id') }}" placeholder="Ej. 1024">
        </div>
        <div>
            <label for="requisicion_id">Requisición ID</label>
            <input type="number" name="requisicion_id" id="requisicion_id"
                   value="{{ request('requisicion_id') }}" placeholder="Opcional">
        </div>
        <div>
            <label for="status">Estatus</label>
            <select name="status" id="status">
                <option value="">Todos</option>
                <option value="uploaded" {{ request('status')==='uploaded'?'selected':'' }}>Subido</option>
                <option value="processing" {{ request('status')==='processing'?'selected':'' }}>Procesando</option>
                <option value="items_extracted" {{ request('status')==='items_extracted'?'selected':'' }}>Items extraídos</option>
                <option value="proposal_ready" {{ request('status')==='proposal_ready'?'selected':'' }}>Propuesta lista</option>
                <option value="error" {{ request('status')==='error'?'selected':'' }}>Error</option>
            </select>
        </div>
        <div>
            <button class="btn-ghost" type="submit">
                <span>🔍</span>
                <span>Filtrar</span>
            </button>
        </div>
    </form>

    @if($pdfs->count())
        <div class="card-grid">
            @foreach($pdfs as $pdf)
                <a href="{{ route('admin.licitacion-pdfs.show', $pdf) }}" class="pdf-card">
                    <div class="pdf-title">
                        <div class="pdf-title-icon">📄</div>
                        <div>
                            <div>{{ $pdf->original_filename }}</div>
                            <div style="font-size:11px; color:var(--muted);">
                                #{{ $pdf->id }} · creado {{ $pdf->created_at?->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>

                    <div class="pdf-meta">
                        @if($pdf->licitacion_id)
                            <span class="pill">
                                <span class="pill-dot"></span>
                                Licitación ID: {{ $pdf->licitacion_id }}
                            </span>
                        @endif
                        @if($pdf->requisicion_id)
                            <span class="pill">
                                <span class="pill-dot"></span>
                                Requisición ID: {{ $pdf->requisicion_id }}
                            </span>
                        @endif
                        <span class="pill">
                            📄 {{ $pdf->pages_count ?? '—' }} páginas
                        </span>
                    </div>

                    <div class="card-footer">
                        <div class="status-pill status-{{ $pdf->status }}">
                            @php
                                $labels = [
                                    'uploaded' => 'Subido',
                                    'processing' => 'Procesando con IA',
                                    'items_extracted' => 'Items extraídos',
                                    'proposal_ready' => 'Propuesta lista',
                                    'error' => 'Error',
                                ];
                            @endphp
                            <strong>Estatus:</strong>
                            &nbsp;{{ $labels[$pdf->status] ?? $pdf->status }}
                        </div>
                        <div class="card-actions">
                            <button type="button" class="btn-ghost"
                                    onclick="event.preventDefault(); window.location='{{ route('admin.licitacion-pdfs.show',$pdf) }}'">
                                Ver detalle
                            </button>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div style="margin-top:20px;">
            {{ $pdfs->withQueryString()->links() }}
        </div>
    @else
        <div class="empty-state">
            <h3>No hay PDFs de requisiciones todavía</h3>
            <p>Sube tu primer PDF para que la IA comience a extraer los renglones de la licitación.</p>
            <div style="margin-top:14px;">
                <a href="{{ route('admin.licitacion-pdfs.create') }}" class="btn-primary">
                    + Subir PDF
                </a>
            </div>
        </div>
    @endif
</div>
@endsection
