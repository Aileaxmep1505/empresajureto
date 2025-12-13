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
        --lp-soft:#f8fafc;
        --lp-border:#e5e7eb;
        --lp-accent:#2563eb;
        --lp-accent-soft:#dbeafe;
        --lp-success:#16a34a;
        --lp-success-soft:#dcfce7;
        --lp-bg:#f5f7ff;
        --lp-radius:18px;
        --lp-shadow:0 18px 44px rgba(15,23,42,.10);
    }

    .lp-shell{
        font-family: "Inter", system-ui, -apple-system, "Segoe UI", sans-serif;
        color: var(--lp-ink);
        display:flex;
        flex-direction:column;
        gap:14px;
    }

    .lp-topbar{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }

    .lp-breadcrumb{
        font-size:.85rem;
        color:var(--lp-muted);
        display:flex;
        align-items:center;
        gap:8px;
        flex-wrap:wrap;
    }
    .lp-breadcrumb a{
        color:inherit;
        text-decoration:none;
    }
    .lp-breadcrumb a:hover{ text-decoration:underline; }

    .lp-titleblock{
        display:flex;
        align-items:flex-start;
        gap:12px;
        flex-wrap:wrap;
    }

    .lp-icon{
        width:44px; height:44px;
        border-radius:14px;
        display:grid; place-items:center;
        background: linear-gradient(135deg, var(--lp-accent-soft), #ffffff);
        border:1px solid rgba(191,219,254,1);
        box-shadow: 0 10px 26px rgba(37,99,235,.10);
    }

    .lp-title{
        font-weight:700;
        font-size:1.05rem;
        line-height:1.2;
    }
    .lp-sub{
        margin-top:6px;
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        color:var(--lp-muted);
        font-size:.82rem;
    }

    .lp-badge{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:4px 10px;
        border-radius:999px;
        border:1px solid var(--lp-border);
        background:#fff;
        font-size:.78rem;
        color:var(--lp-muted);
    }

    .lp-badge--ok{
        background:var(--lp-success-soft);
        border-color:#bbf7d0;
        color:#166534;
    }

    .lp-actions{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        align-items:center;
    }

    .lp-btn{
        border:none;
        border-radius:999px;
        padding:9px 14px;
        font-weight:600;
        font-size:.88rem;
        display:inline-flex;
        align-items:center;
        gap:8px;
        cursor:pointer;
        text-decoration:none;
        transition:transform .08s ease, box-shadow .18s ease, filter .18s ease;
        white-space:nowrap;
    }

    .lp-btn-primary{
        background: linear-gradient(135deg, #eff6ff, var(--lp-accent-soft));
        color:#1d4ed8;
        box-shadow: 0 14px 32px rgba(37,99,235,.18);
    }
    .lp-btn-primary:hover{
        transform: translateY(-1px);
        box-shadow: 0 18px 44px rgba(37,99,235,.24);
    }

    .lp-btn-cta{
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color:#fff;
        box-shadow: 0 14px 32px rgba(22,163,74,.22);
    }
    .lp-btn-cta:hover{
        transform: translateY(-1px);
        box-shadow: 0 18px 44px rgba(22,163,74,.28);
    }

    .lp-btn-ghost{
        background:#fff;
        color:var(--lp-ink);
        border:1px solid var(--lp-border);
        box-shadow: 0 10px 26px rgba(15,23,42,.08);
    }
    .lp-btn-ghost:hover{
        transform: translateY(-1px);
        box-shadow: 0 14px 34px rgba(15,23,42,.10);
    }

    .lp-alert{
        border-radius:14px;
        border:1px solid #bbf7d0;
        background:var(--lp-success-soft);
        color:#166534;
        padding:10px 12px;
        font-size:.86rem;
        display:flex;
        gap:10px;
        align-items:flex-start;
    }

    .lp-grid{
        display:grid;
        grid-template-columns: minmax(0, 380px) minmax(0, 1fr);
        gap:14px;
    }
    @media (max-width: 1024px){
        .lp-grid{ grid-template-columns: minmax(0,1fr); }
    }

    .lp-card{
        border-radius: var(--lp-radius);
        background: #fff;
        border: 1px solid var(--lp-border);
        box-shadow: var(--lp-shadow);
        overflow:hidden;
    }

    .lp-card-h{
        padding:14px 14px 10px;
        border-bottom:1px solid rgba(229,231,235,.8);
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        flex-wrap:wrap;
    }

    .lp-card-title{
        display:flex;
        align-items:center;
        gap:10px;
        font-weight:700;
        font-size:.95rem;
    }

    .lp-card-b{
        padding:14px;
    }

    .lp-fields{
        display:grid;
        grid-template-columns: 1fr 1fr;
        gap:10px;
        align-items:end;
    }
    @media (max-width: 520px){
        .lp-fields{ grid-template-columns: 1fr; }
    }

    .lp-field label{
        display:block;
        font-size:.8rem;
        color:var(--lp-muted);
        margin-bottom:6px;
        font-weight:600;
    }
    .lp-field input{
        width:100%;
        border-radius:12px;
        border:1px solid var(--lp-border);
        padding:10px 12px;
        font-size:.9rem;
        outline:none;
        transition:border .16s ease, box-shadow .16s ease;
        background:#fff;
    }
    .lp-field input:focus{
        border-color: rgba(37,99,235,.65);
        box-shadow: 0 0 0 3px rgba(37,99,235,.12);
    }

    .lp-inline{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        padding:10px 12px;
        border-radius:12px;
        border:1px dashed rgba(191,219,254,.9);
        background: #f8fbff;
        margin-top:10px;
        font-size:.84rem;
        color:var(--lp-muted);
    }

    .lp-quick{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        margin-top:10px;
    }

    .lp-chip{
        border-radius:999px;
        border:1px solid rgba(191,219,254,.9);
        background:#fff;
        padding:7px 12px;
        font-size:.8rem;
        font-weight:600;
        color:#1d4ed8;
        cursor:pointer;
        transition:transform .08s ease, box-shadow .18s ease;
    }
    .lp-chip:hover{
        transform: translateY(-1px);
        box-shadow: 0 14px 34px rgba(37,99,235,.12);
    }

    .lp-note{
        margin-top:10px;
        font-size:.82rem;
        color:var(--lp-muted);
        line-height:1.4;
    }

    .lp-splits{
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .lp-split{
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

    .lp-split-left{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
        min-width: 0;
    }

    .lp-pill{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:5px 10px;
        border-radius:999px;
        background:#f8fafc;
        border:1px solid var(--lp-border);
        font-size:.78rem;
        color:var(--lp-muted);
        white-space:nowrap;
    }
    .lp-pill strong{ color:var(--lp-ink); }

    .lp-split-right{
        display:flex;
        gap:8px;
        flex-wrap:wrap;
        align-items:center;
    }

    .lp-link{
        text-decoration:none;
        border-radius:999px;
        padding:8px 12px;
        font-size:.82rem;
        font-weight:700;
        border:1px solid rgba(191,219,254,1);
        background: #eff6ff;
        color:#1d4ed8;
        transition:transform .08s ease, box-shadow .18s ease;
    }
    .lp-link:hover{
        transform: translateY(-1px);
        box-shadow: 0 14px 34px rgba(37,99,235,.14);
    }

    .lp-preview{
        background:#0b1220;
        border:1px solid #0b1220;
        border-radius: var(--lp-radius);
        overflow:hidden;
        box-shadow: 0 26px 60px rgba(2,6,23,.45);
        min-height: 620px;
        display:flex;
        flex-direction:column;
    }

    .lp-preview-h{
        padding:10px 12px;
        display:flex;
        justify-content:space-between;
        align-items:center;
        background: linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
        color: rgba(229,231,235,.92);
        font-size:.82rem;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }

    .lp-iframe{
        width:100%;
        flex:1;
        border:none;
        min-height: 620px;
        height: 72vh;
        background:#0b1220;
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
            const total = (this.maxPage - this.minPage + 1);
            const mid = Math.floor(total / 2);
            this.from = this.minPage;
            this.to   = this.minPage + mid - 1;
        },
        setSecondHalf(){
            const total = (this.maxPage - this.minPage + 1);
            const mid = Math.floor(total / 2);
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
    <div class="lp-topbar">
        <div class="lp-breadcrumb">
            <a href="{{ route('admin.licitacion-pdfs.index') }}">Gestor de PDFs</a>
            <span style="opacity:.65;">/</span>
            <span>Separar PDF</span>
        </div>

        <div class="lp-actions">
            @if(!$splits->isEmpty())
                <a href="{{ route('admin.licitacion-pdfs.propuesta', ['licitacionPdf' => $pdf->id]) }}" class="lp-btn lp-btn-cta">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>
                    </svg>
                    Propuesta económica
                </a>
            @endif

            <a href="{{ route('admin.licitacion-pdfs.index') }}" class="lp-btn lp-btn-ghost">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                Volver
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="lp-alert">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 6L9 17l-5-5"/>
            </svg>
            <div>{{ session('status') }}</div>
        </div>
    @endif

    <div class="lp-titleblock">
        <div class="lp-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <rect x="4" y="3" width="16" height="18" rx="2"/>
                <path d="M8 7h8M8 11h6M8 15h8"/>
            </svg>
        </div>
        <div style="min-width:0;">
            <div class="lp-title">{{ $pdf->original_filename ?? 'Archivo de licitación' }}</div>
            <div class="lp-sub">
                <span class="lp-badge lp-badge--ok">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Subido
                </span>
                <span class="lp-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 5h18M3 12h18M3 19h18"/>
                    </svg>
                    {{ $pageCount }} páginas
                </span>
                @if($pdf->licitacion_id)
                    <span class="lp-badge">Licitación #{{ $pdf->licitacion_id }}</span>
                @endif
                @if($pdf->requisicion_id)
                    <span class="lp-badge">Requisición #{{ $pdf->requisicion_id }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="lp-grid">
        {{-- IZQUIERDA --}}
        <div style="display:flex; flex-direction:column; gap:14px;">
            <div class="lp-card">
                <div class="lp-card-h">
                    <div class="lp-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                        Rango de páginas
                    </div>
                    <span class="lp-badge">
                        <span>Selección:</span>
                        <strong style="color:var(--lp-ink);" x-text="from"></strong>
                        <span style="opacity:.6;">–</span>
                        <strong style="color:var(--lp-ink);" x-text="to"></strong>
                    </span>
                </div>

                <div class="lp-card-b">
                    <form
                        method="POST"
                        action="{{ route('admin.licitacion-pdfs.split', ['licitacionPdf' => $pdf->id]) }}"
                        x-on:submit="clamp(); loadingSplit = true"
                    >
                        @csrf

                        <div class="lp-fields">
                            <div class="lp-field">
                                <label for="from_page">Desde</label>
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
                                <label for="to_page">Hasta</label>
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
                        </div>

                        <div class="lp-inline">
                            <div>
                                Páginas seleccionadas:
                                <strong style="color:var(--lp-ink);" x-text="(to - from + 1)"></strong>
                            </div>
                            <button type="submit" class="lp-btn lp-btn-primary" :disabled="loadingSplit">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/>
                                </svg>
                                <span x-text="loadingSplit ? 'Generando…' : 'Generar recorte'"></span>
                            </button>
                        </div>

                        <div class="lp-quick">
                            <button type="button" class="lp-chip" x-on:click="setAll()">
                                Todo
                            </button>
                            <button type="button" class="lp-chip" x-on:click="setFirstHalf()">
                                Primera mitad
                            </button>
                            <button type="button" class="lp-chip" x-on:click="setSecondHalf()">
                                Segunda mitad
                            </button>
                        </div>

                        <div class="lp-note">
                            Crea uno o varios recortes por rangos. Esos recortes se usarán después para generar la propuesta económica con IA.
                        </div>
                    </form>
                </div>
            </div>

            <div class="lp-card">
                <div class="lp-card-h">
                    <div class="lp-card-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <path d="M7 9h10M7 13h6"/>
                        </svg>
                        Recortes generados
                    </div>
                    <span class="lp-badge">{{ count($splits) }} total</span>
                </div>

                <div class="lp-card-b">
                    @if($splits->isEmpty())
                        <div style="font-size:.86rem; color:var(--lp-muted);">
                            Aún no hay recortes. Genera el primero con el rango de páginas.
                        </div>
                    @else
                        <div class="lp-splits">
                            @foreach($splits as $split)
                                <div class="lp-split">
                                    <div class="lp-split-left">
                                        <span class="lp-pill">
                                            <strong>págs</strong>&nbsp;{{ $split['from'] }}–{{ $split['to'] }}
                                        </span>
                                        <span class="lp-pill">
                                            <strong>{{ $split['page_count'] ?? ($split['to'] - $split['from'] + 1) }}</strong>&nbsp;pág.
                                        </span>
                                        <span class="lp-pill" title="Fecha de creación">
                                            {{ \Illuminate\Support\Carbon::parse($split['created_at'])->format('d/m H:i') }}
                                        </span>
                                    </div>

                                    <div class="lp-split-right">
                                        <a
                                            class="lp-link"
                                            href="{{ route('admin.licitacion-pdfs.splits.download', ['licitacionPdf' => $pdf->id, 'index' => $split['index'], 'format' => 'pdf']) }}"
                                            target="_blank"
                                        >
                                            Descargar PDF
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
                            <a href="{{ route('admin.licitacion-pdfs.propuesta', ['licitacionPdf' => $pdf->id]) }}" class="lp-btn lp-btn-cta">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>
                                </svg>
                                Crear propuesta con IA
                            </a>

                            <a href="{{ route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $pdf->id]) }}" class="lp-btn lp-btn-ghost" target="_blank">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                Abrir vista previa
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- DERECHA --}}
        <div class="lp-preview">
            <div class="lp-preview-h">
                <div style="display:flex; align-items:center; gap:10px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:.9;">
                        <path d="M3 4h18v16H3z"/><path d="M7 8h10M7 12h7M7 16h10"/>
                    </svg>
                    <div>
                        Vista previa
                        <div style="font-size:.75rem; opacity:.75;">Usa esto para ubicar rangos</div>
                    </div>
                </div>
                <div style="font-size:.78rem; opacity:.85;">{{ $pageCount }} páginas</div>
            </div>

            <iframe
                class="lp-iframe"
                src="{{ route('admin.licitacion-pdfs.preview', ['licitacionPdf' => $pdf->id]) }}"
            ></iframe>
        </div>
    </div>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection
 para ubicar en qué páginas cortar.
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
