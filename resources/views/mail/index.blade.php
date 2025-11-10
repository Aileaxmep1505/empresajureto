{{-- resources/views/mail/index.blade.php --}}
@extends('layouts.app')
@section('title','Correo')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300..700&display=swap"/>

<div id="mailx">
  <style>
    /* =============== MailX UI (Pastel Pro + Performance) =============== */
    #mailx{
      --ink:#0f172a; --muted:#667085; --line:#e8eef6;
      --bg:#f6f8fc; --card:#fff; --brand:#6ea8fe; --brand-ink:#0b1220;
      --chip:#eef4ff; --hover:#f6f9ff; --ring:#cfe0ff; --warn:#f59e0b;
      --shadow:0 20px 48px rgba(2,8,23,.08);
      font-family:'Outfit', system-ui, -apple-system, "Segoe UI", Roboto, Arial;
      color:var(--ink);
      background:
        radial-gradient(900px 520px at -10% -15%, #eaf2ff66, transparent 60%),
        radial-gradient(900px 520px at 110% 110%, #eaf2ff33, transparent 60%),
        var(--bg);
      min-height:calc(100vh - 64px);
    }
    #mailx .wrap{ max-width:1600px; margin:0 auto; padding:16px; display:grid; grid-template-columns: 280px 480px 1fr; gap:16px; }
    #mailx .panel{ background:var(--card); border:1px solid var(--line); border-radius:18px; box-shadow:var(--shadow); overflow:hidden }

    /* NAV */
    #mailx .nav{ position:sticky; top:16px; height:calc(100vh - 32px); display:flex; flex-direction:column; }
    #mailx .nav .brand{ display:flex; align-items:center; gap:10px; padding:14px; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#f9fbff) }
    #mailx .nav .menu{ width:38px;height:38px;border-radius:12px;border:1px solid var(--line);display:grid;place-items:center; cursor:pointer; transition:.18s }
    #mailx .nav .menu:hover{ background:#f3f6fc }
    #mailx .brand-title{ font-weight:800; letter-spacing:.2px }
    #mailx .compose{ margin:12px 12px 2px; display:flex; gap:8px; }
    #mailx .btn{ appearance:none; border:1px solid #dfe6fa; background:#f7f9ff; color:#0b1220; padding:10px 12px; border-radius:14px; display:inline-flex; gap:8px; align-items:center; font-weight:700; cursor:pointer; text-decoration:none; transition:.18s transform }
    #mailx .btn:hover{ transform:translateY(-1px) }
    #mailx .btn.primary{ background:var(--brand); border-color:var(--brand); color:var(--brand-ink) }
    #mailx .folders{ padding:8px 8px 12px; overflow:auto; flex:1 }
    #mailx .folder{ display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px; border-radius:12px; color:inherit; text-decoration:none; }
    #mailx .folder:hover{ background:var(--hover) }
    #mailx .folder.active{ background:linear-gradient(180deg,#eef5ff,#fff); border:1px solid var(--line) }
    #mailx .folder .l{ display:flex; align-items:center; gap:10px; font-weight:600 }
    #mailx .badge{ min-width:22px; height:22px; padding:0 6px; border-radius:999px; display:grid; place-items:center; font-size:.78rem; background:#eef4ff; border:1px solid var(--line) }
    #mailx .meta-nav{ padding:10px; border-top:1px dashed var(--line); color:var(--muted); font-size:.9rem }

    #mailx.nav-collapsed .wrap{ grid-template-columns: 84px 520px 1fr }
    #mailx.nav-collapsed .brand-title, #mailx.nav-collapsed .compose .txt, #mailx.nav-collapsed .folder .text, #mailx.nav-collapsed .meta-nav { display:none }
    #mailx.nav-collapsed .compose{ justify-content:center }
    #mailx.nav-collapsed .folder{ justify-content:center }

    /* LISTA */
    #mailx .list{ display:flex; flex-direction:column; min-height:60vh }
    #mailx .list .top{ display:flex; align-items:center; gap:10px; padding:12px; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#f9fbff) }
    #mailx .search{ flex:1; display:flex; align-items:center; gap:8px; background:#f3f6fc; border:1px solid var(--line); border-radius:12px; padding:8px 10px }
    #mailx .search:focus-within{ box-shadow:0 0 0 3px var(--ring) }
    #mailx .search input{ all:unset; width:100%; color:var(--ink); font-weight:500 }
    #mailx .filters{ display:flex; gap:8px; padding:10px 12px; border-bottom:1px dashed var(--line); flex-wrap:wrap }
    #mailx .chip{ display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border:1px solid var(--line); border-radius:999px; background:var(--chip); font-size:.86rem; font-weight:700; cursor:pointer; transition:.18s }
    #mailx .chip.active{ background:var(--brand); border-color:var(--brand); color:var(--brand-ink) }

    #mailx .groups{ overflow:auto; max-height:calc(100vh - 240px) }
    #mailx .group-title{ position:sticky; top:0; background:#fff; z-index:2; padding:8px 12px; font-size:.86rem; color:var(--muted); border-bottom:1px solid var(--line) }

    #mailx .row{ position:relative; display:grid; grid-template-columns:32px 1fr auto; gap:12px; padding:14px 12px; border-bottom:1px solid var(--line); cursor:pointer; transition:background .15s ease }
    #mailx .row:hover{ background:var(--hover) }
    #mailx .row.read .from, #mailx .row.read .subject{ font-weight:600; opacity:.9 }
    #mailx .from{ font-weight:700 }
    #mailx .subject{ font-weight:700; margin-top:2px }
    #mailx .snippet{ color:var(--muted); margin-top:3px; font-size:.92rem }
    #mailx .meta{ display:flex; align-items:center; gap:8px; color:var(--muted); font-size:.86rem }
    #mailx .star button{ all:unset; cursor:pointer; display:grid; place-items:center; width:28px; height:28px; border-radius:10px }

    /* Acciones rápidas */
    #mailx .quick{ position:absolute; right:8px; top:50%; transform:translateY(-50%); display:flex; gap:6px; opacity:0; pointer-events:none; transition:.15s }
    #mailx .row:hover .quick{ opacity:1; pointer-events:auto; }
    #mailx .qbtn{ all:unset; cursor:pointer; display:grid; place-items:center; width:30px;height:30px; border:1px solid var(--line); border-radius:10px; background:#fff }

    /* PREVIEW */
    #mailx .preview{ display:grid; grid-template-rows:auto 1fr auto; min-height:60vh }
    #mailx .preview .head{ padding:14px 16px; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#f9fbff) }
    #mailx .preview .subject{ font-size:1.28rem; font-weight:800; line-height:1.18 }
    #mailx .preview .meta{ margin-top:6px; color:var(--muted); display:flex; gap:12px; flex-wrap:wrap }
    #mailx .preview .actions{ margin-top:10px; display:flex; gap:10px; flex-wrap:wrap }
    #mailx .preview .body{ padding:16px; min-height:52vh; overflow:auto }
    #mailx .attachments{ padding:12px 16px; border-top:1px dashed var(--line); background:#fbfdff }
    #mailx .attachment{ display:flex; align-items:center; gap:10px; padding:8px 0 }
    #mailx .empty{ display:flex; align-items:center; justify-content:center; height:calc(100vh - 240px); color:var(--muted); font-weight:700 }

    /* Skeleton */
    #mailx .skeleton{ animation:mxpulse 1.2s ease-in-out infinite; background:linear-gradient(90deg,#f1f5ff 25%,#e9effc 37%,#f1f5ff 63%); background-size:400% 100% }
    @keyframes mxpulse{ 0%{background-position:100% 0} 100%{background-position:-100% 0} }
    #mailx .loading{ padding:18px } .bar{ height:14px; border-radius:8px; margin:10px 0 }

    /* Mobile */
    @media (max-width:1280px){
      #mailx .wrap{ grid-template-columns: 84px 1fr; }
      #mailx .preview{
        position:fixed; inset:64px 0 0 0; z-index:50; background:#fff; border-radius:18px 18px 0 0;
        transform:translateY(100%); transition:transform .25s ease; box-shadow:0 -18px 48px rgba(2,8,23,.18)
      }
      #mailx .preview.is-open{ transform:translateY(0) }
    }
  </style>

  @php
    use Illuminate\Support\Str;

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
          $dateTxt  = $dt->isoFormat('DD MMM HH:mm');                 // visible
          $dateIso  = $dt->toIso8601String();                         // machine
          $dateFull = $dt->translatedFormat('d \\de M Y, H:i:s');     // tooltip
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

  <div class="wrap" data-folder="{{ $folderName }}">
    {{-- NAV --}}
    <aside class="panel nav" id="mx-nav">
      <div class="brand">
        <button class="menu" id="mx-toggle-nav" title="Ocultar/mostrar">
          <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="brand-title">Correo</div>
      </div>
      <div class="compose">
        <a class="btn primary" href="{{ route('mail.compose') }}">
          <span class="material-symbols-outlined">edit</span><span class="txt">Redactar</span>
        </a>
        <a class="btn" href="#" id="mx-refresh" title="Forzar sincronización">
          <span class="material-symbols-outlined">refresh</span>
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
            @if($c>0)<span class="badge" data-count="{{ $f['key'] }}">{{ $c }}</span>@else <span class="badge" data-count="{{ $f['key'] }}" style="display:none">0</span>@endif
          </a>
        @endforeach
      </div>
      <div class="meta-nav">⭐ Marca con prioritario para enviarlo a “Prioritarios”.</div>
    </aside>

    {{-- LISTA --}}
    <section class="panel list" id="mx-list">
      <div class="top">
        <div class="title" style="font-weight:800;display:flex;align-items:center;gap:8px">
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
        <label class="search" role="search">
          <span class="material-symbols-outlined">search</span>
          <input id="mx-search" type="text" placeholder="Buscar remitente, asunto o contenido…"/>
        </label>
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
    const btnReply = pane.querySelector('#mx-reply');
    const btnFwd   = pane.querySelector('#mx-forward');
    const btnForce = root.querySelector('#mx-refresh');
    const navBtn   = root.querySelector('#mx-toggle-nav');
    const folderBadges = root.querySelectorAll('[data-count]');
    const folderTitle  = root.querySelector('#mx-folder-title');

    let folder   = wrap.dataset.folder || 'INBOX';
    let maxUid   = 0;
    let currentRow = null;
    let filterKind = 'all';
    let currentQuery = '';

    // Endpoints
    const URL_WAIT   = `{{ route('mail.api.wait') }}`;
    const URL_LIST   = `{{ route('mail.api.messages') }}`;
    const URL_COUNTS = `{{ route('mail.api.counts') }}`;
    const URL_MOVE_T = `{{ route('mail.move',   ['folder'=>'__F__','uid'=>'__U__']) }}`;
    const URL_DEL_T  = `{{ route('mail.delete', ['folder'=>'__F__','uid'=>'__U__']) }}`;

    function tpl(url, f, u){ return url.replace('__F__', encodeURIComponent(f)).replace('__U__', encodeURIComponent(u)); }
    function esc(s){ return (s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m])) }

    // Abort long-poll on folder change
    let waitAbort = null;
    function abortWait(){ if(waitAbort){ waitAbort.abort(); waitAbort=null; } }

    // Sidebar persistencia
    try{ if(localStorage.getItem('mx_nav_collapsed')==='1') root.classList.add('nav-collapsed'); }catch(e){}
    navBtn?.addEventListener('click', ()=>{
      root.classList.toggle('nav-collapsed');
      try{ localStorage.setItem('mx_nav_collapsed', root.classList.contains('nav-collapsed') ? '1':'0'); }catch(e){}
    });

    // UI filter helper
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
      // disparar recarga desde servidor para filtros que lo requieren
      triggerServerReload();
    }));

    // POST helper
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
      bodyEl.innerHTML = `<div class="loading">
        <div class="bar skeleton" style="width:60%"></div>
        <div class="bar skeleton" style="width:40%"></div>
        <div class="bar skeleton" style="width:90%"></div>
        <div class="bar skeleton" style="width:75%"></div>
      </div>`;
      subjectEl.textContent = 'Cargando…'; metaEl.style.display='none'; actionsEl.style.display='none'; attsEl.style.display='none';
    }

    async function openPreview(row){
      currentRow = row;
      listWrap.querySelectorAll('.mx-item').forEach(it => it.classList.remove('is-selected'));
      row.classList.add('is-selected');
      setLoadingPreview();
      if (window.matchMedia('(max-width:1280px)').matches) pane.classList.add('is-open');

      try{
        const res = await fetch(row.dataset.show, { headers:{'X-Requested-With':'XMLHttpRequest'} });
        const html = await res.text();
        const tmp = document.createElement('div'); tmp.innerHTML = html;
        const payload = tmp.querySelector('#mx-payload');
        if(!payload){ subjectEl.textContent='(sin contenido)'; bodyEl.innerHTML='<div class="empty">No se pudo cargar el contenido.</div>'; return; }

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
        else if (textBody){ const pre=document.createElement('pre'); pre.textContent=textBody.textContent||''; bodyEl.appendChild(pre); }
        else bodyEl.innerHTML = '<div class="empty">Sin contenido</div>';

        const atts = payload.querySelectorAll('[data-att]');
        if (atts.length){
          const frag = document.createDocumentFragment();
          const title = document.createElement('div'); title.style.fontWeight='800'; title.style.marginBottom='8px'; title.textContent = `Adjuntos (${atts.length})`;
          frag.appendChild(title);
          atts.forEach(a=>{
            const r = document.createElement('div');
            r.className = 'attachment';
            r.innerHTML = `<span class="material-symbols-outlined">attachment</span><a href="${a.dataset.href}">${esc(a.dataset.name||'archivo')}</a><span style="color:var(--muted);font-size:.86rem">· ${esc(a.dataset.mime||'')}</span>`;
            frag.appendChild(r);
          });
          attsEl.innerHTML=''; attsEl.appendChild(frag); attsEl.style.display='block';
        } else attsEl.style.display='none';

        await post(row.dataset.read);
        row.classList.add('read');
      }catch(err){ console.error(err); subjectEl.textContent='Error'; bodyEl.innerHTML='<div class="empty">No se pudo cargar el mensaje.</div>'; }
    }

    // Delegado de clicks en lista
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

    // ===== Helpers para llamadas al servidor con filtros =====
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
          // Para items que vienen del API (no traen dateIso/kind), fabricamos tooltip simple
          const kind = (folder==='SENT') ? 'Enviado' : 'Recibido';
          const tooltip = `${kind}: ${r.dateTxt||''}`;
          const art = document.createElement('article');
          art.className = 'row mx-item ' + (r.seen ? 'read':'');
          art.dataset.uid=r.uid; art.dataset.priority=r.priority?'1':'0'; art.dataset.hasatt=r.hasAtt?'1':'0';
          art.dataset.flag=r.flagUrl; art.dataset.read=r.readUrl; art.dataset.show=r.showUrl;
          art.innerHTML = `
            <div class="star"><button class="mx-flag" aria-label="Prioritario"><span class="material-symbols-outlined" style="color:${r.flagged ? '#f59e0b':'#9aa3af'}">${r.flagged ? 'star' : 'star_rate'}</span></button></div>
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
              <button class="mx-read" title="Marcar leído" style="all:unset; cursor:pointer"><span class="material-symbols-outlined">done_all</span></button>
            </div>
            <div class="quick">
              <button class="qbtn mx-archive" title="Archivar"><span class="material-symbols-outlined">archive</span></button>
              <button class="qbtn mx-delete"  title="Eliminar"><span class="material-symbols-outlined">delete</span></button>
            </div>`;
          g.appendChild(art);
        });
        listWrap.appendChild(g);
      });
      // recalcular maxUid
      maxUid = 0;
      listWrap.querySelectorAll('.mx-item').forEach(it=>{ const v=parseInt(it.dataset.uid||'0',10); if(v>maxUid) maxUid=v; });
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

    // Clic en carpetas: navegación instantánea
    const foldersEl = root.querySelector('#mx-folders');
    foldersEl.addEventListener('click', async (e)=>{
      const a = e.target.closest('a.folder');
      if (!a) return;
      e.preventDefault();
      const key = a.dataset.key;
      if (!key || key===folder) return;

      root.querySelectorAll('.folder').forEach(f=>f.classList.remove('active'));
      a.classList.add('active');

      abortWait();
      folder = key;
      setFolderTitle(folder);
      bodyEl.innerHTML = '<div class="empty">Selecciona un correo de la lista</div>';
      actionsEl.style.display='none'; metaEl.style.display='none'; attsEl.style.display='none';

      await triggerServerReload();

      if (window.history && window.history.pushState) {
        window.history.pushState({}, '', `{{ route('mail.index') }}?folder=${encodeURIComponent(folder)}`);
      }
      startWaitLoop();
    });

    // Búsqueda (server-side) con debounce (INBOX + q ⇒ búsqueda global en backend)
    let searchDebounce=null;
    searchEl?.addEventListener('input', (e)=>{
      const q = e.target.value || '';
      currentQuery = q;
      if (searchDebounce) clearTimeout(searchDebounce);
      searchDebounce = setTimeout(triggerServerReload, 250);
    });

    // Forzar refresh
    btnForce?.addEventListener('click', async (e)=>{
      e.preventDefault();
      await triggerServerReload();
    });

    // Contadores
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

    // Long-polling con filtros
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
            // Inserta en “Hoy”
            let group = listWrap.querySelector('[data-group="Hoy"]');
            if(!group){
              const g=document.createElement('div');
              g.className='group'; g.setAttribute('data-group','Hoy');
              g.innerHTML='<div class="group-title">Hoy</div>'; listWrap.prepend(g); group=g;
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
                <div class="star"><button class="mx-flag" aria-label="Prioritario"><span class="material-symbols-outlined" style="color:${r.flagged ? '#f59e0b':'#9aa3af'}">${r.flagged ? 'star' : 'star_rate'}</span></button></div>
                <div>
                  <div class="from">${esc(r.from)}</div>
                  <div class="subject">${esc(r.subject)}</div>
                  <div class="snippet">${r.hasAtt?`<span class="chip" style="padding:3px 8px"><span class="material-symbols-outlined" style="font-size:16px">attach_file</span>Adjuntos</span>`:''} ${esc(r.snippet||'')}</div>
                </div>
                <div class="meta">
                  <time title="${esc(tooltip)}">${esc(r.dateTxt||'')}</time>
                  <button class="mx-read" title="Marcar leído" style="all:unset; cursor:pointer"><span class="material-symbols-outlined">done_all</span></button>
                </div>
                <div class="quick">
                  <button class="qbtn mx-archive" title="Archivar"><span class="material-symbols-outlined">archive</span></button>
                  <button class="qbtn mx-delete"  title="Eliminar"><span class="material-symbols-outlined">delete</span></button>
                </div>`;
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

    // Estado inicial
    listWrap.querySelectorAll('.mx-item').forEach(it=>{ const v=parseInt(it.dataset.uid||'0',10); if(v>maxUid) maxUid=v; });
    const first = listWrap.querySelector('.mx-item'); if (first) first.click();
    startWaitLoop();
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') pane.classList.remove('is-open'); });

    // Historial
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
