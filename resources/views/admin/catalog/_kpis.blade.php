{{-- Resumen del listado (con filtros aplicados). Se refresca por AJAX.
     Tarjetas limpias tipo Apple: cada una envuelve su propia mini-gráfica. --}}
@php
  $rTotal = (int) ($resumen->total ?? 0);
  $rCrit  = (int) ($resumen->criticos ?? 0);
  $rSin   = (int) ($resumen->sin_stock ?? 0);
  $rDest  = (int) ($resumen->destacados ?? 0);
  $rMl    = (int) ($resumen->en_ml ?? 0);
  $rValor = (float) ($resumen->valor ?? 0);

  // Publicación (respeta los demás filtros).
  $ePub  = (int) ($porEstado[1] ?? 0);
  $eBorr = (int) ($porEstado[0] ?? 0);
  $eOcul = (int) ($porEstado[2] ?? 0);
  $eTot  = max(1, $ePub + $eBorr + $eOcul);
  $degPub  = round($ePub / $eTot * 360);
  $degBorr = round(($ePub + $eBorr) / $eTot * 360);
  $pctPub  = round($ePub / $eTot * 100);

  // Salud de stock (segmentos que no se enciman y suman el total).
  $sSin  = min($rSin, $rTotal);
  $sCrit = max(0, min($rCrit - $rSin, $rTotal - $sSin));
  $sSan  = max(0, $rTotal - $sSin - $sCrit);
  $sTot  = max(1, $rTotal);
  $pc = fn ($n) => round($n / $sTot * 100, 1);

  $pctMl   = $rTotal > 0 ? round($rMl / $rTotal * 100) : 0;
  $pctDest = $rTotal > 0 ? round($rDest / $rTotal * 100) : 0;
@endphp

<div class="stats">
  {{-- Productos + dona de publicación --}}
  <div class="stat">
    <span class="stat-label">{{ $hasFilters ? 'Productos en la vista' : 'Productos' }}</span>
    <div class="stat-row">
      <b class="stat-num">{{ number_format($rTotal) }}</b>
      <div class="donut" style="--seg:conic-gradient(var(--ui-ok) 0 {{ $degPub }}deg, var(--c-slate) {{ $degPub }}deg {{ $degBorr }}deg, var(--c-violet) {{ $degBorr }}deg 360deg);">
        <div class="donut-hole">{{ $pctPub }}%</div>
      </div>
    </div>
    <ul class="leg">
      <li><i style="background:var(--ui-ok)"></i>Publicado <b>{{ number_format($ePub) }}</b></li>
      <li><i style="background:var(--c-slate)"></i>Borrador <b>{{ number_format($eBorr) }}</b></li>
      <li><i style="background:var(--c-violet)"></i>Oculto <b>{{ number_format($eOcul) }}</b></li>
    </ul>
  </div>

  {{-- Salud de stock + barra apilada --}}
  <div class="stat">
    <div class="stat-head">
      <span class="stat-label">Salud de stock</span>
      @if($rCrit > 0 && $filters['stock'] !== 'critical')
        <a class="stat-link" href="{{ $urlCon(['stock' => 'critical']) }}" data-ajax>Ver críticos</a>
      @endif
    </div>
    <b class="stat-num">{{ number_format($sSan) }}</b>
    <span class="stat-cap">en buen estado</span>
    <div class="stackbar" role="img" aria-label="Salud de stock">
      <span class="seg seg-ok"  style="width:{{ $pc($sSan) }}%"></span>
      <span class="seg seg-amb" style="width:{{ $pc($sCrit) }}%"></span>
      <span class="seg seg-red" style="width:{{ $pc($sSin) }}%"></span>
    </div>
    <ul class="leg">
      <li><i style="background:var(--ui-ok)"></i>Sano <b>{{ number_format($sSan) }}</b></li>
      <li><i style="background:var(--ui-warn)"></i>Crítico <b>{{ number_format($sCrit) }}</b></li>
      <li><i style="background:var(--ui-danger)"></i>Sin <b>{{ number_format($sSin) }}</b></li>
    </ul>
  </div>

  {{-- Valor del inventario --}}
  <div class="stat">
    <span class="stat-label">Valor del inventario</span>
    <b class="stat-num">${{ number_format($rValor, 0) }}</b>
    <span class="stat-cap">{{ $hasFilters ? 'de esta vista' : 'inventario total' }}</span>
  </div>

  {{-- Alcance: Mercado Libre y destacados --}}
  <div class="stat">
    <span class="stat-label">Alcance</span>
    <div class="mbar">
      <div class="mbar-top"><span>Mercado Libre</span><b>{{ number_format($rMl) }}</b></div>
      <div class="mbar-track"><span class="mbar-fill mbar-blue" style="width:{{ $pctMl }}%"></span></div>
    </div>
    <div class="mbar">
      <div class="mbar-top"><span>Destacados</span><b>{{ number_format($rDest) }}</b></div>
      <div class="mbar-track"><span class="mbar-fill mbar-violet" style="width:{{ $pctDest }}%"></span></div>
    </div>
  </div>
</div>
