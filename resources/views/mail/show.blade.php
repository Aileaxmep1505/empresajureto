{{-- resources/views/mail/show.blade.php --}}
@extends(request()->has('partial') ? 'layouts.blank' : 'layouts.app')
@section('title', request()->has('partial') ? '' : 'Correo')

@section('content')
@php
  // ============ Helpers de decodificación RFC y formateo ============
  $decodeHeader = function (?string $v): string {
      if (!$v) return '';
      if (function_exists('iconv_mime_decode')) {
          $d = @iconv_mime_decode($v, 0, 'UTF-8');
          if ($d !== false) return $d;
      }
      if (function_exists('mb_decode_mimeheader')) {
          $d = @mb_decode_mimeheader($v);
          if (is_string($d) && $d !== '') return $d;
      }
      if (preg_match_all('/=\?([^?]+)\?(Q|B)\?([^?]+)\?=/i', $v, $m, PREG_SET_ORDER)) {
          foreach ($m as $p) {
              [$full,$cs,$mode,$data] = $p;
              $mode = strtoupper($mode);
              $data = $mode === 'B'
                  ? base64_decode($data)
                  : quoted_printable_decode(str_replace('_',' ', $data));
              $v = str_replace($full, $data, $v);
          }
      }
      return $v;
  };

  $addressesToString = function ($collection) use ($decodeHeader): string {
      $out = [];
      if ($collection) {
          try {
              foreach ($collection as $a) {
                  $mail = null; $name = null;
                  if (is_object($a)) {
                      $mail = $a->mail ?? (method_exists($a,'getAddress') ? $a->getAddress() : null);
                      $name = $a->personal ?? (method_exists($a,'getName') ? $a->getName() : null);
                  } elseif (is_array($a)) {
                      $mail = $a['mail'] ?? $a['address'] ?? null;
                      $name = $a['personal'] ?? $a['name'] ?? null;
                  } elseif (is_string($a)) {
                      $mail = trim($a);
                  }
                  $mail = is_string($mail) ? trim($mail) : null;
                  $name = is_string($name) ? trim($name) : null;
                  if ($name) $name = $decodeHeader($name);
                  if ($mail) $out[] = $name ? "{$name} <{$mail}>" : $mail;
              }
          } catch (\Throwable $e) {}
      }
      return implode(', ', array_filter($out));
  };

  // ============ Cabeceras seguras ============
  $fromObj   = optional($msg->getFrom())->first();
  $fromMail  = null; $fromName = null;
  if ($fromObj) {
      $fromMail = $fromObj->mail ?? (method_exists($fromObj,'getAddress') ? $fromObj->getAddress() : null);
      $fromName = $fromObj->personal ?? (method_exists($fromObj,'getName') ? $fromObj->getName() : null);
  }
  $fromLabel = $decodeHeader($fromName ?: ($fromMail ?: '(desconocido)'));

  $to   = $addressesToString($msg->getTo());
  $cc   = $addressesToString($msg->getCc());
  $subj = $decodeHeader($msg->getSubject() ?: '(sin asunto)');

  /**
   * ✅ Fecha robusta (arregla cosas tipo: "26262626 Fri [26e] DecemberDecember12, 0909:1212")
   * Intentamos: getDate() -> header Date -> internalDate
   */
  $when = '';
  try {
      $dt = null;

      // 1) Webklex getDate()
      try {
          $d = $msg->getDate();
          if ($d instanceof \Carbon\Carbon) $dt = $d;
          elseif ($d instanceof \DateTimeInterface) $dt = \Carbon\Carbon::instance($d);
          elseif (is_object($d) && method_exists($d,'toString')) $dt = \Carbon\Carbon::parse((string)$d);
          elseif (is_string($d) && trim($d) !== '') $dt = \Carbon\Carbon::parse($d);
      } catch (\Throwable $e) {}

      // 2) Header Date (raw)
      if (!$dt) {
          $h = optional($msg->get('date'))->first();
          $v = $h && method_exists($h,'getValue') ? (string)$h->getValue() : (string)$h;
          if (trim($v) !== '') $dt = \Carbon\Carbon::parse($v);
      }

      // 3) Internal date
      if (!$dt && method_exists($msg,'getInternalDate')) {
          $id = $msg->getInternalDate();
          if ($id instanceof \DateTimeInterface) $dt = \Carbon\Carbon::instance($id);
          elseif (is_string($id) && trim($id) !== '') $dt = \Carbon\Carbon::parse($id);
      }

      if ($dt) {
          $dt = $dt->copy()->setTimezone(config('app.timezone', 'America/Mexico_City'))->locale('es');
          $when = $dt->translatedFormat('d \\de F \\de Y, H:i');
      }
  } catch (\Throwable $e) { $when = ''; }

  // ============ Adjuntos y rutas ============
  $attachments = $attachments ?? ($msg->getAttachments() ?? []);
  $replyUrl    = route('mail.reply',   [$folder, $msg->getUid()]);
  $forwardUrl  = route('mail.forward', [$folder, $msg->getUid()]);

  // ============ Optimización del cuerpo HTML ============
  $optimizeHtml = function (string $html): string {
      $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;

      // Colapsar blockquotes (citas largas)
      $html = preg_replace_callback('#(<blockquote[\s\S]*?</blockquote>)#i', function($m){
          $block = $m[1];
          if (stripos($block, '<details') !== false) return $block;
          return '<details class="mx-quote"><summary>Mostrar cita</summary>'.$block.'</details>';
      }, $html) ?? $html;

      // Lazy/async en imágenes
      $html = preg_replace_callback('#<img\b([^>]*?)>#i', function($m){
          $attrs = $m[1] ?? '';
          if (!preg_match('/\sloading=/', $attrs)) $attrs .= ' loading="lazy"';
          if (!preg_match('/\sdecoding=/', $attrs)) $attrs .= ' decoding="async"';
          if (!preg_match('/\sreferrerpolicy=/', $attrs)) $attrs .= ' referrerpolicy="no-referrer"';

          if (preg_match('/\sstyle=/', $attrs)) {
              if (!preg_match('/max-width\s*:/i', $attrs)) {
                  $attrs = preg_replace('/style\s*=\s*"/i', 'style="max-width:100%;height:auto; ', $attrs, 1);
              }
          } else {
              $attrs .= ' style="max-width:100%;height:auto"';
          }
          return '<img'.$attrs.'>';
      }, $html) ?? $html;

      // Tablas responsivas
      $html = preg_replace_callback('#<table\b([^>]*)>#i', function($m){
          $attrs = $m[1] ?? '';
          if (preg_match('/\sstyle=/', $attrs)) {
              if (!preg_match('/width\s*:/i', $attrs)) {
                  $attrs = preg_replace('/style\s*=\s*"/i', 'style="width:100%; table-layout:auto; ', $attrs, 1);
              }
          } else {
              $attrs .= ' style="width:100%;table-layout:auto"';
          }
          return '<table'.$attrs.'>';
      }, $html) ?? $html;

      return $html;
  };

  $hasHtml  = (bool) $msg->hasHTMLBody();
  $rawHtml  = $hasHtml ? (string)$msg->getHTMLBody() : '';
  $safeHtml = $hasHtml ? $optimizeHtml($rawHtml) : '';

  // Texto (por si no hay HTML)
  $textBody = (string)($msg->getTextBody() ?? '');
@endphp

@if(request()->has('partial'))
  {{-- ========= PARCIAL (lo usa el fetch de la lista) ========= --}}
  <div id="mx-payload"
       data-subject="{{ e($subj) }}"
       data-from="{{ e($fromLabel) }}"
       data-to="{{ e($to) }}"
       data-cc="{{ e($cc) }}"
       data-when="{{ e($when) }}"
       data-reply="{{ $replyUrl }}"
       data-forward="{{ $forwardUrl }}">

    {{-- Estilos encapsulados para el fragmento HTML --}}
    <style>
      .mail-html, .mail-text { color:#0f172a; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial; }
      .mail-html img{ max-width:100%; height:auto; }
      .mail-html table{ width:100%; table-layout:auto; border-collapse:collapse; }
      .mail-html iframe, .mail-html embed, .mail-html object{ max-width:100%; }
      .mail-html pre, .mail-text pre{ white-space:pre-wrap; word-wrap:break-word; }
      .mail-html blockquote{ margin:8px 0 8px 12px; padding-left:12px; border-left:3px solid #e5e7eb; color:#475569; }
      details.mx-quote{ margin:.5rem 0; border:1px dashed #e5e7eb; border-radius:8px; padding:.4rem .6rem; background:#fbfdff; }
      details.mx-quote > summary{ cursor:pointer; font-weight:600; color:#334155; }
      .mail-html * { max-width:100%; }
    </style>

    @if($hasHtml)
      <div class="mail-html" data-body-html>{!! $safeHtml !!}</div>
    @else
      {{-- ✅ Tu index.js ya maneja data-body-text como <pre>, así que aquí lo damos en un div oculto --}}
      <div class="mail-text" data-body-text style="display:none">{{ $textBody }}</div>
    @endif

    @foreach($attachments as $att)
      @php
        $attName = method_exists($att,'getName') ? $decodeHeader($att->getName()) : null;
        $attMime = method_exists($att,'getMimeType') ? $att->getMimeType() : null;
        $part    = (string) (method_exists($att,'getPartNumber') ? $att->getPartNumber() : '');
        // ✅ OJO: usa el nombre de ruta que tengas. Si en tu sistema es mail.download, déjalo así.
        // Si lo cambiaste a mail.attachment, ajusta aquí.
        $href    = route('mail.download', [$folder, $msg->getUid(), $part ?: '1']);
      @endphp
      <div data-att
           data-href="{{ $href }}"
           data-name="{{ e($attName ?: 'archivo') }}"
           data-mime="{{ e($attMime ?: '') }}"></div>
    @endforeach
  </div>

@else
  {{-- ========= Página completa ========= --}}
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700&display=swap"/>
  <div class="container py-4">
    <style>
      .mx-mail-wrap{ font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial; color:#0f172a; }
      .mx-mail-wrap .mail-html img{ max-width:100%; height:auto; }
      .mx-mail-wrap .mail-html table{ width:100%; table-layout:auto; border-collapse:collapse; }
      .mx-mail-wrap details.mx-quote{ margin:.5rem 0; border:1px dashed #e5e7eb; border-radius:8px; padding:.4rem .6rem; background:#fbfdff; }
      .mx-mail-wrap details.mx-quote > summary{ cursor:pointer; font-weight:600; color:#334155; }
      .mx-mail-wrap .mail-html pre{ white-space:pre-wrap; word-wrap:break-word; }
    </style>

    <div class="card shadow-sm mx-mail-wrap">
      <div class="card-body">
        <h1 class="h4 mb-2">{{ $subj }}</h1>

        <div class="text-muted small mb-3">
          <strong>De:</strong> {{ $fromLabel }}
          @if($to) <span class="mx-2">·</span> <strong>Para:</strong> {{ $to }} @endif
          @if($cc) <span class="mx-2">·</span> <strong>CC:</strong> {{ $cc }} @endif
          @if($when) <span class="mx-2">·</span> {{ $when }} @endif
        </div>

        <div class="mb-3">
          @if($hasHtml)
            <div class="mail-html">{!! $safeHtml !!}</div>
          @else
            <pre class="mail-text" style="white-space:pre-wrap">{{ $textBody }}</pre>
          @endif
        </div>

        @if(count($attachments))
          <div class="border-top pt-3">
            <div class="fw-bold mb-2">Adjuntos ({{ count($attachments) }})</div>
            @foreach($attachments as $att)
              @php
                $attName = method_exists($att,'getName') ? $decodeHeader($att->getName()) : null;
                $attMime = method_exists($att,'getMimeType') ? $att->getMimeType() : null;
                $part    = (string) (method_exists($att,'getPartNumber') ? $att->getPartNumber() : '');
                $href    = route('mail.download', [$folder, $msg->getUid(), $part ?: '1']);
              @endphp
              <div class="d-flex align-items-center gap-2 py-1">
                <span class="material-symbols-outlined">attachment</span>
                <a href="{{ $href }}">{{ $attName ?: 'archivo' }}</a>
                <span class="text-muted small">· {{ $attMime }}</span>
              </div>
            @endforeach
          </div>
        @endif

        <div class="mt-3 d-flex gap-2">
          <a class="btn btn-primary" href="{{ $replyUrl }}">
            <span class="material-symbols-outlined align-middle">reply</span> Responder
          </a>
          <a class="btn btn-outline-secondary" href="{{ $forwardUrl }}">
            <span class="material-symbols-outlined align-middle">forward</span> Reenviar
          </a>
        </div>
      </div>
    </div>
  </div>
@endif
@endsection
