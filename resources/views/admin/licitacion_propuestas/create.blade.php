@extends('layouts.app')

@section('title', 'Nueva propuesta económica')

@section('content')
<style>
    :root{
        --ink:#0f172a;
        --muted:#6b7280;
        --accent:#4f46e5;
        --border:#e5e7eb;
        --radius:18px;
        --shadow-soft:0 18px 40px rgba(15,23,42,0.06);
    }
    .page-wrapper{
        max-width: 720px;
        margin: 0 auto;
        padding: 16px;
    }
    .card{
        background:white;
        border-radius:var(--radius);
        padding:22px 22px 24px;
        border:1px solid rgba(226,232,240,0.9);
        box-shadow:var(--shadow-soft);
    }
    .title{
        font-size:20px;
        font-weight:700;
        color:var(--ink);
        margin-bottom:6px;
    }
    .subtitle{
        font-size:13px;
        color:var(--muted);
        margin-bottom:18px;
    }
    .form-row{
        margin-bottom:14px;
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
        padding:7px 12px;
        font-size:13px;
        outline:none;
    }
    .form-input:focus{
        border-color:var(--accent);
        box-shadow:0 0 0 1px rgba(79,70,229,0.18);
    }
    .btn-primary{
        border-radius:999px;
        border:none;
        padding:9px 18px;
        font-size:14px;
        font-weight:600;
        background:linear-gradient(135deg,var(--accent),#6366f1);
        color:white;
        cursor:pointer;
        display:inline-flex;
        align-items:center;
        gap:8px;
    }
    .btn-link{
        font-size:12px;
        text-decoration:none;
        color:var(--muted);
        margin-left:12px;
    }
</style>

<div class="page-wrapper">
    <div class="card">
        <div class="title">Nueva propuesta económica comparativa</div>
        <div class="subtitle">
            Indica la licitación y/o requisición para tomar los items ya extraídos por la IA.
            Se generarán los renglones y se lanzará el match automático contra tu catálogo.
        </div>

        @if ($errors->any())
            <div style="margin-bottom:14px; font-size:12px; color:#b91c1c; background:#fef2f2; border-radius:12px; padding:10px 12px;">
                <strong>Revisa los siguientes errores:</strong>
                <ul style="margin:6px 0 0 16px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.licitacion-propuestas.store') }}">
            @csrf

            <div class="form-row" style="display:flex; gap:14px;">
                <div style="flex:1;">
                    <label class="form-label" for="licitacion_id">Licitación ID</label>
                    <input type="number" name="licitacion_id" id="licitacion_id"
                           class="form-input"
                           value="{{ old('licitacion_id', $licitacionId ?? null) }}"
                           placeholder="Ej. 1024">
                </div>
                <div style="flex:1;">
                    <label class="form-label" for="requisicion_id">Requisición ID</label>
                    <input type="number" name="requisicion_id" id="requisicion_id"
                           class="form-input"
                           value="{{ old('requisicion_id', $requisicionId ?? null) }}"
                           placeholder="Opcional">
                </div>
            </div>

            <div class="form-row">
                <label class="form-label" for="titulo">Título (opcional)</label>
                <input type="text" name="titulo" id="titulo"
                       class="form-input"
                       value="{{ old('titulo') }}"
                       placeholder="Propuesta económica comparativa para la Licitación X">
            </div>

            <div class="form-row" style="display:flex; gap:14px;">
                <div style="flex:1;">
                    <label class="form-label" for="moneda">Moneda</label>
                    <input type="text" name="moneda" id="moneda"
                           class="form-input"
                           value="{{ old('moneda','MXN') }}">
                </div>
                <div style="flex:1;">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input type="date" name="fecha" id="fecha"
                           class="form-input"
                           value="{{ old('fecha', now()->toDateString()) }}">
                </div>
            </div>

            <div style="margin-top:12px;">
                <button type="submit" class="btn-primary">
                    <span>⚡ Crear propuesta y lanzar IA</span>
                </button>
                <a href="{{ route('admin.licitacion-propuestas.index') }}" class="btn-link">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
