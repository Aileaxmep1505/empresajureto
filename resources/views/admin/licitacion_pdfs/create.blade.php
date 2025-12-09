@extends('layouts.app')

@section('title', 'Subir PDF de requisición')

@section('content')
<style>
    :root{
        --ink:#0f172a;
        --muted:#6b7280;
        --accent:#4f46e5;
        --accent-soft:#eef2ff;
        --accent-soft-2:#e0f2fe;
        --border:#e5e7eb;
        --radius:18px;
        --shadow-soft:0 18px 40px rgba(15,23,42,0.06);
        --danger:#b91c1c;
        --danger-soft:#fef2f2;
    }
    .page-wrapper{
        max-width: 760px;
        margin: 0 auto;
        padding: 18px 16px 26px;
    }
    .breadcrumb{
        font-size: 12px;
        color: var(--muted);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .breadcrumb a{
        color: inherit;
        text-decoration: none;
    }
    .breadcrumb a:hover{
        text-decoration: underline;
    }
    .card{
        background:white;
        border-radius:var(--radius);
        padding:22px 22px 24px;
        border:1px solid rgba(226,232,240,0.9);
        box-shadow:var(--shadow-soft);
        position: relative;
        overflow: hidden;
    }
    .card::before{
        content:"";
        position:absolute;
        inset:-120px;
        background:radial-gradient(circle at 0 0, rgba(129,140,248,.12), transparent 55%);
        opacity:.7;
        pointer-events:none;
    }
    .card-inner{
        position:relative;
        z-index:1;
    }
    .page-heading{
        display:flex;
        align-items:flex-start;
        gap:10px;
        margin-bottom:12px;
    }
    .page-icon{
        width:38px;
        height:38px;
        border-radius:14px;
        display:grid;
        place-items:center;
        background:radial-gradient(circle at 0 0,#a5b4fc,#4f46e5);
        color:white;
        box-shadow:0 12px 30px rgba(79,70,229,.4);
        flex-shrink:0;
    }
    .page-title{
        font-size:20px;
        font-weight:700;
        color:var(--ink);
        margin-bottom:4px;
    }
    .page-subtitle{
        font-size:13px;
        color:var(--muted);
    }
    .tag-row{
        display:flex;
        flex-wrap:wrap;
        gap:6px;
        margin-top:8px;
        font-size:11px;
    }
    .tag-chip{
        padding:4px 8px;
        border-radius:999px;
        border:1px solid var(--border);
        background:rgba(249,250,251,0.9);
        color:var(--muted);
    }
    .tag-chip-strong{
        border-color:rgba(129,140,248,0.7);
        background:rgba(238,242,255,0.9);
        color:#1d4ed8;
        font-weight:500;
    }

    .form-row{
        margin-bottom:16px;
    }
    .form-row-inline{
        display:flex;
        gap:14px;
        margin-bottom:16px;
    }
    .form-row-inline > div{
        flex:1;
        min-width:0;
    }
    .form-label{
        font-size:12px;
        color:var(--muted);
        font-weight:500;
        margin-bottom:4px;
        display:block;
    }
    .form-input{
        width:100%;
        border-radius:999px;
        border:1px solid var(--border);
        padding:8px 12px;
        font-size:13px;
        outline:none;
        background:white;
        transition:border-color .16s ease, box-shadow .16s ease, transform .06s ease;
    }
    .form-input:focus{
        border-color:var(--accent);
        box-shadow:0 0 0 1px rgba(79,70,229,0.18);
        transform:translateY(-1px);
    }

    .dropzone{
        border-radius:var(--radius);
        border:1.5px dashed var(--border);
        padding:24px 18px;
        text-align:center;
        background:linear-gradient(135deg,#f9fafb,#eff6ff);
        transition:border-color 0.18s ease, background 0.18s ease, transform 0.16s ease, box-shadow .16s ease;
        cursor:pointer;
    }
    .dropzone:hover{
        border-color:var(--accent);
        background:linear-gradient(135deg,#eff6ff,#e0f2fe);
        transform:translateY(-1px);
        box-shadow:0 14px 30px rgba(15,23,42,0.08);
    }
    .dropzone-icon{
        width:40px;
        height:40px;
        border-radius:999px;
        background:var(--accent-soft);
        display:flex;
        align-items:center;
        justify-content:center;
        margin:0 auto 8px;
        font-size:20px;
    }
    .dropzone-title{
        font-size:13px;
        font-weight:600;
        color:var(--ink);
        margin-bottom:4px;
    }
    .dropzone-text{
        font-size:12px;
        color:var(--muted);
        margin-bottom:4px;
    }
    .helper-text{
        font-size:11px;
        color:var(--muted);
    }

    .tips-box{
        margin-top:16px;
        border-radius:14px;
        border:1px dashed rgba(148,163,184,0.8);
        background:linear-gradient(135deg,#f9fafb,#eef2ff);
        padding:10px 12px;
        font-size:11.5px;
        color:var(--muted);
    }
    .tips-title{
        font-weight:600;
        font-size:11.5px;
        color:var(--ink);
        margin-bottom:4px;
        display:flex;
        align-items:center;
        gap:6px;
    }
    .tips-title-icon{
        width:18px;
        height:18px;
        border-radius:999px;
        background:white;
        display:grid;
        place-items:center;
        font-size:11px;
        border:1px solid rgba(191,219,254,0.9);
    }
    .tips-list{
        margin:0;
        padding-left:16px;
    }
    .tips-list li{
        margin-bottom:2px;
    }

    .alert-errors{
        margin-bottom:14px;
        font-size:12px;
        color:var(--danger);
        background:var(--danger-soft);
        border-radius:12px;
        padding:10px 12px;
        border:1px solid #fecaca;
    }

    .actions-row{
        display:flex;
        align-items:center;
        justify-content:flex-start;
        gap:10px;
        margin-top:16px;
        flex-wrap:wrap;
    }
    .btn-primary{
        border-radius:999px;
        border:none;
        padding:9px 18px;
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
    .btn-primary:active{
        transform:translateY(0);
        box-shadow:0 10px 24px rgba(79,70,229,0.2);
    }
    .btn-secondary-link{
        font-size:12px;
        color:var(--muted);
        text-decoration:none;
        display:inline-flex;
        align-items:center;
        gap:6px;
    }
    .btn-secondary-link svg{
        opacity:.9;
    }
    .btn-secondary-link:hover{
        text-decoration:underline;
    }
</style>

<div class="page-wrapper">
    {{-- Migas --}}
    <div class="breadcrumb">
        <a href="{{ route('admin.licitacion-pdfs.index') }}">Gestor de PDFs</a>
        <span>/</span>
        <span>Subir PDF de requisición</span>
    </div>

    <div class="card">
        <div class="card-inner">
            {{-- Encabezado --}}
            <div class="page-heading">
                <div class="page-icon">
                    <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="1.8">
                        <rect x="5" y="3" width="14" height="18" rx="2" stroke="white" opacity=".9"/>
                        <path d="M9 7h6M9 11h4M9 15h6" stroke="white"/>
                    </svg>
                </div>
                <div>
                    <div class="page-title">Subir PDF de requisición</div>
                    <p class="page-subtitle">
                        Sube el archivo PDF que contiene los renglones de la licitación. Después podrás
                        <strong>separar rangos de páginas</strong> (por ejemplo, solo la partida que te interesa)
                        desde el gestor de PDFs.
                    </p>

                    <div class="tag-row">
                        <span class="tag-chip tag-chip-strong">Paso 1 · Subir PDF</span>
                        <span class="tag-chip">Paso 2 · Ver páginas</span>
                        <span class="tag-chip">Paso 3 · Separar por rangos</span>
                    </div>
                </div>
            </div>

            {{-- Errores --}}
            @if ($errors->any())
                <div class="alert-errors">
                    <strong>Revisa los siguientes errores:</strong>
                    <ul style="margin:6px 0 0 16px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Formulario --}}
            <form method="POST"
                  action="{{ route('admin.licitacion-pdfs.store') }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="form-row-inline">
                    <div>
                        <label class="form-label" for="licitacion_id">Licitación ID (opcional)</label>
                        <input type="number"
                               name="licitacion_id"
                               id="licitacion_id"
                               class="form-input"
                               value="{{ old('licitacion_id') }}"
                               placeholder="Ej. 1024">
                    </div>
                    <div>
                        <label class="form-label" for="requisicion_id">Requisición ID (opcional)</label>
                        <input type="number"
                               name="requisicion_id"
                               id="requisicion_id"
                               class="form-input"
                               value="{{ old('requisicion_id') }}"
                               placeholder="Ej. 3">
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-label">Archivo PDF</label>
                    <div class="dropzone" id="pdf-dropzone">
                        {{-- input real oculto --}}
                        <input
                            id="pdf-input"
                            type="file"
                            name="pdf"
                            accept="application/pdf"
                            style="display:none"
                        >
                        <div class="dropzone-icon">📄</div>
                        <div class="dropzone-title">Arrastra tu PDF aquí o haz clic para seleccionarlo</div>
                        <div class="dropzone-text" id="pdf-filename">
                            Ningún archivo seleccionado
                        </div>
                        <div class="helper-text">
                            Máx. 30 MB · Solo archivos .pdf · Lo ideal es 1 requisición por archivo para que
                            los rangos de páginas sean más claros.
                        </div>
                    </div>
                </div>

                <div class="tips-box">
                    <div class="tips-title">
                        <div class="tips-title-icon">i</div>
                        Recomendaciones para separar mejor después
                    </div>
                    <ul class="tips-list">
                        <li>Si el documento trae varias requisiciones, considera subir un PDF por cada una.</li>
                        <li>Evita escaneos borrosos o inclinados para que las páginas se detecten bien.</li>
                        <li>Una vez subido, ve al gestor de PDFs para revisar las páginas y separar por rangos.</li>
                    </ul>
                </div>

                <div class="actions-row">
                    <button type="submit" class="btn-primary">
                        <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" fill="none" stroke-width="1.8">
                            <path d="M12 3v14"/><path d="M5 10l7 7 7-7"/><rect x="4" y="19" width="16" height="2" rx="1"/>
                        </svg>
                        <span>Subir PDF</span>
                    </button>

                    <a href="{{ route('admin.licitacion-pdfs.index') }}" class="btn-secondary-link">
                        <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" fill="none" stroke-width="1.8">
                            <path d="M15 18l-6-6 6-6"/><path d="M9 12h12"/>
                        </svg>
                        Cancelar y volver al listado
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input   = document.getElementById('pdf-input');
    const label   = document.getElementById('pdf-filename');
    const zone    = document.getElementById('pdf-dropzone');

    if (!input || !label || !zone) return;

    // Click en toda la tarjeta = abrir selector
    zone.addEventListener('click', function () {
        input.click();
    });

    // Cambia el texto cuando se selecciona un archivo
    input.addEventListener('change', function () {
        if (!this.files || this.files.length === 0) {
            label.textContent = 'Ningún archivo seleccionado';
            return;
        }

        const file = this.files[0];
        const sizeMb = file.size ? (file.size / (1024 * 1024)) : 0;
        const sizeLabel = sizeMb ? ' · ' + sizeMb.toFixed(1) + ' MB' : '';

        label.textContent = file.name + sizeLabel;
    });
});
</script>
@endsection
