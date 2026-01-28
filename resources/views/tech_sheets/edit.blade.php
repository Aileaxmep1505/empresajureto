{{-- resources/views/tech_sheets/edit.blade.php --}}
@extends('layouts.app')
@section('title','Editar ficha técnica')

@push('styles')
<style>
  :root{
    --bg:#f6f7fb;
    --card:#ffffff;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e7edf6;
    --shadow:0 18px 50px rgba(15, 23, 42, .08);
    --r:18px;

    --acc:#22c55e;
    --acc-ink:#16a34a;
    --acc-soft:rgba(34,197,94,.12);
    --acc-ring:rgba(34,197,94,.22);

    --slate:#0f172a;
  }

  html,body{ background:var(--bg); }

  .wrap{ max-width:1120px; margin:0 auto; padding:18px 14px 28px; }

  .topbar{
    display:flex; align-items:flex-start; justify-content:space-between;
    gap:14px; flex-wrap:wrap;
    margin:8px 0 14px;
  }
  .title{
    margin:0;
    font-weight:700;
    letter-spacing:-.02em;
    color:var(--ink);
    font-size:1.3rem;
  }
  .subtitle{
    margin-top:6px;
    color:var(--muted);
    font-size:.92rem;
    line-height:1.35;
  }

  .actions{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; }

  .btn{
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    padding:9px 12px;
    border-radius:12px;
    border:1px solid var(--acc-ring);
    text-decoration:none;
    cursor:pointer;
    font-weight:500;
    user-select:none;
    transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease, color .12s ease;
    box-shadow:0 10px 26px rgba(15,23,42,.06);
    background:var(--acc-soft);
    color:var(--acc-ink);
  }
  .btn:hover{
    transform: translateY(-1px);
    background:#ffffff;
    color:var(--acc-ink);
    border-color:var(--acc-ring);
    box-shadow:0 14px 34px rgba(15,23,42,.10);
  }
  .btn:active{ transform:translateY(0); }

  .btn.ghost{
    background:#ffffff;
    color:var(--ink);
    border-color:var(--line);
  }
  .btn.ghost:hover{
    border-color:var(--acc-ring);
  }

  .btn.dark{
    background:var(--slate);
    color:#ffffff;
    border-color:rgba(15,23,42,.4);
  }
  .btn.dark:hover{
    background:#020617;
    border-color:rgba(15,23,42,.6);
    color:#ffffff;
  }

  .ico{
    width:18px; height:18px;
    display:inline-grid; place-items:center;
  }
  .ico svg{ width:18px; height:18px; display:block; }

  .grid{
    display:grid;
    grid-template-columns: 1.15fr .85fr;
    gap:14px;
  }
  @media (max-width: 980px){
    .grid{ grid-template-columns: 1fr; }
  }

  .card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:var(--r);
    box-shadow:var(--shadow);
    overflow:hidden;
  }
  .card-h{
    padding:14px 16px;
    border-bottom:1px solid var(--line);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
  }
  .card-h h3{
    margin:0;
    font-size:.96rem;
    font-weight:600;
    color:var(--ink);
    letter-spacing:-.01em;
  }
  .card-b{ padding:14px 16px 16px; }

  .alert{
    border-radius:16px;
    border:1px solid var(--line);
    background:#ffffff;
    box-shadow:0 14px 30px rgba(15,23,42,.06);
    padding:10px 12px;
    margin-bottom:12px;
    font-size:.86rem;
  }
  .alert.ok{ border-color:rgba(34,197,94,.28); color:#166534; }
  .alert.err{ border-color:rgba(239,68,68,.25); color:#7f1d1d; }

  .fields{ display:grid; gap:12px; }
  .row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  @media (max-width: 720px){ .row{ grid-template-columns:1fr; } }

  label{
    display:block;
    font-size:.84rem;
    font-weight:600;
    color:#334155;
    margin:0 0 6px;
  }
  input[type="text"], textarea{
    width:100%;
    border:1px solid rgba(231,237,246,.95);
    background:#ffffff;
    border-radius:12px;
    padding:9px 11px;
    outline:none;
    color:var(--ink);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 10px 18px rgba(15,23,42,.03);
    transition:border-color .14s ease, box-shadow .14s ease, transform .14s ease;
    font-size:.9rem;
  }
  textarea{ min-height:120px; resize:vertical; }

  input:focus, textarea:focus{
    border-color:var(--acc-ring);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.95), 0 14px 24px rgba(34,197,94,.10);
    transform: translateY(-1px);
  }

  .muted{ color:var(--muted); font-size:.86rem; line-height:1.35; }

  .filebox{
    border:1px dashed rgba(148,163,184,.55);
    border-radius:14px;
    padding:10px;
    background:#f9fafb;
  }
  .filebox input[type="file"]{ width:100%; }

  .pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:999px;
    border:1px solid rgba(231,237,246,.95);
    background:#ffffff;
    color:#334155;
    font-weight:500;
    font-size:.8rem;
  }
  .pill small{ color:var(--muted); font-weight:500; }

  .mini-links{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px;
  }
  .alink{
    font-weight:500;
    color:#0f172a;
    text-decoration:none;
    border-bottom:1px solid rgba(15,23,42,.25);
    padding-bottom:1px;
    font-size:.84rem;
  }
  .alink:hover{ border-bottom-color:var(--acc-ring); }

  .footer-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:12px;
  }

  .hint{
    margin-top:6px;
    font-size:.82rem;
    color:var(--muted);
    line-height:1.4;
  }
</style>
@endpush

@section('content')
<div class="wrap">

  <div class="topbar">
    <div>
      <h1 class="title">Editar ficha técnica</h1>
      <div class="subtitle">
        {{ $sheet->product_name }}
        · Actualizada {{ optional($sheet->updated_at)->diffForHumans() }}
      </div>
    </div>

    <div class="actions">
      <a class="btn ghost" href="{{ route('tech-sheets.index') }}">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 18l-6-6 6-6"/>
          </svg>
        </span>
        Volver
      </a>

      <a class="btn dark" href="{{ route('tech-sheets.pdf.generated', $sheet) }}" target="_blank">
        <span class="ico">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 18h10"/><path d="M12 3v11"/><path d="M8 10l4 4 4-4"/>
          </svg>
        </span>
        PDF generado
      </a>

      @if($sheet->custom_pdf_path)
        <a class="btn" href="{{ asset('storage/'.$sheet->custom_pdf_path) }}" target="_blank">
          <span class="ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M7 18h10"/><path d="M12 3v11"/><path d="M8 10l4 4 4-4"/>
            </svg>
          </span>
          PDF subido
        </a>
      @endif
    </div>
  </div>

  @if(session('ok'))
    <div class="alert ok">
      {{ session('ok') }}
    </div>
  @endif

  @if($errors->any())
    <div class="alert err">
      <b>Revisa los campos:</b>
      <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ route('tech-sheets.update', $sheet) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="grid">
      {{-- DATOS --}}
      <section class="card">
        <div class="card-h">
          <h3>Datos de la ficha</h3>
          <span class="pill">
            ID <small>#{{ $sheet->id }}</small>
          </span>
        </div>

        <div class="card-b">
          <div class="fields">
            <div>
              <label>Nombre del producto</label>
              <input type="text" name="product_name" value="{{ old('product_name', $sheet->product_name) }}" required>
            </div>

            <div class="row">
              <div>
                <label>Marca</label>
                <input type="text" name="brand" value="{{ old('brand', $sheet->brand) }}">
              </div>
              <div>
                <label>Modelo</label>
                <input type="text" name="model" value="{{ old('model', $sheet->model) }}">
              </div>
            </div>

            <div class="row">
              <div>
                <label>Referencia</label>
                <input type="text" name="reference" value="{{ old('reference', $sheet->reference) }}">
              </div>
              <div>
                <label>Identificación / categoría</label>
                <input type="text" name="identification" value="{{ old('identification', $sheet->identification) }}">
              </div>
            </div>

            <div class="row">
              <div>
                <label>Partida</label>
                <input type="text" name="partida_number" value="{{ old('partida_number', $sheet->partida_number) }}">
              </div>
              <div>
                <label class="muted" style="margin-bottom:6px;">&nbsp;</label>
                <div class="muted">
                  Modifica los datos base; el PDF generado siempre estará disponible.
                </div>
              </div>
            </div>

            <div>
              <label>Descripción</label>
              <textarea name="user_description">{{ old('user_description', $sheet->user_description) }}</textarea>
            </div>

            <div class="footer-actions">
              <button class="btn" type="submit">
                <span class="ico">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V7l4-4h10l4 4v12a2 2 0 0 1-2 2z"/>
                    <path d="M7 21v-8h10v8"/>
                    <path d="M7 3v4h10V3"/>
                  </svg>
                </span>
                Guardar cambios
              </button>

              <a class="btn ghost" href="{{ route('tech-sheets.show', $sheet) }}">
                <span class="ico">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </span>
                Ver ficha
              </a>
            </div>
          </div>
        </div>
      </section>

      {{-- PDFS --}}
      <section class="card" id="pdfs">
        <div class="card-h">
          <h3>PDF de la ficha</h3>
          <span class="pill">
            Estado
            <small>{{ $sheet->custom_pdf_path ? 'Con PDF subido' : 'Solo generado' }}</small>
          </span>
        </div>

        <div class="card-b">

          <div>
            <label style="margin-bottom:6px;">Acceso rápido</label>
            <div class="mini-links">
              <a class="alink" href="{{ route('tech-sheets.pdf.generated', $sheet) }}" target="_blank">PDF generado</a>
              @if($sheet->custom_pdf_path)
                <a class="alink" href="{{ asset('storage/'.$sheet->custom_pdf_path) }}" target="_blank">PDF subido</a>
              @endif
              @if($sheet->brand_pdf_path)
                <a class="alink" href="{{ asset('storage/'.$sheet->brand_pdf_path) }}" target="_blank">PDF marca</a>
              @endif
            </div>
          </div>

          <div style="margin-top:14px;">
            <label>Subir PDF adicional</label>
            <div class="filebox">
              <input type="file" name="custom_pdf" accept="application/pdf">
              <div class="hint">
                El PDF subido es independiente del generado.
              </div>
            </div>
          </div>

        </div>
      </section>
    </div>
  </form>

</div>
@endsection
