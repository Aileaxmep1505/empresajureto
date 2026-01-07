{{-- resources/views/mail/show.blade.php --}}
@extends(request()->has('partial') ? 'layouts.blank' : 'layouts.app')
@section('title', request()->has('partial') ? '' : 'Correo')

@section('content')
@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Carbon;

  // ===== Helpers =====
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

  // ===== Datos seguros desde $msg =====
  /** @var \Webklex\PHPIMAP\Message $msg */
  $fromObj  = optional($msg->getFrom())->first();
  $fromMail = $fromObj?->mail ?? (method_exists($fromObj,'getAddress') ? $fromObj->getAddress() : null);
  $fromName = $fromObj?->personal ?? (method_exists($fromObj,'getName') ? $fromObj->getName() : null);
  $fromLabel= $decodeHeader($fromName ?: ($fromMail ?: '(desconocido)'));

  $to   = $addressesToString($msg->getTo());
  $cc   = $addressesToString($msg->getCc());
  $subj = $decodeHeader($msg->getSubject() ?: '(sin asunto)');

  $when = '';
  try {
      // Webklex a veces trae getDate() como atributo/objeto
      $d = $msg->getDate();
      if ($d) $when = Carbon::parse((string)$d)->locale('es')->translatedFormat('d \\de F \\de Y, H:i');
  } catch (\Throwable $e) {}

  $attachments = $attachments ?? [];
  try {
      if (empty($attachments)) $attachments = $msg->getAttachments() ?? [];
  } catch (\Throwable $e) {}

  $replyUrl   = route('mail.reply',   [$folder, $msg->getUid()]);
  $forwardUrl = route('mail.forward', [$folder, $msg->getUid()]);

  // ===== HTML seguro para vista (sin scripts + responsive) =====
  $optimizeHtml = function (string $html): string {
      $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;

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

      $html = preg_replace_callback('#<table\b([^>]*)>#i', function($m){
          $attrs = $m[1] ?? '';
          if (preg_match('/\sstyle=/', $attrs)) {
              if (!preg_match('/width\s*:/i', $attrs)) {
                  $attrs = preg_replace('/style\s*=\s*"/i', 'style="width:100%;table-layout:auto; ', $attrs, 1);
              }
          } else {
              $attrs .= ' style="width:100%;table-layout:auto"';
          }
          return '<table'.$attrs.'>';
      }, $html) ?? $html;

      return $html;
  };

  $hasHtml  = (bool)$msg->hasHTMLBody();
  $rawHtml  = $hasHtml ? (string)$msg->getHTMLBody() : '';
  $safeHtml = $hasHtml ? $optimizeHtml($rawHtml) : '';
@endphp

@if(request()->has('partial'))
  {{-- ========= PARCIAL para tu JS ========= --}}
  <div id="mx-payload"
       data-subject="{{ e($subj) }}"
       data-from="{{ e($fromLabel) }}"
       data-to="{{ e($to) }}"
       data-cc="{{ e($cc) }}"
       data-when="{{ e($when) }}"
       data-reply="{{ $replyUrl }}"
       data-forward="{{ $forwardUrl }}">

    <style>
      .mail-html, .mail-text { color:#0f172a; font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial; }
      .mail-html img{ max-width:100%; height:auto; }
      .mail-html table{ width:100%; table-layout:auto; border-collapse:collapse; }
      .mail-html iframe, .mail-html embed, .mail-html object{ max-width:100%; }
      .mail-html pre, .mail-text pre{ white-space:pre-wrap; word-wrap:break-word; }
      .mail-html blockquote{ margin:8px 0 8px 12px; padding-left:12px; border-left:3px solid #e5e7eb; color:#475569; }
      .mail-html *{ max-width:100%; }
    </style>

    @if($hasHtml)
      <div class="mail-html" data-body-html>{!! $safeHtml !!}</div>
    @else
      <div class="mail-text" data-body-text style="white-space:pre-wrap">{{ (string)$msg->getTextBody() }}</div>
    @endif

    @foreach($attachments as $att)
      @php
        $attName = null; $attMime = null; $part = '';
        try { $attName = method_exists($att,'getName') ? $decodeHeader($att->getName()) : null; } catch (\Throwable $e) {}
        try { $attMime = method_exists($att,'getMimeType') ? $att->getMimeType() : null; } catch (\Throwable $e) {}
        try { $part    = (string)(method_exists($att,'getPartNumber') ? $att->getPartNumber() : ''); } catch (\Throwable $e) {}
        $href = route('mail.download', [$folder, $msg->getUid(), $part]);
      @endphp
      <div data-att
           data-href="{{ $href }}"
           data-name="{{ e($attName ?: 'archivo') }}"
           data-mime="{{ e($attMime ?: '') }}"></div>
    @endforeach
  </div>
@else
  {{-- ========= Vista completa si entras directo ========= --}}
  <div class="container py-4">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-2">{{ $subj }}</h1>
        <div class="text-muted small mb-3">
          <strong>De:</strong> {{ $fromLabel }}
          @if($to) <span class="mx-2">·</span> <strong>Para:</strong> {{ $to }} @endif
          @if($cc) <span class="mx-2">·</span> <strong>CC:</strong> {{ $cc }} @endif
          @if($when) <span class="mx-2">·</span> {{ $when }} @endif
        </div>

        <div class="mb-3 mail-html">
          @if($hasHtml)
            {!! $safeHtml !!}
          @else
            <pre class="mail-text">{{ (string)$msg->getTextBody() }}</pre>
          @endif
        </div>

        @if(count($attachments))
          <div class="border-top pt-3">
            <div class="fw-bold mb-2">Adjuntos ({{ count($attachments) }})</div>
            @foreach($attachments as $att)
              @php
                $attName = null; $attMime = null; $part = '';
                try { $attName = method_exists($att,'getName') ? $decodeHeader($att->getName()) : null; } catch (\Throwable $e) {}
                try { $attMime = method_exists($att,'getMimeType') ? $att->getMimeType() : null; } catch (\Throwable $e) {}
                try { $part    = (string)(method_exists($att,'getPartNumber') ? $att->getPartNumber() : ''); } catch (\Throwable $e) {}
                $href = route('mail.download', [$folder, $msg->getUid(), $part]);
              @endphp
              <div class="py-1">
                <a href="{{ $href }}">{{ $attName ?: 'archivo' }}</a>
                @if($attMime) <span class="text-muted small">· {{ $attMime }}</span> @endif
              </div>
            @endforeach
          </div>
        @endif

        <div class="mt-3 d-flex gap-2">
          <a class="btn btn-primary" href="{{ $replyUrl }}">Responder</a>
          <a class="btn btn-outline-secondary" href="{{ $forwardUrl }}">Reenviar</a>
        </div>
      </div>
    </div>
  </div>
@endif
@endsection
