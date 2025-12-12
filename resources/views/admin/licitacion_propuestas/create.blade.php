@extends('layouts.app')

@section('title', 'Nueva propuesta económica')

@section('content')
<style>
    .proposal-shell{
        max-width: 960px;
        margin: 0 auto;
        padding: 24px 16px 40px;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        color:#0f172a;
    }

    .proposal-card{
        background:#ffffff;
        border-radius:20px;
        border:1px solid #e5e7eb;
        padding:22px 22px 26px;
        box-shadow:0 18px 45px rgba(15,23,42,0.08);
    }

    .proposal-header{
        display:flex;
        align-items:flex-start;
        gap:14px;
        margin-bottom:18px;
    }

    .proposal-icon{
        width:42px;
        height:42px;
        border-radius:999px;
        display:flex;
        align-items:center;
        justify-content:center;
        background:#eef2ff;
        color:#4f46e5;
        font-size:20px;
        flex-shrink:0;
    }

    .proposal-title{
        font-size:20px;
        font-weight:650;
        letter-spacing:-0.02em;
        margin-bottom:4px;
    }

    .proposal-subtitle{
        font-size:13px;
        color:#6b7280;
        line-height:1.5;
    }

    .proposal-subtitle strong{
        color:#111827;
    }

    /* Bloque PDFs */
    .attached-block{
        margin-bottom:18px;
        padding:14px 16px;
        border-radius:16px;
        border:1px solid #e5e7eb;
        background:#f9fafb;
    }

    .attached-head{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:8px;
        margin-bottom:6px;
    }

    .attached-title{
        font-size:13px;
        font-weight:600;
        color:#111827;
        display:flex;
        align-items:center;
        gap:8px;
    }

    .attached-title span.emoji{
        font-size:16px;
    }

    .attached-count{
        font-size:11px;
        padding:4px 10px;
        border-radius:999px;
        background:#eef2ff;
        color:#4f46e5;
    }

    .attached-sub{
        font-size:11px;
        color:#6b7280;
        margin-bottom:8px;
    }

    .attached-list{
        list-style:none;
        margin:0;
        padding:0;
        display:flex;
        flex-direction:column;
        gap:6px;
        max-height:220px;
        overflow:auto;
    }

    .attached-item{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        font-size:11px;
        padding:7px 10px;
        border-radius:999px;
        background:#ffffff;
        border:1px solid #e5e7eb;
    }

    .attached-left{
        display:flex;
        align-items:center;
        gap:8px;
    }

    .radio-wrap{
        display:flex;
        align-items:center;
        justify-content:center;
    }

    .attached-meta{
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        align-items:center;
    }

    .badge{
        padding:2px 8px;
        border-radius:999px;
        font-size:11px;
        background:#f3f4f6;
        color:#374151;
    }

    .badge-pages{
        background:#ecfdf5;
        color:#15803d;
    }

    .badge-time{
        color:#6b7280;
    }

    .badge-req{
        background:#eef2ff;
        color:#4f46e5;
    }

    .attached-link{
        font-size:11px;
        text-decoration:none;
        color:#4f46e5;
        font-weight:500;
        white-space:nowrap;
    }

    /* Formulario */
    .form-section{
        margin-top:18px;
        padding-top:14px;
        border-top:1px dashed #e5e7eb;
    }

    .form-row{
        margin-bottom:14px;
    }

    .form-row-inline{
        display:flex;
        gap:12px;
    }

    .form-row-inline > div{
        flex:1;
        min-width:0;
    }

    .form-label{
        font-size:12px;
        color:#6b7280;
        font-weight:500;
        margin-bottom:4px;
        display:block;
    }

    .form-input{
        width:100%;
        border-radius:10px;
        border:1px solid #e5e7eb;
        padding:8px 11px;
        font-size:13px;
        outline:none;
        background:#ffffff;
        transition:border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }

    .form-input:focus{
        border-color:#4f46e5;
        box-shadow:0 0 0 1px rgba(79,70,229,0.15);
        background:#f9fafb;
    }

    .form-hint{
        font-size:11px;
        color:#9ca3af;
        margin-top:3px;
    }

    /* Botones */
    .actions{
        margin-top:8px;
        display:flex;
        align-items:center;
        gap:12px;
        flex-wrap:wrap;
    }

    .btn-primary{
        border-radius:999px;
        border:none;
        padding:9px 22px;
        font-size:13px;
        font-weight:600;
        background:#4f46e5;
        color:white;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:6px;
        transition:background .15s ease, box-shadow .15s ease, transform .08s ease;
    }

    .btn-primary:hover{
        background:#4338ca;
        box-shadow:0 14px 32px rgba(79,70,229,0.35);
        transform:translateY(-1px);
    }

    .btn-secondary-soft{
        border-radius:999px;
        border:1px dashed #d1d5db;
        padding:7px 16px;
        font-size:11px;
        font-weight:500;
        background:#f9fafb;
        color:#4b5563;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }

    .btn-link{
        font-size:12px;
        text-decoration:none;
        color:#6b7280;
    }

    .status-alert{
        margin-bottom:12px;
        font-size:12px;
        color:#b91c1c;
        background:#fef2f2;
        border-radius:10px;
        padding:8px 10px;
        border:1px solid #fecaca;
    }
</style>

<div class="proposal-shell">
    <div class="proposal-card">
        <div class="proposal-header">
            <div class="proposal-icon">⚡</div>
            <div>
                <div class="proposal-title">Nueva propuesta económica comparativa</div>
                <div class="proposal-subtitle">
                    La IA analizará <strong>cada PDF recortado</strong> de esta licitación,
                    generará cotizaciones individuales por requisición y al final podrás
                    hacer un <strong>merge global</strong> con todos los renglones.
                    <br>
                    Primero elige con qué PDF quieres comenzar.
                </div>
            </div>
        </div>

        {{-- Bloque de PDFs recortados anexados a esta propuesta --}}
        @if(isset($licitacionPdf) && $licitacionPdf && !empty($pdfSplits))
            <div class="attached-block">
                <div class="attached-head">
                    <div class="attached-title">
                        <span class="emoji">📎</span>
                        <span>PDFs recortados incluidos en esta propuesta</span>
                    </div>
                    <div class="attached-count">
                        {{ count($pdfSplits) }} archivos
                    </div>
                </div>

                <div class="attached-sub">
                    Marca con el radio cuál será la <strong>primera requisición</strong> que
                    quieres procesar con la IA. Después, desde el detalle de la propuesta,
                    podrás avanzar al siguiente PDF y finalmente lanzar el merge global.
                </div>

                <ul class="attached-list">
                    @foreach($pdfSplits as $i => $split)
                        @php
                            $from       = $split['from'] ?? ($split['start'] ?? null);
                            $to         = $split['to']   ?? ($split['end']   ?? null);
                            $pageCount  = $split['page_count'] ?? (($from && $to) ? ($to - $from + 1) : null);
                            $createdAt  = $split['created_at'] ?? null;
                            $splitIndex = $split['index'] ?? $i;
                        @endphp
                        <li class="attached-item">
                            <div class="attached-left">
                                <div class="radio-wrap">
                                    <input
                                        type="radio"
                                        name="start_split_index_display"
                                        value="{{ $splitIndex }}"
                                        {{ $loop->first ? 'checked' : '' }}
                                        onclick="document.getElementById('start_split_index').value='{{ $splitIndex }}';"
                                    >
                                </div>
                                <div class="attached-meta">
                                    <span class="badge badge-req">
                                        Req. {{ $loop->iteration }}
                                    </span>

                                    @if($from && $to)
                                        <span class="badge">
                                            págs {{ $from }}–{{ $to }}
                                        </span>
                                    @endif

                                    @if($pageCount)
                                        <span class="badge badge-pages">
                                            {{ $pageCount }} pág.
                                        </span>
                                    @endif

                                    @if($createdAt)
                                        <span class="badge badge-time">
                                            {{ \Illuminate\Support\Carbon::parse($createdAt)->format('d/m H:i') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <a
                                href="{{ route('admin.licitacion-pdfs.splits.download', [
                                    'licitacionPdf' => $licitacionPdf->id,
                                    'index'         => $splitIndex,
                                    'format'        => 'pdf',
                                ]) }}"
                                target="_blank"
                                class="attached-link"
                            >
                                Ver PDF
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @elseif(isset($licitacionPdf) && $licitacionPdf)
            <div class="attached-block">
                <div class="attached-head">
                    <div class="attached-title">
                        <span class="emoji">📎</span>
                        <span>PDF de licitación asociado</span>
                    </div>
                </div>
                <div class="attached-sub">
                    No se encontraron recortes almacenados, pero la propuesta quedará ligada a este archivo.
                </div>
                <div style="font-size:11px; color:#4b5563; margin-top:4px;">
                    {{ $licitacionPdf->original_filename ?? 'Archivo de licitación #' . $licitacionPdf->id }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="status-alert">
                <strong>Revisa los siguientes puntos:</strong>
                <ul style="margin:6px 0 0 16px; padding:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.licitacion-propuestas.store') }}">
            @csrf

            {{-- Guardamos el ID del PDF de licitación del que vienen todos los recortes --}}
            @if(isset($licitacionPdfId) && $licitacionPdfId)
                <input type="hidden" name="licitacion_pdf_id" value="{{ $licitacionPdfId }}">
            @endif

            {{-- Split inicial seleccionado (se actualiza al cambiar el radio) --}}
            <input
                type="hidden"
                id="start_split_index"
                name="start_split_index"
                value="{{ isset($pdfSplits[0]) ? ($pdfSplits[0]['index'] ?? 0) : 0 }}"
            >

            <div class="form-section">
                <div class="form-row">
                    <label class="form-label" for="titulo">Título (opcional)</label>
                    <input
                        type="text"
                        name="titulo"
                        id="titulo"
                        class="form-input"
                        value="{{ old('titulo') }}"
                        placeholder="Propuesta económica Licitación X – PDFs recortados"
                    >
                    <p class="form-hint">
                        Algo corto y reconocible para esta propuesta en tu historial.
                    </p>
                </div>

                <div class="form-row form-row-inline">
                    <div>
                        <label class="form-label" for="moneda">Moneda</label>
                        <input
                            type="text"
                            name="moneda"
                            id="moneda"
                            class="form-input"
                            value="{{ old('moneda','MXN') }}"
                        >
                    </div>
                    <div>
                        <label class="form-label" for="fecha">Fecha</label>
                        <input
                            type="date"
                            name="fecha"
                            id="fecha"
                            class="form-input"
                            value="{{ old('fecha', now()->toDateString()) }}"
                        >
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn-primary">
                        Iniciar propuesta con IA desde el PDF seleccionado
                    </button>

                    <span class="btn-secondary-soft">
                        El merge global se hará en el detalle,
                        cuando todos los PDFs estén procesados.
                    </span>

                    <a href="{{ route('admin.licitacion-propuestas.index') }}" class="btn-link">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
