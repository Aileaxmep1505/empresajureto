@php
  use Illuminate\Support\Str;
  use Carbon\Carbon;
@endphp

{{-- Grid --}}
@if($documents->count() === 0)
  <div class="vault-empty">No hay documentos.</div>
@else
  <div class="vault-grid" aria-live="polite">
    @foreach($documents as $doc)
      @php
        $filename = $doc->original_name ?? basename($doc->file_path ?? '');
        if (!Str::contains($filename, '.')) {
          $extFromPath = pathinfo($doc->file_path ?? '', PATHINFO_EXTENSION);
          if ($extFromPath) $filename .= '.' . $extFromPath;
        }

        $mime = $doc->mime_type ?? null;
        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $isPdf = ($ext === 'pdf') || ($mime === 'application/pdf');

        $dateLabel = $doc->date
          ? Carbon::parse($doc->date)->format('d M Y')
          : Carbon::parse($doc->created_at)->format('d M Y');

        $title = trim((string)($doc->title ?: pathinfo($filename, PATHINFO_FILENAME)));
        $sub   = trim((string)($doc->doc_key ?: ''));
      @endphp

      <article class="vcard" data-id="{{ $doc->id }}" data-preview="{{ route('confidential.documents.preview', $doc->id) }}">
        <a class="vlink-cover"
           href="{{ route('confidential.documents.preview', $doc->id) }}"
           target="_blank" rel="noopener"
           aria-label="Abrir {{ $title }}"></a>

        <div class="vcard-head">
          <span class="vbadge">{{ $isPdf ? 'PDF' : strtoupper($ext ?: 'FILE') }}</span>

          <div class="vhead-actions">
            <form method="POST" action="{{ route('confidential.documents.destroy', $doc->id) }}"
                  class="vdel-form" style="margin:0;">
              @csrf
              @method('DELETE')
              <button type="submit" class="vicon danger" title="Eliminar" aria-label="Eliminar" onclick="event.stopPropagation();">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                  <path d="M10 11v6"></path>
                  <path d="M14 11v6"></path>
                  <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </form>
          </div>
        </div>

        <div class="vcard-body">
          <div class="vdoc-ic" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="rgba(185,28,28,1)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <path d="M14 2v6h6"></path>
              <path d="M8 13h8"></path>
              <path d="M8 17h6"></path>
            </svg>
          </div>

          <div class="vmeta">
            <div class="vtitle vhl">{{ Str::limit($title, 120) }}</div>
            <div class="vsubmeta">
              <span>{{ $dateLabel }}</span>
              @if($sub !== '')
                <span style="opacity:.45;">•</span>
                <span class="vhl">{{ $sub }}</span>
              @endif
            </div>
          </div>
        </div>

        <div class="vcard-foot">
          <div class="vfoot-left vhl" title="{{ $filename }}">{{ $filename }}</div>

          <a class="vdownload vdownload-btn"
             href="{{ route('confidential.documents.download', $doc->id) }}"
             data-filename="{{ $filename }}"
             onclick="event.stopPropagation();"
             aria-label="Descargar {{ $title }}"
             title="Descargar">
            <span class="dl" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="7 10 12 15 17 10"></polyline>
                <line x1="12" y1="15" x2="12" y2="3"></line>
              </svg>
            </span>
            <span class="spin" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <circle cx="12" cy="12" r="9" stroke="white" stroke-width="2.2" opacity=".25"></circle>
                <path d="M21 12a9 9 0 0 0-9-9" stroke="white" stroke-width="2.2" stroke-linecap="round"></path>
              </svg>
            </span>
          </a>
        </div>
      </article>
    @endforeach
  </div>

  <div class="pc-pagination" style="margin-top:18px;">
    {{ $documents->withQueryString()->links() }}
  </div>
@endif