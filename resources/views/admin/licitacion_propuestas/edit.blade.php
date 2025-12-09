@extends('layouts.app')

@section('title', 'Editar '.$propuesta->codigo)

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
        border:1px solid rgba(226,232,240,0.9);
        padding:20px 22px 24px;
        box-shadow:var(--shadow-soft);
    }
    .title{
        font-size:20px;
        font-weight:700;
        color:var(--ink);
        margin-bottom:4px;
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
    .form-input,
    .form-select{
        width:100%;
        border-radius:999px;
        border:1px solid var(--border);
        padding:7px 12px;
        font-size:13px;
        outline:none;
    }
    .form-input:focus,
    .form-select:focus{
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
    }
    .btn-link{
        font-size:12px;
        text-decoration:none;
        color:var(--muted);
        margin-left:10px;
    }
</style>

<div class="page-wrapper">
    <div class="card">
        <div class="title">Editar propuesta</div>
        <div class="subtitle">
            {{ $propuesta->codigo }} &mdash; {{ $propuesta->titulo }}
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

        <form method="POST" action="{{ route('admin.licitacion-propuestas.update', $propuesta) }}">
            @csrf
            @method('PUT')

            <div class="form-row">
                <label class="form-label" for="titulo">Título</label>
                <input type="text" id="titulo" name="titulo"
                       class="form-input"
                       value="{{ old('titulo',$propuesta->titulo) }}">
            </div>

            <div class="form-row" style="display:flex; gap:14px;">
                <div style="flex:1;">
                    <label class="form-label" for="moneda">Moneda</label>
                    <input type="text" id="moneda" name="moneda"
                           class="form-input"
                           value="{{ old('moneda',$propuesta->moneda) }}">
                </div>
                <div style="flex:1;">
                    <label class="form-label" for="fecha">Fecha</label>
                    <input type="date" id="fecha" name="fecha"
                           class="form-input"
                           value="{{ old('fecha',$propuesta->fecha?->toDateString()) }}">
                </div>
            </div>

            <div class="form-row">
                <label class="form-label" for="status">Estatus</label>
                <select id="status" name="status" class="form-select">
                    @php
                        $options = [
                            'draft' => 'Borrador',
                            'revisar' => 'En revisión',
                            'enviada' => 'Enviada',
                            'adjudicada' => 'Adjudicada',
                            'no_adjudicada' => 'No adjudicada',
                        ];
                    @endphp
                    @foreach($options as $value=>$label)
                        <option value="{{ $value }}" {{ $propuesta->status===$value?'selected':'' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-top:12px;">
                <button type="submit" class="btn-primary">
                    Guardar cambios
                </button>
                <a href="{{ route('admin.licitacion-propuestas.show',$propuesta) }}" class="btn-link">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
