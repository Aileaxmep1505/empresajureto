@extends('layouts.app')
@section('title','Checklist de compras')

@section('content')
<style>
:root{
  --mint:#48cfad;
  --mint-dark:#34c29e;
  --ink:#111827;
  --muted:#6b7280;
  --line:#e6eef6;
  --card:#ffffff;
  --danger:#ef4444;
  --success:#16a34a;
  --shadow:0 12px 34px rgba(12,18,30,0.06);
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#f3f5f7;color:var(--ink);margin:0;padding:0}

/* Layout */
.wizard-wrap-wide{max-width:1040px;margin:56px auto;padding:18px;}
.header-row{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;}
.header-step{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:700;margin:0;}
.header-title{margin:4px 0 0;font-weight:700;font-size:22px;letter-spacing:-0.02em;}
.header-sub{margin:4px 0 0;color:var(--muted);font-size:13px;max-width:520px;}
.header-link{font-size:12px;color:var(--muted);text-decoration:none;padding:6px 10px;border-radius:999px;border:1px solid var(--line);background:#fff;}
.header-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Alerts */
.alert-success{
  border-radius:12px;
  background:#ecfdf3;
  border:1px solid #bbf7d0;
  padding:10px 12px;
  font-size:13px;
  color:#166534;
  margin-bottom:12px;
}
.alert-error{
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
  margin-bottom:12px;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Grid cards */
.grid-3{
  display:grid;
  grid-template-columns:minmax(0,2.1fr) minmax(0,1fr);
  gap:20px;
}
@media(max-width:860px){
  .grid-3{grid-template-columns:1fr;}
}

.card{
  background:var(--card);
  border-radius:16px;
  border:1px solid var(--line);
  box-shadow:var(--shadow);
  padding:18px 18px 16px 18px;
}
.card h2{
  margin:0 0 12px 0;
  font-size:14px;
  font-weight:600;
  color:var(--ink);
}

/* Left side: items list */
.items-container{
  max-height:420px;
  overflow-y:auto;
  padding-right:4px;
}
.item-form{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
  padding:10px 10px;
  border-radius:12px;
  border:1px solid #edf1f7;
  margin-bottom:8px;
  background:#fff;
  transition:background-color .15s,border-color .15s,box-shadow .15s;
}
.item-form:hover{
  background:#f9fafb;
  border-color:#e0e7f0;
  box-shadow:0 10px 24px rgba(15,23,42,0.03);
}

.item-main{
  display:flex;
  gap:10px;
}
.checkbox-wrap{
  padding-top:4px;
}
.checkbox-wrap input[type="checkbox"]{
  width:16px;
  height:16px;
  border-radius:4px;
  border:1px solid #d1d5db;
  cursor:pointer;
}
.item-body{
  font-size:13px;
}
.item-title{
  font-weight:600;
  color:var(--ink);
  margin-bottom:4px;
}
.item-meta-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:8px;
  font-size:11px;
  color:var(--muted);
  margin-bottom:4px;
}
.meta-group label{
  display:block;
  margin-bottom:2px;
}
.meta-input{
  width:100%;
  border-radius:8px;
  border:1px solid #e5e7eb;
  padding:5px 7px;
  font-size:11px;
  outline:none;
  font-family:inherit;
}
.meta-input:focus{
  border-color:#c7d2fe;
  box-shadow:0 0 0 1px rgba(79,70,229,0.16);
}
.item-notes{
  margin-top:2px;
}
.item-notes label{
  display:block;
  font-size:11px;
  color:var(--muted);
  margin-bottom:2px;
}
.notes-textarea{
  width:100%;
  border-radius:8px;
  border:1px solid #e5e7eb;
  padding:5px 7px;
  font-size:11px;
  outline:none;
  resize:vertical;
  min-height:40px;
  max-height:120px;
  font-family:inherit;
}
.notes-textarea:focus{
  border-color:#c7d2fe;
  box-shadow:0 0 0 1px rgba(79,70,229,0.16);
}

.item-actions{
  padding-top:4px;
}
.item-actions button{
  border:0;
  background:transparent;
  font-size:11px;
  font-weight:600;
  color:#4f46e5;
  cursor:pointer;
  padding:4px 0;
}
.item-actions button:hover{
  color:#3730a3;
}

/* Right side: add item card */
.form-stack{display:flex;flex-direction:column;gap:12px;}
.field-label{
  display:block;
  font-size:13px;
  font-weight:500;
  color:var(--ink);
  margin-bottom:4px;
}
.field-input,
.field-textarea{
  width:100%;
  border-radius:10px;
  border:1px solid #e5e7eb;
  padding:8px 9px;
  font-size:13px;
  outline:none;
  font-family:inherit;
}
.field-textarea{
  resize:vertical;
  min-height:60px;
}
.field-input:focus,
.field-textarea:focus{
  border-color:#c7d2fe;
  box-shadow:0 0 0 1px rgba(79,70,229,0.16);
}
.btn-primary{
  width:100%;
  border-radius:999px;
  border:0;
  padding:9px 14px;
  font-size:13px;
  font-weight:700;
  background:var(--mint);
  color:#fff;
  cursor:pointer;
  box-shadow:0 10px 24px rgba(72,207,173,0.18);
}
.btn-primary:hover{
  background:var(--mint-dark);
}
.btn-secondary{
  width:100%;
  border-radius:10px;
  border:1px solid #e5e7eb;
  padding:9px 14px;
  font-size:13px;
  font-weight:500;
  background:#fff;
  color:var(--ink);
  cursor:pointer;
}
.btn-secondary:hover{
  background:#f9fafb;
}

.divider-top{
  border-top:1px solid #edf1f7;
  margin-top:16px;
  padding-top:14px;
}
.next-step-label{
  font-size:12px;
  text-align:center;
  margin-bottom:6px;
  color:var(--muted);
}
</style>

<div class="wizard-wrap-wide">
    <div class="header-row">
        <div>
            <p class="header-step">Paso 10 de 12</p>
            <h1 class="header-title">Checklist de compras</h1>
            <p class="header-sub">
                Registra y marca los elementos relacionados con compras y entregas para esta licitación.
            </p>
        </div>
        <a href="{{ route('licitaciones.show', $licitacion) }}" class="header-link">
            Ver resumen de licitación
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid-3">
        {{-- Columna izquierda: items --}}
        <div>
            <div class="card">
                <h2>Items de compras</h2>

                <div class="items-container">
                    @forelse($items as $item)
                        <form
                            action="{{ route('licitaciones.checklist.compras.update', [$licitacion, $item]) }}"
                            method="POST"
                            class="item-form"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="item-main">
                                <div class="checkbox-wrap">
                                    <input
                                        type="checkbox"
                                        name="completado"
                                        value="1"
                                        {{ $item->completado ? 'checked' : '' }}
                                    >
                                </div>

                                <div class="item-body">
                                    <div class="item-title">
                                        {{ $item->descripcion_item }}
                                    </div>

                                    <div class="item-meta-grid">
                                        <div class="meta-group">
                                            <label>Fecha entregado</label>
                                            <input
                                                type="date"
                                                name="fecha_entregado"
                                                value="{{ optional($item->fecha_entregado)->format('Y-m-d') }}"
                                                class="meta-input"
                                            >
                                        </div>
                                        <div class="meta-group">
                                            <label>Entregado por</label>
                                            <input
                                                type="text"
                                                name="entregado_por"
                                                value="{{ $item->entregado_por }}"
                                                class="meta-input"
                                            >
                                        </div>
                                    </div>

                                    <div class="item-notes">
                                        <label>Observaciones</label>
                                        <textarea
                                            name="observaciones"
                                            rows="1"
                                            class="notes-textarea"
                                        >{{ $item->observaciones }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="item-actions">
                                <button type="submit">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    @empty
                        <p style="font-size:13px;color:var(--muted);margin-top:4px;">
                            Aún no hay elementos en el checklist. Agrega uno en el panel derecho.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Columna derecha: agregar ítem --}}
        <div>
            <div class="card">
                <h2>Agregar ítem</h2>

                <form
                    action="{{ route('licitaciones.checklist.compras.store', $licitacion) }}"
                    method="POST"
                    class="form-stack"
                >
                    @csrf

                    <div>
                        <label class="field-label">Descripción</label>
                        <input
                            type="text"
                            name="descripcion_item"
                            class="field-input"
                            placeholder="Ej. Orden de compra emitida"
                        >
                    </div>

                    <div>
                        <label class="field-label">Fecha entregado (opcional)</label>
                        <input
                            type="date"
                            name="fecha_entregado"
                            class="field-input"
                        >
                    </div>

                    <div>
                        <label class="field-label">Entregado por (opcional)</label>
                        <input
                            type="text"
                            name="entregado_por"
                            class="field-input"
                        >
                    </div>

                    <div>
                        <label class="field-label">Observaciones</label>
                        <textarea
                            name="observaciones"
                            rows="2"
                            class="field-textarea"
                        ></textarea>
                    </div>

                    <button type="submit" class="btn-primary">
                        Agregar ítem
                    </button>
                </form>

                <div class="divider-top">
                    <p class="next-step-label">
                        Siguiente paso
                    </p>
                    <a
                        href="{{ route('licitaciones.checklist.facturacion.edit', $licitacion) }}"
                        class="btn-secondary"
                    >
                        Ir al paso 11: Facturación
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
