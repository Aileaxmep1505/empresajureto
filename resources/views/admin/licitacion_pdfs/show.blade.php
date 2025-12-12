@extends('layouts.app') 

@section('title', 'Separar PDF de licitación')
@section('header', 'Separar PDF (Licitación)')

@section('content')
@php
    $minPage = 1;
    $maxPage = $pageCount > 0 ? $pageCount : 1;
@endphp

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root{
        --lp-ink:#0f172a;
        --lp-muted:#6b7280;
        --lp-soft:#f9fafb;
        --lp-soft-2:#eff6ff;
        --lp-border:#e5e7eb;
        --lp-accent:#2563eb;
        --lp-accent-soft:#dbeafe;
        --lp-accent-2:#22c55e;
        --lp-bg:#f5f7ff;
        --lp-radius:20px;
        --lp-shadow:0 22px 50px rgba(148,163,184,.35);
    }

    .lp-shell{
        font-family:"Inter","system-ui",-apple-system,"Segoe UI",sans-serif;
        color:var(--lp-ink);
        display:flex;
        flex-direction:column;
        gap:18px;
    }

    .lp-breadcrumb{
        font-size:.85rem;
        color:var(--lp-muted);
        display:flex;
        align-items:center;
        gap:6px;
    }
    .lp-breadcrumb a{
        color:inherit;
        text-decoration:none;
    }
    .lp-breadcrumb a:hover{
        text-decoration:underline;
    }

    .lp-head{
        display:flex;
        flex-wrap:wrap;
        gap:14px;
        align-items:flex-start;
        justify-content:space-between;
    }
    .lp-head-main{
        display:flex;
        align-items:flex-start;
        gap:14px;
    }
    .lp-head-icon{
        width:50px;
        height:50px;
        border-radius:18px;
        display:grid;
        place-items:center;
        background:radial-gradient(circle at 0 0,#bfdbfe,#2563eb);
        color:white;
        box-shadow:0 16px 40px rgba(37,99,235,.55);
        transform:translateY(1px);
        animation:lpFloat 3s ease-in-out infinite;
    }
    @keyframes lpFloat{
        0%,100%{ transform:translateY(1px); }
        50%{ transform:translateY(-3px); }
    }

    .lp-head-title{
        font-weight:700;
        font-size:1.15rem;
    }
    .lp-head-tags{
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        margin-top:6px;
    }
    .lp-tag{
        padding:4px 9px;
        border-radius:999px;
        font-size:.78rem;
        background:var(--lp-soft);
        border:1px solid rgba(226,232,240,1);
        color:var(--lp-muted);
    }
    .lp-tag--status{
        background:#ecfdf3;
        border-color:#bbf7d0;
        color:#15803d;
    }

    .lp-head-actions{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }

    .lp-main{
        display:grid;
        grid-template-columns:minmax(0, 360px) minmax(0, 1fr);
        gap:18px;
    }
    @media (max-width: 1024px){
        .lp-main{
            grid-template-columns:minmax(0,1fr);
        }
        .lp-head{
            flex-direction:column;
            align-items:flex-start;
        }
    }

    .lp-card{
        border-radius:var(--lp-radius);
        background: radial-gradient(circle at 0 0,#eff6ff,#ffffff);
        border:1px solid rgba(191,219,254,1);
        padding:18px 18px 20px;
        box-shadow:var(--lp-shadow);
        position:relative;
        overflow:hidden;
    }
    .lp-card::before{
        content:"";
        position:absolute;
        inset:-80px;
        background:radial-gradient(circle at 0 0,rgba(191,219,254,.5),transparent 60%);
        opacity:.7;
        pointer-events:none;
    }
    .lp-card-inner{
        position:relative;
        z-index:1;
    }

    .lp-card-title{
        font-weight:600;
        font-size:.98rem;
        display:flex;
        align-items:center;
        gap:8px;
        margin-bottom:4px;
    }

    .lp-range-fields{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        margin-bottom:12px;
        align-items:flex-end;
    }
    .lp-field{
        flex:1 1 120px;
        min-width:0;
    }
    .lp-field label{
        display:block;
        font-size:.8rem;
        font-weight:500;
        color:var(--lp-muted);
        margin-bottom:4px;
    }
    .lp-field input[type="number"]{
        width:100%;
        padding:9px 10px;
        border-radius:999px;
        border:1px solid var(--lp-border);
        font-size:.9rem;
        outline:none;
        background:white;
        transition:border .16s ease, box-shadow .16s ease, transform .06s ease, background .16s ease;
    }
    .lp-field input[type="number"]:focus{
        border-color:var(--lp-accent);
        box-shadow:0 0 0 1px rgba(37,99,235,.25), 0 10px 25px rgba(148,163,184,.25);
        transform:translateY(-1px);
        background:#eff6ff;
    }

    .lp-pill-range{
        font-size:.78rem;
        padding:4px 9px;
        border-radius:999px;
        border:1px solid rgba(191,219,254,1);
        background:rgba(239,246,255,.9);
        display:inline-flex;
        align-items:center;
        gap:5px;
    }

    .lp-quick{
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-bottom:12px;
    }
    .lp-chip-btn{
        border-radius:999px;
        border:1px dashed rgba(191,219,254,.9);
        padding:4px 10px;
        font-size:.78rem;
        background:rgba(239,246,255,.9);
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:6px;
        transition:background .16s ease, transform .06s ease, box-shadow .18s ease;
    }
    .lp-chip-btn:hover{
        background:#e0f2fe;
        transform:translateY(-1px);
        box-shadow:0 10px 24px rgba(148,163,184,.25);
    }

    .lp-actions{
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        align-items:center;
        margin-top:6px;
    }
    .lp-btn{
        border-radius:999px;
        border:none;
        padding:9px 18px;
        font-size:.9rem;
        font-weight:600;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:8px;
        letter-spacing:.01em;
        transition:transform .08s ease, box-shadow .18s ease, filter .18s ease;
        text-decoration:none;
    }
    .lp-btn-primary{
        background:linear-gradient(135deg,#eff6ff,#dbeafe);
        color:#2563eb;
        box-shadow:0 16px 36px rgba(37,99,235,.35);
    }
    .lp-btn-primary:hover{
        filter:brightness(1.03);
        transform:translateY(-1px);
        box-shadow:0 20px 48px rgba(37,99,235,.45);
    }
    .lp-btn-ghost{
        background:linear-gradient(135deg,#f0fdf4,#dcfce7);
        color:#15803d;
        box-shadow:0 10px 26px rgba(34,197,94,.24);
    }

    .lp-btn-cta{
        background:linear-gradient(135deg,#22c55e,#16a34a);
        color:#f0fdf4;
        box-shadow:0 16px 40px rgba(22,163,74,.45);
        font-size:.9rem;
        padding:9px 20px;
    }
    .lp-btn-cta:hover{
        filter:brightness(1.03);
        transform:translateY(-1px);
        box-shadow:0 20px 52px rgba(21,128,61,.55);
    }
    .lp-btn-cta[aria-disabled="true"]{
        opacity:.5;
        cursor:not-allowed;
        box-shadow:none;
        transform:none;
    }

    .lp-hint{
        font-size:.8rem;
        color:var(--lp-muted);
        margin-top:6px;
    }

    .lp-preview-card{
        border-radius:var(--lp-radius);
        background:#020617;
        border:1px solid #020617;
        overflow:hidden;
        box-shadow:0 24px 60px rgba(15,23,42,.65);
        display:flex;
        flex-direction:column;
        min-height:600px;
    }
    .lp-preview-head{
        padding:10px 14px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        background:radial-gradient(circle at 0 0,#111827,#020617);
        color:#e5e7eb;
        font-size:.82rem;
    }
    .lp-preview-frame{
        border:none;
        width:100%;
        flex:1;
        min-height:600px;
        height:70vh;
        background:#020617;
    }

    .lp-splits-card{
        margin-top:18px;
        border-radius:var(--lp-radius);
        background:linear-gradient(135deg,#ecfeff,#f1f5f9);
        border:1px solid rgba(219,234,254,1);
        padding:14px 16px 16px;
        box-shadow:0 14px 34px rgba(148,163,184,.35);
    }
    .lp-splits-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-bottom:8px;
    }
    .lp-splits-title{
        font-weight:600;
        font-size:.92rem;
        display:flex;
        align-items:center;
        gap:6px;
    }
    .lp-splits-sub{
        font-size:.8rem;
        color:var(--lp-muted);
    }
    .lp-splits-empty{
        font-size:.8rem;
        color:var(--lp-muted);
        padding-top:4px;
    }
    .lp-splits-list{
        display:flex;
        flex-direction:column;
        gap:8px;
        margin-top:4px;
        margin-bottom:10px;
    }
    .lp-split-item{
        border-radius:999px;
        background:rgba(255,255,255,.95);
        border:1px solid rgba(191,219,254,1);
        padding:6px 10px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:8px;
        font-size:.8rem;
        transition:transform .06s ease, box-shadow .16s ease, background .16s ease;
    }
    .lp-split-item:hover{
        transform:translateY(-1px);
        box-shadow:0 10px 26px rgba(148,163,184,.35);
        background:#e0f2fe;
    }
    .lp-split-meta{
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    }
    .lp-pill{
        padding:2px 8px;
        border-radius:999px;
        font-size:.75rem;
        background:#dbeafe;
        color:#1d4ed8;
    }
    .lp-pill-pages{
        background:#ecfdf5;
        color:#15803d;
    }

    .lp-split-actions{
        display:flex;
        gap:6px;
        flex-wrap:wrap;
    }
    .lp-split-link{
        border-radius:999px;
        padding:5px 11px;
        font-size:.78rem;
        font-weight:600;
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:4px;
        white-space:nowrap;
        box-shadow:0 10px 24px rgba(129,140,248,.25);
        transition:box-shadow .16s ease, transform .06s ease;
    }
    .lp-split-link:hover{
        box-shadow:0 14px 32px rgba(129,140,248,.35);
        transform:translateY(-1px);
    }
    .lp-split-link--pdf{
        background:linear-gradient(135deg,#eff6ff,#e0f2fe);
        color:#1d4ed8;
    }

    .lp-cta-wrapper{
        margin-top:6px;
        border-top:1px dashed rgba(191,219,254,.8);
        padding-top:8px;
    }
    .lp-cta-hint{
        font-size:.78rem;
        color:var(--lp-muted);
        margin-top:4px;
    }

    .lp-alert{
        margin-bottom:10px;
        padding:8px 10px;
        border-radius:12px;
        font-size:.8rem;
        background:#ecfdf5;
        color:#166534;
        border:1px solid #bbf7d0;
        display:flex;
        align-items:flex-start;
        gap:8px;
    }
</style>

<div
    class="lp-shell"
    x-data="{
        from: {{ $minPage }},
        to: {{ $maxPage }},
        minPage: {{ $minPage }},
        maxPage: {{ $maxPage }},
        loadingSplit: false,
        setAll(){
            this.from = this.minPage;
            this.to   = this.maxPage;
        },
        setFirstHalf(){
            const mid = Math.floor((this.maxPage - this.minPage + 1) / 2);
            this.from = this.minPage;
            this.to   = this.minPage + mid - 1;
        },
        setSecondHalf(){
            const mid = Math.floor((this.maxPage - this.minPage + 1) / 2);
            this.from = this.minPage + mid;
            this.to   = this.maxPage;
        },
        clamp(){
            if(this.from < this.minPage) this.from = this.minPage;
            if(this.to   > this.maxPage) this.to   = this.maxPage;
            if(this.to < this.from) this.to = this.from;
        }
    }"
>
    {{-- Migas --}}
    <div class="lp-breadcrumb">
        <a href="{{ route('admin.licitacion-pdfs.index') }}">Gestor de PDFs</a>
        <span>/</span>
        <span>Separar PDF</span>
    </div>

    {{-- Mensaje flash --}}
    @if (session('status'))
        <div class="lp-alert">
            <span>✅</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    {{-- Encabezado --}}
    <div class="lp-head">
        <div class="lp-head-main">
            <div class="lp-head-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.6" fill="none">
                    <rect x="4" y="3" width="14" height="18" rx="2" stroke="white" opacity=".9"/>
                    <path d="M9 7h6M9 11h4M9 15h6" stroke="white"/>
                </svg>
            </div>
            <div>
                <div class="lp-head-title">
                    {{ $pdf->original_filename ?? 'Archivo de licitación' }}
                </div>

                <div class="lp-head-tags">
                    <span class="lp-tag lp-tag--status">Archivo subido</span>
                    <span class="lp-tag">{{ $pageCount }} páginas</span>
                    @if($pdf->licitacion_id)
                        <span class="lp-tag">Licitación #{{ $pdf->licitacion_id }}</span>
                    @endif
                    @if($pdf->requisicion_id)
                        <span class="lp-tag">Requisición #{{ $pdf->requisicion_id }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- CTA superior opcional (misma ruta que abajo, solo accesible si hay splits) --}}
        <div class="lp-head-actions">
            @if(!$splits->isEmpty())
                <a
                    href="{{ route('admin.licitacion-pdfs.propuesta', ['licitacionPdf' => $pdf->id]) }}"
                    class="lp-btn lp-btn-cta"
                >
                    Ir a propuesta económica
                </a>
            @endif
        </div>
    </div>

    {{-- Main --}}
    <div class="lp-main">
        {{-- COLUMNA IZQUIERDA: rango + lista de recortes --}}
        <div>
            <div class="lp-card">
                <div class="lp-card-inner">
                    <div class="lp-card-title">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
                            <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6"/>
                        </svg>
                        Selecciona el rango de páginas
                    </div>

                    <form
                        method="POST"
                        action="{{ route('admin.licitacion-pdfs.split', ['licitacionPdf' => $pdf->id]) }}"
                        x-on:submit="clamp(); loadingSplit = true"
                    >
                        @csrf

                        <div class="lp-range-fields">
                            <div class="lp-field">
                                <label for="from_page">Desde la página</label>
                                <input
                                    id="from_page"
                                    name="from"
                                    type="number"
                                    x-model.number="from"
                                    x-on:change="clamp()"
                                    min="{{ $minPage }}"
                                    max="{{ $maxPage }}"
                                    required
                                >
                            </div>
                            <div class="lp-field">
                                <label for="to_page">Hasta la página</label>
                                <input
                                    id="to_page"
                                    name="to"
                                    type="number"
                                    x-model.number="to"
                                    x-on:change="clamp()"
                                    min="{{ $minPage }}"
                                    max="{{ $maxPage }}"
                                    required
                                >
                            </div>
                            <div class="lp-field" style="flex:0 0 auto;">
                                <label>&nbsp;</label>
                                <div class="lp-pill-range">
                                    Selección actual:
                                    <strong x-text="from"></strong>
                                    –
                                    <strong x-text="to"></strong>
                                    <span style="opacity:.8;">
                                        (<span x-text="(to - from + 1)"></span> pág.)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="lp-quick">
                            <button type="button" class="lp-chip-btn" x-on:click="setAll()">
                                ☰ Todo el documento
                            </button>
                            <button type="button" class="lp-chip-btn" x-on:click="setFirstHalf()">
                                🌓 Primera mitad
                            </button>
                            <button type="button" class="lp-chip-btn" x-on:click="setSecondHalf()">
                                🌗 Segunda mitad
                            </button>
                        </div>

                        <div class="lp-actions">
                            <button type="submit" class="lp-btn lp-btn-primary" :disabled="loadingSplit">
                                <span x-text="loadingSplit ? 'Creando recorte…' : 'Descargar PDF recortado'"></span>
                            </button>

                            <button
                                type="button"
                                class="lp-btn lp-btn-ghost"
                                onclick="window.history.back()"
                            >
                                ← Volver
                            </button>
                        </div>

                        <p class="lp-hint">
                            Para obtener varios archivos del mismo PDF, repite el proceso con distintos rangos
                            (por ejemplo 1–10, luego 11–70, luego 71–100).
                        </p>
                    </form>
                </div>
            </div>

            {{-- Lista de recortes en la misma columna --}}
            <div class="lp-splits-card">
                <div class="lp-splits-head">
                    <div>
                        <div class="lp-splits-title">
                            <span>Archivos recortados</span>
                        </div>
                        <div class="lp-splits-sub">
                            Cada recorte se guarda aquí. Haz clic para descargarlo cuando lo necesites.
                        </div>
                    </div>
                    <div class="lp-pill">
                        {{ count($splits) }} creados
                    </div>
                </div>

                @if($splits->isEmpty())
                    <div class="lp-splits-empty">
                        Aún no hay recortes generados. Crea el primero usando el botón
                        <strong>“Descargar PDF recortado”</strong>.
                    </div>
                @else
                    <div class="lp-splits-list">
                        @foreach($splits as $split)
                            <div class="lp-split-item">
                                <div class="lp-split-meta">
                                    <span class="lp-pill">
                                        págs {{ $split['from'] }}–{{ $split['to'] }}
                                    </span>
                                    <span class="lp-pill lp-pill-pages">
                                        {{ $split['page_count'] ?? ($split['to'] - $split['from'] + 1) }} pág.
                                    </span>
                                    <span style="font-size:.76rem; color:var(--lp-muted);">
                                        {{ \Illuminate\Support\Carbon::parse($split['created_at'])->format('d/m H:i') }}
                                    </span>
                                </div>

                                <div class="lp-split-actions">
                                    <a
                                        href="{{ route('admin.licitacion-pdfs.splits.download', ['licitacionPdf' => $pdf->id, 'index' => $split['index'], 'format' => 'pdf']) }}"
                                        class="lp-split-link lp-split-link--pdf"
                                        target="_blank"
                                    >
                                        PDF
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- CTA hacia la propuesta económica --}}
                    <div class="lp-cta-wrapper">
                        <a
                            href="{{ route('admin.licitacion-pdfs.propuesta', ['licitacionPdf' => $pdf->id]) }}"
                            class="lp-btn lp-btn-cta"
                        >
                            Crear propuesta económica con estos recortes
                        </a>
                        <div class="lp-cta-hint">
                            Te llevaremos a la vista de cotización económica usando todos los PDFs recortados de esta licitación.
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- COLUMNA DERECHA: solo vista previa --}}
        <div>
            <div class="lp-preview-card">
                <div class="lp-preview-head">
                    <div>
                        Vista previa del PDF
                        <div style="font-size:.75rem; opacity:.8;">
                            Úsala como referencia para ubicar en qué páginas cortar.
                        </div>
                    </div>
                    <div style="font-size:.75rem; opacity:.9;">
                        {{ $pageCount }} páginas
                    </div>
                </div>
                <iframe
                    class="lp-preview-frame"
                    src="{{ route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $pdf->id]) }}"
                ></iframe>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
