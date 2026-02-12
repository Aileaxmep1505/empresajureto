@extends('layouts.app')
@section('title','Editar producto web')

@push('styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@400..700&display=swap"/>

<style>
  :root{
    --ink:#0e1726;
    --muted:#6b7280;
    --line:#e5e7eb;
    --card:#ffffff;

    --blue:#6ea8fe;
    --blue-ink:#0b1220;

    --green:#22c55e;
    --green-soft: rgba(34,197,94,.14);
    --green-ink:#065f46;

    --amber:#f59e0b;
    --amber-soft: rgba(245,158,11,.16);
    --amber-ink:#92400e;

    --red:#ef4444;

    --shadow:0 12px 30px rgba(13,23,38,.06);
    --r:16px;
    --rbtn:12px;
  }

  .wrap-page{max-width:1100px;margin-inline:auto;padding:10px 0;}

  .btn{
    display:inline-flex;align-items:center;gap:8px;
    border-radius:var(--rbtn);
    padding:9px 14px;
    font-weight:700;
    border:1px solid var(--line);
    background:#fff;
    cursor:pointer;
    text-decoration:none;
    font-size:.9rem;
    transition:transform .12s ease, box-shadow .12s ease, background .15s ease, border-color .15s ease, opacity .15s ease;
    white-space:nowrap;
  }
  .btn:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(15,23,42,.08);}
  .btn:active{transform:translateY(0);box-shadow:none;}
  .btn[disabled], .btn[aria-disabled="true"]{opacity:.55;cursor:not-allowed;pointer-events:none;}

  .btn-primary{
    background:var(--blue);
    border-color:var(--blue);
    color:var(--blue-ink);
    box-shadow:0 10px 22px rgba(37,99,235,.16);
  }
  .btn-primary:hover{box-shadow:0 14px 26px rgba(37,99,235,.18);}

  .btn-ghost{
    background:#fff;
    border-color:var(--line);
    color:#111827;
  }

  .btn-danger{
    background:var(--red);
    border-color:var(--red);
    color:#fff;
    box-shadow:0 10px 22px rgba(239,68,68,.16);
  }
  .btn-danger:hover{box-shadow:0 14px 26px rgba(239,68,68,.18);}

  .btn-soft{
    background:#fff;
    border-color:var(--line);
    color:#111827;
  }

  .btn-ml{
    background:linear-gradient(135deg, rgba(16,185,129,.18), rgba(16,185,129,.08));
    color:var(--green-ink);
    border:1px solid rgba(16,185,129,.35);
    box-shadow:0 10px 22px rgba(16,185,129,.14);
  }
  .btn-ml:hover{box-shadow:0 14px 26px rgba(16,185,129,.18);}

  .btn-ml-soft{
    background:rgba(16,185,129,.10);
    color:var(--green-ink);
    border:1px solid rgba(16,185,129,.25);
  }

  .btn-amz{
    background:linear-gradient(135deg, rgba(245,158,11,.22), rgba(245,158,11,.10));
    color:var(--amber-ink);
    border:1px solid rgba(245,158,11,.35);
    box-shadow:0 10px 22px rgba(245,158,11,.14);
  }
  .btn-amz:hover{box-shadow:0 14px 26px rgba(245,158,11,.18);}

  .btn-amz-soft{
    background:rgba(245,158,11,.12);
    color:var(--amber-ink);
    border:1px solid rgba(245,158,11,.25);
  }

  .btn-ai{
    background:linear-gradient(135deg, rgba(59,130,246,.16), rgba(59,130,246,.08));
    color:#0b1220;
    border:1px solid rgba(59,130,246,.28);
    box-shadow:0 10px 22px rgba(59,130,246,.12);
  }
  .btn-ai:hover{box-shadow:0 14px 26px rgba(59,130,246,.14);}

  .i.material-symbols-outlined{font-size:18px;line-height:1;}

  .alert{
    padding:10px 12px;border-radius:12px;border:1px solid var(--line);
    margin-bottom:12px;font-size:.9rem;background:#fff;
  }
  .alert-success{background:#f8fffb;color:#0b6b3a;border-color:rgba(34,197,94,.20);}
  .alert-error{background:#fff4f4;color:#b91c1c;border-color:rgba(239,68,68,.25);}
  .alert-ml{background:#fbfeff;color:#0b4b6b;border-color:var(--line);}
  .alert-amz{background:#fffaf2;color:#6b3d0b;border-color:rgba(245,158,11,.28);}
  .alert-info{background:#f3f8ff;color:#123a7a;border-color:rgba(59,130,246,.22);}

  .head{
    display:flex;justify-content:space-between;align-items:flex-start;
    gap:12px;flex-wrap:wrap;margin:14px 0 10px;
  }
  .head h1{font-weight:800;margin:0;color:#0b1220;font-size:1.15rem;}
  .head p{margin:4px 0 0;font-size:.9rem;color:var(--muted);}

  .actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;align-items:center;}

  .card{
    background:var(--card);
    border:1px solid #e8eef6;
    border-radius:var(--r);
    box-shadow:var(--shadow);
    padding:16px;
  }

  @media (max-width: 720px){
    .btn{width:100%;justify-content:center;}
    .actions{width:100%;}
  }
</style>
@endpush

@section('content')
@php
  use Illuminate\Support\Str;

  // ✅ Amazon requiere AMAZON SKU real para operar (no el sku interno)
  $hasAmazonSku = !empty($item->amazon_sku ?? null);

  $mlErrText  = Str::lower((string)($item->meli_last_error ?? ''));

  // ✅ Compatible: si tu modelo usa amz_last_error o amazon_last_error
  $amzLastError = $item->amz_last_error ?? ($item->amazon_last_error ?? null);
  $amzErrText   = Str::lower((string)($amzLastError ?? ''));

  // ✅ Compatible: si tu modelo usa amz_synced_at o amazon_synced_at
  $amzSyncedAt  = $item->amz_synced_at ?? ($item->amazon_synced_at ?? null);

  // ✅ Compatible: si tu modelo usa amz_status o amazon_status
  $amzStatus    = $item->amz_status ?? ($item->amazon_status ?? null);

  /**
   * ✅ Regla importante:
   * - Si NO está publicado en Amazon todavía, NO mostramos Pausar/Activar/Ver.
   * - Consideramos “publicado/existente” si hay ASIN o un status real (active/paused/inactive/error).
   */
  $hasAmazonListing = !empty($item->amazon_asin ?? null)
    || in_array((string)$amzStatus, ['active','paused','inactive','error'], true);
@endphp

<div class="wrap-page">
  <div class="head">
    <div>
      <h1>Editar: {{ $item->name }}</h1>
      <p>Ajusta la información del producto y revisa el estado de sincronización con Mercado Libre y Amazon.</p>
    </div>

    <div class="actions">
      <a class="btn btn-ghost" href="{{ route('admin.catalog.index') }}">
        <span class="i material-symbols-outlined" aria-hidden="true">arrow_back</span>
        Volver
      </a>

      {{-- ✅ IA: rellenar vacíos con última captura guardada --}}
      <button type="button" class="btn btn-ai" id="btnAiFillEmpty">
        <span class="i material-symbols-outlined" aria-hidden="true">auto_fix_high</span>
        IA: Rellenar vacíos
      </button>

      {{-- Toggle publicar/ocultar (WEB) --}}
      <form action="{{ route('admin.catalog.toggle', $item) }}" method="POST"
            onsubmit="return confirm('¿Cambiar estado de publicación en el sitio web?')">
        @csrf @method('PATCH')
        <button class="btn btn-ghost" type="submit">
          <span class="i material-symbols-outlined" aria-hidden="true">{{ $item->status == 1 ? 'visibility_off' : 'visibility' }}</span>
          {{ $item->status == 1 ? 'Ocultar' : 'Publicar' }}
        </button>
      </form>

      {{-- Mercado Libre: acciones rápidas --}}
      <form method="POST" action="{{ route('admin.catalog.meli.publish', $item) }}">
        @csrf
        <button class="btn btn-ml" type="submit">
          <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
          ML: Publicar/Actualizar
        </button>
      </form>

      @if($item->meli_item_id)
        <form method="POST" action="{{ route('admin.catalog.meli.pause', $item) }}">
          @csrf
          <button class="btn btn-ml-soft" type="submit">
            <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
            ML: Pausar
          </button>
        </form>

        <form method="POST" action="{{ route('admin.catalog.meli.activate', $item) }}">
          @csrf
          <button class="btn btn-ml-soft" type="submit">
            <span class="i material-symbols-outlined" aria-hidden="true">play_circle</span>
            ML: Activar
          </button>
        </form>

        <a class="btn btn-ml-soft" href="{{ route('admin.catalog.meli.view', $item) }}" target="_blank" rel="noopener">
          <span class="i material-symbols-outlined" aria-hidden="true">open_in_new</span>
          ML: Ver
        </a>
      @endif

      {{-- ✅ Amazon: Publicar/Actualizar SIEMPRE disponible cuando hay AMAZON SKU
           ❌ Pausar/Activar/Ver SOLO si ya existe listing (ASIN o status real) --}}
      <form method="POST" action="{{ route('admin.catalog.amazon.publish', $item) }}">
        @csrf
        <button class="btn btn-amz" type="submit" @disabled(!$hasAmazonSku)>
          <span class="i material-symbols-outlined" aria-hidden="true">cloud_upload</span>
          Amazon: Publicar/Actualizar
        </button>
      </form>

      @if($hasAmazonListing)
        <form method="POST" action="{{ route('admin.catalog.amazon.pause', $item) }}">
          @csrf
          <button class="btn btn-amz-soft" type="submit" @disabled(!$hasAmazonSku)>
            <span class="i material-symbols-outlined" aria-hidden="true">pause_circle</span>
            Amazon: Pausar
          </button>
        </form>

        <form method="POST" action="{{ route('admin.catalog.amazon.activate', $item) }}">
          @csrf
          <button class="btn btn-amz-soft" type="submit" @disabled(!$hasAmazonSku)>
            <span class="i material-symbols-outlined" aria-hidden="true">play_circle</span>
            Amazon: Activar
          </button>
        </form>

        <a class="btn btn-amz-soft"
           href="{{ route('admin.catalog.amazon.view', $item) }}"
           target="_blank" rel="noopener"
           @if(!$hasAmazonSku) aria-disabled="true" onclick="return false;" @endif>
          <span class="i material-symbols-outlined" aria-hidden="true">open_in_new</span>
          Amazon: Ver
        </a>
      @endif

      {{-- Eliminar --}}
      <form action="{{ route('admin.catalog.destroy', $item) }}" method="POST"
            onsubmit="return confirm('¿Eliminar este producto del catálogo web? Esta acción no se puede deshacer.')">
        @csrf @method('DELETE')
        <button class="btn btn-danger" type="submit">
          <span class="i material-symbols-outlined" aria-hidden="true">delete</span>
          Eliminar
        </button>
      </form>
    </div>
  </div>

  {{-- Banner de resultado/errores global --}}
  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  {{-- Advertencia AMAZON SKU para Amazon --}}
  @if(!$hasAmazonSku)
    <div class="alert alert-amz">
      <strong>Amazon:</strong> Para publicar necesitas un <strong>AMAZON SKU</strong> (Seller SKU real).
      Captúralo en el formulario y guarda el producto.
    </div>
  @endif

  {{-- ✅ Si hay SKU pero AÚN no existe listing, explica por qué no salen los botones --}}
  @if($hasAmazonSku && !$hasAmazonListing)
    <div class="alert alert-info">
      <strong>Amazon:</strong> Este producto <strong>todavía no está publicado</strong> en Amazon.
      Usa <strong>“Amazon: Publicar/Actualizar”</strong>. Cuando Amazon confirme (ASIN/status), aparecerán <strong>Pausar/Activar/Ver</strong>.
    </div>
  @endif

  {{-- Panel de estado Mercado Libre --}}
  @if($item->meli_item_id || $item->meli_last_error)
    <div class="alert alert-ml">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1 1 260px;">
          <strong>Mercado Libre</strong><br>

          @if($item->meli_item_id)
            <span style="font-size:.88rem;">
              ID: {{ $item->meli_item_id }} ·
              Estado:
              @if($item->meli_status === 'active')
                <span style="font-weight:800;color:#166534;">Activo</span>
              @elseif($item->meli_status === 'paused')
                <span style="font-weight:800;color:#854d0e;">Pausado</span>
              @elseif($item->meli_status === 'error')
                <span style="font-weight:800;color:#b91c1c;">Error</span>
              @else
                <span>{{ $item->meli_status ?: '—' }}</span>
              @endif
            </span>
          @else
            <span style="font-size:.88rem;">Sin publicación en Mercado Libre.</span>
          @endif

          @if($item->meli_synced_at)
            <div style="margin-top:4px;font-size:.8rem;color:#6b7280;">
              Última sincronización: {{ $item->meli_synced_at->format('Y-m-d H:i') }}
            </div>
          @endif
        </div>

        <div style="flex:1 1 260px;font-size:.86rem;color:#4b5563;">
          <strong>Qué revisar en este producto:</strong>
          <ul style="margin:4px 0 0 18px;padding:0;">
            @if(Str::contains($mlErrText, 'gtin'))
              <li>Completa <strong>GTIN / código de barras</strong>. Es obligatorio para esta categoría.</li>
            @endif
            @if(Str::contains($mlErrText, 'title') || Str::contains($mlErrText, 'título'))
              <li>Mejora el <strong>Nombre</strong>: incluye tipo de producto, marca y modelo.</li>
            @endif
            @if(Str::contains($mlErrText, 'price'))
              <li>Ajusta el <strong>Precio</strong> para cumplir mínimos de la categoría.</li>
            @endif
            <li>Verifica que existan <strong>imágenes</strong> (tus 3 fotos).</li>
            <li>Después de corregir, pulsa <strong>“ML: Publicar/Actualizar”</strong>.</li>
          </ul>
        </div>
      </div>

      @if($item->meli_last_error)
        <div style="color:#b91c1c;margin-top:8px;white-space:normal;font-size:.86rem;">
          <strong>Mensaje técnico de Mercado Libre:</strong><br>
          {{ $item->meli_last_error }}
        </div>
      @endif
    </div>
  @endif

  {{-- Panel de estado Amazon (solo si hay señal real) --}}
  @if($hasAmazonListing || !empty($amzLastError) || !empty($amzSyncedAt))
    <div class="alert alert-amz">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div style="flex:1 1 260px;">
          <strong>Amazon</strong><br>
          <span style="font-size:.88rem;">
            AMAZON SKU: {{ $item->amazon_sku ?? '—' }} ·
            Estado:
            @if($amzStatus === 'active')
              <span style="font-weight:800;color:#166534;">Activo</span>
            @elseif($amzStatus === 'inactive' || $amzStatus === 'paused')
              <span style="font-weight:800;color:#854d0e;">Pausado</span>
            @elseif($amzStatus === 'error')
              <span style="font-weight:800;color:#b91c1c;">Error</span>
            @else
              <span>{{ $amzStatus ?: '—' }}</span>
            @endif
          </span>

          @if(!empty($amzSyncedAt))
            <div style="margin-top:4px;font-size:.8rem;color:#6b7280;">
              Última sincronización:
              @php
                try {
                  $dt = $amzSyncedAt instanceof \Carbon\Carbon ? $amzSyncedAt : \Carbon\Carbon::parse($amzSyncedAt);
                  echo e($dt->format('Y-m-d H:i'));
                } catch (\Throwable $e) {
                  echo e((string)$amzSyncedAt);
                }
              @endphp
            </div>
          @endif
        </div>

        <div style="flex:1 1 260px;font-size:.86rem;color:#4b5563;">
          <strong>Qué revisar para Amazon:</strong>
          <ul style="margin:4px 0 0 18px;padding:0;">
            @if(Str::contains($amzErrText, 'sku'))
              <li>Confirma que el <strong>AMAZON SKU</strong> esté exacto al de Seller Central.</li>
            @endif
            @if(Str::contains($amzErrText, 'gtin') || Str::contains($amzErrText, 'upc') || Str::contains($amzErrText, 'ean'))
              <li>Completa <strong>GTIN (EAN/UPC)</strong> si tu productType lo exige.</li>
            @endif
            @if(Str::contains($amzErrText, 'image') || Str::contains($amzErrText, 'imagen'))
              <li>Verifica que existan imágenes (tus 3 fotos).</li>
            @endif
            @if(Str::contains($amzErrText, 'producttype') || Str::contains($amzErrText, 'product_type') || Str::contains($amzErrText, 'product type'))
              <li>Revisa el campo <strong>productType</strong> (ej. OFFICE_PRODUCTS, ELECTRONICS, etc.).</li>
            @endif
            <li>Después de corregir, pulsa <strong>“Amazon: Publicar/Actualizar”</strong>.</li>
          </ul>
        </div>
      </div>

      @if(!empty($amzLastError))
        <div style="color:#b91c1c;margin-top:8px;white-space:normal;font-size:.86rem;">
          <strong>Mensaje técnico de Amazon:</strong><br>
          {{ $amzLastError }}
        </div>
      @endif
    </div>
  @endif

  {{-- Errores de validación de formulario --}}
  @if($errors->any())
    <div class="alert alert-error">
      <strong>Revisa estos campos antes de guardar:</strong>
      <ul style="margin:6px 0 0 18px;">
        @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <form class="card" action="{{ route('admin.catalog.update', $item) }}" method="POST" enctype="multipart/form-data">
    @include('admin.catalog._form', ['item' => $item])
  </form>
</div>
@endsection

@push('scripts')
<script>
  // ✅ Botón “IA: Rellenar vacíos”
  // - Usa la última lista guardada en localStorage por tu analizador (catalog_ai_items)
  // - Solo completa campos vacíos (no pisa lo que ya llenaste)
  document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnAiFillEmpty');
    if (!btn) return;

    const LS_KEY_ITEMS = 'catalog_ai_items';
    const LS_KEY_INDEX = 'catalog_ai_index';

    function norm(s){ return String(s ?? '').trim(); }
    function lower(s){ return norm(s).toLowerCase(); }

    function pick(obj, keys){
      for (const k of keys){
        const v = obj?.[k];
        if (v !== undefined && v !== null && String(v).trim() !== '') return v;
      }
      return null;
    }

    function applyIfEmpty(name, val){
      if (val === undefined || val === null || val === '') return false;
      const el = document.querySelector(`[name="${name}"]`);
      if (!el) return false;
      const cur = (el.value ?? '').toString().trim();
      if (cur !== '') return false;
      el.value = val;
      try { el.dispatchEvent(new Event('change', { bubbles:true })); } catch(e){}
      el.classList.add('ai-suggested');
      setTimeout(()=>el.classList.remove('ai-suggested'), 6500);
      return true;
    }

    function guessProductType(text){
      const t = lower(text);
      if (!t) return '';
      if (t.includes('clip') || t.includes('grapa') || t.includes('engrap') || t.includes('papel') || t.includes('oficina')) return 'OFFICE_PRODUCTS';
      if (t.includes('lapic') || t.includes('pluma') || t.includes('bolig') || t.includes('marcador')) return 'OFFICE_PRODUCTS';
      if (t.includes('cable') || t.includes('usb') || t.includes('cargador') || t.includes('comput')) return 'ELECTRONICS';
      return '';
    }

    function fillFromItem(item){
      if (!item || typeof item !== 'object') return 0;

      const name  = pick(item, ['name','title','descripcion','description']);
      const slug  = pick(item, ['slug']);
      const desc  = pick(item, ['description','descripcion_larga','desc']);
      const ex    = pick(item, ['excerpt','resumen','short_description']);
      const price = pick(item, ['price','unit_price','precio','precio_unitario']);
      const brand = pick(item, ['brand_name','brand','marca']);
      const model = pick(item, ['model_name','model','modelo']);
      const gtin  = pick(item, ['meli_gtin','gtin','ean','upc','barcode','codigo_barras']);
      const qty   = pick(item, ['stock','quantity','qty','cantidad','cant']);

      const amazonSku = pick(item, ['amazon_sku','seller_sku','sellerSku','amazonSellerSku','amazon_seller_sku','amz_sku']);
      const asin      = pick(item, ['amazon_asin','asin']);
      const ptype     = pick(item, ['amazon_product_type','productType','product_type','amz_product_type']);

      let count = 0;
      count += applyIfEmpty('name', name) ? 1 : 0;
      count += applyIfEmpty('slug', slug) ? 1 : 0;
      count += applyIfEmpty('description', desc) ? 1 : 0;
      count += applyIfEmpty('excerpt', ex) ? 1 : 0;
      count += applyIfEmpty('price', price) ? 1 : 0;
      count += applyIfEmpty('brand_name', brand) ? 1 : 0;
      count += applyIfEmpty('model_name', model) ? 1 : 0;
      count += applyIfEmpty('meli_gtin', gtin) ? 1 : 0;
      count += applyIfEmpty('stock', qty) ? 1 : 0;

      count += applyIfEmpty('amazon_sku', amazonSku) ? 1 : 0;
      count += applyIfEmpty('amazon_asin', asin) ? 1 : 0;
      count += applyIfEmpty('amazon_product_type', ptype) ? 1 : 0;

      // fallback productType si no venía
      const curPtypeEl = document.querySelector('[name="amazon_product_type"]');
      const curPtype = curPtypeEl ? (curPtypeEl.value ?? '').trim() : '';
      if (!curPtype){
        const t = `${name || ''} ${desc || ''} ${ex || ''}`;
        const g = guessProductType(t);
        if (g) count += applyIfEmpty('amazon_product_type', g) ? 1 : 0;
      }

      return count;
    }

    btn.addEventListener('click', function(){
      let items = [];
      try {
        const raw = localStorage.getItem(LS_KEY_ITEMS);
        items = raw ? JSON.parse(raw) : [];
      } catch(e){ items = []; }

      if (!Array.isArray(items) || !items.length){
        alert('No hay captura IA guardada. Primero usa “Analizar con IA” (PDF/imágenes) en el formulario.');
        const helper = document.getElementById('ai-helper');
        if (helper) helper.scrollIntoView({ behavior:'smooth', block:'start' });
        return;
      }

      let idx = 0;
      try {
        idx = parseInt(localStorage.getItem(LS_KEY_INDEX) || '0', 10);
        if (isNaN(idx) || idx < 0 || idx >= items.length) idx = 0;
      } catch(e){ idx = 0; }

      const used = fillFromItem(items[idx] || items[0]);

      if (!used){
        alert('No había campos vacíos por completar (o la IA no traía valores nuevos).');
      }else{
        alert(`IA completó ${used} campo(s) vacío(s). Revisa y guarda.`);
        window.scrollTo({ top: 0, behavior:'smooth' });
      }
    });
  });
</script>
@endpush
