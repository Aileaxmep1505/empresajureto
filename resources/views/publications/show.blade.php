@extends('layouts.app')

@section('title', $publication->title)

@section('content')
<div class="container py-4" id="pub-show">
  <style>
    #pub-show{
      --ink:#0f172a;
      --muted:#64748b;
      --line:#e2e8f0;
      --card:#ffffff;
      --shadow: 0 12px 35px rgba(2,6,23,.08);
      --brand:#2563eb;
      --brand2:#7c3aed;
    }
    #pub-show .wrap{
      display:grid;
      grid-template-columns: 1.05fr .95fr;
      gap:18px;
      align-items:start;
    }
    @media (max-width: 992px){ #pub-show .wrap{ grid-template-columns: 1fr; } }

    #pub-show .panel{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:18px;
      box-shadow:var(--shadow);
      overflow:hidden;
    }
    #pub-show .media{
      aspect-ratio: 16/10;
      background:#f1f5f9;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    #pub-show img{ width:100%; height:100%; object-fit:cover; display:block; }
    #pub-show .info{ padding:18px; }
    #pub-show h1{ margin:0; font-weight:900; letter-spacing:-.02em; color:var(--ink); }
    #pub-show .desc{ color:var(--muted); margin-top:10px; line-height:1.6; }
    #pub-show .meta{
      margin-top:14px;
      display:flex; gap:10px; flex-wrap:wrap;
      color:var(--muted); font-size:13px;
    }
    #pub-show .meta b{ color:var(--ink); }
    #pub-show .actions{
      padding:16px 18px;
      border-top:1px solid var(--line);
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:space-between;
      align-items:center;
      background: linear-gradient(180deg, #ffffff, #f8fafc);
    }
    #pub-show .btn-brand{
      border:0; color:#fff; font-weight:800;
      padding:10px 14px; border-radius:12px;
      background: linear-gradient(135deg, var(--brand), var(--brand2));
      text-decoration:none;
      display:inline-flex; align-items:center; gap:10px;
      box-shadow: 0 10px 30px rgba(37,99,235,.18);
    }
    #pub-show .btn-dark{
      border:0; color:#fff; font-weight:800;
      padding:10px 14px; border-radius:12px;
      background:#0f172a;
      text-decoration:none;
      display:inline-flex; align-items:center; gap:10px;
    }
  </style>

  <div class="wrap">
    <div class="panel">
      <div class="media">
        @if($publication->is_image)
          <img src="{{ $publication->url }}" alt="{{ $publication->title }}">
        @elseif($publication->is_video)
          <video controls style="width:100%; height:100%; object-fit:cover;">
            <source src="{{ $publication->url }}" type="{{ $publication->mime_type }}">
            Tu navegador no soporta video.
          </video>
        @elseif($publication->is_pdf)
          <iframe src="{{ $publication->url }}" style="width:100%; height:100%; border:0;"></iframe>
        @else
          <div style="text-align:center; padding:20px;">
            <div style="font-weight:900; font-size:36px; color:#334155;">
              {{ strtoupper($publication->extension ?: 'FILE') }}
            </div>
            <div style="color:#64748b; margin-top:6px;">
              No hay previsualización para este formato. Descárgalo.
            </div>
          </div>
        @endif
      </div>

      <div class="actions">
        <a class="btn-dark" href="{{ route('publications.index') }}">← Volver</a>
        <a class="btn-brand" href="{{ route('publications.download', $publication) }}">⬇️ Descargar</a>
      </div>
    </div>

    <div class="panel">
      <div class="info">
        <h1>{{ $publication->title }}</h1>
        <div class="desc">{{ $publication->description ?: '—' }}</div>

        <div class="meta">
          <div><b>Tipo:</b> {{ ucfirst($publication->kind) }}</div>
          <div><b>Archivo:</b> {{ $publication->original_name }}</div>
          <div><b>Tamaño:</b> {{ $publication->nice_size }}</div>
          <div><b>Subido:</b> {{ $publication->created_at->format('d/m/Y H:i') }}</div>
        </div>
      </div>

      @auth
      <div class="actions">
        <form action="{{ route('publications.destroy', $publication) }}" method="POST" onsubmit="return confirm('¿Eliminar publicación?')">
          @csrf
          @method('DELETE')
          <button class="btn-dark" type="submit">🗑️ Eliminar</button>
        </form>
      </div>
      @endauth
    </div>
  </div>
</div>
@endsection
