@extends('layouts.app')
@section('title','Contabilidad de la licitación')

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

/* Wrapper */
.wizard-wrap{max-width:720px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:16px;box-shadow:var(--shadow);overflow:hidden;}
.panel-head{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.hgroup h2{margin:0;font-weight:700;font-size:20px;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;max-width:520px;}
.step-tag{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:700;margin-bottom:4px;}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:999px;border:1px solid var(--line);background:#fff;font-size:12px;}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Form container */
.form{padding:20px;}
.grid{display:grid;grid-template-columns:1fr;gap:18px;}
.grid-3{grid-template-columns:repeat(3,minmax(0,1fr));}
@media(max-width:800px){ .grid-3{grid-template-columns:1fr;} }

/* Alerts */
.alert-success{
  border-radius:12px;
  background:#ecfdf3;
  border:1px solid #bbf7d0;
  padding:10px 12px;
  font-size:13px;
  color:#166534;
  margin:16px 20px 0 20px;
}
.alert-error{
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
  margin:16px 20px 0 20px;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Inputs */
.field-label{
  display:block;
  font-size:13px;
  font-weight:500;
  color:var(--ink);
  margin-bottom:4px;
}
.field-input,
.field-textarea,
.field-readonly{
  width:100%;
  border-radius:10px;
  border:1px solid #e5e7eb;
  padding:8px 10px;
  font-size:13px;
  outline:none;
  font-family:inherit;
}
.field-input:focus,
.field-textarea:focus{
  border-color:#c7d2fe;
  box-shadow:0 0 0 1px rgba(79,70,229,0.16);
}
.field-readonly{
  background:#f9fafb;
  color:var(--muted);
}
.field-textarea{
  resize:vertical;
  min-height:70px;
}
.field-hint{
  font-size:11px;
  color:var(--muted);
  margin-bottom:4px;
}

/* Actions */
.actions-line{
  margin-top:18px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
}
.actions-right{
  display:flex;
  gap:12px;
  align-items:center;
}
.link-back{
  font-size:12px;
  color:var(--muted);
  text-decoration:none;
}
.link-back:hover{color:var(--ink);text-decoration:underline;}
.btn{
  border:0;
  border-radius:10px;
  padding:9px 15px;
  font-weight:700;
  cursor:pointer;
  font-size:13px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  white-space:nowrap;
  font-family:inherit;
}
.btn-primary{
  background:var(--mint);
  color:#fff;
  box-shadow:0 8px 20px rgba(52,194,158,0.12);
}
.btn-primary:hover{background:var(--mint-dark);}
.btn-ghost{
  background:#fff;
  border:1px solid var(--line);
  color:var(--ink);
}
.btn-ghost:hover{border-color:#dbe7ef;}

@media(max-width:540px){
  .actions-line{flex-direction:column;align-items:flex-start;}
  .actions-right{width:100%;justify-content:flex-end;}
}
</style>

@php
    $cont = $contabilidad ?? null;
@endphp

<div class="wizard-wrap">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 12 de 12</div>
                <h2>Contabilidad de la licitación</h2>
                <p>Registra los montos de inversión, costos y notas contables para cerrar el ciclo de la licitación.</p>
            </div>

            <a href="{{ route('licitaciones.checklist.facturacion.edit', $licitacion) }}" class="back-link" title="Volver al paso anterior">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Paso anterior
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

        <form class="form" action="{{ route('licitaciones.contabilidad.store', $licitacion) }}" method="POST" novalidate>
            @csrf

            {{-- Montos principales --}}
            <div class="grid grid-3">
                <div>
                    <label class="field-label" for="monto_inversion_estimado">Inversión estimada</label>
                    <input
                        id="monto_inversion_estimado"
                        type="number"
                        step="0.01"
                        name="monto_inversion_estimado"
                        value="{{ old('monto_inversion_estimado', $cont->monto_inversion_estimado ?? null) }}"
                        class="field-input"
                        placeholder="0.00"
                    >
                </div>
                <div>
                    <label class="field-label" for="costo_total">Costo total</label>
                    <input
                        id="costo_total"
                        type="number"
                        step="0.01"
                        name="costo_total"
                        value="{{ old('costo_total', $cont->costo_total ?? null) }}"
                        class="field-input"
                        placeholder="0.00"
                    >
                </div>
                <div>
                    <label class="field-label">Utilidad estimada</label>
                    <input
                        type="text"
                        disabled
                        class="field-readonly"
                        value="@if(isset($cont->utilidad_estimada)) ${{ number_format($cont->utilidad_estimada, 2) }} @else Se calculará automáticamente @endif"
                    >
                </div>
            </div>

            {{-- Detalle de costos --}}
            <div>
                <label class="field-label" for="detalle_costos_texto">Detalle de costos (opcional)</label>
                <p class="field-hint">
                    Puedes describir los costos por producto/servicio. Ejemplo corto en texto libre.
                </p>
                <textarea
                    id="detalle_costos_texto"
                    name="detalle_costos[texto]"
                    rows="3"
                    class="field-textarea"
                    placeholder="Ej. Producto A: 10,000; Producto B: 5,000"
                >{{ old('detalle_costos.texto', $cont->detalle_costos['texto'] ?? '') }}</textarea>
            </div>

            {{-- Notas contables --}}
            <div>
                <label class="field-label" for="notas">Notas contables</label>
                <textarea
                    id="notas"
                    name="notas"
                    rows="3"
                    class="field-textarea"
                    placeholder="Observaciones adicionales, condiciones de pago, retenciones, etc."
                >{{ old('notas', $cont->notas ?? '') }}</textarea>
            </div>

            {{-- Acciones --}}
            <div class="actions-line">
                <a href="{{ route('licitaciones.checklist.facturacion.edit', $licitacion) }}" class="link-back">
                    ← Volver al paso anterior
                </a>
                <div class="actions-right">
                    <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar y cerrar licitación
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
