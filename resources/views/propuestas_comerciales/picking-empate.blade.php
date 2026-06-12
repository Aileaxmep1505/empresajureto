@extends('layouts.app')
@section('content_class', 'content--flush')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap" rel="stylesheet">

<style>
  :root{
    --bg:#f9fafb;--card:#fff;--input-bg:#f9fafb;--ink-dark:#111;--ink:#333;--muted:#888;--muted-light:#b8b8b8;
    --line:#ebebeb;--blue:#007aff;--blue-soft:#e6f0ff;--success:#15803d;--success-soft:#e6ffe6;
    --warning:#c2410c;--warning-soft:#fff7ed;--danger:#ff4a4a;--danger-soft:#ffebeb;
  }

  .pk-page{
    font-family:'Quicksand',sans-serif;
    background:var(--bg);
    color:var(--ink);
    min-height:100vh;
    padding:28px 20px;
    font-weight:500;
    -webkit-font-smoothing:antialiased;
  }

  .pk-page *{box-sizing:border-box;}
  .pk-wrap{max-width:1180px;margin:0 auto;}

  .pk-page h1{
    color:var(--ink-dark);
    font-size:22px;
    margin:0 0 6px;
    font-weight:700;
    letter-spacing:-.4px;
  }

  .pk-sub{
    color:var(--muted);
    font-size:14px;
    font-weight:600;
    margin:0 0 24px;
  }

  .back-link{
    display:inline-flex;
    align-items:center;
    gap:7px;
    color:var(--muted);
    font-weight:600;
    font-size:14px;
    margin-bottom:18px;
    text-decoration:none;
  }

  .back-link:hover{color:var(--blue);}

  .btn{
    font-family:inherit;
    font-weight:700;
    height:42px;
    padding:0 20px;
    border-radius:8px;
    border:1px solid transparent;
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    font-size:13.5px;
    text-decoration:none;
    transition:transform .18s,background .2s,box-shadow .2s,border-color .2s,color .2s;
  }

  .btn:hover{transform:translateY(-1px);}
  .btn:active{transform:scale(.98);}
  .btn:disabled{opacity:.55;cursor:not-allowed;transform:none;}

  .btn-primary{background:var(--blue);color:#fff;}
  .btn-primary:hover{background:#0066d6;}

  .btn-outline{background:#fff;color:var(--blue);border-color:var(--blue);}
  .btn-outline:hover{background:var(--blue-soft);}

  .btn-icon{
    flex:0 0 auto;
    width:36px;
    height:36px;
    padding:0;
    border-radius:8px;
    background:var(--input-bg);
    border:1px solid var(--line);
    color:var(--muted);
    cursor:pointer;
    display:inline-flex;
    align-items:center;
    justify-content:center;
  }

  .btn-icon:hover{
    background:#fff;
    color:var(--blue);
    border-color:var(--blue);
  }

  .pk-toolbar{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:24px;
    align-items:center;
  }

  .pk-card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:16px;
    padding:24px;
    margin-bottom:24px;
    box-shadow:0 4px 12px rgba(0,0,0,.02);
  }

  .pk-config{
    display:flex;
    gap:16px;
    flex-wrap:wrap;
    align-items:flex-end;
    margin-bottom:24px;
  }

  .pk-field{
    display:flex;
    flex-direction:column;
    gap:6px;
  }

  .pk-field label{
    font-size:11px;
    font-weight:700;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.04em;
  }

  .table-responsive{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
    margin-bottom:12px;
  }

  table{
    width:100%;
    border-collapse:collapse;
    min-width:980px;
  }

  th{
    text-align:left;
    font-size:11px;
    color:var(--muted);
    font-weight:700;
    padding:12px 10px;
    border-bottom:1px solid var(--line);
    text-transform:uppercase;
    letter-spacing:.04em;
    white-space:nowrap;
  }

  td{
    font-size:13.5px;
    color:var(--ink);
    padding:12px 10px;
    border-bottom:1px solid var(--line);
    vertical-align:top;
  }

  tr:last-child td{border-bottom:none;}

  .tr{text-align:right;}
  .tc{text-align:center;}

  .prod-box{min-width:240px;}

  .prod-empty{
    color:var(--muted-light);
    font-weight:600;
    font-style:italic;
  }

  .prod-name{
    color:var(--ink-dark);
    font-weight:700;
  }

  .prod-meta{
    font-size:11.5px;
    color:var(--muted);
    font-weight:600;
    margin-top:3px;
  }

  .rowthumb{
    flex:0 0 auto;
    width:38px;
    height:38px;
    border-radius:8px;
    background:var(--input-bg);
    border:1px solid var(--line);
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--muted-light);
  }

  .rowthumb img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  .badge{
    display:inline-block;
    padding:4px 10px;
    border-radius:999px;
    font-size:11px;
    font-weight:700;
  }

  .badge-ok{background:var(--success-soft);color:var(--success);}
  .badge-buy{background:var(--warning-soft);color:var(--warning);}
  .badge-none{background:var(--danger-soft);color:var(--danger);}

  .num-strong{
    font-weight:700;
    color:var(--ink-dark);
  }

  .num-buy{
    font-weight:700;
    color:var(--warning);
  }

  .pk-totals{
    display:flex;
    justify-content:flex-end;
    gap:28px;
    font-size:14px;
    font-weight:600;
    color:var(--muted);
    margin-top:20px;
    padding-top:20px;
    border-top:1px solid var(--line);
    flex-wrap:wrap;
  }

  .pk-totals span{
    display:flex;
    align-items:center;
    gap:8px;
  }

  .pk-totals strong{
    color:var(--ink-dark);
    font-size:17px;
    font-weight:700;
  }

  .pk-totals .buy strong{color:var(--warning);}

  .status{
    font-size:13px;
    font-weight:600;
    color:var(--blue);
    min-height:18px;
    display:flex;
    align-items:center;
    gap:6px;
  }

  .loader{
    display:inline-block;
    width:14px;
    height:14px;
    border:2px solid rgba(0,0,0,.15);
    border-radius:50%;
    border-top-color:currentColor;
    animation:spin .8s linear infinite;
  }

  @keyframes spin{
    to{transform:rotate(360deg);}
  }

  /* ===== MODAL REDISEÑADO ===== */
  .modal-backdrop{
    position:fixed;
    inset:0;
    z-index:9999;
    display:none;
    align-items:center;
    justify-content:center;
    padding:16px;
    background:rgba(17,17,17,.4);
    backdrop-filter:blur(4px);
  }

  .modal-backdrop.show{display:flex;}

  .modal{
    width:min(860px,100%);
    max-height:calc(100vh - 32px);
    background:#fff;
    border-radius:18px;
    border:1px solid var(--line);
    display:flex;
    flex-direction:column;
    overflow:hidden;
    box-shadow:0 30px 60px -15px rgba(17,17,17,.28);
    animation:mIn .28s cubic-bezier(.16,1,.3,1);
  }

  @keyframes mIn{
    from{opacity:0;transform:scale(.97) translateY(10px);}
    to{opacity:1;transform:none;}
  }

  .modal-head{
    padding:20px 24px;
    border-bottom:1px solid var(--line);
    display:flex;
    justify-content:space-between;
    align-items:center;
  }

  .modal-head h2{
    margin:0;
    font-size:18px;
    color:var(--ink-dark);
    font-weight:700;
    letter-spacing:-.3px;
  }

  .modal-close{
    border:0;
    background:var(--input-bg);
    cursor:pointer;
    color:var(--muted);
    width:34px;
    height:34px;
    border-radius:9px;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .modal-close:hover{
    background:#f0f0f0;
    color:var(--ink-dark);
  }

  .modal-body{
    padding:22px 24px;
    overflow-y:auto;
  }

  .inp{
    font-family:inherit;
    font-weight:600;
    font-size:13px;
    height:46px;
    padding:0 12px;
    background:#fff;
    border:1px solid var(--line);
    border-radius:10px;
    color:var(--ink-dark);
    width:100%;
  }

  .inp:focus{
    outline:none;
    border-color:var(--blue);
    box-shadow:0 0 0 3px var(--blue-soft);
  }

  select.inp{
    height:44px;
    min-width:220px;
  }

  /* ===== REFERENCIA ARRIBA DEL BUSCADOR ===== */
  .search-reference-box{
    background:#f8fafc;
    border:1px solid var(--line);
    border-radius:14px;
    padding:14px 16px;
    margin-bottom:14px;
  }

  .search-reference-top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:8px;
  }

  .search-reference-label{
    color:var(--muted);
    font-size:11px;
    font-weight:700;
    letter-spacing:.08em;
    text-transform:uppercase;
  }

  .search-reference-qty{
    flex:0 0 auto;
    background:var(--blue-soft);
    color:var(--blue);
    border:1px solid rgba(0,122,255,.18);
    border-radius:999px;
    padding:5px 10px;
    font-size:12px;
    font-weight:700;
    white-space:nowrap;
  }

  .search-reference-text{
    color:var(--ink-dark);
    font-size:13px;
    font-weight:700;
    line-height:1.45;
    text-transform:uppercase;
    max-height:64px;
    overflow:auto;
  }

  .search-actions{
    display:flex;
    gap:8px;
    margin-top:10px;
    flex-wrap:wrap;
  }

  .mini-action{
    border:1px solid var(--blue);
    background:#fff;
    color:var(--blue);
    border-radius:9px;
    height:32px;
    padding:0 12px;
    font-family:inherit;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
  }

  .mini-action:hover{
    background:var(--blue-soft);
  }

  .search-wrap{
    position:relative;
    margin-bottom:14px;
  }

  .search-wrap .s-ic{
    position:absolute;
    left:15px;
    top:50%;
    transform:translateY(-50%);
    color:var(--muted-light);
    pointer-events:none;
  }

  .search-wrap .inp{
    padding-left:44px;
    padding-right:48px;
    height:50px;
    font-size:14px;
  }

  .search-clear{
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    width:32px;
    height:32px;
    border:0;
    border-radius:9px;
    background:#f3f4f6;
    color:#777;
    font-size:22px;
    line-height:1;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .search-clear:hover{
    background:var(--danger-soft);
    color:var(--danger);
  }

  .char-help{
    color:var(--muted);
    font-size:12px;
    font-weight:600;
    margin:-6px 0 14px;
  }

  .char-help strong{
    color:var(--blue);
  }

  .reco-banner{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    background:var(--blue-soft);
    border:1px solid rgba(0,122,255,.16);
    border-radius:12px;
    padding:10px 14px;
    margin-bottom:14px;
    font-size:12.5px;
    font-weight:600;
    color:var(--blue);
  }

  .reco-banner b{color:var(--ink-dark);}

  .link-all{
    background:#fff;
    border:1px solid var(--blue);
    color:var(--blue);
    font-weight:700;
    font-size:12px;
    border-radius:8px;
    padding:7px 12px;
    cursor:pointer;
    white-space:nowrap;
  }

  .link-all:hover{
    background:var(--blue);
    color:#fff;
  }

  .pcard{
    display:flex;
    gap:14px;
    align-items:center;
    padding:12px 14px;
    border:1px solid var(--line);
    border-radius:14px;
    margin-bottom:10px;
    background:#fff;
    transition:border-color .15s,box-shadow .15s,transform .12s;
  }

  .pcard:hover{
    border-color:var(--blue);
    box-shadow:0 6px 16px rgba(0,122,255,.08);
    transform:translateY(-1px);
  }

  .pthumb{
    flex:0 0 auto;
    width:58px;
    height:58px;
    border-radius:11px;
    background:var(--input-bg);
    border:1px solid var(--line);
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--muted-light);
  }

  .pthumb img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }

  .pinfo{
    flex:1 1 auto;
    min-width:0;
  }

  .pname{
    font-weight:700;
    color:var(--ink-dark);
    font-size:14px;
    line-height:1.3;
  }

  .pmeta{
    font-size:11.5px;
    color:var(--muted);
    font-weight:600;
    margin-top:2px;
  }

  .pchips{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
    margin-top:7px;
  }

  .chip{
    font-size:11px;
    font-weight:700;
    padding:3px 9px;
    border-radius:999px;
  }

  .chip-stock{background:var(--success-soft);color:var(--success);}
  .chip-stock0{background:var(--danger-soft);color:var(--danger);}
  .chip-loc{background:var(--blue-soft);color:var(--blue);}

  .cl-empty{
    color:var(--muted-light);
    font-size:14px;
    font-weight:600;
    text-align:center;
    padding:34px;
    border:1px dashed var(--line);
    border-radius:12px;
  }

  #prodStatus{
    margin-bottom:12px;
  }

  @media (max-width:640px){
    .modal-body{
      padding:18px;
    }

    .search-reference-top{
      flex-direction:column;
      align-items:flex-start;
    }

    .reco-banner{
      flex-direction:column;
      align-items:flex-start;
    }

    .link-all{
      width:100%;
    }

    .pcard{
      align-items:flex-start;
    }
  }
</style>

<div class="pk-page">
  <div class="pk-wrap">
    <a href="{{ route('propuestas-comerciales.resultado.show', $resultado) }}" class="back-link">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="m15 18-6-6 6-6"/>
      </svg>
      Volver al resultado
    </a>

    <h1>Surtido / Picking · {{ $folio }}</h1>
    <p class="pk-sub">
      Cliente:
      <strong style="color:var(--ink-dark);">{{ $cliente }}</strong>
      &nbsp;·&nbsp; Empata cada partida con un producto del catálogo. Lo que falte de stock se crea como recolección virtual.
    </p>

    <div class="status" id="status" style="margin-bottom:18px;"></div>

    @if(count($partidas))
      <div class="pk-card">
        <div class="pk-config">
          <div class="pk-field">
            <label>Asignar a (operador)</label>
            <select class="inp" id="assignedUser">
              <option value="">— Selecciona —</option>
              @foreach($users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="pk-field">
            <label>Número de pedido (opcional)</label>
            <input type="text" class="inp" id="orderNumber" placeholder="{{ $folio }}" style="min-width:220px;">
          </div>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Descripción (propuesta)</th>
                <th class="tc">Unidad</th>
                <th class="tr">Pedido</th>
                <th>Producto del catálogo</th>
                <th class="tr">En stock</th>
                <th class="tr">Por comprar</th>
                <th class="tc">Estado</th>
              </tr>
            </thead>

            <tbody>
              @foreach($partidas as $p)
                <tr class="pk-row"
                    data-partida-id="{{ $p['partida_id'] }}"
                    data-desc="{{ $p['desc'] }}"
                    data-unidad="{{ $p['unidad'] }}"
                    data-cantidad="{{ $p['cantidad'] }}">
                  <td class="num-strong">{{ $p['num'] }}</td>

                  <td style="min-width:240px;">{{ $p['desc'] }}</td>

                  <td class="tc">{{ $p['unidad'] }}</td>

                  <td class="tr num-strong">{{ $p['cantidad'] }}</td>

                  <td>
                    <div style="display:flex;gap:8px;align-items:flex-start;">
                      <div class="prod-box">
                        <span class="prod-empty p-display">Sin asignar</span>
                      </div>

                      <button type="button" class="btn-icon" title="Buscar producto" onclick="openProductoModal(this)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                          <circle cx="11" cy="11" r="8"/>
                          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                      </button>
                    </div>

                    <input type="hidden" class="p-product-id" value="">
                    <input type="hidden" class="p-product-name" value="">
                    <input type="hidden" class="p-sku" value="">
                    <input type="hidden" class="p-stock" value="0">
                    <input type="hidden" class="p-image" value="">
                  </td>

                  <td class="tr p-instock num-strong">—</td>
                  <td class="tr p-tobuy num-buy">—</td>
                  <td class="tc p-status"><span class="badge badge-none">Sin asignar</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="pk-totals">
          <span>Partidas: <strong id="tPart">{{ count($partidas) }}</strong></span>
          <span>Total pedido: <strong id="tPedido">0</strong></span>
          <span>En stock: <strong id="tStock">0</strong></span>
          <span class="buy">Por comprar: <strong id="tBuy">0</strong></span>
        </div>
      </div>

      <div class="pk-toolbar">
        <button type="button" class="btn btn-outline" onclick="verResumen()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 11l3 3L22 4"/>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
          </svg>
          Ver resumen
        </button>

        <button type="button" class="btn btn-primary" id="btnEnviar" onclick="enviarAPicking()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"/>
            <path d="m12 5 7 7-7 7"/>
          </svg>
          Enviar a Picking
        </button>
      </div>
    @else
      <div class="pk-card">
        <p style="color:var(--muted-light);padding:28px;text-align:center;font-weight:600;">
          No hay partidas ganadas para surtir.
        </p>
      </div>
    @endif
  </div>

  {{-- Modal búsqueda de producto --}}
  <div class="modal-backdrop" id="prodModal">
    <div class="modal">
      <div class="modal-head">
        <h2>Buscar producto del catálogo</h2>

        <button type="button" class="modal-close" onclick="closeProductoModal()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div class="modal-body">
        <div class="search-reference-box">
          <div class="search-reference-top">
            <div class="search-reference-label">Referencia original</div>
            <div class="search-reference-qty" id="searchReferenceQty">Cantidad: —</div>
          </div>

          <div class="search-reference-text" id="searchReferenceText">—</div>

          <div class="search-actions">
            <button type="button" class="mini-action" onclick="restaurarBusquedaOriginal()">
              Restaurar búsqueda
            </button>

            <button type="button" class="mini-action" onclick="usarPrimerasPalabras()">
              Buscar más corto
            </button>
          </div>
        </div>

        <div class="search-wrap">
          <svg class="s-ic" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
          </svg>

          <input
            type="text"
            class="inp"
            id="prodSearch"
            maxlength="120"
            placeholder="Nombre, SKU, código o ID..."
            oninput="handleProductoSearchInput()"
          >

          <button type="button" class="search-clear" onclick="limpiarBusquedaProducto()" title="Limpiar búsqueda" aria-label="Limpiar búsqueda">
            ×
          </button>
        </div>

        <div class="char-help">
          Búsqueda limitada a <strong>120 caracteres</strong> para evitar errores.
        </div>

        <div class="reco-banner" id="recoBanner">
          <span id="recoText"></span>
          <button type="button" class="link-all" onclick="verTodo()">Ver todo el catálogo</button>
        </div>

        <div class="status" id="prodStatus"></div>
        <div id="prodResults"></div>
      </div>
    </div>
  </div>

  {{-- Modal resumen --}}
  <div class="modal-backdrop" id="resumenModal">
    <div class="modal">
      <div class="modal-head">
        <h2>Resumen de surtido</h2>

        <button type="button" class="modal-close" onclick="document.getElementById('resumenModal').classList.remove('show')">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div class="modal-body" id="resumenBody"></div>
    </div>
  </div>
</div>

<script>
  const csrfToken = @json(csrf_token());
  const buscarUrl = @json(route('propuestas-comerciales.resultado.picking.buscar-producto', $resultado));
  const crearUrl  = @json(route('propuestas-comerciales.resultado.picking.crear', $resultado));

  let prodTargetRow = null;
  let prodTimer = null;
  let prodResults = [];
  let recoDesc = '';
  let recoSearch = '';
  let recoUnidad = '';
  let recoCantidad = '';

  function esc(v){
    return String(v ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function normalizeSearchText(value){
    return String(value ?? '')
      .trim()
      .replace(/\s+/g, ' ');
  }

  function limitSearchText(value){
    return normalizeSearchText(value).substring(0, 120);
  }

  function compactSearchText(value){
    const text = normalizeSearchText(value);
    if (!text) return '';

    const words = text.split(' ');
    let result = '';

    for (const word of words) {
      const next = result ? `${result} ${word}` : word;
      if (next.length > 120) break;
      result = next;
    }

    return result || text.substring(0, 120);
  }

  function phIcon(){
    return '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/></svg>';
  }

  function thumbHtml(image, size, cls){
    const c = cls || 'pthumb';
    const st = `width:${size}px;height:${size}px;`;

    if(image){
      return `<div class="${c}" style="${st}"><img src="${esc(image)}" onerror="this.parentNode.innerHTML=phIcon()"></div>`;
    }

    return `<div class="${c}" style="${st}">${phIcon()}</div>`;
  }

  function handleProductoSearchInput(){
    const input = document.getElementById('prodSearch');
    const limited = limitSearchText(input.value);

    if (input.value !== limited) {
      input.value = limited;
    }

    scheduleProductoSearch();
  }

  function limpiarBusquedaProducto(){
    const input = document.getElementById('prodSearch');
    input.value = '';
    input.focus();

    document.getElementById('prodResults').innerHTML = '';
    document.getElementById('prodStatus').textContent = '';
    document.getElementById('recoText').innerHTML = 'Búsqueda limpia. Escribe una palabra clave o usa “Ver todo el catálogo”.';
  }

  function restaurarBusquedaOriginal(){
    const input = document.getElementById('prodSearch');
    input.value = recoSearch;
    input.focus();
    input.select();
    scheduleProductoSearch(80);
  }

  function usarPrimerasPalabras(){
    const input = document.getElementById('prodSearch');
    input.value = compactSearchText(recoDesc);
    input.focus();
    input.select();
    scheduleProductoSearch(80);
  }

  function openProductoModal(btn){
    prodTargetRow = btn.closest('.pk-row');

    recoDesc = prodTargetRow.dataset.desc || '';
    recoUnidad = prodTargetRow.dataset.unidad || '';
    recoCantidad = prodTargetRow.dataset.cantidad || '';
    recoSearch = limitSearchText(recoDesc);

    const input = document.getElementById('prodSearch');
    const referenceText = document.getElementById('searchReferenceText');
    const referenceQty = document.getElementById('searchReferenceQty');

    input.value = recoSearch;

    referenceText.textContent = recoDesc || 'Sin referencia';
    referenceQty.textContent = `Cantidad: ${recoCantidad || '—'} ${recoUnidad || ''}`.trim();

    document.getElementById('prodResults').innerHTML = '';
    document.getElementById('prodStatus').textContent = '';
    document.getElementById('recoText').innerHTML = recoSearch
      ? `Sugerencias para: <b>${esc(recoSearch)}</b>`
      : 'Escribe una palabra clave para buscar.';

    prodResults = [];

    document.getElementById('prodModal').classList.add('show');

    setTimeout(() => {
      input.focus();
      input.select();
    }, 100);

    scheduleProductoSearch(50);
  }

  function closeProductoModal(){
    document.getElementById('prodModal').classList.remove('show');
    prodTargetRow = null;
  }

  function scheduleProductoSearch(d = 350){
    clearTimeout(prodTimer);
    prodTimer = setTimeout(runProductoSearch, d);
  }

  function verTodo(){
    document.getElementById('prodSearch').value = '';
    runProductoSearch();
  }

  async function runProductoSearch(){
    const input = document.getElementById('prodSearch');
    const q = limitSearchText(input.value);

    if (input.value !== q) {
      input.value = q;
    }

    const status = document.getElementById('prodStatus');
    const box = document.getElementById('prodResults');
    const recoText = document.getElementById('recoText');

    if(q === ''){
      recoText.innerHTML = 'Mostrando todo el catálogo';
    } else if(q === recoSearch){
      recoText.innerHTML = `Sugerencias para: <b>${esc(q)}</b>`;
    } else {
      recoText.innerHTML = `Resultados de “<b>${esc(q)}</b>”`;
    }

    status.innerHTML = '<span class="loader"></span> Buscando...';

    try{
      const resp = await fetch(buscarUrl, {
        method:'POST',
        headers:{
          'X-CSRF-TOKEN':csrfToken,
          'Accept':'application/json',
          'Content-Type':'application/json'
        },
        body:JSON.stringify({ q })
      });

      const data = await resp.json();

      if(!resp.ok || !data.ok) {
        throw new Error(data.message || 'Error en la búsqueda.');
      }

      prodResults = data.results || [];
      status.textContent = `${prodResults.length} resultado(s)`;

      if(!prodResults.length){
        box.innerHTML = '<p class="cl-empty">Sin resultados. Prueba con otra palabra.</p>';
        return;
      }

      box.innerHTML = prodResults.map((r, i) => {
        const stock0 = r.available_stock <= 0;
        const meta = [r.sku, r.code, r.brand].filter(Boolean).join(' · ') || '—';

        return `<div class="pcard">
          ${thumbHtml(r.image,58,'pthumb')}

          <div class="pinfo">
            <div class="pname">${esc(r.name)}</div>
            <div class="pmeta">${esc(meta)}</div>

            <div class="pchips">
              <span class="chip ${stock0 ? 'chip-stock0' : 'chip-stock'}">Stock libre: ${r.available_stock}</span>
              ${r.location_code ? `<span class="chip chip-loc">📍 ${esc(r.location_code)}</span>` : ''}
            </div>
          </div>

          <button type="button" class="btn btn-primary" style="height:40px;" onclick="usarProducto(${i})">
            Usar
          </button>
        </div>`;
      }).join('');
    } catch(e) {
      status.textContent = e.message;
    }
  }

  function usarProducto(i){
    const r = prodResults[i];

    if(!r || !prodTargetRow) return;

    prodTargetRow.querySelector('.p-product-id').value = r.id;
    prodTargetRow.querySelector('.p-product-name').value = r.name;
    prodTargetRow.querySelector('.p-sku').value = r.sku;
    prodTargetRow.querySelector('.p-stock').value = r.available_stock;
    prodTargetRow.querySelector('.p-image').value = r.image || '';

    const meta = [r.sku, r.location_code ? '📍 ' + r.location_code : ''].filter(Boolean).join(' · ');

    prodTargetRow.querySelector('.p-display').outerHTML =
      `<div class="p-display" style="display:flex;gap:8px;align-items:center;">
         ${thumbHtml(r.image,38,'rowthumb')}
         <div style="min-width:0;">
           <span class="prod-name">${esc(r.name)}</span>
           <div class="prod-meta">${esc(meta)} · stock ${r.available_stock}</div>
         </div>
       </div>`;

    recalcRow(prodTargetRow);
    recalcTotals();
    closeProductoModal();
  }

  function recalcRow(row){
    const pedido = parseInt(row.dataset.cantidad || '0', 10);
    const hasProd = !!row.querySelector('.p-product-id').value;
    const stock = parseInt(row.querySelector('.p-stock').value || '0', 10);

    const enStock = hasProd ? Math.min(pedido, stock) : 0;
    const porComprar = hasProd ? Math.max(0, pedido - enStock) : 0;

    row.querySelector('.p-instock').textContent = hasProd ? enStock : '—';
    row.querySelector('.p-tobuy').textContent = hasProd ? porComprar : '—';

    const st = row.querySelector('.p-status');

    if(!hasProd){
      st.innerHTML = '<span class="badge badge-none">Sin asignar</span>';
    } else if(porComprar > 0){
      st.innerHTML = `<span class="badge badge-buy">Compra ${porComprar}</span>`;
    } else {
      st.innerHTML = '<span class="badge badge-ok">En stock</span>';
    }
  }

  function recalcTotals(){
    let pedido = 0;
    let stock = 0;
    let buy = 0;

    document.querySelectorAll('.pk-row').forEach(row => {
      const ped = parseInt(row.dataset.cantidad || '0', 10);
      const hasProd = !!row.querySelector('.p-product-id').value;
      const s = parseInt(row.querySelector('.p-stock').value || '0', 10);

      pedido += ped;

      if(hasProd){
        const en = Math.min(ped, s);
        stock += en;
        buy += Math.max(0, ped - en);
      }
    });

    document.getElementById('tPedido').textContent = pedido;
    document.getElementById('tStock').textContent = stock;
    document.getElementById('tBuy').textContent = buy;
  }

  function collectItems(){
    return [...document.querySelectorAll('.pk-row')].map(r => {
      const pedido = parseInt(r.dataset.cantidad || '0', 10);
      const pid = r.querySelector('.p-product-id').value;
      const stock = parseInt(r.querySelector('.p-stock').value || '0', 10);
      const enStock = pid ? Math.min(pedido, stock) : 0;

      return {
        partida_id: r.dataset.partidaId,
        product_id: pid ? parseInt(pid, 10) : null,
        product_name: r.querySelector('.p-product-name').value,
        product_sku: r.querySelector('.p-sku').value,
        descripcion: r.dataset.desc,
        unidad: r.dataset.unidad,
        cantidad: pedido,
        available_stock: stock,
        en_stock: enStock,
        por_comprar: pid ? Math.max(0, pedido - enStock) : pedido,
      };
    });
  }

  function verResumen(){
    const items = collectItems();
    const sin = items.filter(i => !i.product_id);
    const totBuy = items.reduce((a, b) => a + (b.por_comprar || 0), 0);

    const aviso = sin.length
      ? `<p style="color:var(--danger);font-weight:700;">Faltan ${sin.length} partida(s) por empatar.</p>`
      : '';

    document.getElementById('resumenBody').innerHTML = `
      ${aviso}
      <p style="font-size:13px;color:var(--muted);font-weight:600;margin:0 0 12px;">
        Lo marcado en <strong style="color:var(--warning);">Por comprar</strong> entra como línea <b>virtual / Recolección Virtual</b>.
        Total por comprar: <b style="color:var(--warning);">${totBuy}</b>.
      </p>
      <pre style="background:var(--input-bg);border:1px solid var(--line);border-radius:12px;padding:16px;font-size:12px;line-height:1.6;overflow:auto;max-height:320px;white-space:pre;font-family:ui-monospace,monospace;">${esc(JSON.stringify(items,null,2))}</pre>
    `;

    document.getElementById('resumenModal').classList.add('show');
  }

  async function enviarAPicking(){
    const userId = document.getElementById('assignedUser').value;

    if(!userId){
      alert('Selecciona a quién se asigna la tarea de picking.');
      return;
    }

    const rows = [...document.querySelectorAll('.pk-row')];
    const sin = rows.filter(r => !r.querySelector('.p-product-id').value);

    if(sin.length){
      alert(`Faltan ${sin.length} partida(s) por empatar con un producto.`);
      return;
    }

    const items = collectItems().map(i => ({
      product_id: i.product_id,
      product_name: i.product_name,
      product_sku: i.product_sku,
      descripcion: i.descripcion,
      unidad: i.unidad,
      cantidad: i.cantidad,
    }));

    const btn = document.getElementById('btnEnviar');
    const old = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span class="loader"></span> Creando tarea...';

    try{
      const resp = await fetch(crearUrl, {
        method:'POST',
        headers:{
          'X-CSRF-TOKEN':csrfToken,
          'Accept':'application/json',
          'Content-Type':'application/json'
        },
        body:JSON.stringify({
          assigned_user_id: parseInt(userId, 10),
          order_number: document.getElementById('orderNumber').value,
          items
        })
      });

      const data = await resp.json();

      if(!resp.ok || !data.ok){
        throw new Error(data.message || 'No se pudo crear la tarea.');
      }

      window.location.href = data.redirect;
    } catch(e) {
      alert(e.message);
      btn.disabled = false;
      btn.innerHTML = old;
    }
  }

  document.getElementById('prodModal').addEventListener('click', e => {
    if(e.target.id === 'prodModal') closeProductoModal();
  });

  document.getElementById('resumenModal').addEventListener('click', e => {
    if(e.target.id === 'resumenModal') e.currentTarget.classList.remove('show');
  });
</script>
@endsection