@extends('layouts.app')
@section('title', 'WhatsApp · Conversaciones')

@section('content')
@php
    $statusLabel = function($status){
        return match((string)$status){
            'open' => 'Abierta',
            'pending' => 'Pendiente',
            'closed' => 'Cerrada',
            default => ucfirst((string)$status),
        };
    };

    $statusClass = function($status){
        return match((string)$status){
            'open' => 'is-open',
            'pending' => 'is-pending',
            'closed' => 'is-closed',
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
@endphp

<style>
  .wa-shell{
    --wa-bg:#0b141a;
    --wa-panel:#111b21;
    --wa-panel-2:#202c33;
    --wa-border:#2a3942;
    --wa-text:#e9edef;
    --wa-muted:#8696a0;
    --wa-soft:#0f1a20;
    --wa-accent:#00a884;
    --wa-accent-soft:rgba(0,168,132,.14);
    --wa-hover:#182229;
    --wa-card:#111b21;
    --wa-white:#ffffff;

    width:100%;
  }

  .wa-app{
    background:var(--wa-bg);
    border-radius:24px;
    overflow:hidden;
    min-height:calc(100vh - 150px);
    border:1px solid rgba(255,255,255,.04);
    box-shadow:0 20px 60px rgba(0,0,0,.28);
  }

  .wa-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    padding:18px 22px;
    background:linear-gradient(180deg, #0f171d 0%, #0c1318 100%);
    border-bottom:1px solid rgba(255,255,255,.04);
  }

  .wa-title-wrap h1{
    margin:0;
    color:var(--wa-white);
    font-size:1.25rem;
    font-weight:800;
    letter-spacing:.2px;
  }

  .wa-title-wrap p{
    margin:4px 0 0;
    color:var(--wa-muted);
    font-size:.93rem;
  }

  .wa-count{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:999px;
    background:rgba(255,255,255,.04);
    color:var(--wa-text);
    font-size:.9rem;
    white-space:nowrap;
  }

  .wa-count b{
    color:#7ef0cf;
  }

  .wa-body{
    display:grid;
    grid-template-columns: 380px 1fr;
    min-height:calc(100vh - 230px);
  }

  .wa-sidebar{
    background:var(--wa-panel);
    border-right:1px solid var(--wa-border);
    display:flex;
    flex-direction:column;
    min-width:0;
  }

  .wa-sidebar-head{
    padding:16px;
    border-bottom:1px solid var(--wa-border);
    background:var(--wa-panel);
  }

  .wa-search{
    position:relative;
  }

  .wa-search input{
    width:100%;
    height:46px;
    border:none;
    outline:none;
    border-radius:14px;
    background:var(--wa-soft);
    color:var(--wa-text);
    padding:0 16px 0 44px;
    font-size:.95rem;
    box-shadow:inset 0 0 0 1px rgba(255,255,255,.03);
  }

  .wa-search input::placeholder{
    color:var(--wa-muted);
  }

  .wa-search svg{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    width:18px;
    height:18px;
    color:var(--wa-muted);
  }

  .wa-list{
    overflow:auto;
    padding:8px 0;
  }

  .wa-item{
    display:flex;
    align-items:center;
    gap:14px;
    padding:14px 16px;
    text-decoration:none;
    border-bottom:1px solid rgba(255,255,255,.03);
    transition:.18s ease;
  }

  .wa-item:hover{
    background:var(--wa-hover);
  }

  .wa-avatar{
    width:52px;
    height:52px;
    border-radius:50%;
    display:grid;
    place-items:center;
    flex:0 0 52px;
    background:linear-gradient(135deg, #1f7ae0, #00a884);
    color:#fff;
    font-weight:800;
    font-size:.95rem;
    letter-spacing:.4px;
    box-shadow:0 6px 18px rgba(0,0,0,.18);
  }

  .wa-item-main{
    min-width:0;
    flex:1;
  }

  .wa-item-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:4px;
  }

  .wa-name{
    color:var(--wa-text);
    font-weight:700;
    font-size:.98rem;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .wa-time{
    color:var(--wa-muted);
    font-size:.78rem;
    white-space:nowrap;
  }

  .wa-preview{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
  }

  .wa-phone{
    color:var(--wa-muted);
    font-size:.88rem;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .wa-meta{
    display:flex;
    align-items:center;
    gap:8px;
    flex-shrink:0;
  }

  .wa-badge{
    min-width:22px;
    height:22px;
    padding:0 7px;
    border-radius:999px;
    display:grid;
    place-items:center;
    font-size:.72rem;
    font-weight:800;
    color:#06281f;
    background:#25d366;
  }

  .wa-status{
    font-size:.72rem;
    padding:5px 8px;
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

  .wa-status.is-closed{
    background:rgba(255,255,255,.06);
    color:#c5d1d7;
    border-color:rgba(255,255,255,.08);
  }

  .wa-status.is-neutral{
    background:rgba(255,255,255,.06);
    color:#c5d1d7;
    border-color:rgba(255,255,255,.08);
  }

  .wa-preview-panel{
    background:
      radial-gradient(circle at top left, rgba(0,168,132,.08), transparent 30%),
      radial-gradient(circle at bottom right, rgba(31,122,224,.08), transparent 30%),
      #0b141a;
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    min-width:0;
  }

  .wa-preview-card{
    width:min(560px, 88%);
    background:rgba(17,27,33,.88);
    border:1px solid rgba(255,255,255,.05);
    border-radius:26px;
    padding:28px;
    color:var(--wa-text);
    box-shadow:0 24px 80px rgba(0,0,0,.28);
    backdrop-filter:blur(8px);
  }

  .wa-preview-card .icon{
    width:64px;
    height:64px;
    border-radius:18px;
    display:grid;
    place-items:center;
    background:var(--wa-accent-soft);
    color:#7ef0cf;
    margin-bottom:18px;
  }

  .wa-preview-card h2{
    margin:0 0 10px;
    font-size:1.35rem;
    font-weight:800;
  }

  .wa-preview-card p{
    margin:0;
    color:var(--wa-muted);
    line-height:1.7;
    font-size:.96rem;
  }

  .wa-alert{
    margin-bottom:18px;
    border:none;
    border-radius:14px;
  }

  .wa-pagination{
    padding:14px 18px;
    border-top:1px solid var(--wa-border);
    background:#0f171d;
  }

  .wa-empty{
    color:var(--wa-muted);
    text-align:center;
    padding:36px 18px;
  }

  @media (max-width: 991.98px){
    .wa-body{
      grid-template-columns:1fr;
    }
    .wa-preview-panel{
      display:none;
    }
    .wa-app{
      min-height:auto;
    }
  }
</style>

<div class="wa-shell container-fluid px-0">
  @if(session('ok'))
    <div class="alert alert-success wa-alert">{{ session('ok') }}</div>
  @endif

  <div class="wa-app">
    <div class="wa-top">
      <div class="wa-title-wrap">
        <h1>Conversaciones WhatsApp</h1>
        <p>Vista limpia, rápida y profesional para atención y seguimiento.</p>
      </div>

      <div class="wa-count">
        Total:
        <b>{{ method_exists($conversations, 'total') ? $conversations->total() : $conversations->count() }}</b>
      </div>
    </div>

    <div class="wa-body">
      <aside class="wa-sidebar">
        <div class="wa-sidebar-head">
          <form method="GET" class="wa-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="7"></circle>
              <path d="m20 20-3.5-3.5"></path>
            </svg>
            <input
              type="text"
              name="q"
              value="{{ request('q') }}"
              placeholder="Buscar por teléfono, usuario o agente..."
              autocomplete="off"
            >
          </form>
        </div>

        <div class="wa-list">
          @forelse($conversations as $c)
            @php
              $displayName = $c->user->name ?? ('Contacto '.$c->phone);
            @endphp

            <a href="{{ route('admin.whatsapp.conversations.show', $c) }}" class="wa-item">
              <div class="wa-avatar">
                {{ $initials($c->user->name ?? '', $c->phone) }}
              </div>

              <div class="wa-item-main">
                <div class="wa-item-top">
                  <div class="wa-name">{{ $displayName }}</div>
                  <div class="wa-time">
                    {{ optional($c->last_message_at)->format('h:i A') ?: '—' }}
                  </div>
                </div>

                <div class="wa-preview">
                  <div class="wa-phone">
                    {{ $c->phone }} · {{ $c->agent->name ?? 'Sin agente' }}
                  </div>

                  <div class="wa-meta">
                    <span class="wa-status {{ $statusClass($c->status) }}">
                      {{ $statusLabel($c->status) }}
                    </span>

                    @if((int)$c->messages_count > 0)
                      <span class="wa-badge">{{ $c->messages_count }}</span>
                    @endif
                  </div>
                </div>
              </div>
            </a>
          @empty
            <div class="wa-empty">
              Sin conversaciones.
            </div>
          @endforelse
        </div>

        @if(method_exists($conversations, 'links'))
          <div class="wa-pagination">
            {{ $conversations->withQueryString()->links() }}
          </div>
        @endif
      </aside>

      <section class="wa-preview-panel">
        <div class="wa-preview-card">
          <div class="icon">
            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.9">
              <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>
            </svg>
          </div>
          <h2>Selecciona una conversación</h2>
          <p>
            Abre cualquier chat del panel izquierdo para revisar mensajes, tomar la conversación,
            responder al cliente y mantener un flujo visual similar a WhatsApp Web.
          </p>
        </div>
      </section>
    </div>
  </div>
</div>
@endsection