{{-- resources/views/mail/index.blade.php --}}
@extends('layouts.app')
@section('title','Correo')

@section('content')
{{-- Tipografía: usa la global del layout (NO Outfit) --}}
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700&display=swap"/>

<div id="mailx">
  <style>
    /* ====================== MailX UI (Formal pastel + global font) ====================== */
    #mailx{
      --ink:#0f172a; --muted:#667085;
      --line:#e8eef6; --line2:#eef2f7;
      --bg:#f6f8fc; --card:#ffffff;

      /* Pastel formal */
      --p1:#e8f1ff;         /* pastel base */
      --p2:#d7e8ff;         /* hover */
      --p3:#c6dcff;         /* primary */
      --p4:#b7d1ff;         /* primary hover */
      --ring:#cfe0ff;

      --shadow:0 18px 46px rgba(2,8,23,.08);
      --radius:18px;
      --ease:cubic-bezier(.2,.8,.2,1);

      /* usar tipografía global */
      font-family: inherit;
      color:var(--ink);

      background:
        radial-gradient(900px 520px at -10% -15%, rgba(232,241,255,.55), transparent 60%),
        radial-gradient(900px 520px at 110% 110%, rgba(232,241,255,.30), transparent 60%),
        var(--bg);

      min-height:calc(100vh - 64px);
    }

    #mailx .wrap{
      max-width:1600px; margin:0 auto; padding:16px;
      display:grid; grid-template-columns: 280px 480px 1fr;
      gap:16px; align-items:start;
    }

    /* Panels SOLO para lista + preview */
    #mailx .panel{
      background:var(--card);
      border:1px solid var(--line);
      border-radius:var(--radius);
      box-shadow:var(--shadow);
      overflow:hidden;
    }

    /* ====================== Sidebar SIN contenedor ====================== */
    #mailx .nav{
      position:sticky; top:16px;
      height:calc(100vh - 32px);
      display:flex; flex-direction:column;

      /* NO card */
      background:transparent;
      border:0;
      box-shadow:none;
      border-radius:0;
      overflow:visible;
    }

    #mailx .compose{
      padding:0 0 12px 0;
      margin:0 0 10px 0;
      border-bottom:1px solid rgba(232,238,246,.9);
    }

    /* Botones pastel formales */
    #mailx .btn{
      appearance:none;
      border:1px solid rgba(198,220,255,.95);
      background:var(--p1);
      color:var(--ink);
      padding:12px 14px;
      border-radius:14px;
      display:inline-flex;
      gap:10px;
      align-items:center;
      font-weight:700;
      cursor:pointer;
      text-decoration:none;
      transition:transform .15s var(--ease), background .15s var(--ease), box-shadow .15s var(--ease);
      user-select:none;
    }
    #mailx .btn:hover{ background:var(--p2); transform:translateY(-1px); box-shadow:0 10px 18px rgba(2,8,23,.08); }
    #mailx .btn:focus-visible{ outline:none; box-shadow:0 0 0 3px var(--ring); }

    #mailx .btn.primary{
      width:100%;
      justify-content:center;
      background:var(--p3);
      border-color:rgba(183,209,255,.95);
      color:var(--ink);
    }
    #mailx .btn.primary:hover{ background:var(--p4); }

    #mailx .folders{
      padding:4px 0;
      overflow:auto;
      flex:1;
    }

    /* Items sidebar SIN “card” */
    #mailx .folder{
      position:relative;
      display:flex; align-items:center; justify-content:space-between;
      gap:10px;
      padding:12px 12px;
      border-radius:14px;
      color:inherit;
      text-decoration:none;
      border:1px solid transparent;
      transition:background .15s var(--ease), border-color .15s var(--ease), transform .15s var(--ease);
    }
    #mailx .folder:hover{
      background:rgba(232,241,255,.65);
      transform:translateY(-1px);
    }
    #mailx .folder .l{ display:flex; align-items:center; gap:10px; font-weight:700; }
    #mailx .folder .text{ font-weight:700; }

    #mailx .folder.active{
      background:rgba(214,232,255,.75);
      border-color:rgba(198,220,255,.95);
    }
    #mailx .folder.active::before{
      content:"";
      position:absolute; left:6px; top:10px; bottom:10px;
      width:4px; border-radius:99px;
      background:rgba(148,182,255,.95);
    }
    #mailx .folder.active .l{ padding-left:6px; }

    #mailx .badge{
      min-width:24px; height:24px; padding:0 7px;
      border-radius:999px;
      display:grid; place-items:center;
      font-size:.78rem;
      font-weight:700;
      background:rgba(232,241,255,.95);
      border:1px solid rgba(198,220,255,.95);
      color:#22314a;
    }

    /* ====================== Lista ====================== */
    #mailx .list{ display:flex; flex-direction:column; min-height:60vh; }

    #mailx .list .top{
      display:flex; align-items:center; gap:10px;
      padding:12px;
      border-bottom:1px solid var(--line);
      background:linear-gradient(180deg,#fff,#f9fbff);
    }

    #mailx .iconbtn{
      all:unset;
      cursor:pointer;
      display:grid;
      place-items:center;
      width:42px; height:42px;
      border-radius:14px;
      border:1px solid var(--line);
      background:#fff;
      transition:transform .15s var(--ease), background .15s var(--ease), box-shadow .15s var(--ease);
    }
    #mailx .iconbtn:hover{ transform:translateY(-1px); background:#f6f9ff; box-shadow:0 10px 18px rgba(2,8,23,.08); }
    #mailx .iconbtn:focus-visible{ outline:none; box-shadow:0 0 0 3px var(--ring); }

    #mailx .title{
      font-weight:800;
      display:flex; align-items:center; gap:8px;
      white-space:nowrap;
    }

    #mailx .search{
      flex:1;
      display:flex;
      align-items:center;
      gap:8px;
      background:#f3f6fc;
      border:1px solid var(--line);
      border-radius:14px;
      padding:10px 12px;
    }
    #mailx .search:focus-within{ box-shadow:0 0 0 3px var(--ring); }
    #mailx .search input{ all:unset; width:100%; color:var(--ink); font-weight:600; }

    #mailx .filters{
      display:flex; gap:8px;
      padding:10px 12px;
      border-bottom:1px dashed var(--line);
      flex-wrap:wrap;
      background:#fff;
    }
    #mailx .chip{
      display:inline-flex; align-items:center; gap:6px;
      padding:8px 12px;
      border:1px solid rgba(198,220,255,.85);
      border-radius:999px;
      background:rgba(232,241,255,.9);
      font-size:.86rem;
      font-weight:700;
      cursor:pointer;
      transition:transform .15s var(--ease), background .15s var(--ease);
      user-select:none;
    }
    #mailx .chip:hover{ transform:translateY(-1px); background:rgba(214,232,255,.9); }
    #mailx .chip.active{
      background:rgba(198,220,255,.95);
      border-color:rgba(183,209,255,.95);
      color:var(--ink);
    }

    #mailx .groups{ overflow:auto; max-height:calc(100vh - 240px); background:#fff; }
    #mailx .group-title{
      position:sticky; top:0;
      background:#fff;
      z-index:2;
      padding:9px 12px;
      font-size:.86rem;
      color:var(--muted);
      border-bottom:1px solid var(--line);
      font-weight:700;
    }

    #mailx .row{
      position:relative;
      display:grid;
      grid-template-columns:36px 1fr auto;
      gap:12px;
      padding:14px 12px;
      border-bottom:1px solid var(--line2);
      cursor:pointer;
      transition:background .15s var(--ease);
      background:#fff;
    }
    #mailx .row:hover{ background:rgba(232,241,255,.55); }

    #mailx .row.is-selected{
      background:linear-gradient(90deg, rgba(232,241,255,.85), #ffffff 60%);
    }
    #mailx .row.is-selected::before{
      content:"";
      position:absolute; left:0; top:0; bottom:0;
      width:3px;
      background:rgba(148,182,255,.95);
    }

    #mailx .from{ font-weight:800; }
    #mailx .subject{ font-weight:800; margin-top:2px; }
    #mailx .snippet{ color:var(--muted); margin-top:4px; font-size:.92rem; line-height:1.25; }
    #mailx .meta{ display:flex; align-items:center; gap:10px; color:var(--muted); font-size:.86rem; font-weight:600; }

    #mailx .star button{ all:unset; cursor:pointer; display:grid; place-items:center; width:30px; height:30px; border-radius:12px; }
    #mailx .star button:hover{ background:#fff; box-shadow:0 6px 16px rgba(2,8,23,.08); border:1px solid var(--line); }

    /* Acciones rápidas */
    #mailx .quick{
      position:absolute; right:8px; top:50%;
      transform:translateY(-50%);
      display:flex; gap:6px;
      opacity:0; pointer-events:none;
      transition:.15s var(--ease);
    }
    #mailx .row:hover .quick{ opacity:1; pointer-events:auto; }
    #mailx .qbtn{
      all:unset; cursor:pointer;
      display:grid; place-items:center;
      width:34px; height:34px;
      border:1px solid var(--line);
      border-radius:12px;
      background:#fff;
      transition:transform .15s var(--ease), box-shadow .15s var(--ease);
    }
    #mailx .qbtn:hover{ transform:translateY(-1px); box-shadow:0 10px 18px rgba(2,8,23,.10); }

    /* ====================== Preview ====================== */
    #mailx .preview{ display:grid; grid-template-rows:auto 1fr auto; min-height:60vh; background:#fff; }
    #mailx .preview .head{
      padding:14px 16px;
      border-bottom:1px solid var(--line);
      background:linear-gradient(180deg,#fff,#f9fbff);
    }
    #mailx .preview .subject{ font-size:1.18rem; font-weight:800; line-height:1.18; }
    #mailx .preview .meta{
      margin-top:8px;
      color:var(--muted);
      display:flex; gap:12px; flex-wrap:wrap;
      font-weight:600;
    }
    #mailx .preview .actions{ margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; }
    #mailx .preview .body{ padding:16px; min-height:52vh; overflow:auto; }
    #mailx .attachments{
      padding:12px 16px;
      border-top:1px dashed var(--line);
      background:#fbfdff;
    }
    #mailx .attachment{ display:flex; align-items:center; gap:10px; padding:8px 0; font-weight:600; }
    #mailx .attachment a{ color:#1f4fd8; text-decoration:none; }
    #mailx .attachment a:hover{ text-decoration:underline; }

    #mailx .empty{
      display:flex;
      align-items:center;
      justify-content:center;
      height:calc(100vh - 240px);
      color:var(--muted);
      font-weight:700;
    }

    /* Mobile UX: overlay + drawer de nav + preview sheet */
    #mailx .overlay{
      display:none;
      position:fixed; inset:0;
      background:rgba(2,8,23,.35);
      z-index:80;
    }

    @media (max-width:1280px){
      #mailx .wrap{ grid-template-columns: 1fr; }

      /* Drawer del sidebar (sin contenedor, pero flotante en móvil) */
      #mailx .nav{
        position:fixed;
        inset:64px auto 0 0;
        width:320px;
        max-width:86vw;
        z-index:90;
        transform:translateX(-105%);
        transition:transform .22s var(--ease);
        height:calc(100vh - 64px);

        /* En móvil sí le damos base blanca para legibilidad */
        padding:14px;
        background:#fff;
        border-right:1px solid var(--line);
        box-shadow:0 18px 46px rgba(2,8,23,.15);
      }
      #mailx.nav-open .nav{ transform:translateX(0); }
      #mailx.nav-open .overlay{ display:block; }

      #mailx .preview{
        position:fixed;
        inset:64px 0 0 0;
        z-index:70;
        border-radius:18px 18px 0 0;
        transform:translateY(100%);
        transition:transform .25s var(--ease);
        box-shadow:0 -18px 48px rgba(2,8,23,.18);
      }
      #mailx .preview.is-open{ transform:translateY(0); }

      #mailx .quick{ opacity:1; pointer-events:auto; position:static; transform:none; margin-left:auto; }
      #mailx .row{ grid-template-columns:36px 1fr; }
      #mailx .meta{ display:none; }
    }
  </style>

  @php
    $folderParam = $current ?? request()->route('folder') ?? request('folder') ?? env('IMAP_DEFAULT_FOLDER','INBOX');
    $folderName  = strtoupper($folderParam);

    $FOLDERS = [
      ['key'=>'INBOX',    'icon'=>'inbox',   'text'=>'Bandeja de entrada'],
      ['key'=>'PRIORITY', 'icon'=>'star',    'text'=>'Prioritarios'],
      ['key'=>'DRAFTS',   'icon'=>'drafts',  'text'=>'Borradores'],
      ['key'=>'SENT',     'icon'=>'send',    'text'=>'Enviados'],
      ['key'=>'ARCHIVE',  'icon'=>'archive', 'text'=>'Archivo'],
      ['key'=>'OUTBOX',   'icon'=>'outbox',  'text'=>'Bandeja de salida'],
      ['key'=>'SPAM',     'icon'=>'report',  'text'=>'Correo no deseado'],
      ['key'=>'TRASH',    'icon'=>'delete',  'text'=>'Papelera'],
    ];

    $counts = $counts ?? [];
    $countOf = fn($k)=> $counts[$k] ?? 0;

    $decode = function (?string $v): string {
      if (!$v) return '';
      if (function_exists('iconv_mime_decode')) { $d=@iconv_mime_decode($v,0,'UTF-8'); if($d!==false) return $d; }
      if (function_exists('mb_decode_mimeheader')) { $d=@mb_decode_mimeheader($v); if(is_string($d)&&$d!=='') return $d; }
      if (preg_match_all('/=\?([^?]+)\?(Q|B)\?([^?]+)\?=/i', $v, $mm, PREG_SET_ORDER)) {
        foreach ($mm as $p) { [$full,$cs,$mode,$data]=$p;
          $data = strtoupper($mode)==='B' ? base64_decode($data) : quoted_printable_decode(str_replace('_',' ',$data));
          $v=str_replace($full,$data,$v);
        }
      }
      return $v;
    };

    $rows = collect($messages ?? [])->map(function($m) use ($decode, $folderName){
      $fromObj  = optional($m->getFrom())->first();
      $rawFrom  = $fromObj?->personal ?: $fromObj?->mail ?: '(desconocido)';
      $from     = $decode($rawFrom);
      $subject  = $decode($m->getSubject() ?: '(sin asunto)');
      $hasAtt   = $m->hasAttachments();
      $seen     = $m->hasFlag('Seen');
      $flagged  = $m->hasFlag('Flagged');

      $dateHeader = optional($m->get('date'))->first();
      $dateVal    = method_exists($dateHeader,'getValue') ? (string)$dateHeader->getValue() : null;
      $dateTxt    = ''; $dateTs=null; $dateIso=null; $dateFull=null;
      try{
        if($dateVal){
          $dt=\Carbon\Carbon::parse($dateVal)->locale('es');
          $dateTxt  = $dt->isoFormat('DD MMM HH:mm');
          $dateIso  = $dt->toIso8601String();
          $dateFull = $dt->translatedFormat('d \\de M Y, H:i:s');
          $dateTs   = $dt->timestamp;
        }
      }catch(\Throwable $e){}

      $bodySample = $m->hasHTMLBody() ? strip_tags($m->getHTMLBody()) : $m->getTextBody();
      $snippet = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/',' ', $bodySample ?? '')), 140);

      $kind = ($folderName==='SENT') ? 'Enviado' : 'Recibido';

      return [
        'uid'=>$m->getUid(),
        'from'=>$from,
        'subject'=>$subject,
        'snippet'=>$snippet,
        'dateTxt'=>$dateTxt,
        'dateIso'=>$dateIso,
        'dateFull'=>$dateFull,
        'kind'=>$kind,
        'dateTs'=>$dateTs,
        'hasAtt'=>$hasAtt,
        'seen'=>$seen,
        'flagged'=>$flagged,
        'priority'=>$flagged?1:0,
        'showUrl'=>route('mail.show',[$folderName,$m->getUid()]).'?partial=1',
        'flagUrl'=>route('mail.toggleFlag',[$folderName,$m->getUid()]),
        'readUrl'=>route('mail.markRead',[$folderName,$m->getUid()]),
      ];
    });

    $now = \Carbon\Carbon::now();
    $grouped = [
      'Hoy'         => $rows->filter(fn($r)=> $r['dateTs'] && \Carbon\Carbon::createFromTimestamp($r['dateTs'])->isSameDay($now)),
      'Ayer'        => $rows->filter(fn($r)=> $r['dateTs'] && \Carbon\Carbon::createFromTimestamp($r['dateTs'])->isYesterday()),
      'Esta semana' => $rows->filter(fn($r)=> $r['dateTs'] && \Carbon\Carbon::createFromTimestamp($r['dateTs'])->isSameWeek($now)),
      'Anteriores'  => $rows->filter(fn($r)=> $r['dateTs'] && \Carbon\Carbon::createFromTimestamp($r['dateTs'])->lt($now->startOfWeek())),
    ];
  @endphp

  <div class="overlay" id="mx-overlay"></div>

  <div class="wrap" data-folder="{{ $folderName }}">
    {{-- NAV (SIN contenedor) --}}
    <aside class="nav" id="mx-nav" aria-label="Carpetas">
      <div class="compose">
        <a class="btn primary" href="{{ route('mail.compose') }}">
          <span class="material-symbols-outlined">edit</span>
          <span class="txt">Redactar</span>
        </a>
      </div>

      <div class="folders" id="mx-folders">
        @foreach($FOLDERS as $f)
          @php $isActive = $folderName === $f['key']; @endphp
          <a class="folder {{ $isActive ? 'active':'' }}" href="{{ route('mail.folder',['folder'=>$f['key']]) }}" data-key="{{ $f['key'] }}">
            <div class="l">
              <span class="material-symbols-outlined">{{ $f['icon'] }}</span>
              <span class="text">{{ $f['text'] }}</span>
            </div>
            @php $c = $countOf($f['key']); @endphp
            @if($c>0)
              <span class="badge" data-count="{{ $f['key'] }}">{{ $c }}</span>
            @else
              <span class="badge" data-count="{{ $f['key'] }}" style="display:none">0</span>
            @endif
          </a>
        @endforeach
      </div>
    </aside>

    {{-- LISTA --}}
    <section class="panel list" id="mx-list">
      <div class="top">
        <button class="iconbtn" id="mx-toggle-nav" title="Carpetas">
          <span class="material-symbols-outlined">menu</span>
        </button>

        <div class="title">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1,'wght' 700">mail</span>
          <span id="mx-folder-title">
            @if($folderName==='PRIORITY') Prioritarios
            @elseif($folderName==='INBOX') Bandeja de entrada
            @elseif($folderName==='SPAM') Correo no deseado
            @elseif($folderName==='DRAFTS') Borradores
            @elseif($folderName==='SENT') Enviados
            @elseif($folderName==='ARCHIVE') Archivo
            @elseif($folderName==='OUTBOX') Bandeja de salida
            @elseif($folderName==='TRASH') Papelera
            @else {{ $folderName }}
            @endif
          </span>
        </div>

        <label class="search" role="search" aria-label="Buscar correo">
          <span class="material-symbols-outlined">search</span>
          <input id="mx-search" type="text" placeholder="Buscar remitente, asunto o contenido…"/>
        </label>

        <button class="iconbtn" id="mx-refresh" title="Actualizar">
          <span class="material-symbols-outlined">refresh</span>
        </button>
      </div>

      <div class="filters">
        <button class="chip active" data-filter="all"><span class="material-symbols-outlined">inbox</span> Todo</button>
        <button class="chip" data-filter="unread"><span class="material-symbols-outlined">mark_email_unread</span> No leídos</button>
        <button class="chip" data-filter="priority"><span class="material-symbols-outlined">star</span> Prioritarios</button>
        <button class="chip" data-filter="attach"><span class="material-symbols-outlined">attach_file</span> Con adjuntos</button>
      </div>

      <div class="groups" id="mx-groups">
        @foreach($grouped as $gTitle => $items)
          @if($items->count())
            <div class="group" data-group="{{ $gTitle }}">
              <div class="group-title">{{ $gTitle }}</div>
              @foreach($items as $r)
                <article class="row mx-item {{ $r['seen'] ? 'read' : '' }}"
                  data-uid="{{ $r['uid'] }}"
                  data-priority="{{ $r['priority'] ? '1':'0' }}"
                  data-hasatt="{{ $r['hasAtt'] ? '1':'0' }}"
                  data-flag="{{ $r['flagUrl'] }}"
                  data-read="{{ $r['readUrl'] }}"
                  data-show="{{ $r['showUrl'] }}"
                >
                  <div class="star" title="Marcar prioritario">
                    <button class="mx-flag" aria-label="Prioritario">
                      <span class="material-symbols-outlined" style="color:{{ $r['flagged'] ? '#f59e0b':'#9aa3af' }}">{{ $r['flagged'] ? 'star' : 'star_rate' }}</span>
                    </button>
                  </div>

                  <div>
                    <div class="from">{{ $r['from'] }}</div>
                    <div class="subject">{{ $r['subject'] }}</div>
                    <div class="snippet">
                      @if($r['hasAtt'])
                        <span class="chip" style="padding:3px 8px"><span class="material-symbols-outlined" style="font-size:16px">attach_file</span>Adjuntos</span>
                      @endif
                      {{ $r['snippet'] }}
                    </div>
                  </div>

                  <div class="meta">
                    <time datetime="{{ $r['dateIso'] }}" title="{{ $r['kind'] }}: {{ $r['dateFull'] }}">{{ $r['dateTxt'] }}</time>
                    <button class="mx-read" title="Marcar leído" style="all:unset; cursor:pointer">
                      <span class="material-symbols-outlined">done_all</span>
                    </button>
                  </div>

                  <div class="quick">
                    <button class="qbtn mx-archive" title="Archivar"><span class="material-symbols-outlined">archive</span></button>
                    <button class="qbtn mx-delete"  title="Eliminar"><span class="material-symbols-outlined">delete</span></button>
                  </div>
                </article>
              @endforeach
            </div>
          @endif
        @endforeach
      </div>
    </section>

    {{-- PREVIEW --}}
    <section class="panel preview" id="mx-preview">
      <div class="head">
        <div class="subject">Previsualización</div>
        <div class="meta" id="mx-meta" style="display:none"></div>
        <div class="actions" id="mx-actions" style="display:none">
          <form id="mx-form-read" method="POST" style="display:inline">@csrf
            <button class="btn"><span class="material-symbols-outlined">done_all</span> Marcar leído</button>
          </form>
          <a class="btn primary" href="#" id="mx-reply"><span class="material-symbols-outlined">reply</span> Responder</a>
          <a class="btn" href="#" id="mx-forward"><span class="material-symbols-outlined">forward</span> Reenviar</a>
        </div>
      </div>
      <div class="body" id="mx-body"><div class="empty">Selecciona un correo de la lista</div></div>
      <div class="attachments" id="mx-atts" style="display:none"></div>
    </section>
  </div>

  <script>
  (function(){
    const root     = document.querySelector('#mailx');
    const wrap     = root.querySelector('.wrap');
    const listWrap = root.querySelector('#mx-groups');
    const searchEl = root.querySelector('#mx-search');
    const chips    = root.querySelectorAll('.chip[data-filter]');
    const pane     = root.querySelector('#mx-preview');
    const subjectEl= pane.querySelector('.subject');
    const metaEl   = pane.querySelector('#mx-meta');
    const actionsEl= pane.querySelector('#mx-actions');
    const bodyEl   = pane.querySelector('#mx-body');
    const attsEl   = pane.querySelector('#mx-atts');
    const formRead = pane.querySelector('#mx-form-read');
    const btnForce = root.querySelector('#mx-refresh');
    const navBtn   = root.querySelector('#mx-toggle-nav');
    const overlay  = root.querySelector('#mx-overlay');

    const folderBadges = root.querySelectorAll('[data-count]');
    const folderTitle  = root.querySelector('#mx-folder-title');

    let folder   = wrap.dataset.folder || 'INBOX';
    let maxUid   = 0;
    let currentRow = null;
    let filterKind = 'all';
    let currentQuery = '';

    const URL_WAIT   = `{{ route('mail.api.wait') }}`;
    const URL_LIST   = `{{ route('mail.api.messages') }}`;
    const URL_COUNTS = `{{ route('mail.api.counts') }}`;
    const URL_MOVE_T = `{{ route('mail.move',   ['folder'=>'__F__','uid'=>'__U__']) }}`;
    const URL_DEL_T  = `{{ route('mail.delete', ['folder'=>'__F__','uid'=>'__U__']) }}`;

    function tpl(url, f, u){ return url.replace('__F__', encodeURIComponent(f)).replace('__U__', encodeURIComponent(u)); }
    function esc(s){ return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])) }

    let waitAbort = null;
    function abortWait(){ if(waitAbort){ waitAbort.abort(); waitAbort=null; } }

    function isMobile(){ return window.matchMedia('(max-width:1280px)').matches; }
    function openNav(){ root.classList.add('nav-open'); }
    function closeNav(){ root.classList.remove('nav-open'); }
    navBtn?.addEventListener('click', ()=>{ if (isMobile()) root.classList.toggle('nav-open'); });
    overlay?.addEventListener('click', closeNav);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape'){ closeNav(); pane.classList.remove('is-open'); } });

    function applyFilter(){
      listWrap.querySelectorAll('.mx-item').forEach(it=>{
        const unread = !it.classList.contains('read');
        const prio   = it.dataset.priority==='1';
        const att    = it.dataset.hasatt==='1';
        let show = true;
        if(filterKind==='unread')  show = unread;
        if(filterKind==='priority')show = prio;
        if(filterKind==='attach')  show = att;
        it.style.display = show ? '' : 'none';
      });
    }
    chips.forEach(ch=> ch.addEventListener('click', ()=>{
      chips.forEach(c=>c.classList.remove('active')); ch.classList.add('active');
      filterKind = ch.dataset.filter; applyFilter();
      triggerServerReload();
    }));

    async function post(url, bodyObj=null){
      try{
        const res = await fetch(url, {
          method:'POST',
          headers:{
            'X-Requested-With':'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
            ...(bodyObj ? {'Content-Type':'application/json'} : {})
          },
          body: bodyObj ? JSON.stringify(bodyObj) : null
        });
        if (!res.ok) return null;
        const ct = res.headers.get('content-type') || '';
        return ct.includes('json') ? await res.json() : {ok:true};
      }catch(e){ console.error(e); return null; }
    }

    function setLoadingPreview(){
      bodyEl.innerHTML = `<div style="padding:18px">
        <div style="height:14px;border-radius:10px;background:#e9effc;margin:10px 0;width:60%"></div>
        <div style="height:14px;border-radius:10px;background:#e9effc;margin:10px 0;width:40%"></div>
        <div style="height:14px;border-radius:10px;background:#e9effc;margin:10px 0;width:90%"></div>
        <div style="height:14px;border-radius:10px;background:#e9effc;margin:10px 0;width:75%"></div>
      </div>`;
      subjectEl.textContent = 'Cargando…';
      metaEl.style.display='none';
      actionsEl.style.display='none';
      attsEl.style.display='none';
    }

    async function openPreview(row){
      currentRow = row;
      listWrap.querySelectorAll('.mx-item').forEach(it => it.classList.remove('is-selected'));
      row.classList.add('is-selected');
      setLoadingPreview();

      if (isMobile()){
        pane.classList.add('is-open');
        closeNav();
      }

      try{
        const res = await fetch(row.dataset.show, { headers:{'X-Requested-With':'XMLHttpRequest'} });
        const html = await res.text();
        const tmp = document.createElement('div'); tmp.innerHTML = html;
        const payload = tmp.querySelector('#mx-payload');
        if(!payload){
          subjectEl.textContent='(sin contenido)';
          bodyEl.innerHTML='<div class="empty">No se pudo cargar el contenido.</div>';
          return;
        }

        subjectEl.textContent = payload.dataset.subject || '(sin asunto)';
        const from = payload.dataset.from || '(desconocido)';
        const to   = payload.dataset.to   || '';
        const cc   = payload.dataset.cc   || '';
        const when = payload.dataset.when || '';

        metaEl.innerHTML = `
          <div><strong>De:</strong> ${esc(from)}</div>
          ${to ? `<div><strong>Para:</strong> ${esc(to)}</div>`:''}
          ${cc ? `<div><strong>CC:</strong> ${esc(cc)}</div>`:''}
          ${when ? `<div>· ${esc(when)}</div>`:''}
        `;
        metaEl.style.display='flex';

        formRead.setAttribute('action', row.dataset.read);
        actionsEl.style.display='flex';

        const htmlBody = payload.querySelector('[data-body-html]');
        const textBody = payload.querySelector('[data-body-text]');
        bodyEl.innerHTML = '';
        if (htmlBody) bodyEl.appendChild(htmlBody.cloneNode(true));
        else if (textBody){
          const pre=document.createElement('pre');
          pre.style.whiteSpace='pre-wrap';
          pre.style.fontFamily='inherit';
          pre.textContent=textBody.textContent||'';
          bodyEl.appendChild(pre);
        } else {
          bodyEl.innerHTML = '<div class="empty">Sin contenido</div>';
        }

        const atts = payload.querySelectorAll('[data-att]');
        if (atts.length){
          const frag = document.createDocumentFragment();
          const title = document.createElement('div');
          title.style.fontWeight='800';
          title.style.marginBottom='8px';
          title.textContent = `Adjuntos (${atts.length})`;
          frag.appendChild(title);

          atts.forEach(a=>{
            const r = document.createElement('div');
            r.className = 'attachment';
            r.innerHTML = `<span class="material-symbols-outlined">attachment</span>
              <a href="${a.dataset.href}">${esc(a.dataset.name||'archivo')}</a>
              <span style="color:var(--muted);font-size:.86rem">· ${esc(a.dataset.mime||'')}</span>`;
            frag.appendChild(r);
          });
          attsEl.innerHTML='';
          attsEl.appendChild(frag);
          attsEl.style.display='block';
        } else attsEl.style.display='none';

        await post(row.dataset.read);
        row.classList.add('read');
      }catch(err){
        console.error(err);
        subjectEl.textContent='Error';
        bodyEl.innerHTML='<div class="empty">No se pudo cargar el mensaje.</div>';
      }
    }

    listWrap.addEventListener('click', async (e)=>{
      const row = e.target.closest('.mx-item');
      if (!row) return;

      if (e.target.closest('.mx-flag')) {
        await post(row.dataset.flag);
        const icon = row.querySelector('.mx-flag .material-symbols-outlined');
        const on = icon.textContent.trim()==='star';
        icon.textContent = on ? 'star_rate' : 'star';
        icon.style.color = on ? '#9aa3af' : '#f59e0b';
        row.dataset.priority = on ? '0' : '1';
        return;
      }
      if (e.target.closest('.mx-read')) {
        await post(row.dataset.read);
        row.classList.add('read');
        return;
      }
      if (e.target.closest('.mx-archive')) {
        await post(tpl(URL_MOVE_T, folder, row.dataset.uid), { dest: 'ARCHIVE' });
        row.remove(); updateCounts(); return;
      }
      if (e.target.closest('.mx-delete')) {
        await post(tpl(URL_DEL_T, folder, row.dataset.uid));
        row.remove(); updateCounts(); return;
      }
      openPreview(row);
    });

    formRead?.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const url = formRead.getAttribute('action');
      await post(url);
      if (currentRow) currentRow.classList.add('read');
    });

    function buildListURL(base, withLimit=true){
      const url = new URL(base);
      url.searchParams.set('folder', folder);
      if (withLimit) url.searchParams.set('limit', '80');
      if (currentQuery.trim()!=='') url.searchParams.set('q', currentQuery.trim());
      if (filterKind==='attach')   url.searchParams.set('only_with_attachments', '1');
      if (filterKind==='priority' && folder!=='PRIORITY') url.searchParams.set('priority_only', '1');
      return url;
    }

    function setFolderTitle(key){
      const map = {INBOX:'Bandeja de entrada',PRIORITY:'Prioritarios',SPAM:'Correo no deseado',DRAFTS:'Borradores',SENT:'Enviados',ARCHIVE:'Archivo',OUTBOX:'Bandeja de salida',TRASH:'Papelera'};
      folderTitle.textContent = map[key] || key;
    }

    function renderFull(items){
      const groups = { 'Hoy':[], 'Ayer':[], 'Esta semana':[], 'Anteriores':[] };
      const now = new Date();
      items.forEach(r=>{
        const ts = r.dateTs ? (r.dateTs*1000) : null;
        let key = 'Hoy';
        if (ts){
          const d = new Date(ts);
          const isSameDay = d.toDateString()===now.toDateString();
          const yd = new Date(now); yd.setDate(now.getDate()-1);
          const isYesterday = d.toDateString()===yd.toDateString();
          const ws = new Date(now); ws.setDate(now.getDate() - ((now.getDay()+6)%7));
          key = isSameDay ? 'Hoy' : isYesterday ? 'Ayer' : (d>=ws ? 'Esta semana' : 'Anteriores');
        }
        groups[key].push(r);
      });

      listWrap.innerHTML='';
      Object.keys(groups).forEach(title=>{
        const arr = groups[title];
        if (!arr.length) return;

        const g = document.createElement('div');
        g.className='group'; g.setAttribute('data-group', title);
        g.innerHTML = `<div class="group-title">${title}</div>`;

        arr.forEach(r=>{
          const kind = (folder==='SENT') ? 'Enviado' : 'Recibido';
          const tooltip = `${kind}: ${r.dateTxt||''}`;

          const art = document.createElement('article');
          art.className = 'row mx-item ' + (r.seen ? 'read':'');

          art.dataset.uid=r.uid;
          art.dataset.priority=r.priority?'1':'0';
          art.dataset.hasatt=r.hasAtt?'1':'0';
          art.dataset.flag=r.flagUrl;
          art.dataset.read=r.readUrl;
          art.dataset.show=r.showUrl;

          art.innerHTML = `
            <div class="star">
              <button class="mx-flag" aria-label="Prioritario">
                <span class="material-symbols-outlined" style="color:${r.flagged ? '#f59e0b':'#9aa3af'}">${r.flagged ? 'star' : 'star_rate'}</span>
              </button>
            </div>
            <div>
              <div class="from">${esc(r.from)}</div>
              <div class="subject">${esc(r.subject)}</div>
              <div class="snippet">
                ${r.hasAtt ? `<span class="chip" style="padding:3px 8px"><span class="material-symbols-outlined" style="font-size:16px">attach_file</span>Adjuntos</span>` : ''}
                ${esc(r.snippet || '')}
              </div>
            </div>
            <div class="meta">
              <time title="${esc(tooltip)}">${esc(r.dateTxt || '')}</time>
              <button class="mx-read" title="Marcar leído" style="all:unset; cursor:pointer">
                <span class="material-symbols-outlined">done_all</span>
              </button>
            </div>
            <div class="quick">
              <button class="qbtn mx-archive" title="Archivar"><span class="material-symbols-outlined">archive</span></button>
              <button class="qbtn mx-delete"  title="Eliminar"><span class="material-symbols-outlined">delete</span></button>
            </div>
          `;
          g.appendChild(art);
        });

        listWrap.appendChild(g);
      });

      maxUid = 0;
      listWrap.querySelectorAll('.mx-item').forEach(it=>{
        const v=parseInt(it.dataset.uid||'0',10);
        if(v>maxUid) maxUid=v;
      });
      applyFilter();
    }

    async function triggerServerReload(){
      const url = buildListURL(URL_LIST, true);
      const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if (!res.ok) return;
      const data = await res.json();
      renderFull(data.items||[]);
      updateCounts();
    }

    const foldersEl = root.querySelector('#mx-folders');
    foldersEl.addEventListener('click', async (e)=>{
      const a = e.target.closest('a.folder');
      if (!a) return;
      e.preventDefault();

      const key = a.dataset.key;
      if (!key || key===folder) { closeNav(); return; }

      root.querySelectorAll('.folder').forEach(f=>f.classList.remove('active'));
      a.classList.add('active');

      abortWait();
      folder = key;
      setFolderTitle(folder);

      bodyEl.innerHTML = '<div class="empty">Selecciona un correo de la lista</div>';
      actionsEl.style.display='none';
      metaEl.style.display='none';
      attsEl.style.display='none';

      await triggerServerReload();

      if (window.history && window.history.pushState) {
        window.history.pushState({}, '', `{{ route('mail.index') }}?folder=${encodeURIComponent(folder)}`);
      }

      closeNav();
      startWaitLoop();
    });

    let searchDebounce=null;
    searchEl?.addEventListener('input', (e)=>{
      currentQuery = e.target.value || '';
      if (searchDebounce) clearTimeout(searchDebounce);
      searchDebounce = setTimeout(triggerServerReload, 250);
    });

    btnForce?.addEventListener('click', async (e)=>{
      e.preventDefault();
      await triggerServerReload();
    });

    async function updateCounts(){
      try{
        const res = await fetch(URL_COUNTS, { headers:{'X-Requested-With':'XMLHttpRequest'} });
        if(!res.ok) return;
        const data = await res.json();
        folderBadges.forEach(b=>{
          const key = b.getAttribute('data-count');
          const val = data[key] ?? 0;
          b.textContent = val;
          b.style.display = val>0 ? '' : 'none';
        });
      }catch(e){}
    }

    async function waitLoop(signal){
      try{
        const url = buildListURL(URL_WAIT, false);
        url.searchParams.set('after_uid', String(maxUid||0));
        url.searchParams.set('timeout', '25');
        url.searchParams.set('tick', '3');
        url.searchParams.set('limit', '120');

        const res = await fetch(url, { headers:{'X-Requested-With':'XMLHttpRequest'}, signal });
        if (res.ok){
          const data = await res.json();
          if (currentQuery.trim()!=='') {
            renderFull(data.items||[]);
          } else if (data.items && data.items.length){
            let group = listWrap.querySelector('[data-group="Hoy"]');
            if(!group){
              const g=document.createElement('div');
              g.className='group'; g.setAttribute('data-group','Hoy');
              g.innerHTML='<div class="group-title">Hoy</div>';
              listWrap.prepend(g);
              group=g;
            }
            const title = group.querySelector('.group-title');
            data.items.forEach(r=>{
              if (listWrap.querySelector(`.mx-item[data-uid="${r.uid}"]`)) return;

              const kind = (folder==='SENT') ? 'Enviado' : 'Recibido';
              const tooltip = `${kind}: ${r.dateTxt||''}`;

              const art = document.createElement('article');
              art.className='row mx-item '+(r.seen?'read':'');
              art.dataset.uid=r.uid; art.dataset.priority=r.priority?'1':'0'; art.dataset.hasatt=r.hasAtt?'1':'0';
              art.dataset.flag=r.flagUrl; art.dataset.read=r.readUrl; art.dataset.show=r.showUrl;

              art.innerHTML = `
                <div class="star">
                  <button class="mx-flag" aria-label="Prioritario">
                    <span class="material-symbols-outlined" style="color:${r.flagged ? '#f59e0b':'#9aa3af'}">${r.flagged ? 'star' : 'star_rate'}</span>
                  </button>
                </div>
                <div>
                  <div class="from">${esc(r.from)}</div>
                  <div class="subject">${esc(r.subject)}</div>
                  <div class="snippet">
                    ${r.hasAtt ? `<span class="chip" style="padding:3px 8px"><span class="material-symbols-outlined" style="font-size:16px">attach_file</span>Adjuntos</span>` : ''}
                    ${esc(r.snippet||'')}
                  </div>
                </div>
                <div class="meta">
                  <time title="${esc(tooltip)}">${esc(r.dateTxt||'')}</time>
                  <button class="mx-read" title="Marcar leído" style="all:unset; cursor:pointer">
                    <span class="material-symbols-outlined">done_all</span>
                  </button>
                </div>
                <div class="quick">
                  <button class="qbtn mx-archive" title="Archivar"><span class="material-symbols-outlined">archive</span></button>
                  <button class="qbtn mx-delete"  title="Eliminar"><span class="material-symbols-outlined">delete</span></button>
                </div>
              `;
              title.after(art);
            });

            maxUid = Math.max(maxUid||0, data.max_uid||0);
            applyFilter();
          }
          updateCounts();
        }
      }catch(e){
        if (e.name!=='AbortError') console.warn('waitLoop error', e);
      }
      if (!signal.aborted) startWaitLoop();
    }

    function startWaitLoop(){
      abortWait();
      waitAbort = new AbortController();
      waitLoop(waitAbort.signal);
    }

    listWrap.querySelectorAll('.mx-item').forEach(it=>{
      const v=parseInt(it.dataset.uid||'0',10);
      if(v>maxUid) maxUid=v;
    });
    const first = listWrap.querySelector('.mx-item');
    if (first) first.click();
    startWaitLoop();

    window.addEventListener('popstate', async ()=>{
      const urlp = new URL(window.location.href);
      const f = (urlp.searchParams.get('folder')||'INBOX').toUpperCase();
      const a = root.querySelector(`a.folder[data-key="${f}"]`);
      if (a) a.click();
    });
  })();
  </script>
</div>
@endsection
