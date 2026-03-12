@extends('layouts.app')
@section('title', 'WhatsApp · Conversación #'.$conversation->id)

@section('content')
@php
    \Carbon\Carbon::setLocale('es');

    $statusLabel = function($status){
        return match(mb_strtolower((string)$status)){
            'open', 'abierta' => 'Abierta',
            'pending', 'pendiente' => 'Pendiente',
            'closed', 'cerrada' => 'Cerrada',
            default => ucfirst((string)$status ?: 'Sin estado'),
        };
    };

    $statusClass = function($status){
        return match(mb_strtolower((string)$status)){
            'open', 'abierta' => 'is-open',
            'pending', 'pendiente' => 'is-pending',
            'closed', 'cerrada' => 'is-closed',
            default => 'is-neutral',
        };
    };

    $initials = function($name, $phone = null){
        $name = trim((string)$name);
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name);
            $letters = '';
            foreach(array_slice($parts, 0, 2) as $p){
                $letters .= mb_strtoupper(mb_substr($p, 0, 1));
            }
            return $letters ?: 'WA';
        }
        return $phone ? mb_substr(preg_replace('/\D+/', '', $phone), -2) : 'WA';
    };

    $messageTypeLabel = function($type){
        return match(mb_strtolower((string)$type)){
            'text' => 'Texto',
            'image' => 'Imagen',
            'audio' => 'Audio',
            'voice' => 'Voz',
            'ptt' => 'Voz',
            'video' => 'Video',
            'document' => 'Documento',
            'file' => 'Archivo',
            'sticker' => 'Sticker',
            'location' => 'Ubicación',
            default => ucfirst((string)$type ?: 'Texto'),
        };
    };

    $messageStatusIcon = function($status, $direction = null){
        $status = mb_strtolower((string)$status);
        $direction = mb_strtolower((string)$direction);

        if (in_array($direction, ['inbound','in','incoming'])) return '';

        return match($status){
            'sent', 'enviado', 'accepted', 'aceptado' => '✓',
            'delivered', 'entregado' => '✓✓',
            'read', 'seen', 'leido', 'leído' => '✓✓',
            'failed', 'error', 'fallido' => '!',
            'pending', 'pendiente', 'queued' => '🕓',
            default => '✓',
        };
    };

    $messageStatusClass = function($status, $direction = null){
        $status = mb_strtolower((string)$status);
        $direction = mb_strtolower((string)$direction);

        if (in_array($direction, ['inbound','in','incoming'])) return 'is-inbound';

        return match($status){
            'sent', 'enviado', 'accepted', 'aceptado' => 'is-sent',
            'delivered', 'entregado' => 'is-delivered',
            'read', 'seen', 'leido', 'leído' => 'is-read',
            'failed', 'error', 'fallido' => 'is-failed',
            'pending', 'pendiente', 'queued' => 'is-pending',
            default => 'is-sent',
        };
    };

    $displayName = $conversation->user->name ?? ('Contacto '.$conversation->phone);

    $sortedMessages = collect($conversation->messages ?? [])->sortBy(function($m){
        return optional($m->created_at)?->timestamp ?? 0;
    });

    $groupMessagesByDate = [];
    foreach($sortedMessages as $msg){
        $key = optional($msg->created_at)?->format('Y-m-d') ?? 'sin-fecha';
        $groupMessagesByDate[$key][] = $msg;
    }

    $emojiList = [
        '😀','😃','😄','😁','😆','😅','😂','🤣','😊','🙂','😉','😍','🥰','😘','😗','😙','😚',
        '😋','😛','😜','🤪','😝','🫠','🤗','🤭','🤫','🤔','🫡','🤩','🥳','😎','🤓','🧐','😕',
        '😟','🙁','☹️','😮','😯','😲','😳','🥺','😢','😭','😤','😠','😡','🤯','😬','🙄','😴',
        '🤤','🤢','🤮','🤧','🥵','🥶','😇','🤠','🥸','😈','👻','💀','☠️','👽','🤖','🎃','😺',
        '😸','😹','😻','😼','🙈','🙉','🙊','💋','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎',
        '💔','❤️‍🔥','❤️‍🩹','❣️','💕','💞','💓','💗','💖','💘','💝','💯','💢','💥','💫','💦',
        '💨','🕳️','💬','🗨️','🗯️','💭','👍','👎','👌','✌️','🤞','🤟','🤘','🤙','👋','🫶','👏',
        '🙌','👐','🤲','🙏','✍️','💪','🦾','🫵','👀','🧠','🔥','✨','⭐','🌟','💡','🎉','🎊',
        '✅','❌','⚠️','🚀','🎁','📦','📄','📎','📁','💼','🛒','🖨️','🖥️','💻','📱','☎️','🎵',
        '🎧','🎤','📷','📹','🎬','📍','🗂️'
    ];
@endphp

<style>
  .wa-chat{
    --wa-bg:#0b141a;
    --wa-panel:#111b21;
    --wa-panel-2:#202c33;
    --wa-border:#2a3942;
    --wa-text:#e9edef;
    --wa-muted:#8696a0;
    --wa-soft:#202c33;
    --wa-accent:#00a884;
    --wa-accent-2:#25d366;
    --wa-in:#202c33;
    --wa-out:#005c4b;
    --wa-shadow:0 20px 60px rgba(0,0,0,.28);
    --wa-read:#53bdeb;
  }

  .wa-chat *{ box-sizing:border-box; }

  .wa-chat-wrap{
    max-width:1280px;
    margin:0 auto;
  }

  .wa-chat-app{
    background:var(--wa-bg);
    border-radius:20px;
    overflow:hidden;
    height:calc(100vh - 150px);
    min-height:560px;
    max-height:760px;
    border:1px solid rgba(255,255,255,.04);
    box-shadow:var(--wa-shadow);
    display:flex;
    flex-direction:column;
  }

  .wa-chat-head{
    background:var(--wa-panel);
    border-bottom:1px solid var(--wa-border);
    padding:12px 16px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    flex-wrap:wrap;
    flex:0 0 auto;
  }

  .wa-chat-user{
    display:flex;
    align-items:center;
    gap:12px;
    min-width:0;
  }

  .wa-avatar{
    width:44px;
    height:44px;
    border-radius:50%;
    display:grid;
    place-items:center;
    flex:0 0 44px;
    background:linear-gradient(135deg, #1f7ae0, #00a884);
    color:#fff;
    font-weight:800;
    font-size:.9rem;
    letter-spacing:.3px;
    box-shadow:0 6px 18px rgba(0,0,0,.18);
  }

  .wa-chat-meta{ min-width:0; }

  .wa-chat-name{
    color:var(--wa-text);
    font-size:1rem;
    font-weight:700;
    margin:0;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .wa-chat-sub{
    margin-top:2px;
    color:var(--wa-muted);
    font-size:.85rem;
    display:flex;
    align-items:center;
    gap:8px;
    flex-wrap:wrap;
  }

  .wa-status{
    font-size:.72rem;
    padding:4px 8px;
    border-radius:999px;
    font-weight:700;
    border:1px solid transparent;
    white-space:nowrap;
  }

  .wa-status.is-open{
    background:rgba(37,211,102,.12);
    color:#7df0a7;
    border-color:rgba(37,211,102,.18);
  }

  .wa-status.is-pending{
    background:rgba(255,193,7,.12);
    color:#ffd76b;
    border-color:rgba(255,193,7,.18);
  }

  .wa-status.is-closed,
  .wa-status.is-neutral{
    background:rgba(255,255,255,.06);
    color:#c5d1d7;
    border-color:rgba(255,255,255,.08);
  }

  .wa-chat-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
  }

  .wa-btn{
    height:40px;
    border:none;
    border-radius:10px;
    padding:0 14px;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    text-decoration:none;
    transition:.18s ease;
    cursor:pointer;
  }

  .wa-btn-back{
    background:rgba(255,255,255,.06);
    color:var(--wa-text);
  }

  .wa-btn-back:hover{
    background:rgba(255,255,255,.1);
    color:var(--wa-text);
  }

  .wa-btn-take{
    background:#ffd166;
    color:#1d1d1d;
  }

  .wa-btn-close{
    background:rgba(255,255,255,.08);
    color:var(--wa-text);
  }

  .wa-btn-close:hover{
    background:rgba(255,255,255,.12);
  }

  .wa-chat-main{
    flex:1 1 auto;
    min-height:0;
    display:flex;
    flex-direction:column;
    overflow:hidden;
  }

  .wa-chat-body{
    position:relative;
    flex:1 1 auto;
    min-height:0;
    overflow:hidden;
    background:
      radial-gradient(circle at top left, rgba(0,168,132,.05), transparent 20%),
      radial-gradient(circle at bottom right, rgba(31,122,224,.05), transparent 22%),
      #0b141a;
  }

  .wa-chat-body::before{
    content:"";
    position:absolute;
    inset:0;
    opacity:.06;
    pointer-events:none;
    background-image:
      radial-gradient(circle at 25px 25px, rgba(255,255,255,.11) 2px, transparent 2px),
      radial-gradient(circle at 75px 75px, rgba(255,255,255,.08) 2px, transparent 2px);
    background-size:100px 100px;
  }

  .wa-chat-scroll{
    position:relative;
    z-index:1;
    height:100%;
    overflow-y:auto;
    overflow-x:hidden;
    padding:16px 12px;
    scrollbar-width:thin;
    scrollbar-color:#3a4a54 transparent;
    overscroll-behavior:contain;
  }

  .wa-chat-scroll::-webkit-scrollbar{ width:10px; }
  .wa-chat-scroll::-webkit-scrollbar-thumb{
    background:#30414b;
    border-radius:999px;
  }

  .wa-chat-inner{
    max-width:1040px;
    margin:0 auto;
    min-height:100%;
    display:flex;
    flex-direction:column;
  }

  .wa-messages-stack{
    margin-top:auto;
  }

  .wa-date{
    display:flex;
    justify-content:center;
    margin:8px 0 16px;
  }

  .wa-date span{
    background:rgba(17,27,33,.92);
    color:#d3dde2;
    border:1px solid rgba(255,255,255,.05);
    padding:7px 12px;
    border-radius:10px;
    font-size:.78rem;
    font-weight:700;
    box-shadow:0 8px 22px rgba(0,0,0,.18);
  }

  .wa-row{
    display:flex;
    margin-bottom:10px;
  }

  .wa-row.in{ justify-content:flex-start; }
  .wa-row.out{ justify-content:flex-end; }

  .wa-bubble{
    max-width:min(72%, 760px);
    border-radius:8px;
    padding:7px 9px 6px;
    color:var(--wa-text);
    position:relative;
    box-shadow:0 2px 8px rgba(0,0,0,.12);
    line-height:1.45;
    word-break:break-word;
  }

  .wa-row.in .wa-bubble{
    background:var(--wa-in);
    border-top-left-radius:2px;
  }

  .wa-row.out .wa-bubble{
    background:var(--wa-out);
    border-top-right-radius:2px;
  }

  .wa-row.in .wa-bubble::before{
    content:"";
    position:absolute;
    top:0;
    left:-6px;
    width:0;
    height:0;
    border-top:6px solid var(--wa-in);
    border-left:6px solid transparent;
  }

  .wa-row.out .wa-bubble::after{
    content:"";
    position:absolute;
    top:0;
    right:-6px;
    width:0;
    height:0;
    border-top:6px solid var(--wa-out);
    border-right:6px solid transparent;
  }

  .wa-msg-top{
    display:flex;
    align-items:center;
    gap:6px;
    margin-bottom:6px;
    font-size:.72rem;
    color:#c9d4d9;
    opacity:.88;
    flex-wrap:wrap;
  }

  .wa-type-pill{
    background:rgba(255,255,255,.08);
    padding:4px 8px;
    border-radius:999px;
  }

  .wa-msg-text{
    font-size:1rem;
    white-space:pre-wrap;
    color:#f3f6f7;
    padding-right:62px;
  }

  .wa-media{
    margin-bottom:6px;
    overflow:hidden;
    border-radius:10px;
  }

  .wa-media img{
    display:block;
    max-width:100%;
    width:100%;
    height:auto;
    border-radius:10px;
    object-fit:cover;
    background:#0f1a20;
  }

  .wa-video{
    width:100%;
    max-width:100%;
    border-radius:10px;
    background:#000;
    display:block;
  }

  .wa-audio-wrap{
    display:flex;
    align-items:center;
    gap:10px;
    min-width:240px;
    padding:10px 12px;
    background:rgba(0,0,0,.14);
    border-radius:14px;
  }

  .wa-audio-icon{
    width:42px;
    height:42px;
    flex:0 0 42px;
    border-radius:50%;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.12);
    color:#fff;
  }

  .wa-audio-meta{
    min-width:0;
    flex:1;
  }

  .wa-audio-title{
    font-size:.9rem;
    font-weight:700;
    color:#fff;
    margin-bottom:4px;
  }

  .wa-audio-player{
    width:100%;
    max-width:280px;
    height:34px;
  }

  .wa-doc{
    display:flex;
    align-items:center;
    gap:12px;
    background:rgba(0,0,0,.14);
    border-radius:14px;
    padding:12px;
    min-width:250px;
  }

  .wa-doc-icon{
    width:44px;
    height:44px;
    flex:0 0 44px;
    border-radius:12px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.12);
    color:#fff;
  }

  .wa-doc-info{
    min-width:0;
    flex:1;
  }

  .wa-doc-name{
    font-size:.92rem;
    font-weight:700;
    color:#fff;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .wa-doc-mime{
    font-size:.78rem;
    color:rgba(233,237,239,.7);
    margin-top:2px;
  }

  .wa-doc-link{
    margin-top:8px;
    display:inline-flex;
    align-items:center;
    gap:6px;
    color:#9fe7c9;
    text-decoration:none;
    font-size:.82rem;
    font-weight:700;
  }

  .wa-sticker{
    max-width:180px;
    width:100%;
    background:transparent !important;
    box-shadow:none !important;
  }

  .wa-sticker img{
    max-width:180px;
    width:100%;
    height:auto;
    object-fit:contain;
    background:transparent;
    filter:drop-shadow(0 8px 16px rgba(0,0,0,.22));
  }

  .wa-msg-bottom{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:6px;
    margin-top:4px;
    font-size:.76rem;
    color:#d8e2e7;
    opacity:.92;
    margin-left:auto;
    width:max-content;
  }

  .wa-msg-time{
    white-space:nowrap;
    color:rgba(233,237,239,.78);
  }

  .wa-msg-check{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:18px;
    font-weight:700;
    letter-spacing:-1px;
    line-height:1;
  }

  .wa-msg-check.is-sent,
  .wa-msg-check.is-delivered{
    color:rgba(233,237,239,.85);
  }

  .wa-msg-check.is-read{ color:var(--wa-read); }
  .wa-msg-check.is-failed{ color:#ff8f8f; letter-spacing:0; }
  .wa-msg-check.is-pending{ color:#ffe08a; letter-spacing:0; }

  .wa-empty{
    text-align:center;
    color:var(--wa-muted);
    padding:50px 20px;
    margin:auto 0;
  }

  .wa-compose{
    background:#111b21;
    border-top:1px solid var(--wa-border);
    padding:10px 12px;
    flex:0 0 auto;
  }

  .wa-compose-inner{
    max-width:1040px;
    margin:0 auto;
  }

  .wa-compose-bar{
    display:flex;
    align-items:flex-end;
    gap:10px;
  }

  .wa-compose-tools{
    position:relative;
    flex:0 0 auto;
  }

  .wa-icon-btn{
    width:46px;
    height:46px;
    flex:0 0 46px;
    border:none;
    border-radius:50%;
    background:#202c33;
    color:#c8d2d8;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:.18s ease;
  }

  .wa-icon-btn:hover{
    background:#2a3942;
    color:#fff;
  }

  .wa-compose-input-wrap{
    flex:1;
    background:#2a3942;
    border-radius:28px;
    min-height:50px;
    display:flex;
    align-items:flex-end;
    padding:0 16px;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.03);
  }

  .wa-textarea{
    width:100%;
    min-height:24px;
    max-height:120px;
    resize:none;
    overflow-y:auto;
    border:none;
    outline:none;
    background:transparent;
    color:var(--wa-text);
    padding:13px 0;
    font-size:.98rem;
    line-height:1.45;
  }

  .wa-textarea::placeholder{ color:var(--wa-muted); }

  .wa-send{
    width:52px;
    height:52px;
    flex:0 0 52px;
    border:none;
    border-radius:50%;
    background:#00a884;
    color:#041b16;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    transition:.18s ease;
    box-shadow:0 10px 24px rgba(0,168,132,.24);
    cursor:pointer;
  }

  .wa-send:hover{
    transform:translateY(-1px);
    filter:brightness(1.03);
  }

  .wa-send:disabled{
    opacity:.65;
    cursor:not-allowed;
    transform:none;
  }

  .wa-send svg{ display:block; }

  .wa-emoji-picker{
    position:absolute;
    left:0;
    bottom:58px;
    width:320px;
    max-width:min(320px, calc(100vw - 40px));
    max-height:280px;
    overflow-y:auto;
    overflow-x:hidden;
    background:#111b21;
    border:1px solid rgba(255,255,255,.06);
    border-radius:18px;
    box-shadow:0 24px 60px rgba(0,0,0,.35);
    padding:12px;
    display:none;
    z-index:80;
    scrollbar-width:thin;
    scrollbar-color:#3a4a54 transparent;
  }

  .wa-emoji-picker::-webkit-scrollbar{ width:8px; }
  .wa-emoji-picker::-webkit-scrollbar-thumb{
    background:#30414b;
    border-radius:999px;
  }

  .wa-emoji-picker.is-open{ display:block; }

  .wa-emoji-grid{
    display:grid;
    grid-template-columns:repeat(8, 1fr);
    gap:8px;
  }

  .wa-emoji{
    border:none;
    background:#202c33;
    color:#fff;
    border-radius:12px;
    height:38px;
    font-size:1.15rem;
    cursor:pointer;
    transition:.15s ease;
  }

  .wa-emoji:hover{
    background:#2a3942;
    transform:translateY(-1px);
  }

  .wa-alert{
    margin-bottom:14px;
    border:none;
    border-radius:14px;
  }

  .wa-lightbox{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.84);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:24px;
  }

  .wa-lightbox.is-open{ display:flex; }

  .wa-lightbox img{
    max-width:min(95vw, 1200px);
    max-height:90vh;
    border-radius:16px;
    box-shadow:0 20px 80px rgba(0,0,0,.4);
  }

  .wa-lightbox-close{
    position:absolute;
    top:18px;
    right:18px;
    width:46px;
    height:46px;
    border:none;
    border-radius:50%;
    background:rgba(255,255,255,.12);
    color:#fff;
    font-size:1.4rem;
    cursor:pointer;
  }

  @media (max-width: 991.98px){
    .wa-chat-app{
      height:calc(100vh - 120px);
      min-height:520px;
      max-height:none;
      border-radius:16px;
    }
    .wa-bubble{ max-width:84%; }
  }

  @media (max-width: 767.98px){
    .wa-chat-wrap{ max-width:100%; }

    .wa-chat-app{
      height:calc(100vh - 94px);
      min-height:0;
      border-radius:14px;
    }

    .wa-chat-head{ padding:12px; }
    .wa-chat-scroll{ padding:14px 8px; }
    .wa-compose{ padding:10px 8px; }
    .wa-bubble{ max-width:92%; }

    .wa-chat-actions{ width:100%; }

    .wa-chat-actions form,
    .wa-chat-actions a{
      flex:1 1 100%;
    }

    .wa-btn{ width:100%; }

    .wa-msg-text{
      font-size:.97rem;
      padding-right:54px;
    }

    .wa-compose-bar{ gap:8px; }

    .wa-compose-input-wrap{
      min-height:48px;
      padding:0 14px;
    }

    .wa-send,
    .wa-icon-btn{
      width:48px;
      height:48px;
      flex-basis:48px;
    }

    .wa-audio-wrap,
    .wa-doc{
      min-width:0;
      width:100%;
    }

    .wa-emoji-picker{
      left:0;
      right:auto;
      bottom:56px;
      width:min(290px, calc(100vw - 24px));
      max-width:calc(100vw - 24px);
      max-height:240px;
    }

    .wa-emoji-grid{
      grid-template-columns:repeat(6, 1fr);
    }
  }
</style>

<div class="wa-chat container-fluid px-0">
  @if(session('ok'))
    <div class="alert alert-success wa-alert">{{ session('ok') }}</div>
  @endif

  @if(session('err'))
    <div class="alert alert-danger wa-alert">{{ session('err') }}</div>
  @endif

  <div class="wa-chat-wrap">
    <div class="wa-chat-app">
      <div class="wa-chat-head">
        <div class="wa-chat-user">
          <div class="wa-avatar">
            {{ $initials($conversation->user->name ?? '', $conversation->phone) }}
          </div>

          <div class="wa-chat-meta">
            <h1 class="wa-chat-name">{{ $displayName }}</h1>
            <div class="wa-chat-sub">
              <span>{{ $conversation->phone }}</span>
              <span>•</span>
              <span>{{ $conversation->agent->name ?? 'Sin agente asignado' }}</span>
              <span class="wa-status {{ $statusClass($conversation->status) }}">
                {{ $statusLabel($conversation->status) }}
              </span>
            </div>
          </div>
        </div>

        <div class="wa-chat-actions">
          <a href="{{ url('/admin/whatsapp/conversations') }}" class="wa-btn wa-btn-back">
            ← Volver
          </a>

          <form method="POST" action="{{ route('admin.whatsapp.conversations.take', $conversation) }}">
            @csrf
            <button class="wa-btn wa-btn-take" type="submit">
              Tomar conversación
            </button>
          </form>

          <form method="POST" action="{{ route('admin.whatsapp.conversations.close', $conversation) }}">
            @csrf
            <button class="wa-btn wa-btn-close" type="submit">
              Cerrar conversación
            </button>
          </form>
        </div>
      </div>

      <div class="wa-chat-main">
        <div class="wa-chat-body">
          <div class="wa-chat-scroll" id="chatScroll">
            <div class="wa-chat-inner">
              <div class="wa-messages-stack" id="chatMessages">
                @forelse($groupMessagesByDate as $dateKey => $items)
                  <div class="wa-date">
                    <span>
                      {{ $dateKey !== 'sin-fecha' ? \Carbon\Carbon::parse($dateKey)->translatedFormat('d \d\e F \d\e Y') : 'Sin fecha' }}
                    </span>
                  </div>

                  @foreach($items as $m)
                    @php
                      $dirClass = in_array(mb_strtolower((string)$m->direction), ['outbound','out','outgoing']) ? 'out' : 'in';
                      $statusIcon = $messageStatusIcon($m->status, $m->direction);
                      $statusCss = $messageStatusClass($m->status, $m->direction);
                      $isOutbound = $dirClass === 'out';

                      $type = mb_strtolower((string)($m->message_type ?? 'text'));

                      $mediaUrl = $m->media_url
                          ?? $m->file_url
                          ?? $m->document_url
                          ?? $m->audio_url
                          ?? $m->image_url
                          ?? $m->video_url
                          ?? null;

                      $mimeType = (string)($m->mime_type ?? '');
                      $fileName = (string)($m->file_name ?? $m->filename ?? ('archivo_'.$m->id));

                      $isImage = in_array($type, ['image', 'sticker']) || str_starts_with($mimeType, 'image/');
                      $isAudio = in_array($type, ['audio', 'voice', 'ptt']) || str_starts_with($mimeType, 'audio/');
                      $isVideo = $type === 'video' || str_starts_with($mimeType, 'video/');
                      $isDocument = in_array($type, ['document', 'file']) || (!$isImage && !$isAudio && !$isVideo && !empty($mediaUrl) && $type !== 'text');
                      $isSticker = $type === 'sticker';
                    @endphp

                    <div class="wa-row {{ $dirClass }}">
                      <div class="wa-bubble {{ $isSticker ? 'wa-sticker' : '' }}">
                        @if(($m->message_type ?? 'text') !== 'text')
                          <div class="wa-msg-top">
                            <span class="wa-type-pill">{{ $messageTypeLabel($m->message_type) }}</span>
                          </div>
                        @endif

                        @if($isImage && !empty($mediaUrl) && !$isSticker)
                          <div class="wa-media">
                            <img src="{{ $mediaUrl }}" alt="Imagen" class="wa-image-preview" loading="lazy">
                          </div>
                        @endif

                        @if($isSticker && !empty($mediaUrl))
                          <div class="wa-media">
                            <img src="{{ $mediaUrl }}" alt="Sticker" class="wa-image-preview" loading="lazy">
                          </div>
                        @endif

                        @if($isVideo && !empty($mediaUrl))
                          <div class="wa-media">
                            <video class="wa-video" controls preload="metadata">
                              <source src="{{ $mediaUrl }}" type="{{ $mimeType ?: 'video/mp4' }}">
                              Tu navegador no soporta video.
                            </video>
                          </div>
                        @endif

                        @if($isAudio && !empty($mediaUrl))
                          <div class="wa-audio-wrap">
                            <div class="wa-audio-icon">
                              <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                                <path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3Zm5 9a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V22h2v-3.08A7 7 0 0 0 19 12h-2Z"/>
                              </svg>
                            </div>
                            <div class="wa-audio-meta">
                              <div class="wa-audio-title">{{ in_array($type, ['voice','ptt']) ? 'Mensaje de voz' : 'Audio' }}</div>
                              <audio class="wa-audio-player" controls preload="metadata">
                                <source src="{{ $mediaUrl }}" type="{{ $mimeType ?: 'audio/mpeg' }}">
                                Tu navegador no soporta audio.
                              </audio>
                            </div>
                          </div>
                        @endif

                        @if($isDocument && !empty($mediaUrl))
                          <div class="wa-doc">
                            <div class="wa-doc-icon">
                              <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                                <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7l-5-5Zm0 1.5L17.5 7H14V3.5ZM8 12h8v1.5H8V12Zm0 3h8v1.5H8V15Zm0-6h4v1.5H8V9Z"/>
                              </svg>
                            </div>
                            <div class="wa-doc-info">
                              <div class="wa-doc-name">{{ $fileName }}</div>
                              <div class="wa-doc-mime">{{ $mimeType ?: 'Documento adjunto' }}</div>
                              <a href="{{ $mediaUrl }}" target="_blank" class="wa-doc-link">
                                Abrir documento
                              </a>
                            </div>
                          </div>
                        @endif

                        @if(!empty($m->text))
                          <div class="wa-msg-text">{{ $m->text }}</div>
                        @elseif(!$isImage && !$isAudio && !$isVideo && !$isDocument && !$isSticker)
                          <div class="wa-msg-text">—</div>
                        @endif

                        <div class="wa-msg-bottom">
                          <span class="wa-msg-time">{{ optional($m->created_at)?->format('g:i a') }}</span>
                          @if($isOutbound && $statusIcon !== '')
                            <span class="wa-msg-check {{ $statusCss }}">{{ $statusIcon }}</span>
                          @endif
                        </div>
                      </div>
                    </div>
                  @endforeach
                @empty
                  <div class="wa-empty">
                    No hay mensajes en esta conversación.
                  </div>
                @endforelse
              </div>
            </div>
          </div>
        </div>

        <div class="wa-compose">
          <div class="wa-compose-inner">
            <form method="POST" action="{{ route('admin.whatsapp.conversations.reply', $conversation) }}" id="replyForm">
              @csrf

              <div class="wa-compose-bar">
                <div class="wa-compose-tools">
                  <button class="wa-icon-btn" type="button" id="emojiToggle" aria-label="Emojis">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                      <path d="M12 22a10 10 0 1 1 10-10 10.01 10.01 0 0 1-10 10Zm0-18a8 8 0 1 0 8 8 8.01 8.01 0 0 0-8-8Zm-3 6.5A1.5 1.5 0 1 1 10.5 9 1.5 1.5 0 0 1 9 10.5Zm6 0A1.5 1.5 0 1 1 16.5 9 1.5 1.5 0 0 1 15 10.5Zm-3 7a5.72 5.72 0 0 1-4.3-1.93l1.16-1.04A4.18 4.18 0 0 0 12 15.9a4.18 4.18 0 0 0 3.14-1.37l1.16 1.04A5.72 5.72 0 0 1 12 17.5Z"/>
                    </svg>
                  </button>

                  <div class="wa-emoji-picker" id="emojiPicker">
                    <div class="wa-emoji-grid">
                      @foreach($emojiList as $emoji)
                        <button type="button" class="wa-emoji">{{ $emoji }}</button>
                      @endforeach
                    </div>
                  </div>
                </div>

                <div class="wa-compose-input-wrap">
                  <textarea
                    name="text"
                    class="wa-textarea"
                    rows="1"
                    required
                    placeholder="Escribe un mensaje"
                    id="messageBox"
                  ></textarea>
                </div>

                <button class="wa-send" type="submit" id="sendBtn" aria-label="Enviar">
                  <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                    <path d="M3.4 20.4 21.85 12 3.4 3.6v6.3l13.2 2.1-13.2 2.1v6.3z"/>
                  </svg>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="wa-lightbox" id="lightbox">
    <button class="wa-lightbox-close" type="button" id="lightboxClose">×</button>
    <img src="" alt="Vista previa" id="lightboxImage">
  </div>
</div>

<script>
  (function () {
    const chat = document.getElementById('chatScroll');
    const chatMessages = document.getElementById('chatMessages');
    const textarea = document.getElementById('messageBox');
    const replyForm = document.getElementById('replyForm');
    const sendBtn = document.getElementById('sendBtn');
    const emojiToggle = document.getElementById('emojiToggle');
    const emojiPicker = document.getElementById('emojiPicker');
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxClose = document.getElementById('lightboxClose');
    const pageUrl = window.location.href;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    let pollingTimer = null;
    let isSending = false;
    let isRefreshing = false;
    let lastKnownMessageCount = chatMessages ? chatMessages.children.length : 0;

    function scrollToBottom(force = false) {
      if (!chat) return;
      const nearBottom = (chat.scrollHeight - chat.scrollTop - chat.clientHeight) < 140;
      if (force || nearBottom) {
        requestAnimationFrame(() => {
          chat.scrollTop = chat.scrollHeight;
        });
      }
    }

    function resizeTextarea() {
      if (!textarea) return;
      textarea.style.height = '24px';
      textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function currentTime() {
      const now = new Date();
      let h = now.getHours();
      const m = String(now.getMinutes()).padStart(2, '0');
      const suffix = h >= 12 ? 'pm' : 'am';
      h = h % 12 || 12;
      return `${h}:${m} ${suffix}`;
    }

    function ensureMessagesBottomAligned() {
      scrollToBottom(true);
    }

    function closeEmojiPicker() {
      if (emojiPicker) emojiPicker.classList.remove('is-open');
    }

    function fixEmojiPosition() {
      if (!emojiPicker || !emojiToggle) return;

      emojiPicker.style.left = '0px';
      emojiPicker.style.right = 'auto';

      const rect = emojiPicker.getBoundingClientRect();
      const overflowRight = rect.right - window.innerWidth;

      if (overflowRight > 0) {
        emojiPicker.style.left = 'auto';
        emojiPicker.style.right = '0px';
      }
    }

    function bindImagePreviewEvents(scope = document) {
      scope.querySelectorAll('.wa-image-preview').forEach(img => {
        if (img.dataset.bound === '1') return;
        img.dataset.bound = '1';
        img.style.cursor = 'zoom-in';
        img.addEventListener('click', function () {
          if (lightbox && lightboxImage) {
            lightboxImage.src = this.src;
            lightbox.classList.add('is-open');
          }
        });
      });
    }

    function appendOutgoingMessage(text) {
      if (!chatMessages) return null;

      const row = document.createElement('div');
      row.className = 'wa-row out';
      row.innerHTML = `
        <div class="wa-bubble">
          <div class="wa-msg-text">${escapeHtml(text)}</div>
          <div class="wa-msg-bottom">
            <span class="wa-msg-time">${currentTime()}</span>
            <span class="wa-msg-check is-pending">🕓</span>
          </div>
        </div>
      `;
      chatMessages.appendChild(row);
      ensureMessagesBottomAligned();
      return row;
    }

    function markMessageAsSent(row) {
      if (!row) return;
      const check = row.querySelector('.wa-msg-check');
      if (check) {
        check.className = 'wa-msg-check is-sent';
        check.textContent = '✓';
      }
    }

    function markMessageAsFailed(row) {
      if (!row) return;
      const check = row.querySelector('.wa-msg-check');
      if (check) {
        check.className = 'wa-msg-check is-failed';
        check.textContent = '!';
      }
    }

    async function refreshMessages(forceScroll = false) {
      if (isRefreshing || isSending) return;
      isRefreshing = true;

      try {
        const response = await fetch(pageUrl, {
          method: 'GET',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        });

        if (!response.ok) throw new Error('No se pudo actualizar el chat');

        const html = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newMessages = doc.getElementById('chatMessages');

        if (newMessages && chatMessages) {
          const oldCount = chatMessages.children.length;
          chatMessages.innerHTML = newMessages.innerHTML;
          bindImagePreviewEvents(chatMessages);
          lastKnownMessageCount = chatMessages.children.length;

          const hasNewMessages = lastKnownMessageCount !== oldCount;
          if (forceScroll || hasNewMessages) {
            ensureMessagesBottomAligned();
          }
        }
      } catch (error) {
        console.error(error);
      } finally {
        isRefreshing = false;
      }
    }

    function startPolling() {
      stopPolling();
      pollingTimer = setInterval(() => {
        refreshMessages(false);
      }, 5000);
    }

    function stopPolling() {
      if (pollingTimer) {
        clearInterval(pollingTimer);
        pollingTimer = null;
      }
    }

    bindImagePreviewEvents(document);
    ensureMessagesBottomAligned();
    resizeTextarea();
    startPolling();

    if (textarea) {
      textarea.addEventListener('input', resizeTextarea);

      textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          if (replyForm) replyForm.requestSubmit();
        }
      });
    }

    if (emojiToggle && emojiPicker && textarea) {
      emojiToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        emojiPicker.classList.toggle('is-open');
        if (emojiPicker.classList.contains('is-open')) {
          fixEmojiPosition();
        }
      });

      document.querySelectorAll('.wa-emoji').forEach(btn => {
        btn.addEventListener('click', function () {
          const emoji = this.textContent;
          const start = textarea.selectionStart ?? textarea.value.length;
          const end = textarea.selectionEnd ?? textarea.value.length;
          const value = textarea.value;

          textarea.value = value.substring(0, start) + emoji + value.substring(end);
          textarea.focus();
          textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
          resizeTextarea();
        });
      });

      document.addEventListener('click', function (e) {
        if (!emojiPicker.contains(e.target) && !emojiToggle.contains(e.target)) {
          closeEmojiPicker();
        }
      });

      window.addEventListener('resize', function () {
        if (emojiPicker.classList.contains('is-open')) {
          fixEmojiPosition();
        }
      });
    }

    if (replyForm && textarea && sendBtn) {
      replyForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const text = textarea.value.trim();
        if (!text || isSending) return;

        isSending = true;
        sendBtn.disabled = true;
        stopPolling();

        const originalText = textarea.value;
        const optimisticRow = appendOutgoingMessage(text);

        textarea.value = '';
        resizeTextarea();
        closeEmojiPicker();

        try {
          const formData = new FormData(replyForm);

          const response = await fetch(replyForm.action, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrfToken,
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData,
            credentials: 'same-origin'
          });

          if (!response.ok) {
            throw new Error('No se pudo enviar el mensaje');
          }

          markMessageAsSent(optimisticRow);
          await refreshMessages(true);
        } catch (error) {
          markMessageAsFailed(optimisticRow);
          textarea.value = originalText;
          resizeTextarea();
          console.error(error);
        } finally {
          isSending = false;
          sendBtn.disabled = false;
          textarea.focus();
          ensureMessagesBottomAligned();
          startPolling();
        }
      });
    }

    if (lightbox && lightboxClose) {
      lightboxClose.addEventListener('click', function () {
        lightbox.classList.remove('is-open');
        lightboxImage.src = '';
      });

      lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) {
          lightbox.classList.remove('is-open');
          lightboxImage.src = '';
        }
      });
    }

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        stopPolling();
      } else {
        startPolling();
        refreshMessages(false);
      }
    });

    window.addEventListener('load', function () {
      ensureMessagesBottomAligned();
      refreshMessages(true);
    });
  })();
</script>
@endsection