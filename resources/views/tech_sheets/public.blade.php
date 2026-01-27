{{-- resources/views/tech_sheets/public.blade.php --}}
@php
  use Illuminate\Support\Str;

  // ✅ Esta vista espera $sheet (igual que show.blade.php)
  // Si tu publicShow() manda otra variable (ej: $techSheet),
  // cambia abajo: $sheet = $techSheet;

  $features = $sheet->ai_features ?? [];
  $specs    = $sheet->ai_specs ?? [];
  $desc     = $sheet->ai_description ?: $sheet->user_description;
  $ref      = $sheet->reference ?? null;
  $ident    = $sheet->identification ?? null;
  $partida  = $sheet->partida_number ?? null;
@endphp

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ficha técnica · {{ $sheet->product_name ?? 'Producto' }}</title>

  <style>
    :root{
      --ink:#0f172a; --muted:#6b7280;
      --bg:#eef2ff; --card:#ffffff;
      --line:#e5e7eb;
      --btn1:#e0f2fe; --btn2:#f3e8ff;
      --header:#020617;
    }

    html,body{ background:var(--bg); margin:0; padding:0; }
    body{ font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }

    #ts-public{
      padding:16px 0 26px;
    }

    #ts-public .ts-wrap{
      max-width:980px;
      margin-inline:auto;
    }

    /* ===== Topbar (solo título) ===== */
    #ts-public .ts-topbar{
      display:flex; align-items:center; justify-content:space-between;
      gap:12px; margin:10px 0 10px;
      padding:0 6px;
    }
    #ts-public .ts-topbar-title{
      font-size:1.1rem;
      font-weight:800;
      color:var(--ink);
    }
    #ts-public .ts-topbar-sub{
      font-size:.82rem;
      color:var(--muted);
      font-weight:600;
      display:flex;
      align-items:center;
      gap:8px;
      flex-wrap:wrap;
      justify-content:flex-end;
      text-align:right;
    }
    #ts-public .ts-dot{
      width:7px;height:7px;border-radius:999px;
      background:linear-gradient(135deg,#22c55e,#a3e635);
      box-shadow:0 0 0 3px #16a34a33;
      display:inline-block;
    }

    /* ===== CARD PRINCIPAL ===== */
    #ts-public .ts-card{
      background:var(--card);
      border-radius:24px;
      box-shadow:0 24px 70px rgba(15,23,42,.16);
      overflow:hidden;
      border:1px solid rgba(148,163,184,.28);
      margin:0 6px;
    }

    /* ===== HEADER OSCURO ===== */
    #ts-public .ts-header{
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

    #ts-public .ts-img-box{
      flex:0 0 240px;
      border-radius:18px;
      overflow:hidden;
      background:#020617;
      display:flex;
      align-items:center;
      justify-content:center;
      position:relative;
    }
    #ts-public .ts-img-box img{
      width:100%;
      height:100%;
      object-fit:cover;
    }
    #ts-public .ts-img-ph{
      color:#9ca3af;
      font-size:.9rem;
    }

    #ts-public .ts-main{
      flex:1 1 260px;
      display:flex;
      flex-direction:column;
      color:#e5e7eb;
      position:relative;
    }

    /* logo empresa (esquina) */
    #ts-public .ts-logo{
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
    #ts-public .ts-logo img{
      height:26px;
      width:auto;
      display:block;
    }
    #ts-public .ts-logo span{
      font-size:.7rem;
      text-transform:uppercase;
      letter-spacing:.14em;
      color:#cbd5f5;
      font-weight:700;
    }

    /* badges */
    #ts-public .ts-badges{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      margin-bottom:6px;
      align-items:center;
    }
    #ts-public .pill{
      border-radius:999px;
      padding:4px 10px;
      font-size:.75rem;
      font-weight:700;
      display:inline-flex;
      align-items:center;
      gap:6px;
    }

    /* logo dentro de la ficha (tipo chip) */
    #ts-public .pill-logo{
      background:#ffffff;
      border:1px solid rgba(148,163,184,.7);
      box-shadow:0 8px 18px rgba(15,23,42,.25);
      padding:4px 12px;
    }
    #ts-public .pill-logo img{
      height:32px;
      width:auto;
      border-radius:999px;
      display:block;
    }
    #ts-public .pill-logo span{ display:none; }

    /* N° Partida */
    #ts-public .pill-partida{
      background:transparent;
      color:#f9fafb;
      border:1px solid rgba(148,163,184,.8);
      backdrop-filter:blur(4px);
      padding-inline:12px;
    }
    #ts-public .pill-partida .label{
      opacity:.85;
      font-weight:600;
    }
    #ts-public .pill-partida .value{
      font-weight:800;
      letter-spacing:.08em;
      text-transform:uppercase;
    }

    #ts-public .ts-ref{
      font-size:.78rem;
      letter-spacing:.14em;
      text-transform:uppercase;
      color:#9ca3af;
      margin:2px 0 8px;
    }
    #ts-public .ts-title{
      font-size:1.45rem;
      font-weight:900;
      letter-spacing:-.02em;
      color:#f9fafb;
    }
    #ts-public .ts-description{
      font-size:.9rem;
      color:#e5e7eb;
      margin-top:8px;
      max-width:60ch;
    }

    #ts-public .ts-tags-row{
      display:flex; flex-wrap:wrap; gap:8px;
      margin-top:12px;
    }
    #ts-public .ts-tag{
      border-radius:999px;
      padding:4px 10px;
      background:rgba(15,23,42,.8);
      border:1px solid rgba(148,163,184,.7);
      font-size:.78rem;
      color:#e5e7eb;
    }

    /* ===== BODY ===== */
    #ts-public .ts-body{
      padding:16px 20px 18px;
      background:#f9fafb;
    }
    #ts-public .ts-section{
      background:#ffffff;
      border-radius:16px;
      border:1px solid var(--line);
      padding:14px 16px 16px;
      margin-bottom:12px;
    }
    #ts-public .ts-section-header{
      display:flex;
      align-items:center;
      gap:8px;
      margin-bottom:10px;
      color:var(--ink);
    }
    #ts-public .ts-section-header-icon{
      width:22px; height:22px;
      border-radius:7px;
      display:grid; place-items:center;
      background:#e0f2fe;
      color:#1d4ed8;
    }
    #ts-public .ts-section-header-icon svg{
      width:14px; height:14px;
    }
    #ts-public .ts-section-title{
      font-size:1rem;
      font-weight:800;
    }

    /* tabla specs */
    #ts-public .ts-table{
      width:100%; border-collapse:collapse;
      font-size:.9rem;
    }
    #ts-public .ts-table thead th{
      background:#f3f4f6;
      color:#4b5563;
      font-weight:700;
      padding:8px 10px;
      border-bottom:1px solid #e5e7eb;
    }
    #ts-public .ts-table td{
      padding:8px 10px;
      border-bottom:1px solid #e5e7eb;
      color:#111827;
    }
    #ts-public .ts-table tr:nth-child(even) td{
      background:#f9fafb;
    }

    /* características */
    #ts-public .ts-list{
      margin:0;
      padding-left:1.1rem;
      font-size:.9rem;
      color:#374151;
    }
    #ts-public .ts-list li{ margin-bottom:4px; }

    /* footer meta */
    #ts-public .ts-footer{
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
    #ts-public .ts-footer-item{
      display:flex;
      align-items:center;
      gap:6px;
    }
    #ts-public .ts-footer-item svg{
      width:14px; height:14px;
    }

    @media (max-width: 768px){
      #ts-public .ts-wrap{ padding:0 4px; }
      #ts-public .ts-header{ flex-direction:column; }
      #ts-public .ts-img-box{ flex:1 1 auto; height:190px; }
      #ts-public .ts-main{ margin-top:8px; }
      #ts-public .ts-logo{
        position:absolute;
        top:8px; right:8px;
        transform:none;
      }
    }
  </style>
</head>

<body>
  <div id="ts-public">
    <div class="ts-wrap">
      <div class="ts-topbar">
        <div class="ts-topbar-title">Ficha técnica</div>
        <div class="ts-topbar-sub">
          <span class="ts-dot"></span>
          <span>Vista pública</span>
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
            {{-- logo empresa (esquina) --}}
            <div class="ts-logo">
              <img src="{{ asset('images/logo-mail.png') }}" alt="Logo empresa">
              <span>JURETO S.A DE C.V.</span>
            </div>

            <div class="ts-badges">
              {{-- chip con logo de la marca: SOLO si hay brand_image_path --}}
              @if($sheet->brand_image_path)
                <div class="pill pill-logo">
                  <img src="{{ asset('storage/'.$sheet->brand_image_path) }}" alt="Logo de la marca">
                </div>
              @endif

              @if($partida)
                <div class="pill pill-partida">
                  <span class="label">N° Partida:</span>
                  <span class="value">{{ $partida }}</span>
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
              @if($ident)
                <span class="ts-tag">{{ Str::limit($ident, 40) }}</span>
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
</body>
</html>
