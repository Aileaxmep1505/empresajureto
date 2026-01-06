@php
  /** @var \App\Models\Publication $p */
@endphp

<div class="card">
  <div class="top">
    <div style="min-width:0;">
      <h3 class="title">{{ $p->title }}</h3>
      <p class="desc mt-2">{{ $p->description ?: '—' }}</p>
    </div>
    @if($p->pinned)
      <span class="badge-pin">Fijado</span>
    @endif
  </div>

  <div class="preview">
    @if($p->is_image)
      <img src="{{ $p->url }}" alt="{{ $p->title }}">
    @elseif($p->is_video)
      <div class="file-icon">MP4</div>
    @elseif($p->is_pdf)
      <div class="file-icon">PDF</div>
    @else
      <div class="file-icon">{{ strtoupper($p->extension ?: 'FILE') }}</div>
    @endif
  </div>

  <div class="meta">
    <div><b>Tipo:</b> {{ ucfirst($p->kind) }}</div>
    <div><b>Tamaño:</b> {{ $p->nice_size }}</div>
    <div><b>Fecha:</b> {{ $p->created_at->diffForHumans() }}</div>
  </div>

  <div class="footer">
    <a class="link" href="{{ route('publications.show', $p) }}">📄 Ver</a>
    <a class="link secondary" href="{{ route('publications.download', $p) }}">⬇️ Descargar</a>
  </div>
</div>
