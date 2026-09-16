{{-- Resumen del listado con los filtros aplicados. Se refresca por AJAX. --}}
<div class="kpi is-blue">
  <span class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg></span>
  <div><b>{{ number_format((int) ($resumen->total ?? 0)) }}</b><span>{{ $hasFilters ? 'Productos en esta vista' : 'Productos' }}</span></div>
</div>
<div class="kpi is-amb">
  <span class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg></span>
  <div><b>{{ number_format((int) ($resumen->criticos ?? 0)) }}</b><span>Con stock crítico</span></div>
  @if((int)($resumen->criticos ?? 0) > 0 && $filters['stock'] !== 'critical')
    <a href="{{ $urlCon(['stock' => 'critical']) }}" data-ajax>Ver</a>
  @endif
</div>
<div class="kpi is-red">
  <span class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12h8"/></svg></span>
  <div><b>{{ number_format((int) ($resumen->sin_stock ?? 0)) }}</b><span>Sin existencia</span></div>
  @if((int)($resumen->sin_stock ?? 0) > 0 && $filters['stock'] !== 'empty')
    <a href="{{ $urlCon(['stock' => 'empty']) }}" data-ajax>Ver</a>
  @endif
</div>
<div class="kpi">
  <span class="kpi-ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
  <div><b>${{ number_format((float) ($resumen->valor ?? 0), 0) }}</b><span>Valor del inventario</span></div>
</div>
