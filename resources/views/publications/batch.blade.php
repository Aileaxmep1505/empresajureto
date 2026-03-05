@extends('layouts.app')
@section('title','Lote agrupado')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

@php
  $groups = $groups ?? [];

  // ✅ Si tu service/controller lo manda, úsalo (RECOMENDADO):
  // $processing = true/false
  // $filesCount = total de archivos del lote
  // $processedCount = cuantos ya generaron PurchaseDocument
  $processing = $processing ?? null; // null = “no sé” => usamos auto-refresh con límite
  $filesCount = $filesCount ?? null;
  $processedCount = $processedCount ?? null;

  $isEmpty = count($groups) === 0;

  // Texto de progreso (si tienes contadores)
  $progressTxt = 'Actualizando… (esta pantalla se recargará automáticamente)';
  if (is_numeric($processedCount) && is_numeric($filesCount) && (int)$filesCount > 0) {
    $progressTxt = 'Analizando ' . (int)$processedCount . '/' . (int)$filesCount . '… (se recargará automáticamente)';
  }
@endphp

<style>
  :root{--ink:#0f172a;--muted:#64748b;--line:#e5e7eb;--card:#fff}
  .wrap{max-width:1200px;margin:14px auto;padding:0 14px;font-family:Outfit,system-ui}
  .top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center}
  h1{margin:0;font-size:20px;font-weight:900;color:var(--ink)}
  .sub{color:var(--muted);font-size:13px;margin-top:4px}
  .grid{margin-top:14px;display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:14px;box-shadow:0 14px 40px rgba(2,6,23,.06)}
  .name{font-weight:900;color:var(--ink)}
  .meta{color:var(--muted);font-size:12px;margin-top:4px;display:flex;gap:10px;flex-wrap:wrap}
  .doc{margin-top:10px;border:1px solid var(--line);border-radius:14px;padding:10px;background:#fff}
  .items{margin-top:10px;border-top:1px dashed var(--line);padding-top:10px}
  .it{display:flex;justify-content:space-between;gap:12px;font-size:12px;color:var(--muted);padding:4px 0}
  .it b{color:var(--ink)}
  @media(max-width:980px){.grid{grid-template-columns:1fr}}

  /* ✅ estado vacío (sin loop) */
  .emptyState{
    max-width: 920px;
    margin: 18px auto 0;
    padding: 14px;
    border:1px solid var(--line);
    background:#fff;
    border-radius:18px;
    box-shadow:0 14px 40px rgba(2,6,23,.06);
  }
  .emptyTitle{font-weight:900;color:var(--ink)}
  .emptySub{color:var(--muted);font-size:13px;margin-top:6px;line-height:1.5}
  .emptyActions{margin-top:12px;display:flex;gap:10px;flex-wrap:wrap}

  /* ✅ loader overlay (granito) */
  .overlay{
    position:fixed; inset:0; display:none; align-items:center; justify-content:center;
    z-index:9999; padding:24px;
  }
  .overlay.show{display:flex}
  .grain{
    position:absolute; inset:0;
    background:
      radial-gradient(1200px 500px at 10% 10%, rgba(255,255,255,.14), transparent 60%),
      radial-gradient(900px 420px at 90% 20%, rgba(255,255,255,.10), transparent 55%),
      radial-gradient(900px 420px at 60% 90%, rgba(255,255,255,.10), transparent 55%),
      linear-gradient(180deg, rgba(2,6,23,.68), rgba(2,6,23,.62));
    backdrop-filter: blur(6px);
  }
  .grain:after{
    content:""; position:absolute; inset:0; opacity:.22;
    background-image:
      repeating-linear-gradient(0deg, rgba(255,255,255,.035) 0, rgba(255,255,255,.035) 1px, transparent 1px, transparent 3px),
      repeating-linear-gradient(90deg, rgba(255,255,255,.03) 0, rgba(255,255,255,.03) 1px, transparent 1px, transparent 4px);
    mix-blend-mode: overlay;
  }
  .box{ position:relative; z-index:1; width:min(900px,94vw); background:transparent; border:none; box-shadow:none; }
  .boxTop{ display:flex; justify-content:space-between; gap:12px; margin-bottom:12px; flex-wrap:wrap; }
  .t{ color:#fff; font-weight:900; font-size:13px; }
  .s{ color:rgba(255,255,255,.78); font-weight:800; font-size:12px; max-width:520px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

  .bar{ width:100%; height:10px; border-radius:999px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.10); overflow:hidden; }
  .bar > span{ display:block; height:100%; width:40%; border-radius:999px; background:linear-gradient(90deg, rgba(59,130,246,.9), rgba(79,70,229,.9)); animation: indet 1.2s ease-in-out infinite; }
  @keyframes indet{
    0%{ transform: translateX(-60%); width:30% }
    50%{ transform: translateX(40%); width:55% }
    100%{ transform: translateX(140%); width:30% }
  }

  /* mini “Generando” */
  .loader-wrapper{
    position:relative; display:flex; align-items:center; justify-content:center;
    width:180px; height:180px; margin:6px auto 12px; color:#fff; font-family:Inter,system-ui;
  }
  .loader{
    position:absolute; inset:0; border-radius:50%;
    animation: loader-rotate 2s linear infinite;
  }
  @keyframes loader-rotate{
    0%{ transform: rotate(90deg); box-shadow:0 10px 20px 0 #fff inset,0 20px 30px 0 #ad5fff inset,0 60px 60px 0 #471eec inset; }
    50%{ transform: rotate(270deg); box-shadow:0 10px 20px 0 #fff inset,0 20px 10px 0 #d60a47 inset,0 40px 60px 0 #311e80 inset; }
    100%{ transform: rotate(450deg); box-shadow:0 10px 20px 0 #fff inset,0 20px 30px 0 #ad5fff inset,0 60px 60px 0 #471eec inset; }
  }
  .loader-letter{ opacity:.4; animation: letter 2s infinite; font-weight:500; }
  .loader-letter:nth-child(1){animation-delay:0s}
  .loader-letter:nth-child(2){animation-delay:.1s}
  .loader-letter:nth-child(3){animation-delay:.2s}
  .loader-letter:nth-child(4){animation-delay:.3s}
  .loader-letter:nth-child(5){animation-delay:.4s}
  .loader-letter:nth-child(6){animation-delay:.5s}
  .loader-letter:nth-child(7){animation-delay:.6s}
  .loader-letter:nth-child(8){animation-delay:.7s}
  .loader-letter:nth-child(9){animation-delay:.8s}
  @keyframes letter{
    0%,100%{opacity:.4}
    20%{opacity:1; transform:scale(1.15)}
    40%{opacity:.7; transform:none}
  }

  /* ✅ acciones overlay */
  .ovActions{
    display:flex; gap:10px; flex-wrap:wrap; justify-content:center;
    margin-top: 14px;
  }
  .ovBtn{
    border:1px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.08);
    color:#fff;
    padding:8px 12px;
    border-radius: 999px;
    font-weight:900;
    font-size:12px;
    cursor:pointer;
    user-select:none;
  }
  .ovBtn:hover{ background: rgba(255,255,255,.12); }
</style>

{{-- ✅ Overlay SOLO si está vacío Y (processing=true o processing=null “no sé”) --}}
@if($isEmpty && ($processing !== false))
  <div class="overlay show" id="batchOverlay" aria-hidden="true">
    <div class="grain"></div>
    <div class="box">
      <div class="boxTop">
        <div class="t">Procesando lote {{ $batchKey }}…</div>
        <div class="s" id="ovHint">Estamos extrayendo información de los documentos</div>
      </div>

      <div class="loader-wrapper" aria-label="Generando">
        <span class="loader-letter">G</span>
        <span class="loader-letter">e</span>
        <span class="loader-letter">n</span>
        <span class="loader-letter">e</span>
        <span class="loader-letter">r</span>
        <span class="loader-letter">a</span>
        <span class="loader-letter">n</span>
        <span class="loader-letter">d</span>
        <span class="loader-letter">o</span>
        <div class="loader"></div>
      </div>

      <div class="bar"><span id="ovBar"></span></div>

      <div style="margin-top:10px;color:rgba(255,255,255,.78);font-size:12px;font-weight:800;text-align:left">
        {{ $progressTxt }}
      </div>

      <div class="ovActions">
        <button type="button" class="ovBtn" id="btnStopRefresh">Detener auto-actualización</button>
        <button type="button" class="ovBtn" id="btnRetryNow">Reintentar ahora</button>
        <a class="ovBtn" href="{{ route('publications.index') }}" style="text-decoration:none;display:inline-flex;align-items:center;">Volver</a>
      </div>
    </div>
  </div>

  <script>
    (function(){
      const batchKey = @json($batchKey);
      const processing = @json($processing); // true|false|null
      const key = 'batch_reload_' + batchKey;

      // ✅ límite anti-bucle (≈ 20 recargas * 2.5s = 50s)
      const MAX = 20;
      const DELAY = 2500;

      const ovHint = document.getElementById('ovHint');
      const ovBar  = document.getElementById('ovBar');
      const btnStop = document.getElementById('btnStopRefresh');
      const btnRetry = document.getElementById('btnRetryNow');

      const setCount = (n) => localStorage.setItem(key, String(n));
      const getCount = () => parseInt(localStorage.getItem(key) || '0', 10);

      function stopAuto(msg){
        setCount(MAX);
        if (ovHint) ovHint.textContent = msg || 'Auto-actualización detenida. Presiona “Reintentar ahora” cuando quieras.';
        // “congela” la barra
        if (ovBar){
          ovBar.style.animation = 'none';
          ovBar.style.width = '100%';
        }
      }

      btnStop?.addEventListener('click', () => stopAuto());
      btnRetry?.addEventListener('click', () => { setCount(0); location.reload(); });

      // ✅ si el backend ya sabe que NO está procesando, no auto-recargues
      if (processing === false) {
        localStorage.removeItem(key);
        stopAuto('No hay resultados por ahora. Puedes reintentar manualmente.');
        return;
      }

      let n = getCount();

      // ✅ si ya nos pasamos del límite, ya NO recargues
      if (n >= MAX) {
        stopAuto('Aún no hay resultados. Puede tardar o el proceso falló. Presiona “Reintentar ahora” o vuelve más tarde.');
        return;
      }

      // ✅ sigue recargando (pero con límite)
      setCount(n + 1);
      setTimeout(() => location.reload(), DELAY);
    })();
  </script>
@endif

{{-- ✅ Si está vacío PERO processing=false (o ya se detuvo), muestra estado vacío normal --}}
@if($isEmpty && ($processing === false))
  <div class="emptyState">
    <div class="emptyTitle">Este lote aún no tiene resultados</div>
    <div class="emptySub">
      No hay documentos procesados para mostrar (o la extracción no generó datos).
      Puedes volver e intentar subir de nuevo o reintentar más tarde.
    </div>
    <div class="emptyActions">
      <a href="{{ route('publications.index') }}" class="btn btn-sm btn-primary">Volver</a>
      <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">Refrescar</a>
    </div>
  </div>
@endif

<div class="wrap" style="{{ ($isEmpty && ($processing !== false)) ? 'filter: blur(2px); opacity:.25; pointer-events:none;' : '' }}">
  <div class="top">
    <div>
      <h1>Lote {{ $batchKey }}</h1>
      <div class="sub">Subiste varios documentos; aquí están agrupados por RFC (si existe) o por nombre.</div>
    </div>
    <a href="{{ route('publications.index') }}" class="btn btn-sm btn-primary">Volver</a>
  </div>

  <div class="grid">
    @foreach($groups as $g)
      <div class="card">
        <div class="name">{{ $g['name'] }}</div>
        <div class="meta">
          <span>RFC: <b>{{ $g['rfc'] ?: '—' }}</b></span>
          <span>Docs: <b>{{ $g['count'] }}</b></span>
          <span>Total: <b>${{ number_format($g['sum'], 2) }}</b></span>
        </div>

        @foreach($g['docs'] as $d)
          <div class="doc">
            <div class="meta" style="margin-top:0">
              <span>Tipo: <b>{{ $d->document_type }}</b></span>
              <span>Fecha: <b>{{ $d->document_datetime?->format('Y-m-d H:i') ?? '—' }}</b></span>
              <span>Total: <b>${{ number_format((float)$d->total,2) }}</b></span>
            </div>
            <div class="meta">
              <span>UUID: <b>{{ data_get($d->ai_meta,'cfdi.uuid') ?: '—' }}</b></span>
              <span>Serie/Folio: <b>{{ data_get($d->ai_meta,'cfdi.serie') ?: '—' }} / {{ data_get($d->ai_meta,'cfdi.folio') ?: '—' }}</b></span>
              @if($d->publication_id)
                <span>Archivo: <a href="{{ route('publications.show', $d->publication_id) }}"><b>ver</b></a></span>
              @endif
            </div>
          </div>
        @endforeach

        @if(!empty($g['topItems']))
          <div class="items">
            <div class="meta" style="margin-top:0"><b>Resumen (top items)</b></div>
            @foreach($g['topItems'] as $it)
              <div class="it">
                <span>{{ \Illuminate\Support\Str::limit($it['item_name'], 40) }}</span>
                <span>Qty <b>{{ number_format((float)$it['qty'], 3) }}</b> · $ <b>{{ number_format((float)$it['spent'], 2) }}</b></span>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    @endforeach
  </div>
</div>

{{-- ✅ cuando YA hay datos, limpia el contador del loop --}}
@if(!$isEmpty)
  <script>
    (function(){
      const key = 'batch_reload_' + @json($batchKey);
      try { localStorage.removeItem(key); } catch(e){}
    })();
  </script>
@endif

@endsection