@extends('layouts.app')

@section('title', 'Editar PDF #'.$pdf->id)

@section('content')
<style>
    :root{
        --ink:#0f172a;
        --muted:#6b7280;
        --accent:#4f46e5;
        --border:#e5e7eb;
        --radius:18px;
    }
    .page-wrapper{
        max-width: 600px;
        margin: 0 auto;
        padding: 16px;
    }
    .card{
        background:white;
        border-radius:var(--radius);
        border:1px solid rgba(226,232,240,0.9);
        padding:20px;
    }
    .title{
        font-size:18px;
        font-weight:600;
        color:var(--ink);
        margin-bottom:4px;
    }
    .subtitle{
        font-size:13px;
        color:var(--muted);
        margin-bottom:16px;
    }
    .form-label{
        font-size:12px;
        color:var(--muted);
        margin-bottom:4px;
        display:block;
        font-weight:500;
    }
    select{
        width:100%;
        border-radius:999px;
        border:1px solid var(--border);
        padding:7px 12px;
        font-size:13px;
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
    }
    .btn-link{
        font-size:12px;
        margin-left:8px;
        text-decoration:none;
        color:var(--muted);
    }
</style>

<div class="page-wrapper">
    <div class="card">
        <div class="title">Editar estatus del PDF</div>
        <div class="subtitle">
            {{ $pdf->original_filename }} · ID #{{ $pdf->id }}
        </div>

        <form method="POST" action="{{ route('admin.licitacion-pdfs.update', $pdf) }}">
            @csrf
            @method('PUT')

            <label class="form-label" for="status">Estatus</label>
            <select name="status" id="status">
                <option value="">Sin cambio</option>
                <option value="uploaded" {{ $pdf->status==='uploaded'?'selected':'' }}>Subido</option>
                <option value="processing" {{ $pdf->status==='processing'?'selected':'' }}>Procesando</option>
                <option value="items_extracted" {{ $pdf->status==='items_extracted'?'selected':'' }}>Items extraídos</option>
                <option value="proposal_ready" {{ $pdf->status==='proposal_ready'?'selected':'' }}>Propuesta lista</option>
                <option value="error" {{ $pdf->status==='error'?'selected':'' }}>Error</option>
            </select>

            <div style="margin-top:14px;">
                <button type="submit" class="btn-primary">Guardar cambios</button>
                <a href="{{ route('admin.licitacion-pdfs.show',$pdf) }}" class="btn-link">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
