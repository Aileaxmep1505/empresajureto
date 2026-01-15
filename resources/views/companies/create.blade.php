{{-- resources/views/companies/create.blade.php --}}
@extends('layouts.app')

@section('title','Nueva Empresa')

@section('content')
@php
  // ✅ A donde SIEMPRE quieres regresar
  $backUrl = url('/part-contable');

  // ✅ Ruta de guardado (POST). Si tu store también debe redirigir a /part-contable,
  //    esto se hace en el Controller (te dejo el código abajo).
  $postUrl = url('/companies');
@endphp

<div id="cmpCreateLight" class="cc-wrap">
  {{-- Material Symbols (iconos elegantes) --}}
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300;400;500&display=swap"/>

  <style>
    /* ==========================
       Company Create · Light UI/UX (Responsive, Minimal, Production)
       ========================== */
    #cmpCreateLight{
      --bg:#f6f8fc;
      --card:#ffffff;
      --ink:#0b1220;
      --muted:#667085;

      --line:#e7edf6;
      --line2:#eef2f7;

      /* Pasteles */
      --p-blue-bg:#e8f1ff;
      --p-blue-bd:#cfe2ff;
      --p-blue:#2563eb;

      --p-mint-bg:#e8fbf4;
      --p-mint-bd:#c8f2e3;
      --p-mint:#16a34a;

      --danger:#ef4444;

      --shadow: 0 18px 55px rgba(2,6,23,.08);
      --shadow2: 0 10px 30px rgba(2,6,23,.08);
      --radius: 20px;
      --ease: cubic-bezier(.2,.8,.2,1);
    }

    #cmpCreateLight *{ box-sizing:border-box; }

    #cmpCreateLight .cc-wrap{
      max-width: 1100px;
      margin: 16px auto 28px;
      padding: 0 14px;
    }

    /* Iconos */
    #cmpCreateLight .ms{
      font-family: "Material Symbols Outlined";
      font-weight: 400;
      font-style: normal;
      font-size: 20px;
      line-height: 1;
      display: inline-block;
      -webkit-font-smoothing: antialiased;
      user-select: none;
    }
    #cmpCreateLight .ms.sm{ font-size: 18px; }
    #cmpCreateLight .ms.xs{ font-size: 16px; }

    /* Hero */
    #cmpCreateLight .cc-hero{
      border-radius: 26px;
      padding: 16px;
      overflow: hidden;
      background:
        radial-gradient(900px 420px at 12% 0%, rgba(37,99,235,.10), transparent 60%),
        radial-gradient(900px 520px at 60% 110%, rgba(22,163,74,.08), transparent 65%),
        linear-gradient(180deg, #ffffff, #fbfcff);
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
    }

    /* Top */
    #cmpCreateLight .cc-top{
      display:flex;
      align-items:flex-start;
      justify-content:space-between;
      gap: 12px;
      padding: 14px 14px 10px;
      flex-wrap: wrap;
    }

    #cmpCreateLight .cc-title{
      display:flex;
      align-items:flex-start;
      gap: 12px;
      min-width: 260px;
      flex: 1 1 520px;
    }

    #cmpCreateLight .cc-mark{
      width:44px; height:44px;
      border-radius: 14px;
      background: linear-gradient(135deg, rgba(37,99,235,.18), rgba(22,163,74,.14));
      border: 1px solid rgba(37,99,235,.16);
      box-shadow: 0 14px 30px rgba(37,99,235,.10);
      display:grid;
      place-items:center;
      color: var(--p-blue);
      flex: 0 0 auto;
    }

    #cmpCreateLight h1{
      margin:0;
      font-size: 20px;
      line-height: 1.15;
      letter-spacing: -.02em;
      color: var(--ink);
      font-weight: 650;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    #cmpCreateLight .cc-sub{
      margin-top:6px;
      color: var(--muted);
      font-size: 13.5px;
      line-height: 1.35;
      max-width: 60ch;
    }

    #cmpCreateLight .cc-actions{
      display:flex;
      align-items:center;
      gap:10px;
      margin-left: auto;
    }
    @media (max-width: 640px){
      #cmpCreateLight .cc-actions{ width: 100%; justify-content: flex-start; }
    }

    /* Botones pastel */
    #cmpCreateLight .btnx{
      appearance:none;
      border: 1px solid var(--line);
      background: #ffffff;
      color: var(--ink);
      padding: 10px 12px;
      border-radius: 14px;
      cursor:pointer;
      display:inline-flex;
      align-items:center;
      gap:8px;
      font-weight: 550;
      letter-spacing: .01em;
      transition: transform .15s var(--ease), background .15s var(--ease), border-color .15s var(--ease), box-shadow .15s var(--ease);
      text-decoration:none;
      user-select:none;
      white-space:nowrap;
    }
    #cmpCreateLight .btnx:hover{
      transform: translateY(-1px);
      box-shadow: var(--shadow2);
      border-color: rgba(37,99,235,.18);
    }
    #cmpCreateLight .btnx:active{ transform: translateY(0px) scale(.99); }
    #cmpCreateLight .btnx:focus-visible{
      outline:none;
      box-shadow: 0 0 0 4px rgba(37,99,235,.14);
      border-color: rgba(37,99,235,.35);
    }

    #cmpCreateLight .btnx-pastel-blue{
      background: var(--p-blue-bg);
      border-color: var(--p-blue-bd);
      color: var(--p-blue);
      box-shadow: none;
    }
    #cmpCreateLight .btnx-pastel-blue:hover{
      background: #dfeaff;
      border-color: #bdd6ff;
      box-shadow: 0 10px 22px rgba(37,99,235,.10);
    }

    #cmpCreateLight .btnx-pastel-mint{
      background: var(--p-mint-bg);
      border-color: var(--p-mint-bd);
      color: var(--p-mint);
      box-shadow: none;
    }
    #cmpCreateLight .btnx-pastel-mint:hover{
      background: #ddf8ee;
      border-color: #b7eddc;
      box-shadow: 0 10px 22px rgba(22,163,74,.10);
    }

    /* Layout */
    #cmpCreateLight .cc-body{
      display:grid;
      grid-template-columns: 1.35fr .65fr;
      gap: 14px;
      padding: 10px 14px 14px;
    }
    @media (max-width: 980px){
      #cmpCreateLight .cc-body{ grid-template-columns: 1fr; }
    }

    /* Cards */
    #cmpCreateLight .cc-card{
      background: rgba(255,255,255,.90);
      border: 1px solid var(--line2);
      border-radius: var(--radius);
      padding: 14px;
      backdrop-filter: blur(8px);
    }
    #cmpCreateLight .cc-card h2{
      margin:0;
      font-size: 12px;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: rgba(102,112,133,.85);
      font-weight: 650;
    }
    #cmpCreateLight .cc-divider{
      height:1px;
      background: linear-gradient(90deg, transparent, rgba(15,23,42,.10), transparent);
      margin: 12px 0 14px;
    }

    /* Grid */
    #cmpCreateLight .grid{
      display:grid;
      grid-template-columns: repeat(12, 1fr);
      gap: 12px;
    }
    #cmpCreateLight .col-12{ grid-column: span 12; }
    #cmpCreateLight .col-8{ grid-column: span 8; }
    #cmpCreateLight .col-6{ grid-column: span 6; }
    #cmpCreateLight .col-4{ grid-column: span 4; }
    @media (max-width: 820px){
      #cmpCreateLight .col-8,
      #cmpCreateLight .col-6,
      #cmpCreateLight .col-4{ grid-column: span 12; }
    }

    /* Inputs */
    #cmpCreateLight .field{ display:flex; flex-direction:column; gap:7px; }
    #cmpCreateLight label{
      font-size: 12.5px;
      color: var(--muted);
      letter-spacing: .01em;
      font-weight: 500;
    }
    #cmpCreateLight .control{ position: relative; }
    #cmpCreateLight .input{
      width:100%;
      height: 46px;
      padding: 0 12px 0 44px;
      border-radius: 14px;
      border: 1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.98);
      color: var(--ink);
      outline: none;
      transition: border-color .15s var(--ease), box-shadow .15s var(--ease), background .15s var(--ease);
      font-size: 14.5px;
      font-weight: 450;
    }
    #cmpCreateLight .input::placeholder{ color: rgba(102,112,133,.55); }
    #cmpCreateLight .input:hover{ border-color: rgba(37,99,235,.18); }
    #cmpCreateLight .input:focus{
      border-color: rgba(37,99,235,.45);
      box-shadow: 0 0 0 4px rgba(37,99,235,.12);
      background: #ffffff;
    }
    #cmpCreateLight .icon{
      position:absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      width: 22px;
      height: 22px;
      display:grid;
      place-items:center;
      color: rgba(15,23,42,.55);
      pointer-events:none;
    }

    /* Errores */
    #cmpCreateLight .is-invalid{
      border-color: rgba(239,68,68,.55) !important;
      box-shadow: 0 0 0 4px rgba(239,68,68,.10) !important;
    }
    #cmpCreateLight .err{
      margin-top: 2px;
      font-size: 12.3px;
      color: rgba(239,68,68,.95);
      font-weight: 500;
    }

    /* Aside */
    #cmpCreateLight .cc-aside{
      position: sticky;
      top: 14px;
      align-self: start;
    }
    @media (max-width: 980px){ #cmpCreateLight .cc-aside{ position: static; } }

    #cmpCreateLight .mini{
      margin: 0;
      padding: 0;
      list-style: none;
      display:flex;
      flex-direction:column;
      gap: 10px;
    }
    #cmpCreateLight .mini li{
      display:flex;
      gap: 10px;
      align-items:flex-start;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.35;
    }
    #cmpCreateLight .mini .b{
      width: 32px; height: 32px;
      border-radius: 12px;
      border: 1px solid var(--line2);
      background: rgba(255,255,255,.98);
      box-shadow: 0 10px 18px rgba(2,6,23,.05);
      display:grid;
      place-items:center;
      color: rgba(37,99,235,.75);
      flex: 0 0 auto;
      margin-top: 1px;
    }

    /* Footer */
    #cmpCreateLight .cc-footer{
      display:flex;
      justify-content:flex-end;
      gap:10px;
      padding-top: 14px;
      flex-wrap: wrap;
    }
    @media (max-width: 520px){
      #cmpCreateLight .cc-footer .btnx{
        width: 100%;
        justify-content: center;
      }
    }

    /* Toast */
    #cmpCreateLight .toast{
      margin: 0 14px 10px;
      padding: 12px 12px;
      border-radius: 16px;
      border: 1px solid rgba(22,163,74,.20);
      background: rgba(22,163,74,.08);
      color: rgba(15,23,42,.92);
      display:flex;
      align-items:flex-start;
      gap:10px;
    }
    #cmpCreateLight .toast .dot{
      width: 10px; height:10px; border-radius:99px;
      background: rgba(22,163,74,.85);
      box-shadow: 0 0 0 4px rgba(22,163,74,.12);
      margin-top: 4px;
      flex:0 0 auto;
    }

    @media (prefers-reduced-motion: reduce){
      #cmpCreateLight .btnx, #cmpCreateLight .input{ transition: none !important; }
    }
  </style>

  <div class="cc-hero">
    <div class="cc-top">
      <div class="cc-title">
        <div class="cc-mark" aria-hidden="true">
          <span class="ms">apartment</span>
        </div>
        <div style="min-width:0">
          <h1>Nueva empresa</h1>
          <div class="cc-sub">Crea una empresa con la información esencial.</div>
        </div>
      </div>

      <div class="cc-actions">
        {{-- ✅ SIEMPRE a /part-contable --}}
        <a class="btnx btnx-pastel-blue" href="{{ $backUrl }}">
          <span class="ms sm" aria-hidden="true">arrow_back</span>
          Volver
        </a>

        <button type="button" class="btnx btnx-pastel-mint" id="ccFillDemo">
          <span class="ms sm" aria-hidden="true">auto_awesome</span>
          Autocompletar
        </button>
      </div>
    </div>

    @if(session('success'))
      <div class="toast" role="status" aria-live="polite">
        <span class="dot" aria-hidden="true"></span>
        <div>{{ session('success') }}</div>
      </div>
    @endif

    <div class="cc-body">
      <div class="cc-card">
        <h2>Datos</h2>
        <div class="cc-divider"></div>

        <form id="companyCreateForm" method="POST" action="{{ $postUrl }}" novalidate>
          @csrf

          <div class="grid">
            <div class="field col-8">
              <label for="name">Nombre de la empresa</label>
              <div class="control">
                <span class="icon" aria-hidden="true"><span class="ms xs">business</span></span>
                <input id="name" name="name" type="text"
                  class="input @error('name') is-invalid @enderror"
                  value="{{ old('name') }}"
                  placeholder="Ej. Jureto S.A. de C.V."
                  autocomplete="organization"
                  required />
              </div>
              @error('name') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="field col-4">
              <label for="rfc">RFC</label>
              <div class="control">
                <span class="icon" aria-hidden="true"><span class="ms xs">badge</span></span>
                <input id="rfc" name="rfc" type="text"
                  class="input @error('rfc') is-invalid @enderror"
                  value="{{ old('rfc') }}"
                  placeholder="XAXX010101000"
                  autocomplete="off"
                  inputmode="text" />
              </div>
              @error('rfc') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="field col-6">
              <label for="phone">Teléfono</label>
              <div class="control">
                <span class="icon" aria-hidden="true"><span class="ms xs">call</span></span>
                <input id="phone" name="phone" type="text"
                  class="input @error('phone') is-invalid @enderror"
                  value="{{ old('phone') }}"
                  placeholder="Ej. 55 1234 5678"
                  autocomplete="tel"
                  inputmode="tel" />
              </div>
              @error('phone') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="field col-6">
              <label for="email">Correo</label>
              <div class="control">
                <span class="icon" aria-hidden="true"><span class="ms xs">mail</span></span>
                <input id="email" name="email" type="email"
                  class="input @error('email') is-invalid @enderror"
                  value="{{ old('email') }}"
                  placeholder="contacto@empresa.com"
                  autocomplete="email"
                  inputmode="email" />
              </div>
              @error('email') <div class="err">{{ $message }}</div> @enderror
            </div>

            <div class="field col-12">
              <label for="address">Dirección</label>
              <div class="control">
                <span class="icon" aria-hidden="true"><span class="ms xs">location_on</span></span>
                <input id="address" name="address" type="text"
                  class="input @error('address') is-invalid @enderror"
                  value="{{ old('address') }}"
                  placeholder="Calle, número, colonia, ciudad"
                  autocomplete="street-address" />
              </div>
              @error('address') <div class="err">{{ $message }}</div> @enderror
            </div>
          </div>

          <div class="cc-footer">
            {{-- ✅ Cancelar SIEMPRE a /part-contable --}}
            <a class="btnx btnx-pastel-blue" href="{{ $backUrl }}">
              <span class="ms sm" aria-hidden="true">close</span>
              Cancelar
            </a>

            {{-- ✅ Guardar: al enviar, el Controller debe redirigir a /part-contable --}}
            <button class="btnx btnx-pastel-mint" id="ccSubmitBtn" type="submit">
              <span class="ms sm" aria-hidden="true">save</span>
              Guardar
            </button>
          </div>
        </form>
      </div>

      <aside class="cc-card cc-aside">
        <h2>Tips</h2>
        <div class="cc-divider"></div>

        <ul class="mini">
          <li>
            <span class="b" aria-hidden="true"><span class="ms sm">verified</span></span>
            <span>Nombre obligatorio. RFC y correo recomendados.</span>
          </li>
          <li>
            <span class="b" aria-hidden="true" style="color: rgba(22,163,74,.75);"><span class="ms sm">bolt</span></span>
            <span>RFC y teléfono se normalizan automáticamente.</span>
          </li>
        </ul>
      </aside>
    </div>
  </div>

  <script>
    (function(){
      const form = document.getElementById('companyCreateForm');
      if(!form) return;

      const $ = (id) => document.getElementById(id);

      const name  = $('name');
      const rfc   = $('rfc');
      const phone = $('phone');
      const email = $('email');
      const addr  = $('address');

      // RFC a MAYÚSCULAS y caracteres típicos
      if (rfc){
        rfc.addEventListener('input', () => {
          const v = (rfc.value || '')
            .toUpperCase()
            .replace(/\s+/g,'')
            .replace(/[^A-Z0-9&Ñ]/g,'');
          rfc.value = v;
        });
      }

      // Teléfono: deja números, espacios, +, -, ()
      if (phone){
        phone.addEventListener('input', () => {
          phone.value = (phone.value || '').replace(/[^\d\s\+\-\(\)]/g,'');
        });
      }

      // Validación UX mínima
      form.addEventListener('submit', (e) => {
        if (name && !name.value.trim()){
          e.preventDefault();
          name.focus();
          name.classList.add('is-invalid');
          return;
        }
        if (email && email.value.trim()){
          const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
          if(!ok){
            e.preventDefault();
            email.focus();
            email.classList.add('is-invalid');
            return;
          }
        }

        // Evitar doble submit
        const btn = document.getElementById('ccSubmitBtn');
        if (btn){
          btn.disabled = true;
          btn.style.opacity = .85;
          btn.style.cursor = 'not-allowed';
          btn.lastChild && (btn.lastChild.textContent = ' Guardando…');
        }
      });

      // Quitar estado inválido al escribir
      [name,rfc,phone,email,addr].filter(Boolean).forEach(el=>{
        el.addEventListener('input', ()=> el.classList.remove('is-invalid'));
      });

      // Autocompletar (comodidad)
      const demo = document.getElementById('ccFillDemo');
      if(demo){
        demo.addEventListener('click', ()=>{
          if(name)  name.value  = name.value  || 'Jureto S.A. de C.V.';
          if(rfc)   rfc.value   = rfc.value   || 'XAXX010101000';
          if(phone) phone.value = phone.value || '55 1234 5678';
          if(email) email.value = email.value || 'contacto@empresa.com';
          if(addr)  addr.value  = addr.value  || 'Av. Insurgentes Sur 123, Roma Norte, CDMX';
          [name,rfc,phone,email,addr].filter(Boolean).forEach(el=> el.dispatchEvent(new Event('input')));
          if(name) name.focus();
        });
      }
    })();
  </script>
</div>
@endsection
