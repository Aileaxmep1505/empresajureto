@extends('layouts.app')

@section('title', 'Publicaciones')

@section('content')
@php
  // Helper local para icono por tipo (fallback si no hay extensión)
  $kindIcon = function($p){
    $k = $p->kind ?? 'file';
    return match($k){
      'image' => 'photo',
      'video' => 'video',
      'pdf'   => 'pdf',
      'doc'   => 'doc',
      'sheet' => 'sheet',
      default => 'file',
    };
  };
@endphp

<div class="container py-4" id="pubsPro">
  <style>
    #pubsPro{
      --bg:#f6f7fb;
      --card:#ffffff;
      --ink:#0b1220;
      --muted:#6b7280;
      --line:rgba(15,23,42,.10);
      --soft:rgba(2,6,23,.04);

      /* Pastel system */
      --pastel-blue-bg: rgba(59,130,246,.12);
      --pastel-blue-ink:#1d4ed8;
      --pastel-blue-brd: rgba(59,130,246,.22);

      --pastel-mint-bg: rgba(16,185,129,.12);
      --pastel-mint-ink:#047857;
      --pastel-mint-brd: rgba(16,185,129,.22);

      --pastel-rose-bg: rgba(244,63,94,.12);
      --pastel-rose-ink:#be123c;
      --pastel-rose-brd: rgba(244,63,94,.22);

      --shadow: 0 18px 60px rgba(2,6,23,.08);
      --shadow2: 0 10px 30px rgba(2,6,23,.08);

      --radius:18px;
    }

    /* ====== Layout base ====== */
    #pubsPro .shell{
      background:
        radial-gradient(1200px 500px at 10% 0%, rgba(59,130,246,.10), transparent 55%),
        radial-gradient(900px 420px at 90% 0%, rgba(16,185,129,.08), transparent 55%),
        linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,.25));
      border:1px solid rgba(15,23,42,.08);
      border-radius: calc(var(--radius) + 6px);
      padding: 18px;
      box-shadow: 0 18px 70px rgba(2,6,23,.06);
    }

    /* ====== Header ====== */
    #pubsPro .hero{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:14px;
      margin-bottom: 14px;
    }
    #pubsPro .hero .ttl{
      display:flex; flex-direction:column; gap:6px;
      min-width:0;
    }
    #pubsPro .hero h1{
      margin:0;
      font-weight:950;
      letter-spacing:-.03em;
      color:var(--ink);
      font-size: 26px;
      display:flex;
      align-items:center;
      gap:10px;
    }
    #pubsPro .hero p{
      margin:0;
      color:var(--muted);
      line-height:1.55;
      max-width: 760px;
      font-weight: 700;
    }

    #pubsPro .toolbar{
      display:flex; gap:10px; align-items:center; justify-content:flex-end;
      flex-wrap:wrap;
    }

    /* ====== Pastel Buttons (NO solid purple) ====== */
    #pubsPro .btn{
      border:1px solid var(--line);
      background: rgba(255,255,255,.78);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      color: var(--ink);
      padding: 10px 12px;
      border-radius: 14px;
      font-weight: 900;
      text-decoration:none;
      display:inline-flex; align-items:center; gap:10px;
      transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
      box-shadow: 0 8px 22px rgba(2,6,23,.06);
    }
    #pubsPro .btn:hover{
      transform: translateY(-1px);
      border-color: rgba(59,130,246,.22);
      box-shadow: 0 14px 34px rgba(2,6,23,.10);
    }
    #pubsPro .btn.pastel{
      background: var(--pastel-blue-bg);
      border-color: var(--pastel-blue-brd);
      color: var(--pastel-blue-ink);
      box-shadow: 0 14px 36px rgba(59,130,246,.10);
    }
    #pubsPro .btn.pastel:hover{
      background: rgba(59,130,246,.15);
      border-color: rgba(59,130,246,.28);
      box-shadow: 0 18px 45px rgba(59,130,246,.14);
    }

    /* ====== Tabs/section titles ====== */
    #pubsPro .sectionTitle{
      margin: 16px 0 10px;
      display:flex; align-items:center; justify-content:space-between; gap:10px;
    }
    #pubsPro .sectionTitle h2{
      margin:0;
      font-size: 12px;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(15,23,42,.70);
      font-weight: 950;
      display:flex; align-items:center; gap:10px;
    }
    #pubsPro .pill{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:6px 10px;
      border-radius:999px;
      border:1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.70);
      color: rgba(15,23,42,.80);
      font-weight: 900;
      font-size: 12px;
    }

    /* ====== Grid ====== */
    #pubsPro .grid{
      display:grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 14px;
    }
    #pubsPro .card{
      grid-column: span 4;
      background: rgba(255,255,255,.80);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border:1px solid rgba(15,23,42,.10);
      border-radius: var(--radius);
      box-shadow: var(--shadow2);
      overflow:hidden;
      position:relative;
      transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
      cursor:pointer;
      user-select:none;
      outline:none;
    }
    #pubsPro .card::after{
      content:"";
      position:absolute;
      inset:0;
      background: radial-gradient(600px 180px at 15% 0%, rgba(59,130,246,.10), transparent 60%),
                  radial-gradient(600px 180px at 85% 0%, rgba(16,185,129,.08), transparent 60%);
      opacity:.0;
      transition: opacity .18s ease;
      pointer-events:none;
    }
    #pubsPro .card:hover::after{ opacity:1; }

    #pubsPro .card:hover{
      transform: translateY(-2px);
      border-color: rgba(59,130,246,.20);
      box-shadow: var(--shadow);
    }
    #pubsPro .card:active{
      transform: translateY(0px) scale(.995);
    }

    @media (max-width: 992px){ #pubsPro .card{ grid-column: span 6; } }
    @media (max-width: 576px){ #pubsPro .card{ grid-column: span 12; } }

    /* ====== Card content ====== */
    #pubsPro .card .top{
      padding: 14px 14px 10px 14px;
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap:10px;
      position:relative;
      z-index:1;
    }
    #pubsPro .card .title{
      margin:0;
      font-weight: 950;
      letter-spacing:-.02em;
      color: var(--ink);
      line-height:1.15;
      font-size: 16px;
      display:-webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow:hidden;
    }
    #pubsPro .card .desc{
      margin:8px 0 0 0;
      color: var(--muted);
      line-height:1.55;
      font-size: 13px;
      font-weight: 700;
      display:-webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow:hidden;
    }

    #pubsPro .pin{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,.75);
      border: 1px solid rgba(15,23,42,.10);
      color: rgba(15,23,42,.85);
      font-weight: 950;
      font-size: 12px;
      white-space:nowrap;
    }

    /* ====== Preview / media ====== */
    #pubsPro .preview{
      margin: 0 14px 12px 14px;
      border-radius: 14px;
      overflow:hidden;
      border: 1px solid rgba(15,23,42,.10);
      background:
        linear-gradient(180deg, rgba(255,255,255,.55), rgba(255,255,255,.18)),
        radial-gradient(700px 220px at 30% 0%, rgba(59,130,246,.10), transparent 60%),
        radial-gradient(700px 220px at 80% 0%, rgba(16,185,129,.10), transparent 60%),
        #eef2ff;
      aspect-ratio: 16 / 9;
      display:flex;
      align-items:center;
      justify-content:center;
      position:relative;
      z-index:1;
    }
    #pubsPro .preview img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
      transform: scale(1.02);
      transition: transform .25s ease;
    }
    #pubsPro .card:hover .preview img{
      transform: scale(1.06);
    }

    #pubsPro .fileBadge{
      display:inline-flex;
      align-items:center;
      gap:10px;
      padding: 10px 12px;
      border-radius: 14px;
      background: rgba(255,255,255,.72);
      border: 1px solid rgba(15,23,42,.10);
      box-shadow: 0 10px 25px rgba(2,6,23,.08);
    }
    #pubsPro .fileBadge .ext{
      font-weight: 1000;
      letter-spacing: .06em;
      color: rgba(15,23,42,.82);
      font-size: 12px;
    }
    #pubsPro .fileBadge .sub{
      font-size: 12px;
      color: rgba(15,23,42,.55);
      font-weight: 900;
    }

    /* ====== Bottom meta row ====== */
    #pubsPro .meta{
      padding: 0 14px 14px 14px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      color: rgba(15,23,42,.62);
      font-size: 12px;
      font-weight: 900;
      position:relative;
      z-index:1;
    }
    #pubsPro .meta .left{
      display:flex; gap:10px; align-items:center; flex-wrap:wrap;
      min-width:0;
    }
    #pubsPro .dot{
      width:5px; height:5px; border-radius:999px;
      background: rgba(15,23,42,.25);
    }

    #pubsPro .ghostActions{
      display:flex; align-items:center; gap:10px;
      opacity: .0;
      transform: translateY(2px);
      transition: opacity .18s ease, transform .18s ease;
      pointer-events:none;
    }
    #pubsPro .card:hover .ghostActions{
      opacity: 1;
      transform: translateY(0);
    }
    #pubsPro .iconBtn{
      width:34px; height:34px;
      border-radius: 12px;
      border: 1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.78);
      display:flex; align-items:center; justify-content:center;
      transition: transform .16s ease, box-shadow .16s ease;
      box-shadow: 0 10px 22px rgba(2,6,23,.06);
    }
    #pubsPro .iconBtn:hover{
      transform: translateY(-1px);
      box-shadow: 0 14px 30px rgba(2,6,23,.10);
    }

    /* ====== Alert ====== */
    #pubsPro .alert{ border-radius: 16px; }

    /* ====== Cards fade-in ====== */
    @keyframes fadeUp{
      from { opacity:0; transform: translateY(10px); }
      to   { opacity:1; transform: translateY(0); }
    }
    #pubsPro .card{ animation: fadeUp .28s ease both; }
    #pubsPro .card:nth-child(2){ animation-delay:.03s; }
    #pubsPro .card:nth-child(3){ animation-delay:.06s; }
    #pubsPro .card:nth-child(4){ animation-delay:.09s; }
    #pubsPro .card:nth-child(5){ animation-delay:.12s; }
    #pubsPro .card:nth-child(6){ animation-delay:.15s; }

    /* ====== Modal ====== */
    #pubsPro .modalx{
      position: fixed;
      inset: 0;
      background: rgba(2,6,23,.55);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      display:none;
      align-items:center;
      justify-content:center;
      padding: 18px;
      z-index: 9999;
    }
    #pubsPro .modalx.open{ display:flex; }
    #pubsPro .modalx .panel{
      width:min(1100px, 100%);
      background: rgba(255,255,255,.92);
      border:1px solid rgba(255,255,255,.35);
      border-radius: 22px;
      box-shadow: 0 30px 90px rgba(2,6,23,.30);
      overflow:hidden;
      transform: translateY(10px) scale(.98);
      opacity: 0;
      transition: transform .18s ease, opacity .18s ease;
    }
    #pubsPro .modalx.open .panel{
      transform: translateY(0) scale(1);
      opacity: 1;
    }
    #pubsPro .modalx .head{
      padding: 14px 16px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 12px;
      border-bottom: 1px solid rgba(15,23,42,.10);
      background: linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,255,255,.65));
    }
    #pubsPro .modalx .head .twrap{
      min-width:0;
      display:flex;
      flex-direction:column;
      gap:4px;
    }
    #pubsPro .modalx .head h3{
      margin:0;
      font-weight: 950;
      letter-spacing:-.02em;
      color: var(--ink);
      font-size: 16px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
      max-width: 760px;
    }
    #pubsPro .modalx .head .sub{
      color: rgba(15,23,42,.60);
      font-size: 12px;
      font-weight: 900;
      display:flex;
      gap:10px;
      align-items:center;
      flex-wrap:wrap;
    }
    #pubsPro .modalx .head .sub .sep{
      width:4px; height:4px; border-radius:999px;
      background: rgba(15,23,42,.25);
    }
    #pubsPro .modalx .head .actions{
      display:flex; gap:10px; align-items:center;
      flex-wrap:wrap;
      justify-content:flex-end;
    }
    #pubsPro .modalx .closeBtn{
      width:38px; height:38px;
      border-radius: 14px;
      border:1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.85);
      display:flex; align-items:center; justify-content:center;
      box-shadow: 0 12px 26px rgba(2,6,23,.10);
      transition: transform .16s ease;
    }
    #pubsPro .modalx .closeBtn:hover{ transform: translateY(-1px); }

    #pubsPro .modalx .body{
      background: rgba(248,250,252,.65);
      padding: 16px;
      display:grid;
      grid-template-columns: 1.25fr .75fr;
      gap: 14px;
      align-items:start;
    }
    @media (max-width: 992px){
      #pubsPro .modalx .body{ grid-template-columns: 1fr; }
      #pubsPro .modalx .head h3{ max-width: 56vw; }
    }

    #pubsPro .viewer{
      border-radius: 18px;
      border:1px solid rgba(15,23,42,.10);
      background: #fff;
      overflow:hidden;
      box-shadow: 0 18px 55px rgba(2,6,23,.10);
      min-height: 420px;
    }
    #pubsPro .viewer .inner{
      width:100%;
      height: min(70vh, 640px);
      background: #0b1220;
    }
    #pubsPro .viewer iframe,
    #pubsPro .viewer video,
    #pubsPro .viewer img{
      width:100%;
      height:100%;
      display:block;
      border:0;
      object-fit:contain;
      background: #0b1220;
    }

    #pubsPro .side{
      border-radius: 18px;
      border:1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.85);
      box-shadow: 0 18px 55px rgba(2,6,23,.08);
      overflow:hidden;
    }
    #pubsPro .side .pad{ padding: 14px 14px 12px; }
    #pubsPro .side .label{
      font-size: 11px;
      letter-spacing: .10em;
      text-transform: uppercase;
      color: rgba(15,23,42,.55);
      font-weight: 950;
    }
    #pubsPro .side .text{
      margin-top:8px;
      color: rgba(15,23,42,.82);
      line-height:1.7;
      font-size: 13px;
      font-weight: 750;
      white-space: pre-wrap;
    }
    #pubsPro .side .ctaRow{
      padding: 12px 14px 14px;
      border-top:1px solid rgba(15,23,42,.10);
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      align-items:center;
      justify-content:flex-end;
      background: linear-gradient(180deg, rgba(255,255,255,.92), rgba(255,255,255,.65));
    }

    /* ====== Modal buttons (Pastel + same-color text) ====== */
    #pubsPro .btnSmall{
      border:1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.80);
      color: rgba(15,23,42,.88);
      padding: 10px 12px;
      border-radius: 14px;
      font-weight: 950;
      text-decoration:none;
      display:inline-flex; align-items:center; gap:10px;
      transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
      box-shadow: 0 12px 26px rgba(2,6,23,.08);
    }
    #pubsPro .btnSmall:hover{
      transform: translateY(-1px);
      box-shadow: 0 16px 34px rgba(2,6,23,.12);
    }
    #pubsPro .btnSmall.pastelBlue{
      background: var(--pastel-blue-bg);
      border-color: var(--pastel-blue-brd);
      color: var(--pastel-blue-ink);
    }
    #pubsPro .btnSmall.pastelBlue:hover{
      background: rgba(59,130,246,.15);
      border-color: rgba(59,130,246,.30);
    }
    #pubsPro .btnSmall.pastelMint{
      background: var(--pastel-mint-bg);
      border-color: var(--pastel-mint-brd);
      color: var(--pastel-mint-ink);
    }
    #pubsPro .btnSmall.pastelMint:hover{
      background: rgba(16,185,129,.15);
      border-color: rgba(16,185,129,.30);
    }
    #pubsPro .btnSmall.pastelRose{
      background: var(--pastel-rose-bg);
      border-color: var(--pastel-rose-brd);
      color: var(--pastel-rose-ink);
    }
    #pubsPro .btnSmall.pastelRose:hover{
      background: rgba(244,63,94,.15);
      border-color: rgba(244,63,94,.30);
    }

    /* Accessibility */
    #pubsPro .sr-only{
      position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden;
      clip:rect(0,0,0,0); white-space:nowrap; border:0;
    }
  </style>

  <div class="shell">
    <div class="hero">
      <div class="ttl">
        <h1>
          <span class="icon" aria-hidden="true">@include('publications.partials.icons', ['name' => 'stack'])</span>
          Publicaciones
        </h1>
        <p>Documentos, imágenes y videos internos. Toca una tarjeta para abrir la vista previa y descargar.</p>
      </div>

      <div class="toolbar">
        @auth
          <a class="btn pastel" href="{{ route('publications.create') }}">
            @include('publications.partials.icons', ['name' => 'upload'])
            Subir
          </a>
        @endauth
      </div>
    </div>

    @if(session('ok'))
      <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    @if($pinned->count())
      <div class="sectionTitle">
        <h2>
          @include('publications.partials.icons', ['name' => 'pin'])
          Fijadas
        </h2>
        <span class="pill">{{ $pinned->count() }}</span>
      </div>

      <div class="grid">
        @foreach($pinned as $p)
          <div class="card"
               role="button"
               tabindex="0"
               data-open-publication
               data-id="{{ $p->id }}"
               data-title="{{ e($p->title) }}"
               data-desc="{{ e($p->description ?? '') }}"
               data-url="{{ e($p->url) }}"
               data-download="{{ e(route('publications.download', $p)) }}"
               data-mime="{{ e($p->mime_type ?? '') }}"
               data-kind="{{ e($p->kind ?? 'file') }}"
               data-size="{{ e($p->nice_size ?? '') }}"
               data-when="{{ e($p->created_at?->diffForHumans() ?? '') }}"
               data-name="{{ e($p->original_name ?? '') }}"
               data-delete="{{ e(route('publications.destroy', $p)) }}"
               aria-label="Abrir publicación {{ e($p->title) }}"
          >
            <div class="top">
              <div style="min-width:0;">
                <h3 class="title">{{ $p->title }}</h3>
                <p class="desc">{{ $p->description ?: '—' }}</p>
              </div>
              <span class="pin">
                @include('publications.partials.icons', ['name' => 'pin'])
                Fijado
              </span>
            </div>

            <div class="preview">
              @if($p->is_image)
                <img src="{{ $p->url }}" alt="{{ $p->title }}">
              @else
                @php $icon = $kindIcon($p); @endphp
                <div class="fileBadge">
                  @include('publications.partials.icons', ['name' => $icon])
                  <div style="display:flex; flex-direction:column; line-height:1.1;">
                    <span class="ext">{{ strtoupper($p->extension ?: $p->kind ?: 'FILE') }}</span>
                    <span class="sub">{{ $p->nice_size }}</span>
                  </div>
                </div>
              @endif
            </div>

            <div class="meta">
              <div class="left">
                <span>{{ $p->created_at->diffForHumans() }}</span>
                <span class="dot"></span>
                <span>{{ $p->nice_size }}</span>
              </div>

              <div class="ghostActions" aria-hidden="true">
                <div class="iconBtn">
                  @include('publications.partials.icons', ['name' => 'arrowUpRight'])
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <div class="sectionTitle">
      <h2>
        @include('publications.partials.icons', ['name' => 'clock'])
        Últimas publicaciones
      </h2>
      <span class="pill">{{ $latest->total() }}</span>
    </div>

    <div class="grid">
      @forelse($latest as $p)
        <div class="card"
             role="button"
             tabindex="0"
             data-open-publication
             data-id="{{ $p->id }}"
             data-title="{{ e($p->title) }}"
             data-desc="{{ e($p->description ?? '') }}"
             data-url="{{ e($p->url) }}"
             data-download="{{ e(route('publications.download', $p)) }}"
             data-mime="{{ e($p->mime_type ?? '') }}"
             data-kind="{{ e($p->kind ?? 'file') }}"
             data-size="{{ e($p->nice_size ?? '') }}"
             data-when="{{ e($p->created_at?->diffForHumans() ?? '') }}"
             data-name="{{ e($p->original_name ?? '') }}"
             data-delete="{{ e(route('publications.destroy', $p)) }}"
             aria-label="Abrir publicación {{ e($p->title) }}"
        >
          <div class="top">
            <div style="min-width:0;">
              <h3 class="title">{{ $p->title }}</h3>
              <p class="desc">{{ $p->description ?: '—' }}</p>
            </div>

            @if($p->pinned)
              <span class="pin">
                @include('publications.partials.icons', ['name' => 'pin'])
                Fijado
              </span>
            @endif
          </div>

          <div class="preview">
            @if($p->is_image)
              <img src="{{ $p->url }}" alt="{{ $p->title }}">
            @else
              @php $icon = $kindIcon($p); @endphp
              <div class="fileBadge">
                @include('publications.partials.icons', ['name' => $icon])
                <div style="display:flex; flex-direction:column; line-height:1.1;">
                  <span class="ext">{{ strtoupper($p->extension ?: $p->kind ?: 'FILE') }}</span>
                  <span class="sub">{{ $p->nice_size }}</span>
                </div>
              </div>
            @endif
          </div>

          <div class="meta">
            <div class="left">
              <span>{{ $p->created_at->diffForHumans() }}</span>
              <span class="dot"></span>
              <span>{{ $p->nice_size }}</span>
            </div>

            <div class="ghostActions" aria-hidden="true">
              <div class="iconBtn">
                @include('publications.partials.icons', ['name' => 'arrowUpRight'])
              </div>
            </div>
          </div>
        </div>
      @empty
        <div style="grid-column: span 12; color: var(--muted); font-weight:900; padding: 8px 2px;">
          No hay publicaciones todavía.
        </div>
      @endforelse
    </div>

    <div class="mt-4">
      {{ $latest->links() }}
    </div>
  </div>

  {{-- Modal --}}
  <div class="modalx" id="pubModal" aria-hidden="true">
    <div class="panel" role="dialog" aria-modal="true" aria-labelledby="pubModalTitle">
      <div class="head">
        <div class="twrap">
          <h3 id="pubModalTitle">—</h3>
          <div class="sub" id="pubModalSub">
            <span id="pubModalWhen">—</span><span class="sep"></span>
            <span id="pubModalSize">—</span><span class="sep"></span>
            <span id="pubModalName">—</span>
          </div>
        </div>

        <div class="actions">
          <a class="btnSmall pastelBlue" id="pubModalDownload" href="#" target="_blank" rel="noopener">
            @include('publications.partials.icons', ['name' => 'download'])
            Descargar
          </a>

          @auth
          <form id="pubModalDeleteForm" method="POST" action="#" style="margin:0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="btnSmall pastelRose" id="pubModalDeleteBtn">
              @include('publications.partials.icons', ['name' => 'trash'])
              Eliminar
            </button>
          </form>
          @endauth

          <button type="button" class="closeBtn" id="pubModalClose" aria-label="Cerrar">
            @include('publications.partials.icons', ['name' => 'x'])
          </button>
        </div>
      </div>

      <div class="body">
        <div class="viewer">
          <div class="inner" id="pubModalViewer">
            {{-- Se inyecta por JS --}}
          </div>
        </div>

        <div class="side">
          <div class="pad">
            <div class="label">Descripción</div>
            <div class="text" id="pubModalDesc">—</div>
          </div>
          <div class="ctaRow">
            <button type="button" class="btnSmall pastelMint" id="pubModalCopyLink">
              @include('publications.partials.icons', ['name' => 'link'])
              Copiar enlace
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      const modal = document.getElementById('pubModal');
      const closeBtn = document.getElementById('pubModalClose');

      const titleEl = document.getElementById('pubModalTitle');
      const descEl  = document.getElementById('pubModalDesc');
      const whenEl  = document.getElementById('pubModalWhen');
      const sizeEl  = document.getElementById('pubModalSize');
      const nameEl  = document.getElementById('pubModalName');
      const viewer  = document.getElementById('pubModalViewer');

      const download = document.getElementById('pubModalDownload');
      const copyBtn  = document.getElementById('pubModalCopyLink');

      const deleteForm = document.getElementById('pubModalDeleteForm');
      const deleteBtn  = document.getElementById('pubModalDeleteBtn');

      let lastActive = null;

      function openModal(payload){
        lastActive = document.activeElement;

        titleEl.textContent = payload.title || '—';
        descEl.textContent  = payload.desc || '—';
        whenEl.textContent  = payload.when || '—';
        sizeEl.textContent  = payload.size || '—';
        nameEl.textContent  = payload.name || '—';

        download.href = payload.download || payload.url || '#';

        // Delete route (solo si existe form)
        if (deleteForm && payload.deleteUrl) {
          deleteForm.action = payload.deleteUrl;
          deleteBtn.disabled = false;
          deleteBtn.dataset.title = payload.title || '';
        }

        // Render viewer
        viewer.innerHTML = '';

        const mime = (payload.mime || '').toLowerCase();
        const kind = (payload.kind || '').toLowerCase();
        const url  = payload.url;

        if (!url) {
          viewer.innerHTML = `<div style="padding:16px;color:#fff;font-weight:900;">Archivo no disponible.</div>`;
        } else if (mime.startsWith('image/') || kind === 'image') {
          const img = document.createElement('img');
          img.src = url;
          img.alt = payload.title || 'Imagen';
          viewer.appendChild(img);
        } else if (mime.startsWith('video/') || kind === 'video') {
          const video = document.createElement('video');
          video.controls = true;
          video.playsInline = true;
          video.src = url;
          viewer.appendChild(video);
        } else if (mime === 'application/pdf' || kind === 'pdf') {
          const iframe = document.createElement('iframe');
          iframe.src = url;
          viewer.appendChild(iframe);
        } else {
          viewer.innerHTML = `
            <div style="height:100%;display:flex;align-items:center;justify-content:center;text-align:center;padding:18px;color:#fff;">
              <div style="max-width:520px;">
                <div style="font-weight:950;letter-spacing:-.02em;font-size:18px;">Vista previa no disponible</div>
                <div style="margin-top:8px;color:rgba(255,255,255,.75);font-weight:900;line-height:1.55;">
                  Este formato no soporta previsualización directa. Descárgalo para abrirlo.
                </div>
                <a href="${(payload.download || url)}" target="_blank" rel="noopener"
                   style="margin-top:14px;display:inline-flex;align-items:center;gap:10px;padding:10px 12px;border-radius:14px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.25);color:#fff;text-decoration:none;font-weight:950;">
                  Descargar archivo
                </a>
              </div>
            </div>
          `;
        }

        modal.classList.add('open');
        modal.setAttribute('aria-hidden','false');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
      }

      function closeModal(){
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden','true');
        viewer.innerHTML = '';
        document.body.style.overflow = '';
        if (lastActive && typeof lastActive.focus === 'function') lastActive.focus();
      }

      // Click cards
      document.querySelectorAll('[data-open-publication]').forEach((el) => {
        const getPayload = () => ({
          id: el.dataset.id || '',
          title: el.dataset.title || '',
          desc: el.dataset.desc || '',
          url: el.dataset.url || '',
          download: el.dataset.download || '',
          mime: el.dataset.mime || '',
          kind: el.dataset.kind || '',
          size: el.dataset.size || '',
          when: el.dataset.when || '',
          name: el.dataset.name || '',
          deleteUrl: el.dataset.delete || '',
        });

        el.addEventListener('click', () => openModal(getPayload()));
        el.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openModal(getPayload());
          }
        });
      });

      // Close interactions
      closeBtn.addEventListener('click', closeModal);
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });
      document.addEventListener('keydown', (e) => {
        if (modal.classList.contains('open') && e.key === 'Escape') closeModal();
      });

      // Confirm delete
      if (deleteForm) {
        deleteForm.addEventListener('submit', (e) => {
          const t = deleteBtn?.dataset?.title || 'esta publicación';
          if (!confirm(`¿Eliminar "${t}"?`)) {
            e.preventDefault();
            return false;
          }
        });
      }

      // Copy link
      copyBtn.addEventListener('click', async () => {
        try{
          const url = download.href || '';
          if (!url) return;
          await navigator.clipboard.writeText(url);

          copyBtn.innerHTML = `{!! str_replace(["\n","\r"],'', view('publications.partials.icons', ['name'=>'check'])->render()) !!} Copiado`;
          setTimeout(() => {
            copyBtn.innerHTML = `{!! str_replace(["\n","\r"],'', view('publications.partials.icons', ['name'=>'link'])->render()) !!} Copiar enlace`;
          }, 1200);
        }catch(err){
          const url = download.href || '';
          const ta = document.createElement('textarea');
          ta.value = url;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          document.body.removeChild(ta);
        }
      });
    })();
  </script>
</div>
@endsection
