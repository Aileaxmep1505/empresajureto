{{-- resources/views/tech_sheets/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Ficha técnica · '.$sheet->product_name)

@section('content')
@php
  use Illuminate\Support\Str;

  $features = $sheet->ai_features ?? [];
  $specs    = $sheet->ai_specs ?? [];
  $hasAi    = !empty($sheet->ai_description);
  $desc     = $sheet->ai_description ?: $sheet->user_description;
  $ref      = $sheet->reference ?? null;
  $ident    = $sheet->identification ?? null;
@endphp

<div id="ts-show" class="container my-4">
  <style>
    #ts-show{
      --ink:#0f172a; --muted:#6b7280;
      --bg:#eef2ff; --card:#ffffff;
      --line:#e5e7eb;
      --btn1:#e0f2fe; --btn2:#f3e8ff;
      --header:#020617;
    }
    html,body{ background:var(--bg); }

    #ts-show .ts-wrap{
      max-width:980px;
      margin-inline:auto;
    }

    /* ===== Topbar botones ===== */
    #ts-show .ts-topbar{
      display:flex; align-items:center; justify-content:space-between;
      gap:12px; margin-bottom:10px;
    }
    #ts-show .ts-topbar-title{
      font-size:1.1rem;
      font-weight:800;
      color:var(--ink);
    }
    #ts-show .ts-actions{
      display:flex; gap:8px; flex-wrap:wrap;
    }
    #ts-show .ts-btn{
      border-radius:999px;
      padding:7px 14px;
      font-size:.85rem;
      font-weight:700;
      border:0;
      background:linear-gradient(135deg,var(--btn1),var(--btn2));
      color:#0f172a;
      text-decoration:none;
      display:inline-flex; align-items:center; gap:6px;
      box-shadow:0 6px 16px rgba(15,23,42,.10);
      transition:transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease;
    }
    #ts-show .ts-btn svg{
      width:14px; height:14px;
    }
    #ts-show .ts-btn:hover{
      background:#ffffff;
      color:#111827;
      transform:translateY(-1px);
      box-shadow:0 10px 24px rgba(15,23,42,.18);
    }

    /* ===== CARD PRINCIPAL ===== */
    #ts-show .ts-card{
      background:var(--card);
      border-radius:24px;
      box-shadow:0 24px 70px rgba(15,23,42,.16);
      overflow:hidden;
      border:1px solid rgba(148,163,184,.28);
    }

    /* ===== HEADER OSCURO ===== */
    #ts-show .ts-header{
      background:
        radial-gradient(120% 160% at 0% 0%, #38bdf82e, transparent 55%),
        radial-gradient(140% 180% at 95% 0%, #a855f733, transparent 60%),
        var(--header);
      padding:18px 20px 18px;
      display:flex;
      gap:18px;
      align-items:stretch;
      flex-wrap:wrap;
    }

    #ts-show .ts-img-box{
      flex:0 0 240px;
      border-radius:18px;
      overflow:hidden;
      background:#020617;
      display:flex;
      align-items:center;
      justify-content:center;
      position:relative;
    }
    #ts-show .ts-img-box img{
      width:100%;
      height:100%;
      object-fit:cover;
    }
    #ts-show .ts-img-ph{
      color:#9ca3af;
      font-size:.9rem;
    }

    #ts-show .ts-main{
      flex:1 1 260px;
      display:flex;
      flex-direction:column;
      color:#e5e7eb;
      position:relative;
    }

    /* logo empresa */
    #ts-show .ts-logo{
      position:absolute;
      top:0;
      right:0;
      transform:translate(4px,-10px);
      background:rgba(15,23,42,.88);
      border-radius:999px;
      padding:4px 10px 4px 6px;
      display:inline-flex;
      align-items:center;
      gap:6px;
      border:1px solid rgba(148,163,184,.55);
      box-shadow:0 14px 30px rgba(0,0,0,.45);
    }
    #ts-show .ts-logo img{
      height:26px;
      width:auto;
      display:block;
    }
    #ts-show .ts-logo span{
      font-size:.7rem;
      text-transform:uppercase;
      letter-spacing:.14em;
      color:#cbd5f5;
      font-weight:700;
    }

    /* badges */
    #ts-show .ts-badges{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      margin-bottom:6px;
    }
    #ts-show .pill{
      border-radius:999px;
      padding:4px 10px;
      font-size:.75rem;
      font-weight:700;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }
    /* pastel, sin verde chillón */
    #ts-show .pill-status{
      background:#e0f2fe;
      color:#1d4ed8;
      border:1px solid rgba(59,130,246,.35);
    }
    #ts-show .pill-status span.dot{
      width:7px; height:7px; border-radius:999px;
      background:#60a5fa;
    }
    #ts-show .pill-cat{
      background:#0f172a;
      color:#e5e7eb;
      border:1px solid rgba(148,163,184,.7);
    }

    #ts-show .ts-ref{
      font-size:.78rem;
      letter-spacing:.14em;
      text-transform:uppercase;
      color:#9ca3af;
      margin:2px 0 8px;
    }
    #ts-show .ts-title{
      font-size:1.45rem;
      font-weight:900;
      letter-spacing:-.02em;
      color:#f9fafb;
    }
    #ts-show .ts-ident{
      font-size:.82rem;
      color:#cbd5f5;
      margin-top:4px;
    }
    #ts-show .ts-description{
      font-size:.9rem;
      color:#e5e7eb;
      margin-top:8px;
      max-width:60ch;
    }

    #ts-show .ts-tags-row{
      display:flex; flex-wrap:wrap; gap:8px;
      margin-top:12px;
    }
    #ts-show .ts-tag{
      border-radius:999px;
      padding:4px 10px;
      background:rgba(15,23,42,.8);
      border:1px solid rgba(148,163,184,.7);
      font-size:.78rem;
      color:#e5e7eb;
    }

    /* ===== BODY ===== */
    #ts-show .ts-body{
      padding:16px 20px 18px;
      background:#f9fafb;
    }
    #ts-show .ts-section{
      background:#ffffff;
      border-radius:16px;
      border:1px solid var(--line);
      padding:14px 16px 16px;
      margin-bottom:12px;
    }
    #ts-show .ts-section-header{
      display:flex;
      align-items:center;
      gap:8px;
      margin-bottom:10px;
      color:var(--ink);
    }
    #ts-show .ts-section-header-icon{
      width:22px; height:22px;
      border-radius:7px;
      display:grid; place-items:center;
      background:#e0f2fe;
      color:#1d4ed8;
    }
    #ts-show .ts-section-header-icon svg{
      width:14px; height:14px;
    }
    #ts-show .ts-section-title{
      font-size:1rem;
      font-weight:800;
    }

    /* tabla specs */
    #ts-show .ts-table{
      width:100%; border-collapse:collapse;
      font-size:.9rem;
    }
    #ts-show .ts-table thead th{
      background:#f3f4f6;
      color:#4b5563;
      font-weight:700;
      padding:8px 10px;
      border-bottom:1px solid #e5e7eb;
    }
    #ts-show .ts-table td{
      padding:8px 10px;
      border-bottom:1px solid #e5e7eb;
      color:#111827;
    }
    #ts-show .ts-table tr:nth-child(even) td{
      background:#f9fafb;
    }

    /* características */
    #ts-show .ts-list{
      margin:0;
      padding-left:1.1rem;
      font-size:.9rem;
      color:#374151;
    }
    #ts-show .ts-list li{ margin-bottom:4px; }

    /* footer meta */
    #ts-show .ts-footer{
      margin-top:6px;
      padding-top:10px;
      border-top:1px solid #e5e7eb;
      font-size:.8rem;
      color:var(--muted);
      display:flex;
      flex-wrap:wrap;
      gap:12px;
      justify-content:space-between;
      align-items:center;
    }
    #ts-show .ts-footer-item{
      display:flex;
      align-items:center;
      gap:6px;
    }
    #ts-show .ts-footer-item svg{
      width:14px; height:14px;
    }

    @media (max-width: 768px){
      #ts-show .ts-wrap{ padding:0 4px; }
      #ts-show .ts-header{
        flex-direction:column;
      }
      #ts-show .ts-img-box{
        flex:1 1 auto;
        height:190px;
      }
      #ts-show .ts-main{
        margin-top:8px;
      }
      #ts-show .ts-logo{
        position:absolute;
        top:8px; right:8px;
        transform:none;
      }
    }
  </style>

  <div class="ts-wrap">
    {{-- Topbar acciones --}}
    <div class="ts-topbar">
      <div class="ts-topbar-title">Ficha técnica</div>
      <div class="ts-actions">
        <a href="{{ route('tech-sheets.pdf', $sheet) }}" class="ts-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M7 3h10v4"/><path d="M12 3v10"/><path d="M5 13h14v8H5z"/>
          </svg>
          PDF
        </a>
        <a href="{{ route('tech-sheets.word', $sheet) }}" class="ts-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M4 4h9l5 5v11H4z"/><path d="M9 9l1.5 6L12 11l1.5 4L15 9"/>
          </svg>
          Word
        </a>
      </div>
    </div>

    <article class="ts-card">
      {{-- HEADER --}}
      <header class="ts-header">
        <div class="ts-img-box">
          @if($sheet->image_path)
            <img src="{{ asset('storage/'.$sheet->image_path) }}" alt="{{ $sheet->product_name }}">
          @else
            <div class="ts-img-ph">Sin imagen</div>
          @endif
        </div>

        <div class="ts-main">
          {{-- logo empresa --}}
          <div class="ts-logo">
            <img src="{{ asset('images/logo-mail.png') }}" alt="Logo empresa">
            <span>FICHA TÉCNICA</span>
          </div>

          <div class="ts-badges">
            <div class="pill pill-status">
              <span class="dot"></span>
              {{ $hasAi ? 'Generada con IA' : 'Borrador' }}
            </div>
            @if($ident)
              <div class="pill pill-cat">
                {{ Str::limit($ident, 26) }}
              </div>
            @endif
          </div>

          @if($ref)
            <div class="ts-ref">{{ Str::upper($ref) }}</div>
          @endif

          <h1 class="ts-title">{{ $sheet->product_name }}</h1>

          @if($desc)
            <p class="ts-description">{{ $desc }}</p>
          @endif

          <div class="ts-tags-row">
            @if($sheet->brand)
              <span class="ts-tag">Marca: {{ $sheet->brand }}</span>
            @endif
            @if($sheet->model)
              <span class="ts-tag">Modelo: {{ $sheet->model }}</span>
            @endif
          </div>
        </div>
      </header>

      {{-- BODY --}}
      <div class="ts-body">

        {{-- ESPECIFICACIONES TÉCNICAS --}}
        @if(!empty($specs))
          <section class="ts-section">
            <div class="ts-section-header">
              <div class="ts-section-header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="4" height="16"/><rect x="10" y="8" width="4" height="12"/><rect x="17" y="11" width="4" height="9"/>
                </svg>
              </div>
              <div class="ts-section-title">Especificaciones técnicas</div>
            </div>
            <div class="table-responsive">
              <table class="ts-table">
                <thead>
                  <tr>
                    <th style="width:45%;">Característica</th>
                    <th style="width:55%;">Valor</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($specs as $spec)
                    @php
                      $name  = $spec['nombre'] ?? '';
                      $value = $spec['valor'] ?? '';
                    @endphp
                    <tr>
                      <td>{{ $name }}</td>
                      <td>{{ $value }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </section>
        @endif

        {{-- CARACTERÍSTICAS DESTACADAS --}}
        @if(!empty($features))
          <section class="ts-section">
            <div class="ts-section-header">
              <div class="ts-section-header-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 2l2.3 6.9H21l-5.3 3.9 2 6.9L12 16l-5.7 3.7 2-6.9L3 8.9h6.7z"/>
                </svg>
              </div>
              <div class="ts-section-title">Características destacadas</div>
            </div>
            <ul class="ts-list">
              @foreach($features as $f)
                <li>{{ $f }}</li>
              @endforeach
            </ul>
          </section>
        @endif

        {{-- FOOTER META --}}
        <footer class="ts-footer">
          <div class="ts-footer-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M3 10h18"/><path d="M8 2v4"/><path d="M16 2v4"/>
            </svg>
            <span>Creado: {{ optional($sheet->created_at)->format('d/m/Y H:i') ?? '—' }}</span>
          </div>
          <div class="ts-footer-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10"/><path d="M7 12h6"/>
            </svg>
            <span>Código: {{ $ref ?: '—' }}</span>
          </div>
        </footer>
      </div>
    </article>
  </div>
</div>
@endsection
