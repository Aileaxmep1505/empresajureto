@extends('layouts.app')
@section('title','Acta de antecedente')

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
  --shadow: 0 12px 34px rgba(12,18,30,0.06);
}
*{box-sizing:border-box}
body{font-family:"Open Sans",sans-serif;background:#f3f5f7;color:var(--ink);margin:0;padding:0}

/* Wrapper */
.wizard-wrap{max-width:720px;margin:56px auto;padding:18px;}
.panel{background:var(--card);border-radius:14px;box-shadow:var(--shadow);overflow:hidden;}
.panel-head{padding:20px 22px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:16px;}
.hgroup h2{margin:0;font-weight:700;font-size:20px;}
.hgroup p{margin:4px 0 0;color:var(--muted);font-size:13px;}
.step-tag{font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--mint-dark);font-weight:700;margin-bottom:4px;}
.back-link{display:inline-flex;align-items:center;gap:8px;color:var(--muted);text-decoration:none;padding:8px 12px;border-radius:10px;border:1px solid var(--line);background:#fff;font-size:13px;}
.back-link:hover{border-color:#dbe7ef;color:var(--ink);}

/* Form container */
.form{padding:20px;}

/* Error box */
.alert-error{
  margin:16px 20px 0 20px;
  border-radius:12px;
  background:#fef2f2;
  border:1px solid #fecaca;
  padding:10px 12px;
  font-size:13px;
  color:#b91c1c;
}
.alert-error ul{margin:0;padding-left:18px;}
.alert-error li{margin:2px 0;}

/* Uploader block */
.block{
  border-radius:12px;
  padding:16px;
  background:#fbfdff;
  border:1px dashed var(--line);
}
.uploader{display:flex;flex-direction:column;gap:10px;}
.uploader-top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;}
.btn-file{
  background:var(--mint);
  color:#fff;
  padding:10px 14px;
  border-radius:999px;
  border:none;
  cursor:pointer;
  font-weight:700;
  font-size:13px;
  box-shadow:0 10px 22px rgba(72,207,173,0.16);
}
.btn-file:hover{background:var(--mint-dark);}
.file-chosen{
  font-size:13px;
  color:var(--muted);
}
.small{color:var(--muted);font-size:12px;}

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
  padding:10px 16px;
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

<div class="wizard-wrap">
    <div class="panel">
        <div class="panel-head">
            <div class="hgroup">
                <div class="step-tag">Paso 6 de 9</div>
                <h2>Acta de antecedente</h2>
                <p>Sube el acta de antecedente en formato PDF para tenerla ligada a la licitación.</p>
            </div>

            <a href="{{ route('licitaciones.edit.step5', $licitacion) }}" class="back-link" title="Volver al paso anterior">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                Paso anterior
            </a>
        </div>

        @if($errors->any())
            <div class="alert-error">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="form" action="{{ route('licitaciones.update.step6', $licitacion) }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            <div class="block">
                <div class="uploader">
                    <div class="uploader-top">
                        <label for="acta_antecedente" class="btn-file">
                            Seleccionar archivo PDF
                        </label>
                        <input
                            id="acta_antecedente"
                            type="file"
                            name="acta_antecedente"
                            accept=".pdf"
                            style="display:none;"
                        >
                        <span id="file-name" class="file-chosen">
                            Ningún archivo seleccionado
                        </span>
                    </div>
                    <p class="small">
                        Solo se permite formato <strong>PDF</strong>. Verifica que sea la versión firmada o oficial del acta de antecedente.
                    </p>
                </div>
            </div>

            <div class="actions-line">
                <a href="{{ route('licitaciones.edit.step5', $licitacion) }}" class="link-back">
                    ← Volver al paso anterior
                </a>

                <div class="actions-right">
                    <a href="{{ route('licitaciones.index') }}" class="btn btn-ghost">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        Guardar y continuar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function(){
    const input = document.getElementById('acta_antecedente');
    const label = document.getElementById('file-name');

    if(input && label){
        input.addEventListener('change', function(){
            if(this.files && this.files.length > 0){
                label.textContent = this.files[0].name;
            } else {
                label.textContent = 'Ningún archivo seleccionado';
            }
        });
    }
})();
</script>
@endsection
