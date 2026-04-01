@extends('layouts.app')
@section('title','Nuevo producto web')

@section('content')
<div class="wrap-ai fade-in-up">
  {{-- Header --}}
  <div class="head-ai">
    <div class="head-ai__text">
      <h1 class="h1-ai">Nuevo producto <span class="h1-ai__muted">Catálogo web</span></h1>
      <p class="p-ai">
        Completa la información de tu producto. Hazlo manualmente o acelera el proceso extrayendo datos con IA desde tu factura o remisión.
      </p>
    </div>
    <a class="btn-ai btn-ai--ghost" href="{{ route('admin.catalog.index') }}">
      <span class="ico-ai" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/><path d="M9 12h12"/></svg>
      </span>
      Volver al catálogo
    </a>
  </div>

  {{-- Tabs --}}
  <div class="tabs-wrapper-ai">
    <div class="tabs-ai">
      <button type="button" id="tabManual" class="tab-ai tab-ai--active">
        <span class="ico-ai" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5l4 4L7 21l-4 1 1-4 12.5-14.5z"/></svg>
        </span>
        Carga Manual
      </button>
      <button type="button" id="tabAi" class="tab-ai">
        <span class="ico-ai" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <path d="M5 3l2 5 5 2-5 2-2 5-2-5-5-2 5-2 2-5z" transform="translate(7 2)"/>
            <path d="M4 17l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3z"/>
          </svg>
        </span>
        Captura Inteligente (IA)
      </button>
    </div>
    <div class="tabs-ai__mode">
      <span class="pill-ai" id="modeLabel">
        <span class="dot-ai dot-ai--active"></span>
        Modo actual: Manual
      </span>
    </div>
  </div>

  {{-- Panel IA --}}
  <section id="panelAi" class="panel-ai fade-in-up" style="display:none">
    <div class="panel-ai__grid">

      {{-- Columna izquierda --}}
      <div class="panel-ai__left">
        <div class="card-ai step-ai">
          <div class="step-ai__title">
            <span class="step-ai__num">1</span>
            <div>
              <h3 class="h3-ai">Sincronización Móvil</h3>
              <p class="hint-ai">Genera un código QR, escanéalo con tu smartphone y sube las fotos de tu documento.</p>
            </div>
          </div>

          <div class="row-ai">
            <div class="select-wrap-ai">
              <label class="lbl-ai">Tipo de comprobante</label>
              <select id="aiSourceType" class="inp-ai">
                <option value="factura">Factura</option>
                <option value="remision">Remisión</option>
                <option value="otro">Otro documento</option>
              </select>
            </div>

            <button type="button" id="btnAiStart" class="btn-ai btn-ai--primary">
              <span class="ico-ai" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                  <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                  <path d="M14 14h3v3h-3z"/><path d="M20 14h1v1h-1z"/><path d="M14 20h7"/>
                </svg>
              </span>
              Generar QR
            </button>
          </div>

          {{-- QR Box --}}
          <div id="qrWrap" class="qr-wrap-ai" style="display:none;">
            <div class="qr-card-ai">
              <div class="qr-card-ai__header">
                <div class="qr-chip-ai">
                  <span class="ico-ai" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                  </span>
                  Escanea para iniciar
                </div>
                <div class="qr-status-ai" id="qrMiniStatus">
                  <span class="dot-ai dot-ai--slate"></span>
                  Esperando conexión
                </div>
              </div>

              <div class="qr-box-ai">
                <div class="qr-box-ai__frame">
                  <div class="qr-corner top-left"></div>
                  <div class="qr-corner top-right"></div>
                  <div class="qr-corner bottom-left"></div>
                  <div class="qr-corner bottom-right"></div>
                  <div id="qrBox"></div>
                  <div class="qr-box-ai__scanline"></div>
                </div>
              </div>

              <div class="qr-card-ai__footer">
                <div class="qr-url-ai">
                  <div class="qr-url-ai__label">Enlace de acceso manual</div>
                  <a id="mobileUrl" href="#" target="_blank" class="qr-url-ai__link"></a>
                </div>

                <div class="timeline-ai">
                  <div class="timeline-ai__item" data-st="0">
                    <span class="timeline-ai__dot"></span> Conectando
                  </div>
                  <div class="timeline-ai__item" data-st="1">
                    <span class="timeline-ai__dot"></span> Subiendo
                  </div>
                  <div class="timeline-ai__item" data-st="2">
                    <span class="timeline-ai__dot"></span> Analizando
                  </div>
                  <div class="timeline-ai__item" data-st="3">
                    <span class="timeline-ai__dot"></span> Completado
                  </div>
                </div>
              </div>
            </div>

            {{-- Status grande --}}
            <div class="status-ai">
              <div class="status-ai__badge" id="aiStatusBadge">
                <span class="ico-ai" aria-hidden="true">
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/></svg>
                </span>
                <span id="aiStatusText">Pendiente</span>
              </div>
              <div class="status-ai__hint" id="aiStatusHint">
                El sistema está esperando las imágenes de tu dispositivo...
              </div>
            </div>
          </div>
        </div>

        {{-- Tips --}}
        <div class="card-ai tips-ai">
          <div class="tips-ai__title">
            <span class="ico-ai" aria-hidden="true">
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8h.01"/><path d="M11 12h1v4h1"/></svg>
            </span>
            Mejores prácticas para la IA
          </div>
          <ul class="tips-ai__list">
            <li>Asegúrate de tener buena iluminación, sin sombras pronunciadas.</li>
            <li>Encuadra correctamente el encabezado y la tabla de productos.</li>
            <li>Para documentos multipágina, toma una fotografía individual por hoja.</li>
          </ul>
        </div>
      </div>

      {{-- Columna derecha --}}
      <div class="panel-ai__right">
        <div class="card-ai step-ai h-100">
          <div class="step-ai__title">
            <span class="step-ai__num">2</span>
            <div>
              <h3 class="h3-ai">Resultados de Extracción</h3>
              <p class="hint-ai">La Inteligencia Artificial tabulará los datos encontrados. Selecciona el producto a registrar.</p>
            </div>
          </div>

          {{-- Waiting --}}
          <div id="aiWaiting" class="waiting-ai">
            <div class="waiting-ai__content">
              <div class="waiting-icon">
                <svg viewBox="0 0 24 24" class="spin-ai"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" stroke-dasharray="30 30" stroke-linecap="round"/></svg>
              </div>
              <div class="waiting-ai__msg">Esperando extracción de datos...</div>
              <p class="muted-ai" style="font-size:0.85rem; margin-top:4px;">Los resultados aparecerán aquí automáticamente.</p>
            </div>
            <div class="waiting-ai__skeleton-wrap">
              <div class="waiting-ai__skeleton"></div>
              <div class="waiting-ai__skeleton"></div>
              <div class="waiting-ai__skeleton short"></div>
            </div>
          </div>

          {{-- Result --}}
          <div id="aiResult" class="result-ai fade-in" style="display:none;">
            <div class="summary-ai">
              <div class="summary-ai__item">
                <div class="summary-ai__label">PROVEEDOR</div>
                <div class="summary-ai__value" id="exSupplier">—</div>
              </div>
              <div class="summary-ai__item">
                <div class="summary-ai__label">Nº FOLIO</div>
                <div class="summary-ai__value" id="exFolio">—</div>
              </div>
              <div class="summary-ai__item">
                <div class="summary-ai__label">FECHA</div>
                <div class="summary-ai__value" id="exDate">—</div>
              </div>
              <div class="summary-ai__item">
                <div class="summary-ai__label">TOTAL DOC.</div>
                <div class="summary-ai__value highlight-text" id="exTotal">—</div>
              </div>
            </div>

            <div class="table-ai">
              <table>
                <thead>
                  <tr>
                    <th>SKU</th>
                    <th>Descripción del Producto</th>
                    <th>Cant.</th>
                    <th>U.M.</th>
                    <th>P. Unitario</th>
                    <th>Total</th>
                    <th class="right">Acción</th>
                  </tr>
                </thead>
                <tbody id="aiItemsTbody"></tbody>
              </table>
            </div>

            <div class="actions-ai">
              <button type="button" id="btnFillFirst" class="btn-ai btn-ai--primary btn-ai--sm">
                <span class="ico-ai" aria-hidden="true">
                  <svg viewBox="0 0 24 24"><path d="M3 12h6"/><path d="M15 12h6"/><path d="M9 6l6 6-6 6"/></svg>
                </span>
                Autocompletar primer ítem
              </button>
              <button type="button" id="btnBackManual" class="btn-ai btn-ai--ghost btn-ai--sm">
                <span class="ico-ai" aria-hidden="true">
                  <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5l4 4L7 21l-4 1 1-4 12.5-14.5z"/></svg>
                </span>
                Editar Manualmente
              </button>
              <div class="hint-ai" style="margin-left:auto; align-self:center;">
                Podrás verificar y editar todo antes de guardar.
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  {{-- Errors --}}
  @if($errors->any())
    <div class="alert-ai fade-in">
      <div class="alert-ai__icon">
        <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <div class="alert-ai__content">
        <div class="alert-ai__title">Revisa los siguientes detalles antes de continuar:</div>
        <ul class="alert-ai__list">
          @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    </div>
  @endif

  {{-- Manual panel --}}
  <div id="panelManual" class="fade-in-up">
    <form class="card-ai form-ai"
          action="{{ route('admin.catalog.store') }}"
          method="POST"
          enctype="multipart/form-data">
      @include('admin.catalog._form')
    </form>
  </div>
</div>
@endsection

@push('styles')
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

  :root {
    /* Premium Color Palette */
    --c-bg: #f8fafc;
    --c-surface: #ffffff;
    --c-surface-hover: #f1f5f9;
    --c-border: #e2e8f0;
    --c-text-main: #0f172a;
    --c-text-muted: #64748b;
    
    --c-brand: #4f46e5;
    --c-brand-hover: #4338ca;
    --c-brand-light: #e0e7ff;
    
    --c-success: #10b981;
    --c-success-bg: #d1fae5;
    
    --c-danger: #ef4444;
    --c-danger-bg: #fee2e2;

    --gradient-primary: linear-gradient(135deg, #4f46e5 0%, #0ea5e9 100%);
    --gradient-surface: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    
    /* Structure */
    --radius-xl: 20px;
    --radius-lg: 14px;
    --radius-md: 10px;
    --radius-sm: 6px;
    
    /* Shadows - "Soft & SaaS" style */
    --shadow-sm: 0 2px 4px rgba(15, 23, 42, 0.04);
    --shadow-md: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.02);
    --shadow-hover: 0 20px 25px -5px rgba(15, 23, 42, 0.08), 0 10px 10px -5px rgba(15, 23, 42, 0.04);
    --shadow-glow: 0 0 20px rgba(79, 70, 229, 0.25);
  }

  body { background-color: var(--c-bg); font-family: 'Inter', sans-serif; }

  /* Utilities */
  .fade-in-up { animation: fadeInUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
  .fade-in { animation: fadeIn 0.4s ease forwards; }
  .h-100 { height: 100%; display: flex; flex-direction: column; }
  
  @keyframes fadeInUp { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

  .wrap-ai { max-width: 1140px; margin: 24px auto; padding: 0 20px; color: var(--c-text-main); }
  
  .card-ai {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    transition: box-shadow 0.3s ease;
  }
  .card-ai:hover { box-shadow: var(--shadow-hover); }

  /* Header */
  .head-ai {
    display: flex; justify-content: space-between; align-items: center; 
    gap: 16px; flex-wrap: wrap; margin-bottom: 24px;
  }
  .h1-ai { margin: 0; font-weight: 800; letter-spacing: -0.03em; font-size: 1.8rem; color: var(--c-text-main); }
  .h1-ai__muted { color: var(--c-text-muted); font-weight: 500; font-size: 1.2rem; margin-left: 8px; font-weight: 400;}
  .p-ai { margin: 8px 0 0; font-size: 0.95rem; color: var(--c-text-muted); line-height: 1.5; max-width: 600px; }

  /* Buttons */
  .btn-ai {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: 0; cursor: pointer; text-decoration: none; font-weight: 600; font-size: 0.95rem;
    border-radius: var(--radius-md); padding: 10px 18px; 
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: inherit;
  }
  .btn-ai:active { transform: scale(0.97); }
  
  .btn-ai--primary {
    background: var(--gradient-primary); color: #fff;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3); border: none;
  }
  .btn-ai--primary:hover { box-shadow: var(--shadow-glow); transform: translateY(-1px); }
  
  .btn-ai--ghost { background: var(--c-surface); color: var(--c-text-main); border: 1px solid var(--c-border); box-shadow: var(--shadow-sm); }
  .btn-ai--ghost:hover { background: var(--c-surface-hover); transform: translateY(-1px); }
  
  .btn-ai--sm { padding: 8px 14px; font-size: 0.85rem; border-radius: var(--radius-sm); }
  .ico-ai svg { width: 18px; height: 18px; stroke: currentColor; stroke-width: 2.5; fill: none; stroke-linecap: round; stroke-linejoin: round; }

  /* Tabs (Segmented Control style) */
  .tabs-wrapper-ai { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
  .tabs-ai {
    display: inline-flex; background: var(--c-bg); padding: 6px; 
    border-radius: var(--radius-lg); border: 1px solid var(--c-border);
  }
  .tab-ai {
    padding: 10px 18px; border-radius: var(--radius-md); border: none;
    background: transparent; font-weight: 600; font-size: 0.95rem; color: var(--c-text-muted);
    display: flex; align-items: center; gap: 8px; cursor: pointer;
    transition: all 0.25s ease; font-family: inherit;
  }
  .tab-ai:hover { color: var(--c-text-main); }
  .tab-ai--active { background: var(--c-surface); color: var(--c-brand); box-shadow: var(--shadow-sm); border: 1px solid rgba(0,0,0,0.04); }
  
  .pill-ai {
    display: inline-flex; align-items: center; gap: 8px; font-weight: 600; font-size: 0.85rem; color: var(--c-text-muted);
    background: var(--c-surface); border: 1px solid var(--c-border); border-radius: 999px; padding: 6px 14px; box-shadow: var(--shadow-sm);
  }
  .dot-ai { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
  .dot-ai--active { background: var(--c-success); box-shadow: 0 0 8px var(--c-success); animation: pulseDot 2s infinite; }
  .dot-ai--slate { background: var(--c-text-muted); }
  
  @keyframes pulseDot { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

  /* Panels Grid */
  .panel-ai { margin-bottom: 24px; }
  .panel-ai__grid { display: grid; grid-template-columns: 380px 1fr; gap: 20px; align-items: stretch; }
  @media (max-width: 980px) { .panel-ai__grid { grid-template-columns: 1fr; } }

  /* Steps & Common Layout */
  .step-ai { padding: 24px; background: var(--c-surface); border: 1px solid var(--c-border); }
  .step-ai__title { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 20px; }
  .step-ai__num {
    width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; font-weight: 800; font-size: 1.1rem;
    background: var(--c-brand-light); color: var(--c-brand); flex: 0 0 auto;
  }
  .h3-ai { margin: 0 0 4px; font-weight: 700; font-size: 1.15rem; color: var(--c-text-main); }
  .hint-ai { margin: 0; font-size: 0.88rem; color: var(--c-text-muted); line-height: 1.4; }

  /* Form Elements in Panel */
  .row-ai { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 16px; }
  .select-wrap-ai { flex: 1 1 180px; }
  .lbl-ai { display: block; font-weight: 600; color: var(--c-text-main); margin: 0 0 8px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
  .inp-ai {
    width: 100%; background: var(--c-surface); border: 1px solid var(--c-border); border-radius: var(--radius-md);
    padding: 10px 14px; min-height: 44px; font-size: 0.95rem; color: var(--c-text-main); font-family: inherit; transition: all 0.2s;
  }
  .inp-ai:focus { outline: none; border-color: var(--c-brand); box-shadow: 0 0 0 4px var(--c-brand-light); }

  /* QR Box Premium */
  .qr-wrap-ai { margin-top: 16px; display: flex; flex-direction: column; gap: 16px; }
  .qr-card-ai { border-radius: var(--radius-lg); border: 1px solid var(--c-border); background: var(--gradient-surface); padding: 16px; }
  .qr-card-ai__header { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 16px; }
  .qr-chip-ai { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.8rem; background: var(--c-brand-light); color: var(--c-brand); padding: 6px 12px; border-radius: 999px; }
  .qr-status-ai { font-weight: 600; font-size: 0.8rem; color: var(--c-text-muted); display: flex; align-items: center; gap: 6px; }

  .qr-box-ai {
    border-radius: var(--radius-md); background: #0f172a; min-height: 280px;
    display: grid; place-items: center; position: relative; overflow: hidden; padding: 20px;
  }
  .qr-box-ai__frame { position: relative; background: #fff; border-radius: 8px; padding: 12px; z-index: 2; }
  
  /* Cyberpunk / Tech corners */
  .qr-corner { position: absolute; width: 16px; height: 16px; border: 3px solid var(--c-brand); z-index: 3; }
  .top-left { top: -6px; left: -6px; border-right: none; border-bottom: none; border-top-left-radius: 4px;}
  .top-right { top: -6px; right: -6px; border-left: none; border-bottom: none; border-top-right-radius: 4px;}
  .bottom-left { bottom: -6px; left: -6px; border-right: none; border-top: none; border-bottom-left-radius: 4px;}
  .bottom-right { bottom: -6px; right: -6px; border-left: none; border-top: none; border-bottom-right-radius: 4px;}

  /* Sci-fi Scanline */
  .qr-box-ai__scanline {
    position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: rgba(14, 165, 233, 0.8);
    box-shadow: 0 0 10px 2px rgba(14, 165, 233, 0.5); z-index: 4; opacity: 0.8;
    animation: scanline 2.5s linear infinite;
  }
  @keyframes scanline { 0% { top: 0; } 50% { top: 100%; } 100% { top: 0; } }

  .qr-card-ai__footer { margin-top: 16px; display: grid; gap: 16px; }
  .qr-url-ai__label { font-weight: 600; font-size: 0.8rem; color: var(--c-text-muted); margin-bottom: 6px; text-transform: uppercase; }
  .qr-url-ai__link {
    display: block; word-break: break-all; font-weight: 500; font-size: 0.9rem; color: var(--c-brand);
    text-decoration: none; background: #fff; border: 1px dashed var(--c-border); padding: 10px 12px; border-radius: var(--radius-sm); transition: 0.2s;
  }
  .qr-url-ai__link:hover { border-color: var(--c-brand); background: var(--c-brand-light); }

  /* Timeline Nodes */
  .timeline-ai { display: flex; justify-content: space-between; align-items: center; position: relative; margin-top: 8px;}
  .timeline-ai::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: var(--c-border); z-index: 1; transform: translateY(-50%); }
  .timeline-ai__item {
    font-size: 0.75rem; font-weight: 700; color: var(--c-text-muted); background: var(--c-surface);
    display: flex; flex-direction: column; align-items: center; gap: 6px; position: relative; z-index: 2; padding: 0 4px;
  }
  .timeline-ai__dot { width: 12px; height: 12px; border-radius: 50%; background: var(--c-border); border: 2px solid var(--c-surface); transition: 0.3s; }
  .timeline-ai__item.active { color: var(--c-brand); }
  .timeline-ai__item.active .timeline-ai__dot { background: var(--c-brand); box-shadow: 0 0 0 3px var(--c-brand-light); }

  /* Status Block */
  .status-ai { padding: 16px; background: var(--c-surface-hover); border-radius: var(--radius-md); border: 1px solid var(--c-border); }
  .status-ai__badge {
    display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 0.95rem; color: var(--c-text-main);
    background: #fff; border: 1px solid var(--c-border); padding: 6px 14px; border-radius: 999px; margin-bottom: 8px;
    box-shadow: var(--shadow-sm);
  }
  .status-ai__hint { font-size: 0.9rem; color: var(--c-text-muted); line-height: 1.4; }

  /* Waiting State (Premium Skeleton) */
  .waiting-ai {
    flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;
    border: 1px dashed var(--c-border); border-radius: var(--radius-md); padding: 32px 20px; background: var(--c-surface-hover);
  }
  .spin-ai { width: 40px; height: 40px; color: var(--c-brand); animation: spin 2s linear infinite; margin-bottom: 16px; }
  @keyframes spin { 100% { transform: rotate(360deg); } }
  .waiting-ai__msg { font-weight: 600; font-size: 1.1rem; color: var(--c-text-main); }
  .waiting-ai__skeleton-wrap { width: 100%; max-width: 400px; margin-top: 24px; }
  .waiting-ai__skeleton {
    height: 14px; border-radius: 999px; margin-bottom: 12px;
    background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
    background-size: 200% 100%; animation: skeletonShimmer 1.5s infinite;
  }
  .waiting-ai__skeleton.short { width: 60%; margin: 0 auto; }
  @keyframes skeletonShimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

  /* Results Table Area */
  .summary-ai { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 16px; }
  @media (max-width: 780px) { .summary-ai { grid-template-columns: repeat(2, 1fr); } }
  .summary-ai__item { background: var(--c-surface-hover); border: 1px solid var(--c-border); border-radius: var(--radius-md); padding: 12px 16px; }
  .summary-ai__label { font-size: 0.75rem; font-weight: 700; color: var(--c-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
  .summary-ai__value { font-size: 1.05rem; font-weight: 800; color: var(--c-text-main); }
  .highlight-text { color: var(--c-brand); }

  .table-ai { border: 1px solid var(--c-border); border-radius: var(--radius-md); overflow-x: auto; background: #fff; margin-bottom: 16px; box-shadow: var(--shadow-sm); }
  .table-ai table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
  .table-ai th, .table-ai td { padding: 12px 16px; border-bottom: 1px solid var(--c-border); white-space: nowrap; }
  .table-ai th { background: var(--c-surface-hover); font-weight: 700; text-align: left; color: var(--c-text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
  .table-ai td { color: var(--c-text-main); font-weight: 500; }
  .table-ai tr:last-child td { border-bottom: none; }
  .table-ai tbody tr:hover { background: var(--c-bg); }
  .table-ai .right { text-align: right; }

  .actions-ai { display: flex; gap: 12px; flex-wrap: wrap; padding-top: 8px; border-top: 1px solid var(--c-border); }

  /* Tips Block */
  .tips-ai { padding: 16px 20px; background: var(--c-brand-light); border: none; }
  .tips-ai__title { font-weight: 700; color: var(--c-brand); display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.95rem; }
  .tips-ai__list { margin: 0 0 0 20px; padding: 0; color: #3730a3; font-size: 0.85rem; font-weight: 500; display: grid; gap: 6px; line-height: 1.4;}

  /* Alert Errors */
  .alert-ai {
    display: flex; gap: 16px; background: #fef2f2; border-left: 4px solid var(--c-danger); border-radius: var(--radius-md);
    padding: 16px 20px; margin-bottom: 24px; color: #991b1b; box-shadow: var(--shadow-sm);
  }
  .alert-ai__icon { color: var(--c-danger); flex-shrink: 0; }
  .alert-ai__title { font-weight: 800; margin-bottom: 8px; font-size: 1rem;}
  .alert-ai__list { margin: 0 0 0 20px; font-weight: 500; font-size: 0.9rem; line-height: 1.5; }

  .form-ai { padding: 32px; }
  .muted-ai { color: var(--c-text-muted); }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  // ========= Tabs =========
  const tabManual = document.getElementById('tabManual');
  const tabAi = document.getElementById('tabAi');
  const panelManual = document.getElementById('panelManual');
  const panelAi = document.getElementById('panelAi');
  const modeLabel = document.getElementById('modeLabel');

  function setMode(mode){
    const isAi = mode === 'ai';
    panelAi.style.display = isAi ? 'block' : 'none';
    panelManual.style.display = isAi ? 'none' : 'block';
    
    // Add animation re-trigger
    if(isAi) {
      panelAi.classList.remove('fade-in-up');
      void panelAi.offsetWidth; // trigger reflow
      panelAi.classList.add('fade-in-up');
    } else {
      panelManual.classList.remove('fade-in-up');
      void panelManual.offsetWidth; 
      panelManual.classList.add('fade-in-up');
    }

    tabAi.classList.toggle('tab-ai--active', isAi);
    tabManual.classList.toggle('tab-ai--active', !isAi);
    modeLabel.innerHTML = isAi
      ? `<span class="dot-ai dot-ai--active"></span> Modo actual: Captura IA`
      : `<span class="dot-ai dot-ai--active"></span> Modo actual: Manual`;
  }
  tabManual.onclick = ()=>setMode('manual');
  tabAi.onclick = ()=>setMode('ai');

  // ========= IA START / POLL =========
  let intakeId = null;
  let pollTimer = null;
  let extractedCache = null;

  const btnAiStart   = document.getElementById('btnAiStart');
  const qrWrap       = document.getElementById('qrWrap');
  const qrBox        = document.getElementById('qrBox');
  const mobileUrlA   = document.getElementById('mobileUrl');

  const aiStatusText = document.getElementById('aiStatusText');
  const aiStatusHint = document.getElementById('aiStatusHint');
  const qrMiniStatus = document.getElementById('qrMiniStatus');

  const aiWaiting    = document.getElementById('aiWaiting');
  const aiResult     = document.getElementById('aiResult');

  const exSupplier   = document.getElementById('exSupplier');
  const exFolio      = document.getElementById('exFolio');
  const exDate       = document.getElementById('exDate');
  const exTotal      = document.getElementById('exTotal');
  const aiItemsTbody = document.getElementById('aiItemsTbody');

  const stMap = {
    0:{txt:'Conectado', hint:'Esperando a que subas fotos desde tu celular...'},
    1:{txt:'Fotos recibidas', hint:'Iniciando el motor de reconocimiento...'},
    2:{txt:'Procesando con IA', hint:'Extrayendo tabla de productos y montos...'},
    3:{txt:'Completado', hint:'Datos extraídos exitosamente. Selecciona un ítem.'},
    4:{txt:'Confirmado', hint:'Esta captura ya fue aplicada anteriormente.'},
    9:{txt:'Error', hint:'No se pudo analizar el documento. Intenta nuevamente.'},
  };

  function setTimelineActive(status){
    document.querySelectorAll('.timeline-ai__item').forEach(el=>{
      const st = parseInt(el.getAttribute('data-st'));
      el.classList.toggle('active', st <= status);
    });
  }

  function setStatusUI(status, meta){
    const st = stMap[status] || {txt:String(status), hint:''};
    aiStatusText.textContent = st.txt;
    aiStatusHint.textContent = (meta && meta.error) ? meta.error : st.hint;

    if(qrMiniStatus){
      const colorClass = status === 9 ? 'dot-ai--slate' : 'dot-ai--active';
      qrMiniStatus.innerHTML = `<span class="dot-ai ${colorClass}"></span> ${st.txt}`;
    }

    setTimelineActive(status);
  }

  btnAiStart.addEventListener('click', async ()=>{
    btnAiStart.disabled = true;
    btnAiStart.innerHTML = `<svg class="spin-ai" style="width:16px;height:16px;margin:0" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="30 30" stroke-linecap="round"/></svg> Generando...`;

    try{
      const source_type = document.getElementById('aiSourceType').value;

      const res = await fetch(`{{ route('admin.catalog.ai.start') }}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ source_type })
      });

      const j = await res.json();
      if(!j.ok) throw new Error(j.error || 'No se pudo iniciar la IA');

      intakeId = j.intake_id;

      qrWrap.style.display = 'block';
      qrWrap.classList.add('fade-in-up');
      qrBox.innerHTML = '';
      
      // Ajuste de colores del QR para que sea dark mode
      new QRCode(qrBox, { 
        text: j.mobile_url, 
        width: 200, 
        height: 200,
        colorDark : "#ffffff",
        colorLight : "#0f172a",
      });

      mobileUrlA.href = j.mobile_url;
      mobileUrlA.textContent = "Abrir enlace de captura";

      setStatusUI(0);
      aiWaiting.style.display = 'flex';
      aiResult.style.display = 'none';

      // reset
      extractedCache = null;
      aiItemsTbody.innerHTML = '';
      exSupplier.textContent = '—';
      exFolio.textContent = '—';
      exDate.textContent = '—';
      exTotal.textContent = '—';

      if(pollTimer) clearInterval(pollTimer);
      pollTimer = setInterval(pollStatus, 2200);

    }catch(e){
      alert(e.message || 'Error de conexión');
    }finally{
      btnAiStart.disabled = false;
      btnAiStart.innerHTML = `<span class="ico-ai" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3z"/><path d="M20 14h1v1h-1z"/><path d="M14 20h7"/></svg></span> Generar QR`;
    }
  });

  async function pollStatus(){
    if(!intakeId) return;

    const res = await fetch(`/admin/catalog/ai/${intakeId}/status`, {
      headers:{'X-Requested-With':'XMLHttpRequest'}
    });
    const j = await res.json();

    setStatusUI(j.status, j.meta);

    // al subir fotos, ocultar QR visualmente (suavemente)
    if (j.status >= 1 && j.status < 3) {
      const qrCard = document.querySelector('.qr-card-ai');
      if (qrCard && qrCard.style.display !== 'none') {
        qrCard.style.opacity = '0';
        setTimeout(() => qrCard.style.display = 'none', 300);
      }
      aiWaiting.style.display = 'flex';
    }

    if(j.status === 3){
      clearInterval(pollTimer);
      extractedCache = j.extracted || {};
      renderExtracted(extractedCache);
    }

    if(j.status === 9){
      clearInterval(pollTimer);
      aiWaiting.innerHTML = `
        <div style="font-weight:700;color:#ef4444;text-align:center;">
          <svg viewBox="0 0 24 24" width="32" height="32" stroke="currentColor" stroke-width="2" fill="none" style="margin-bottom:8px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><br>
          ${ (j.meta && j.meta.error) ? j.meta.error : 'Fallo en la extracción de la IA.'}
        </div>`;
    }
  }

  function renderExtracted(ex){
    aiWaiting.style.display = 'none';
    aiResult.style.display = 'block';

    exSupplier.textContent = ex.supplier_name || '—';
    exFolio.textContent    = ex.folio || '—';
    exDate.textContent     = ex.invoice_date || '—';
    exTotal.textContent    = (ex.total ? `$${ex.total}` : '—');

    const items = Array.isArray(ex.items) ? ex.items : [];
    aiItemsTbody.innerHTML = '';

    items.forEach((it, idx)=>{
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><span style="font-family:monospace; color:#64748b; font-weight:600;">${escapeHtml(it.sku || '—')}</span></td>
        <td style="white-space:normal;min-width:240px;font-weight:600;">${escapeHtml(it.description || '—')}</td>
        <td>${escapeHtml(it.quantity ?? '—')}</td>
        <td><span class="pill-ai" style="padding:2px 8px; font-size:0.7rem;">${escapeHtml(it.unit || '—')}</span></td>
        <td>${escapeHtml(it.unit_price ? `$${it.unit_price}` : '—')}</td>
        <td style="font-weight:700; color:var(--c-brand);">${escapeHtml(it.line_total ? `$${it.line_total}` : '—')}</td>
        <td class="right">
          <button type="button" class="btn-ai btn-ai--ghost btn-ai--sm" data-use="${idx}">Seleccionar</button>
        </td>
      `;
      aiItemsTbody.appendChild(tr);
    });

    aiItemsTbody.querySelectorAll('button[data-use]').forEach(btn=>{
      btn.onclick = ()=>{
        const i = parseInt(btn.getAttribute('data-use'));
        fillFormFromItem(items[i], ex);
        setMode('manual');
      };
    });

    const btnBackManual = document.getElementById('btnBackManual');
    if(btnBackManual) btnBackManual.onclick = ()=>setMode('manual');
  }

  const btnFillFirst = document.getElementById('btnFillFirst');
  if(btnFillFirst){
    btnFillFirst.onclick = ()=>{
      const items = (extractedCache && Array.isArray(extractedCache.items)) ? extractedCache.items : [];
      if(!items.length) return alert('No hay ítems detectados.');
      fillFormFromItem(items[0], extractedCache);
      setMode('manual');
    };
  }

  function fillFormFromItem(it, ex){
    if(!it) return;

    const setVal = (name, val, mark=true)=>{
      const el = document.querySelector(`[name="${name}"]`);
      if(!el) return;
      if(val === undefined || val === null || val === '') return;
      el.value = val;
      if(mark){
        el.classList.add('ai-suggested-input');
        setTimeout(()=> el.classList.remove('ai-suggested-input'), 6500);
      }
    };

    const desc  = (it.description || '').trim();
    const brand = (it.brand || it.brand_name || '').trim();
    const model = (it.model || it.model_name || '').trim();

    let finalName = desc || 'PRODUCTO SIN NOMBRE';
    if(brand && !finalName.toLowerCase().includes(brand.toLowerCase())) finalName += ' ' + brand;
    if(model && !finalName.toLowerCase().includes(model.toLowerCase())) finalName += ' ' + model;

    setVal('name', finalName);
    setVal('sku', it.sku || '');
    setVal('price', it.unit_price ?? it.price ?? 0);
    setVal('brand_name', brand);
    setVal('model_name', model);
    setVal('excerpt', desc ? desc.slice(0, 160) : '');

    // GTIN / barcode
    const gtin = it.gtin || it.ean || it.upc || it.barcode || it.codigo_barras || '';
    setVal('meli_gtin', gtin);

    // stock
    const qty = it.quantity ?? it.qty ?? it.cantidad ?? null;
    setVal('stock', qty);

    // Descripción con contexto
    const extra = ex || extractedCache || {};
    let longDesc = '';
    if(extra.supplier_name) longDesc += `Proveedor: ${extra.supplier_name}\n`;
    if(extra.folio)         longDesc += `Folio: ${extra.folio}\n`;
    if(extra.invoice_date)  longDesc += `Fecha Documento: ${extra.invoice_date}\n\n`;

    longDesc += `Descripción Original:\n${desc || '—'}\n\n`;
    longDesc += `Cantidad: ${qty ?? '—'} ${it.unit || ''}\n`;
    longDesc += `Precio unitario: ${it.unit_price ?? '—'}\n`;
    longDesc += `Total línea: ${it.line_total ?? '—'}`;

    const dEl = document.querySelector('[name="description"]');
    if(dEl){
      dEl.value = longDesc;
      dEl.classList.add('ai-suggested-input');
      setTimeout(()=> dEl.classList.remove('ai-suggested-input'), 6500);
    }

    window.scrollTo({top:0, behavior:'smooth'});
  }

  function escapeHtml(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }
</script>

<style>
  /* Feedback Premium al Rellenar */
  .ai-suggested-input {
    border-color: var(--c-success) !important;
    background: var(--c-success-bg) !important;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2) !important;
    transition: all 0.5s ease;
  }
</style>
@endpush